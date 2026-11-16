#include <WiFi.h>
#include <WebServer.h>
#include <HTTPClient.h>
#include <string.h>

const char* ssid     = "SSID";       // Ganti dengan nama WiFi pabrik/laptop
const char* password = "12345678";   // Ganti dengan password WiFi

const char* server_ip = "10.171.46.201:8000"; 

// ================================================================
// KONFIGURASI IP MANUAL / STATIC IP ESP32 DEVKIT
// ================================================================
// true  = Menggunakan IP Statis / Manual yang ditentukan di bawah
// false = Otomatis meminta IP dari router (DHCP)
const bool USE_STATIC_IP = true;

IPAddress devkit_static_ip(10, 171, 46, 102);  // Ganti dengan IP DevKit yang Anda inginkan
IPAddress devkit_gateway(10, 171, 46, 193);    // Gateway router Anda (dari ipconfig)
IPAddress devkit_subnet(255, 255, 255, 0);     // Subnet mask
IPAddress devkit_dns(8, 8, 8, 8);              // DNS opsional

String dashboard_trigger_url = String("http://") + server_ip + "/api/sensor-trigger";
String dashboard_confirm_url = String("http://") + server_ip + "/api/confirm-hanger-output";

WebServer server(80);

const int PIN_BTN_ON   = 13; // Push Button HIJAU: NO (Normally Open)   -> Normal=HIGH (pullup), Ditekan=LOW (START Mesin)
const int PIN_BTN_OFF  = 12; // Push Button MERAH: NC (Normally Closed) -> Normal=LOW (ke GND), Ditekan=HIGH (STOP Mesin)
const int PIN_SENS_IN  = 25; // Sensor Inlet (Input Hanger)
const int PIN_SENS_OUT = 27; // Sensor Outlet (Kamera Shutter)

// ================================================================
// KONFIGURASI TIPE LOGIKA SENSOR (PIN 25 & PIN 27)
// ================================================================
// true  = Active-LOW  (Standby = HIGH/1, Saat deteksi/kedip = LOW/0  -> Interrupt FALLING, +1 per barang)
// false = Active-HIGH (Standby = LOW/0,  Saat deteksi/kedip = HIGH/1 -> Interrupt RISING,  +1 per barang)

// PIN 25 (Inlet Sensor melalui Modul Optocoupler PC817):
// Dengan perkabelan NPN (IN1 ke +24V, GND input ke kabel Hitam sensor):
// - Standby (LED PC817 mati)   : Output ditarik pullup ke 3.3V (HIGH / 1)
// - Deteksi (LED PC817 nyala)  : Output ditarik transistor ke 0V (LOW / 0)
// Sehingga logikanya adalah Active-LOW (true), persis sama dengan Pin 27.
const bool SENS_IN_ACTIVE_LOW  = true;

// PIN 27 (Outlet Sensor - Shutter Kamera):
// Sensor NPN langsung (Active-LOW: Standby = HIGH, Saat deteksi = LOW).
const bool SENS_OUT_ACTIVE_LOW = true;

// Durasi standby bersih (ms) setelah objek dilepas/lampu sensor mati sebelum siap hitung barang berikutnya.
// Deteksi saat barang masuk tetap INSTAN 0 ms! Ini khusus mencegah pantulan saat dilepas ikut terhitung #2.
const unsigned long RELEASE_SETTLE_MS = 100;

const int PIN_FLASH    = 26; // Lampu Flash / Penerangan Output (Area Kamera)

// ================================================================
// KONFIGURASI LAMPU FLASH OUTPUT (AREA KAMERA - PIN 26)
// ================================================================
// 1 = SOLID (Nyala Terus / Selalu Menyala Konstan) -> Cahaya stabil tanpa flicker
// 2 = STROBE (Hanya nyala sejenak saat ada hanger/barang lewat di sensor 2)
// 0 = MATI (Off)
int flashOutputMode    = 1;   // Default: 1 (SOLID / Nyala Terus)
int flashBrightness    = 255; // Kecerahan lampu PWM (0 - 255)

// RS-485 to TTL UART (Serial2)
#define RS485_RX_PIN 16 // RO Modul RS485 ke Pin 16 ESP32
#define RS485_TX_PIN 17 // DI Modul RS485 ke Pin 17 ESP32

bool isProduksi = false;
int hangerInCount  = 0;
int hangerOutCount = 0;

bool lastBtnOnState   = HIGH; // NO (Pin 13 Hijau) default = HIGH (floating, ditarik pullup)
bool lastBtnOffState  = LOW;  // NC (Pin 12 Merah) default = LOW (tersambung ke GND)

// State Interrupt & Re-Arm Bersih Photosensor (0 ms saat masuk deteksi, anti double-count saat dilepas)
volatile bool sensInEdgeFlag  = false;
volatile bool sensOutEdgeFlag = false;

bool sensInArmed              = true;
unsigned long sensInIdleStart = 0;
bool sensOutArmed             = true;
unsigned long sensOutIdleStart = 0;

void IRAM_ATTR handleSensInISR() {
  sensInEdgeFlag = true;
}

void IRAM_ATTR handleSensOutISR() {
  sensOutEdgeFlag = true;
}

bool isSensorRawActive(int pin, bool activeLow) {
  int activeLevel = activeLow ? LOW : HIGH;
  return digitalRead(pin) == activeLevel;
}

// Memicu counter +1 secara INSTAN (0 ms) begitu masuk kondisi deteksi,
// lalu menahan pantulan sinyal selama objek masih di depan sensor maupun saat baru dilepas
// hingga sensor kembali standby stabil selama RELEASE_SETTLE_MS.
bool checkSensorTrigger(int pin, bool activeLow, volatile bool &edgeFlag, bool &armed, unsigned long &idleStartMs, unsigned long now) {
  bool hasEdge = false;
  if (edgeFlag) {
    edgeFlag = false;
    hasEdge = true;
  }

  bool isIdle = !isSensorRawActive(pin, activeLow);

  if (armed) {
    if (hasEdge || !isIdle) {
      armed = false;
      idleStartMs = 0;
      return true;
    }
    return false;
  }

  if (isIdle && !hasEdge) {
    if (idleStartMs == 0) {
      idleStartMs = now;
    } else if (now - idleStartMs >= RELEASE_SETTLE_MS) {
      armed = true;
      idleStartMs = 0;
    }
  } else {
    idleStartMs = 0;
  }
  return false;
}

// Timer non-blocking untuk mematikan lampu flash & reconnect WiFi (tanpa delay)
unsigned long flashOffTime    = 0;
unsigned long lastWifiRetryMs = 0;

// ================================================================
// ANTREAN HTTP ASINKRON (CORE 0) - AGAR LOOP SENSOR TIDAK PERNAH NGE-LAG!
// ================================================================
typedef struct {
  char tipe[16];
  int val;
} TriggerEventMsg;

QueueHandle_t triggerQueue = NULL;

void httpWorkerTask(void* pvParameters) {
  TriggerEventMsg msg;
  for (;;) {
    if (xQueueReceive(triggerQueue, &msg, portMAX_DELAY) == pdTRUE) {
      if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        String targetUrl = dashboard_trigger_url + "?type=" + String(msg.tipe) + "&val=" + String(msg.val);
        
        http.begin(targetUrl);
        http.setConnectTimeout(800); // Timeout koneksi cepat 800 ms (bukan 5000 ms default)
        http.setTimeout(1000);       // Timeout baca respons maks 1000 ms
        http.setReuse(false);
        int httpCode = http.GET();
        
        if (httpCode > 0) {
          Serial.printf("[WEB-API] Trigger '%s' (val=%d) berhasil dikirim ke Web Dashboard (HTTP %d)\n", msg.tipe, msg.val, httpCode);
        } else {
          Serial.printf("[WEB-API] Gagal kirim trigger '%s'. Error: %s\n", msg.tipe, http.errorToString(httpCode).c_str());
        }
        http.end();
      } else {
        Serial.printf("[WEB-API] Trigger '%s' dilewati (WiFi belum terhubung)\n", msg.tipe);
      }
    }
  }
}

void kirimTriggerKeWeb(String tipe, int val = 1) {
  if (triggerQueue == NULL) return;
  TriggerEventMsg msg;
  memset(&msg, 0, sizeof(msg));
  strncpy(msg.tipe, tipe.c_str(), sizeof(msg.tipe) - 1);
  msg.val = val;
  if (xQueueSend(triggerQueue, &msg, 0) != pdTRUE) {
    Serial.printf("[WEB-API] Antrean HTTP penuh, trigger '%s' dilewati\n", msg.tipe);
  }
}

// ================================================================
// PENGELOLAAN WEB API LOKAL (REST API)
// ================================================================
void setCORS() {
  server.sendHeader("Access-Control-Allow-Origin", "*");
  server.sendHeader("Access-Control-Allow-Methods", "GET, POST, OPTIONS");
  server.sendHeader("Access-Control-Allow-Headers", "*");
}

void handleKabel() {
  setCORS();
  if (server.hasArg("tipe") && server.hasArg("val")) {
    String tipe = server.arg("tipe");
    String val = server.arg("val");
    Serial2.print(tipe + ":" + val + "\n");
    Serial.println("[RS485-TX] Kirim: " + tipe + ":" + val);
  }
  server.send(200, "text/plain", "OK");
}

void handleLampu() {
  setCORS();
  // 1. Ganti mode lampu (?mode=solid / ?mode=strobe / ?mode=off)
  if (server.hasArg("mode")) {
    String mode = server.arg("mode");
    mode.toLowerCase();
    if (mode == "solid" || mode == "1") {
      flashOutputMode = 1;
      if (server.hasArg("val")) flashBrightness = constrain(server.arg("val").toInt(), 0, 255);
      ledcWrite(PIN_FLASH, flashBrightness);
      Serial.printf("[FLASH] Mode diset ke SOLID (Nyala Terus, Nilai: %d)\n", flashBrightness);
    } 
    else if (mode == "strobe" || mode == "2") {
      flashOutputMode = 2;
      ledcWrite(PIN_FLASH, 0); // Standby mati, hanya flash saat sensor lewat
      Serial.println("[FLASH] Mode diset ke STROBE (Flash saat ada barang lewat)");
    } 
    else if (mode == "off" || mode == "0") {
      flashOutputMode = 0;
      ledcWrite(PIN_FLASH, 0);
      Serial.println("[FLASH] Lampu Output dimatikan (OFF)");
    }
  }
  // 2. Kontrol kecerahan langsung (?val=...)
  else if (server.hasArg("val")) {
    int val = constrain(server.arg("val").toInt(), 0, 255);
    flashBrightness = val;
    if (val > 0) {
      flashOutputMode = 1;
      ledcWrite(PIN_FLASH, flashBrightness);
    } else {
      flashOutputMode = 0;
      ledcWrite(PIN_FLASH, 0);
    }
    Serial.printf("[FLASH] Kecerahan diset ke: %d\n", val);
  }
  
  String res = "{\"mode\":" + String(flashOutputMode) + ",\"brightness\":" + String(flashBrightness) + "}";
  server.send(200, "application/json", res);
}

void handleStatus() {
  setCORS();
  String json = "{\"isProduksi\":" + String(isProduksi ? "true" : "false") + 
                ",\"in\":" + String(hangerInCount) + 
                ",\"out\":" + String(hangerOutCount) + 
                ",\"flash_mode\":" + String(flashOutputMode) + 
                ",\"flash_brightness\":" + String(flashBrightness) + "}";
  server.send(200, "application/json", json);
}

void handleOptions() {
  setCORS();
  server.send(204);
}

// ================================================================
// SETUP
// ================================================================
void setup() {
  Serial.begin(115200);
  
  // RS-485 to TTL UART ke ESP32-CAM / Sensor Bus (timeout 10ms agar tidak memblokir loop)
  Serial2.begin(115200, SERIAL_8N1, RS485_RX_PIN, RS485_TX_PIN);
  Serial2.setTimeout(10);

  // Pin Tombol Push Button & Sensor
  pinMode(PIN_BTN_ON, INPUT_PULLUP);
  pinMode(PIN_BTN_OFF, INPUT_PULLUP);
  pinMode(PIN_SENS_IN, SENS_IN_ACTIVE_LOW ? INPUT_PULLUP : INPUT_PULLDOWN);
  pinMode(PIN_SENS_OUT, SENS_OUT_ACTIVE_LOW ? INPUT_PULLUP : INPUT_PULLDOWN);

  // Tunggu sejenak agar tegangan pin stabil saat bootup
  delay(100);
  lastBtnOnState  = digitalRead(PIN_BTN_ON);
  lastBtnOffState = digitalRead(PIN_BTN_OFF);

  ledcAttach(PIN_FLASH, 5000, 8);
  if (flashOutputMode == 1) {
    ledcWrite(PIN_FLASH, flashBrightness); // Langsung nyala SOLID konstan
    Serial.printf("[FLASH] Lampu Output Pin 26 aktif SOLID (Kecerahan: %d)\n", flashBrightness);
  } else {
    ledcWrite(PIN_FLASH, 0);
  }

  Serial.print("Menghubungkan ke WiFi: ");
  Serial.println(ssid);
  WiFi.mode(WIFI_STA);
  WiFi.setSleep(false);
  WiFi.setAutoReconnect(true);

  if (USE_STATIC_IP) {
    if (!WiFi.config(devkit_static_ip, devkit_gateway, devkit_subnet, devkit_dns)) {
      Serial.println("[WIFI] Konfigurasi Static IP Gagal! Menggunakan DHCP router...");
    } else {
      Serial.print("[WIFI] Mode Static IP Aktif: ");
      Serial.println(devkit_static_ip);
    }
  }

  WiFi.begin(ssid, password);
  unsigned long wifiStart = millis();
  while (WiFi.status() != WL_CONNECTED && (millis() - wifiStart < 8000)) { 
    delay(250); 
    Serial.print(".");
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("[WIFI] Berhasil terhubung!");
    // Otomatis sinkronkan IP DevKit ke Dashboard Laragon
    HTTPClient httpSync;
    String setIpUrl = String("http://") + server_ip + "/api/set-devkit-ip?ip=" + WiFi.localIP().toString();
    httpSync.begin(setIpUrl);
    int syncCode = httpSync.GET();
    Serial.printf("[DEVKIT-SYNC] Auto-register IP ke Dashboard: %s (Status: %d)\n", setIpUrl.c_str(), syncCode);
    httpSync.end();
  } else {
    Serial.println("[WIFI] Belum terhubung saat boot, lanjutkan sensor & reconnect otomatis di background...");
  }
  
  Serial.println("==========================================");
  Serial.println("  ESP32 DEVKIT (RS-485 + HANGER TRIGGER)  ");
  Serial.println("==========================================");
  Serial.print("IP ESP32      : "); Serial.println(WiFi.localIP());
  Serial.print("Target Server : "); Serial.println(server_ip);
  Serial.println("==========================================");

  triggerQueue = xQueueCreate(16, sizeof(TriggerEventMsg));
  if (triggerQueue != NULL) {
    xTaskCreatePinnedToCore(httpWorkerTask, "HttpWorkerTask", 8192, NULL, 1, NULL, 0);
  }

  server.on("/kabel", HTTP_GET, handleKabel);
  server.on("/kabel", HTTP_OPTIONS, handleOptions);
  
  server.on("/lampu", HTTP_GET, handleLampu);
  server.on("/lampu", HTTP_OPTIONS, handleOptions);
  
  server.on("/status-mesin", HTTP_GET, handleStatus);
  server.on("/status-mesin", HTTP_OPTIONS, handleOptions);

  server.on("/trigger-in", HTTP_GET, []() {
    setCORS(); 
    server.send(200, "text/plain", "OK"); 
    hangerInCount++;
    kirimTriggerKeWeb("input", 1);
  });
  
  server.on("/trigger-out", HTTP_GET, []() {
    setCORS(); 
    server.send(200, "text/plain", "OK"); 
    hangerOutCount++;
    kirimTriggerKeWeb("output", 1);
  });
  
  server.begin();
  delay(200);
  lastBtnOnState  = digitalRead(PIN_BTN_ON);
  lastBtnOffState = digitalRead(PIN_BTN_OFF);

  int rawSensIn      = digitalRead(PIN_SENS_IN);
  int rawSensOut     = digitalRead(PIN_SENS_OUT);
  bool bootInActive  = isSensorRawActive(PIN_SENS_IN, SENS_IN_ACTIVE_LOW);
  bool bootOutActive = isSensorRawActive(PIN_SENS_OUT, SENS_OUT_ACTIVE_LOW);

  sensInEdgeFlag   = false;
  sensOutEdgeFlag  = false;
  sensInArmed      = !bootInActive;
  sensInIdleStart  = 0;
  sensOutArmed     = !bootOutActive;
  sensOutIdleStart = 0;

  int interruptModeIn  = SENS_IN_ACTIVE_LOW  ? FALLING : RISING;
  int interruptModeOut = SENS_OUT_ACTIVE_LOW ? FALLING : RISING;
  attachInterrupt(digitalPinToInterrupt(PIN_SENS_IN), handleSensInISR, interruptModeIn);
  attachInterrupt(digitalPinToInterrupt(PIN_SENS_OUT), handleSensOutISR, interruptModeOut);

  Serial.println("------------------------------------------");
  Serial.printf("[PIN INITIAL STATE]\n");
  Serial.printf(" - Pin 13 (PB HIJAU ON  - NO) : %s\n", lastBtnOnState == LOW ? "LOW (Tertutup)" : "HIGH (Standby)");
  Serial.printf(" - Pin 12 (PB MERAH OFF - NC) : %s\n", lastBtnOffState == LOW ? "LOW (Standby)" : "HIGH (Terbuka)");
  Serial.printf(" - Pin %d (INLET via PC817)   : %s (Raw: %d, Mode: %s, Intr: %s)\n",
                PIN_SENS_IN, bootInActive ? "AKTIF (Terdeteksi)" : "Standby", rawSensIn,
                SENS_IN_ACTIVE_LOW ? "Active-LOW" : "Active-HIGH",
                SENS_IN_ACTIVE_LOW ? "FALLING" : "RISING");
  Serial.printf(" - Pin %d (OUTLET Kamera)     : %s (Raw: %d, Mode: %s, Intr: %s)\n",
                PIN_SENS_OUT, bootOutActive ? "AKTIF (Terdeteksi)" : "Standby", rawSensOut,
                SENS_OUT_ACTIVE_LOW ? "Active-LOW" : "Active-HIGH",
                SENS_OUT_ACTIVE_LOW ? "FALLING" : "RISING");
  Serial.printf(" - Release Settle Time        : %lu ms\n", RELEASE_SETTLE_MS);
  if (bootInActive) {
    Serial.printf(" [PERINGATAN] Pin %d (PC817) terbaca AKTIF saat standby (Raw: %d)!\n", PIN_SENS_IN, rawSensIn);
    Serial.printf("             Jika saat standby lampu sensor MATI, ubah SENS_IN_ACTIVE_LOW = %s di baris 44.\n",
                  SENS_IN_ACTIVE_LOW ? "false" : "true");
  }
  if (bootOutActive) {
    Serial.printf(" [PERINGATAN] Pin %d terbaca AKTIF saat standby (Raw: %d)!\n", PIN_SENS_OUT, rawSensOut);
    Serial.printf("             Jika saat standby lampu sensor MATI, ubah SENS_OUT_ACTIVE_LOW = %s di baris 48.\n",
                  SENS_OUT_ACTIVE_LOW ? "false" : "true");
  }
  Serial.printf(" - Status Mesin Awal          : %s\n", isProduksi ? "AKTIF (ON)" : "STANDBY (OFF)");
  Serial.println("------------------------------------------");
  Serial.println(">> ESP32 SIAP! (Interrupt 0ms + HTTP Async Core 0) <<\n");
}

void handleRs485Line(String dataRs485, unsigned long now) {
  dataRs485.trim();
  if (dataRs485.length() == 0) return;

  Serial.println("[RS485-RX] Diterima: " + dataRs485);

  if (dataRs485.indexOf("IN") >= 0 || dataRs485 == "1") {
    hangerInCount++;
    Serial.println("[AUTO-MARK] Hanger Masuk Terdeteksi via RS-485 -> Kirim Nilai 1!");
    kirimTriggerKeWeb("input", 1);
  } else if (dataRs485.indexOf("OUT") >= 0 || dataRs485 == "2") {
    hangerOutCount++;
    Serial.println("[AUTO-SHUTTER] Hanger Output Terdeteksi via RS-485 -> Trigger Kamera!");
    if (flashOutputMode == 2) {
      ledcWrite(PIN_FLASH, flashBrightness > 0 ? flashBrightness : 220);
      flashOffTime = now + 150; 
    }
    kirimTriggerKeWeb("output", 1);
  }
}

void loop() {
  server.handleClient();
  unsigned long now = millis();

  if (WiFi.status() != WL_CONNECTED && (now - lastWifiRetryMs >= 10000)) {
    lastWifiRetryMs = now;
    WiFi.reconnect();
  }

  if (flashOffTime > 0 && (long)(now - flashOffTime) >= 0) {
    ledcWrite(PIN_FLASH, 0);
    flashOffTime = 0;
  }

  static String rs485Buffer = "";
  while (Serial2.available()) {
    char c = (char)Serial2.read();
    if (c == '\n' || c == '\r') {
      handleRs485Line(rs485Buffer, now);
      rs485Buffer = "";
    } else if (rs485Buffer.length() < 64) {
      rs485Buffer += c;
    }
  }

  bool currentBtnOnState = digitalRead(PIN_BTN_ON);
  if (currentBtnOnState == LOW && lastBtnOnState == HIGH) {
    if (!isProduksi) {
      isProduksi = true; 
      hangerInCount = 0; 
      hangerOutCount = 0;
      Serial.println("\n>>> MESIN PRODUKSI DI-START (OFF -> ON, PB HIJAU Pin 13) <<<");
      kirimTriggerKeWeb("btn_on", 1);
    } 
  } 
  lastBtnOnState = currentBtnOnState;

  bool currentBtnOffState = digitalRead(PIN_BTN_OFF);
  if (currentBtnOffState == HIGH && lastBtnOffState == LOW) {
    if (isProduksi) { 
      isProduksi = false; 
      Serial.println("\n>>> MESIN PRODUKSI DI-STOP (ON -> OFF, PB MERAH Pin 12) <<<"); 
      kirimTriggerKeWeb("btn_off", 0);
    }
  } 
  lastBtnOffState = currentBtnOffState;

  if (checkSensorTrigger(PIN_SENS_IN, SENS_IN_ACTIVE_LOW, sensInEdgeFlag, sensInArmed, sensInIdleStart, now)) {
    hangerInCount++;
    Serial.printf("[AUTO-MARK] Hanger #%d Masuk ke Line (Pin %d) -> Kirim Nilai 1 ke Web!\n", hangerInCount, PIN_SENS_IN);
    kirimTriggerKeWeb("input", 1);
  }

  if (checkSensorTrigger(PIN_SENS_OUT, SENS_OUT_ACTIVE_LOW, sensOutEdgeFlag, sensOutArmed, sensOutIdleStart, now)) {
    hangerOutCount++;
    Serial.printf("[AUTO-SHUTTER] Hanger #%d Tiba di Depan Kamera (Pin %d) -> Kirim Nilai 1 ke Web!\n", hangerOutCount, PIN_SENS_OUT);
    if (flashOutputMode == 2) {
      ledcWrite(PIN_FLASH, flashBrightness > 0 ? flashBrightness : 220);
      flashOffTime = now + 200;
    }
    kirimTriggerKeWeb("output", 1);
  }
}