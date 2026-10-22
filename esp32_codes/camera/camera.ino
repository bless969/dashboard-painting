#include <Arduino.h>
#include "esp_camera.h"
#include <WiFi.h>
#include <HTTPClient.h>

// ===========================
// Select camera model in board_config.h
// ===========================
#include "board_config.h"

// ===========================
// Enter your WiFi credentials & Server IP
// ===========================
const char *ssid = "SSID";
const char *password = "12345678";
const char *server_ip = "192.168.1.50"; // IP PC / Server Dashboard Laragon

void startCameraServer();
void setupLedFlash();

void setup() {
  Serial.begin(115200);
  Serial.setDebugOutput(true);
  Serial.println();

  camera_config_t config;
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
  config.xclk_freq_hz = 20000000;
  config.frame_size = FRAMESIZE_UXGA;
  config.pixel_format = PIXFORMAT_JPEG;  // for streaming
  config.grab_mode = CAMERA_GRAB_WHEN_EMPTY;
  config.fb_location = CAMERA_FB_IN_PSRAM;
  config.jpeg_quality = 12;
  config.fb_count = 1;

  if (config.pixel_format == PIXFORMAT_JPEG) {
    if (psramFound()) {
      config.jpeg_quality = 10;
      config.fb_count = 2;
      config.grab_mode = CAMERA_GRAB_LATEST;
    } else {
      config.frame_size = FRAMESIZE_SVGA;
      config.fb_location = CAMERA_FB_IN_DRAM;
    }
  } else {
    config.frame_size = FRAMESIZE_240X240;
#if CONFIG_IDF_TARGET_ESP32S3
    config.fb_count = 2;
#endif
  }

#if defined(CAMERA_MODEL_ESP_EYE)
  pinMode(13, INPUT_PULLUP);
  pinMode(14, INPUT_PULLUP);
#endif

  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("Camera init failed with error 0x%x", err);
    return;
  }

  sensor_t *s = esp_camera_sensor_get();
  if (s->id.PID == OV3660_PID) {
    s->set_vflip(s, 1);        
    s->set_brightness(s, 1);   
    s->set_saturation(s, -2);  
  }
  
  if (config.pixel_format == PIXFORMAT_JPEG) {
    s->set_framesize(s, FRAMESIZE_QVGA);
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

  WiFi.begin(ssid, password);
  WiFi.setSleep(false);

  Serial.print("WiFi connecting");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("");
  Serial.println("WiFi connected");

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
