#include <Arduino.h>
#include "esp_camera.h"
#include <WiFi.h>
#include <HTTPClient.h>
#include "soc/soc.h"
#include "soc/rtc_cntl_reg.h"

// ===========================
// Select camera model in board_config.h
// ===========================
#include "board_config.h"

// ===========================
// Enter your WiFi credentials & Server IP
// ===========================
const char *ssid = "SSID";
const char *password = "12345678";
const char *server_ip = "10.171.46.201:8000"; // IP PC / Server Dashboard Laragon (Port 8000)


const bool USE_STATIC_IP = true;

IPAddress cam_static_ip(10, 171, 46, 87);      // Ganti dengan IP Kamera yang Anda inginkan
IPAddress cam_gateway(10, 171, 46, 193);       // Gateway router Anda (dari ipconfig)
IPAddress cam_subnet(255, 255, 255, 0);        // Subnet mask
IPAddress cam_dns(8, 8, 8, 8);                 // DNS opsional

void startCameraServer();
void setupLedFlash();

void setup() {
  WRITE_PERI_REG(RTC_CNTL_BROWN_OUT_REG, 0); // Matikan Brownout Detector agar tidak reboot saat tegangan drop

  Serial.begin(115200);
  Serial.setTimeout(20); // Timeout UART cepat 20ms agar video stream tidak tersendat jika data kabel belum selesai
  Serial.setDebugOutput(true);
  Serial.println();
  Serial.println("==========================================");
  Serial.println("         ESP32-CAM DIAGNOSTIC BOOT        ");
  Serial.println("==========================================");
  Serial.printf("[SYSTEM] Free Internal Heap : %d KB\n", ESP.getFreeHeap() / 1024);
  Serial.printf("[SYSTEM] PSRAM Terdeteksi   : %s\n", psramFound() ? "YA (PSRAM AKTIF)" : "TIDAK");
  if (psramFound()) {
    Serial.printf("[SYSTEM] Total PSRAM         : %d KB\n", ESP.getPsramSize() / 1024);
    Serial.printf("[SYSTEM] Free PSRAM          : %d KB\n", ESP.getFreePsram() / 1024);
  }
  Serial.println("==========================================");

  camera_config_t config;
  memset(&config, 0, sizeof(config)); // Bersihkan memori struct agar tidak ada nilai sampah
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer = LEDC_TIMER_0;
  config.pin_d0 = Y2_GPIO_NUM;
  config.pin_d1 = Y3_GPIO_NUM;
  config.pin_d2 = Y4_GPIO_NUM;
  config.pin_d3 = Y5_GPIO_NUM;
  config.pin_d4 = Y6_GPIO_NUM;
  config.pin_d5 = Y7_GPIO_NUM;
  config.pin_d6 = Y8_GPIO_NUM;
  config.pin_d7 = Y9_GPIO_NUM;
  config.pin_xclk = XCLK_GPIO_NUM;
  config.pin_pclk = PCLK_GPIO_NUM;
  config.pin_vsync = VSYNC_GPIO_NUM;
  config.pin_href = HREF_GPIO_NUM;
  config.pin_sccb_sda = SIOD_GPIO_NUM;
  config.pin_sccb_scl = SIOC_GPIO_NUM;
  config.pin_pwdn = PWDN_GPIO_NUM;
  config.pin_reset = RESET_GPIO_NUM;
  config.xclk_freq_hz = 20000000; // 20 MHz (Standar OV2640 agar video live mulus 20-25 FPS)
  config.pixel_format = PIXFORMAT_JPEG;
  config.grab_mode = CAMERA_GRAB_LATEST; // Selalu ambil frame terbaru agar tidak freeze
  config.frame_size = FRAMESIZE_VGA;
  config.jpeg_quality = 12;
  config.fb_count = 2; // 2 Buffer di PSRAM untuk kelancaran streaming
  config.fb_location = CAMERA_FB_IN_PSRAM;

#if defined(CAMERA_MODEL_ESP_EYE)
  pinMode(13, INPUT_PULLUP);
  pinMode(14, INPUT_PULLUP);
#endif

  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("[FATAL] Camera init gagal! Error kode: 0x%x\n", err);
    Serial.println("Tips: Cek pita fleksibel kamera (kendor/miring) atau ganti Board ke 'AI Thinker ESP32-CAM'.");
    return;
  }

  sensor_t *s = esp_camera_sensor_get();
  if (s) {
    s->set_exposure_ctrl(s, 1); // Wajib ON agar auto-exposure jalan dan sensor tidak hang/freeze
    s->set_whitebal(s, 1);      // Aktifkan AWB
    if (s->id.PID == OV3660_PID) {
      s->set_vflip(s, 1);        
      s->set_brightness(s, 1);   
      s->set_saturation(s, -2);  
    }
  }

#if defined(CAMERA_MODEL_M5STACK_WIDE) || defined(CAMERA_MODEL_M5STACK_ESP32CAM)
  s->set_vflip(s, 1);
  s->set_hmirror(s, 1);
#endif

#if defined(CAMERA_MODEL_ESP32S3_EYE)
  s->set_vflip(s, 1);
#endif

#if defined(LED_GPIO_NUM)
  setupLedFlash();
#endif

  if (USE_STATIC_IP) {
    if (!WiFi.config(cam_static_ip, cam_gateway, cam_subnet, cam_dns)) {
      Serial.println("[WIFI] Konfigurasi Static IP Gagal! Menggunakan DHCP router...");
    } else {
      Serial.print("[WIFI] Mode Static IP Aktif: ");
      Serial.println(cam_static_ip);
    }
  }

  WiFi.setAutoReconnect(true);
  WiFi.begin(ssid, password);
  WiFi.setSleep(false);

  Serial.print("Menghubungkan ke WiFi");
  unsigned long wifiStart = millis();
  while (WiFi.status() != WL_CONNECTED && (millis() - wifiStart < 10000)) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("");
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("[WIFI] Berhasil terhubung!");
  } else {
    Serial.println("[WIFI] Belum terhubung saat boot, kamera akan reconnect otomatis di background...");
  }

  startCameraServer();

  Serial.print("Camera Ready! Use 'http://");
  Serial.print(WiFi.localIP());
  Serial.println("' to connect");

  // Otomatis sinkronkan IP Kamera ke Web Dashboard Laragon
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    String setIpUrl = String("http://") + server_ip + "/api/set-cam-ip?ip=" + WiFi.localIP().toString();
    http.begin(setIpUrl);
    int code = http.GET();
    Serial.printf("[CAM-SYNC] Auto-register IP ke Dashboard: %s (Status: %d)\n", setIpUrl.c_str(), code);
    http.end();
  }
}

void loop() {
  unsigned long now = millis();
  static unsigned long lastCamWifiRetryMs = 0;

  // Auto-reconnect non-blocking jika WiFi sempat terputus
  if (WiFi.status() != WL_CONNECTED && (now - lastCamWifiRetryMs >= 10000)) {
    lastCamWifiRetryMs = now;
    WiFi.reconnect();
  }

  // ==========================================
  // MENDENGARKAN PERINTAH KABEL DARI DEVKIT
  // ==========================================
  if (Serial.available()) {
    String data = Serial.readStringUntil('\n');
    data.trim(); // Bersihkan karakter kosong/spasi dari ujung pesan
    
    sensor_t * s = esp_camera_sensor_get();
    if(!s) return;
    
    // 1. Ganti Resolusi (Contoh masuk: "RES:6")
    if(data.startsWith("RES:")) {
      int val = data.substring(4).toInt();
      s->set_framesize(s, (framesize_t)val);
    } 
    // 2. Ganti Flip Vertikal (Contoh masuk: "FLIP:1")
    else if(data.startsWith("FLIP:")) {
      int val = data.substring(5).toInt();
      s->set_vflip(s, val);
    }
    // 3. Ganti Kualitas Kompresi (Contoh masuk: "QUAL:12")
    else if(data.startsWith("QUAL:")) {
      int val = data.substring(5).toInt();
      s->set_quality(s, val);
    }
    // 4. Ganti Brightness (Contoh masuk: "BRIGHT:1")
    else if(data.startsWith("BRIGHT:")) {
      int val = data.substring(7).toInt();
      s->set_brightness(s, val);
    }
    // 5. Ganti Contrast (Contoh masuk: "CONTRAST:1")
    else if(data.startsWith("CONTRAST:")) {
      int val = data.substring(9).toInt();
      s->set_contrast(s, val);
    }
  }
}