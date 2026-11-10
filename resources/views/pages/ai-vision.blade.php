<div id="view-ai-vision" style="{{ $view_aktif !== 'ai-vision' ? 'display:none;' : '' }}">
    <div class="panel">
        <div class="panel-header-row">
            <div class="panel-title">Pengaturan ESP32-CAM & AI Vision (Object Detection Hanger)</div>
        </div>
        <div class="settings-row-desc" style="margin-bottom:16px;">
            Sistem deteksi visual dan counter otomatis untuk part/barang pada conveyor hanger Line Painting menggunakan model <strong>Roboflow Object Detection</strong> yang terintegrasi dengan <strong>RS-485 Photosensor Input & Output</strong>.
        </div>

        <!-- Banner Sesi Produksi & Part Aktif (Changeover Bar) -->
        <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); border-radius:12px; padding:18px 22px; margin-bottom:20px; box-shadow:0 4px 16px rgba(49,46,129,0.25); color:white;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                <div style="display:flex; align-items:center; gap:16px;">
                    <div style="width:50px; height:50px; border-radius:12px; background:rgba(255,255,255,0.12); display:flex; align-items:center; justify-content:center; font-size:26px; border:1px solid rgba(255,255,255,0.2);">
                        🏷️
                    </div>
                    <div>
                        <div style="font-size:12.5px; text-transform:uppercase; letter-spacing:0.8px; color:#a5b4fc; font-weight:700;">
                            Sesi Produksi Aktif (Single-Part Batch)
                        </div>
                        <div style="display:flex; align-items:center; gap:12px; margin-top:3px;">
                            <span id="active-part-name" style="font-size:22px; font-weight:800; color:#ffffff; letter-spacing:-0.2px;">Memuat Part...</span>
                            <span id="active-part-code" style="background:#4f46e5; color:#e0e7ff; font-size:13px; font-weight:700; padding:3px 10px; border-radius:6px; font-family:monospace;">P-01</span>
                        </div>
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                    <!-- Info Model Roboflow yang sedang aktif -->
                    <div style="background:rgba(255,255,255,0.1); padding:8px 14px; border-radius:8px; border:1px solid rgba(255,255,255,0.2); font-size:13.5px;">
                        <span style="color:#c7d2fe;">Model AI:</span>
                        <strong id="active-part-model-info" style="color:#ffffff; margin-left:6px; font-family:monospace;">-</strong>
                        <span id="badge-model-version" style="background:#22c55e; color:#ffffff; font-size:12px; font-weight:700; padding:2px 8px; border-radius:5px; margin-left:6px;">v1</span>
                    </div>

                    <!-- Tombol Ganti Part (Changeover) -->
                    <button type="button" id="btn-open-change-part" style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px; background:#f59e0b; color:#78350f; border:none; border-radius:8px; font-weight:800; font-size:13.5px; cursor:pointer; box-shadow:0 3px 8px rgba(245,158,11,0.35); transition:all 0.2s;">
                        🔄 Ganti Part (Changeover)
                    </button>

                    <!-- Tombol Kelola Part -->
                    <button type="button" id="btn-open-manage-parts" style="display:inline-flex; align-items:center; gap:8px; padding:10px 15px; background:rgba(255,255,255,0.15); color:white; border:1px solid rgba(255,255,255,0.3); border-radius:8px; font-weight:700; font-size:13.5px; cursor:pointer;" title="Kelola Katalog Part">
                        ⚙️ Katalog Part
                    </button>
                </div>
            </div>
        </div>

        <!-- Banner Sinkronisasi Konfigurasi ESP32 & RS-485 Status -->
        <div style="background:#f8fafc; border:1.5px solid #dbe4f3; border-radius:10px; padding:18px; margin-bottom:20px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                <div>
                    <h3 style="margin-top:0; margin-bottom:6px; font-size:16.5px; color:#1e293b; font-weight:800;">Konfigurasi Master & Jalur RS-485</h3>
                    <p style="font-size:13.5px; color:#475569; margin:0; line-height:1.5;">
                        ESP32 membaca sensor melalui <strong>RS-485 to TTL</strong>. Photosensor 1 menandai hanger masuk, Photosensor 2 memicu kamera mengonfirmasi jumlah pcs barang.
                    </p>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="http://master-painting.test/cctv" target="_blank" class="btn-simpan-produksi" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; padding:9px 16px; background:#0d3b8c; color:white; border-radius:8px; font-weight:700; font-size:13px;">
                        ⚙️ Master Painting &rarr;
                    </a>
                    <button type="button" id="btn-selesai-konfigurasi" class="btn-simpan-produksi" style="display:inline-flex; align-items:center; gap:6px; padding:9px 16px; background:#16a34a; color:white; border:none; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer;">
                        🔄 Sinkronkan IP Kamera
                    </button>
                </div>
            </div>

            <!-- Status Indikator Realtime RS-485 Photosensor (Hardware Monitoring) -->
            <div style="margin-top:14px; padding-top:14px; border-top:1px dashed #cbd5e1; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                    <!-- Indikator Status Mesin Produksi (PB ON / PB OFF) - Standar 2 Warna: MERAH (Off) & HIJAU (On) -->
                    <div style="display:flex; align-items:center; gap:8px; background:#ffffff; border:1px solid #cbd5e1; padding:7px 14px; border-radius:8px;">
                        <span id="dot-mesin-status" style="width:12px; height:12px; border-radius:50%; background:#ef4444; box-shadow:0 0 8px rgba(239,68,68,0.5); display:inline-block; transition:all 0.3s;"></span>
                        <span style="font-size:13px; font-weight:700; color:#334155;">Status Mesin:</span>
                        <span id="status-mesin-text" style="font-size:13px; font-weight:800; color:#dc2626; font-family:monospace;">STANDBY (Nilai 0)</span>
                    </div>

                    <!-- Indikator Sensor 1 (Input) - Standar 2 Warna: MERAH (Standby) & HIJAU (Trigger) -->
                    <div style="display:flex; align-items:center; gap:8px; background:#ffffff; border:1px solid #cbd5e1; padding:7px 14px; border-radius:8px;">
                        <span id="dot-sensor-input" style="width:12px; height:12px; border-radius:50%; background:#ef4444; box-shadow:0 0 8px rgba(239,68,68,0.5); display:inline-block; transition:all 0.3s;"></span>
                        <span style="font-size:13px; font-weight:700; color:#334155;">Sensor 1 (Input):</span>
                        <span id="status-sensor-input-text" style="font-size:13px; font-weight:800; color:#dc2626; font-family:monospace;">Standby</span>
                    </div>

                    <!-- Indikator Sensor 2 (Output Shutter) - Standar 2 Warna: MERAH (Standby) & HIJAU (Trigger) -->
                    <div style="display:flex; align-items:center; gap:8px; background:#ffffff; border:1px solid #cbd5e1; padding:7px 14px; border-radius:8px;">
                        <span id="dot-sensor-output" style="width:12px; height:12px; border-radius:50%; background:#ef4444; box-shadow:0 0 8px rgba(239,68,68,0.5); display:inline-block; transition:all 0.3s;"></span>
                        <span style="font-size:13px; font-weight:700; color:#334155;">Sensor 2 (Output Shutter):</span>
                        <span id="status-sensor-output-text" style="font-size:13px; font-weight:800; color:#dc2626; font-family:monospace;">Standby</span>
                    </div>
                </div>

                <!-- Tombol Simulasi Trigger -->
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span style="font-size:12.5px; font-weight:700; color:#64748b;">Simulasi:</span>
                    <button type="button" id="btn-sim-on" style="background:#f0fdf4; border:1px solid #86efac; color:#16a34a; padding:6px 11px; border-radius:6px; font-size:12.5px; font-weight:700; cursor:pointer;" title="Simulasikan Tombol START (PB ON) Ditekan (Nilai 1)">
                        🟢 PB ON
                    </button>
                    <button type="button" id="btn-sim-off" style="background:#fef2f2; border:1px solid #fca5a5; color:#dc2626; padding:6px 11px; border-radius:6px; font-size:12.5px; font-weight:700; cursor:pointer;" title="Simulasikan Tombol STOP (PB OFF) Ditekan (Nilai 0)">
                        🔴 PB OFF
                    </button>
                    <button type="button" id="btn-sim-input" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#0f172a; padding:6px 11px; border-radius:6px; font-size:12.5px; font-weight:700; cursor:pointer;" title="Simulasikan Hanger Lewat di Sensor Input">
                        ⚡ Sensor In
                    </button>
                    <button type="button" id="btn-sim-output" style="background:#eff6ff; border:1px solid #93c5fd; color:#1d4ed8; padding:6px 11px; border-radius:6px; font-size:12.5px; font-weight:700; cursor:pointer;" title="Simulasikan Hanger Lewat di Sensor Output & Picu Kamera">
                        📸 Sensor Out
                    </button>
                </div>
            </div>
        </div>

        <div style="display:flex; gap:22px; flex-wrap:wrap;">
            <!-- KOLOM KIRI: Layar Video Stream & Live Counter KPI -->
            <div style="flex:1.3; min-width:340px; display:flex; flex-direction:column; gap:16px;">
                
                <!-- Layar Video Stream ESP32-CAM dengan Canvas Bounding Box -->
                <div id="ai-video-container" style="background:#0b1120; border:2px solid #1e293b; border-radius:12px; overflow:hidden; position:relative; min-height:360px; display:flex; align-items:center; justify-content:center; box-shadow:0 6px 24px rgba(0,0,0,0.25); transition:border-color 0.3s;">
                    
                    <!-- Placeholder saat Stream Mati / Loading -->
                    <div id="ai-video-placeholder" style="color:#94a3b8; font-size:14px; text-align:center; padding:28px; display:flex; flex-direction:column; align-items:center; gap:10px;">
                        <span style="font-size:46px;">📷</span>
                        <span id="ai-video-placeholder-title" style="font-weight:700; color:#f1f5f9; font-size:16px;">Menghubungkan ke Kamera ESP32-CAM (Output)...</span>
                        <span id="ai-video-placeholder-desc" style="font-size:13px; color:#94a3b8; max-width:360px; line-height:1.5;">
                            Mencari IP kamera secara otomatis. Pastikan modul ESP32-CAM terhubung ke jaringan WiFi yang sama.
                        </span>
                    </div>

                    <!-- Wrapper Stream & Canvas Overlay -->
                    <div id="ai-stream-wrapper" style="display:none; position:relative; width:100%; height:auto; line-height:0;">
                        <!-- Gambar MJPEG Stream -->
                        <img id="ai-mjpeg-stream" crossorigin="anonymous" style="width:100%; height:auto; object-fit:contain; max-height:480px; display:block;" alt="ESP32-CAM Stream">
                        
                        <!-- Canvas Overlay untuk Gambar Bounding Box -->
                        <canvas id="ai-canvas-overlay" style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none;"></canvas>
                        
                        <!-- Badge Indikator Live di Pojok Layar -->
                        <div style="position:absolute; top:10px; left:10px; display:flex; gap:6px; z-index:5;">
                            <span id="ai-stream-badge" style="background:rgba(15,23,42,0.88); color:#4ade80; border:1px solid rgba(74,222,128,0.3); font-size:12px; font-weight:700; padding:5px 11px; border-radius:6px; font-family:ui-monospace, monospace; backdrop-filter:blur(4px); display:flex; align-items:center; gap:6px;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#22c55e; animation:lampKedip 1.2s infinite;"></span>
                                LIVE OUTPUT CAM
                            </span>
                            <span id="ai-inference-time-badge" style="background:rgba(15,23,42,0.88); color:#38bdf8; border:1px solid rgba(56,189,248,0.3); font-size:12px; font-weight:700; padding:5px 11px; border-radius:6px; font-family:ui-monospace, monospace; backdrop-filter:blur(4px); display:none;">
                                0 ms
                            </span>
                        </div>

                        <!-- Flash Shutter Banner saat Sensor Output Trigger -->
                        <div id="ai-shutter-flash" style="display:none; position:absolute; inset:0; background:rgba(255,255,255,0.35); pointer-events:none; z-index:10;"></div>
                    </div>
                </div>

                <!-- 4 KPI Box: Live Conveyor & Hanger Flow -->
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:12px;">
                    
                    <!-- Kartu 1: WIP Hanger (Sedang di dalam line oven/booth) -->
                    <div style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:10px; padding:16px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                        <div style="font-size:12.5px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                            Hanger di Line (WIP)
                        </div>
                        <div style="display:flex; align-items:baseline; gap:6px;">
                            <span id="kpi-wip-hangers" style="font-size:34px; font-weight:800; color:#f59e0b; line-height:1;">0</span>
                            <span style="font-size:14px; font-weight:700; color:#64748b;">Hanger</span>
                        </div>
                        <div style="font-size:12px; color:#d97706; font-weight:700; margin-top:5px;">
                            Marked oleh Sensor 1
                        </div>
                    </div>

                    <!-- Kartu 2: Part di Hanger Output Saat Ini -->
                    <div style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:10px; padding:16px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                        <div style="font-size:12.5px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                            Part di Hanger Ini
                        </div>
                        <div style="display:flex; align-items:baseline; gap:6px;">
                            <span id="kpi-current-parts" style="font-size:34px; font-weight:800; color:#0d3b8c; line-height:1;">0</span>
                            <span style="font-size:14px; font-weight:700; color:#64748b;">Pcs</span>
                        </div>
                        <div style="font-size:12px; color:#16a34a; font-weight:700; margin-top:5px;">
                            Deteksi Kamera Output
                        </div>
                    </div>

                    <!-- Kartu 3: Total Hanger Selesai (Output) -->
                    <div style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:10px; padding:16px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                        <div style="font-size:12.5px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                            Hanger Selesai
                        </div>
                        <div style="display:flex; align-items:baseline; gap:6px;">
                            <span id="kpi-output-hangers" style="font-size:34px; font-weight:800; color:#0f172a; line-height:1;">0</span>
                            <span style="font-size:14px; font-weight:700; color:#64748b;">Hanger</span>
                        </div>
                        <div style="font-size:12px; color:#64748b; font-weight:600; margin-top:5px;">
                            Terkonfirmasi Sensor 2
                        </div>
                    </div>

                    <!-- Kartu 4: Total Output Aktual Terkonfirmasi (PCS) -->
                    <div style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:10px; padding:16px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-size:12.5px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">Total Output</span>
                            <button type="button" id="btn-reset-counter" style="background:none; border:none; color:#ef4444; font-size:12px; cursor:pointer; font-weight:700; text-decoration:underline;">Reset</button>
                        </div>
                        <div style="display:flex; align-items:baseline; gap:6px;">
                            <span id="kpi-total-parts" style="font-size:34px; font-weight:800; color:#16a34a; line-height:1;">0</span>
                            <span style="font-size:14px; font-weight:700; color:#64748b;">Pcs</span>
                        </div>
                        <div style="font-size:12px; color:#16a34a; font-weight:700; margin-top:5px;">
                            Aktual Roboflow AI
                        </div>
                    </div>
                </div>

                <!-- Info Log Alur Kerja Konveyor -->
                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:13px 16px; font-size:13.5px; color:#1e40af; line-height:1.5;">
                    💡 <strong>Alur Sistem RS-485:</strong> Saat Photosensor 1 mendeteksi hanger masuk, antrean <em>WIP Hanger</em> bertambah. Begitu hanger keluar di depan kamera dan memicu Photosensor 2, sistem otomatis mem-freeze frame, menghitung jumlah barang via Roboflow, dan menambahkan jumlah pcs ke <em>Total Output</em>!
                </div>

                <!-- CARD KOLEKTOR DATASET ROBOFLOW (100 SAMPEL TRAINING) -->
                <div style="background:#ffffff; border:1.5px solid #c7d2fe; border-radius:12px; padding:18px; box-shadow:0 2px 8px rgba(99,102,241,0.06);">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; border-bottom:1px solid #e0e7ff; padding-bottom:12px; flex-wrap:wrap; gap:8px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size:24px;">📸</span>
                            <div>
                                <h4 style="margin:0; font-size:16px; color:#3730a3; font-weight:800;">Kolektor Dataset Roboflow (100 Sampel Training)</h4>
                                <div style="font-size:13px; color:#4338ca; font-weight:700; margin-top:2px;">
                                    Part Sasaran: <span id="label-kolektor-part-name" style="background:#e0e7ff; padding:2px 10px; border-radius:5px; font-family:monospace; color:#3730a3; font-size:13px;">Memuat...</span>
                                </div>
                            </div>
                        </div>
                        <span id="badge-sample-status" style="font-size:12.5px; font-weight:700; background:#e0e7ff; color:#3730a3; padding:5px 12px; border-radius:6px; font-family:ui-monospace,monospace;">
                            Standby (0 / 100)
                        </span>
                    </div>

                    <!-- Progress Bar Koleksi Sampel -->
                    <div style="margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:13.5px; font-weight:700; margin-bottom:6px;">
                            <span style="color:#334155;">Progres Pengumpulan:</span>
                            <span id="label-sample-progress" style="color:#4f46e5; font-family:monospace; font-weight:800; font-size:14px;">0 / 100 Sampel (0%)</span>
                        </div>
                        <div style="background:#f1f5f9; border-radius:8px; height:14px; overflow:hidden; border:1px solid #cbd5e1; position:relative;">
                            <div id="bar-sample-progress" style="background:linear-gradient(90deg, #6366f1, #4f46e5); width:0%; height:100%; transition:width 0.3s; border-radius:6px;"></div>
                        </div>
                    </div>

                    <!-- Pilihan Mode & Tombol Aksi -->
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:14px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:600; color:#334155;">
                            <input type="checkbox" id="chk-auto-sample-on-hanger" checked style="width:17px; height:17px; accent-color:#4f46e5;">
                            <span>Auto-Capture saat Photosensor Output (Sensor 2) Terpicu</span>
                        </label>

                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <button type="button" id="btn-toggle-sampling" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#4f46e5; color:white; border:none; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; transition:all 0.2s;">
                                ▶ Mulai Pengumpulan
                            </button>
                            <button type="button" id="btn-capture-manual-sample" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#f8fafc; color:#1e293b; border:1px solid #cbd5e1; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer;">
                                📷 Jepret Manual
                            </button>
                            <a id="btn-download-dataset-zip" href="/api/download-roboflow-zip" target="_blank" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#16a34a; color:white; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; opacity:0.6; pointer-events:none; transition:all 0.2s;">
                                📦 Download ZIP
                            </a>
                            <button type="button" id="btn-open-update-version" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#0284c7; color:white; border:none; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer;" title="Update Versi Model Roboflow Setelah Retraining">
                                🆙 Update Versi Model
                            </button>
                            <button type="button" id="btn-reset-samples" style="display:inline-flex; align-items:center; gap:6px; padding:8px 12px; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer;" title="Reset / Hapus Semua Sampel Part Ini">
                                ↺ Reset
                            </button>
                        </div>
                    </div>

                    <!-- Filmstrip Preview Sampel Terbaru -->
                    <div>
                        <div style="font-size:12.5px; font-weight:700; color:#475569; margin-bottom:6px;">Preview Sampel Terakhir:</div>
                        <div id="sample-preview-container" style="display:flex; gap:8px; overflow-x:auto; padding:6px 0; min-height:72px; align-items:center;">
                            <div id="sample-empty-notice" style="font-size:12.5px; color:#94a3b8; font-style:italic;">Belum ada sampel foto yang diambil. Klik 'Mulai Pengumpulan' atau 'Jepret Manual'.</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- KOLOM KANAN: Pengaturan Model Roboflow & Kontrol Stream -->
            <div style="flex:1; min-width:320px; display:flex; flex-direction:column; gap:16px;">
                
                <!-- CARD 1: Profil Model AI Part Aktif & Roboflow -->
                <div style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:10px; padding:18px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:20px;">🤖</span>
                            <h4 style="margin:0; font-size:16px; color:#0d3b8c; font-weight:800;">Model Roboflow Part Aktif</h4>
                        </div>
                        <span id="badge-ai-model-ready" style="font-size:12px; font-weight:700; background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:6px; font-family:ui-monospace,monospace;">
                            🔴 Belum Siap
                        </span>
                    </div>

                    <!-- Profil Part Aktif Box -->
                    <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:14px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                            <div>
                                <span id="card-part-code" style="font-size:12.5px; font-weight:800; background:#0d3b8c; color:white; padding:3px 8px; border-radius:4px; font-family:monospace;">-</span>
                                <div id="card-part-name" style="font-size:16px; font-weight:800; color:#0f172a; margin-top:5px;">Memuat Part...</div>
                            </div>
                            <span id="card-model-version" style="font-size:12.5px; font-weight:800; background:#dbeafe; color:#1d4ed8; padding:4px 10px; border-radius:6px; font-family:monospace;">v1</span>
                        </div>
                        
                        <div style="font-size:13px; color:#475569; margin-top:8px; display:flex; flex-direction:column; gap:4px;">
                            <div><strong>Slug Model:</strong> <span id="card-model-slug" style="font-family:monospace; color:#0f172a; font-weight:600;">-</span></div>
                            <div><strong>Target Part / Hanger:</strong> <span id="card-part-target" style="font-weight:800; color:#16a34a;">-</span> Pcs</div>
                        </div>

                        <div style="display:flex; gap:8px; margin-top:12px;">
                            <button type="button" id="btn-quick-change-part" style="flex:1; padding:8px 12px; background:#f1f5f9; color:#0d3b8c; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-weight:700; cursor:pointer;">
                                🔄 Ganti Part
                            </button>
                            <button type="button" id="btn-quick-update-ver" style="padding:8px 12px; background:#e0f2fe; color:#0284c7; border:1px solid #bae6fd; border-radius:6px; font-size:13px; font-weight:700; cursor:pointer;" title="Update Versi Model Setelah Retraining">
                                🆙 Update Versi
                            </button>
                        </div>
                    </div>

                    <!-- Slider Confidence Threshold -->
                    <div style="margin-bottom:14px;">
                        <div style="display:flex; justify-content:space-between; font-size:13.5px; font-weight:700; color:#334155; margin-bottom:5px;">
                            <span>Threshold Confidence AI:</span>
                            <span id="label-conf-val" style="color:#0d3b8c; font-weight:800;">50%</span>
                        </div>
                        <input type="range" id="rf-conf-slider" min="20" max="95" value="50" style="width:100%; accent-color:#0d3b8c;">
                        <div style="display:flex; justify-content:space-between; font-size:11px; color:#94a3b8; margin-top:3px; font-weight:600;">
                            <span>20% (Sensitif)</span>
                            <span>50% (Ideal)</span>
                            <span>95% (Sangat Ketat)</span>
                        </div>
                    </div>

                    <!-- Collapsible Panel: Pengaturan Manual / Override API Key -->
                    <div style="border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; background:#f8fafc;">
                        <button type="button" id="btn-toggle-advanced-rf" style="width:100%; display:flex; justify-content:space-between; align-items:center; background:#f1f5f9; border:none; padding:9px 12px; cursor:pointer; font-size:12.5px; font-weight:700; color:#475569; text-align:left;">
                            <span>⚙️ Pengaturan Manual (Advanced / Override)</span>
                            <span id="icon-toggle-advanced-rf" style="font-size:11px; color:#64748b;">▼</span>
                        </button>

                        <div id="panel-advanced-rf" style="display:none; padding:12px; border-top:1px solid #e2e8f0; font-size:12.5px;">
                            <div style="margin-bottom:10px;">
                                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:3px;">Model ID (Project Slug):</label>
                                <input type="text" id="rf-model-input" class="filter-select" style="width:100%; padding:8px 10px; font-size:12.5px;" placeholder="contoh: bracket-motor-detection">
                            </div>

                            <div style="margin-bottom:10px;">
                                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:3px;">Versi Model:</label>
                                <input type="number" id="rf-version-input" class="filter-select" style="width:100%; padding:8px 10px; font-size:12.5px;" value="1" min="1">
                            </div>

                            <div style="margin-bottom:12px;">
                                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:3px;">Roboflow Publishable API Key:</label>
                                <div style="position:relative; display:flex;">
                                    <input type="password" id="rf-key-input" class="filter-select" style="width:100%; padding:8px 36px 8px 10px; font-size:12.5px; font-family:monospace;" placeholder="rf_xxxxxxxxxxxx">
                                    <button type="button" id="btn-toggle-key" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; color:#64748b; cursor:pointer; font-size:14px;">👁️</button>
                                </div>
                            </div>

                            <button type="button" class="btn-simpan-produksi" id="btn-load-model" style="width:100%; padding:9px 14px; background:#0d3b8c; color:white; border:none; border-radius:6px; font-weight:700; font-size:12.5px; cursor:pointer;">
                                📥 Muat Ulang Model Manual
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: Kamera ESP32-CAM (Output) & Auto-Stream -->
                <div style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:10px; padding:18px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:20px;">📹</span>
                            <h4 style="margin:0; font-size:16px; color:#0d3b8c; font-weight:800;">Kamera ESP32-CAM (Output)</h4>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span id="ai-cam-quick-badge" style="font-size:12px; font-weight:700; background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:6px; font-family:ui-monospace, monospace;">
                                🔴 Mencari IP...
                            </span>
                            <button type="button" id="btn-reconnect-cam" style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; padding:4px 8px; font-size:13px; cursor:pointer;" title="Hubungkan Ulang / Refresh Kamera">
                                🔄
                            </button>
                        </div>
                    </div>

                    <!-- Pilihan Resolusi Kamera Utama -->
                    <div style="margin-bottom:14px;">
                        <label style="display:flex; justify-content:space-between; align-items:center; font-size:13px; font-weight:700; color:#334155; margin-bottom:5px;">
                            <span>Resolusi Kamera:</span>
                            <span style="font-size:11.5px; color:#16a34a; font-weight:700;">Auto-Apply</span>
                        </label>
                        <select id="cam-resolution-select" class="filter-select" style="width:100%; padding:9px 12px; font-size:13px; font-weight:600; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff;">
                            <option value="5">QVGA (320 x 240) - Sangat Cepat & Ringan</option>
                            <option value="6">CIF (400 x 296) - Cepat</option>
                            <option value="8" selected>VGA (640 x 480) - Standar Roboflow (Rekomendasi AI)</option>
                            <option value="9">SVGA (800 x 600) - Lebih Detail</option>
                            <option value="10">XGA (1024 x 768)</option>
                            <option value="11">HD (1280 x 720) - Tajam (FPS Rendah)</option>
                            <option value="12">SXGA (1280 x 1024)</option>
                            <option value="13">UXGA (1600 x 1200) - 2 MP (Still Photo / Berat)</option>
                        </select>
                    </div>

                    <!-- Collapsible Panel: Pengaturan Sensor & Pencahayaan -->
                    <div style="margin-bottom:14px; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; background:#f8fafc;">
                        <button type="button" id="btn-toggle-cam-tuning" style="width:100%; display:flex; justify-content:space-between; align-items:center; background:#f1f5f9; border:none; padding:10px 14px; cursor:pointer; font-size:13px; font-weight:700; color:#1e293b; text-align:left;">
                            <span style="display:flex; align-items:center; gap:8px;">
                                <span>⚙️</span>
                                <span>Pengaturan Sensor & Pencahayaan</span>
                            </span>
                            <span id="icon-toggle-cam-tuning" style="font-size:11px; color:#64748b; transition:transform 0.2s;">▼</span>
                        </button>

                        <div id="panel-cam-tuning" style="display:none; padding:14px; border-top:1px solid #e2e8f0; font-size:12.5px;">
                            
                            <!-- XCLK Clock Speed (Kecepatan Streaming Sensor) -->
                            <div style="margin-bottom:12px; background:#ffffff; padding:10px 12px; border-radius:6px; border:1px solid #e2e8f0;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <span style="font-weight:700; color:#334155; font-size:12.5px;">⚡ Kecepatan Clock Sensor (XCLK):</span>
                                    <span id="val-cam-xclk" style="color:#0d3b8c; font-weight:800; font-family:monospace; font-size:13px;">20 MHz</span>
                                </div>
                                <div style="display:flex; gap:6px;">
                                    <button type="button" class="btn-xclk-opt" data-xclk="10" style="flex:1; padding:6px 8px; font-size:12px; font-weight:600; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer;">10 MHz</button>
                                    <button type="button" class="btn-xclk-opt" data-xclk="16" style="flex:1; padding:6px 8px; font-size:12px; font-weight:600; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer;">16 MHz</button>
                                    <button type="button" class="btn-xclk-opt active" data-xclk="20" style="flex:1; padding:6px 8px; font-size:12px; font-weight:700; background:#0d3b8c; color:white; border:none; border-radius:4px; cursor:pointer;">20 MHz (Mulus)</button>
                                </div>
                                <div style="font-size:11px; color:#64748b; margin-top:5px;">
                                    *Pilih 20 MHz untuk streaming langsung tanpa lag/freeze.
                                </div>
                            </div>

                            <!-- Kualitas JPEG & Brightness -->
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:12px;">
                                <div>
                                    <div style="display:flex; justify-content:space-between; font-weight:700; color:#475569; margin-bottom:4px; font-size:12px;">
                                        <span>Kualitas JPEG:</span>
                                        <span id="val-cam-quality" style="color:#0d3b8c; font-weight:800;">10</span>
                                    </div>
                                    <input type="range" id="slider-cam-quality" min="4" max="63" value="10" step="1" style="width:100%; accent-color:#0d3b8c;" title="Angka lebih kecil = kualitas gambar makin tinggi dan tajam">
                                    <div style="display:flex; justify-content:space-between; font-size:10.5px; color:#94a3b8; margin-top:2px;">
                                        <span>4 (Tajam)</span>
                                        <span>63 (Buram)</span>
                                    </div>
                                </div>
                                <div>
                                    <div style="display:flex; justify-content:space-between; font-weight:700; color:#475569; margin-bottom:4px; font-size:12px;">
                                        <span>Brightness:</span>
                                        <span id="val-cam-brightness" style="color:#0d3b8c; font-weight:800;">0</span>
                                    </div>
                                    <input type="range" id="slider-cam-brightness" min="-2" max="2" value="0" step="1" style="width:100%; accent-color:#0d3b8c;">
                                </div>
                            </div>

                            <!-- Contrast & Saturation -->
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:12px;">
                                <div>
                                    <div style="display:flex; justify-content:space-between; font-weight:700; color:#475569; margin-bottom:4px; font-size:12px;">
                                        <span>Contrast:</span>
                                        <span id="val-cam-contrast" style="color:#0d3b8c; font-weight:800;">0</span>
                                    </div>
                                    <input type="range" id="slider-cam-contrast" min="-2" max="2" value="0" step="1" style="width:100%; accent-color:#0d3b8c;">
                                </div>
                                <div>
                                    <div style="display:flex; justify-content:space-between; font-weight:700; color:#475569; margin-bottom:4px; font-size:12px;">
                                        <span>Saturation:</span>
                                        <span id="val-cam-saturation" style="color:#0d3b8c; font-weight:800;">0</span>
                                    </div>
                                    <input type="range" id="slider-cam-saturation" min="-2" max="2" value="0" step="1" style="width:100%; accent-color:#0d3b8c;">
                                </div>
                            </div>

                            <!-- Flash LED Intensity -->
                            <div style="margin-bottom:12px;">
                                <div style="display:flex; justify-content:space-between; font-weight:700; color:#475569; margin-bottom:4px; font-size:12px;">
                                    <span>Lampu Flash ESP32-CAM:</span>
                                    <span id="val-cam-led" style="color:#d97706; font-weight:800;">0 (Mati)</span>
                                </div>
                                <input type="range" id="slider-cam-led" min="0" max="255" value="0" step="5" style="width:100%; accent-color:#f59e0b;">
                            </div>

                            <!-- Orientasi Kamera: V-Flip & H-Mirror -->
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:12px; background:#ffffff; padding:10px 12px; border-radius:6px; border:1px solid #e2e8f0;">
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; color:#334155; font-size:12.5px;">
                                    <input type="checkbox" id="chk-cam-vflip" style="accent-color:#0d3b8c;">
                                    <span>V-Flip</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; color:#334155; font-size:12.5px;">
                                    <input type="checkbox" id="chk-cam-hmirror" style="accent-color:#0d3b8c;">
                                    <span>H-Mirror</span>
                                </label>
                            </div>

                            <!-- Exposure & White Balance -->
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:12px; background:#ffffff; padding:10px 12px; border-radius:6px; border:1px solid #e2e8f0;">
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; color:#334155; font-size:12.5px;">
                                    <input type="checkbox" id="chk-cam-awb" checked style="accent-color:#0d3b8c;">
                                    <span>AWB</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; color:#334155; font-size:12.5px;">
                                    <input type="checkbox" id="chk-cam-aec" checked style="accent-color:#0d3b8c;">
                                    <span>AEC</span>
                                </label>
                            </div>

                            <!-- Tombol Reset & IP Manual Dropdown -->
                            <div style="display:flex; gap:8px; margin-top:10px;">
                                <button type="button" id="btn-reset-cam-tuning" style="flex:1; padding:7px 10px; font-size:12px; font-weight:700; background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; color:#475569;" title="Kembalikan semua slider ke standar rekomendasi AI">
                                    ↺ Reset Standar AI
                                </button>
                                <button type="button" id="btn-toggle-manual-ip" style="padding:7px 12px; font-size:12px; font-weight:700; background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; color:#475569;" title="Atur IP Kamera Secara Manual">
                                    IP Manual
                                </button>
                            </div>

                            <!-- Box IP Manual (Hidden by default) -->
                            <div id="box-manual-ip" style="display:none; margin-top:10px; padding:10px; background:#ffffff; border:1px dashed #cbd5e1; border-radius:6px;">
                                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:4px;">IP Kamera Output Manual:</label>
                                <div style="display:flex; gap:8px;">
                                    <input type="text" id="manual-cam-ip-input" class="filter-select" style="flex:1; padding:7px 10px; font-size:12.5px;" placeholder="192.168.1.89">
                                    <button type="button" id="btn-apply-manual-ip" style="padding:7px 14px; background:#0d3b8c; color:white; border:none; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer;">
                                        Simpan
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Mode Pemicu Deteksi (Auto Shutter via RS-485 vs Manual Kontinu) -->
                    <div style="margin-bottom:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px;">
                        <label style="display:flex; justify-content:space-between; align-items:center; cursor:pointer;">
                            <span style="font-size:13.5px; font-weight:700; color:#1e293b;">⚡ Auto-Trigger RS-485 Aktif</span>
                            <input type="checkbox" id="chk-auto-trigger" checked style="width:17px; height:17px; accent-color:#16a34a;">
                        </label>
                        <div style="font-size:12px; color:#64748b; margin-top:5px; line-height:1.4;">
                            Saat Photosensor 2 terpicu, sistem otomatis memotret dan menghitung jumlah part pada hanger.
                        </div>
                    </div>

                    <!-- Tombol Mulai Deteksi Kontinu -->
                    <button type="button" id="btn-start-ai" style="padding:13px 18px; width:100%; text-align:center; background:#16a34a; color:white; border:none; border-radius:8px; font-weight:800; font-size:14.5px; cursor:pointer; transition:all 0.2s;" disabled>
                        ▶ Mulai Deteksi Object (AI)
                    </button>
                </div>

                <!-- CARD: Kontrol Lampu Flash Area Kamera (ESP32 DevKit - Pin 26) -->
                <div style="background:#ffffff; border:1.5px solid #fde68a; border-radius:10px; padding:18px; box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; border-bottom:1px solid #fef9c3; padding-bottom:10px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:20px;">💡</span>
                            <h4 style="margin:0; font-size:16px; color:#92400e; font-weight:800;">Lampu Flash Area Kamera</h4>
                        </div>
                        <span id="devkit-flash-badge" style="font-size:12px; font-weight:700; background:#fef3c7; color:#b45309; padding:3px 10px; border-radius:6px; font-family:ui-monospace,monospace;">Mencari DevKit...</span>
                    </div>

                    <!-- Slider Kecerahan Lampu -->
                    <div style="margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; font-size:13px; font-weight:700; color:#78350f; margin-bottom:5px;">
                            <span>Kecerahan Lampu (Pin 26):</span>
                            <span id="val-devkit-flash" style="color:#d97706; font-weight:800; font-family:monospace; font-size:13.5px;">255 (Maks)</span>
                        </div>
                        <input type="range" id="slider-devkit-flash" min="0" max="255" value="255" step="5"
                            style="width:100%; accent-color:#f59e0b;">
                        <div style="display:flex; justify-content:space-between; font-size:11px; color:#94a3b8; margin-top:3px; font-weight:600;">
                            <span>0 (Mati)</span>
                            <span>128 (Redup)</span>
                            <span>255 (Maks)</span>
                        </div>
                    </div>

                    <!-- Mode Toggle: Solid / Strobe / Off -->
                    <div style="display:flex; gap:6px; margin-bottom:12px;">
                        <button type="button" class="btn-flash-mode active" data-mode="solid"
                            style="flex:1; padding:7px 6px; font-size:12.5px; font-weight:700; background:#f59e0b; color:white; border:none; border-radius:6px; cursor:pointer;">
                            ☀️ Solid
                        </button>
                        <button type="button" class="btn-flash-mode" data-mode="strobe"
                            style="flex:1; padding:7px 6px; font-size:12.5px; font-weight:700; background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;">
                            ⚡ Strobe
                        </button>
                        <button type="button" class="btn-flash-mode" data-mode="off"
                            style="flex:1; padding:7px 6px; font-size:12.5px; font-weight:700; background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;">
                            🌑 Mati
                        </button>
                    </div>

                    <!-- Input IP DevKit Manual -->
                    <div style="background:#fffbeb; border:1px dashed #fcd34d; border-radius:8px; padding:10px;">
                        <div style="font-size:12px; font-weight:700; color:#92400e; margin-bottom:5px;">IP ESP32 DevKit:</div>
                        <div style="display:flex; gap:8px;">
                            <input type="text" id="devkit-ip-input" placeholder="contoh: 192.168.1.50"
                                style="flex:1; padding:7px 10px; font-size:12.5px; border:1px solid #fcd34d; border-radius:6px; background:#fff; font-family:monospace;">
                            <button type="button" id="btn-save-devkit-ip"
                                style="padding:7px 14px; background:#f59e0b; color:white; border:none; border-radius:6px; font-size:12.5px; font-weight:700; cursor:pointer;">
                                Simpan
                            </button>
                        </div>
                        <div style="font-size:11px; color:#a16207; margin-top:4px;">
                            Lihat IP DevKit di Serial Monitor Arduino IDE saat ESP32 menyala.
                        </div>
                    </div>
                </div>

                <!-- CARD 3: Status Sistem & Timeline Log Deteksi -->
                <div style="background:#f8fafc; border:1.5px solid #dbe4f3; border-radius:10px; padding:18px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <h4 style="margin:0; font-size:15px; color:#0d3b8c; font-weight:800;">Status & Riwayat Trigger</h4>
                        <span id="ai-conn-badge" style="font-size:12px; font-weight:700; background:#e2e8f0; color:#475569; padding:3px 9px; border-radius:6px;">Standby</span>
                    </div>
                    
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13.5px;">
                        <span style="color:#64748b;">Status Model Roboflow:</span>
                        <span id="ai-model-status" style="font-weight:700; color:#b45309;">Belum Dimuat</span>
                    </div>
                    
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13.5px;">
                        <span style="color:#64748b;">Koneksi Kamera:</span>
                        <span id="ai-cam-status" style="font-weight:700; color:#b45309;">Terputus</span>
                    </div>

                    <!-- Timeline Log Hanger -->
                    <div style="margin-top:14px; padding-top:12px; border-top:1px dashed #cbd5e1;">
                        <span style="font-size:13px; color:#334155; font-weight:700; display:block; margin-bottom:8px;">
                            Riwayat Event Hanger & Sensor:
                        </span>
                        <div id="ai-predictions-container" style="display:flex; flex-direction:column; gap:6px; max-height:210px; overflow-y:auto; padding-right:4px;">
                            <div style="font-size:13px; color:#94a3b8; font-style:italic;">Menunggu aktivitas sensor atau deteksi...</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- 1. MODAL: KONFIRMASI GANTI PART (CHANGEOVER) -->
<!-- ================================================================ -->
<div id="modal-change-part" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.65); z-index:9999; justify-content:center; align-items:center; backdrop-filter:blur(3px);">
    <div style="background:#ffffff; border-radius:14px; max-width:560px; width:92%; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.25); border:1px solid #e2e8f0; animation:modalSlide 0.25s ease-out;">
        
        <!-- Header Modal -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:26px;">🔄</span>
                <div>
                    <h3 style="margin:0; font-size:18px; color:#0f172a; font-weight:800;">Konfirmasi Ganti Part (Changeover)</h3>
                    <div style="font-size:13px; color:#64748b;">Pergantian jenis part produksi di lini conveyor</div>
                </div>
            </div>
            <button type="button" id="btn-close-modal-change-x" style="background:none; border:none; font-size:20px; color:#94a3b8; cursor:pointer; padding:4px 8px; border-radius:4px;">✕</button>
        </div>

        <!-- Warning Callout -->
        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:14px 16px; font-size:13px; color:#92400e; line-height:1.5; margin-bottom:16px;">
            ⚠️ <strong>SOP Changeover:</strong> Di lini produksi, 1 sesi berjalan khusus untuk 1 jenis part. Mengganti part akan mengarsipkan statistik sesi saat ini ke riwayat, mereset counter conveyor ke 0, dan otomatis memuat Model Roboflow yang sesuai.
        </div>

        <!-- Summary Sesi Part Sebelumnya -->
        <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; padding:14px 16px; margin-bottom:16px;">
            <div style="font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:6px;">Data Sesi Saat Ini yang Akan Diarsipkan:</div>
            <div style="font-size:15px; font-weight:800; color:#0f172a; margin-bottom:10px;" id="modal-summary-part-name">-</div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
                <div style="background:#ffffff; padding:8px 12px; border-radius:6px; border:1px solid #cbd5e1; text-align:center;">
                    <div style="font-size:11.5px; color:#64748b; font-weight:700;">WIP Hanger</div>
                    <div id="modal-summary-wip" style="font-size:19px; font-weight:800; color:#f59e0b;">0</div>
                </div>
                <div style="background:#ffffff; padding:8px 12px; border-radius:6px; border:1px solid #cbd5e1; text-align:center;">
                    <div style="font-size:11.5px; color:#64748b; font-weight:700;">Hanger Output</div>
                    <div id="modal-summary-output" style="font-size:19px; font-weight:800; color:#0f172a;">0</div>
                </div>
                <div style="background:#ffffff; padding:8px 12px; border-radius:6px; border:1px solid #cbd5e1; text-align:center;">
                    <div style="font-size:11.5px; color:#64748b; font-weight:700;">Total Pcs Part</div>
                    <div id="modal-summary-parts" style="font-size:19px; font-weight:800; color:#16a34a;">0</div>
                </div>
            </div>
        </div>

        <!-- Pemilihan Part Baru (Hanya Part yang Sudah Ditraining) -->
        <div id="wrapper-select-change-part" style="margin-bottom:16px;">
            <label style="display:block; font-size:13.5px; font-weight:700; color:#1e293b; margin-bottom:6px;">
                Pilih Part Baru untuk Sesi Berikutnya:
            </label>
            <select id="select-change-part" class="filter-select" style="width:100%; padding:11px 14px; font-size:14px; font-weight:700; border:1.5px solid #0d3b8c; border-radius:8px; background:#f0f7ff;">
                <!-- Diisi dinamis via JS hanya part yang sudah ditraining -->
            </select>
        </div>

        <!-- Notifikasi jika belum ada part yang ditraining di katalog -->
        <div id="box-empty-part-notice" style="display:none; padding:16px; background:#fef2f2; border:1.5px solid #fecaca; border-radius:10px; color:#dc2626; margin-bottom:18px; text-align:center;">
            <div style="font-size:15px; font-weight:800; margin-bottom:6px;">⚠️ Belum Ada Part yang Ditraining</div>
            <div style="font-size:13px; color:#64748b; line-height:1.5; margin-bottom:12px;">
                Katalog saat ini belum memiliki data part. Silakan daftarkan part yang sudah Anda training di Roboflow terlebih dahulu melalui menu <strong>Katalog Part</strong>.
            </div>
            <button type="button" id="btn-goto-manage-parts" style="background:#4f46e5; color:white; border:none; padding:9px 18px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer;">
                ⚙️ Buka Katalog Part Sekarang
            </button>
        </div>

        <!-- Detail Model Part Baru -->
        <div id="box-new-part-preview" style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px 14px; font-size:13px; color:#166534; margin-bottom:18px;">
            <div style="font-weight:700; margin-bottom:4px;">Detail Konfigurasi Otomatis:</div>
            <div>• Slug Model: <strong id="preview-new-slug">-</strong> (Versi: <strong id="preview-new-version">-</strong>)</div>
            <div>• Target Isi: <strong id="preview-new-target">-</strong> Pcs / Hanger</div>
            <div style="font-size:12px; color:#15803d; margin-top:4px;">✨ Operator tidak perlu memasukkan API Key atau setting manual.</div>
        </div>

        <!-- Tombol Aksi Modal -->
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" id="btn-cancel-change-part" style="padding:10px 18px; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:8px; font-weight:700; font-size:13.5px; cursor:pointer;">
                Batal
            </button>
            <button type="button" id="btn-confirm-change-part" style="padding:10px 22px; background:#0d3b8c; color:white; border:none; border-radius:8px; font-weight:800; font-size:13.5px; cursor:pointer; display:flex; align-items:center; gap:6px;">
                ✓ Konfirmasi & Mulai Sesi Part Ini
            </button>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- 2. MODAL: MANAJEMEN KATALOG PART & MODEL ROBOFLOW -->
<!-- ================================================================ -->
<div id="modal-manage-parts" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.65); z-index:9999; justify-content:center; align-items:center; backdrop-filter:blur(3px);">
    <div style="background:#ffffff; border-radius:14px; max-width:840px; width:94%; max-height:90vh; overflow-y:auto; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.25); border:1px solid #e2e8f0; animation:modalSlide 0.25s ease-out;">
        
        <!-- Header Modal -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:26px;">📋</span>
                <div>
                    <h3 style="margin:0; font-size:18px; color:#0f172a; font-weight:800;">Katalog & Profil Part Roboflow</h3>
                    <div style="font-size:13px; color:#64748b;">Kelola daftar part dan konfigurasi model AI pendeteksi part</div>
                </div>
            </div>
            <button type="button" id="btn-close-manage-parts-x" style="background:none; border:none; font-size:20px; color:#94a3b8; cursor:pointer; padding:4px 8px; border-radius:4px;">✕</button>
        </div>

        <!-- Tabel Daftar Part -->
        <div style="margin-bottom:20px;">
            <div style="font-size:13.5px; font-weight:700; color:#334155; margin-bottom:8px;">Daftar Part Terdaftar:</div>
            <div style="border:1px solid #cbd5e1; border-radius:8px; overflow:hidden;">
                <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #cbd5e1; color:#475569; font-weight:700;">
                            <th style="padding:11px 14px;">Kode & Nama Part</th>
                            <th style="padding:11px 14px;">Model Slug Roboflow</th>
                            <th style="padding:11px 14px; text-align:center;">Versi</th>
                            <th style="padding:11px 14px; text-align:center;">Target/Hgr</th>
                            <th style="padding:11px 14px; text-align:center;">Status</th>
                            <th style="padding:11px 14px; text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="table-parts-body">
                        <!-- Diisi via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Form Tambah / Edit Part -->
        <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; padding:18px;">
            <div style="font-size:14.5px; font-weight:800; color:#0d3b8c; margin-bottom:14px; display:flex; align-items:center; gap:8px;">
                <span>➕</span>
                <span id="form-part-title">Tambah Part Baru ke Katalog</span>
            </div>

            <form id="form-save-part" onsubmit="return false;">
                <input type="hidden" id="part-form-id">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#475569; margin-bottom:5px;">Kode Part <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="part-form-code" class="filter-select" style="width:100%; padding:9px 12px; font-size:13px;" placeholder="contoh: BRK-01" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#475569; margin-bottom:5px;">Nama Part <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="part-form-name" class="filter-select" style="width:100%; padding:9px 12px; font-size:13px;" placeholder="contoh: Bracket Depan Motor V2" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#475569; margin-bottom:5px;">Roboflow Model Slug <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="part-form-slug" class="filter-select" style="width:100%; padding:9px 12px; font-size:13px; font-family:monospace;" placeholder="contoh: bracket-motor-detection" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#475569; margin-bottom:5px;">Versi Model <span style="color:#ef4444;">*</span></label>
                        <input type="number" id="part-form-version" class="filter-select" style="width:100%; padding:9px 12px; font-size:13px;" value="1" min="1" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#475569; margin-bottom:5px;">Target Pcs / Hanger <span style="color:#ef4444;">*</span></label>
                        <input type="number" id="part-form-target" class="filter-select" style="width:100%; padding:9px 12px; font-size:13px;" value="4" min="1" required>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:12.5px; font-weight:700; color:#475569; margin-bottom:5px;">Roboflow Publishable API Key <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="part-form-key" class="filter-select" style="width:100%; padding:9px 12px; font-size:13px; font-family:monospace;" placeholder="rf_xxxxxxxxxxxx" required>
                    <div style="font-size:11.5px; color:#94a3b8; margin-top:4px;">Diambil dari menu Deploy > JavaScript Library pada project Roboflow Anda.</div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" id="btn-reset-part-form" style="padding:9px 16px; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:7px; font-size:13px; font-weight:700; cursor:pointer;">
                        Batal Edit
                    </button>
                    <button type="button" id="btn-submit-part-form" style="padding:9px 20px; background:#0d3b8c; color:white; border:none; border-radius:7px; font-size:13px; font-weight:800; cursor:pointer;">
                        💾 Simpan Part
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer Modal -->
        <div style="display:flex; justify-content:flex-end; margin-top:18px;">
            <button type="button" id="btn-close-manage-parts" style="padding:9px 20px; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:7px; font-weight:700; font-size:13.5px; cursor:pointer;">
                Tutup
            </button>
        </div>

    </div>
</div>

<!-- ================================================================ -->
<!-- 3. MODAL: UPDATE VERSI MODEL (SETELAH RETRAINING ROBOFLOW) -->
<!-- ================================================================ -->
<div id="modal-update-version" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.65); z-index:9999; justify-content:center; align-items:center; backdrop-filter:blur(3px);">
    <div style="background:#ffffff; border-radius:14px; max-width:500px; width:92%; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.25); border:1px solid #e2e8f0; animation:modalSlide 0.25s ease-out;">
        
        <!-- Header Modal -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:26px;">🆙</span>
                <div>
                    <h3 style="margin:0; font-size:18px; color:#0f172a; font-weight:800;">Update Versi Model Roboflow</h3>
                    <div style="font-size:13px; color:#64748b;">Perbarui versi model setelah selesai retraining di Roboflow</div>
                </div>
            </div>
            <button type="button" id="btn-close-update-version-x" style="background:none; border:none; font-size:20px; color:#94a3b8; cursor:pointer; padding:4px 8px; border-radius:4px;">✕</button>
        </div>

        <!-- Profil Part Aktif -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:16px; font-size:13.5px;">
            <div>Part Sasaran: <strong id="ver-modal-part-name">-</strong></div>
            <div style="margin-top:5px;">Model Slug: <span id="ver-modal-slug" style="font-family:monospace; color:#0d3b8c; font-weight:700;">-</span></div>
            <div style="margin-top:5px;">Versi Model Saat Ini: <strong id="ver-modal-current" style="color:#d97706;">v1</strong></div>
        </div>

        <!-- Input Versi Baru -->
        <div style="margin-bottom:18px;">
            <label style="display:block; font-size:13.5px; font-weight:700; color:#1e293b; margin-bottom:6px;">
                Nomor Versi Model Terbaru di Roboflow:
            </label>
            <input type="number" id="input-new-model-version" class="filter-select" style="width:100%; padding:11px 14px; font-size:15px; font-weight:800;" min="1" value="2">
            <div style="font-size:12px; color:#64748b; margin-top:5px; line-height:1.4;">
                Setelah kamu upload 100 gambar dari ZIP ke Roboflow dan melatih model baru (misal: v2 atau v3), masukkan nomor versinya di sini.
            </div>
        </div>

        <!-- Tombol Aksi Modal -->
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" id="btn-cancel-update-ver" style="padding:10px 18px; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:8px; font-weight:700; font-size:13.5px; cursor:pointer;">
                Batal
            </button>
            <button type="button" id="btn-save-new-version" style="padding:10px 22px; background:#0284c7; color:white; border:none; border-radius:8px; font-weight:800; font-size:13.5px; cursor:pointer; display:flex; align-items:center; gap:6px;">
                🚀 Terapkan & Reload Model
            </button>
        </div>

    </div>
</div>

<!-- Library Roboflow.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/roboflowjs@latest"></script>

<script>
    // State Global AI & RS-485
    let rfModel = null;
    let isDetecting = false;
    let detectionLoopId = null;
    let lastDetectionTime = 0;
    let lastProcessedTriggerId = null;
    let triggerPollInterval = null;

    // Elements
    const btnLoadModel = document.getElementById('btn-load-model');
    const btnStartAi = document.getElementById('btn-start-ai');
    const btnResetCounter = document.getElementById('btn-reset-counter');

    const streamImg = document.getElementById('ai-mjpeg-stream');
    const streamWrapper = document.getElementById('ai-stream-wrapper');
    const streamPlaceholder = document.getElementById('ai-video-placeholder');
    const canvasOverlay = document.getElementById('ai-canvas-overlay');
    const ctxOverlay = canvasOverlay.getContext('2d');
    const shutterFlash = document.getElementById('ai-shutter-flash');
    const videoContainer = document.getElementById('ai-video-container');

    const statusModel = document.getElementById('ai-model-status');
    const statusCam = document.getElementById('ai-cam-status');
    const containerPredictions = document.getElementById('ai-predictions-container');

    const kpiWipHangers = document.getElementById('kpi-wip-hangers');
    const kpiCurrentParts = document.getElementById('kpi-current-parts');
    const kpiOutputHangers = document.getElementById('kpi-output-hangers');
    const kpiTotalParts = document.getElementById('kpi-total-parts');
    const badgeLatency = document.getElementById('ai-inference-time-badge');

    // Indikator Status Mesin & Sensor RS-485 (Standar 2 Warna: Hijau & Merah)
    const dotMesinStatus = document.getElementById('dot-mesin-status');
    const textMesinStatus = document.getElementById('status-mesin-text');
    const dotSensorInput = document.getElementById('dot-sensor-input');
    const dotSensorOutput = document.getElementById('dot-sensor-output');
    const textSensorInput = document.getElementById('status-sensor-input-text');
    const textSensorOutput = document.getElementById('status-sensor-output-text');
    const chkAutoTrigger = document.getElementById('chk-auto-trigger');

    const sliderConf = document.getElementById('rf-conf-slider');
    const labelConf = document.getElementById('label-conf-val');

    // Elements Kontrol Kamera Output & Tuning Sensor
    let activeCamIp = '';
    const camQuickBadge = document.getElementById('ai-cam-quick-badge');
    const btnReconnectCam = document.getElementById('btn-reconnect-cam');
    const selCamResolution = document.getElementById('cam-resolution-select');
    const btnToggleCamTuning = document.getElementById('btn-toggle-cam-tuning');
    const panelCamTuning = document.getElementById('panel-cam-tuning');
    const iconToggleCamTuning = document.getElementById('icon-toggle-cam-tuning');

    const sliderCamQuality = document.getElementById('slider-cam-quality');
    const valCamQuality = document.getElementById('val-cam-quality');
    const sliderCamBrightness = document.getElementById('slider-cam-brightness');
    const valCamBrightness = document.getElementById('val-cam-brightness');
    const sliderCamContrast = document.getElementById('slider-cam-contrast');
    const valCamContrast = document.getElementById('val-cam-contrast');
    const sliderCamSaturation = document.getElementById('slider-cam-saturation');
    const valCamSaturation = document.getElementById('val-cam-saturation');
    const sliderCamLed = document.getElementById('slider-cam-led');
    const valCamLed = document.getElementById('val-cam-led');

    const chkCamVflip = document.getElementById('chk-cam-vflip');
    const chkCamHmirror = document.getElementById('chk-cam-hmirror');
    const chkCamAwb = document.getElementById('chk-cam-awb');
    const chkCamAec = document.getElementById('chk-cam-aec');
    const selectCamEffect = document.getElementById('select-cam-effect');
    const btnResetCamTuning = document.getElementById('btn-reset-cam-tuning');

    const btnToggleManualIp = document.getElementById('btn-toggle-manual-ip');
    const boxManualIp = document.getElementById('box-manual-ip');
    const manualCamIpInput = document.getElementById('manual-cam-ip-input');
    const btnApplyManualIp = document.getElementById('btn-apply-manual-ip');

    // ================================================================
    // KONTROL LAMPU FLASH DEVKIT (PIN 26)
    // ================================================================
    let activeDevkitIp = '';
    const devkitFlashBadge = document.getElementById('devkit-flash-badge');
    const sliderDevkitFlash = document.getElementById('slider-devkit-flash');
    const valDevkitFlash = document.getElementById('val-devkit-flash');
    const devkitIpInput = document.getElementById('devkit-ip-input');
    const btnSaveDevkitIp = document.getElementById('btn-save-devkit-ip');
    let flashDebounceTimer = null;

    function labelFlash(val) {
        val = parseInt(val);
        if (val === 0)   return '0 (Mati)';
        if (val <= 50)   return val + ' (Sangat Redup)';
        if (val <= 100)  return val + ' (Redup)';
        if (val <= 170)  return val + ' (Sedang)';
        if (val <= 220)  return val + ' (Terang)';
        return val + ' (Maks)';
    }

    function updateFlashModeButtons(activeMode) {
        document.querySelectorAll('.btn-flash-mode').forEach(btn => {
            const isActive = btn.dataset.mode === activeMode;
            btn.style.background = isActive ? '#f59e0b' : '#f1f5f9';
            btn.style.color      = isActive ? 'white'  : '#334155';
            btn.style.border     = isActive ? 'none'   : '1px solid #cbd5e1';
            btn.style.fontWeight = isActive ? '700'    : '600';
        });
    }

    async function sendFlashCmd(params) {
        if (!activeDevkitIp) {
            devkitFlashBadge.innerText = '⚠ IP DevKit Belum Diset';
            devkitFlashBadge.style.background = '#fee2e2';
            devkitFlashBadge.style.color = '#dc2626';
            return;
        }
        try {
            await fetch(`http://${activeDevkitIp}/lampu?${params}`, { mode: 'no-cors' });
            devkitFlashBadge.innerText = `🟢 Online (${activeDevkitIp})`;
            devkitFlashBadge.style.background = '#dcfce7';
            devkitFlashBadge.style.color = '#16a34a';
        } catch (e) {
            devkitFlashBadge.innerText = `🔴 Offline (${activeDevkitIp})`;
            devkitFlashBadge.style.background = '#fee2e2';
            devkitFlashBadge.style.color = '#dc2626';
        }
    }

    // Slider kecerahan: debounce 400ms agar tidak spam request
    sliderDevkitFlash.addEventListener('input', (e) => {
        const val = parseInt(e.target.value);
        valDevkitFlash.innerText = labelFlash(val);
        clearTimeout(flashDebounceTimer);
        flashDebounceTimer = setTimeout(() => {
            sendFlashCmd(`val=${val}`);
        }, 400);
    });

    // Tombol mode: solid / strobe / off
    document.querySelectorAll('.btn-flash-mode').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            updateFlashModeButtons(mode);
            const val = parseInt(sliderDevkitFlash.value);
            if (mode === 'off') {
                sendFlashCmd('mode=off');
            } else if (mode === 'strobe') {
                sendFlashCmd(`mode=strobe&val=${val}`);
            } else {
                sendFlashCmd(`mode=solid&val=${val}`);
            }
        });
    });

    // Simpan IP DevKit ke backend & localStorage
    btnSaveDevkitIp.addEventListener('click', async () => {
        const ip = devkitIpInput.value.trim();
        if (!ip) return;
        activeDevkitIp = ip;
        localStorage.setItem('devkit_ip', ip);
        await fetch(`/api/set-devkit-ip?ip=${encodeURIComponent(ip)}`).catch(() => {});
        devkitFlashBadge.innerText = '🔄 Mencoba...';
        devkitFlashBadge.style.background = '#fef3c7';
        devkitFlashBadge.style.color = '#b45309';
        // Langsung coba konek dengan setting saat ini
        const val = parseInt(sliderDevkitFlash.value);
        await sendFlashCmd(`mode=solid&val=${val}`);
        appendLog(`💡 IP ESP32 DevKit disimpan: ${ip}`, '#d97706');
    });

    // Load IP DevKit dari server & localStorage saat halaman dibuka
    async function loadDevkitIp() {
        const local = localStorage.getItem('devkit_ip');
        try {
            const res = await fetch('/api/get-devkit-ip');
            const data = await res.json();
            const ip = data.ip || local || '';
            if (ip) {
                activeDevkitIp = ip;
                devkitIpInput.value = ip;
                devkitFlashBadge.innerText = `Terakhir: ${ip}`;
                localStorage.setItem('devkit_ip', ip);
            }
        } catch (e) {
            if (local) {
                activeDevkitIp = local;
                devkitIpInput.value = local;
                devkitFlashBadge.innerText = `Terakhir: ${local}`;
            }
        }
    }
    loadDevkitIp();

    // Load Saved Settings dari LocalStorage
    const savedModel = localStorage.getItem('rf_model_id');
    const savedVersion = localStorage.getItem('rf_model_version');
    const savedKey = localStorage.getItem('rf_api_key');
    const savedConf = localStorage.getItem('rf_confidence');

    if (savedModel) document.getElementById('rf-model-input').value = savedModel;
    if (savedVersion) document.getElementById('rf-version-input').value = savedVersion;
    if (savedKey) document.getElementById('rf-key-input').value = savedKey;
    if (savedConf) {
        sliderConf.value = savedConf;
        labelConf.innerText = savedConf + '%';
    }

    sliderConf.addEventListener('input', (e) => {
        labelConf.innerText = e.target.value + '%';
        localStorage.setItem('rf_confidence', e.target.value);
    });

    // Toggle Password Visibility untuk API Key
    document.getElementById('btn-toggle-key').addEventListener('click', () => {
        const input = document.getElementById('rf-key-input');
        input.type = input.type === 'password' ? 'text' : 'password';
    });

    // Tombol Sinkronisasi IP Kamera di Header
    const btnSyncHeader = document.getElementById('btn-selesai-konfigurasi');
    if (btnSyncHeader) {
        btnSyncHeader.addEventListener('click', () => {
            autoConnectCamera(true);
        });
    }

    // Muat data statistik hanger saat halaman dibuka
    async function loadHangerStats() {
        try {
            const res = await fetch('/api/get-hanger-stats');
            const data = await res.json();
            if (data && data.stats) {
                kpiWipHangers.innerText = data.stats.wip_hangers || 0;
                kpiOutputHangers.innerText = data.stats.total_output_hangers || 0;
                kpiTotalParts.innerText = data.stats.total_output_pcs || 0;
                if (data.stats.is_produksi !== undefined) {
                    updateMesinUI(data.stats.is_produksi == 1);
                }
            }
        } catch (e) {}
    }
    loadHangerStats();

    // Reset Statistik Counter
    btnResetCounter.addEventListener('click', async () => {
        if (!confirm('Yakin ingin mereset statistik counter hanger & output?')) return;
        try {
            await fetch('/api/reset-hanger-stats', { method: 'POST' });
            kpiWipHangers.innerText = '0';
            kpiOutputHangers.innerText = '0';
            kpiTotalParts.innerText = '0';
            kpiCurrentParts.innerText = '0';
            appendLog('🔄 Statistik hanger & output berhasil direset.', '#ef4444');
        } catch (e) {}
    });

    // Helper Tambah Log ke Panel
    function appendLog(text, color = '#334155') {
        const time = new Date().toLocaleTimeString('id-ID');
        const logItem = document.createElement('div');
        logItem.style.display = 'flex';
        logItem.style.alignItems = 'center';
        logItem.style.justifyContent = 'space-between';
        logItem.style.padding = '5px 8px';
        logItem.style.background = '#ffffff';
        logItem.style.borderRadius = '6px';
        logItem.style.border = '1px solid #e2e8f0';
        logItem.style.fontSize = '12px';

        logItem.innerHTML = `
            <span style="color:${color}; font-weight:600;">${text}</span>
            <span style="font-size:10.5px; color:#94a3b8; font-family:monospace;">${time}</span>
        `;

        if (containerPredictions.children.length === 1 && containerPredictions.children[0].innerText.includes('Menunggu')) {
            containerPredictions.innerHTML = '';
        }

        containerPredictions.insertBefore(logItem, containerPredictions.firstChild);
        if (containerPredictions.children.length > 25) {
            containerPredictions.removeChild(containerPredictions.lastChild);
        }
    }

    // 1. MUAT MODEL ROBOFLOW (Reusable Helper)
    async function initRoboflowModel(modelId, version, apiKey, silent = false) {
        if (!modelId || !apiKey) {
            statusModel.innerText = 'Model Belum Dikonfigurasi';
            statusModel.style.color = '#94a3b8';
            const badgeModel = document.getElementById('badge-ai-model-ready');
            if (badgeModel) {
                badgeModel.innerText = '⚠ Belum Siap';
                badgeModel.style.background = '#fee2e2';
                badgeModel.style.color = '#dc2626';
            }
            return false;
        }

        const badgeModel = document.getElementById('badge-ai-model-ready');
        if (badgeModel) {
            badgeModel.innerText = '⏳ Memuat Model...';
            badgeModel.style.background = '#fef3c7';
            badgeModel.style.color = '#b45309';
        }
        statusModel.innerText = `Memuat [${modelId} v${version}]...`;
        statusModel.style.color = '#b45309';
        btnLoadModel.disabled = true;

        try {
            if (typeof roboflow === 'undefined') {
                throw new Error('Library roboflow.js belum termuat. Periksa koneksi internet.');
            }

            rfModel = await roboflow.auth({
                publishable_key: apiKey
            }).load({
                model: modelId,
                version: parseInt(version, 10) || 1
            });

            statusModel.innerText = `Model Siap (${modelId} v${version})`;
            statusModel.style.color = '#16a34a';
            if (badgeModel) {
                badgeModel.innerText = `🟢 v${version} Siap`;
                badgeModel.style.background = '#dcfce7';
                badgeModel.style.color = '#15803d';
            }
            checkEnableDetectButton();
            appendLog(`🤖 Model Roboflow [${modelId} v${version}] aktif & siap mendeteksi.`, '#16a34a');
            return true;
        } catch (err) {
            console.error('Error memuat model Roboflow:', err);
            statusModel.innerText = 'Gagal memuat model!';
            statusModel.style.color = '#dc2626';
            if (badgeModel) {
                badgeModel.innerText = '🔴 Gagal';
                badgeModel.style.background = '#fee2e2';
                badgeModel.style.color = '#dc2626';
            }
            if (!silent) {
                alert(`Gagal memuat model Roboflow (${modelId} v${version}). Periksa koneksi internet atau Publishable API Key.`);
            }
            return false;
        } finally {
            btnLoadModel.disabled = false;
        }
    }

    btnLoadModel.addEventListener('click', async () => {
        const modelId = document.getElementById('rf-model-input').value.trim();
        const version = parseInt(document.getElementById('rf-version-input').value.trim(), 10) || 1;
        const apiKey = document.getElementById('rf-key-input').value.trim();

        if (!modelId || !apiKey) {
            alert('Harap isi Model ID dan Publishable API Key dari Roboflow terlebih dahulu!');
            return;
        }

        localStorage.setItem('rf_model_id', modelId);
        localStorage.setItem('rf_model_version', version);
        localStorage.setItem('rf_api_key', apiKey);

        await initRoboflowModel(modelId, version, apiKey, false);
    });

    // Toggle Collapsible Pengaturan Manual Roboflow
    const btnToggleAdvancedRf = document.getElementById('btn-toggle-advanced-rf');
    const panelAdvancedRf = document.getElementById('panel-advanced-rf');
    const iconToggleAdvancedRf = document.getElementById('icon-toggle-advanced-rf');
    if (btnToggleAdvancedRf && panelAdvancedRf) {
        btnToggleAdvancedRf.addEventListener('click', () => {
            const isHidden = panelAdvancedRf.style.display === 'none';
            panelAdvancedRf.style.display = isHidden ? 'block' : 'none';
            if (iconToggleAdvancedRf) iconToggleAdvancedRf.innerText = isHidden ? '▲' : '▼';
        });
    }

    // ==============================================================
    // 2. SISTEM AUTO-CONNECT KAMERA OUTPUT & PENGATURAN SENSOR (TUNING)
    // ==============================================================
    async function autoConnectCamera(showNotice = false) {
        if (camQuickBadge) {
            camQuickBadge.innerText = 'Menghubungkan...';
            camQuickBadge.style.background = '#fef3c7';
            camQuickBadge.style.color = '#b45309';
        }
        if (statusCam) {
            statusCam.innerText = 'Menghubungkan...';
            statusCam.style.color = '#b45309';
        }

        try {
            const res = await fetch('/api/get-cam-ip');
            const data = await res.json();
            let targetIp = (data && data.ip) ? data.ip.trim() : '';

            if (!targetIp) {
                targetIp = localStorage.getItem('painting_cam_output_ip') || '';
            }

            // Jika belum ada IP kamera yang melapor
            if (!targetIp) {
                if (camQuickBadge) {
                    camQuickBadge.innerText = 'Menunggu Kamera...';
                    camQuickBadge.style.background = '#fef3c7';
                    camQuickBadge.style.color = '#b45309';
                }
                statusCam.innerText = 'Menunggu IP Kamera';
                statusCam.style.color = '#b45309';
                const pTitle = document.getElementById('ai-video-placeholder-title');
                const pDesc = document.getElementById('ai-video-placeholder-desc');
                if (pTitle) pTitle.innerText = '[ Menunggu Kamera ESP32-CAM Menyala ]';
                if (pDesc) pDesc.innerText = 'Nyalakan ESP32-CAM. Kamera akan otomatis terhubung saat mendapat IP dari WiFi.';
                return;
            }

            activeCamIp = targetIp;
            localStorage.setItem('painting_cam_output_ip', targetIp);
            if (manualCamIpInput) manualCamIpInput.value = targetIp;

            const streamUrl = `http://${activeCamIp}:81/stream`;
            streamImg.src = streamUrl;

            if (showNotice) {
                appendLog(`🔄 Menyambungkan ulang ke Kamera Output (IP: ${activeCamIp})...`, '#0284c7');
            }
        } catch (e) {
            console.error('Gagal mengambil IP kamera:', e);
            if (camQuickBadge) {
                camQuickBadge.innerText = 'Offline';
                camQuickBadge.style.background = '#fee2e2';
                camQuickBadge.style.color = '#dc2626';
            }
        }
    }

    streamImg.onload = () => {
        streamPlaceholder.style.display = 'none';
        streamWrapper.style.display = 'block';
        if (camQuickBadge) {
            camQuickBadge.innerText = `🟢 Online (${activeCamIp})`;
            camQuickBadge.style.background = '#dcfce7';
            camQuickBadge.style.color = '#15803d';
        }
        statusCam.innerText = `Tersambung (${activeCamIp})`;
        statusCam.style.color = '#16a34a';

        canvasOverlay.width = streamImg.naturalWidth || streamImg.clientWidth || 640;
        canvasOverlay.height = streamImg.naturalHeight || streamImg.clientHeight || 480;

        checkEnableDetectButton();
        appendLog(`📹 Kamera ESP32-CAM (${activeCamIp}) aktif & live stream dimulai.`, '#16a34a');

        // Sinkronkan settingan sensor kamera dari ESP32
        syncCameraSettingsFromDevice(activeCamIp);
    };

    streamImg.onerror = () => {
        if (camQuickBadge) {
            camQuickBadge.innerText = `🔴 Offline (${activeCamIp || 'Kamera'})`;
            camQuickBadge.style.background = '#fee2e2';
            camQuickBadge.style.color = '#dc2626';
        }
        statusCam.innerText = 'Error / Terputus';
        statusCam.style.color = '#dc2626';
        streamWrapper.style.display = 'none';
        streamPlaceholder.style.display = 'flex';
        
        const pTitle = document.getElementById('ai-video-placeholder-title');
        const pDesc = document.getElementById('ai-video-placeholder-desc');
        if (pTitle) pTitle.innerText = `[ Kamera di ${activeCamIp || 'Jaringan'} Terputus ]`;
        if (pDesc) pDesc.innerText = `Pastikan ESP32-CAM menyala dan terhubung ke WiFi. Klik tombol 🔄 untuk mencoba lagi.`;

        if (isDetecting) {
            isDetecting = false;
            updateDetectButtonState();
        }
    };

    async function sendCamControl(variable, value) {
        if (!activeCamIp) return;
        try {
            const url = `http://${activeCamIp}/control?var=${variable}&val=${value}`;
            fetch(url, { mode: 'no-cors' }).catch(() => {});
            localStorage.setItem(`cam_setting_${variable}`, value);
        } catch (e) {
            console.warn(`Gagal mengirim kontrol kamera: ${variable}=${value}`);
        }
    }

    async function syncCameraSettingsFromDevice(ip) {
        if (!ip) return;
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 2000);
            const res = await fetch(`http://${ip}/status`, { signal: controller.signal });
            clearTimeout(timeoutId);
            if (!res.ok) return;
            const data = await res.json();
            if (!data) return;

            if (data.framesize !== undefined && selCamResolution) selCamResolution.value = data.framesize;
            if (data.quality !== undefined && sliderCamQuality) {
                sliderCamQuality.value = data.quality;
                valCamQuality.innerText = formatQualityDesc(data.quality);
            }
            if (data.brightness !== undefined && sliderCamBrightness) {
                sliderCamBrightness.value = data.brightness;
                valCamBrightness.innerText = data.brightness;
            }
            if (data.contrast !== undefined && sliderCamContrast) {
                sliderCamContrast.value = data.contrast;
                valCamContrast.innerText = data.contrast;
            }
            if (data.saturation !== undefined && sliderCamSaturation) {
                sliderCamSaturation.value = data.saturation;
                valCamSaturation.innerText = data.saturation;
            }
            if (data.led_intensity !== undefined && data.led_intensity >= 0 && sliderCamLed) {
                sliderCamLed.value = data.led_intensity;
                valCamLed.innerText = data.led_intensity > 0 ? data.led_intensity : '0 (Mati)';
            }
            if (data.vflip !== undefined && chkCamVflip) chkCamVflip.checked = !!data.vflip;
            if (data.hmirror !== undefined && chkCamHmirror) chkCamHmirror.checked = !!data.hmirror;
            if (data.awb !== undefined && chkCamAwb) chkCamAwb.checked = !!data.awb;
            if (data.aec !== undefined && chkCamAec) chkCamAec.checked = !!data.aec;
            if (data.special_effect !== undefined && selectCamEffect) selectCamEffect.value = data.special_effect;
        } catch (e) {
            loadSavedCameraSettingsFromStorage();
        }
    }

    function loadSavedCameraSettingsFromStorage() {
        const savedRes = localStorage.getItem('cam_setting_framesize');
        if (savedRes && selCamResolution) selCamResolution.value = savedRes;
        
        const savedQuality = localStorage.getItem('cam_setting_quality');
        if (savedQuality && sliderCamQuality) {
            sliderCamQuality.value = savedQuality;
            valCamQuality.innerText = savedQuality;
        }

        const savedBri = localStorage.getItem('cam_setting_brightness');
        if (savedBri && sliderCamBrightness) {
            sliderCamBrightness.value = savedBri;
            valCamBrightness.innerText = savedBri;
        }

        const savedCon = localStorage.getItem('cam_setting_contrast');
        if (savedCon && sliderCamContrast) {
            sliderCamContrast.value = savedCon;
            valCamContrast.innerText = savedCon;
        }

        const savedVflip = localStorage.getItem('cam_setting_vflip');
        if (savedVflip !== null && chkCamVflip) chkCamVflip.checked = savedVflip === '1';

        const savedHmirror = localStorage.getItem('cam_setting_hmirror');
        if (savedHmirror !== null && chkCamHmirror) chkCamHmirror.checked = savedHmirror === '1';
    }

    // Event Listener Resolusi Kamera
    if (selCamResolution) {
        selCamResolution.addEventListener('change', (e) => {
            const val = e.target.value;
            sendCamControl('framesize', val);
            appendLog(`📐 Resolusi kamera diubah: ${e.target.options[e.target.selectedIndex].text}`, '#0d3b8c');
        });
    }

    // Event Listener XCLK Clock (Kecepatan Streaming Sensor)
    document.querySelectorAll('.btn-xclk-opt').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const xclk = e.target.getAttribute('data-xclk');
            document.querySelectorAll('.btn-xclk-opt').forEach(b => {
                b.style.background = '#f1f5f9';
                b.style.color = '#334155';
                b.style.fontWeight = '600';
            });
            e.target.style.background = '#0d3b8c';
            e.target.style.color = '#ffffff';
            e.target.style.fontWeight = '700';
            const valLabel = document.getElementById('val-cam-xclk');
            if (valLabel) valLabel.innerText = xclk + ' MHz';

            if (activeCamIp) {
                fetch(`http://${activeCamIp}/xclk?xclk=${xclk}`, { mode: 'no-cors' }).catch(() => {});
                appendLog(`⚡ Kecepatan XCLK diatur ke ${xclk} MHz.`, '#0d3b8c');
            }
        });
    });

    // Event Listener Toggle Collapsible Panel
    if (btnToggleCamTuning && panelCamTuning) {
        btnToggleCamTuning.addEventListener('click', () => {
            const isOpen = panelCamTuning.style.display !== 'none';
            panelCamTuning.style.display = isOpen ? 'none' : 'block';
            if (iconToggleCamTuning) {
                iconToggleCamTuning.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
            }
        });
    }

    function formatQualityDesc(val) {
        const v = parseInt(val);
        if (v <= 10) return `${v} (Super Jernih)`;
        if (v <= 16) return `${v} (Tinggi)`;
        if (v <= 30) return `${v} (Sedang)`;
        return `${v} (Buram)`;
    }

    // Sliders & Checkbox Tuning Kamera
    if (sliderCamQuality) {
        sliderCamQuality.addEventListener('input', (e) => valCamQuality.innerText = formatQualityDesc(e.target.value));
        sliderCamQuality.addEventListener('change', (e) => sendCamControl('quality', e.target.value));
    }
    if (sliderCamBrightness) {
        sliderCamBrightness.addEventListener('input', (e) => valCamBrightness.innerText = e.target.value);
        sliderCamBrightness.addEventListener('change', (e) => sendCamControl('brightness', e.target.value));
    }
    if (sliderCamContrast) {
        sliderCamContrast.addEventListener('input', (e) => valCamContrast.innerText = e.target.value);
        sliderCamContrast.addEventListener('change', (e) => sendCamControl('contrast', e.target.value));
    }
    if (sliderCamSaturation) {
        sliderCamSaturation.addEventListener('input', (e) => valCamSaturation.innerText = e.target.value);
        sliderCamSaturation.addEventListener('change', (e) => sendCamControl('saturation', e.target.value));
    }
    if (sliderCamLed) {
        sliderCamLed.addEventListener('input', (e) => valCamLed.innerText = e.target.value > 0 ? e.target.value : '0 (Mati)');
        sliderCamLed.addEventListener('change', (e) => sendCamControl('led_intensity', e.target.value));
    }
    if (chkCamVflip) {
        chkCamVflip.addEventListener('change', (e) => sendCamControl('vflip', e.target.checked ? 1 : 0));
    }
    if (chkCamHmirror) {
        chkCamHmirror.addEventListener('change', (e) => sendCamControl('hmirror', e.target.checked ? 1 : 0));
    }
    if (chkCamAwb) {
        chkCamAwb.addEventListener('change', (e) => sendCamControl('awb', e.target.checked ? 1 : 0));
    }
    if (chkCamAec) {
        chkCamAec.addEventListener('change', (e) => sendCamControl('aec', e.target.checked ? 1 : 0));
    }
    if (selectCamEffect) {
        selectCamEffect.addEventListener('change', (e) => sendCamControl('special_effect', e.target.value));
    }

    // Reset Camera Tuning
    if (btnResetCamTuning) {
        btnResetCamTuning.addEventListener('click', () => {
            if (selCamResolution) selCamResolution.value = '8';
            if (sliderCamQuality) { sliderCamQuality.value = '12'; valCamQuality.innerText = '12'; }
            if (sliderCamBrightness) { sliderCamBrightness.value = '0'; valCamBrightness.innerText = '0'; }
            if (sliderCamContrast) { sliderCamContrast.value = '0'; valCamContrast.innerText = '0'; }
            if (sliderCamSaturation) { sliderCamSaturation.value = '0'; valCamSaturation.innerText = '0'; }
            if (sliderCamLed) { sliderCamLed.value = '0'; valCamLed.innerText = '0 (Mati)'; }
            if (chkCamVflip) chkCamVflip.checked = false;
            if (chkCamHmirror) chkCamHmirror.checked = false;
            if (chkCamAwb) chkCamAwb.checked = true;
            if (chkCamAec) chkCamAec.checked = true;
            if (selectCamEffect) selectCamEffect.value = '0';

            sendCamControl('framesize', 8);
            sendCamControl('quality', 12);
            sendCamControl('brightness', 0);
            sendCamControl('contrast', 0);
            sendCamControl('saturation', 0);
            sendCamControl('led_intensity', 0);
            sendCamControl('vflip', 0);
            sendCamControl('hmirror', 0);
            sendCamControl('awb', 1);
            sendCamControl('aec', 1);
            sendCamControl('special_effect', 0);

            appendLog('↺ Pengaturan kamera dikembalikan ke standar rekomendasi AI (VGA 640x480).', '#0d3b8c');
        });
    }

    // Tombol IP Manual
    if (btnToggleManualIp && boxManualIp) {
        btnToggleManualIp.addEventListener('click', () => {
            boxManualIp.style.display = boxManualIp.style.display === 'none' ? 'block' : 'none';
        });
    }
    if (btnApplyManualIp && manualCamIpInput) {
        btnApplyManualIp.addEventListener('click', async () => {
            const manualIp = manualCamIpInput.value.trim();
            if (!manualIp) return alert('Masukkan IP yang valid!');
            activeCamIp = manualIp;
            localStorage.setItem('painting_cam_output_ip', manualIp);
            try {
                await fetch(`/api/set-cam-ip?ip=${manualIp}`);
            } catch (e) {}
            autoConnectCamera(true);
            boxManualIp.style.display = 'none';
        });
    }

    // Tombol Reconnect Cepat
    if (btnReconnectCam) {
        btnReconnectCam.addEventListener('click', () => autoConnectCamera(true));
    }

    // Mulai Auto-Connect Kamera Otomatis Saat Halaman Terbuka
    autoConnectCamera(false);

    // 3. TOGGLE DETEKSI KONTINU
    btnStartAi.addEventListener('click', () => {
        if (!isDetecting) {
            isDetecting = true;
            updateDetectButtonState();
            detectLoop();
        } else {
            isDetecting = false;
            if (detectionLoopId) cancelAnimationFrame(detectionLoopId);
            updateDetectButtonState();
            ctxOverlay.clearRect(0, 0, canvasOverlay.width, canvasOverlay.height);
        }
    });

    function checkEnableDetectButton() {
        if (rfModel && statusCam.innerText === 'Tersambung') {
            btnStartAi.disabled = false;
        } else {
            btnStartAi.disabled = true;
            if (isDetecting) {
                isDetecting = false;
                updateDetectButtonState();
            }
        }
    }

    function updateDetectButtonState() {
        if (isDetecting) {
            btnStartAi.innerHTML = '⏹ Hentikan Deteksi Kontinu';
            btnStartAi.style.background = '#dc2626';
            badgeLatency.style.display = 'inline-block';
        } else {
            btnStartAi.innerHTML = '▶ Mulai Deteksi Object (AI)';
            btnStartAi.style.background = '#16a34a';
            badgeLatency.style.display = 'none';
        }
    }

    // Loop Prediksi Kontinu
    async function detectLoop() {
        if (!isDetecting) return;

        const now = Date.now();
        if (now - lastDetectionTime > 120) {
            lastDetectionTime = now;
            await runObjectDetection(false); // false = jangan trigger konfirmasi otomatis saat sekadar live preview
        }

        detectionLoopId = requestAnimationFrame(detectLoop);
    }

    // 4. INFERENSI OBJECT DETECTION & GAMBAR BOUNDING BOX
    async function runObjectDetection(isShutterTrigger = false) {
        if (!rfModel || streamWrapper.style.display === 'none') return 0;

        const tStart = performance.now();

        try {
            const predictions = await rfModel.detect(streamImg);
            const tEnd = performance.now();
            badgeLatency.innerText = Math.round(tEnd - tStart) + ' ms';

            const minConfidence = (parseInt(sliderConf.value, 10) || 50) / 100;
            const validDetections = (predictions || []).filter(p => (p.confidence || 0) >= minConfidence);

            ctxOverlay.clearRect(0, 0, canvasOverlay.width, canvasOverlay.height);

            const countPart = validDetections.length;
            kpiCurrentParts.innerText = countPart;

            // Gambar setiap bounding box
            validDetections.forEach((pred, index) => {
                const bbox = pred.bbox || pred;
                const x = bbox.x;
                const y = bbox.y;
                const width = bbox.width;
                const height = bbox.height;
                const className = pred.class || pred.name || 'Part';
                const confScore = Math.round((pred.confidence || 0) * 100);

                const left = x - width / 2;
                const top = y - height / 2;

                // Box Deteksi Bounding Box (Hijau Standar)
                ctxOverlay.strokeStyle = '#22c55e';
                ctxOverlay.lineWidth = 3;
                ctxOverlay.strokeRect(left, top, width, height);

                // Label
                const labelText = `#${index + 1} ${className} ${confScore}%`;
                ctxOverlay.font = 'bold 12px Inter, sans-serif';
                const textWidth = ctxOverlay.measureText(labelText).width;

                ctxOverlay.fillStyle = '#22c55e';
                ctxOverlay.fillRect(left, Math.max(0, top - 20), textWidth + 10, 20);

                ctxOverlay.fillStyle = '#ffffff';
                ctxOverlay.fillText(labelText, left + 5, Math.max(14, top - 5));
            });

            // Jika dipanggil oleh trigger Photosensor Output, simpan konfirmasi ke backend!
            if (isShutterTrigger) {
                const res = await fetch('/api/confirm-hanger-output', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pcs_count: countPart })
                });
                const resData = await res.json();
                if (resData && resData.stats) {
                    kpiWipHangers.innerText = resData.stats.wip_hangers || 0;
                    kpiOutputHangers.innerText = resData.stats.total_output_hangers || 0;
                    kpiTotalParts.innerText = resData.stats.total_output_pcs || 0;
                }
                appendLog(`📸 [Trigger Sensor 2] Hanger Selesai & Terkonfirmasi: ${countPart} Pcs Part!`, '#0d3b8c');
            }

            return countPart;
        } catch (e) {
            console.error('Error saat inferensi Roboflow:', e);
            return 0;
        }
    }

    // ================================================================
    // KOLEKTOR DATASET ROBOFLOW (100 SAMPEL TRAINING)
    // ================================================================
    let sampleCount = 0;
    const maxSamples = 100;
    let isSamplingActive = false;

    const badgeSampleStatus = document.getElementById('badge-sample-status');
    const labelSampleProgress = document.getElementById('label-sample-progress');
    const barSampleProgress = document.getElementById('bar-sample-progress');
    const chkAutoSampleOnHanger = document.getElementById('chk-auto-sample-on-hanger');
    const btnToggleSampling = document.getElementById('btn-toggle-sampling');
    const btnCaptureManualSample = document.getElementById('btn-capture-manual-sample');
    const btnDownloadDatasetZip = document.getElementById('btn-download-dataset-zip');
    const btnResetSamples = document.getElementById('btn-reset-samples');
    const samplePreviewContainer = document.getElementById('sample-preview-container');
    const sampleEmptyNotice = document.getElementById('sample-empty-notice');

    function updateMesinUI(isOn) {
        if (isOn) {
            if (dotMesinStatus) {
                dotMesinStatus.style.background = '#22c55e';
                dotMesinStatus.style.boxShadow = '0 0 12px #22c55e';
            }
            if (textMesinStatus) {
                textMesinStatus.innerText = 'PRODUKSI AKTIF (Nilai 1)';
                textMesinStatus.style.color = '#16a34a';
            }
        } else {
            if (dotMesinStatus) {
                dotMesinStatus.style.background = '#ef4444';
                dotMesinStatus.style.boxShadow = '0 0 8px rgba(239,68,68,0.5)';
            }
            if (textMesinStatus) {
                textMesinStatus.innerText = 'STANDBY (Nilai 0)';
                textMesinStatus.style.color = '#dc2626';
            }
        }
    }

    function updateSampleUI() {
        const percent = Math.min(100, Math.round((sampleCount / maxSamples) * 100));
        if (barSampleProgress) barSampleProgress.style.width = percent + '%';
        if (labelSampleProgress) labelSampleProgress.innerText = `${sampleCount} / ${maxSamples} Sampel (${percent}%)`;

        if (!badgeSampleStatus || !btnDownloadDatasetZip) return;

        if (sampleCount >= maxSamples) {
            badgeSampleStatus.innerText = `✅ Lengkap (100 / 100)`;
            badgeSampleStatus.style.background = '#dcfce7';
            badgeSampleStatus.style.color = '#16a34a';
            btnDownloadDatasetZip.style.opacity = '1';
            btnDownloadDatasetZip.style.pointerEvents = 'auto';
            btnDownloadDatasetZip.style.boxShadow = '0 0 12px rgba(22, 163, 74, 0.4)';
        } else if (isSamplingActive) {
            badgeSampleStatus.innerText = `🟢 Auto-Sampling (${sampleCount} / 100)`;
            badgeSampleStatus.style.background = '#fef3c7';
            badgeSampleStatus.style.color = '#b45309';
            if (sampleCount > 0) {
                btnDownloadDatasetZip.style.opacity = '1';
                btnDownloadDatasetZip.style.pointerEvents = 'auto';
            }
        } else {
            badgeSampleStatus.innerText = `Standby (${sampleCount} / 100)`;
            badgeSampleStatus.style.background = '#e0e7ff';
            badgeSampleStatus.style.color = '#3730a3';
            if (sampleCount > 0) {
                btnDownloadDatasetZip.style.opacity = '1';
                btnDownloadDatasetZip.style.pointerEvents = 'auto';
            } else {
                btnDownloadDatasetZip.style.opacity = '0.6';
                btnDownloadDatasetZip.style.pointerEvents = 'none';
            }
        }
    }

    function updateSamplingButtonState() {
        if (!btnToggleSampling) return;
        if (isSamplingActive) {
            btnToggleSampling.innerHTML = '⏸ Jeda Pengumpulan';
            btnToggleSampling.style.background = '#d97706';
        } else {
            btnToggleSampling.innerHTML = '▶ Mulai Pengumpulan Sampel';
            btnToggleSampling.style.background = '#4f46e5';
        }
        updateSampleUI();
    }

    function addThumbnailToPreview(dataUrl, num) {
        if (!samplePreviewContainer) return;
        if (sampleEmptyNotice) sampleEmptyNotice.style.display = 'none';

        const thumb = document.createElement('div');
        thumb.style.position = 'relative';
        thumb.style.width = '70px';
        thumb.style.height = '52px';
        thumb.style.flexShrink = '0';
        thumb.style.borderRadius = '6px';
        thumb.style.overflow = 'hidden';
        thumb.style.border = '1.5px solid #6366f1';
        thumb.style.boxShadow = '0 2px 4px rgba(0,0,0,0.15)';

        const img = document.createElement('img');
        img.src = dataUrl;
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';

        const numBadge = document.createElement('span');
        numBadge.innerText = '#' + num;
        numBadge.style.position = 'absolute';
        numBadge.style.bottom = '2px';
        numBadge.style.right = '2px';
        numBadge.style.background = 'rgba(15,23,42,0.8)';
        numBadge.style.color = '#ffffff';
        numBadge.style.fontSize = '9px';
        numBadge.style.padding = '1px 3px';
        numBadge.style.borderRadius = '3px';
        numBadge.style.fontFamily = 'monospace';

        thumb.appendChild(img);
        thumb.appendChild(numBadge);

        samplePreviewContainer.insertBefore(thumb, samplePreviewContainer.firstChild);

        while (samplePreviewContainer.children.length > 9) {
            samplePreviewContainer.removeChild(samplePreviewContainer.lastChild);
        }
    }

    async function captureRoboflowSample(source = 'Manual') {
        if (sampleCount >= maxSamples) {
            appendLog('✅ Koleksi 100 sampel Roboflow sudah lengkap! Silakan klik "Download Dataset (ZIP)".', '#16a34a');
            return;
        }

        try {
            let base64Data = '';
            if (streamImg && (streamImg.naturalWidth > 0 || streamImg.clientWidth > 0)) {
                const snapCanvas = document.createElement('canvas');
                snapCanvas.width = streamImg.naturalWidth || 640;
                snapCanvas.height = streamImg.naturalHeight || 480;
                const snapCtx = snapCanvas.getContext('2d');
                snapCtx.drawImage(streamImg, 0, 0, snapCanvas.width, snapCanvas.height);
                base64Data = snapCanvas.toDataURL('image/jpeg', 0.90);
            }

            if (!base64Data) {
                appendLog('⚠ Gagal mengambil sampel: Kamera belum siap / stream belum aktif.', '#dc2626');
                return;
            }

            const activePartId = activePart ? activePart.id : '';
            const res = await fetch('/api/save-roboflow-sample', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    image: base64Data,
                    part_id: activePartId
                })
            });
            const data = await res.json();

            if (data && data.status === 'ok') {
                sampleCount = data.count;
                updateSampleUI();
                addThumbnailToPreview(base64Data, sampleCount);
                appendLog(`📸 [Sampel #${sampleCount}/100] ${source}: Foto sampel disimpan untuk Part [${activePart ? activePart.code : 'Default'}]!`, '#4f46e5');

                if (sampleCount >= maxSamples) {
                    isSamplingActive = false;
                    updateSamplingButtonState();
                    alert('🎉 Target 100 Sampel Gambar untuk Training Roboflow berhasil dikumpulkan!\n\nKlik tombol "Download Dataset (ZIP)" untuk mengunduh semua file gambar yang siap di-upload ke Roboflow.');
                }
            } else if (data && data.status === 'full') {
                sampleCount = maxSamples;
                updateSampleUI();
            }
        } catch (e) {
            console.error('Error saat capture sample:', e);
        }
    }

    if (btnToggleSampling) {
        btnToggleSampling.addEventListener('click', () => {
            if (sampleCount >= maxSamples) {
                alert('Koleksi sudah 100 sampel. Jika ingin mengambil lagi dari awal, klik tombol Reset terlebih dahulu.');
                return;
            }
            isSamplingActive = !isSamplingActive;
            updateSamplingButtonState();
            if (isSamplingActive) {
                appendLog('🟢 Pengumpulan sampel Roboflow AKTIF. Kamera akan memotret otomatis tiap ada hanger di Sensor 2.', '#4f46e5');
            } else {
                appendLog('⏸ Pengumpulan sampel dijeda.', '#64748b');
            }
        });
    }

    if (btnCaptureManualSample) {
        btnCaptureManualSample.addEventListener('click', () => {
            captureRoboflowSample('Jepret Manual');
        });
    }

    if (btnResetSamples) {
        btnResetSamples.addEventListener('click', async () => {
            const pName = activePart ? activePart.name : 'part ini';
            if (!confirm(`Apakah kamu yakin ingin mereset dan menghapus seluruh sampel gambar untuk ${pName}?`)) return;
            try {
                await fetch('/api/reset-roboflow-samples', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ part_id: activePart ? activePart.id : '' })
                });
                sampleCount = 0;
                isSamplingActive = false;
                updateSamplingButtonState();
                if (samplePreviewContainer) {
                    samplePreviewContainer.innerHTML = '<div id="sample-empty-notice" style="font-size:11.5px; color:#94a3b8; font-style:italic;">Belum ada sampel foto yang diambil. Klik \'Mulai Pengumpulan\' atau \'Jepret Manual\'.</div>';
                }
                appendLog(`↺ Seluruh sampel foto Roboflow untuk ${pName} berhasil direset.`, '#dc2626');
            } catch (e) {}
        });
    }

    async function loadRoboflowSamplesStats(partId = '') {
        try {
            const targetPartId = partId || (activePart ? activePart.id : '');
            const url = targetPartId ? `/api/get-roboflow-samples?part_id=${encodeURIComponent(targetPartId)}` : '/api/get-roboflow-samples';
            const res = await fetch(url);
            const data = await res.json();
            if (data && data.status === 'ok') {
                sampleCount = data.count || 0;
                updateSampleUI();
            }
        } catch (e) {}
    }

    // ================================================================
    // SISTEM MANAJEMEN PART, CHANGEOVER & RETRAINING
    // ================================================================
    let partsCatalog = [];
    let activePart = null;

    // Elements Sesi & Part Aktif Header
    const activePartName = document.getElementById('active-part-name');
    const activePartCode = document.getElementById('active-part-code');
    const activePartModelInfo = document.getElementById('active-part-model-info');
    const badgeModelVersion = document.getElementById('badge-model-version');
    const labelKolektorPartName = document.getElementById('label-kolektor-part-name');

    // Elements Card 1 Part Profile
    const cardPartName = document.getElementById('card-part-name');
    const cardPartCode = document.getElementById('card-part-code');
    const cardModelSlug = document.getElementById('card-model-slug');
    const cardModelVersion = document.getElementById('card-model-version');
    const cardPartTarget = document.getElementById('card-part-target');

    // Modals
    const modalChangePart = document.getElementById('modal-change-part');
    const modalManageParts = document.getElementById('modal-manage-parts');
    const modalUpdateVersion = document.getElementById('modal-update-version');

    const selectChangePart = document.getElementById('select-change-part');
    const previewNewSlug = document.getElementById('preview-new-slug');
    const previewNewVersion = document.getElementById('preview-new-version');
    const previewNewTarget = document.getElementById('preview-new-target');

    async function loadPartsCatalog(reloadModel = true) {
        try {
            const res = await fetch('/api/get-parts');
            const data = await res.json();
            if (data && data.status === 'ok') {
                partsCatalog = data.parts || [];
                activePart = data.active_part || partsCatalog[0] || null;

                if (activePart) {
                    // Update Header Banner
                    if (activePartName) activePartName.innerText = activePart.name;
                    if (activePartCode) activePartCode.innerText = activePart.code;
                    if (activePartModelInfo) activePartModelInfo.innerText = `Roboflow: ${activePart.model_id}`;
                    if (badgeModelVersion) badgeModelVersion.innerText = `v${activePart.model_version}`;
                    if (labelKolektorPartName) labelKolektorPartName.innerText = `[${activePart.code}] ${activePart.name}`;

                    // Update Card 1 Profile
                    if (cardPartName) cardPartName.innerText = activePart.name;
                    if (cardPartCode) cardPartCode.innerText = activePart.code;
                    if (cardModelSlug) cardModelSlug.innerText = activePart.model_id;
                    if (cardModelVersion) cardModelVersion.innerText = `v${activePart.model_version}`;
                    if (cardPartTarget) cardPartTarget.innerText = activePart.target_per_hanger;

                    // Sync Manual Inputs
                    const inModel = document.getElementById('rf-model-input');
                    const inVer = document.getElementById('rf-version-input');
                    const inKey = document.getElementById('rf-key-input');
                    if (inModel) inModel.value = activePart.model_id;
                    if (inVer) inVer.value = activePart.model_version;
                    if (inKey) inKey.value = activePart.publishable_key;

                    // Update Download Dataset ZIP Link
                    const btnZip = document.getElementById('btn-download-dataset-zip');
                    if (btnZip) {
                        btnZip.href = `/api/download-roboflow-zip?part_id=${encodeURIComponent(activePart.id)}`;
                    }

                    // Reload samples count for this part
                    loadRoboflowSamplesStats(activePart.id);

                    // Auto load Roboflow model for active part
                    if (reloadModel && activePart.model_id && activePart.publishable_key) {
                        initRoboflowModel(activePart.model_id, activePart.model_version, activePart.publishable_key, true);
                    }
                } else {
                    // Tampilan ketika belum ada part yang didaftarkan di katalog
                    if (activePartName) activePartName.innerText = 'Belum Ada Part';
                    if (activePartCode) activePartCode.innerText = 'KOSONG';
                    if (activePartModelInfo) activePartModelInfo.innerText = 'Daftarkan Part di Katalog';
                    if (badgeModelVersion) badgeModelVersion.innerText = '-';
                    if (labelKolektorPartName) labelKolektorPartName.innerText = 'Belum Ada Part';

                    if (cardPartName) cardPartName.innerText = 'Belum Ada Part Terdaftar';
                    if (cardPartCode) cardPartCode.innerText = '-';
                    if (cardModelSlug) cardModelSlug.innerText = '-';
                    if (cardModelVersion) cardModelVersion.innerText = '-';
                    if (cardPartTarget) cardPartTarget.innerText = '-';

                    const inModel = document.getElementById('rf-model-input');
                    const inVer = document.getElementById('rf-version-input');
                    const inKey = document.getElementById('rf-key-input');
                    if (inModel) inModel.value = '';
                    if (inVer) inVer.value = 1;
                    if (inKey) inKey.value = '';

                    const badgeModel = document.getElementById('badge-ai-model-ready');
                    if (badgeModel) {
                        badgeModel.innerText = '🔴 Belum Ada Model';
                        badgeModel.style.background = '#fee2e2';
                        badgeModel.style.color = '#dc2626';
                    }
                }

                // Render table in manage parts modal
                renderPartsTable();
            }
        } catch (e) {
            console.error('Gagal memuat katalog part:', e);
        }
    }

    function renderPartsTable() {
        const tbody = document.getElementById('table-parts-body');
        if (!tbody) return;
        tbody.innerHTML = '';

        partsCatalog.forEach(p => {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #e2e8f0';
            const isActive = activePart && activePart.id === p.id;
            if (isActive) tr.style.background = '#f0f9ff';

            tr.innerHTML = `
                <td style="padding:8px 12px;">
                    <div style="font-weight:700; color:#0f172a;">${p.name}</div>
                    <span style="font-size:10.5px; font-weight:800; background:#e2e8f0; color:#475569; padding:1px 6px; border-radius:4px; font-family:monospace;">${p.code}</span>
                </td>
                <td style="padding:8px 12px; font-family:monospace; color:#0d3b8c; font-weight:600;">${p.model_id}</td>
                <td style="padding:8px 12px; text-align:center;">
                    <span style="font-size:11px; font-weight:800; background:#dbeafe; color:#1d4ed8; padding:2px 7px; border-radius:4px; font-family:monospace;">v${p.model_version}</span>
                </td>
                <td style="padding:8px 12px; text-align:center; font-weight:700; color:#16a34a;">${p.target_per_hanger} pcs</td>
                <td style="padding:8px 12px; text-align:center;">
                    ${isActive ? '<span style="font-size:11px; font-weight:700; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:12px;">● Aktif</span>' : '<span style="color:#94a3b8; font-size:11px;">Standby</span>'}
                </td>
                <td style="padding:8px 12px; text-align:center;">
                    <button type="button" class="btn-edit-part" data-id="${p.id}" style="background:none; border:none; color:#0284c7; cursor:pointer; font-weight:700; font-size:12px; margin-right:6px;" title="Edit Part">✏️</button>
                    ${!isActive ? `<button type="button" class="btn-delete-part" data-id="${p.id}" data-name="${p.name}" style="background:none; border:none; color:#ef4444; cursor:pointer; font-weight:700; font-size:12px;" title="Hapus Part">🗑️</button>` : ''}
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Event listener Edit
        tbody.querySelectorAll('.btn-edit-part').forEach(b => {
            b.addEventListener('click', () => {
                const id = b.dataset.id;
                const part = partsCatalog.find(item => item.id === id);
                if (part) {
                    document.getElementById('part-form-id').value = part.id;
                    document.getElementById('part-form-code').value = part.code;
                    document.getElementById('part-form-name').value = part.name;
                    document.getElementById('part-form-slug').value = part.model_id;
                    document.getElementById('part-form-version').value = part.model_version;
                    document.getElementById('part-form-target').value = part.target_per_hanger;
                    document.getElementById('part-form-key').value = part.publishable_key;
                    document.getElementById('form-part-title').innerText = `Edit Part: ${part.name}`;
                }
            });
        });

        // Event listener Delete
        tbody.querySelectorAll('.btn-delete-part').forEach(b => {
            b.addEventListener('click', async () => {
                const id = b.dataset.id;
                const name = b.dataset.name;
                if (!confirm(`Hapus part '${name}' dari katalog?`)) return;
                try {
                    await fetch('/api/delete-part', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });
                    loadPartsCatalog(false);
                } catch (e) {}
            });
        });
    }

    // Modal Changeover Triggers & Handlers
    function openChangeoverModal() {
        if (!modalChangePart) return;
        // Populate Summary
        const sumName = document.getElementById('modal-summary-part-name');
        const sumWip = document.getElementById('modal-summary-wip');
        const sumOut = document.getElementById('modal-summary-output');
        const sumParts = document.getElementById('modal-summary-parts');

        if (sumName && activePart) sumName.innerText = `[${activePart.code}] ${activePart.name}`;
        if (sumWip) sumWip.innerText = kpiWipHangers ? kpiWipHangers.innerText : '0';
        if (sumOut) sumOut.innerText = kpiOutputHangers ? kpiOutputHangers.innerText : '0';
        if (sumParts) sumParts.innerText = kpiTotalParts ? kpiTotalParts.innerText : '0';

        const wrapperSelect = document.getElementById('wrapper-select-change-part');
        const boxEmptyNotice = document.getElementById('box-empty-part-notice');
        const boxPreview = document.getElementById('box-new-part-preview');
        const btnConfirm = document.getElementById('btn-confirm-change-part');

        if (partsCatalog.length === 0) {
            if (wrapperSelect) wrapperSelect.style.display = 'none';
            if (boxPreview) boxPreview.style.display = 'none';
            if (boxEmptyNotice) boxEmptyNotice.style.display = 'block';
            if (btnConfirm) btnConfirm.style.display = 'none';
        } else {
            if (wrapperSelect) wrapperSelect.style.display = 'block';
            if (boxPreview) boxPreview.style.display = 'block';
            if (boxEmptyNotice) boxEmptyNotice.style.display = 'none';
            if (btnConfirm) btnConfirm.style.display = 'inline-flex';

            // Populate Select hanya dengan part yang sudah terdaftar/ditraining
            if (selectChangePart) {
                selectChangePart.innerHTML = '';
                partsCatalog.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.innerText = `[${p.code}] ${p.name}`;
                    if (activePart && activePart.id === p.id) {
                        opt.innerText += ' (Sedang Berjalan)';
                    }
                    selectChangePart.appendChild(opt);
                });
                updateNewPartPreview();
            }
        }

        modalChangePart.style.display = 'flex';
    }

    function updateNewPartPreview() {
        if (!selectChangePart) return;
        const selectedId = selectChangePart.value;
        const part = partsCatalog.find(p => p.id === selectedId);
        if (part) {
            if (previewNewSlug) previewNewSlug.innerText = part.model_id;
            if (previewNewVersion) previewNewVersion.innerText = `v${part.model_version}`;
            if (previewNewTarget) previewNewTarget.innerText = part.target_per_hanger;
        }
    }

    if (selectChangePart) {
        selectChangePart.addEventListener('change', updateNewPartPreview);
    }

    const btnOpenChangePart = document.getElementById('btn-open-change-part');
    const btnQuickChangePart = document.getElementById('btn-quick-change-part');
    if (btnOpenChangePart) btnOpenChangePart.addEventListener('click', openChangeoverModal);
    if (btnQuickChangePart) btnQuickChangePart.addEventListener('click', openChangeoverModal);

    const btnCloseChangeX = document.getElementById('btn-close-modal-change-x');
    const btnCancelChange = document.getElementById('btn-cancel-change-part');
    if (btnCloseChangeX) btnCloseChangeX.addEventListener('click', () => modalChangePart.style.display = 'none');
    if (btnCancelChange) btnCancelChange.addEventListener('click', () => modalChangePart.style.display = 'none');

    const btnGotoManage = document.getElementById('btn-goto-manage-parts');
    if (btnGotoManage) {
        btnGotoManage.addEventListener('click', () => {
            modalChangePart.style.display = 'none';
            renderPartsTable();
            if (modalManageParts) modalManageParts.style.display = 'flex';
        });
    }

    const btnConfirmChangePart = document.getElementById('btn-confirm-change-part');
    if (btnConfirmChangePart) {
        btnConfirmChangePart.addEventListener('click', async () => {
            const newPartId = selectChangePart.value;
            if (!newPartId) return;

            btnConfirmChangePart.disabled = true;
            btnConfirmChangePart.innerText = 'Mengganti Part...';

            try {
                const res = await fetch('/api/change-part', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ part_id: newPartId })
                });
                const data = await res.json();
                if (data && data.status === 'ok') {
                    modalChangePart.style.display = 'none';
                    appendLog(`🔄 CHANGEOVER: Part produksi diganti ke [${data.active_part.code}] ${data.active_part.name}. Counter direset!`, '#0d3b8c');
                    await loadHangerStats();
                    await loadPartsCatalog(true);
                    alert(`✅ Pergantian part berhasil!\n\nPart Aktif: ${data.active_part.name}\nModel Roboflow v${data.active_part.model_version} siap digunakan.`);
                }
            } catch (e) {
                alert('Gagal mengganti part: ' + e.message);
            } finally {
                btnConfirmChangePart.disabled = false;
                btnConfirmChangePart.innerHTML = '✓ Konfirmasi & Mulai Sesi Part Ini';
            }
        });
    }

    // Modal Manage Parts Handlers
    const btnOpenManageParts = document.getElementById('btn-open-manage-parts');
    if (btnOpenManageParts) {
        btnOpenManageParts.addEventListener('click', () => {
            renderPartsTable();
            if (modalManageParts) modalManageParts.style.display = 'flex';
        });
    }
    const btnCloseManagePartsX = document.getElementById('btn-close-manage-parts-x');
    const btnCloseManageParts = document.getElementById('btn-close-manage-parts');
    if (btnCloseManagePartsX) btnCloseManagePartsX.addEventListener('click', () => modalManageParts.style.display = 'none');
    if (btnCloseManageParts) btnCloseManageParts.addEventListener('click', () => modalManageParts.style.display = 'none');

    const btnResetPartForm = document.getElementById('btn-reset-part-form');
    if (btnResetPartForm) {
        btnResetPartForm.addEventListener('click', () => {
            document.getElementById('part-form-id').value = '';
            document.getElementById('part-form-code').value = '';
            document.getElementById('part-form-name').value = '';
            document.getElementById('part-form-slug').value = '';
            document.getElementById('part-form-version').value = '1';
            document.getElementById('part-form-target').value = '4';
            document.getElementById('part-form-key').value = '';
            document.getElementById('form-part-title').innerText = 'Tambah Part Baru ke Katalog';
        });
    }

    const btnSubmitPartForm = document.getElementById('btn-submit-part-form');
    if (btnSubmitPartForm) {
        btnSubmitPartForm.addEventListener('click', async () => {
            const id = document.getElementById('part-form-id').value;
            const code = document.getElementById('part-form-code').value.trim();
            const name = document.getElementById('part-form-name').value.trim();
            const model_id = document.getElementById('part-form-slug').value.trim();
            const model_version = parseInt(document.getElementById('part-form-version').value, 10) || 1;
            const target_per_hanger = parseInt(document.getElementById('part-form-target').value, 10) || 4;
            const publishable_key = document.getElementById('part-form-key').value.trim();

            if (!code || !name || !model_id || !publishable_key) {
                alert('Semua field wajib diisi!');
                return;
            }

            try {
                const res = await fetch('/api/save-part', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, code, name, model_id, model_version, target_per_hanger, publishable_key })
                });
                if (!res.ok) {
                    const errData = await res.json().catch(() => null);
                    throw new Error((errData && errData.message) ? errData.message : `HTTP ${res.status}`);
                }
                const data = await res.json();
                if (data && data.status === 'ok') {
                    btnResetPartForm.click();
                    await loadPartsCatalog(false);
                    appendLog(`💾 Part [${code}] ${name} berhasil disimpan ke katalog.`, '#16a34a');
                    alert(`✅ Part '${name}' [${code}] berhasil disimpan ke katalog!`);
                }
            } catch (e) {
                alert('Gagal menyimpan part: ' + e.message);
            }
        });
    }

    // Modal Update Versi Model Handlers
    function openUpdateVersionModal() {
        if (!modalUpdateVersion || !activePart) return;
        document.getElementById('ver-modal-part-name').innerText = `[${activePart.code}] ${activePart.name}`;
        document.getElementById('ver-modal-slug').innerText = activePart.model_id;
        document.getElementById('ver-modal-current').innerText = `v${activePart.model_version}`;
        document.getElementById('input-new-model-version').value = (parseInt(activePart.model_version, 10) || 1) + 1;
        modalUpdateVersion.style.display = 'flex';
    }

    const btnOpenUpdateVer = document.getElementById('btn-open-update-version');
    const btnQuickUpdateVer = document.getElementById('btn-quick-update-ver');
    if (btnOpenUpdateVer) btnOpenUpdateVer.addEventListener('click', openUpdateVersionModal);
    if (btnQuickUpdateVer) btnQuickUpdateVer.addEventListener('click', openUpdateVersionModal);

    const btnCloseUpdateVerX = document.getElementById('btn-close-update-version-x');
    const btnCancelUpdateVer = document.getElementById('btn-cancel-update-ver');
    if (btnCloseUpdateVerX) btnCloseUpdateVerX.addEventListener('click', () => modalUpdateVersion.style.display = 'none');
    if (btnCancelUpdateVer) btnCancelUpdateVer.addEventListener('click', () => modalUpdateVersion.style.display = 'none');

    const btnSaveNewVersion = document.getElementById('btn-save-new-version');
    if (btnSaveNewVersion) {
        btnSaveNewVersion.addEventListener('click', async () => {
            if (!activePart) return;
            const newVer = parseInt(document.getElementById('input-new-model-version').value, 10);
            if (!newVer || newVer < 1) {
                alert('Nomor versi harus angka bulat >= 1');
                return;
            }

            btnSaveNewVersion.disabled = true;
            try {
                const res = await fetch('/api/update-part-model-version', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ part_id: activePart.id, new_version: newVer })
                });
                const data = await res.json();
                if (data && data.status === 'ok') {
                    modalUpdateVersion.style.display = 'none';
                    appendLog(`🆙 Model part [${activePart.name}] diupdate ke Versi v${newVer}! Me-reload model...`, '#0284c7');
                    await loadPartsCatalog(true);
                    alert(`🎉 Berhasil update versi model ke v${newVer}!\n\nModel Roboflow baru telah otomatis dimuat.`);
                }
            } catch (e) {
                alert('Gagal update versi model: ' + e.message);
            } finally {
                btnSaveNewVersion.disabled = false;
            }
        });
    }

    // Panggil Inisialisasi Katalog Part saat Halaman Dimuat
    loadPartsCatalog(true);

    // 5. LISTENER AUTO-TRIGGER RS-485 (Polling Real-Time dari Backend)
    async function pollSensorTrigger() {
        if (!chkAutoTrigger.checked) return;

        try {
            const res = await fetch('/api/get-sensor-trigger');
            const data = await res.json();
            const trig = data ? data.trigger : null;

            if (trig && trig.trigger_id && trig.trigger_id !== lastProcessedTriggerId) {
                lastProcessedTriggerId = trig.trigger_id;

                // Event Tombol Hardware PB ON (Start)
                if (trig.type === 'btn_on') {
                    updateMesinUI(true);
                    appendLog('🚀 [Hardware PB ON] Tombol START Ditekan: Status Produksi Aktif (Nilai 1). Photosensor kini aktif!', '#16a34a');
                }
                // Event Tombol Hardware PB OFF (Stop)
                else if (trig.type === 'btn_off') {
                    updateMesinUI(false);
                    appendLog('🛑 [Hardware PB OFF] Tombol STOP Ditekan: Status Produksi Dihentikan (Nilai 0). Photosensor standby.', '#dc2626');
                }
                // Event Photosensor 1 (Inlet / Hanger Masuk) - Standard 2 Warna: Hijau (Trigger) & Merah (Standby)
                else if (trig.type === 'input') {
                    dotSensorInput.style.background = '#22c55e';
                    dotSensorInput.style.boxShadow = '0 0 12px #22c55e';
                    textSensorInput.innerText = 'TRIGGER DETECTED!';
                    textSensorInput.style.color = '#16a34a';

                    appendLog('🚪 Photosensor 1 (Input) menandai Hanger Baru Masuk ke Line (Nilai 1).', '#16a34a');
                    loadHangerStats();

                    setTimeout(() => {
                        dotSensorInput.style.background = '#ef4444';
                        dotSensorInput.style.boxShadow = '0 0 8px rgba(239,68,68,0.5)';
                        textSensorInput.innerText = 'Standby';
                        textSensorInput.style.color = '#dc2626';
                    }, 1200);

                }
                // Event Photosensor 2 (Outlet / Shutter Kamera) - Standard 2 Warna: Hijau (Trigger) & Merah (Standby)
                else if (trig.type === 'output' || trig.type === 'output_trigger') {
                    dotSensorOutput.style.background = '#22c55e';
                    dotSensorOutput.style.boxShadow = '0 0 12px #22c55e';
                    textSensorOutput.innerText = 'SHUTTER TRIGGER!';
                    textSensorOutput.style.color = '#16a34a';

                    // Efek Rana / Flash Kamera di Layar (Border Hijau)
                    videoContainer.style.borderColor = '#22c55e';
                    shutterFlash.style.display = 'block';
                    setTimeout(() => {
                        shutterFlash.style.display = 'none';
                        videoContainer.style.borderColor = '#1e293b';
                    }, 200);

                    // 1. Jalankan deteksi konfirmasi otomatis Roboflow
                    await runObjectDetection(true);

                    // 2. Jika mode sampling aktif & auto-capture aktif: ambil 1 sampel training!
                    if (isSamplingActive && chkAutoSampleOnHanger && chkAutoSampleOnHanger.checked) {
                        await captureRoboflowSample('Sensor 2 Auto');
                    }

                    setTimeout(() => {
                        dotSensorOutput.style.background = '#ef4444';
                        dotSensorOutput.style.boxShadow = '0 0 8px rgba(239,68,68,0.5)';
                        textSensorOutput.innerText = 'Standby';
                        textSensorOutput.style.color = '#dc2626';
                    }, 1200);
                }
            }
        } catch (e) {}
    }

    // Jalankan polling trigger sensor setiap 250ms
    triggerPollInterval = setInterval(pollSensorTrigger, 250);

    // 6. EVENT TOMBOL SIMULASI (Testing tanpa conveyor fisik)
    const btnSimOn = document.getElementById('btn-sim-on');
    if (btnSimOn) {
        btnSimOn.addEventListener('click', async () => {
            try {
                await fetch('/api/sensor-trigger?type=btn_on&val=1');
            } catch (e) {}
        });
    }

    const btnSimOff = document.getElementById('btn-sim-off');
    if (btnSimOff) {
        btnSimOff.addEventListener('click', async () => {
            try {
                await fetch('/api/sensor-trigger?type=btn_off&val=0');
            } catch (e) {}
        });
    }

    document.getElementById('btn-sim-input').addEventListener('click', async () => {
        try {
            await fetch('/api/sensor-trigger?type=input&val=1');
        } catch (e) {}
    });

    document.getElementById('btn-sim-output').addEventListener('click', async () => {
        try {
            await fetch('/api/sensor-trigger?type=output&val=1');
        } catch (e) {}
    });
</script>
