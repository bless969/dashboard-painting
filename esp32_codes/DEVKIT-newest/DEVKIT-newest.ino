#include <WiFi.h>
#include <WebServer.h>
#include <HTTPClient.h>

const char* ssid     = "SSID";       // Ganti dengan nama WiFi pabrik/laptop
const char* password = "12345678";   // Ganti dengan password WiFi

const char* server_ip = "10.171.46.201:8000"; 

String dashboard_trigger_url = String("http://") + server_ip + "/api/sensor-trigger";
String dashboard_confirm_url = String("http://") + server_ip + "/api/confirm-hanger-output";
String master_painting_url   = String("http://") + server_ip + "/master-painting/public/api/sensor-data";

WebServer server(80);

const int PIN_BTN_ON   = 12; // Push Button NO (Normally Open)   -> Normal=HIGH (pullup), Ditekan=LOW
const int PIN_BTN_OFF  = 13; // Push Button NC (Normally Closed) -> Normal=LOW (ke GND), Ditekan=HIGH
const int PIN_SENS_IN  = 14;
const int PIN_SENS_OUT = 27;

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

bool lastBtnOnState   = HIGH; // NO default = HIGH (floating, ditarik pullup)
bool lastBtnOffState  = LOW;  // NC default = LOW (tersambung ke GND)
bool lastSensInState  = HIGH;
bool lastSensOutState = HIGH;

// Debounce waktu sensor agar tidak double trigger (1.2 detik per hanger)
unsigned long lastTriggerInTime  = 0;
unsigned long lastTriggerOutTime = 0;
const unsigned long DEBOUNCE_TIME = 1200;

unsigned long lastPostTime = 0;
const unsigned long POST_INTERVAL = 2000; // Kirim status ke server tiap 2 detik

void kirimTriggerKeWeb(String tipe, int val = 1) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    String targetUrl = dashboard_trigger_url + "?type=" + tipe + "&val=" + String(val);
    
    http.begin(targetUrl);
    http.setTimeout(1500); // Timeout cepat agar loop tidak terhambat
    int httpCode = http.GET();
    
    if (httpCode > 0) {
      Serial.printf("[WEB-API] Trigger '%s' (val=%d) berhasil dikirim ke Web Dashboard (HTTP %d)\n", tipe.c_str(), val, httpCode);
    } else {
      Serial.printf("[WEB-API] Gagal kirim trigger '%s'. Error: %s\n", tipe.c_str(), http.errorToString(httpCode).c_str());
    }
    http.end();
  }
}

// Push data status produksi ke Master Painting
void sendDataToLaravel() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(master_painting_url);
    http.addHeader("Content-Type", "application/json");
    http.setTimeout(1500);

    String json = "{\"isProduksi\":" + String(isProduksi ? "true" : "false") + 
                  ",\"in\":" + String(hangerInCount) + 
                  ",\"out\":" + String(hangerOutCount) + "}";

    int httpCode = http.POST(json);
    http.end();
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
      flashOutputMode = 1; // Otomatis aktifkan SOLID jika nilai > 0
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
  
  // RS-485 to TTL UART ke ESP32-CAM / Sensor Bus
  Serial2.begin(115200, SERIAL_8N1, RS485_RX_PIN, RS485_TX_PIN);

  // Pin Tombol & Sensor (dengan internal pullup)
  pinMode(PIN_BTN_ON, INPUT_PULLUP);
  pinMode(PIN_BTN_OFF, INPUT_PULLUP);
  pinMode(PIN_SENS_IN, INPUT_PULLUP);
  pinMode(PIN_SENS_OUT, INPUT_PULLUP);

  // Tunggu sejenak agar tegangan pin stabil, lalu baca kondisi resting/idle pin
  delay(100);
  lastBtnOnState   = digitalRead(PIN_BTN_ON);
  lastBtnOffState  = digitalRead(PIN_BTN_OFF);
  lastSensInState  = digitalRead(PIN_SENS_IN);
  lastSensOutState = digitalRead(PIN_SENS_OUT);

  // Inisialisasi Lampu Flash PWM (Pin 26)
  ledcAttach(PIN_FLASH, 5000, 8);
  if (flashOutputMode == 1) {
    ledcWrite(PIN_FLASH, flashBrightness); // Langsung nyala SOLID konstan
    Serial.printf("[FLASH] Lampu Output Pin 26 aktif SOLID (Kecerahan: %d)\n", flashBrightness);
  } else {
    ledcWrite(PIN_FLASH, 0);
  }

  // Sambungkan ke Jaringan WiFi
  Serial.print("Menghubungkan ke WiFi: ");
  Serial.println(ssid);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) { 
    delay(400); 
    Serial.print(".");
  }
  
  Serial.println("\n==========================================");
  Serial.println("  ESP32 DEVKIT (RS-485 + HANGER TRIGGER)  ");
  Serial.println("==========================================");
  Serial.print("IP ESP32      : "); Serial.println(WiFi.localIP());
  Serial.print("Target Server : "); Serial.println(server_ip);
  Serial.println("==========================================");

  // Endpoint WebServer ESP32
  server.on("/kabel", HTTP_GET, handleKabel);
  server.on("/kabel", HTTP_OPTIONS, handleOptions);
  
  server.on("/lampu", HTTP_GET, handleLampu);
  server.on("/lampu", HTTP_OPTIONS, handleOptions);
  
  server.on("/status-mesin", HTTP_GET, handleStatus);
  server.on("/status-mesin", HTTP_OPTIONS, handleOptions);

  // Endpoint trigger manual/eksternal jika diperlukan
  server.on("/trigger-in", HTTP_GET, []() {
    setCORS(); 
    server.send(200, "text/plain", "OK"); 
    hangerInCount++;
    kirimTriggerKeWeb("input");
    kirimTriggerKeWeb("input", 1);
  });
  
  server.on("/trigger-out", HTTP_GET, []() {
    setCORS(); 
    server.send(200, "text/plain", "OK"); 
    hangerOutCount++;
    kirimTriggerKeWeb("output");
    kirimTriggerKeWeb("output", 1);
  });
  
  server.begin();

  // STABILISASI & RE-SINKRONISASI PEMBACAAN PIN TEPAT SEBELUM LOOP UTAMA
  // Memastikan tidak ada false edge trigger akibat glitch bootup atau wifi
  delay(200);
  lastBtnOnState   = digitalRead(PIN_BTN_ON);
  lastBtnOffState  = digitalRead(PIN_BTN_OFF);
  lastSensInState  = digitalRead(PIN_SENS_IN);
  lastSensOutState = digitalRead(PIN_SENS_OUT);

  Serial.println("------------------------------------------");
  Serial.printf("[PIN INITIAL STATE]\n");
  Serial.printf(" - Pin 12 (PB ON  - NO): %s\n", lastBtnOnState == LOW ? "LOW (Tertutup)" : "HIGH (Terbuka/Standby)");
  Serial.printf(" - Pin 13 (PB OFF - NC): %s\n", lastBtnOffState == LOW ? "LOW (Tertutup/Standby)" : "HIGH (Terbuka)");
  Serial.printf(" - Status Mesin Awal   : %s\n", isProduksi ? "AKTIF (ON)" : "STANDBY (OFF)");
  Serial.println("------------------------------------------");
  Serial.println(">> ESP32 SIAP! Tekan PB ON (Pin 12) untuk memulai produksi. <<\n");
}

// ================================================================
// LOOP UTAMA
// ================================================================
void loop() {
  server.handleClient();
  unsigned long now = millis();

  // 1. BACA DATA SERIAL RS-485 (Jika sensor lewat bus serial RS485)
  if (Serial2.available()) {
    String dataRs485 = Serial2.readStringUntil('\n');
    dataRs485.trim();
    if (dataRs485.length() > 0) {
      Serial.println("[RS485-RX] Diterima: " + dataRs485);

      // Trigger Input lewat RS-485 (Hanya jika produksi ON)
      if (dataRs485.indexOf("IN") >= 0 || dataRs485 == "1") {
        if (isProduksi && (now - lastTriggerInTime > DEBOUNCE_TIME)) {
          lastTriggerInTime = now;
          hangerInCount++;
          Serial.println("[AUTO-MARK] Hanger Masuk Terdeteksi via RS-485 -> Kirim Nilai 1!");
          kirimTriggerKeWeb("input", 1);
        }
      }
      // Trigger Output lewat RS-485 (Hanya jika produksi ON)
      else if (dataRs485.indexOf("OUT") >= 0 || dataRs485 == "2") {
        if (isProduksi && (now - lastTriggerOutTime > DEBOUNCE_TIME)) {
          lastTriggerOutTime = now;
          hangerOutCount++;
          Serial.println("[AUTO-SHUTTER] Hanger Output Terdeteksi via RS-485 -> Trigger Kamera (Kirim Nilai 1)!");
          // Flash / Trigger Shutter Kamera Output via RS-485
          if (flashOutputMode == 2) {
            ledcWrite(PIN_FLASH, flashBrightness > 0 ? flashBrightness : 220);
            kirimTriggerKeWeb("output", 1);
            delay(150);
            ledcWrite(PIN_FLASH, 0);
          } else {
            // Mode Solid (Nyala Terus) atau OFF: Langsung kirim trigger tanpa delay
            kirimTriggerKeWeb("output", 1);
          }
        }
      }
    }
  }

  // 2. KONTROL MESIN ON/OFF (TOMBOL HARDWARE)
  // --- TOMBOL ON: NO (Pin 12) -> Normal=HIGH, Ditekan=LOW (falling edge) ---
  bool currentBtnOnState = digitalRead(PIN_BTN_ON);
  if (currentBtnOnState == LOW && lastBtnOnState == HIGH) {
    if (!isProduksi) {
      isProduksi = true; 
      hangerInCount = 0; 
      hangerOutCount = 0;
      Serial.println("\n>>> MESIN PRODUKSI DI-START (ON - Kirim Nilai 1 ke Web) <<<");
      kirimTriggerKeWeb("btn_on", 1);
      sendDataToLaravel();
    } 
    delay(50);
  } 
  lastBtnOnState = currentBtnOnState;

  // --- TOMBOL OFF: NC (Pin 13) -> Normal=LOW, Ditekan=HIGH (rising edge) ---
  bool currentBtnOffState = digitalRead(PIN_BTN_OFF);
  if (currentBtnOffState == HIGH && lastBtnOffState == LOW) {
    if (isProduksi) { 
      isProduksi = false; 
      Serial.println("\n>>> MESIN PRODUKSI DI-STOP (OFF - Kirim Nilai 0 ke Web) <<<"); 
      kirimTriggerKeWeb("btn_off", 0);
      sendDataToLaravel();
    }
    delay(50);
  } 
  lastBtnOffState = currentBtnOffState;

  // 3. PEMBACAAN PIN SENSOR FISIK LANGSUNG (PIN 14 & 27)
  // HANYA AKTIF JIKA MESIN PRODUKSI DALAM STATUS ON (isProduksi == true)
  if (isProduksi) {
    // --- PHOTOSENSOR 1 (INLET / INPUT): AUTO-MARKING HANGER ---
    bool currentSensInState = digitalRead(PIN_SENS_IN);
    if (currentSensInState == LOW && lastSensInState == HIGH) {
      if (now - lastTriggerInTime > DEBOUNCE_TIME) {
        lastTriggerInTime = now;
        hangerInCount++;
        Serial.printf("[AUTO-MARK] Hanger #%d Masuk ke Line (Pin 14) -> Kirim Nilai 1 ke Web!\n", hangerInCount);
        
        // Kirim sinyal marking langsung ke Web Dashboard (Nilai 1)
        kirimTriggerKeWeb("input", 1);
      }
    }
    lastSensInState = currentSensInState;

    // --- PHOTOSENSOR 2 (OUTLET / OUTPUT): AUTO-SHUTTER TRIGGER KAMERA ---
    bool currentSensOutState = digitalRead(PIN_SENS_OUT);
    if (currentSensOutState == LOW && lastSensOutState == HIGH) {
      if (now - lastTriggerOutTime > DEBOUNCE_TIME) {
        lastTriggerOutTime = now;
        hangerOutCount++;
        Serial.printf("[AUTO-SHUTTER] Hanger #%d Tiba di Depan Kamera (Pin 27) -> Kirim Nilai 1 ke Web!\n", hangerOutCount);
        
        // Flash / Trigger Shutter Kamera Output via Pin 27
        if (flashOutputMode == 2) {
          ledcWrite(PIN_FLASH, flashBrightness > 0 ? flashBrightness : 220);
          kirimTriggerKeWeb("output", 1);
          delay(200);
          ledcWrite(PIN_FLASH, 0); // Matikan flash kembali
        } else {
          // Mode Solid (Nyala Terus) atau OFF: Cahaya sudah konstan, langsung kirim trigger
          kirimTriggerKeWeb("output", 1);
        }
      }
    }
    lastSensOutState = currentSensOutState;
  } 
  else {
    // Jika mesin OFF, simpan kondisi sensor agar tidak loncat saat mesin baru di-ON-kan
    lastSensInState  = digitalRead(PIN_SENS_IN);
    lastSensOutState = digitalRead(PIN_SENS_OUT);
  }

  // 4. PUSH DATA STATUS KE SERVER SECARA BERKALA
  if (now - lastPostTime > POST_INTERVAL) {
    sendDataToLaravel();
    lastPostTime = now;
  }
}