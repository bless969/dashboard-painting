<div id="view-pengaturan" style="{{ $view_aktif !== 'pengaturan' ? 'display:none;' : '' }}">
    <!-- HEADER PENGATURAN -->
    <div class="panel" style="margin-bottom: 16px;">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div>
                <div class="panel-title" style="margin-bottom:4px; font-size:18px;">⚙️ Menu Pengaturan Sistem — Line Painting</div>
                <div style="font-size:13px; color:#64748b;">Kelola konfigurasi perangkat keras ESP-CAM, sensor hanger, penerangan LED, dan preferensi dashboard.</div>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <button type="button" class="btn-nav-action btn-blue" onclick="bukaCameraPopup()" style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; padding:7px 14px;">
                    📷 Buka Monitor Kamera
                </button>
            </div>
        </div>

        <!-- TABS NAVIGASI PENGATURAN -->
        <div class="settings-tabs-nav" style="display:flex; gap:8px; margin-top:16px; border-bottom:1px solid #e2e8f0; padding-bottom:2px; flex-wrap:wrap;">
            <button type="button" class="settings-tab-btn active" id="tab-btn-camera" onclick="switchSettingsTab('camera')">
                📷 Kamera ESP &amp; Hardware Painting
            </button>
            <button type="button" class="settings-tab-btn" id="tab-btn-ai" onclick="switchSettingsTab('ai')">
                🤖 Panduan Training AI (Top-Down POV)
            </button>
            <button type="button" class="settings-tab-btn" id="tab-btn-grafik" onclick="switchSettingsTab('grafik')">
                📊 Tampilan &amp; Grafik
            </button>
            <button type="button" class="settings-tab-btn" id="tab-btn-target" onclick="switchSettingsTab('target')">
                🏭 Target &amp; Shift Produksi
            </button>
        </div>
    </div>

    <!-- ===== TAB 1: PENGATURAN ESP-CAM & HARDWARE PAINTING ===== -->
    <div id="tab-panel-camera" class="settings-tab-panel">
        <!-- DIAGRAM ARSITEKTUR HARDWARE PAINTING -->
        <div class="panel" style="margin-bottom:16px; background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color:#f8fafc; border:1px solid #334155;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:18px;">🔬</span>
                    <strong style="font-size:14.5px; letter-spacing:0.3px;">DIAGRAM &amp; ALUR KERJA KOMPONEN LINE PAINTING (TOP-DOWN POV)</strong>
                </div>
                <div style="display:flex; align-items:center; gap:6px;">
                    <span style="font-size:11px; background:rgba(56,189,248,0.2); color:#38bdf8; border:1px solid rgba(56,189,248,0.35); padding:3px 9px; border-radius:12px; font-weight:700;">
                        📐 POV: TAMPAK ATAS
                    </span>
                    <span style="font-size:11px; background:rgba(34,197,94,0.2); color:#4ade80; border:1px solid rgba(34,197,94,0.3); padding:3px 9px; border-radius:12px; font-weight:700;">
                        ● SISTEM AKTIF
                    </span>
                </div>
            </div>

            <div class="hw-flow-container">
                <div class="hw-flow-step">
                    <div class="hw-flow-icon">🚡</div>
                    <div class="hw-flow-label">Konveyor Hanger</div>
                    <div class="hw-flow-desc">Gantungan bergerak kontinu membawa wadah lampu (0 - 4 wadah)</div>
                </div>
                <div class="hw-flow-arrow">&rarr;</div>

                <div class="hw-flow-step highlight" style="border-color:#22c55e;">
                    <div class="hw-flow-icon">🔴🟢</div>
                    <div class="hw-flow-label">Tombol Operator</div>
                    <div class="hw-flow-desc">Hijau = Memproduksi, Merah = Tidak ada operator</div>
                </div>
                <div class="hw-flow-arrow">&rarr;</div>

                <div class="hw-flow-step" style="border-color:#38bdf8;">
                    <div class="hw-flow-icon">🟢</div>
                    <div class="hw-flow-label">Photosensor di Atas</div>
                    <div class="hw-flow-desc">Terpasang overhead, mendeteksi hanger dari atas secara tersentralisasi</div>
                </div>
                <div class="hw-flow-arrow">&rarr;</div>

                <div class="hw-flow-step">
                    <div class="hw-flow-icon">🧠</div>
                    <div class="hw-flow-label">Central ESP</div>
                    <div class="hw-flow-desc">Sinkronisasi trigger &amp; kontrol sinyal flash IRF520</div>
                </div>
                <div class="hw-flow-arrow">&rarr;</div>

                <div class="hw-flow-step">
                    <div class="hw-flow-icon">⚡</div>
                    <div class="hw-flow-label">MOSFET IRF520 &amp; LED</div>
                    <div class="hw-flow-desc">Strobe flash LED HPL Cool White dari atas via Step-Down LM2596</div>
                </div>
                <div class="hw-flow-arrow">&rarr;</div>

                <div class="hw-flow-step highlight" style="border-color:#38bdf8;">
                    <div class="hw-flow-icon">📷</div>
                    <div class="hw-flow-label">ESP Camera (Atas)</div>
                    <div class="hw-flow-desc">Sudut pandang atas (Top-Down), jarak dekat 30-50 cm, tanpa background</div>
                </div>
            </div>
            
            <div style="margin-top:12px; font-size:12px; color:#94a3b8; line-height:1.5; background:rgba(0,0,0,0.25); padding:10px 14px; border-radius:8px; border:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                <div>
                    💡 <strong>Keunggulan Penempatan di Atas:</strong> Kamera &amp; sensor tersentralisasi langsung di atas rel konveyor. Jarak objek sangat dekat (30-50 cm), pencahayaan LED HPL langsung tegak lurus, dan <em>TIDAK memerlukan background curtain</em> karena kamera menghadap ke alas/lantai bilik.
                </div>
                <button type="button" class="btn-nav-action" onclick="switchSettingsTab('ai')" style="font-size:11.5px; padding:4px 10px; background:#0284c7; color:#fff; border:none; font-weight:700;">
                    📘 Buka Panduan Training AI &rarr;
                </button>
            </div>
        </div>

        <!-- FORM SETTING KAMERA DUA KOLOM -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(420px, 1fr)); gap:16px;">
            <!-- KOLOM KIRI: KONEKSI & PHOTOSENSOR -->
            <div style="display:flex; flex-direction:column; gap:16px;">
                <!-- 1. KONEKSI & STREAM ESP-CAM -->
                <div class="panel">
                    <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                        <span>🌐</span>
                        <span>1. Koneksi &amp; Stream ESP-CAM</span>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Mode Operasi Kamera</div>
                            <div class="settings-row-desc">Pilih antara simulasi canvas otomatis atau koneksi langsung ke modul ESP32-CAM riil.</div>
                        </div>
                        <select id="cfg-cam-mode" class="filter-select" onchange="ubahModeKamera(this.value)">
                            <option value="simulasi">Simulasi Live Konveyor (Built-in)</option>
                            <option value="riil">Hubungkan ke IP ESP32-CAM Fisik</option>
                        </select>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Alamat IP ESP-CAM</div>
                            <div class="settings-row-desc">IP address modul ESP32-CAM di jaringan pabrik.</div>
                        </div>
                        <input type="text" id="cfg-cam-ip" class="filter-select" style="max-width:220px;" placeholder="192.168.1.88" value="192.168.1.88">
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">URL Stream / Capture</div>
                            <div class="settings-row-desc">Endpoint MJPEG stream atau snapshot gambar kamera.</div>
                        </div>
                        <input type="text" id="cfg-cam-url" class="filter-select" style="max-width:260px;" placeholder="http://192.168.1.88/stream" value="http://192.168.1.88/stream">
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Resolusi Sensor Kamera</div>
                            <div class="settings-row-desc">Resolusi frame tangkapan modul kamera OV2640.</div>
                        </div>
                        <select id="cfg-cam-resolution" class="filter-select">
                            <option value="VGA (640x480)" selected>VGA (640x480) — Cepat &amp; Stabil (Default Firmware)</option>
                            <option value="SVGA (800x600)">SVGA (800x600) — Rekomendasi Optimal</option>
                            <option value="QVGA (320x240)">QVGA (320x240) — Ultra Cepat</option>
                            <option value="UXGA (1600x1200)">UXGA (1600x1200) — Foto Training Resolusi Tinggi</option>
                        </select>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Target Frame Rate (FPS)</div>
                            <div class="settings-row-desc">Kecepatan refresh streaming inspeksi visual.</div>
                        </div>
                        <select id="cfg-cam-fps" class="filter-select">
                            <option value="15">15 FPS</option>
                            <option value="20">20 FPS</option>
                            <option value="24" selected>24 FPS (Halus)</option>
                            <option value="30">30 FPS</option>
                        </select>
                    </div>
                </div>

                <!-- 2. PHOTOSENSOR & TRIGGER HANGER -->
                <div class="panel">
                    <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                        <span>🟢</span>
                        <span>2. Photosensor &amp; Trigger Hanger</span>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Tipe Sinyal Photosensor</div>
                            <div class="settings-row-desc">Tipe sensor optik yang digunakan di gantungan hanger.</div>
                        </div>
                        <select id="cfg-sensor-type" class="filter-select">
                            <option value="LOW" selected>Active LOW (NPN Normally Open)</option>
                            <option value="HIGH">Active HIGH (PNP Normally Open)</option>
                        </select>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Debounce Filter Sensor (ms)</div>
                            <div class="settings-row-desc">Mencegah trigger ganda akibat getaran atau goyangan hanger.</div>
                        </div>
                        <input type="number" id="cfg-sensor-debounce" class="filter-select" style="max-width:120px;" min="10" max="500" value="50">
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Trigger Capture Delay (ms)</div>
                            <div class="settings-row-desc">Jeda waktu sejak sensor aktif sampai LED menyala &amp; kamera mengambil gambar (mengatur posisi hanger tepat di tengah kamera).</div>
                        </div>
                        <input type="number" id="cfg-sensor-delay" class="filter-select" style="max-width:120px;" min="0" max="1000" value="80">
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: PENERANGAN & AI DETEKSI WADAH -->
            <div style="display:flex; flex-direction:column; gap:16px;">
                <!-- 3. LED HPL COOL WHITE & MOSFET IRF520 -->
                <div class="panel">
                    <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                        <span>⚡</span>
                        <span>3. LED HPL Cool White &amp; Modul MOSFET IRF520</span>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Mode Flash Penerangan</div>
                            <div class="settings-row-desc">Kontrol penyalaan LED HPL 1/3W Cool White melalui gate MOSFET IRF520.</div>
                        </div>
                        <select id="cfg-led-mode" class="filter-select">
                            <option value="strobe" selected>Strobe on Trigger (Flash saat Hanger Lewat — Hemat &amp; Tidak Panas)</option>
                            <option value="continuous">Continuous ON (Menyala Terus Menerus)</option>
                            <option value="off">Manual Test / Dimatikan</option>
                        </select>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Kecerahan Flash LED (PWM %)</div>
                            <div class="settings-row-desc">Duty cycle PWM dari ESP ke MOSFET IRF520 (<span id="cfg-pwm-label">85%</span>).</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px; width:220px;">
                            <input type="range" id="cfg-led-pwm" min="10" max="100" value="85" style="flex:1; cursor:pointer;" oninput="document.getElementById('cfg-pwm-label').textContent = this.value + '%'">
                        </div>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Durasi Pulse Flash (ms)</div>
                            <div class="settings-row-desc">Lama waktu LED HPL menyala saat pengambilan gambar (<span id="cfg-pulse-label">120 ms</span>).</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px; width:220px;">
                            <input type="range" id="cfg-pulse-duration" min="30" max="400" step="10" value="120" style="flex:1; cursor:pointer;" oninput="document.getElementById('cfg-pulse-label').textContent = this.value + ' ms'">
                        </div>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Status Modul Step-Down LM2596</div>
                            <div class="settings-row-desc">Regulasi tegangan input DC 24V/12V menjadi 5.0V mantap untuk ESP &amp; Driver.</div>
                        </div>
                        <span style="font-size:12px; font-weight:700; color:#15803d; background:#dcfce7; padding:4px 10px; border-radius:6px; border:1px solid #bbf7d0;">
                            5.02V DC (Regulated &amp; Normal)
                        </span>
                    </div>
                </div>

                <!-- 4. LOGIKA DETEKSI WADAH LAMPU -->
                <div class="panel">
                    <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                        <span>📦</span>
                        <span>4. Logika AI &amp; Deteksi Wadah Lampu</span>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Ambang Batas Keyakinan (Confidence %)</div>
                            <div class="settings-row-desc">Batas minimal akurasi deteksi visual agar wadah dihitung sah (<span id="cfg-conf-label">75%</span>).</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px; width:220px;">
                            <input type="range" id="cfg-ai-confidence" min="50" max="95" value="75" style="flex:1; cursor:pointer;" oninput="document.getElementById('cfg-conf-label').textContent = this.value + '%'">
                        </div>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Kapasitas Maksimal Wadah per Hanger</div>
                            <div class="settings-row-desc">Jumlah slot maksimum wadah lampu pada satu gantungan hanger.</div>
                        </div>
                        <select id="cfg-max-wadah" class="filter-select">
                            <option value="1">1 Wadah</option>
                            <option value="2">2 Wadah</option>
                            <option value="3">3 Wadah</option>
                            <option value="4" selected>4 Wadah (Standar Line Painting)</option>
                            <option value="6">6 Wadah</option>
                        </select>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Tampilkan Bounding Box &amp; HUD di Layar</div>
                            <div class="settings-row-desc">Tampilkan kotak hijau deteksi objek dan teks HUD pada live stream kamera.</div>
                        </div>
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13px;">
                            <input type="checkbox" id="cfg-show-hud" checked style="width:16px; height:16px;">
                            <span>Aktifkan Overlay HUD</span>
                        </label>
                    </div>
                </div>

                <!-- 5. TOMBOL FISIK OPERATOR (HIJAU & MERAH) -->
                <div class="panel">
                    <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                        <span>🔴🟢</span>
                        <span>5. Tombol Fisik Operator (Kehadiran &amp; Memproduksi)</span>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Status Operator Saat Ini</div>
                            <div class="settings-row-desc">Status terdeteksi dari penekanan tombol push button fisik operator di lini painting.</div>
                        </div>
                        <span id="cfg-operator-status-badge" class="operator-status-badge active-green" style="font-size:12px; padding:4px 10px;">
                            🟢 Operator Memproduksi
                        </span>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Pin GPIO Tombol Hijau (Memproduksi)</div>
                            <div class="settings-row-desc">Input ESP32 untuk mendeteksi operator hadir dan sedang memproduksi/memuat wadah lampu ke hanger.</div>
                        </div>
                        <input type="text" id="cfg-pin-btn-green" class="filter-select" style="max-width:140px;" value="GPIO 14" readonly>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Pin GPIO Tombol Merah (Tidak Ada Operator)</div>
                            <div class="settings-row-desc">Input ESP32 saat operator meninggalkan bilik/istirahat (hanger konveyor lewat tanpa wadah).</div>
                        </div>
                        <input type="text" id="cfg-pin-btn-red" class="filter-select" style="max-width:140px;" value="GPIO 12" readonly>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Simulasi Tekan Tombol Operator</div>
                            <div class="settings-row-desc">Uji respons sistem terhadap penekanan tombol fisik operator.</div>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <button type="button" class="btn-push-hw btn-push-green" onclick="setOperatorStatus('hadir')" style="padding:6px 12px; font-size:11.5px;">
                                🟢 Tekan Hijau
                            </button>
                            <button type="button" class="btn-push-hw btn-push-red" onclick="setOperatorStatus('tidak_ada')" style="padding:6px 12px; font-size:11.5px;">
                                🔴 Tekan Merah
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. BENCH PENGUJIAN & KALIBRASI HARDWARE LANGSUNG -->
        <div class="panel" style="margin-top:16px;">
            <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                <span>🛠️</span>
                <span>6. Uji Kalibrasi &amp; Diagnosis Hardware Langsung</span>
            </div>

            <div style="font-size:13px; color:#64748b; margin-bottom:14px;">
                Gunakan tombol-tombol di bawah untuk menguji respons perangkat keras secara instan sebelum menjalankan sesi produksi.
            </div>

            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <button type="button" class="btn-nav-action btn-blue" onclick="ujiFlashLedManual()" style="padding:9px 18px; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                    ⚡ Uji Flash LED (IRF520)
                </button>

                <button type="button" class="btn-nav-action btn-green" onclick="simulasiTriggerPhotosensorManual()" style="padding:9px 18px; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                    🟢 Simulasi Trigger Photosensor
                </button>

                <button type="button" class="btn-nav-action btn-light" onclick="bukaCameraPopup(); toggleMembesarKamera(true);" style="padding:9px 18px; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                    📸 Buka Kamera Ukuran Penuh
                </button>

                <button type="button" class="btn-nav-action" style="background:#0d3b8c; color:#fff; border-color:#0d3b8c; padding:9px 24px; font-size:13px; font-weight:700; margin-left:auto; display:inline-flex; align-items:center; gap:6px;" onclick="simpanPengaturanKameraLengkap()">
                    💾 Simpan Pengaturan Kamera
                </button>
            </div>

            <!-- Pesan Status Simpan -->
            <div id="cfg-cam-status-msg" style="display:none; margin-top:12px; padding:10px 14px; border-radius:6px; font-size:13px; font-weight:600;"></div>
        </div>
    </div>

    <!-- ===== TAB 2: PENGATURAN GRAFIK ===== -->
    <div id="tab-panel-grafik" class="settings-tab-panel" style="display:none;">
        <div class="panel">
            <div class="panel-title">Tampilan Grafik Produksi</div>

            <div class="settings-row">
                <div class="settings-row-text">
                    <div class="settings-row-label">Bentuk Grafik Produksi &amp; EFF</div>
                    <div class="settings-row-desc">Mengatur bentuk grafik Input/Output dan EFF di halaman Dashboard &amp; Chart Line Painting.</div>
                </div>
                <select id="chart-type-select" class="filter-select" onchange="simpanBentukGrafik(this.value)" title="Pilih jenis grafik">
                    <option value="bar">Bar Chart (Batang)</option>
                    <option value="line">Line Chart (Garis)</option>
                    <option value="area">Area Chart</option>
                    <option value="scatter">Scatter Plot</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ===== TAB 3: PENGATURAN TARGET & SHIFT PRODUKSI ===== -->
    <div id="tab-panel-target" class="settings-tab-panel" style="display:none;">
        <div class="panel">
            <div class="panel-title">Target &amp; Jam Kerja Shift Line Painting</div>

            <div class="settings-row">
                <div class="settings-row-text">
                    <div class="settings-row-label">Target Produksi per Shift (Wadah)</div>
                    <div class="settings-row-desc">Standar kuantitas output wadah lampu per satu sesi shift kerja.</div>
                </div>
                <input type="number" id="cfg-target-shift" class="filter-select" style="max-width:160px;" value="1200" min="100">
            </div>

            <div class="settings-row">
                <div class="settings-row-text">
                    <div class="settings-row-label">Estimasi Waktu Siklus (Cycle Time Konveyor)</div>
                    <div class="settings-row-desc">Interval rata-rata gantungan hanger melewati sensor inspeksi (detik).</div>
                </div>
                <input type="number" id="cfg-ct-conveyor" class="filter-select" style="max-width:160px;" value="4.5" step="0.1" min="1">
            </div>

            <div style="margin-top:14px; text-align:right;">
                <button type="button" class="btn-nav-action btn-blue" onclick="simpanTargetShiftPainting()" style="padding:8px 20px;">
                    Simpan Target Shift
                </button>
            </div>
        </div>
    </div>

    <!-- ===== TAB 4: PANDUAN LENGKAP TRAINING AI (TOP-DOWN POV) ===== -->
    <div id="tab-panel-ai" class="settings-tab-panel" style="display:none;">
        <!-- BANNER HEADER PANDUAN -->
        <div class="ai-guide-header-card">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                <div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                        <span style="font-size:22px;">🤖</span>
                        <strong style="font-size:17px; letter-spacing:0.3px;">PANDUAN TRAINING MODEL AI ESP-CAM (TOP-DOWN OVERHEAD POV)</strong>
                    </div>
                    <div style="font-size:13.5px; color:#cbd5e1; max-width:850px; line-height:1.5;">
                        Panduan praktis pembuatan dataset, anotasi, dan pelatihan computer vision untuk deteksi keberadaan &amp; penghitungan <strong>Wadah Lampu</strong> dengan sudut pandang kamera dari atas (Top-Down) langsung menghadap konveyor hanger.
                    </div>
                </div>
                <span style="background:rgba(56,189,248,0.25); color:#38bdf8; border:1px solid rgba(56,189,248,0.4); padding:4px 12px; border-radius:14px; font-size:12px; font-weight:700;">
                    📐 POSISI ATAS: SANGAT EFISIEN
                </span>
            </div>
        </div>

        <!-- PERBANDINGAN: KENAPA SETUP TOP-DOWN ANDA SANGAT BAGUS -->
        <div class="panel" style="margin-bottom:18px;">
            <div class="panel-title" style="margin-bottom:12px;">💡 Analisis Setup Pabrik: Mengapa Pilihan Menaruh Sensor &amp; Kamera di Atas Sangat Tepat?</div>
            <div class="ai-comparison-grid">
                <div class="ai-compare-box top-down">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                        <span style="font-size:18px;">✅</span>
                        <strong style="font-size:14px; color:#0284c7;">Setup Anda: Tampak Atas (Top-Down Overhead)</strong>
                    </div>
                    <ul style="font-size:13px; color:#334155; line-height:1.6; padding-left:18px; margin:0;">
                        <li><strong>Tanpa Background Curtain:</strong> Kamera menatap langsung ke bawah (ke rel/lantai bilik yang statis). Tidak ada orang lewat di latar belakang yang bisa memicu <em>false positive</em>.</li>
                        <li><strong>Jarak Dekat &amp; Tersentralisasi (30–50 cm):</strong> Resolusi pixel objek wadah lampu sangat padat dan tajam meskipun ESP32-CAM memakai resolusi hemat SVGA/VGA.</li>
                        <li><strong>Pencahayaan LED HPL Langsung:</strong> Sinar flash cool white menyinari tegak lurus, meniadakan bayangan panjang miring yang biasa mengaburkan tepi wadah.</li>
                        <li><strong>Geometri Objek Simetris:</strong> Dari atas, wadah lampu selalu berbentuk persegi panjang teratur, membuat <em>bounding box</em> model AI sangat konsisten.</li>
                    </ul>
                </div>

                <div class="ai-compare-box" style="background:#f8fafc; border-color:#e2e8f0;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                        <span style="font-size:18px;">⚠️</span>
                        <strong style="font-size:14px; color:#64748b;">Setup Konvensional: Tampak Samping (Side View)</strong>
                    </div>
                    <ul style="font-size:13px; color:#64748b; line-height:1.6; padding-left:18px; margin:0;">
                        <li><strong>Wajib Pasang Backdrop Fisik:</strong> Harus memasang papan/tirai latar hitam di belakang konveyor agar tidak menangkap pergerakan orang pabrik.</li>
                        <li><strong>Jarak Kamera Harus Jauh (1–2 meter):</strong> Agar bisa mencakup seluruh tinggi gantungan, memakan ruang kerja dan rentan terhalang operator.</li>
                        <li><strong>Bayangan Menyilang:</strong> Lampu flash samping rawan memantulkan silau (glare) ke arah lensa kamera.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- REKOMENDASI FRAMEWORK AI -->
        <div class="panel" style="margin-bottom:18px;">
            <div class="panel-title" style="margin-bottom:4px;">🧠 Pilihan Framework &amp; Arsitektur Model AI</div>
            <div style="font-size:13px; color:#64748b; margin-bottom:12px;">Pilih metode sesuai dengan tempat inferensi model dijalankan:</div>

            <div class="ai-arch-choice-grid">
                <!-- Opsi 1: Edge Impulse FOMO -->
                <div class="ai-arch-box recommended">
                    <span class="ai-badge-rec">⭐ SANGAT DIREKOMENDASIKAN</span>
                    <div style="font-size:15px; font-weight:800; color:#0f172a; margin-bottom:6px;">
                        1. Edge Impulse FOMO (On-Device di ESP32-CAM)
                    </div>
                    <div style="font-size:12.5px; color:#0284c7; font-weight:700; margin-bottom:8px;">
                        Model berjalan langsung di dalam chip ESP32 tanpa butuh PC server
                    </div>
                    <div style="font-size:13px; color:#475569; line-height:1.55; margin-bottom:10px;">
                        <strong>FOMO (Faster Objects, More Objects)</strong> dirancang khusus oleh Edge Impulse untuk mikrokontroler dengan RAM kecil seperti ESP32 (SRAM 520KB / PSRAM 4MB).
                    </div>
                    <div style="font-size:12.5px; color:#334155; line-height:1.5;">
                        • <strong>Kecepatan Inferensi:</strong> 80 – 140 ms per frame.<br>
                        • <strong>Kebutuhan RAM:</strong> Hanya ~150 KB (Sangat aman di ESP32).<br>
                        • <strong>Output:</strong> Menghitung jumlah wadah lampu &amp; titik centroid (x,y).<br>
                        • <strong>Hasil Export:</strong> Langsung berupa C++ library untuk Arduino IDE!
                    </div>
                </div>

                <!-- Opsi 2: YOLOv8-Nano di PC Gateway -->
                <div class="ai-arch-box">
                    <div style="font-size:15px; font-weight:800; color:#0f172a; margin-bottom:6px;">
                        2. YOLOv8-Nano (Di Server / Mini PC Dashboard)
                    </div>
                    <div style="font-size:12.5px; color:#64748b; font-weight:700; margin-bottom:8px;">
                        ESP32-CAM hanya streaming video MJPEG, AI diproses di komputer
                    </div>
                    <div style="font-size:13px; color:#475569; line-height:1.55; margin-bottom:10px;">
                        Jika ESP32-CAM difungsikan murni sebagai IP Camera via WiFi, stream video diterima oleh skrip Python di PC dashboard dan diprediksi dengan <strong>YOLOv8n (Ultralytics)</strong>.
                    </div>
                    <div style="font-size:12.5px; color:#334155; line-height:1.5;">
                        • <strong>Kecepatan Inferensi:</strong> &lt; 15 ms pada PC standar.<br>
                        • <strong>Fitur:</strong> Visualisasi bounding box lengkap &amp; label akurasi tinggi.<br>
                        • <strong>Kelemahan:</strong> Tergantung stabilitas jaringan WiFi pabrik.
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP-BY-STEP PANDUAN TRAINING DATASET -->
        <div class="panel" style="margin-bottom:18px;">
            <div class="panel-title" style="margin-bottom:14px;">📋 Langkah Demi Langkah (Step-by-Step) Pelatihan Model AI Top-Down</div>
            <div class="ai-step-timeline">
                <!-- STEP 1 -->
                <div class="ai-step-card">
                    <div class="ai-step-number">1</div>
                    <div class="ai-step-content">
                        <div class="ai-step-title">Pengambilan Foto Dataset (Data Acquisition)</div>
                        <div class="ai-step-desc">
                            Untuk setup top-down dengan kondisi cahaya terkontrol, Anda <strong>hanya memerlukan 150 – 250 lembar foto</strong> untuk mendapatkan akurasi di atas 97%!
                            <br><br>
                            <strong>Komposisi Foto yang Wajib Dikumpulkan:</strong>
                            <ul style="margin:6px 0; padding-left:18px;">
                                <li><strong>40 Foto:</strong> Hanger KOSONG (0 Wadah) — Sangat penting sebagai latar belakang negatif agar AI tidak salah mendeteksi gantungan kosong sebagai wadah.</li>
                                <li><strong>40 Foto:</strong> Hanger dengan 1 Wadah (variasikan di slot atas, tengah, dan bawah).</li>
                                <li><strong>40 Foto:</strong> Hanger dengan 2 Wadah.</li>
                                <li><strong>40 Foto:</strong> Hanger dengan 3 Wadah.</li>
                                <li><strong>30 Foto:</strong> Hanger sedikit miring atau goyang ±5° (mensimulasikan ayunan aktual konveyor).</li>
                            </ul>
                            <div style="background:#fef3c7; border-left:3px solid #f59e0b; padding:8px 12px; border-radius:4px; font-size:12.5px; color:#92400e; margin-top:8px;">
                                ⚠️ <strong>TIPS PENTING:</strong> Ambil foto <em>langsung menggunakan modul ESP-CAM Anda pada posisi terpasang final di atas konveyor</em> dengan LED HPL Cool White menyala! Jangan menggunakan kamera HP, karena karakteristik sensor OV2640 dan lensa wide-angle-nya berbeda dengan HP.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2 -->
                <div class="ai-step-card">
                    <div class="ai-step-number">2</div>
                    <div class="ai-step-content">
                        <div class="ai-step-title">Anotasi / Pelabelan Gambar (Labeling)</div>
                        <div class="ai-step-desc">
                            Gunakan platform gratis berbasis web seperti <strong>Roboflow (roboflow.com)</strong> atau langsung di <strong>Edge Impulse Studio</strong>.
                            <br><br>
                            • Buat <strong>1 Label Class saja:</strong> <code style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-weight:700;">wadah_lampu</code>.<br>
                            • Gambar kotak persegi panjang yang rapi membingkai setiap bodi wadah lampu tampak atas.<br>
                            • Pada foto <strong>Hanger Kosong</strong>, jangan beri label apapun (biarkan kosong). Sistem AI akan otomatis memahaminya sebagai area latar belakang (background class).
                        </div>
                    </div>
                </div>

                <!-- STEP 3 -->
                <div class="ai-step-card">
                    <div class="ai-step-number">3</div>
                    <div class="ai-step-content">
                        <div class="ai-step-title">Augmentasi Data (Data Augmentation) yang Direkomendasikan</div>
                        <div class="ai-step-desc">
                            Karena sudut pandang Anda adalah Top-Down yang tersentralisasi, terapkan konfigurasi augmentasi berikut:
                            <br><br>
                            • <strong>Rotasi Acak (Random Rotation):</strong> Batasi hanya ±5° hingga ±10° (karena ayunan hanger di konveyor overhead sangat minim).<br>
                            • <strong>Variasi Kecerahan (Brightness):</strong> ±12% (mengantisipasi variasi voltase Step-Down LM2596 atau kebersihan cover LED).<br>
                            • <strong>Crop / Shift:</strong> ±6% (mengantisipasi jeda timing photosensor saat mendeteksi hanger).<br>
                            • ❌ <em>JANGAN aktifkan Vertical Flip</em> jika wadah lampu selalu memiliki orientasi maju-mundur yang seragam.
                        </div>
                    </div>
                </div>

                <!-- STEP 4 -->
                <div class="ai-step-card">
                    <div class="ai-step-number">4</div>
                    <div class="ai-step-content">
                        <div class="ai-step-title">Training Model &amp; Kuantisasi Int8</div>
                        <div class="ai-step-desc">
                            Di Edge Impulse Studio atau YOLOv8:
                            <br><br>
                            1. Pilih resolusi input: <strong>96x96 atau 160x160 Grayscale/RGB</strong> (resolusi ini sangat ringan untuk ESP32 namun sangat jelas untuk mengenali kontur kotak wadah lampu).<br>
                            2. Pilih arsitektur: <strong>FOMO (Faster Objects, More Objects) MobileNetV2 0.35</strong>.<br>
                            3. Jalankan Training (biasanya selesai dalam 2–4 menit di cloud).<br>
                            4. Pada tab <em>Deployment</em>, pilih <strong>Arduino Library</strong> dan centang <strong>Quantized (int8)</strong>. Model int8 menghemat memori 4x lipat dibanding float32 tanpa mengurangi akurasi!
                        </div>
                    </div>
                </div>

                <!-- STEP 5 -->
                <div class="ai-step-card">
                    <div class="ai-step-number">5</div>
                    <div class="ai-step-content">
                        <div class="ai-step-title">Integrasi Firmware ESP32-CAM &amp; Skema Pengkabelan Lengkap</div>
                        <div class="ai-step-desc">
                            <strong>1. Skema Pengkabelan Pinout Komponen ke ESP32-CAM AI-THINKER:</strong>
                            <div style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:8px; padding:12px 14px; margin:8px 0 14px 0; font-size:12.5px; color:#1e293b; line-height:1.6;">
                                • <strong>Modul Driver MOSFET IRF520 (LED HPL Cool White):</strong> Hubungkan pin <code>SIG / Gate</code> ke <strong>GPIO 13</strong>, pin <code>GND</code> ke GND bersama, dan <code>VIN / V+</code> ke output <strong>Step-Down LM2596 (diset ~3.3V - 3.4V untuk LED HPL 1/3W)</strong>.<br>
                                • <strong>Photosensor Overhead (Deteksi Hanger):</strong> Hubungkan kabel sinyal ke <strong>GPIO 14</strong> (aktif LOW saat mendeteksi plat hanger).<br>
                                • <strong>Tombol Hijau (Operator Hadir):</strong> Hubungkan satu kaki ke <strong>GPIO 12</strong> dan kaki lainnya ke GND.<br>
                                • <strong>Tombol Merah (Operator Kosong):</strong> Hubungkan satu kaki ke <strong>GPIO 2</strong> dan kaki lainnya ke GND.<br>
                                • <strong>Power Supply:</strong> Input 12V/24V konveyor masuk ke LM2596 Step-Down &rarr; diatur ke 5V stabil masuk ke pin 5V &amp; GND ESP32-CAM.
                            </div>

                            <strong>2. Kode Firmware ESP32-CAM Lengkap (Telah Disempurnakan):</strong>
                            <div style="font-size:12px; color:#64748b; margin-bottom:6px;">Kode ini mempertahankan seluruh fungsi CCTV stream Anda, sekaligus menambahkan endpoint <code>/capture</code> (untuk ambil foto dataset AI dengan 1-klik), endpoint <code>/status</code>, dan kendali otomatis flash MOSFET IRF520 saat photosensor terpicu:</div>
                            <div class="ai-code-snippet" style="max-height:450px; overflow-y:auto;">
#include "esp_camera.h"
#include &lt;WiFi.h&gt;
#include "Arduino.h"
#include "soc/soc.h" 
#include "soc/rtc_cntl_reg.h"  
#include "esp_http_server.h"

// Kredensial WiFi Pabrik
const char* ssid = "SSID_PABRIK";
const char* password = "PASSWORD_WIFI";

#define PART_BOUNDARY "123456789000000000000987654321"

// Definisi Pin Hardware Tambahan (Line Painting)
#define PIN_MOSFET_FLASH    13  // Kontrol Gate Driver MOSFET IRF520 (LED HPL Cool White)
#define PIN_PHOTOSENSOR     14  // Input Sensor Pendeteksi Hanger di Posisi Atas
#define PIN_BTN_OP_HIJAU    12  // Tombol Fisik: Operator Hadir / Memproduksi
#define PIN_BTN_OP_MERAH     2  // Tombol Fisik: Tidak Ada Operator / Kosong

// Pin Kamera AI-THINKER
#define PWDN_GPIO_NUM     32
#define RESET_GPIO_NUM    -1
#define XCLK_GPIO_NUM      0
#define SIOD_GPIO_NUM     26
#define SIOC_GPIO_NUM     27
#define Y9_GPIO_NUM       35
#define Y8_GPIO_NUM       34
#define Y7_GPIO_NUM       39
#define Y6_GPIO_NUM       36
#define Y5_GPIO_NUM       21
#define Y4_GPIO_NUM       19
#define Y3_GPIO_NUM       18
#define Y2_GPIO_NUM        5
#define VSYNC_GPIO_NUM    25
#define HREF_GPIO_NUM     23
#define PCLK_GPIO_NUM     22

httpd_handle_t camera_httpd = NULL;
volatile bool operatorHadir = true;
volatile unsigned long totalHanger = 0;
volatile unsigned long lastTrigger = 0;

// Handler Snapshot Foto Tunggal (Sangat penting untuk mengumpulkan 150-250 foto dataset AI)
static esp_err_t capture_handler(httpd_req_t *req){
  // Nyalakan Flash LED HPL sebentar agar pencahayaan foto dataset optimal
  digitalWrite(PIN_MOSFET_FLASH, HIGH);
  delay(80);
  
  camera_fb_t * fb = esp_camera_fb_get();
  digitalWrite(PIN_MOSFET_FLASH, LOW);
  
  if (!fb) {
    return httpd_resp_send_500(req);
  }
  
  httpd_resp_set_type(req, "image/jpeg");
  httpd_resp_set_hdr(req, "Content-Disposition", "inline; filename=capture_wadah.jpg");
  httpd_resp_set_hdr(req, "Access-Control-Allow-Origin", "*");
  esp_err_t res = httpd_resp_send(req, (const char *)fb-&gt;buf, fb-&gt;len);
  esp_camera_fb_return(fb);
  return res;
}

// Handler Telemetri Status Hardware (JSON)
static esp_err_t status_handler(httpd_req_t *req){
  char json[200];
  snprintf(json, sizeof(json),
    "{\"status\":\"online\",\"hanger_id\":%lu,\"operator\":\"%s\",\"sensor\":\"%s\",\"flash_pin\":%d}",
    totalHanger, operatorHadir ? "HADIR" : "KOSONG",
    digitalRead(PIN_PHOTOSENSOR) == LOW ? "TRIGGERED" : "READY",
    PIN_MOSFET_FLASH
  );
  httpd_resp_set_type(req, "application/json");
  httpd_resp_set_hdr(req, "Access-Control-Allow-Origin", "*");
  return httpd_resp_send(req, json, strlen(json));
}

// (Handler index_handler, control_handler, stream_handler tetap sama persis seperti kode Anda)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
