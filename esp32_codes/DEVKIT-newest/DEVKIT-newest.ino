#include <WiFi.h>
#include <WebServer.h>
#include <HTTPClient.h>
#include <string.h>

// ================================================================
// KONFIGURASI JARINGAN & SERVER DASHBOARD
// ================================================================
const char* ssid     = "SSID";       // Ganti dengan nama WiFi pabrik/laptop
const char* password = "12345678";   // Ganti dengan password WiFi

const char* server_ip = "10.144.103.201:8000"; 

// Konfigurasi Static IP ESP32
const bool USE_STATIC_IP = true;
IPAddress devkit_static_ip(10, 144, 103, 102);
IPAddress devkit_gateway(10, 144, 103, 70);
IPAddress devkit_subnet(255, 255, 255, 0);
IPAddress devkit_dns(8, 8, 8, 8);

String dashboard_trigger_url = String("http://") + server_ip + "/api/sensor-trigger";

WebServer server(80);

// ================================================================
// PIN ASSIGNMENT (ESP32 DEVKIT V1) - KONSEP 1 BOX INSPEKSI KAMERA
// ================================================================
const int PIN_BTN_ON   = 13; // Push Button HIJAU: NO (Normally Open)   -> Normal=HIGH, Tekan=LOW (START Mesin)
const int PIN_BTN_OFF  = 12; // Push Button MERAH: NC (Normally Closed) -> Normal=LOW, Tekan=HIGH (STOP Mesin)

// KEDUA SENSOR DALAM 1 BOX (TIPE NPN LANGSUNG KE GPIO ESP32):
// - PIN 27: Sensor Masuk ke dalam Box (Inlet Box) -> Pemicu masuk / deteksi awal
// - PIN 25: Sensor Keluar dari Box (Outlet Box)   -> Pemicu konfirmasi Roboflow & hitung pcs
const int PIN_SENS_IN  = 27; // Sensor Masuk Box (Inlet)
const int PIN_SENS_OUT = 25; // Sensor Keluar Box (Outlet)

const int PIN_FLASH    = 26; // Lampu Flash / Penerangan Dalam Box (Area Kamera)

// RS-485 to TTL UART (Serial2)
#define RS485_RX_PIN 16
#define RS485_TX_PIN 17

// ================================================================
// LOGIKA SENSOR MURNI DIGITAL (TEGANGAN HIGH & LOW)
// ================================================================
// Sensor NPN Open-Collector (Langsung ke ESP32 dengan INPUT_PULLUP):
// - Standby (Tidak ada hanger) : Tegangan HIGH (3.3V) ditarik internal pullup
// - Deteksi (Ada hanger)       : Transistor NPN menarik pin ke 0V (LOW / GND)
const int SENSOR_STANDBY = HIGH; // 3.3V
const int SENSOR_DETECT  = LOW;  // 0V (GND)

// ================================================================
// DELAY ANTI-DOUBLE TRIGGER (LOCKOUT & STABILISASI LEPAS)
// ================================================================
// 1. LOCKOUT_MS: Durasi sensor DIKUNCI MATI setelah mendeteksi hanger pertama.
//    Selama masa ini (misal 1.2 detik), tiang kedua, celah berlubang, atau getaran
//    pada hanger yang sama TIDAK BISA memicu hitungan ganda (2x).
const unsigned long LOCKOUT_INLET_MS   = 2500; // 1200 ms (1.2 detik) jeda kunci Pin 27
const unsigned long LOCKOUT_OUTLET_MS  = 2500; // 1200 ms (1.2 detik) jeda kunci Pin 25

// 2. RELEASE_MS: Setelah masa lockout selesai, sinyal harus stabil KOSONG (HIGH)
//    terus-menerus selama durasi ini sebelum sensor siap menerima hanger berikutnya.
const unsigned long RELEASE_INLET_MS   = 200;  // 200 ms sinyal bersih konstan
const unsigned long RELEASE_OUTLET_MS  = 200;  // 200 ms sinyal bersih konstan

// State Latch Masuk Box (Pin 27)
bool inletArmed = true;           // true = siap mendeteksi hanger baru masuk
unsigned long lastInletTriggerMs  = 0;
unsigned long inletClearSinceMs   = 0;

// State Latch Keluar Box (Pin 25)
bool outletArmed = true;          // true = siap mendeteksi hanger baru keluar
unsigned long lastOutletTriggerMs = 0;
unsigned long outletClearSinceMs  = 0;

// ================================================================
// VARIABEL STATUS PRODUKSI & COUNTER
// ================================================================
bool isProduksi    = true; // DEFAULT: LANGSUNG AKTIF (Langsung siap hitung saat dinyalakan)
int hangerInCount  = 0;
int hangerOutCount = 0;

int flashOutputMode = 1;   // 1 = SOLID (Nyala terus di dalam box), 2 = STROBE, 0 = OFF
int flashBrightness = 255; // Kecerahan PWM (0 - 255)
unsigned long flashOffTime = 0;

bool lastBtnOnState  = HIGH;
bool lastBtnOffState = LOW;

unsigned long lastWifiRetryMs = 0;

// ================================================================
// ANTREAN HTTP ASINKRON (CORE 0) - AGAR LOOP SENSOR TIDAK PERNAH LAG!
// ================================================================
typedef struct {
  char url[256];
  char label[36];
} TriggerEventMsg;

QueueHandle_t triggerQueue = NULL;

void httpWorkerTask(void* pvParameters) {
  TriggerEventMsg msg;
  for (;;) {
    if (xQueueReceive(triggerQueue, &msg, portMAX_DELAY) == pdTRUE) {
      if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        http.begin(msg.url);
        http.setConnectTimeout(1500); // 1.5 detik toleransi koneksi
        http.setTimeout(2500);        // 2.5 detik toleransi respons server
        http.setReuse(false);
        int httpCode = http.GET();
        
        if (httpCode > 0) {
          Serial.printf("[WEB-API] %s terkirim ke Web Dashboard (HTTP %d)\n", msg.label, httpCode);
        } else {
          Serial.printf("[WEB-API] %s tertunda. Status: %s\n", msg.label, http.errorToString(httpCode).c_str());
        }
        http.end();
      }
    }
  }
}

void kirimTriggerKeWeb(String tipe, int val = 1) {
  if (triggerQueue == NULL) return;
  TriggerEventMsg msg;
  memset(&msg, 0, sizeof(msg));
  String targetUrl = dashboard_trigger_url + "?type=" + tipe + "&val=" + String(val);
  strncpy(msg.url, targetUrl.c_str(), sizeof(msg.url) - 1);
  snprintf(msg.label, sizeof(msg.label), "Trigger '%s'", tipe.c_str());
  xQueueSend(triggerQueue, &msg, 0);
}

// ================================================================
// FUNGSI MANAJEMEN STATUS MESIN
// ================================================================
void printTotalCounterSummary() {
  int inBox = hangerInCount - hangerOutCount;
  if (inBox < 0) inBox = 0;

  Serial.println("\n==================================================");
  Serial.printf("📊 [TOTAL COUNTER BOX KAMERA (PIN 27 IN & PIN 25 OUT)]\n");
  Serial.printf("   🟢 TOTAL MASUK BOX  (Pin %d) : %d Hanger\n", PIN_SENS_IN, hangerInCount);
  Serial.printf("   🔵 TOTAL KELUAR BOX (Pin %d) : %d Hanger\n", PIN_SENS_OUT, hangerOutCount);
  Serial.printf("   🟡 HANGER DI DALAM BOX       : %d Hanger\n", inBox);
  Serial.printf("   ⚙️ Status Mesin              : %s\n", isProduksi ? "PRODUKSI (RUNNING)" : "STANDBY (STOP)");
  Serial.println("==================================================");
}

void startProduksi(const char* triggerSource) {
  if (isProduksi) {
    Serial.printf("ℹ️ [INFO] Mesin sudah dalam status AKTIF (Trigger via %s).\n", triggerSource);
    return;
  }
  isProduksi         = true;
  hangerInCount      = 0;
  hangerOutCount     = 0;
  inletArmed         = true;
  outletArmed        = true;
  lastInletTriggerMs = 0;
  lastOutletTriggerMs= 0;
  inletClearSinceMs  = 0;
  outletClearSinceMs = 0;

  if (flashOutputMode == 1) {
    ledcWrite(PIN_FLASH, flashBrightness);
  }

  Serial.println("\n==================================================");
  Serial.printf("🚀 >>> MESIN PRODUKSI DI-START [via %s] <<<\n", triggerSource);
  Serial.println("🟢 STATUS: AKTIF (ON) - Fotosel Box Siap Hitung!");
  Serial.println("📊 Counter Direset: TOTAL MASUK = 0 | TOTAL KELUAR = 0 | DALAM BOX = 0");
  Serial.println("==================================================");

  kirimTriggerKeWeb("btn_on", 1);
}

void stopProduksi(const char* triggerSource) {
  if (!isProduksi) {
    Serial.printf("ℹ️ [INFO] Mesin sudah dalam status STANDBY (Trigger via %s).\n", triggerSource);
    return;
  }
  isProduksi = false;

  ledcWrite(PIN_FLASH, 0); // Matikan penerangan saat standby

  Serial.println("\n==================================================");
  Serial.printf("🛑 >>> MESIN PRODUKSI DI-STOP [via %s] <<<\n", triggerSource);
  Serial.println("🔒 STATUS: STANDBY (OFF) - Fotosel Box Dinonaktifkan Sementara.");
  printTotalCounterSummary();
  Serial.println("💤 Menunggu tombol START ditekan kembali.\n");

  kirimTriggerKeWeb("btn_off", 0);
}

// ================================================================
// WEB SERVER LOCAL ENDPOINTS
// ================================================================
void setCORS() {
  server.sendHeader("Access-Control-Allow-Origin", "*");
  server.sendHeader("Access-Control-Allow-Methods", "GET, POST, OPTIONS");
  server.sendHeader("Access-Control-Allow-Headers", "*");
}

void handleOptions() {
  setCORS();
  server.send(204);
}

void handleStatus() {
  setCORS();
  int inBox = hangerInCount - hangerOutCount;
  if (inBox < 0) inBox = 0;
  String json = "{\"isProduksi\":" + String(isProduksi ? "true" : "false") + 
                ",\"in\":" + String(hangerInCount) + 
                ",\"out\":" + String(hangerOutCount) + 
                ",\"wip\":" + String(inBox) + 
                ",\"in_pin\":" + String(PIN_SENS_IN) + 
                ",\"out_pin\":" + String(PIN_SENS_OUT) + 
                ",\"in_noise_hz\":0.0" + 
                ",\"out_noise_hz\":0.0" + 
                ",\"noise_diagnosis\":\"1 Box System (Pin 27 In, Pin 25 Out)\"" + 
                ",\"flash_mode\":" + String(flashOutputMode) + 
                ",\"flash_brightness\":" + String(flashBrightness) + "}";
  server.send(200, "application/json", json);
}

void handleNoiseMeter() {
  setCORS();
  String json = "{\"status\":\"ok\""
                ",\"in_noise_hz\":0.0"
                ",\"out_noise_hz\":0.0"
                ",\"in_peak_hz\":0.0"
                ",\"out_peak_hz\":0.0"
                ",\"in_total\":" + String(hangerInCount) + 
                ",\"out_total\":" + String(hangerOutCount) + 
                ",\"diagnosis\":\"1 Box System (Pin 27 In, Pin 25 Out Roboflow)\""
                ",\"is_clean\":true"
                ",\"recommendation\":\"Sensor NPN langsung terhubung ke ESP32 tanpa optocoupler.\"}";
  server.send(200, "application/json", json);
}

void handleResetNoisePeak() {
  setCORS();
  server.send(200, "application/json", "{\"status\":\"ok\",\"message\":\"Reset berhasil\"}");
}

void handleResetCounter() {
  setCORS();
  hangerInCount = 0;
  hangerOutCount = 0;
  inletArmed = true;
  outletArmed = true;
  lastInletTriggerMs = 0;
  lastOutletTriggerMs = 0;
  inletClearSinceMs = 0;
  outletClearSinceMs = 0;
  Serial.println("\n🔄 [RESET COUNTER] Counter Masuk & Keluar di-reset ke 0 via Web API.");
  server.send(200, "application/json", "{\"status\":\"ok\",\"message\":\"Counter berhasil direset ke 0\"}");
}

void handleLampu() {
  setCORS();
  if (server.hasArg("mode")) {
    String mode = server.arg("mode");
    mode.toLowerCase();
    if (mode == "solid" || mode == "1") {
      flashOutputMode = 1;
      if (server.hasArg("val")) flashBrightness = constrain(server.arg("val").toInt(), 0, 255);
      ledcWrite(PIN_FLASH, flashBrightness);
      Serial.printf("[FLASH] Mode diset ke SOLID (Nilai: %d)\n", flashBrightness);
    } else if (mode == "strobe" || mode == "2") {
      flashOutputMode = 2;
      ledcWrite(PIN_FLASH, 0);
      Serial.println("[FLASH] Mode diset ke STROBE (Flash saat hanger masuk/keluar)");
    } else if (mode == "off" || mode == "0") {
      flashOutputMode = 0;
      ledcWrite(PIN_FLASH, 0);
      Serial.println("[FLASH] Lampu dimatikan (OFF)");
    }
  } else if (server.hasArg("val")) {
    int val = constrain(server.arg("val").toInt(), 0, 255);
    flashBrightness = val;
    if (val > 0) {
      flashOutputMode = 1;
      ledcWrite(PIN_FLASH, flashBrightness);
    } else {
      flashOutputMode = 0;
      ledcWrite(PIN_FLASH, 0);
    }
  }
  server.send(200, "application/json", "{\"mode\":" + String(flashOutputMode) + ",\"brightness\":" + String(flashBrightness) + "}");
}

void handleKabel() {
  setCORS();
  if (server.hasArg("tipe") && server.hasArg("val")) {
    String tipe = server.arg("tipe");
    String val = server.arg("val");
    Serial2.print(tipe + ":" + val + "\n");
    Serial.println("[RS485-TX] " + tipe + ":" + val);
  }
  server.send(200, "text/plain", "OK");
}

void handleStart() {
  setCORS();
  startProduksi("WEB DASHBOARD");
  server.send(200, "application/json", "{\"status\":\"ok\",\"isProduksi\":true}");
}

void handleStop() {
  setCORS();
  stopProduksi("WEB DASHBOARD");
  server.send(200, "application/json", "{\"status\":\"ok\",\"isProduksi\":false}");
}

// ================================================================
// SETUP
// ================================================================
void setup() {
  Serial.begin(115200);
  
  // RS-485 UART
  Serial2.begin(115200, SERIAL_8N1, RS485_RX_PIN, RS485_TX_PIN);
  Serial2.setTimeout(10);

  // Konfigurasi Pin Digital (Kedua sensor NPN langsung dengan INPUT_PULLUP)
  pinMode(PIN_BTN_ON, INPUT_PULLUP);
  pinMode(PIN_BTN_OFF, INPUT_PULLUP);
  pinMode(PIN_SENS_IN, INPUT_PULLUP);      // Pin 27: NPN Masuk Box
  pinMode(PIN_SENS_OUT, INPUT_PULLUP);     // Pin 25: NPN Keluar Box (Konfirmasi Roboflow)

  delay(100);
  lastBtnOnState  = digitalRead(PIN_BTN_ON);
  lastBtnOffState = digitalRead(PIN_BTN_OFF);

  // Setup Lampu Flash / Penerangan Box PWM Pin 26
  ledcAttach(PIN_FLASH, 5000, 8);
  if (isProduksi && flashOutputMode == 1) {
    ledcWrite(PIN_FLASH, flashBrightness);
  } else {
    ledcWrite(PIN_FLASH, 0);
  }

  // Koneksi WiFi
  Serial.print("Menghubungkan ke WiFi: ");
  Serial.println(ssid);
  WiFi.mode(WIFI_STA);
  WiFi.setSleep(false);
  WiFi.setAutoReconnect(true);

  if (USE_STATIC_IP) {
    if (!WiFi.config(devkit_static_ip, devkit_gateway, devkit_subnet, devkit_dns)) {
      Serial.println("[WIFI] Konfigurasi Static IP Gagal! Gunakan DHCP...");
    } else {
      Serial.print("[WIFI] Static IP Aktif: ");
      Serial.println(devkit_static_ip);
    }
  }

  WiFi.begin(ssid, password);
  unsigned long wifiStart = millis();
  while (WiFi.status() != WL_CONNECTED && (millis() - wifiStart < 7000)) { 
    delay(200); 
    Serial.print(".");
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("[WIFI] Berhasil terhubung!");
    HTTPClient httpSync;
    String setIpUrl = String("http://") + server_ip + "/api/set-devkit-ip?ip=" + WiFi.localIP().toString();
    httpSync.begin(setIpUrl);
    httpSync.setTimeout(2000);
    httpSync.GET();
    httpSync.end();
  }

  // Setup Task HTTP di Core 0
  triggerQueue = xQueueCreate(16, sizeof(TriggerEventMsg));
  if (triggerQueue != NULL) {
    xTaskCreatePinnedToCore(httpWorkerTask, "HttpWorkerTask", 8192, NULL, 1, NULL, 0);
  }

  // Setup Web Server Routes
  server.on("/kabel", HTTP_GET, handleKabel);
  server.on("/kabel", HTTP_OPTIONS, handleOptions);
  server.on("/lampu", HTTP_GET, handleLampu);
  server.on("/lampu", HTTP_OPTIONS, handleOptions);
  server.on("/status-mesin", HTTP_GET, handleStatus);
  server.on("/status-mesin", HTTP_OPTIONS, handleOptions);
  server.on("/noise-meter", HTTP_GET, handleNoiseMeter);
  server.on("/noise-meter", HTTP_OPTIONS, handleOptions);
  server.on("/reset-noise-peak", HTTP_GET, handleResetNoisePeak);
  server.on("/reset-noise-peak", HTTP_OPTIONS, handleOptions);
  server.on("/reset-counter", HTTP_GET, handleResetCounter);
  server.on("/reset-counter", HTTP_POST, handleResetCounter);
  server.on("/reset-counter", HTTP_OPTIONS, handleOptions);
  server.on("/start", HTTP_GET, handleStart);
  server.on("/start", HTTP_OPTIONS, handleOptions);
  server.on("/stop", HTTP_GET, handleStop);
  server.on("/stop", HTTP_OPTIONS, handleOptions);

  server.on("/trigger-in", HTTP_GET, []() {
    setCORS(); 
    server.send(200, "text/plain", "OK"); 
    hangerInCount++;
    int inBox = hangerInCount - hangerOutCount;
    if (inBox < 0) inBox = 0;
    Serial.printf("🌐 [WEB TRIGGER IN - PIN 27] TOTAL MASUK: %d | TOTAL KELUAR: %d | DALAM BOX: %d\n",
                  hangerInCount, hangerOutCount, inBox);
    kirimTriggerKeWeb("input", hangerInCount);
  });
  
  server.on("/trigger-out", HTTP_GET, []() {
    setCORS(); 
    server.send(200, "text/plain", "OK"); 
    hangerOutCount++;
    int inBox = hangerInCount - hangerOutCount;
    if (inBox < 0) inBox = 0;
    Serial.printf("🌐 [WEB TRIGGER OUT - PIN 25] TOTAL MASUK: %d | TOTAL KELUAR: %d | DALAM BOX: %d\n",
                  hangerInCount, hangerOutCount, inBox);
    kirimTriggerKeWeb("output", hangerOutCount);
  });

  server.begin();

  // Status awal pin saat boot
  int raw27 = digitalRead(PIN_SENS_IN);
  int raw25 = digitalRead(PIN_SENS_OUT);

  Serial.println("==================================================");
  Serial.println(" ESP32 DEVKIT - 1 BOX SYSTEM (PIN 27 IN, PIN 25 OUT)");
  Serial.println("==================================================");
  Serial.printf(" - Pin 27 (Sensor Masuk Box) : %s (Raw: %d)\n",
                raw27 == LOW ? "0V / LOW (Objek Ada)" : "3.3V / HIGH (Standby)", raw27);
  Serial.printf(" - Pin 25 (Sensor Keluar Box): %s (Raw: %d)\n",
                raw25 == LOW ? "0V / LOW (Objek Ada)" : "3.3V / HIGH (Standby)", raw25);
  Serial.printf(" - Mode Produksi             : %s\n", isProduksi ? "AKTIF (SIAP HITUNG)" : "STANDBY (OFF)");
  Serial.println("==================================================\n");
}

// ================================================================
// LOOP UTAMA (CORE 1) - SUPER CEPAT & RESPONS 0 MS
// ================================================================
void loop() {
  server.handleClient();
  unsigned long now = millis();

  // Reconnect WiFi berkala jika terputus
  if (WiFi.status() != WL_CONNECTED && (now - lastWifiRetryMs >= 15000)) {
    lastWifiRetryMs = now;
    WiFi.reconnect();
  }

  // Matikan lampu flash strobe setelah durasi selesai
  if (flashOffTime > 0 && (long)(now - flashOffTime) >= 0) {
    ledcWrite(PIN_FLASH, 0);
    flashOffTime = 0;
  }

  // 1. MONITOR TOMBOL START (PB HIJAU - PIN 13)
  bool currentBtnOnState = digitalRead(PIN_BTN_ON);
  if (currentBtnOnState == LOW && lastBtnOnState == HIGH) {
    startProduksi("PB HIJAU Pin 13");
  } 
  lastBtnOnState = currentBtnOnState;

  // 2. MONITOR TOMBOL STOP (PB MERAH - PIN 12)
  bool currentBtnOffState = digitalRead(PIN_BTN_OFF);
  if (currentBtnOffState == HIGH && lastBtnOffState == LOW) {
    stopProduksi("PB MERAH Pin 12");
  } 
  lastBtnOffState = currentBtnOffState;

  // 3. MONITOR PERINTAH SERIAL MONITOR (start, stop, s, test)
  if (Serial.available()) {
    String cmd = Serial.readStringUntil('\n');
    cmd.trim();
    cmd.toLowerCase();
    if (cmd == "start" || cmd == "1" || cmd == "on") {
      startProduksi("SERIAL MONITOR");
    } else if (cmd == "stop" || cmd == "0" || cmd == "off") {
      stopProduksi("SERIAL MONITOR");
    } else if (cmd == "s" || cmd == "status") {
      printTotalCounterSummary();
    } else if (cmd == "test" || cmd == "raw" || cmd == "pin") {
      Serial.println("--------------------------------------------------");
      Serial.printf("🔍 [BACA TEGANGAN SENSOR BOX REAL-TIME]\n");
      Serial.printf(" - Pin 27 (Sensor Masuk Box) : %s | Armed: %s\n",
                    digitalRead(PIN_SENS_IN) == LOW ? "0V (LOW - ADA OBJEK)" : "3.3V (HIGH - KOSONG)",
                    inletArmed ? "SIAP" : "TERKUNCI");
      Serial.printf(" - Pin 25 (Sensor Keluar Box): %s | Armed: %s\n",
                    digitalRead(PIN_SENS_OUT) == LOW ? "0V (LOW - ADA OBJEK)" : "3.3V (HIGH - KOSONG)",
                    outletArmed ? "SIAP" : "TERKUNCI");
      Serial.println("--------------------------------------------------");
    }
  }

  // 4. BACA DATA RS-485 JIKA ADA
  static String rs485Buffer = "";
  while (Serial2.available()) {
    char c = (char)Serial2.read();
    if (c == '\n' || c == '\r') {
      rs485Buffer.trim();
      if (rs485Buffer.length() > 0 && isProduksi) {
        if (rs485Buffer.indexOf("IN") >= 0 || rs485Buffer == "1") {
          hangerInCount++;
          int inBox = hangerInCount - hangerOutCount;
          if (inBox < 0) inBox = 0;
          Serial.printf("📊 [RS485 IN] TOTAL MASUK: %d | TOTAL KELUAR: %d | DALAM BOX: %d\n",
                        hangerInCount, hangerOutCount, inBox);
          kirimTriggerKeWeb("input", hangerInCount);
        } else if (rs485Buffer.indexOf("OUT") >= 0 || rs485Buffer == "2") {
          hangerOutCount++;
          int inBox = hangerInCount - hangerOutCount;
          if (inBox < 0) inBox = 0;
          Serial.printf("📊 [RS485 OUT] TOTAL MASUK: %d | TOTAL KELUAR: %d | DALAM BOX: %d\n",
                        hangerInCount, hangerOutCount, inBox);
          kirimTriggerKeWeb("output", hangerOutCount);
        }
      }
      rs485Buffer = "";
    } else if (rs485Buffer.length() < 64) {
      rs485Buffer += c;
    }
  }

  // 5. HEARTBEAT BERKALA KE DASHBOARD (Setiap 5 detik saat aktif)
  static unsigned long lastHeartbeatMs = 0;
  if (now - lastHeartbeatMs >= (isProduksi ? 5000 : 15000)) {
    lastHeartbeatMs = now;
    kirimTriggerKeWeb("heartbeat", isProduksi ? 1 : 0);
  }

  // JIKA MESIN SEDANG STANDBY (STOP): Cuekin sensor fisik
  if (!isProduksi) {
    inletArmed  = true;
    outletArmed = true;
    return;
  }

  // ================================================================
  // DETEKSI SENSOR 1: PIN 27 (HANGER MASUK KE DALAM BOX)
  // ================================================================
  bool inIsActive = (digitalRead(PIN_SENS_IN) == SENSOR_DETECT);

  if (inIsActive) {
    inletClearSinceMs = 0; // Sensor sedang terhalang, reset timer bersih

    // Syarat memicu:
    // 1. Gerbang sedang ARMED (siap)
    // 2. Sudah melewati masa LOCKOUT sejak trigger terakhir (mencegah double trigger)
    if (inletArmed && (now - lastInletTriggerMs >= LOCKOUT_INLET_MS)) {
      inletArmed = false;       // Kunci gerbang rapat!
      lastInletTriggerMs = now; // Catat waktu trigger awal
      hangerInCount++;
      
      int inBox = hangerInCount - hangerOutCount;
      if (inBox < 0) inBox = 0;

      Serial.println("\n--------------------------------------------------");
      Serial.printf("🟢 [HANGER MASUK BOX] Terdeteksi di Pin %d (Tegangan: 0V / LOW)\n", PIN_SENS_IN);
      Serial.printf("   📊 STATUS -> TOTAL MASUK BOX: %d | TOTAL KELUAR: %d | DALAM BOX: %d Hanger\n",
                    hangerInCount, hangerOutCount, inBox);
      Serial.printf("   🔒 Anti-Double Trigger: Sensor Pin %d DIKUNCI selama %lu ms!\n", PIN_SENS_IN, LOCKOUT_INLET_MS);
      Serial.println("--------------------------------------------------");

      // Jika mode strobe, nyalakan flash saat hanger masuk
      if (flashOutputMode == 2) {
        ledcWrite(PIN_FLASH, flashBrightness > 0 ? flashBrightness : 220);
        flashOffTime = now + 250; 
      }

      kirimTriggerKeWeb("input", hangerInCount);
    }
  } else {
    // Sinyal kembali HIGH (3.3V / Standby kosong)
    if (!inletArmed) {
      // 1. Pastikan masa lockout telah berlalu
      if (now - lastInletTriggerMs >= LOCKOUT_INLET_MS) {
        // 2. Pastikan sensor benar-benar bersih kontinu selama RELEASE_INLET_MS
        if (inletClearSinceMs == 0) {
          inletClearSinceMs = now;
        } else if (now - inletClearSinceMs >= RELEASE_INLET_MS) {
          inletArmed = true;        // Buka kunci! Siap menerima hanger berikutnya
          inletClearSinceMs = 0;
        }
      }
    }
  }

  // ================================================================
  // DETEKSI SENSOR 2: PIN 25 (HANGER KELUAR BOX -> KONFIRMASI ROBOFLOW)
  // ================================================================
  bool outIsActive = (digitalRead(PIN_SENS_OUT) == SENSOR_DETECT);

  if (outIsActive) {
    outletClearSinceMs = 0; // Sensor sedang terhalang, reset timer bersih

    // Syarat memicu:
    // 1. Gerbang sedang ARMED (siap)
    // 2. Sudah melewati masa LOCKOUT sejak trigger terakhir (mencegah double trigger)
    if (outletArmed && (now - lastOutletTriggerMs >= LOCKOUT_OUTLET_MS)) {
      outletArmed = false;       // Kunci gerbang rapat!
      lastOutletTriggerMs = now; // Catat waktu trigger awal
      hangerOutCount++;

      int inBox = hangerInCount - hangerOutCount;
      if (inBox < 0) inBox = 0;

      Serial.println("\n--------------------------------------------------");
      Serial.printf("🔵 [HANGER KELUAR BOX] Terdeteksi di Pin %d (Tegangan: 0V / LOW)\n", PIN_SENS_OUT);
      Serial.println("   📸 Web Dashboard mengeksekusi Shutter AI & konfirmasi jumlah PCS via Roboflow!");
      Serial.printf("   📊 STATUS -> TOTAL MASUK BOX: %d | TOTAL KELUAR: %d | DALAM BOX: %d Hanger\n",
                    hangerInCount, hangerOutCount, inBox);
      Serial.printf("   🔒 Anti-Double Trigger: Sensor Pin %d DIKUNCI selama %lu ms!\n", PIN_SENS_OUT, LOCKOUT_OUTLET_MS);
      Serial.println("--------------------------------------------------");

      // Jika mode strobe, nyalakan flash saat hanger keluar
      if (flashOutputMode == 2) {
        ledcWrite(PIN_FLASH, flashBrightness > 0 ? flashBrightness : 220);
        flashOffTime = now + 200; 
      }

      kirimTriggerKeWeb("output", hangerOutCount);
    }
  } else {
    // Sinyal kembali HIGH (3.3V / Standby kosong)
    if (!outletArmed) {
      if (now - lastOutletTriggerMs >= LOCKOUT_OUTLET_MS) {
        if (outletClearSinceMs == 0) {
          outletClearSinceMs = now;
        } else if (now - outletClearSinceMs >= RELEASE_OUTLET_MS) {
          outletArmed = true;        // Buka kunci! Siap menerima hanger berikutnya
          outletClearSinceMs = 0;
        }
      }
    }
  }

  // Ringkasan status berkala santai (setiap 30 detik) saat aktif produksi
  static unsigned long lastSummaryMs = 0;
  if (now - lastSummaryMs >= 30000) {
    lastSummaryMs = now;
    int inBox = hangerInCount - hangerOutCount;
    if (inBox < 0) inBox = 0;
    Serial.printf("⏱️ [PRODUKSI AKTIF 30s] MASUK BOX: %d | KELUAR BOX: %d | DALAM BOX: %d Hanger\n",
                  hangerInCount, hangerOutCount, inBox);
  }
}
