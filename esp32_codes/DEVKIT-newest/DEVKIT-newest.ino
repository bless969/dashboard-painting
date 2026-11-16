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

// ================================================================
// DEBOUNCE / RELEASE SETTLE TIME
// ================================================================
// Jeda standby bersih (ms) setelah objek lepas sebelum siap menghitung objek berikutnya (+1 per barang)
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
void printTotalCounterSummary();
void startProduksi(const char* triggerSource = "TOMBOL FISIK");
void stopProduksi(const char* triggerSource = "TOMBOL FISIK");

bool lastBtnOnState   = HIGH; // NO (Pin 13 Hijau) default = HIGH (floating, ditarik pullup)
bool lastBtnOffState  = LOW;  // NC (Pin 12 Merah) default = LOW (tersambung ke GND)

// State Interrupt & Armed Pemicu Sensor Instan
volatile bool sensInEdgeFlag   = false;
volatile bool sensOutEdgeFlag  = false;
bool sensInArmed               = true;
unsigned long sensInIdleStart  = 0;
bool sensOutArmed              = true;
unsigned long sensOutIdleStart = 0;

// ================================================================
// DETEKSI & PENGUKUR FREKUENSI NOISE MOTOR INDUSTRI (EMI / RFI)
// ================================================================
volatile unsigned long isrCountIn  = 0;
volatile unsigned long isrCountOut = 0;

unsigned long lastNoiseWindowMs   = 0;
unsigned long lastSnapIsrIn        = 0;
unsigned long lastSnapIsrOut       = 0;

float currentNoiseFreqIn           = 0.0;
float currentNoiseFreqOut          = 0.0;
float peakNoiseFreqIn              = 0.0;
float peakNoiseFreqOut             = 0.0;
String currentNoiseDiagnosis       = "BERSIH (Normal)";

void IRAM_ATTR handleSensInISR() {
  sensInEdgeFlag = true;
  isrCountIn = isrCountIn + 1;
}

void IRAM_ATTR handleSensOutISR() {
  sensOutEdgeFlag = true;
  isrCountOut = isrCountOut + 1;
}

bool isSensorRawActive(int pin, bool activeLow) {
  int activeLevel = activeLow ? LOW : HIGH;
  return digitalRead(pin) == activeLevel;
}

// ================================================================
// TRIGGER SENSOR INSTAN (0ms LATENCY) DENGAN ANTI-BOUNCE RELEASE
// ================================================================
bool checkSensorTrigger(
  int pin,
  bool activeLow,
  volatile bool &edgeFlag,
  bool &armed,
  unsigned long &idleStartMs,
  unsigned long now
) {
  bool hasEdge = false;
  if (edgeFlag) {
    edgeFlag = false;
    hasEdge = true;
  }

  bool rawActive = isSensorRawActive(pin, activeLow);
  bool isIdle    = !rawActive;

  // Objek menyentuh sensor: trigger instan 0ms!
  if (armed && (hasEdge || rawActive)) {
    armed       = false;
    idleStartMs = 0;
    return true; // Pemicu instan diterima!
  }

  // Objek lepas dan kembali standby bersih selama RELEASE_SETTLE_MS
  if (isIdle) {
    if (!armed) {
      if (idleStartMs == 0) {
        idleStartMs = now;
      } else if (now - idleStartMs >= RELEASE_SETTLE_MS) {
        armed       = true; // Siap hitung objek berikutnya
        idleStartMs = 0;
      }
    }
  } else {
    idleStartMs = 0;
  }

  return false;
}

// ================================================================
// ENGINE PENGUKUR FREKUENSI NOISE MOTOR INDUSTRI (EMI SPECTRUM)
// ================================================================
void updateNoiseFrequencyEngine(unsigned long now) {
  if (now - lastNoiseWindowMs < 1000) return;

  unsigned long elapsed = now - lastNoiseWindowMs;
  lastNoiseWindowMs = now;

  unsigned long snapIn  = isrCountIn;
  unsigned long snapOut = isrCountOut;

  unsigned long diffIn  = (snapIn >= lastSnapIsrIn) ? (snapIn - lastSnapIsrIn) : 0;
  unsigned long diffOut = (snapOut >= lastSnapIsrOut) ? (snapOut - lastSnapIsrOut) : 0;

  lastSnapIsrIn  = snapIn;
  lastSnapIsrOut = snapOut;

  currentNoiseFreqIn  = ((float)diffIn * 1000.0f) / (float)elapsed;
  currentNoiseFreqOut = ((float)diffOut * 1000.0f) / (float)elapsed;

  if (currentNoiseFreqIn > peakNoiseFreqIn)   peakNoiseFreqIn = currentNoiseFreqIn;
  if (currentNoiseFreqOut > peakNoiseFreqOut) peakNoiseFreqOut = currentNoiseFreqOut;

  float maxFreq = max(currentNoiseFreqIn, currentNoiseFreqOut);
  if (maxFreq < 1.5f) {
    currentNoiseDiagnosis = "BERSIH (Normal, Bebas Induksi Motor)";
  } else if (maxFreq >= 40.0f && maxFreq <= 65.0f) {
    currentNoiseDiagnosis = "INDUKSI AC 50Hz (Kabel Daya / Grounding Motor)";
  } else if (maxFreq >= 85.0f && maxFreq <= 130.0f) {
    currentNoiseDiagnosis = "HARMONISA PENYEARAH 100Hz (Power Supply / DC Motor)";
  } else if (maxFreq >= 200.0f) {
    currentNoiseDiagnosis = "SWITCHING INVERTER / VFD MOTOR (PWM High Frequency)";
  } else {
    currentNoiseDiagnosis = "INDUKSI TRANSIENT MOTOR (Spark / Getaran)";
  }

  // Hanya cetak ke Serial Monitor jika status kebisingan berubah (State Change),
  // agar Serial Monitor tetap bersih dan tidak terbanjiri teks setiap beberapa detik
  static bool prevNoiseState = false;
  bool isNoiseState = (maxFreq >= 3.0f);
  if (isNoiseState != prevNoiseState) {
    prevNoiseState = isNoiseState;
    if (isNoiseState) {
      Serial.printf("\n⚡ [GANGGUAN MOTOR TERDETEKSI] Frekuensi: %.1f Hz | %s (Filter Otomatis Menolak Pulsa Palsu)\n",
                    maxFreq, currentNoiseDiagnosis.c_str());
    } else {
      Serial.println("\n🟢 [JALUR SENSOR BERSIH] Gangguan motor telah reda / hilang (Sinyal Normal).");
    }
  }
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
  unsigned long durationMs;
} TriggerEventMsg;

QueueHandle_t triggerQueue = NULL;

void httpWorkerTask(void* pvParameters) {
  TriggerEventMsg msg;
  for (;;) {
    if (xQueueReceive(triggerQueue, &msg, portMAX_DELAY) == pdTRUE) {
      if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        String targetUrl = dashboard_trigger_url + "?type=" + String(msg.tipe) + "&val=" + String(msg.val);
        if (msg.durationMs > 0) {
          targetUrl += "&duration_ms=" + String(msg.durationMs);
        }
        
        http.begin(targetUrl);
        http.setConnectTimeout(800); // Timeout koneksi cepat 800 ms (bukan 5000 ms default)
        http.setTimeout(1000);       // Timeout baca respons maks 1000 ms
        http.setReuse(false);
        int httpCode = http.GET();
        
        if (httpCode > 0) {
          Serial.printf("[WEB-API] Trigger '%s' (val=%d, dur=%lums) berhasil dikirim ke Web Dashboard (HTTP %d)\n",
                        msg.tipe, msg.val, msg.durationMs, httpCode);
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

void kirimTriggerKeWeb(String tipe, int val = 1, unsigned long durationMs = 0) {
  if (triggerQueue == NULL) return;
  TriggerEventMsg msg;
  memset(&msg, 0, sizeof(msg));
  strncpy(msg.tipe, tipe.c_str(), sizeof(msg.tipe) - 1);
  msg.val = val;
  msg.durationMs = durationMs;
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
                ",\"in_noise_hz\":" + String(currentNoiseFreqIn, 1) + 
                ",\"out_noise_hz\":" + String(currentNoiseFreqOut, 1) + 
                ",\"in_peak_hz\":" + String(peakNoiseFreqIn, 1) + 
                ",\"out_peak_hz\":" + String(peakNoiseFreqOut, 1) + 
                ",\"noise_diagnosis\":\"" + currentNoiseDiagnosis + "\"" +
                ",\"flash_mode\":" + String(flashOutputMode) + 
                ",\"flash_brightness\":" + String(flashBrightness) + "}";
  server.send(200, "application/json", json);
}

void handleNoiseMeter() {
  setCORS();
  float maxFreq = max(currentNoiseFreqIn, currentNoiseFreqOut);
  String json = "{\"status\":\"ok\""
                ",\"in_noise_hz\":" + String(currentNoiseFreqIn, 1) + 
                ",\"out_noise_hz\":" + String(currentNoiseFreqOut, 1) + 
                ",\"in_peak_hz\":" + String(peakNoiseFreqIn, 1) + 
                ",\"out_peak_hz\":" + String(peakNoiseFreqOut, 1) + 
                ",\"in_total\":" + String(hangerInCount) + 
                ",\"out_total\":" + String(hangerOutCount) + 
                ",\"diagnosis\":\"" + currentNoiseDiagnosis + "\"" +
                ",\"is_clean\":" + String((maxFreq < 1.5f) ? "true" : "false") + 
                ",\"recommendation\":\"Jika frekuensi > 50Hz, pasang kapasitor keramik 100nF antara Pin dan GND serta gunakan kabel shielded ber-ground\"}";
  server.send(200, "application/json", json);
}

void handleResetNoisePeak() {
  setCORS();
  peakNoiseFreqIn  = 0.0;
  peakNoiseFreqOut = 0.0;
  server.send(200, "application/json", "{\"status\":\"ok\",\"message\":\"Peak noise reset berhasil\"}");
}

void handleOptions() {
  setCORS();
  server.send(204);
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
  if (isProduksi && flashOutputMode == 1) {
    ledcWrite(PIN_FLASH, flashBrightness); // Langsung nyala SOLID konstan jika aktif
    Serial.printf("[FLASH] Lampu Output Pin 26 aktif SOLID (Kecerahan: %d)\n", flashBrightness);
  } else {
    ledcWrite(PIN_FLASH, 0); // Standby: matikan lampu flash
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

  server.on("/noise-meter", HTTP_GET, handleNoiseMeter);
  server.on("/noise-meter", HTTP_OPTIONS, handleOptions);
  server.on("/reset-noise-peak", HTTP_GET, handleResetNoisePeak);
  server.on("/reset-noise-peak", HTTP_OPTIONS, handleOptions);

  server.on("/start", HTTP_GET, handleStart);
  server.on("/start", HTTP_OPTIONS, handleOptions);
  server.on("/stop", HTTP_GET, handleStop);
  server.on("/stop", HTTP_OPTIONS, handleOptions);

  server.on("/trigger-in", HTTP_GET, []() {
    setCORS(); 
    server.send(200, "text/plain", "OK"); 
    hangerInCount++;
    int wip = hangerInCount - hangerOutCount;
    if (wip < 0) wip = 0;
    Serial.printf("🌐 [WEB TRIGGER IN] TOTAL IN: %d | TOTAL OUT: %d | WIP: %d Hanger\n",
                  hangerInCount, hangerOutCount, wip);
    kirimTriggerKeWeb("input", 1);
  });
  
  server.on("/trigger-out", HTTP_GET, []() {
    setCORS(); 
    server.send(200, "text/plain", "OK"); 
    hangerOutCount++;
    int wip = hangerInCount - hangerOutCount;
    if (wip < 0) wip = 0;
    Serial.printf("🌐 [WEB TRIGGER OUT] TOTAL IN: %d | TOTAL OUT: %d | WIP: %d Hanger\n",
                  hangerInCount, hangerOutCount, wip);
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
  Serial.printf(" - Anti-Bounce Settle Time    : %lu ms\n", RELEASE_SETTLE_MS);
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
  Serial.printf(" - Status Mesin Saat Boot     : %s\n", isProduksi ? "AKTIF (ON)" : "STANDBY (OFF)");
  Serial.println("------------------------------------------");
  Serial.println(">> ESP32 SIAP! (MENUNGGU PERINTAH START) <<");
  Serial.println("💤 Status: STANDBY (Fotosel Dinonaktifkan, Data Dicuekin)");
  Serial.println("💡 Cara Mulai Produksi:");
  Serial.println("   - Tekan Push Button HIJAU (Pin 13), ATAU");
  Serial.println("   - Ketik 'start' di Serial Monitor lalu tekan Enter, ATAU");
  Serial.println("   - Klik tombol 'Start' di Web Dashboard.\n");
}

void printTotalCounterSummary() {
  int wip = hangerInCount - hangerOutCount;
  if (wip < 0) wip = 0;

  Serial.println("\n==================================================");
  Serial.printf("📊 [TOTAL COUNTER PHOTOSENSOR]\n");
  Serial.printf("   🟢 TOTAL INPUT  (Pin %d) : %d Hanger\n", PIN_SENS_IN, hangerInCount);
  Serial.printf("   🔵 TOTAL OUTPUT (Pin %d) : %d Hanger\n", PIN_SENS_OUT, hangerOutCount);
  Serial.printf("   🟡 WIP DI DALAM LINE     : %d Hanger\n", wip);
  Serial.printf("   ⚙️ Status Mesin          : %s\n", isProduksi ? "PRODUKSI (RUNNING)" : "STANDBY (STOP)");
  Serial.println("==================================================");
}

void startProduksi(const char* triggerSource) {
  if (isProduksi) {
    Serial.printf("ℹ️ [INFO] Mesin sudah dalam status AKTIF (Trigger via %s).\n", triggerSource);
    return;
  }
  isProduksi       = true;
  hangerInCount    = 0;
  hangerOutCount   = 0;
  sensInEdgeFlag   = false;
  sensOutEdgeFlag  = false;
  sensInArmed      = true;
  sensOutArmed     = true;
  sensInIdleStart  = 0;
  sensOutIdleStart = 0;

  if (flashOutputMode == 1) {
    ledcWrite(PIN_FLASH, flashBrightness);
  }

  Serial.println("\n==================================================");
  Serial.printf("🚀 >>> MESIN PRODUKSI DI-START [via %s] <<<\n", triggerSource);
  Serial.println("🟢 STATUS: AKTIF (ON) - Fotosel Kini MULAI MENGAMBIL DATA!");
  Serial.println("📊 Counter Direset: TOTAL INPUT = 0 | TOTAL OUTPUT = 0 | WIP = 0");
  Serial.println("==================================================");

  kirimTriggerKeWeb("btn_on", 1);
}

void stopProduksi(const char* triggerSource) {
  if (!isProduksi) {
    Serial.printf("ℹ️ [INFO] Mesin sudah dalam status STANDBY (Trigger via %s).\n", triggerSource);
    return;
  }
  isProduksi = false;

  ledcWrite(PIN_FLASH, 0); // Matikan flash saat standby

  Serial.println("\n==================================================");
  Serial.printf("🛑 >>> MESIN PRODUKSI DI-STOP [via %s] <<<\n", triggerSource);
  Serial.println("🔒 STATUS: STANDBY (OFF) - Fotosel DINONAKTIFKAN (DATA DICUEKIN)!");
  printTotalCounterSummary();
  Serial.println("💤 Serial Monitor Diam & Standby Sampai START Ditekan Kembali.\n");

  kirimTriggerKeWeb("btn_off", 0);
}

void handleRs485Line(String dataRs485, unsigned long now) {
  dataRs485.trim();
  if (dataRs485.length() == 0) return;

  if (!isProduksi) {
    // Mesin belum distart, abaikan data sensor RS485
    return;
  }

  Serial.println("[RS485-RX] Diterima: " + dataRs485);

  if (dataRs485.indexOf("IN") >= 0 || dataRs485 == "1") {
    hangerInCount++;
    int wip = hangerInCount - hangerOutCount;
    if (wip < 0) wip = 0;
    Serial.printf("📊 [RS485 COUNTER IN] TOTAL IN: %d | TOTAL OUT: %d | WIP: %d Hanger\n",
                  hangerInCount, hangerOutCount, wip);
    kirimTriggerKeWeb("input", 1);
  } else if (dataRs485.indexOf("OUT") >= 0 || dataRs485 == "2") {
    hangerOutCount++;
    int wip = hangerInCount - hangerOutCount;
    if (wip < 0) wip = 0;
    Serial.printf("📊 [RS485 COUNTER OUT] TOTAL IN: %d | TOTAL OUT: %d | WIP: %d Hanger\n",
                  hangerInCount, hangerOutCount, wip);
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

  // Engine Pengukur Frekuensi Noise Motor Industri (EMI Detector)
  updateNoiseFrequencyEngine(now);

  if (WiFi.status() != WL_CONNECTED && (now - lastWifiRetryMs >= 10000)) {
    lastWifiRetryMs = now;
    WiFi.reconnect();
  }

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

  // 3. MONITOR PERINTAH SERIAL MONITOR (start / stop / s)
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
    }
  }

  // 4. BACA DATA RS-485 JIKA ADA
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

  // ================================================================
  // JIKA MESIN BELUM DI-START: CUEKIN SEMUA SENSOR & JANGAN TAMPILKAN APA-APA!
  // ================================================================
  if (!isProduksi) {
    sensInEdgeFlag  = false;
    sensOutEdgeFlag = false;
    return; // KELUAR DARI loop()! Sensor fotosel dicuekin total!
  }

  // ================================================================
  // JIKA SUDAH DI-START: PROSES SENSOR & TAMPILKAN DATA DI SERIAL MONITOR
  // ================================================================
  if (checkSensorTrigger(PIN_SENS_IN, SENS_IN_ACTIVE_LOW, sensInEdgeFlag, sensInArmed, sensInIdleStart, now)) {
    hangerInCount++;
    int wip = hangerInCount - hangerOutCount;
    if (wip < 0) wip = 0;
    Serial.println("\n--------------------------------------------------");
    Serial.printf("🟢 [PHOTOSENSOR INLET - PIN %d] Hanger Masuk!\n", PIN_SENS_IN);
    Serial.printf("   📊 HASIL AKUMULASI -> TOTAL INPUT: %d | TOTAL OUTPUT: %d | WIP: %d Hanger\n",
                  hangerInCount, hangerOutCount, wip);
    Serial.println("--------------------------------------------------");
    kirimTriggerKeWeb("input", 1);
  }

  if (checkSensorTrigger(PIN_SENS_OUT, SENS_OUT_ACTIVE_LOW, sensOutEdgeFlag, sensOutArmed, sensOutIdleStart, now)) {
    hangerOutCount++;
    int wip = hangerInCount - hangerOutCount;
    if (wip < 0) wip = 0;
    Serial.println("\n--------------------------------------------------");
    Serial.printf("🔵 [PHOTOSENSOR OUTLET - PIN %d] Hanger Tiba di Kamera!\n", PIN_SENS_OUT);
    Serial.printf("   📊 HASIL AKUMULASI -> TOTAL INPUT: %d | TOTAL OUTPUT: %d | WIP: %d Hanger\n",
                  hangerInCount, hangerOutCount, wip);
    Serial.println("--------------------------------------------------");
    if (flashOutputMode == 2) {
      ledcWrite(PIN_FLASH, flashBrightness > 0 ? flashBrightness : 220);
      flashOffTime = now + 200;
    }
    kirimTriggerKeWeb("output", 1);
  }

  // Heartbeat berkala santai (setiap 30 detik) HANYA saat mesin AKTIF berproduksi
  static unsigned long lastPeriodicStatusMs = 0;
  if (now - lastPeriodicStatusMs >= 30000) {
    lastPeriodicStatusMs = now;
    int wip = hangerInCount - hangerOutCount;
    if (wip < 0) wip = 0;

    Serial.printf("⏱️ [PRODUKSI AKTIF 30s] TOTAL INPUT: %d | TOTAL OUTPUT: %d | WIP: %d Hanger\n",
                  hangerInCount, hangerOutCount, wip);
  }
}