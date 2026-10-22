#include <WiFi.h>
#include <WebServer.h>
#include <HTTPClient.h>

// ================================================================
// KONFIGURASI WIFI & DASHBOARD PAINTING
// ================================================================
const char* ssid     = "SSID";       // Ganti dengan nama WiFi pabrik/laptop
const char* password = "12345678";   // Ganti dengan password WiFi

// Ganti IP Komputer/Laptop yang menjalankan Laragon (misal: 192.168.1.50)
const char* server_ip = "192.168.1.50"; 

// Base URL Dashboard Painting
String dashboard_trigger_url = String("http://") + server_ip + "/api/sensor-trigger";
String dashboard_confirm_url = String("http://") + server_ip + "/api/confirm-hanger-output";
String master_painting_url   = String("http://") + server_ip + "/master-painting/public/api/sensor-data";

WebServer server(80);

// ================================================================
// DEFINISI PIN
// ================================================================
const int PIN_BTN_ON   = 13; // Tombol Mesin Start
const int PIN_BTN_OFF  = 12; // Tombol Mesin Stop
const int PIN_SENS_IN  = 14; // Photosensor Input (Infeed)
const int PIN_SENS_OUT = 27; // Photosensor Output (Outfeed Shutter)

// Pin HPL Flash Kamera
const int PIN_FLASH_1  = 15; // Lampu Flash Input (Opsional)
const int PIN_FLASH_2  = 26; // Lampu Flash Output (Area Kamera)

// RS-485 to TTL UART (Serial2)
#define RS485_RX_PIN 16 // RO Modul RS485 ke Pin 16 ESP32
#define RS485_TX_PIN 17 // DI Modul RS485 ke Pin 17 ESP32

// ================================================================
// VARIABEL STATUS & COUNTER HANGER
// ================================================================
bool isProduksi = false;
int hangerInCount  = 0;
int hangerOutCount = 0;

bool lastBtnOnState   = HIGH;
bool lastBtnOffState  = HIGH;
bool lastSensInState  = HIGH;
bool lastSensOutState = HIGH;

// Debounce waktu sensor agar tidak double trigger (1.2 detik per hanger)
unsigned long lastTriggerInTime  = 0;
unsigned long lastTriggerOutTime = 0;
const unsigned long DEBOUNCE_TIME = 1200;

unsigned long lastPostTime = 0;
const unsigned long POST_INTERVAL = 2000; // Kirim status ke server tiap 2 detik

// ================================================================
// FUNGSI PENGIRIMAN TRIGGER KE WEB DASHBOARD (HTTP CLIENT)
// ================================================================
void kirimTriggerKeWeb(String tipe) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    String targetUrl = dashboard_trigger_url + "?type=" + tipe;
    
    http.begin(targetUrl);
    http.setTimeout(1500); // Timeout cepat agar loop tidak terhambat
    int httpCode = http.GET();
    
    if (httpCode > 0) {
      Serial.printf("[WEB-API] Trigger '%s' berhasil dikirim ke Web Dashboard (HTTP %d)\n", tipe.c_str(), httpCode);
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
  if (server.hasArg("id") && server.hasArg("val")) {
    int idLampu = server.arg("id").toInt();
    int val = server.arg("val").toInt();
    if (idLampu == 1) ledcWrite(PIN_FLASH_1, val);
    else if (idLampu == 2) ledcWrite(PIN_FLASH_2, val);
  }
  server.send(200, "text/plain", "OK");
}

void handleStatus() {
  setCORS();
  String json = "{\"isProduksi\":" + String(isProduksi ? "true" : "false") + 
                ",\"in\":" + String(hangerInCount) + 
                ",\"out\":" + String(hangerOutCount) + "}";
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

  // Inisialisasi Lampu Flash PWM
  ledcAttach(PIN_FLASH_1, 5000, 8); ledcWrite(PIN_FLASH_1, 0);
  ledcAttach(PIN_FLASH_2, 5000, 8); ledcWrite(PIN_FLASH_2, 0);

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
  });
  
  server.on("/trigger-out", HTTP_GET, []() {
    setCORS(); 
    server.send(200, "text/plain", "OK"); 
    hangerOutCount++;
    kirimTriggerKeWeb("output");
  });
  
  server.begin();
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

      // Trigger Input lewat RS-485
      if (dataRs485.indexOf("IN") >= 0 || dataRs485 == "1") {
        if (now - lastTriggerInTime > DEBOUNCE_TIME) {
          lastTriggerInTime = now;
          hangerInCount++;
          Serial.println("[AUTO-MARK] Hanger Masuk Terdeteksi via RS-485!");
          kirimTriggerKeWeb("input");
        }
      }
      // Trigger Output lewat RS-485
      else if (dataRs485.indexOf("OUT") >= 0 || dataRs485 == "2") {
        if (now - lastTriggerOutTime > DEBOUNCE_TIME) {
          lastTriggerOutTime = now;
          hangerOutCount++;
          Serial.println("[AUTO-SHUTTER] Hanger Output Terdeteksi via RS-485 -> Trigger Kamera!");
          // Flash sejenak saat motret
          ledcWrite(PIN_FLASH_2, 200);
          kirimTriggerKeWeb("output");
          delay(150);
          ledcWrite(PIN_FLASH_2, 0);
        }
      }
    }
  }

  // 2. KONTROL MESIN ON/OFF (TOMBOL HARDWARE)
  bool currentBtnOnState = digitalRead(PIN_BTN_ON);
  if (currentBtnOnState == LOW && lastBtnOnState == HIGH) {
    if (!isProduksi) {
      isProduksi = true; 
      hangerInCount = 0; 
      hangerOutCount = 0;
      Serial.println("\n>>> MESIN PRODUKSI DI-START (ON) <<<");
    } 
    delay(50);
  } 
  lastBtnOnState = currentBtnOnState;

  bool currentBtnOffState = digitalRead(PIN_BTN_OFF);
  if (currentBtnOffState == LOW && lastBtnOffState == HIGH) {
    if (isProduksi) { 
      isProduksi = false; 
      Serial.println("\n>>> MESIN PRODUKSI DI-STOP (OFF) <<<"); 
    }
    delay(50);
  } 
  lastBtnOffState = currentBtnOffState;

  // 3. PEMBACAAN PIN SENSOR FISIK LANGSUNG (PIN 14 & 27)
  if (isProduksi) {
    // --- PHOTOSENSOR 1 (INLET / INPUT): AUTO-MARKING HANGER ---
    bool currentSensInState = digitalRead(PIN_SENS_IN);
    if (currentSensInState == LOW && lastSensInState == HIGH) {
      if (now - lastTriggerInTime > DEBOUNCE_TIME) {
        lastTriggerInTime = now;
        hangerInCount++;
        Serial.printf("[AUTO-MARK] Hanger #%d Masuk ke Line (Pin 14)!\n", hangerInCount);
        
        // Kirim sinyal marking langsung ke Web Dashboard
        kirimTriggerKeWeb("input");
      }
    }
    lastSensInState = currentSensInState;

    // --- PHOTOSENSOR 2 (OUTLET / OUTPUT): AUTO-SHUTTER TRIGGER KAMERA ---
    bool currentSensOutState = digitalRead(PIN_SENS_OUT);
    if (currentSensOutState == LOW && lastSensOutState == HIGH) {
      if (now - lastTriggerOutTime > DEBOUNCE_TIME) {
        lastTriggerOutTime = now;
        hangerOutCount++;
        Serial.printf("[AUTO-SHUTTER] Hanger #%d Tiba di Depan Kamera (Pin 27) -> Memicu Roboflow!\n", hangerOutCount);
        
        // Nyalakan Flash LED sejenak untuk pencahayaan kamera
        ledcWrite(PIN_FLASH_2, 220);
        
        // Kirim trigger shutter kamera ke Web Dashboard
        kirimTriggerKeWeb("output");
        
        delay(200);
        ledcWrite(PIN_FLASH_2, 0); // Matikan flash kembali
      }
    }
    lastSensOutState = currentSensOutState;
  } 
  else {
    lastSensInState  = digitalRead(PIN_SENS_IN);
    lastSensOutState = digitalRead(PIN_SENS_OUT);
  }

  // 4. PUSH DATA STATUS KE SERVER SECARA BERKALA
  if (now - lastPostTime > POST_INTERVAL) {
    sendDataToLaravel();
    lastPostTime = now;
  }
}
