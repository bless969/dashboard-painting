@php
    $statsFileAi = storage_path('app/hanger_stats.json');
    $initialStatsAi = file_exists($statsFileAi) ? (@json_decode(file_get_contents($statsFileAi), true) ?: []) : [];
    $isMesinActiveAi = !empty($initialStatsAi['is_produksi']);
@endphp
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
                        <div style="font-size:12.5px; text-transform:uppercase; letter-spacing:0.8px; color:#a5b4fc; font-weight:700; display:flex; align-items:center; gap:6px;">
                            <span>✨</span> Deteksi Part Otomatis (Vision AI)
                        </div>
                        <div style="display:flex; align-items:center; gap:12px; margin-top:3px;">
                            <span id="active-part-name" style="font-size:22px; font-weight:800; color:#ffffff; letter-spacing:-0.2px;">Part Otomatis</span>
                            <span id="active-part-code" style="background:#4f46e5; color:#e0e7ff; font-size:13px; font-weight:700; padding:3px 10px; border-radius:6px; font-family:monospace;">AUTO</span>
                        </div>
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                    <!-- Status Mode Otomatis -->
                    <div style="background:rgba(34,197,94,0.18); padding:8px 14px; border-radius:8px; border:1px solid rgba(34,197,94,0.4); font-size:13px; color:#4ade80; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                        <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#22c55e; box-shadow:0 0 8px #22c55e;"></span>
                        Auto-Recognition Active
                    </div>

                    <!-- Info Model Roboflow yang sedang aktif -->
                    <div style="background:rgba(255,255,255,0.1); padding:8px 14px; border-radius:8px; border:1px solid rgba(255,255,255,0.2); font-size:13.5px;">
                        <span style="color:#c7d2fe;">Model AI:</span>
                        <strong id="active-part-model-info" style="color:#ffffff; margin-left:6px; font-family:monospace;">-</strong>
                        <span id="badge-model-version" style="background:#22c55e; color:#ffffff; font-size:12px; font-weight:700; padding:2px 8px; border-radius:5px; margin-left:6px;">v1</span>
                    </div>

                    <!-- Operator PIC Shift Aktif -->
                    <div onclick="bukaModalGantiShift()" style="background:rgba(255,255,255,0.12); padding:8px 14px; border-radius:8px; border:1px solid rgba(255,255,255,0.25); font-size:13px; color:#ffffff; font-weight:700; display:inline-flex; align-items:center; gap:6px; cursor:pointer;" title="Klik untuk Ganti Operator Shift">
                        <span>👤</span>
                        <span>PIC: <strong id="label-ai-operator" style="color:#fde047;">Mukhamad Nawwaf</strong></span>
                    </div>

                    <!-- Tombol Kelola Part -->
                    <button type="button" id="btn-open-manage-parts" style="display:inline-flex; align-items:center; gap:8px; padding:9px 15px; background:rgba(255,255,255,0.15); color:white; border:1px solid rgba(255,255,255,0.3); border-radius:8px; font-weight:700; font-size:13px; cursor:pointer;" title="Kelola Data Spesifikasi & SUT Part">
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
                        <span id="dot-mesin-status" style="width:12px; height:12px; border-radius:50%; background:{{ $isMesinActiveAi ? '#22c55e' : '#ef4444' }}; box-shadow:0 0 {{ $isMesinActiveAi ? '12px #22c55e' : '8px rgba(239,68,68,0.5)' }}; display:inline-block; transition:all 0.3s;"></span>
                        <span style="font-size:13px; font-weight:700; color:#334155;">Status Mesin:</span>
                        <span id="status-mesin-text" style="font-size:13px; font-weight:800; color:{{ $isMesinActiveAi ? '#16a34a' : '#dc2626' }}; font-family:monospace;">{{ $isMesinActiveAi ? 'PRODUKSI AKTIF (Nilai 1)' : 'STANDBY (Nilai 0)' }}</span>
                    </div>

                    <!-- Indikator Sensor 1 (Input Box - Pin 27) - Standar 2 Warna: MERAH (Standby) & HIJAU (Trigger) -->
                    <div style="display:flex; align-items:center; gap:8px; background:#ffffff; border:1px solid #cbd5e1; padding:7px 14px; border-radius:8px;">
                        <span id="dot-sensor-input" style="width:12px; height:12px; border-radius:50%; background:#ef4444; box-shadow:0 0 8px rgba(239,68,68,0.5); display:inline-block; transition:all 0.3s;"></span>
                        <span style="font-size:13px; font-weight:700; color:#334155;">Sensor Masuk (Pin 27):</span>
                        <span id="status-sensor-input-text" style="font-size:13px; font-weight:800; color:#dc2626; font-family:monospace;">Standby</span>
                    </div>

                    <!-- Indikator Sensor 2 (Output Box - Pin 25) - Standar 2 Warna: MERAH (Standby) & HIJAU (Trigger) -->
                    <div style="display:flex; align-items:center; gap:8px; background:#ffffff; border:1px solid #cbd5e1; padding:7px 14px; border-radius:8px;">
                        <span id="dot-sensor-output" style="width:12px; height:12px; border-radius:50%; background:#ef4444; box-shadow:0 0 8px rgba(239,68,68,0.5); display:inline-block; transition:all 0.3s;"></span>
                        <span style="font-size:13px; font-weight:700; color:#334155;">Sensor Keluar (Pin 25):</span>
                        <span id="status-sensor-output-text" style="font-size:13px; font-weight:800; color:#dc2626; font-family:monospace;">Standby</span>
                    </div>

                    <!-- Tombol Simulasi Sensor Manual (Dev & Uji Coba) -->
                    <div style="display:flex; align-items:center; gap:6px;">
                        <button type="button" onclick="fetch('/api/sensor-trigger?type=input').catch(()=>{})" title="Simulasi Hanger Masuk Box (Pin 27: Mulai Tracking)" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-size:11.5px; font-weight:700; padding:6px 10px; border-radius:6px; cursor:pointer; display:flex; align-items:center; gap:4px; transition:all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            🚪 Test In (Pin 27)
                        </button>
                        <button type="button" onclick="fetch('/api/sensor-trigger?type=output').catch(()=>{})" title="Simulasi Hanger Keluar Box (Pin 25: Finalisasi Hasil)" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-size:11.5px; font-weight:700; padding:6px 10px; border-radius:6px; cursor:pointer; display:flex; align-items:center; gap:4px; transition:all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            📸 Test Out (Pin 25)
                        </button>
                    </div>
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
                        <div style="position:absolute; top:10px; left:10px; display:flex; gap:6px; z-index:5; flex-wrap:wrap;">
                            <span id="ai-stream-badge" style="background:rgba(15,23,42,0.88); color:#4ade80; border:1px solid rgba(74,222,128,0.3); font-size:12px; font-weight:700; padding:5px 11px; border-radius:6px; font-family:ui-monospace, monospace; backdrop-filter:blur(4px); display:flex; align-items:center; gap:6px;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#22c55e; animation:lampKedip 1.2s infinite;"></span>
                                LIVE OUTPUT CAM
                            </span>
                            <span id="ai-inference-time-badge" style="background:rgba(15,23,42,0.88); color:#38bdf8; border:1px solid rgba(56,189,248,0.3); font-size:12px; font-weight:700; padding:5px 11px; border-radius:6px; font-family:ui-monospace, monospace; backdrop-filter:blur(4px); display:none;">
                                0 ms
                            </span>
                            <!-- Badge Gated Inspection Window (Pin 27 Start -> Pin 25 Finish) -->
                            <span id="ai-inspection-badge" style="background:rgba(16,185,129,0.92); color:#ffffff; border:1px solid rgba(255,255,255,0.35); font-size:12px; font-weight:800; padding:5px 12px; border-radius:6px; font-family:ui-monospace, monospace; backdrop-filter:blur(4px); display:none; align-items:center; gap:6px; box-shadow:0 0 12px rgba(16,185,129,0.5);">
                                <span style="width:8px; height:8px; border-radius:50%; background:#ffffff; animation:lampKedip 0.8s infinite;"></span>
                                <span id="ai-inspection-badge-text">🔍 INSPEKSI AKTIF: 0 PART</span>
                            </span>
                        </div>

                        <!-- Flash Shutter Banner saat Sensor Output Trigger -->
                        <div id="ai-shutter-flash" style="display:none; position:absolute; inset:0; background:rgba(255,255,255,0.35); pointer-events:none; z-index:10;"></div>
                    </div>
                </div>

                <!-- 4 KPI Box: Live Conveyor & Hanger Flow -->
                <!-- 4 KPI Box: Live Conveyor & Hanger Flow (Selaras, Proporsional & Elegan) -->
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:12px;">
                    
                    <!-- Kartu 1: WIP Hanger (Sedang di dalam line oven/booth) -->
                    <div style="background:#ffffff; border:1.5px solid #e2e8f0; border-top:3.5px solid #f59e0b; border-radius:12px; padding:14px 16px; box-shadow:0 2px 8px rgba(15,23,42,0.04); display:flex; flex-direction:column; justify-content:space-between; min-height:122px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-size:11.5px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Hanger di Line (WIP)</span>
                            <span style="font-size:10.5px; font-weight:700; background:#fef3c7; color:#b45309; padding:2px 7px; border-radius:4px;">In-Line</span>
                        </div>
                        <div style="display:flex; align-items:baseline; gap:6px; margin:2px 0;">
                            <span id="kpi-wip-hangers" style="font-size:32px; font-weight:900; color:#f59e0b; line-height:1;">{{ $initialStatsAi['wip_hangers'] ?? 0 }}</span>
                            <span style="font-size:13px; font-weight:700; color:#64748b;">Hanger</span>
                        </div>
                        <div>
                            <span style="font-size:11px; color:#b45309; font-weight:700; display:inline-flex; align-items:center; gap:5px; background:#fffbeb; padding:2px 8px; border-radius:6px; border:1px solid #fde68a;">
                                <span style="width:6px; height:6px; border-radius:50%; background:#f59e0b;"></span>
                                Marked Sensor 1
                            </span>
                        </div>
                    </div>

                    <!-- Kartu 2: Part di Hanger Output Saat Ini -->
                    <div style="background:#ffffff; border:1.5px solid #e2e8f0; border-top:3.5px solid #0284c7; border-radius:12px; padding:14px 16px; box-shadow:0 2px 8px rgba(15,23,42,0.04); display:flex; flex-direction:column; justify-content:space-between; min-height:122px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-size:11.5px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Part di Hanger Ini</span>
                            <span style="font-size:10.5px; font-weight:700; background:#e0f2fe; color:#0369a1; padding:2px 7px; border-radius:4px;">Kamera</span>
                        </div>
                        <div style="display:flex; align-items:baseline; gap:6px; margin:2px 0;">
                            <span id="kpi-current-parts" style="font-size:32px; font-weight:900; color:#0284c7; line-height:1;">0</span>
                            <span style="font-size:13px; font-weight:700; color:#64748b;">Pcs</span>
                        </div>
                        <div>
                            <span style="font-size:11px; color:#0369a1; font-weight:700; display:inline-flex; align-items:center; gap:5px; background:#f0f9ff; padding:2px 8px; border-radius:6px; border:1px solid #bae6fd;">
                                <span style="width:6px; height:6px; border-radius:50%; background:#0284c7;"></span>
                                Deteksi Kamera Output
                            </span>
                        </div>
                    </div>

                    <!-- Kartu 3: Total Hanger Selesai (Output) -->
                    <div style="background:#ffffff; border:1.5px solid #e2e8f0; border-top:3.5px solid #0d9488; border-radius:12px; padding:14px 16px; box-shadow:0 2px 8px rgba(15,23,42,0.04); display:flex; flex-direction:column; justify-content:space-between; min-height:122px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-size:11.5px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Hanger Selesai</span>
                            <span style="font-size:10.5px; font-weight:700; background:#ccfbf1; color:#0f766e; padding:2px 7px; border-radius:4px;">Output</span>
                        </div>
                        <div style="display:flex; align-items:baseline; gap:6px; margin:2px 0;">
                            <span id="kpi-output-hangers" style="font-size:32px; font-weight:900; color:#0d9488; line-height:1;">{{ $initialStatsAi['total_output_hangers'] ?? 0 }}</span>
                            <span style="font-size:13px; font-weight:700; color:#64748b;">Hanger</span>
                        </div>
                        <div>
                            <span style="font-size:11px; color:#0f766e; font-weight:700; display:inline-flex; align-items:center; gap:5px; background:#f0fdfa; padding:2px 8px; border-radius:6px; border:1px solid #99f6e4;">
                                <span style="width:6px; height:6px; border-radius:50%; background:#0d9488;"></span>
                                Terkonfirmasi Sensor 2
                            </span>
                        </div>
                    </div>

                    <!-- Kartu 4: Total Output Aktual Terkonfirmasi (PCS) -->
                    <div style="background:#ffffff; border:1.5px solid #e2e8f0; border-top:3.5px solid #16a34a; border-radius:12px; padding:14px 16px; box-shadow:0 2px 8px rgba(15,23,42,0.04); display:flex; flex-direction:column; justify-content:space-between; min-height:122px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-size:11.5px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Total Output</span>
                            <button type="button" id="btn-reset-counter" style="background:#fee2e2; border:1px solid #fca5a5; color:#dc2626; font-size:11px; cursor:pointer; font-weight:800; padding:2px 8px; border-radius:5px; transition:all 0.15s; display:inline-flex; align-items:center; gap:3px;" onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">
                                ↺ Reset
                            </button>
                        </div>
                        <div style="display:flex; align-items:baseline; gap:6px; margin:2px 0;">
                            <span id="kpi-total-parts" style="font-size:32px; font-weight:900; color:#16a34a; line-height:1;">{{ $initialStatsAi['total_output_pcs'] ?? 0 }}</span>
                            <span style="font-size:13px; font-weight:700; color:#64748b;">Pcs</span>
                        </div>
                        <div>
                            <span style="font-size:11px; color:#15803d; font-weight:700; display:inline-flex; align-items:center; gap:5px; background:#f0fdf4; padding:2px 8px; border-radius:6px; border:1px solid #bbf7d0;">
                                <span style="width:6px; height:6px; border-radius:50%; background:#16a34a;"></span>
                                Aktual Roboflow AI
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Info Log Alur Kerja Konveyor -->
                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:13px 16px; font-size:13.5px; color:#1e40af; line-height:1.5;">
                    💡 <strong>Alur Gated Inspection Window:</strong> Saat <strong>Sensor Masuk (Pin 27)</strong> mendeteksi hanger masuk ke dalam box, sistem membuka sesi inspeksi dan AI ByteTrack otomatis melacak & mengakumulasikan setiap part unik yang terlihat. Begitu hanger memicu <strong>Sensor Keluar (Pin 25)</strong>, sesi inspeksi selesai, total part unik otomatis dikonfirmasi ke <em>Total Output</em>, dan counter di-reset bersih untuk hanger berikutnya!
                </div>

                <!-- CARD KOLEKTOR DATASET ROBOFLOW (UNLIMITED & CEPAT) -->
                <div style="background:#ffffff; border:1.5px solid #c7d2fe; border-radius:12px; padding:18px; box-shadow:0 2px 8px rgba(99,102,241,0.06);">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; border-bottom:1px solid #e0e7ff; padding-bottom:12px; flex-wrap:wrap; gap:8px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size:24px;">📸</span>
                            <div>
                                <h4 style="margin:0; font-size:16px; color:#3730a3; font-weight:800;">Kolektor Dataset Foto Part</h4>
                                <div style="font-size:13px; color:#4338ca; font-weight:700; margin-top:2px;">
                                    Part Sasaran: <span id="label-kolektor-part-name" style="background:#e0e7ff; padding:2px 10px; border-radius:5px; font-family:monospace; color:#3730a3; font-size:13px;">Memuat...</span>
                                </div>
                            </div>
                        </div>
                        <span id="badge-sample-status" style="font-size:12.5px; font-weight:700; background:#e0e7ff; color:#3730a3; padding:5px 12px; border-radius:6px; font-family:ui-monospace,monospace;">
                            Standby (0 Foto Terkumpul)
                        </span>
                    </div>

                    <!-- Indikator Total Sampel Terkumpul (Bebas & Dinamis) -->
                    <div style="background:#f8fafc; border:1.5px solid #e0e7ff; border-radius:10px; padding:14px 18px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <div>
                            <div style="font-size:12px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Total Sampel Tersimpan:</div>
                            <div style="display:flex; align-items:baseline; gap:8px; margin-top:2px;">
                                <span id="label-sample-progress" style="color:#4f46e5; font-family:monospace; font-weight:900; font-size:28px;">0</span>
                                <span style="color:#334155; font-weight:800; font-size:14.5px;">Foto Sampel</span>
                            </div>
                        </div>
                        <div style="font-size:12.5px; color:#475569; text-align:right; max-width:340px; line-height:1.45;">
                            ⚡ <strong>Jumlah Sampel Bebas:</strong> Tidak dibatasi 100 foto. Klik <strong>Mulai Ambil Sampel</strong> untuk memotret beruntun super cepat, dan klik <strong>Berhenti</strong> kapan pun Anda mau.
                        </div>
                    </div>

                    <!-- Kontrol Pengambilan Sampel Dataset -->
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:10px;">
                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <button type="button" id="btn-toggle-sampling" style="display:inline-flex; align-items:center; gap:8px; padding:9px 20px; background:#4f46e5; color:white; border:none; border-radius:8px; font-weight:800; font-size:14px; cursor:pointer; transition:all 0.25s; box-shadow:0 2px 6px rgba(79,70,229,0.3);">
                                ▶ Mulai Ambil Sampel
                            </button>
                            <div style="display:inline-flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:6px 10px;">
                                <span style="font-size:12px; font-weight:700; color:#475569;">⏱️ Jeda:</span>
                                <select id="select-sampling-speed" style="font-size:12.5px; font-weight:700; color:#1e293b; background:transparent; border:none; outline:none; cursor:pointer;">
                                    <option value="500" selected>0.5 detik (Pas &amp; Rekomendasi)</option>
                                    <option value="350">0.35 detik (Sedikit Lebih Cepat)</option>
                                    <option value="750">0.75 detik (Santai)</option>
                                    <option value="1000">1.0 detik (Pelan / 1 foto per detik)</option>
                                    <option value="150">0.15 detik (Kilat / Sangat Cepat)</option>
                                </select>
                            </div>
                            <button type="button" id="btn-capture-manual-sample" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#f8fafc; color:#1e293b; border:1px solid #cbd5e1; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer;" title="Ambil 1 foto secara manual">
                                📷 1x Jepret Manual
                            </button>
                        </div>

                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
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

                    <div style="font-size:12px; color:#4338ca; background:#eef2ff; border:1px solid #c7d2fe; border-radius:6px; padding:6px 10px; margin-bottom:14px; display:flex; align-items:center; gap:6px;">
                        <span>⚡</span>
                        <span><strong>Pengambilan Foto Mandiri:</strong> Kamera langsung memotret terus-menerus secara cepat tanpa menunggu sensor, trigger hardware, ataupun konveyor.</span>
                    </div>

                    <!-- Filmstrip Preview Sampel Terbaru -->
                    <div>
                        <div style="font-size:12.5px; font-weight:700; color:#475569; margin-bottom:6px;">Preview Sampel Terakhir:</div>
                        <div id="sample-preview-container" style="display:flex; gap:8px; overflow-x:auto; padding:6px 0; min-height:72px; align-items:center;">
                            <div id="sample-empty-notice" style="font-size:12.5px; color:#94a3b8; font-style:italic;">Belum ada sampel foto yang diambil. Klik 'Mulai Ambil Sampel' untuk mengambil foto beruntun.</div>
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

                    <!-- Diagnostik Error Model Roboflow (Otomatis muncul jika gagal) -->
                    <div id="ai-model-error-hint" style="display:none; margin-bottom:14px; padding:12px 14px; background:#fef2f2; border:1.5px solid #fecaca; border-radius:8px; font-size:12.5px; color:#991b1b;">
                        <div style="font-weight:800; display:flex; align-items:center; gap:6px; margin-bottom:6px; font-size:13px;">
                            <span>⚠️</span> <span id="ai-model-error-title">Gagal Memuat Model Roboflow</span>
                        </div>
                        <div id="ai-model-error-msg" style="font-family:monospace; font-size:11.5px; background:#ffffff; color:#b91c1c; padding:8px 10px; border-radius:6px; border:1px solid #fee2e2; margin-bottom:8px; word-break:break-word; line-height:1.4;">
                            -
                        </div>
                        <div style="font-size:11.5px; color:#7f1d1d; line-height:1.45;">
                            💡 <strong>Solusi:</strong> Klik <strong>⚙️ Pengaturan Manual (Advanced / Override)</strong> di bawah atau tombol <strong>⚙️ Katalog Part</strong>, lalu sesuaikan <strong>Model Slug</strong>, <strong>Versi</strong>, dan <strong>Publishable API Key</strong> dari akun Roboflow Anda.
                        </div>
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
                            <button type="button" id="btn-quick-manage-parts" style="flex:1; padding:8px 12px; background:#f1f5f9; color:#0d3b8c; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-weight:700; cursor:pointer;" title="Lihat & Kelola Spesifikasi Part di Katalog">
                                ⚙️ Katalog Part
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
                                <input type="text" id="rf-model-input" class="filter-select" style="width:100%; padding:8px 10px; font-size:12.5px;" placeholder="contoh: hanger-object-detection">
                                <div style="font-size:11px; color:#64748b; margin-top:3px;">* Tanpa spasi (gunakan tanda strip <code style="background:#e2e8f0; padding:1px 4px; border-radius:3px;">-</code>). Contoh: <b>hanger-object-detection</b></div>
                            </div>

                            <div style="margin-bottom:10px;">
                                <label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:3px;">Versi Model:</label>
                                <input type="number" id="rf-version-input" class="filter-select" style="width:100%; padding:8px 10px; font-size:12.5px;" value="1" min="1">
                                <div style="font-size:11px; color:#64748b; margin-top:3px;">* Sesuai nomor versi training Roboflow (misal: <b>2</b>).</div>
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

                    <!-- Tombol Kontrol Deteksi AI (Default Otomatis Aktif) -->
                    <button type="button" id="btn-start-ai" style="padding:13px 18px; width:100%; text-align:center; background:#16a34a; color:white; border:none; border-radius:8px; font-weight:800; font-size:14.5px; cursor:pointer; transition:all 0.2s;">
                        🟢 Deteksi AI Aktif Otomatis (Klik untuk Jeda)
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

                <!-- CARD: Inspektor Frekuensi Gangguan Motor (EMI Spectrum Monitor) -->
                <div style="background:#ffffff; border:1.5px solid #c7d2fe; border-radius:10px; padding:18px; box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; border-bottom:1px solid #e0e7ff; padding-bottom:8px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:20px;">⚡</span>
                            <h4 style="margin:0; font-size:15px; color:#3730a3; font-weight:800;">Spektrum Noise Motor Industri</h4>
                        </div>
                        <span id="badge-noise-status" style="font-size:11.5px; font-weight:700; background:#dcfce7; color:#16a34a; padding:3px 9px; border-radius:6px; font-family:ui-monospace,monospace;">
                            🟢 Bersih (0.0 Hz)
                        </span>
                    </div>

                    <!-- Indikator Dua Kolom: Pin 25 (Inlet) & Pin 27 (Outlet) -->
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px; text-align:center;">
                            <div style="font-size:11.5px; color:#64748b; font-weight:700;">Pin 25 (Inlet Sensor)</div>
                            <div id="val-noise-in" style="font-size:20px; font-weight:800; color:#1e293b; font-family:monospace; margin:4px 0;">0.0 <span style="font-size:12px; color:#94a3b8;">Hz</span></div>
                            <div style="font-size:11px; color:#94a3b8;">Peak: <span id="val-peak-in" style="font-weight:700; color:#475569;">0.0 Hz</span></div>
                        </div>

                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px; text-align:center;">
                            <div style="font-size:11.5px; color:#64748b; font-weight:700;">Pin 27 (Outlet Sensor)</div>
                            <div id="val-noise-out" style="font-size:20px; font-weight:800; color:#1e293b; font-family:monospace; margin:4px 0;">0.0 <span style="font-size:12px; color:#94a3b8;">Hz</span></div>
                            <div style="font-size:11px; color:#94a3b8;">Peak: <span id="val-peak-out" style="font-weight:700; color:#475569;">0.0 Hz</span></div>
                        </div>
                    </div>

                    <!-- Diagnosa & Rekomendasi Filter -->
                    <div style="background:#eef2ff; border-left:3px solid #6366f1; border-radius:6px; padding:8px 10px; margin-bottom:10px; font-size:12px;">
                        <div style="color:#4338ca; font-weight:700; margin-bottom:2px;">
                            Diagnosa: <span id="text-noise-diagnosis" style="font-weight:800;">Memuat spektrum...</span>
                        </div>
                        <div style="color:#6366f1; font-size:11px;">
                            Input (Pin 25): <b>RAW Tanpa Filter (Instant 0 ms)</b> | Outlet (Pin 27): <b>Filter 10 Hz (100 ms)</b>.
                        </div>
                    </div>

                    <!-- Live Serial Stream Log Frekuensi Motor (Mirip Serial Monitor) -->
                    <div style="background:#090d16; border:1px solid #1e293b; border-radius:6px; padding:8px 10px; margin-bottom:10px; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:11px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; border-bottom:1px solid #1e293b; padding-bottom:3px;">
                            <span style="color:#38bdf8; font-weight:700; display:flex; align-items:center; gap:5px;">
                                <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:#22c55e;"></span>
                                Aliran Serial Monitor Frekuensi
                            </span>
                            <span id="noise-stream-status" style="color:#64748b; font-size:10px;">Live Sync</span>
                        </div>
                        <div id="noise-serial-log-list" style="max-height:80px; overflow-y:auto; display:flex; flex-direction:column; gap:3px;">
                            <div style="color:#64748b; font-style:italic;">[Menunggu sinyal frekuensi dari Serial Monitor ESP32...]</div>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span id="text-noise-rejected-summary" style="font-size:11.5px; color:#64748b;">
                            Total noise disaring: <b id="val-total-noise-rejected" style="color:#ef4444;">0</b> pulsa
                        </span>
                        <button type="button" id="btn-reset-noise-peak" style="font-size:11px; font-weight:700; color:#4f46e5; background:#e0e7ff; border:none; border-radius:4px; padding:4px 8px; cursor:pointer;">
                            Reset Peak
                        </button>
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
                    <h3 style="margin:0; font-size:18px; color:#0f172a; font-weight:800;">Katalog & Profil Part (AI Offline)</h3>
                    <div style="font-size:13px; color:#64748b;">Kelola daftar part dan upload file model AI (.pt / .onnx) hasil download Roboflow</div>
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
                            <th style="padding:11px 14px;">File Model AI (Offline)</th>
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

            <form id="form-save-part" onsubmit="return false;" enctype="multipart/form-data">
                <input type="hidden" id="part-form-id">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#475569; margin-bottom:5px;">Kode Part <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="part-form-code" class="filter-select" style="width:100%; padding:9px 12px; font-size:13px;" placeholder="contoh: PART-001" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#475569; margin-bottom:5px;">Nama Part <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="part-form-name" class="filter-select" style="width:100%; padding:9px 12px; font-size:13px;" placeholder="contoh: PART-Tester" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#475569; margin-bottom:5px;">Target Pcs / Hanger <span style="color:#ef4444;">*</span></label>
                        <input type="number" id="part-form-target" class="filter-select" style="width:100%; padding:9px 12px; font-size:13px;" value="15" min="1" required>
                    </div>
                </div>

                <!-- Upload File Model AI (.pt / .onnx) -->
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:12.5px; font-weight:700; color:#475569; margin-bottom:5px;">
                        File Model AI Offline (.pt / .onnx)
                    </label>
                    <div id="part-form-model-info" style="display:none; margin-bottom:8px; padding:9px 13px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; font-size:12.5px; color:#15803d; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span>🟢 Model Tersimpan: <strong id="current-model-filename">PART-001_v1.pt</strong></span>
                            <span id="current-model-size" style="font-size:11px; background:#dcfce7; padding:2px 8px; border-radius:4px; font-weight:700;">42.0 MB</span>
                        </div>
                        <button type="button" id="btn-delete-model-file" style="padding:4px 10px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; border-radius:6px; font-weight:700; font-size:11.5px; cursor:pointer; display:inline-flex; align-items:center; gap:4px; transition:all 0.15s;" title="Hapus file weights (.pt/.onnx) ini dari server">
                            🗑️ Hapus Model Saja
                        </button>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <input type="file" id="part-form-file" accept=".pt,.onnx" style="display:none;">
                        <button type="button" id="btn-browse-model-file" style="padding:9px 16px; background:#ffffff; color:#0d3b8c; border:1.5px solid #0d3b8c; border-radius:7px; font-size:13px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                            📂 Pilih File weights.pt / .onnx
                        </button>
                        <span id="label-selected-model-file" style="font-size:12.5px; color:#64748b; font-family:monospace;">
                            Belum ada file baru dipilih (opsional jika sudah ada)
                        </span>
                    </div>
                    <div style="font-size:11.5px; color:#64748b; margin-top:6px; line-height:1.4;">
                        💡 <em>Cukup upload file <strong>weights.pt</strong> atau <strong>.onnx</strong> hasil download dari Roboflow. Sistem akan mengeksekusi model ini secara 100% offline di PC pabrik tanpa butuh internet lagi!</em>
                    </div>
                </div>

                <!-- Progress Bar Upload Model AI (Realtime) -->
                <div id="part-upload-progress-container" style="display:none; margin-bottom:14px; background:#eff6ff; border:1.5px solid #bfdbfe; border-radius:8px; padding:12px 14px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:12.5px; font-weight:700; color:#1e40af; margin-bottom:6px;">
                        <span id="part-upload-progress-text">Mengupload file model weights...</span>
                        <span id="part-upload-progress-percent">0%</span>
                    </div>
                    <div style="background:#dbeafe; border-radius:6px; height:10px; width:100%; overflow:hidden;">
                        <div id="part-upload-progress-bar" style="height:100%; width:0%; background:linear-gradient(90deg, #2563eb, #16a34a); border-radius:6px; transition:width 0.15s ease;"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:11px; color:#64748b; margin-top:5px; font-family:monospace;">
                        <span id="part-upload-progress-bytes">0 MB / 0 MB</span>
                        <span id="part-upload-progress-speed">Memulai upload...</span>
                    </div>
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
    let isOfflineAiReady = false;
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

    // --- Gated Inspection Window (Pin 27 Start -> Pin 25 Finish) ---
    let isInspectionActive = false;
    let inspectionTrackedPartIds = new Set();
    let inspectionMaxFrameParts = 0;
    let inspectionStartTime = 0;
    let inspectionDetectedClasses = {};
    const badgeInspection = document.getElementById('ai-inspection-badge');
    const badgeInspectionText = document.getElementById('ai-inspection-badge-text');

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

    // ================================================================
    // LISTENER SPEKTRUM FREKUENSI NOISE MOTOR INDUSTRI (POLLING 2s)
    // ================================================================
    const badgeNoiseStatus = document.getElementById('badge-noise-status');
    const valNoiseIn = document.getElementById('val-noise-in');
    const valNoiseOut = document.getElementById('val-noise-out');
    const valPeakIn = document.getElementById('val-peak-in');
    const valPeakOut = document.getElementById('val-peak-out');
    const textNoiseDiagnosis = document.getElementById('text-noise-diagnosis');
    const valTotalNoiseRejected = document.getElementById('val-total-noise-rejected');
    const btnResetNoisePeak = document.getElementById('btn-reset-noise-peak');
    const noiseSerialLogList = document.getElementById('noise-serial-log-list');
    let lastLoggedNoiseHz = -1;
    let isPollingDevkit = false;

    async function pollDevkitNoise() {
        if (window.isUploadingLargeFile || isPollingDevkit) return;
        isPollingDevkit = true;
        try {
            const res = await fetch('/api/devkit-noise');
            const data = await res.json();
            if (data && (data.status === 'ok' || data.status === 'cached')) {
                const inHz = parseFloat(data.in_noise_hz) || 0;
                const outHz = parseFloat(data.out_noise_hz) || 0;
                const peakIn = parseFloat(data.in_peak_hz) || 0;
                const peakOut = parseFloat(data.out_peak_hz) || 0;
                const maxHz = Math.max(inHz, outHz);

                if (valNoiseIn) valNoiseIn.innerHTML = `${inHz.toFixed(1)} <span style="font-size:12px; color:#94a3b8;">Hz</span>`;
                if (valNoiseOut) valNoiseOut.innerHTML = `${outHz.toFixed(1)} <span style="font-size:12px; color:#94a3b8;">Hz</span>`;
                if (valPeakIn) valPeakIn.innerText = `${peakIn.toFixed(1)} Hz`;
                if (valPeakOut) valPeakOut.innerText = `${peakOut.toFixed(1)} Hz`;

                const totalRejected = data.total_rejected ?? ((parseInt(data.in_blind_rejected) || 0) + (parseInt(data.out_blind_rejected) || 0));
                if (valTotalNoiseRejected) valTotalNoiseRejected.innerText = totalRejected;
                if (textNoiseDiagnosis) textNoiseDiagnosis.innerText = data.diagnosis || 'Normal';

                if (badgeNoiseStatus) {
                    if (maxHz < 1.5) {
                        badgeNoiseStatus.innerText = `🟢 Bersih (${maxHz.toFixed(1)} Hz)`;
                        badgeNoiseStatus.style.background = '#dcfce7';
                        badgeNoiseStatus.style.color = '#16a34a';
                    } else if (maxHz < 50) {
                        badgeNoiseStatus.innerText = `🟡 Noise (${maxHz.toFixed(1)} Hz)`;
                        badgeNoiseStatus.style.background = '#fef3c7';
                        badgeNoiseStatus.style.color = '#b45309';
                    } else {
                        badgeNoiseStatus.innerText = `🚨 Tinggi (${maxHz.toFixed(1)} Hz)`;
                        badgeNoiseStatus.style.background = '#fee2e2';
                        badgeNoiseStatus.style.color = '#dc2626';
                    }
                }

                // Render Live Aliran Serial Stream
                if (noiseSerialLogList) {
                    if (Array.isArray(data.events) && data.events.length > 0) {
                        noiseSerialLogList.innerHTML = data.events.map(ev => {
                            const isClean = ev.hz < 1.5;
                            const color = isClean ? '#4ade80' : (ev.hz >= 50 ? '#f87171' : '#fbbf24');
                            const icon = isClean ? '🟢' : (ev.hz >= 50 ? '🚨' : '⚡');
                            return `<div style="color:${color}; display:flex; gap:6px; line-height:1.4;">
                                <span style="color:#64748b;">[${ev.time || '-'}]</span>
                                <span>${icon} <b>${ev.hz} Hz</b></span>
                                <span style="color:#cbd5e1;">— ${ev.diagnosis || 'Gangguan Motor'}</span>
                            </div>`;
                        }).join('');
                    } else if (maxHz > 0) {
                        const nowTime = new Date().toLocaleTimeString('id-ID');
                        const isClean = maxHz < 1.5;
                        const color = isClean ? '#4ade80' : (maxHz >= 50 ? '#f87171' : '#fbbf24');
                        const icon = isClean ? '🟢' : (maxHz >= 50 ? '🚨' : '⚡');
                        noiseSerialLogList.innerHTML = `<div style="color:${color};">
                            <span style="color:#64748b;">[${nowTime}]</span> ${icon} <b>${maxHz.toFixed(1)} Hz</b> — ${data.diagnosis || 'Sinyal Terdeteksi'}
                        </div>`;
                    }
                }

                // Log ke konsol & timeline utama saat ada lonjakan noise
                if (maxHz >= 3.0 && Math.abs(maxHz - lastLoggedNoiseHz) >= 2.0) {
                    lastLoggedNoiseHz = maxHz;
                    appendLog(`⚡ [Noise Motor] Terdeteksi frekuensi ${maxHz.toFixed(1)} Hz (${data.diagnosis}). Filter otomatis menyaring pulsa palsu.`, '#f59e0b');
                } else if (maxHz < 1.5 && lastLoggedNoiseHz >= 3.0) {
                    lastLoggedNoiseHz = 0;
                    appendLog('🟢 [Jalur Sensor Bersih] Gangguan motor telah reda / hilang.', '#16a34a');
                }
            } else if (data && data.status === 'offline') {
                if (badgeNoiseStatus) {
                    badgeNoiseStatus.innerText = '⚪ DevKit Offline';
                    badgeNoiseStatus.style.background = '#f1f5f9';
                    badgeNoiseStatus.style.color = '#64748b';
                }
            }
        } catch (e) {
        } finally {
            isPollingDevkit = false;
        }
    }

    setInterval(pollDevkitNoise, 2500); // Polling berkala 2.5 detik
    pollDevkitNoise();

    if (btnResetNoisePeak) {
        btnResetNoisePeak.addEventListener('click', async () => {
            await fetch('/api/reset-noise-peak');
            if (valPeakIn) valPeakIn.innerText = '0.0 Hz';
            if (valPeakOut) valPeakOut.innerText = '0.0 Hz';
            if (noiseSerialLogList) {
                noiseSerialLogList.innerHTML = '<div style="color:#a5b4fc; font-style:italic;">[Peak & log frekuensi di-reset ke 0 Hz]</div>';
            }
            appendLog('🔄 Peak frekuensi noise motor di-reset ke 0 Hz.', '#6366f1');
        });
    }

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

    // Muat data statistik hanger saat halaman dibuka & refresh berkala
    let isPollingStats = false;
    async function loadHangerStats() {
        if (window.isUploadingLargeFile || isPollingStats) return;
        isPollingStats = true;
        try {
            const res = await fetch('/api/get-hanger-stats');
            const data = await res.json();
            if (data && data.stats) {
                kpiWipHangers.innerText = data.stats.wip_hangers || 0;
                kpiOutputHangers.innerText = data.stats.total_output_hangers || 0;
                kpiTotalParts.innerText = data.stats.total_output_pcs || 0;
                if (data.stats.is_produksi !== undefined) {
                    updateMesinUI(data.stats.is_produksi == 1, true);
                }
            }
        } catch (e) {
        } finally {
            isPollingStats = false;
        }
    }
    loadHangerStats();
    setInterval(loadHangerStats, 3000); // Sinkronisasi berkala status produksi & sinyal ESP32

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
                badgeModel.innerText = '⚙ Belum Dikonfigurasi';
                badgeModel.style.background = '#fef3c7';
                badgeModel.style.color = '#b45309';
                badgeModel.style.cursor = 'default';
                badgeModel.onclick = null;
                badgeModel.title = 'Model Roboflow belum diisi. Masukkan slug & API key di Pengaturan Manual.';
            }
            const errBox = document.getElementById('ai-model-error-hint');
            if (errBox) errBox.style.display = 'none';
            return false;
        }

        // Sanitasi modelId: jika user memasukkan full name seperti 'part-tester-6cyho-1-yolo26m-t1' atau 'part-tester-6cyho/1'
        let cleanModelId = String(modelId).trim().replace(/\s+/g, '-');
        if (cleanModelId.includes('/')) {
            const parts = cleanModelId.split('/');
            cleanModelId = parts[0];
            if (parts[1] && !isNaN(parseInt(parts[1], 10))) {
                version = parseInt(parts[1], 10);
            }
        } else if (cleanModelId.includes('-yolo') || cleanModelId.includes('-rf-detr')) {
            const match = cleanModelId.match(/^(.+?)-(\d+)-(?:yolo|rf-detr|coco|m6|m7|t\d+)/i);
            if (match) {
                cleanModelId = match[1];
                version = parseInt(match[2], 10);
            }
        }
        modelId = cleanModelId;
        version = parseInt(version, 10) || 1;

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
                version: version
            });

            statusModel.innerText = `Model Siap (${modelId} v${version})`;
            statusModel.style.color = '#16a34a';
            if (badgeModel) {
                badgeModel.innerText = `🟢 v${version} Siap`;
                badgeModel.title = `Model ${modelId} v${version} aktif & siap mendeteksi`;
                badgeModel.style.background = '#dcfce7';
                badgeModel.style.color = '#15803d';
                badgeModel.style.cursor = 'default';
                badgeModel.onclick = null;
            }
            const errBox = document.getElementById('ai-model-error-hint');
            if (errBox) errBox.style.display = 'none';

            checkEnableDetectButton();
            appendLog(`🤖 Model Roboflow [${modelId} v${version}] aktif & siap mendeteksi.`, '#16a34a');
            return true;
        } catch (err) {
            console.error('Error memuat model Roboflow:', err);
            const rawMsg = err.message || String(err);
            let userFriendlyMsg = rawMsg;
            if (rawMsg.includes('Library roboflow.js belum termuat')) {
                userFriendlyMsg = 'Library Roboflow tidak dapat dimuat. Pastikan komputer/browser terhubung ke Internet.';
            } else if (rawMsg.includes('404') || rawMsg.toLowerCase().includes('not found')) {
                userFriendlyMsg = `Model [${modelId} v${version}] tidak ditemukan di Roboflow. Periksa Model Slug dan pastikan versi sudah selesai di-train.`;
            } else if (rawMsg.includes('401') || rawMsg.includes('403') || rawMsg.toLowerCase().includes('unauthorized') || rawMsg.toLowerCase().includes('key')) {
                userFriendlyMsg = 'Roboflow Publishable API Key salah atau tidak memiliki akses ke project ini.';
            } else if (rawMsg.toLowerCase().includes('failed to fetch')) {
                userFriendlyMsg = 'Koneksi ke server Roboflow gagal (periksa internet / blokir firewall).';
            }

            statusModel.innerText = 'Gagal: ' + userFriendlyMsg;
            statusModel.style.color = '#dc2626';
            if (badgeModel) {
                badgeModel.innerText = '🔴 Gagal';
                badgeModel.title = `Klik untuk detail: ${userFriendlyMsg}`;
                badgeModel.style.cursor = 'pointer';
                badgeModel.onclick = () => alert(`Detail Error Roboflow:\n\n${rawMsg}\n\nSolusi:\n1. Buka 'Pengaturan Manual' di bawah.\n2. Masukkan Model Slug & Publishable API Key yang sesuai dari project Roboflow Anda.\n3. Pastikan versi model (v${version}) sudah selesai di-training.`);
                badgeModel.style.background = '#fee2e2';
                badgeModel.style.color = '#dc2626';
            }

            const errBox = document.getElementById('ai-model-error-hint');
            const errMsgEl = document.getElementById('ai-model-error-msg');
            if (errBox && errMsgEl) {
                errMsgEl.innerText = `${userFriendlyMsg} (Error: ${rawMsg})`;
                errBox.style.display = 'block';
            }

            appendLog(`❌ Gagal muat model [${modelId} v${version}]: ${userFriendlyMsg}`, '#dc2626');
            if (!silent) {
                alert(`Gagal memuat model Roboflow [${modelId} v${version}]:\n\n${userFriendlyMsg}\n\n(Error teknis: ${rawMsg})`);
            }
            return false;
        } finally {
            btnLoadModel.disabled = false;
        }
    }

    btnLoadModel.addEventListener('click', async () => {
        const inputModelEl = document.getElementById('rf-model-input');
        const inputVerEl = document.getElementById('rf-version-input');
        let modelId = inputModelEl.value.trim().replace(/\s+/g, '-');
        let version = parseInt(inputVerEl.value.trim(), 10) || 1;
        const apiKey = document.getElementById('rf-key-input').value.trim();

        // Auto-clean jika user memasukkan nama panjang seperti 'part-tester-6cyho-1-yolo26m-t1'
        if (modelId.includes('/')) {
            const parts = modelId.split('/');
            modelId = parts[0];
            if (parts[1] && !isNaN(parseInt(parts[1], 10))) version = parseInt(parts[1], 10);
        } else if (modelId.includes('-yolo') || modelId.includes('-rf-detr')) {
            const match = modelId.match(/^(.+?)-(\d+)-(?:yolo|rf-detr|coco|m6|m7|t\d+)/i);
            if (match) {
                modelId = match[1];
                version = parseInt(match[2], 10);
            }
        }
        inputModelEl.value = modelId;
        inputVerEl.value = version;

        if (!modelId || !apiKey) {
            alert('Harap isi Model ID dan Publishable API Key dari Roboflow terlebih dahulu!');
            return;
        }

        localStorage.setItem('rf_model_id', modelId);
        localStorage.setItem('rf_model_version', version);
        localStorage.setItem('rf_api_key', apiKey);

        const ok = await initRoboflowModel(modelId, version, apiKey, false);
        if (ok && activePart) {
            const cardModelSlug = document.getElementById('card-model-slug');
            const cardModelVersion = document.getElementById('card-model-version');
            if (cardModelSlug) cardModelSlug.innerText = modelId;
            if (cardModelVersion) cardModelVersion.innerText = `v${version}`;

            // Simpan otomatis ke katalog part aktif di backend agar permanen
            try {
                await fetch('/api/save-part', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: activePart.id,
                        code: activePart.code,
                        name: activePart.name,
                        model_id: modelId,
                        model_version: version,
                        target_per_hanger: activePart.target_per_hanger || 4,
                        publishable_key: apiKey
                    })
                });
                activePart.model_id = modelId;
                activePart.model_version = version;
                activePart.publishable_key = apiKey;
                appendLog(`💾 Konfigurasi model untuk part [${activePart.code}] telah diperbarui permanen di katalog.`, '#16a34a');
            } catch (e) {
                console.warn('Gagal sync part katalog:', e);
            }
        }
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
        const isCamConnected = statusCam && (
            statusCam.innerText.includes('Tersambung') || 
            statusCam.innerText.includes('Online') ||
            (typeof activeCamIp === 'string' && activeCamIp.length > 6)
        );
        const isModelReady = !!(rfModel || isOfflineAiReady);

        if (isModelReady && isCamConnected) {
            btnStartAi.disabled = false;
            // AUTO-START: Deteksi AI langsung aktif otomatis tanpa perlu klik tombol manual!
            if (!isDetecting) {
                isDetecting = true;
                updateDetectButtonState();
                detectLoop();
                appendLog('🚀 Deteksi AI Vision otomatis AKTIF (Real-Time Monitoring).', '#16a34a');
            }
        } else if (!isCamConnected) {
            btnStartAi.disabled = true;
            if (isDetecting) {
                isDetecting = false;
                updateDetectButtonState();
            }
        }
    }

    function updateDetectButtonState() {
        if (isDetecting) {
            btnStartAi.innerHTML = '🟢 Deteksi AI Aktif Otomatis (Klik untuk Jeda)';
            btnStartAi.style.background = '#15803d';
            badgeLatency.style.display = 'inline-block';
        } else {
            btnStartAi.innerHTML = '▶ Lanjutkan Deteksi AI';
            btnStartAi.style.background = '#0284c7';
            badgeLatency.style.display = 'none';
        }
    }

    // Loop Prediksi Kontinu (Auto-Detection)
    let isDetectingBusy = false;
    async function detectLoop() {
        if (!isDetecting) return;

        const now = Date.now();
        if (!isDetectingBusy && (now - lastDetectionTime > 150)) {
            lastDetectionTime = now;
            isDetectingBusy = true;
            try {
                await runObjectDetection(false); // false = jangan trigger konfirmasi otomatis saat sekadar live preview
            } catch (eLoop) {
                console.warn('detectLoop error:', eLoop);
            } finally {
                isDetectingBusy = false;
            }
        }

        if (isDetecting) {
            detectionLoopId = requestAnimationFrame(detectLoop);
        }
    }

    // 4. INFERENSI OBJECT DETECTION & GAMBAR BOUNDING BOX
    async function runObjectDetection(isShutterTrigger = false) {
        if ((!rfModel && !isOfflineAiReady) || streamWrapper.style.display === 'none') return 0;

        // Pastikan ukuran canvas overlay presisi sesuai resolusi kamera saat ini
        if (streamImg.naturalWidth && (canvasOverlay.width !== streamImg.naturalWidth || canvasOverlay.height !== streamImg.naturalHeight)) {
            canvasOverlay.width = streamImg.naturalWidth;
            canvasOverlay.height = streamImg.naturalHeight;
        }

        const tStart = performance.now();

        try {
            let predictions = [];
            if (isOfflineAiReady) {
                // Snapshot frame kamera ke base64 (dengan fallback getFrameBase64)
                let b64 = '';
                try {
                    const offCanvas = document.createElement('canvas');
                    offCanvas.width = streamImg.naturalWidth || 640;
                    offCanvas.height = streamImg.naturalHeight || 480;
                    const offCtx = offCanvas.getContext('2d');
                    offCtx.drawImage(streamImg, 0, 0, offCanvas.width, offCanvas.height);
                    b64 = offCanvas.toDataURL('image/jpeg', 0.85);
                } catch (eCanvas) {
                    b64 = await getFrameBase64();
                }

                const minConf = (parseInt(sliderConf.value, 10) || 35) / 100;
                const res = await fetch('/api/detect-local', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        image_base64: b64 || '',
                        conf: minConf,
                        model_file: activePart ? activePart.model_file : 'PART-001_v1.pt'
                    })
                });
                const detData = await res.json();
                if (detData && detData.status === 'ok') {
                    predictions = detData.predictions || [];
                    const tEnd = performance.now();
                    const lat = detData.latency_ms || Math.round(tEnd - tStart);
                    badgeLatency.innerText = `${lat} ms (Offline AI)`;
                    badgeLatency.style.background = '#0284c7';
                    badgeLatency.title = 'Inference AI Lokal Berhasil';

                    // Auto-sync jika backend mendeteksi part aktif baru dari AI
                    if (detData.active_part && (!activePart || activePart.id !== detData.active_part.id)) {
                        activePart = detData.active_part;
                        if (activePartName) activePartName.innerText = activePart.name;
                        if (activePartCode) activePartCode.innerText = activePart.code;
                        if (cardPartName) cardPartName.innerText = activePart.name;
                        if (cardPartCode) cardPartCode.innerText = activePart.code;
                        if (cardPartTarget) cardPartTarget.innerText = activePart.target_per_hanger;
                        if (labelKolektorPartName) labelKolektorPartName.innerText = `[${activePart.code}] ${activePart.name}`;
                        appendLog(`🤖 [AI Auto-Switch] Kamera mendeteksi: [${activePart.code}] ${activePart.name}. Part aktif otomatis diganti!`, '#0284c7');
                    }
                } else if (detData && detData.status === 'error') {
                    const errMsg = detData.message || 'Error inferensi AI lokal';
                    badgeLatency.innerText = `⚠️ Format Model Belum Sesuai`;
                    badgeLatency.style.background = '#ef4444';
                    badgeLatency.title = errMsg;
                    if (!window._lastAiErrToastTime || Date.now() - window._lastAiErrToastTime > 15000) {
                        window._lastAiErrToastTime = Date.now();
                        appendLog(`⚠️ [AI Error] ${errMsg}`, '#ef4444');
                        console.warn('AI Inference Error:', detData);
                    }
                }
            } else if (rfModel) {
                predictions = await rfModel.detect(streamImg);
                const tEnd = performance.now();
                badgeLatency.innerText = Math.round(tEnd - tStart) + ' ms';
            }

            const minConfidence = (parseInt(sliderConf.value, 10) || 35) / 100;
            const validDetections = (predictions || []).filter(p => (p.confidence || 0) >= minConfidence);

            ctxOverlay.clearRect(0, 0, canvasOverlay.width, canvasOverlay.height);

            // Pisahkan deteksi part vs deteksi hanger/gantungan
            const partDetections = [];
            const hangerDetections = [];

            validDetections.forEach(pred => {
                const cls = (pred.class || pred.name || '').trim().toLowerCase();
                if (cls.includes('hanger') || cls.includes('gantungan') || cls.includes('kosong') || cls.includes('empty')) {
                    hangerDetections.push(pred);
                } else {
                    partDetections.push(pred);
                }
            });

            // Jumlah part aktual yang dihitung (tidak menghitung bounding box hanger)
            const countPart = partDetections.length;

            // Logika Gated Inspection Window (Akumulasi Part ID Unik selama Hanger di dalam Box)
            if (isInspectionActive) {
                partDetections.forEach(pred => {
                    if (pred.tracker_id !== undefined && pred.tracker_id !== null) {
                        inspectionTrackedPartIds.add(pred.tracker_id);
                    }
                });
                inspectionMaxFrameParts = Math.max(inspectionMaxFrameParts, countPart);

                const accumulatedCount = inspectionTrackedPartIds.size > 0 
                    ? inspectionTrackedPartIds.size 
                    : inspectionMaxFrameParts;
                kpiCurrentParts.innerText = accumulatedCount;

                if (badgeInspection && badgeInspectionText) {
                    badgeInspection.style.display = 'inline-flex';
                    badgeInspection.style.background = 'rgba(16,185,129,0.92)';
                    badgeInspectionText.innerText = `🔍 MELACAK: ${accumulatedCount} PART UNIK`;
                }
            } else {
                kpiCurrentParts.innerText = countPart;
            }

            // Gambar setiap bounding box
            let partIndex = 0;
            validDetections.forEach((pred) => {
                const bbox = pred.bbox || pred;
                const x = bbox.x;
                const y = bbox.y;
                const width = bbox.width;
                const height = bbox.height;
                const className = pred.class || pred.name || 'Part';
                const clsLower = className.toLowerCase();
                const isHanger = clsLower.includes('hanger') || clsLower.includes('gantungan') || clsLower.includes('kosong') || clsLower.includes('empty');
                const confScore = Math.round((pred.confidence || 0) * 100);

                const left = x - width / 2;
                const top = y - height / 2;

                // Box Deteksi: Biru untuk Hanger/Gantungan, Hijau untuk Part Produk
                const boxColor = isHanger ? '#0284c7' : '#22c55e';
                ctxOverlay.strokeStyle = boxColor;
                ctxOverlay.lineWidth = 3;
                ctxOverlay.strokeRect(left, top, width, height);

                // Label
                let labelText = '';
                if (isHanger) {
                    labelText = `[Hanger] ${className} ${confScore}%`;
                } else {
                    partIndex++;
                    const trackStr = pred.tracker_id ? ` (ID:${pred.tracker_id})` : '';
                    labelText = `#${partIndex}${trackStr} ${className} ${confScore}%`;
                }

                ctxOverlay.font = 'bold 12px Inter, sans-serif';
                const textWidth = ctxOverlay.measureText(labelText).width;

                ctxOverlay.fillStyle = boxColor;
                ctxOverlay.fillRect(left, Math.max(0, top - 20), textWidth + 10, 20);

                ctxOverlay.fillStyle = '#ffffff';
                ctxOverlay.fillText(labelText, left + 5, Math.max(14, top - 5));
            });

            // Ekstraksi kode part otomatis dari kelas deteksi AI (Auto-Recognition)
            let detectedPartCode = '';
            const classCounts = {};
            partDetections.forEach(pred => {
                const cls = (pred.class || pred.name || '').trim();
                if (cls && cls.toLowerCase() !== 'part' && cls.toLowerCase() !== 'object' && cls.toLowerCase() !== 'item') {
                    classCounts[cls] = (classCounts[cls] || 0) + 1;
                }
            });

            let maxCount = 0;
            for (const [cls, cnt] of Object.entries(classCounts)) {
                if (cnt > maxCount) {
                    maxCount = cnt;
                    detectedPartCode = cls;
                }
            }

            // Jika dipanggil oleh trigger Photosensor Output (Sensor Keluar - Pin 25)
            if (isShutterTrigger) {
                // Tentukan jumlah final part yang terkonfirmasi:
                // Gunakan akumulasi ID part unik selama di dalam box, atau frame terbesar
                let finalPcs = 0;
                if (inspectionTrackedPartIds.size > 0) {
                    finalPcs = inspectionTrackedPartIds.size;
                } else if (inspectionMaxFrameParts > 0) {
                    finalPcs = inspectionMaxFrameParts;
                } else {
                    finalPcs = countPart;
                }

                const durationSec = inspectionStartTime ? Math.round((Date.now() - inspectionStartTime) / 100) / 10 : 0;
                isInspectionActive = false; // Selesai sesi inspeksi hanger ini

                // Perbarui badge status visual di pojok layar
                if (badgeInspection && badgeInspectionText) {
                    badgeInspection.style.display = 'inline-flex';
                    badgeInspection.style.background = '#0284c7';
                    badgeInspectionText.innerText = `✅ SELESAI: ${finalPcs} PART (${durationSec}s)`;
                    setTimeout(() => {
                        if (!isInspectionActive) {
                            badgeInspection.style.display = 'none';
                        }
                    }, 4000);
                }

                const res = await fetch('/api/confirm-hanger-output', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        pcs_count: finalPcs,
                        detected_part_code: detectedPartCode,
                        detected_classes: classCounts,
                        tracked_ids_count: inspectionTrackedPartIds.size,
                        inspection_duration_s: durationSec
                    })
                });
                const resData = await res.json();
                if (resData && resData.stats) {
                    kpiWipHangers.innerText = resData.stats.wip_hangers || 0;
                    kpiOutputHangers.innerText = resData.stats.total_output_hangers || 0;
                    kpiTotalParts.innerText = resData.stats.total_output_pcs || 0;
                }
                if (resData && resData.part) {
                    activePart = resData.part;
                    if (activePartName) activePartName.innerText = activePart.name;
                    if (activePartCode) activePartCode.innerText = activePart.code;
                    if (cardPartName) cardPartName.innerText = activePart.name;
                    if (cardPartCode) cardPartCode.innerText = activePart.code;
                    if (cardPartTarget) cardPartTarget.innerText = activePart.target_per_hanger;
                    if (labelKolektorPartName) labelKolektorPartName.innerText = `[${activePart.code}] ${activePart.name}`;
                }
                const partLabel = detectedPartCode ? ` [Part: ${detectedPartCode}]` : (activePart ? ` [Part: ${activePart.code}]` : '');
                if (finalPcs === 0) {
                    appendLog(`📸 [Sensor Keluar - Pin 25] Hanger KOSONG Lewat (0 Pcs)${partLabel} (${durationSec}s) — Counter part tidak bertambah.`, '#f59e0b');
                } else {
                    appendLog(`📸 [Sensor Keluar - Pin 25] Hanger Selesai: ${finalPcs} Pcs${partLabel} diverifikasi (${durationSec}s)!`, '#0d3b8c');
                }

                // Reset tracker di background AI Daemon agar hanger berikutnya mulai dari nol
                inspectionTrackedPartIds.clear();
                fetch('/api/reset-ai-tracker', { method: 'POST' }).catch(() => {});

                // Tahan bounding box di layar selama 2.5 detik agar terlihat jelas oleh operator
                if (!isDetecting) {
                    clearTimeout(window._shutterOverlayTimer);
                    window._shutterOverlayTimer = setTimeout(() => {
                        ctxOverlay.clearRect(0, 0, canvasOverlay.width, canvasOverlay.height);
                    }, 2500);
                }
            } else if (detectedPartCode && partsCatalog && partsCatalog.length > 0) {
                // Saat live preview: perbarui info part jika kamera mengenali jenis part tertentu
                const det = detectedPartCode.trim().toLowerCase();
                const matched = partsCatalog.find(p => {
                    const code = (p.code || '').toLowerCase();
                    const name = (p.name || '').toLowerCase();
                    const id = (p.id || '').toLowerCase();
                    return code === det || name === det || id === det ||
                           det.includes(code) || code.includes(det) ||
                           det.includes(name) || name.includes(det);
                });
                if (matched && (!activePart || activePart.id !== matched.id)) {
                    activePart = matched;
                    if (activePartName) activePartName.innerText = activePart.name;
                    if (activePartCode) activePartCode.innerText = activePart.code;
                    if (cardPartName) cardPartName.innerText = activePart.name;
                    if (cardPartCode) cardPartCode.innerText = activePart.code;
                    if (cardPartTarget) cardPartTarget.innerText = activePart.target_per_hanger;
                    if (labelKolektorPartName) labelKolektorPartName.innerText = `[${activePart.code}] ${activePart.name}`;

                    // Auto-sync part aktif baru ke backend
                    fetch('/api/change-part', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ part_id: matched.id })
                    }).catch(e => console.warn('Auto-sync part aktif error:', e));

                    appendLog(`🤖 [AI Auto-Switch] Kamera mendeteksi: [${matched.code}] ${matched.name}. Part aktif otomatis diganti!`, '#0284c7');
                }
            }

            return countPart;
        } catch (e) {
            console.error('Error saat inferensi Roboflow:', e);
            return 0;
        }
    }

    // ================================================================
    // KOLEKTOR DATASET ROBOFLOW (UNLIMITED & ULTRA-FAST)
    // ================================================================
    let sampleCount = 0;
    let isSamplingActive = false;
    let continuousSamplingTimeout = null;

    const badgeSampleStatus = document.getElementById('badge-sample-status');
    const labelSampleProgress = document.getElementById('label-sample-progress');
    const barSampleProgress = document.getElementById('bar-sample-progress');
    const btnToggleSampling = document.getElementById('btn-toggle-sampling');
    const btnCaptureManualSample = document.getElementById('btn-capture-manual-sample');
    const btnDownloadDatasetZip = document.getElementById('btn-download-dataset-zip');
    const btnResetSamples = document.getElementById('btn-reset-samples');
    const samplePreviewContainer = document.getElementById('sample-preview-container');
    const sampleEmptyNotice = document.getElementById('sample-empty-notice');

    function updateMesinUI(isOn, hasSignal = true) {
        if (isOn && hasSignal) {
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
                if (!hasSignal) {
                    textMesinStatus.innerText = 'BERHENTI PRODUKSI (Tidak Ada Signal ESP)';
                } else {
                    textMesinStatus.innerText = 'STANDBY (Nilai 0)';
                }
                textMesinStatus.style.color = '#dc2626';
            }
        }
    }

    function updateSampleUI() {
        if (labelSampleProgress) labelSampleProgress.innerText = sampleCount;
        if (!badgeSampleStatus || !btnDownloadDatasetZip) return;

        if (isSamplingActive) {
            badgeSampleStatus.innerHTML = `<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#ef4444; margin-right:6px; box-shadow:0 0 8px #ef4444;"></span>Merekam (${sampleCount} Foto...)`;
            badgeSampleStatus.style.background = '#fef2f2';
            badgeSampleStatus.style.color = '#dc2626';
        } else {
            badgeSampleStatus.innerText = `Standby (${sampleCount} Foto Terkumpul)`;
            badgeSampleStatus.style.background = '#e0e7ff';
            badgeSampleStatus.style.color = '#3730a3';
        }

        if (sampleCount > 0) {
            btnDownloadDatasetZip.style.opacity = '1';
            btnDownloadDatasetZip.style.pointerEvents = 'auto';
            btnDownloadDatasetZip.style.boxShadow = '0 2px 8px rgba(22, 163, 74, 0.35)';
        } else {
            btnDownloadDatasetZip.style.opacity = '0.5';
            btnDownloadDatasetZip.style.pointerEvents = 'none';
            btnDownloadDatasetZip.style.boxShadow = 'none';
        }
    }

    function updateSamplingButtonState() {
        if (!btnToggleSampling) return;
        if (isSamplingActive) {
            btnToggleSampling.innerHTML = '⏹ Berhenti Ambil Sampel';
            btnToggleSampling.style.background = '#dc2626';
            btnToggleSampling.style.boxShadow = '0 0 14px rgba(220, 38, 38, 0.45)';
        } else {
            btnToggleSampling.innerHTML = '▶ Mulai Ambil Sampel';
            btnToggleSampling.style.background = '#4f46e5';
            btnToggleSampling.style.boxShadow = '0 2px 6px rgba(79, 70, 229, 0.3)';
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

    // Persistent offscreen canvas untuk capture frame kilat tanpa overhead alokasi memory (<2ms)
    let persistentSnapCanvas = null;
    let persistentSnapCtx = null;

    function getFastFrameBase64() {
        if (!streamImg) return '';
        const w = streamImg.naturalWidth || streamImg.clientWidth || 640;
        const h = streamImg.naturalHeight || streamImg.clientHeight || 480;
        if (w <= 0 || h <= 0) return '';

        if (!persistentSnapCanvas) {
            persistentSnapCanvas = document.createElement('canvas');
            persistentSnapCtx = persistentSnapCanvas.getContext('2d', { willReadFrequently: true });
        }
        persistentSnapCanvas.width = 640;
        persistentSnapCanvas.height = 480;

        try {
            persistentSnapCtx.drawImage(streamImg, 0, 0, 640, 480);
            const dataUrl = persistentSnapCanvas.toDataURL('image/jpeg', 0.70);
            if (dataUrl && dataUrl.length > 500) {
                return dataUrl;
            }
        } catch (eCanvas) {}
        return '';
    }

    // Ekstraksi frame kamera yang handal & sangat cepat
    async function getFrameBase64() {
        // 1. Ekstraksi langsung dari stream canvas (<2ms)
        const fastFrame = getFastFrameBase64();
        if (fastFrame) return fastFrame;

        // 2. Fallback cepat: Ambil langsung dari /capture ESP32-CAM dengan timeout pendek (500ms)
        if (activeCamIp) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 500);
                const res = await fetch(`http://${activeCamIp}/capture?_t=${Date.now()}`, {
                    signal: controller.signal,
                    cache: 'no-store'
                });
                clearTimeout(timeoutId);
                if (res.ok) {
                    const blob = await res.blob();
                    if (blob.size > 500) {
                        return await new Promise((resolve) => {
                            const reader = new FileReader();
                            reader.onloadend = () => resolve(reader.result);
                            reader.readAsDataURL(blob);
                        });
                    }
                }
            } catch (eFetch) {}
        }

        return '';
    }

    // Pengambilan Sampel Kilat (Optimistic Feedback: Shutter & Counter naik seketika <10ms, simpan di background)
    async function captureRoboflowSample(source = 'Manual') {
        const base64Data = await getFrameBase64();

        if (!base64Data) {
            if (source !== 'Beruntun') {
                appendLog('⚠ Gagal mengambil sampel: Kamera belum siap / stream belum aktif.', '#dc2626');
            }
            return;
        }

        // 1. Efek Rana / Flash Kamera Instan
        if (shutterFlash) {
            shutterFlash.style.display = 'block';
            setTimeout(() => { shutterFlash.style.display = 'none'; }, 40);
        }
        if (videoContainer) {
            videoContainer.style.borderColor = '#6366f1';
            setTimeout(() => { videoContainer.style.borderColor = '#1e293b'; }, 60);
        }

        // 2. Optimistic UI: Naikkan counter dan tampilkan thumbnail seketika (<10ms)
        sampleCount++;
        updateSampleUI();
        addThumbnailToPreview(base64Data, sampleCount);

        // 3. Simpan ke Backend secara Asinkron (Non-blocking) di background
        const activePartId = activePart ? activePart.id : '';
        fetch('/api/save-roboflow-sample', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                image: base64Data,
                part_id: activePartId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.count) {
                sampleCount = data.count;
                updateSampleUI();
            }
        })
        .catch(err => {
            console.error('Error background save sample:', err);
        });

        appendLog(`📸 [Sampel #${sampleCount}] ${source}: Foto tersimpan untuk Part [${activePart ? activePart.code : 'Default'}]!`, '#4f46e5');
    }

    // ================================================================
    // LOGIKA PENGAMBILAN SAMPEL BERUNTUN (CONTINUOUS SAMPLING CEPAT)
    // ================================================================
    function stopContinuousSampling() {
        isSamplingActive = false;
        if (continuousSamplingTimeout) {
            clearTimeout(continuousSamplingTimeout);
            continuousSamplingTimeout = null;
        }
        updateSamplingButtonState();
    }

    async function runContinuousSamplingLoop() {
        if (!isSamplingActive) return;

        try {
            await captureRoboflowSample('Beruntun');
        } catch (err) {
            console.error('Error continuous capture:', err);
        }

        // Pengambilan berikutnya berdasarkan jeda yang dipilih (default 500ms = 0.5 detik)
        if (isSamplingActive) {
            const speedEl = document.getElementById('select-sampling-speed');
            const interval = speedEl ? (parseInt(speedEl.value, 10) || 500) : 500;
            continuousSamplingTimeout = setTimeout(runContinuousSamplingLoop, interval);
        }
    }

    function startContinuousSampling() {
        isSamplingActive = true;
        updateSamplingButtonState();
        const speedEl = document.getElementById('select-sampling-speed');
        const interval = speedEl ? (parseInt(speedEl.value, 10) || 500) : 500;
        appendLog(`📸 Pengambilan sampel foto beruntun DIMULAI (Jeda: ${interval}ms / ${(interval/1000).toFixed(2)} detik per foto). Klik Berhenti kapan saja!`, '#16a34a');
        runContinuousSamplingLoop();
    }

    if (btnToggleSampling) {
        btnToggleSampling.addEventListener('click', () => {
            if (isSamplingActive) {
                stopContinuousSampling();
                appendLog('⏹ Pengambilan sampel beruntun DIHENTIKAN.', '#dc2626');
            } else {
                startContinuousSampling();
            }
        });
    }

    if (btnCaptureManualSample) {
        btnCaptureManualSample.addEventListener('click', () => {
            captureRoboflowSample('1x Manual');
        });
    }

    if (btnResetSamples) {
        btnResetSamples.addEventListener('click', async () => {
            const pName = activePart ? activePart.name : 'part ini';
            const pId = activePart ? (activePart.id || '') : '';
            if (!confirm(`Apakah Anda yakin ingin mereset dan menghapus seluruh (${sampleCount}) sampel gambar untuk ${pName}?`)) return;
            if (isSamplingActive) {
                stopContinuousSampling();
            }

            btnResetSamples.disabled = true;
            const originalResetHtml = btnResetSamples.innerHTML;
            btnResetSamples.innerText = 'Mereset...';

            try {
                const res = await fetch('/api/reset-roboflow-samples', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ part_id: pId })
                });
                const resData = await res.json().catch(() => null);

                sampleCount = 0;
                updateSamplingButtonState();
                updateSampleUI();

                if (samplePreviewContainer) {
                    samplePreviewContainer.innerHTML = '<div id="sample-empty-notice" style="font-size:12.5px; color:#94a3b8; font-style:italic;">Belum ada sampel foto yang diambil. Klik \'Mulai Ambil Sampel\' untuk mengambil foto beruntun.</div>';
                }

                // Verifikasi ulang dari server
                await loadRoboflowSamplesStats(pId);

                appendLog(`↺ Seluruh sampel foto Roboflow untuk ${pName} berhasil direset (0 foto).`, '#dc2626');
                alert(`✅ Seluruh sampel foto untuk ${pName} berhasil direset menjadi 0.`);
            } catch (e) {
                alert('Gagal mereset sampel: ' + e.message);
            } finally {
                btnResetSamples.disabled = false;
                btnResetSamples.innerHTML = originalResetHtml;
            }
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
                    if (activePartModelInfo) activePartModelInfo.innerText = activePart.model_id ? `Roboflow: ${activePart.model_id}` : 'Roboflow: Belum Dikonfigurasi';
                    if (badgeModelVersion) badgeModelVersion.innerText = `v${activePart.model_version}`;
                    if (labelKolektorPartName) labelKolektorPartName.innerText = `[${activePart.code}] ${activePart.name}`;

                    // Update Card 1 Profile
                    if (cardPartName) cardPartName.innerText = activePart.name;
                    if (cardPartCode) cardPartCode.innerText = activePart.code;
                    if (cardModelSlug) cardModelSlug.innerText = activePart.model_id || '(Belum Dikonfigurasi)';
                    if (cardModelVersion) cardModelVersion.innerText = `v${activePart.model_version}`;
                    if (cardPartTarget) cardPartTarget.innerText = activePart.target_per_hanger;

                    // Sync Manual Inputs
                    const inModel = document.getElementById('rf-model-input');
                    const inVer = document.getElementById('rf-version-input');
                    const inKey = document.getElementById('rf-key-input');
                    if (inModel) inModel.value = activePart.model_id || '';
                    if (inVer) inVer.value = activePart.model_version || 1;
                    if (inKey) inKey.value = activePart.publishable_key || '';

                    // Update Download Dataset ZIP Link
                    const btnZip = document.getElementById('btn-download-dataset-zip');
                    if (btnZip) {
                        btnZip.href = `/api/download-roboflow-zip?part_id=${encodeURIComponent(activePart.id)}`;
                    }

                    // Reload samples count for this part
                    loadRoboflowSamplesStats(activePart.id);

                    // Auto load model for active part
                    if (activePart.has_local_model) {
                        isOfflineAiReady = true;
                        if (activePartModelInfo) activePartModelInfo.innerText = `AI Offline: ${activePart.model_file}`;
                        const badgeModel = document.getElementById('badge-ai-model-ready');
                        if (badgeModel) {
                            badgeModel.innerText = `🟢 Model Offline Siap`;
                            badgeModel.title = `Model offline ${activePart.model_file} (${activePart.model_size_mb || 42} MB) aktif & siap mendeteksi 100% lokal`;
                            badgeModel.style.background = '#dcfce7';
                            badgeModel.style.color = '#15803d';
                            badgeModel.style.cursor = 'default';
                            badgeModel.onclick = null;
                        }
                        const errBox = document.getElementById('ai-model-error-hint');
                        if (errBox) errBox.style.display = 'none';
                        if (statusModel) {
                            statusModel.innerText = `Model Offline Siap (${activePart.model_file})`;
                            statusModel.style.color = '#16a34a';
                        }
                        checkEnableDetectButton();
                        appendLog(`🤖 Model AI Offline [${activePart.model_file}] aktif & siap mendeteksi 100% lokal.`, '#16a34a');
                    } else if (reloadModel && activePart.model_id && activePart.publishable_key && !activePart.model_id.startsWith('offline_')) {
                        initRoboflowModel(activePart.model_id, activePart.model_version, activePart.publishable_key, true);
                    } else if (reloadModel) {
                        isOfflineAiReady = false;
                        initRoboflowModel('', 1, '', true);
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

            const hasModel = p.has_local_model;
            const modelBadge = hasModel 
                ? `<span style="font-size:11.5px; font-weight:700; background:#dcfce7; color:#15803d; padding:3px 9px; border-radius:6px; font-family:monospace;">🟢 ${p.model_file} (${p.model_size_mb || 42} MB)</span>`
                : `<span style="font-size:11.5px; font-weight:700; background:#fee2e2; color:#dc2626; padding:3px 9px; border-radius:6px; font-family:monospace;">🔴 Belum Upload</span>`;

            tr.innerHTML = `
                <td style="padding:10px 14px;">
                    <div style="font-weight:700; color:#0f172a;">${p.name}</div>
                    <span style="font-size:10.5px; font-weight:800; background:#e2e8f0; color:#475569; padding:1px 6px; border-radius:4px; font-family:monospace;">${p.code}</span>
                </td>
                <td style="padding:10px 14px;">
                    ${modelBadge}
                </td>
                <td style="padding:10px 14px; text-align:center; font-weight:700; color:#16a34a;">${p.target_per_hanger} pcs</td>
                <td style="padding:10px 14px; text-align:center;">
                    ${isActive ? '<span style="font-size:11px; font-weight:700; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:12px;">● Aktif</span>' : '<span style="color:#94a3b8; font-size:11px;">Standby</span>'}
                </td>
                <td style="padding:10px 14px; text-align:center;">
                    <div style="display:inline-flex; align-items:center; gap:6px;">
                        <button type="button" class="btn-edit-part" data-id="${p.id}" style="background:#f0f9ff; border:1px solid #bae6fd; color:#0284c7; cursor:pointer; font-weight:700; font-size:12px; padding:5px 10px; border-radius:6px; display:inline-flex; align-items:center; gap:4px; transition:all 0.15s;" title="Edit Part" onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#f0f9ff'">✏️ Edit</button>
                        <button type="button" class="btn-delete-part" data-id="${p.id}" data-name="${p.name}" data-is-active="${isActive ? '1' : '0'}" style="background:#fef2f2; border:1px solid #fecaca; color:#dc2626; cursor:pointer; font-weight:700; font-size:12px; padding:5px 10px; border-radius:6px; display:inline-flex; align-items:center; gap:4px; transition:all 0.15s;" title="Hapus Part" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">🗑️ Hapus</button>
                    </div>
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
                    document.getElementById('part-form-target').value = part.target_per_hanger || 15;
                    const modelInfoBox = document.getElementById('part-form-model-info');
                    const modelFileEl = document.getElementById('current-model-filename');
                    const modelSizeEl = document.getElementById('current-model-size');
                    if (part.has_local_model) {
                        modelFileEl.innerText = part.model_file;
                        modelSizeEl.innerText = `${part.model_size_mb || 42} MB`;
                        modelInfoBox.style.display = 'flex';
                    } else {
                        modelInfoBox.style.display = 'none';
                    }
                    const inputF = document.getElementById('part-form-file');
                    if (inputF) inputF.value = '';
                    const lblF = document.getElementById('label-selected-model-file');
                    if (lblF) {
                        lblF.innerText = 'Pilih file baru jika ingin mengganti model';
                        lblF.style.color = '#64748b';
                        lblF.style.fontWeight = 'normal';
                    }
                    document.getElementById('form-part-title').innerText = `Edit Part: ${part.name}`;
                }
            });
        });

        // Event listener Delete
        tbody.querySelectorAll('.btn-delete-part').forEach(b => {
            b.addEventListener('click', () => {
                const id = b.dataset.id;
                const name = b.dataset.name;
                const isActive = b.dataset.isActive === '1';
                tampilkanKonfirmasiHapusPart(id, name, isActive);
            });
        });
    }

    /**
     * Modal Konfirmasi Hapus Part
     */
    function tampilkanKonfirmasiHapusPart(partId, partName, isActive = false) {
        // Cek apakah modal sudah ada, jika belum buat
        let modal = document.getElementById('modal-konfirmasi-hapus-part');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'modal-konfirmasi-hapus-part';
            modal.style.cssText = 'display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.65); z-index:10001; justify-content:center; align-items:center; backdrop-filter:blur(3px);';
            modal.innerHTML = `
                <div style="background:#ffffff; border-radius:14px; max-width:480px; width:92%; padding:28px; box-shadow:0 20px 40px rgba(0,0,0,0.25); border:1px solid #e2e8f0; animation:modalSlide 0.25s ease-out;">
                    <div style="display:flex; align-items:center; gap:14px; margin-bottom:18px;">
                        <div style="width:48px; height:48px; border-radius:12px; background:#fef2f2; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1.5px solid #fecaca;">
                            <span style="font-size:24px;">⚠️</span>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:17px; color:#0f172a; font-weight:800;">Konfirmasi Hapus Part</h3>
                            <div style="font-size:13px; color:#64748b; margin-top:3px;">Tindakan ini tidak dapat dibatalkan</div>
                        </div>
                    </div>

                    <div id="hapus-part-warning-active" style="display:none; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:12.5px; color:#92400e; line-height:1.5;">
                        ⚠️ <strong>Peringatan Part Aktif:</strong> Part ini sedang <strong>Aktif Diproduksi</strong>. Jika dihapus, sistem akan mengosongkan antrean part aktif atau mengalihkan ke part berikutnya yang tersedia di katalog.
                    </div>

                    <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px 16px; margin-bottom:18px;">
                        <div style="font-size:13px; color:#991b1b; line-height:1.6;">
                            Anda akan menghapus part <strong id="hapus-part-nama" style="color:#dc2626;">-</strong> dari katalog.
                            <br>
                            <span style="font-size:12px; color:#b91c1c;">• Data profil part akan dihapus permanen<br>• File model AI (.pt / .onnx) juga akan dihapus dari server</span>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" id="btn-batal-hapus-part" style="padding:9px 18px; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:7px; font-weight:700; font-size:13px; cursor:pointer; transition:all 0.15s;">
                            Batal
                        </button>
                        <button type="button" id="btn-konfirmasi-hapus-part" style="padding:9px 18px; background:#dc2626; color:white; border:none; border-radius:7px; font-weight:700; font-size:13px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all 0.15s;">
                            🗑️ Ya, Hapus Part
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            document.getElementById('btn-batal-hapus-part').addEventListener('click', () => {
                modal.style.display = 'none';
            });
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.style.display = 'none';
            });
        }

        // Isi nama part & status warning
        document.getElementById('hapus-part-nama').innerText = partName;
        const warnActive = document.getElementById('hapus-part-warning-active');
        if (warnActive) warnActive.style.display = isActive ? 'block' : 'none';
        modal.style.display = 'flex';

        const btnKonfirmasi = document.getElementById('btn-konfirmasi-hapus-part');
        // Clone untuk hapus event listener lama
        const newBtn = btnKonfirmasi.cloneNode(true);
        btnKonfirmasi.parentNode.replaceChild(newBtn, btnKonfirmasi);

        newBtn.addEventListener('click', async () => {
            newBtn.disabled = true;
            newBtn.innerHTML = '⏳ Menghapus...';

            try {
                const res = await fetch(`${APP_BASE_PATH}/api/delete-part`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ id: partId })
                });
                const data = await res.json();

                if (data.status === 'ok') {
                    modal.style.display = 'none';
                    // Reset form edit jika part yang dihapus sedang dibuka di form
                    const formIdInput = document.getElementById('part-form-id');
                    if (formIdInput && formIdInput.value === partId) {
                        const resetBtn = document.getElementById('btn-reset-part-form');
                        if (resetBtn) resetBtn.click();
                    }
                    // Refresh katalog
                    await loadPartsCatalog(false);
                    // Tampilkan notifikasi sukses
                    tampilkanNotifikasiHapusPart('success', data.message || `Part '${partName}' berhasil dihapus`);
                } else {
                    tampilkanNotifikasiHapusPart('error', data.message || 'Gagal menghapus part');
                    modal.style.display = 'none';
                }
            } catch (e) {
                tampilkanNotifikasiHapusPart('error', 'Terjadi kesalahan koneksi saat menghapus part');
                modal.style.display = 'none';
            } finally {
                newBtn.disabled = false;
                newBtn.innerHTML = '🗑️ Ya, Hapus Part';
            }
        });
    }

    function tampilkanNotifikasiHapusPart(type, message) {
        const notif = document.createElement('div');
        const isError = type === 'error';
        notif.style.cssText = `position:fixed; top:20px; right:20px; z-index:10002; padding:14px 20px; border-radius:10px; font-size:13.5px; font-weight:700; box-shadow:0 8px 24px rgba(0,0,0,0.18); display:flex; align-items:center; gap:10px; animation:modalSlide 0.3s ease-out; max-width:420px; background:${isError ? '#fef2f2' : '#f0fdf4'}; border:1.5px solid ${isError ? '#fecaca' : '#bbf7d0'}; color:${isError ? '#991b1b' : '#166534'};`;
        notif.innerHTML = `<span style="font-size:18px;">${isError ? '❌' : '✅'}</span> <span>${message}</span>`;
        document.body.appendChild(notif);
        setTimeout(() => {
            notif.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            notif.style.opacity = '0';
            notif.style.transform = 'translateY(-10px)';
            setTimeout(() => notif.remove(), 400);
        }, 3500);
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
    const btnQuickManageParts = document.getElementById('btn-quick-manage-parts');
    if (btnOpenChangePart) btnOpenChangePart.addEventListener('click', openChangeoverModal);
    if (btnQuickManageParts) {
        btnQuickManageParts.addEventListener('click', () => {
            renderPartsTable();
            if (modalManageParts) modalManageParts.style.display = 'flex';
        });
    }

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

    const btnBrowseModel = document.getElementById('btn-browse-model-file');
    const inputModelFile = document.getElementById('part-form-file');
    const labelSelectedFile = document.getElementById('label-selected-model-file');
    if (btnBrowseModel && inputModelFile) {
        btnBrowseModel.addEventListener('click', () => inputModelFile.click());
        inputModelFile.addEventListener('change', () => {
            if (inputModelFile.files && inputModelFile.files[0]) {
                const file = inputModelFile.files[0];
                const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
                labelSelectedFile.innerText = `📄 ${file.name} (${sizeMb} MB) dipilih`;
                labelSelectedFile.style.color = '#15803d';
                labelSelectedFile.style.fontWeight = '700';
            }
        });
    }

    const btnResetPartForm = document.getElementById('btn-reset-part-form');
    if (btnResetPartForm) {
        btnResetPartForm.addEventListener('click', () => {
            document.getElementById('part-form-id').value = '';
            document.getElementById('part-form-code').value = '';
            document.getElementById('part-form-name').value = '';
            document.getElementById('part-form-target').value = '15';
            if (inputModelFile) inputModelFile.value = '';
            const modelInfoBox = document.getElementById('part-form-model-info');
            if (modelInfoBox) modelInfoBox.style.display = 'none';
            if (labelSelectedFile) {
                labelSelectedFile.innerText = 'Belum ada file baru dipilih';
                labelSelectedFile.style.color = '#64748b';
                labelSelectedFile.style.fontWeight = 'normal';
            }
            const progressContainer = document.getElementById('part-upload-progress-container');
            if (progressContainer) progressContainer.style.display = 'none';
            document.getElementById('form-part-title').innerText = 'Tambah Part Baru ke Katalog';
        });
    }

    // Event listener Hapus File Model AI Saja
    const btnDeleteModelFile = document.getElementById('btn-delete-model-file');
    if (btnDeleteModelFile) {
        btnDeleteModelFile.addEventListener('click', async () => {
            const id = document.getElementById('part-form-id').value;
            const name = document.getElementById('part-form-name').value || 'part ini';
            if (!id) return;
            if (!confirm(`Yakin ingin menghapus file model AI (.pt / .onnx) untuk part '${name}' dari server?\n\nProfil part tetap tersimpan di katalog, Anda dapat mengupload model baru kapan saja.`)) return;

            btnDeleteModelFile.disabled = true;
            btnDeleteModelFile.innerText = '⏳ Menghapus...';
            try {
                const res = await fetch(`${APP_BASE_PATH}/api/delete-part-model`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.status === 'ok') {
                    const modelInfoBox = document.getElementById('part-form-model-info');
                    if (modelInfoBox) modelInfoBox.style.display = 'none';
                    tampilkanNotifikasiHapusPart('success', data.message || 'File model AI berhasil dihapus');
                    await loadPartsCatalog(false);
                } else {
                    tampilkanNotifikasiHapusPart('error', data.message || 'Gagal menghapus file model');
                }
            } catch (e) {
                tampilkanNotifikasiHapusPart('error', 'Terjadi kesalahan saat menghapus model');
            } finally {
                btnDeleteModelFile.disabled = false;
                btnDeleteModelFile.innerText = '🗑️ Hapus Model Saja';
            }
        });
    }

    const btnSubmitPartForm = document.getElementById('btn-submit-part-form');
    if (btnSubmitPartForm) {
        btnSubmitPartForm.addEventListener('click', () => {
            const id = document.getElementById('part-form-id').value;
            const code = document.getElementById('part-form-code').value.trim();
            const name = document.getElementById('part-form-name').value.trim();
            const target_per_hanger = parseInt(document.getElementById('part-form-target').value, 10) || 15;
            const fileInput = document.getElementById('part-form-file');

            if (!code || !name) {
                alert('Kode Part dan Nama Part wajib diisi!');
                return;
            }

            const progressContainer = document.getElementById('part-upload-progress-container');
            const progressBar = document.getElementById('part-upload-progress-bar');
            const progressText = document.getElementById('part-upload-progress-text');
            const progressPercent = document.getElementById('part-upload-progress-percent');
            const progressBytes = document.getElementById('part-upload-progress-bytes');
            const progressSpeed = document.getElementById('part-upload-progress-speed');

            const hasFile = fileInput && fileInput.files && fileInput.files[0];
            const fileSizeMB = hasFile ? (fileInput.files[0].size / 1048576).toFixed(1) : 0;

            btnSubmitPartForm.disabled = true;
            btnSubmitPartForm.innerText = hasFile ? 'Mengupload Model...' : 'Menyimpan...';

            if (progressContainer) {
                progressContainer.style.display = hasFile ? 'block' : 'none';
                if (progressBar) progressBar.style.width = '0%';
                if (progressPercent) progressPercent.innerText = '0%';
                if (progressText) progressText.innerText = `Mengupload ${fileInput.files[0].name} (${fileSizeMB} MB)...`;
                if (progressBytes) progressBytes.innerText = `0.0 MB / ${fileSizeMB} MB`;
                if (progressSpeed) progressSpeed.innerText = 'Memulai proses upload...';
            }

            // Tandai status upload besar agar background polling dijeda sementara
            if (hasFile) window.isUploadingLargeFile = true;

            const formData = new FormData();
            if (id) formData.append('id', id);
            formData.append('code', code);
            formData.append('name', name);
            formData.append('target_per_hanger', target_per_hanger);
            if (hasFile) {
                formData.append('model_file', fileInput.files[0]);
            }

            const xhr = new XMLHttpRequest();
            const startTime = Date.now();

            if (xhr.upload && hasFile) {
                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable && e.total > 0) {
                        const pct = Math.min(100, Math.round((e.loaded / e.total) * 100));
                        const loadedMB = (e.loaded / 1048576).toFixed(1);
                        const totalMB = (e.total / 1048576).toFixed(1);
                        const elapsedSec = (Date.now() - startTime) / 1000;
                        const speedMBs = elapsedSec > 0.3 ? ((e.loaded / 1048576) / elapsedSec).toFixed(1) : '-';

                        if (progressBar) progressBar.style.width = pct + '%';
                        if (progressPercent) progressPercent.innerText = pct + '%';
                        if (progressBytes) progressBytes.innerText = `${loadedMB} MB / ${totalMB} MB`;
                        if (progressSpeed) progressSpeed.innerText = `Kecepatan: ${speedMBs} MB/s`;

                        if (pct >= 100) {
                            if (progressText) progressText.innerText = 'File terkirim! Menyimpan ke storage server...';
                            if (progressSpeed) progressSpeed.innerText = 'Menyelesaikan penyimpanan...';
                            btnSubmitPartForm.innerText = 'Menyimpan di server...';
                        } else {
                            btnSubmitPartForm.innerText = `Mengupload (${pct}%)...`;
                        }
                    }
                };
            }

            xhr.onload = async () => {
                window.isUploadingLargeFile = false;
                btnSubmitPartForm.disabled = false;
                btnSubmitPartForm.innerText = '💾 Simpan Part';

                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data && data.status === 'ok') {
                            if (progressContainer) progressContainer.style.display = 'none';
                            btnResetPartForm.click();
                            await loadPartsCatalog(true);
                            appendLog(`💾 Part [${code}] ${name} berhasil disimpan ke katalog offline.`, '#16a34a');
                            alert(`✅ Part '${name}' [${code}] berhasil disimpan!\nModel AI Offline (${fileSizeMB > 0 ? fileSizeMB + ' MB' : 'siap'}) kini aktif.`);
                            return;
                        } else {
                            throw new Error(data.message || 'Gagal menyimpan part');
                        }
                    } catch (parseErr) {
                        alert('Respon server tidak valid: ' + parseErr.message);
                    }
                } else {
                    let errMsg = `HTTP Error ${xhr.status}`;
                    try {
                        const errObj = JSON.parse(xhr.responseText);
                        if (errObj && errObj.message) errMsg = errObj.message;
                    } catch (e) {}
                    alert('Gagal menyimpan part: ' + errMsg);
                }
            };

            xhr.onerror = () => {
                window.isUploadingLargeFile = false;
                btnSubmitPartForm.disabled = false;
                btnSubmitPartForm.innerText = '💾 Simpan Part';
                if (progressContainer) progressContainer.style.display = 'none';
                alert('Gagal menghubungi server saat upload. Periksa koneksi lokal Anda.');
            };

            xhr.open('POST', '/api/save-part', true);
            xhr.send(formData);
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
    let lastEspSignalTime = Date.now();
    let isPollingSensor = false;

    async function pollSensorTrigger() {
        if (!chkAutoTrigger.checked || window.isUploadingLargeFile || isPollingSensor) return;
        isPollingSensor = true;

        try {
            const res = await fetch('/api/get-sensor-trigger');
            const data = await res.json();
            const trig = data ? data.trigger : null;

            if (trig && trig.trigger_id && trig.trigger_id !== lastProcessedTriggerId) {
                lastProcessedTriggerId = trig.trigger_id;
                lastEspSignalTime = Date.now(); // Perbarui waktu sinyal ESP terakhir

                // Event Tombol Hardware PB ON (Start)
                if (trig.type === 'btn_on') {
                    updateMesinUI(true, true);
                    appendLog('🚀 [Hardware PB ON] Tombol START Ditekan: Status Produksi Aktif (Nilai 1). Photosensor kini aktif!', '#16a34a');
                }
                // Event Tombol Hardware PB OFF (Stop)
                else if (trig.type === 'btn_off') {
                    updateMesinUI(false, true);
                    appendLog('🛑 [Hardware PB OFF] Tombol STOP Ditekan: Status Produksi Dihentikan (Nilai 0). Photosensor standby.', '#dc2626');
                }
                // Event Sinyal Heartbeat dari ESP32
                else if (trig.type === 'heartbeat') {
                    const isRunning = (trig.val == 1);
                    updateMesinUI(isRunning, true);
                }
                // Event Photosensor 1 (Inlet / Hanger Masuk - Pin 27) - Buka Sesi Inspeksi
                else if (trig.type === 'input') {
                    updateMesinUI(true, true);
                    dotSensorInput.style.background = '#22c55e';
                    dotSensorInput.style.boxShadow = '0 0 12px #22c55e';
                    textSensorInput.innerText = 'TRIGGER DETECTED!';
                    textSensorInput.style.color = '#16a34a';

                    const durText = trig.duration_ms ? ` (${trig.duration_ms}ms)` : '';
                    appendLog(`🚪 [Sensor Masuk Box - Pin 27] Hanger Masuk${durText} -> Buka Sesi Inspeksi & Mulai Melacak Part...`, '#16a34a');
                    loadHangerStats();

                    // --- BUKA JENDELA INSPEKSI GATED WINDOW (PIN 27: MULAI HITUNG) ---
                    isInspectionActive = true;
                    inspectionTrackedPartIds.clear();
                    inspectionMaxFrameParts = 0;
                    inspectionStartTime = Date.now();
                    inspectionDetectedClasses = {};

                    // Reset tracker AI Daemon via Laravel agar ID part baru mulai dari awal
                    fetch('/api/reset-ai-tracker', { method: 'POST' }).catch(() => {});

                    // Tampilkan badge inspeksi aktif di atas video
                    if (badgeInspection && badgeInspectionText) {
                        badgeInspection.style.display = 'inline-flex';
                        badgeInspection.style.background = 'rgba(16,185,129,0.92)';
                        badgeInspectionText.innerText = '🔍 INSPEKSI AKTIF: MULAI MELACAK...';
                    }

                    setTimeout(() => {
                        dotSensorInput.style.background = '#ef4444';
                        dotSensorInput.style.boxShadow = '0 0 8px rgba(239,68,68,0.5)';
                        textSensorInput.innerText = 'Standby';
                        textSensorInput.style.color = '#dc2626';
                    }, 1200);

                }
                // Event Photosensor 2 (Outlet / Shutter Kamera - Pin 25) - Tutup Sesi & Finalisasi
                else if (trig.type === 'output' || trig.type === 'output_trigger') {
                    updateMesinUI(true, true);
                    dotSensorOutput.style.background = '#22c55e';
                    dotSensorOutput.style.boxShadow = '0 0 12px #22c55e';
                    textSensorOutput.innerText = 'ROBOFLOW CONFIRMED!';
                    textSensorOutput.style.color = '#16a34a';

                    const durText = trig.duration_ms ? ` (${trig.duration_ms}ms)` : '';
                    appendLog(`📸 [Sensor Keluar Box - Pin 25] Hanger Keluar${durText} -> Finalisasi Hasil Inspeksi!`, '#16a34a');

                    // Efek Rana / Flash Kamera di Layar (Border Hijau)
                    videoContainer.style.borderColor = '#22c55e';
                    shutterFlash.style.display = 'block';
                    setTimeout(() => {
                        shutterFlash.style.display = 'none';
                        videoContainer.style.borderColor = '#1e293b';
                    }, 200);

                    // Jalankan deteksi konfirmasi otomatis (mode shutter/output)
                    try {
                        await runObjectDetection(true);
                    } catch (errDet) {
                        console.warn('runObjectDetection warning:', errDet);
                    }

                    setTimeout(() => {
                        dotSensorOutput.style.background = '#ef4444';
                        dotSensorOutput.style.boxShadow = '0 0 8px rgba(239,68,68,0.5)';
                        textSensorOutput.innerText = 'Standby';
                        textSensorOutput.style.color = '#dc2626';
                    }, 1200);
                }
            }
        } catch (e) {
        } finally {
            isPollingSensor = false;
        }
    }

    // Jalankan polling trigger sensor setiap 350ms
    triggerPollInterval = setInterval(pollSensorTrigger, 350);

</script>
