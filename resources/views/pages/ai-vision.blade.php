<div id="view-ai-vision" style="{{ $view_aktif !== 'ai-vision' ? 'display:none;' : '' }}">
    <div class="panel">
        <div class="panel-header-row">
            <div class="panel-title">Pengaturan ESP32-CAM & AI Vision (Object Detection Hanger)</div>
        </div>
        <div class="settings-row-desc" style="margin-bottom:16px;">
            Sistem deteksi visual dan counter otomatis untuk part/barang pada conveyor hanger Line Painting menggunakan model <strong>Roboflow Object Detection</strong> yang terintegrasi dengan <strong>RS-485 Photosensor Input & Output</strong>.
        </div>

        <!-- Banner Sinkronisasi Konfigurasi ESP32 & RS-485 Status -->
        <div style="background:#f8fafc; border:1.5px solid #dbe4f3; border-radius:10px; padding:16px; margin-bottom:20px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                <div>
                    <h3 style="margin-top:0; margin-bottom:6px; font-size:15px; color:#1e293b; font-weight:700;">Konfigurasi Master & Jalur RS-485</h3>
                    <p style="font-size:13px; color:#64748b; margin:0; line-height:1.5;">
                        ESP32 membaca sensor melalui <strong>RS-485 to TTL</strong>. Photosensor 1 menandai hanger masuk, Photosensor 2 memicu kamera mengonfirmasi jumlah pcs barang.
                    </p>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <a href="http://master-painting.test/cctv" target="_blank" class="btn-simpan-produksi" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#0d3b8c; color:white; border-radius:6px; font-weight:600; font-size:12.5px;">
                        ⚙️ Master Painting &rarr;
                    </a>
                    <button type="button" id="btn-selesai-konfigurasi" class="btn-simpan-produksi" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#16a34a; color:white; border:none; border-radius:6px; font-weight:600; font-size:12.5px; cursor:pointer;">
                        🔄 Sinkronkan IP Kamera
                    </button>
                </div>
            </div>

            <!-- Status Indikator Realtime RS-485 Photosensor (Hardware Monitoring) -->
            <div style="margin-top:14px; padding-top:12px; border-top:1px dashed #cbd5e1; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                    <!-- Indikator Sensor 1 (Input) -->
                    <div style="display:flex; align-items:center; gap:8px; background:#ffffff; border:1px solid #e2e8f0; padding:6px 12px; border-radius:8px;">
                        <span id="dot-sensor-input" style="width:10px; height:10px; border-radius:50%; background:#94a3b8; display:inline-block; transition:all 0.3s;"></span>
                        <span style="font-size:12px; font-weight:700; color:#334155;">Photosensor 1 (Input):</span>
                        <span id="status-sensor-input-text" style="font-size:11.5px; color:#64748b; font-family:monospace;">Standby</span>
                    </div>

                    <!-- Indikator Sensor 2 (Output) -->
                    <div style="display:flex; align-items:center; gap:8px; background:#ffffff; border:1px solid #e2e8f0; padding:6px 12px; border-radius:8px;">
                        <span id="dot-sensor-output" style="width:10px; height:10px; border-radius:50%; background:#94a3b8; display:inline-block; transition:all 0.3s;"></span>
                        <span style="font-size:12px; font-weight:700; color:#334155;">Photosensor 2 (Output Shutter):</span>
                        <span id="status-sensor-output-text" style="font-size:11.5px; color:#64748b; font-family:monospace;">Standby</span>
                    </div>
                </div>

                <!-- Tombol Simulasi Trigger (Sangat berguna untuk pengujian tanpa conveyor fisik) -->
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:11px; font-weight:600; color:#64748b;">Simulasi Trigger:</span>
                    <button type="button" id="btn-sim-input" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#0f172a; padding:5px 9px; border-radius:6px; font-size:11.5px; font-weight:600; cursor:pointer;" title="Simulasikan Hanger Lewat di Sensor Input">
                        ⚡ Tes Sensor Input
                    </button>
                    <button type="button" id="btn-sim-output" style="background:#eff6ff; border:1px solid #93c5fd; color:#1d4ed8; padding:5px 9px; border-radius:6px; font-size:11.5px; font-weight:600; cursor:pointer;" title="Simulasikan Hanger Lewat di Sensor Output & Picu Kamera">
                        📸 Tes Sensor Output (Shutter)
                    </button>
                </div>
            </div>
        </div>

        <div style="display:flex; gap:22px; flex-wrap:wrap;">
            <!-- KOLOM KIRI: Layar Video Stream & Live Counter KPI -->
            <div style="flex:1.3; min-width:340px; display:flex; flex-direction:column; gap:16px;">
                
                <!-- Layar Video Stream ESP32-CAM dengan Canvas Bounding Box -->
                <div id="ai-video-container" style="background:#0b1120; border:2px solid #1e293b; border-radius:12px; overflow:hidden; position:relative; min-height:360px; display:flex; align-items:center; justify-content:center; box-shadow:0 6px 24px rgba(0,0,0,0.25); transition:border-color 0.3s;">
                    
                    <!-- Placeholder saat Stream Mati -->
                    <div id="ai-video-placeholder" style="color:#94a3b8; font-size:13.5px; text-align:center; padding:28px; display:flex; flex-direction:column; align-items:center; gap:10px;">
                        <span style="font-size:42px;">📷</span>
                        <span style="font-weight:700; color:#f1f5f9; font-size:15px;">Layar Kamera Live ESP32-CAM (Output)</span>
                        <span style="font-size:12px; color:#64748b; max-width:320px; line-height:1.4;">
                            Stream belum aktif. Klik tombol <strong style="color:#38bdf8;">'Buka Stream'</strong> di panel samping untuk memulai tayangan.
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
                            <span id="ai-stream-badge" style="background:rgba(15,23,42,0.85); color:#4ade80; border:1px solid rgba(74,222,128,0.3); font-size:11px; font-weight:700; padding:4px 9px; border-radius:6px; font-family:ui-monospace, monospace; backdrop-filter:blur(4px); display:flex; align-items:center; gap:6px;">
                                <span style="width:7px; height:7px; border-radius:50%; background:#22c55e; animation:lampKedip 1.2s infinite;"></span>
                                LIVE OUTPUT CAM
                            </span>
                            <span id="ai-inference-time-badge" style="background:rgba(15,23,42,0.85); color:#38bdf8; border:1px solid rgba(56,189,248,0.3); font-size:11px; font-weight:700; padding:4px 9px; border-radius:6px; font-family:ui-monospace, monospace; backdrop-filter:blur(4px); display:none;">
                                0 ms
                            </span>
                        </div>

                        <!-- Flash Shutter Banner saat Sensor Output Trigger -->
                        <div id="ai-shutter-flash" style="display:none; position:absolute; inset:0; background:rgba(255,255,255,0.35); pointer-events:none; z-index:10;"></div>
                    </div>
                </div>

                <!-- 4 KPI Box: Live Conveyor & Hanger Flow -->
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:12px;">
                    
                    <!-- Kartu 1: WIP Hanger (Sedang di dalam line oven/booth) -->
                    <div style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:10px; padding:14px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                        <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                            Hanger di Line (WIP)
                        </div>
                        <div style="display:flex; align-items:baseline; gap:6px;">
                            <span id="kpi-wip-hangers" style="font-size:30px; font-weight:800; color:#f59e0b; line-height:1;">0</span>
                            <span style="font-size:13px; font-weight:600; color:#64748b;">Hanger</span>
                        </div>
                        <div style="font-size:11px; color:#d97706; font-weight:600; margin-top:4px;">
                            Marked oleh Sensor 1
                        </div>
                    </div>

                    <!-- Kartu 2: Part di Hanger Output Saat Ini -->
                    <div style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:10px; padding:14px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                        <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                            Part di Hanger Ini
                        </div>
                        <div style="display:flex; align-items:baseline; gap:6px;">
                            <span id="kpi-current-parts" style="font-size:30px; font-weight:800; color:#0d3b8c; line-height:1;">0</span>
                            <span style="font-size:13px; font-weight:600; color:#64748b;">Pcs</span>
                        </div>
                        <div style="font-size:11px; color:#16a34a; font-weight:600; margin-top:4px;">
                            Deteksi Kamera Output
                        </div>
                    </div>

                    <!-- Kartu 3: Total Hanger Selesai (Output) -->
                    <div style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:10px; padding:14px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                        <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                            Hanger Selesai
                        </div>
                        <div style="display:flex; align-items:baseline; gap:6px;">
                            <span id="kpi-output-hangers" style="font-size:30px; font-weight:800; color:#0f172a; line-height:1;">0</span>
                            <span style="font-size:13px; font-weight:600; color:#64748b;">Hanger</span>
                        </div>
                        <div style="font-size:11px; color:#64748b; margin-top:4px;">
                            Terkonfirmasi Sensor 2
                        </div>
                    </div>

                    <!-- Kartu 4: Total Output Aktual Terkonfirmasi (PCS) -->
                    <div style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:10px; padding:14px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Total Output</span>
                            <button type="button" id="btn-reset-counter" style="background:none; border:none; color:#ef4444; font-size:11px; cursor:pointer; font-weight:600; text-decoration:underline;">Reset</button>
                        </div>
                        <div style="display:flex; align-items:baseline; gap:6px;">
                            <span id="kpi-total-parts" style="font-size:30px; font-weight:800; color:#16a34a; line-height:1;">0</span>
                            <span style="font-size:13px; font-weight:600; color:#64748b;">Pcs</span>
                        </div>
                        <div style="font-size:11px; color:#16a34a; font-weight:600; margin-top:4px;">
                            Aktual Roboflow AI
                        </div>
                    </div>
                </div>

                <!-- Info Log Alur Kerja Konveyor -->
                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px 14px; font-size:12.5px; color:#1e40af; line-height:1.45;">
                    💡 <strong>Alur Sistem RS-485:</strong> Saat Photosensor 1 mendeteksi hanger masuk, antrean <em>WIP Hanger</em> bertambah. Begitu hanger keluar di depan kamera dan memicu Photosensor 2, sistem otomatis mem-freeze frame, menghitung jumlah barang via Roboflow, dan menambahkan jumlah pcs ke <em>Total Output</em>!
                </div>
            </div>
            
            <!-- KOLOM KANAN: Pengaturan Model Roboflow & Kontrol Stream -->
            <div style="flex:1; min-width:320px; display:flex; flex-direction:column; gap:16px;">
                
                <!-- CARD 1: Konfigurasi Model Roboflow -->
                <div style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:10px; padding:16px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                        <span style="font-size:18px;">🤖</span>
                        <h4 style="margin:0; font-size:14px; color:#0d3b8c; font-weight:700;">Konfigurasi Model Roboflow</h4>
                    </div>

                    <!-- Model ID / Project Slug -->
                    <div style="margin-bottom:12px;">
                        <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:4px;">
                            Model ID (Project Slug) <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="text" id="rf-model-input" class="filter-select" style="width:100%; padding:9px 12px; font-size:13px;" placeholder="contoh: hanger-painting-counter">
                        <div style="font-size:11px; color:#94a3b8; margin-top:2px;">Nama project yang kamu buat di Roboflow.</div>
                    </div>

                    <div style="display:flex; gap:10px; margin-bottom:12px;">
                        <!-- Version -->
                        <div style="flex:1;">
                            <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:4px;">
                                Versi Model <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="number" id="rf-version-input" class="filter-select" style="width:100%; padding:9px 12px; font-size:13px;" placeholder="1" value="1" min="1">
                        </div>

                        <!-- Threshold Confidence Slider -->
                        <div style="flex:1.5;">
                            <label style="display:flex; justify-content:space-between; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:4px;">
                                <span>Confidence</span>
                                <span id="label-conf-val" style="color:#0d3b8c; font-weight:700;">50%</span>
                            </label>
                            <input type="range" id="rf-conf-slider" min="20" max="95" value="50" style="width:100%; accent-color:#0d3b8c; margin-top:6px;">
                        </div>
                    </div>

                    <!-- Publishable / API Key -->
                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:4px;">
                            Roboflow Publishable API Key <span style="color:#ef4444;">*</span>
                        </label>
                        <div style="position:relative; display:flex;">
                            <input type="password" id="rf-key-input" class="filter-select" style="width:100%; padding:9px 38px 9px 12px; font-size:13px; font-family:monospace;" placeholder="rf_xxxxxxxxxxxx">
                            <button type="button" id="btn-toggle-key" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; color:#64748b; cursor:pointer; font-size:14px;" title="Lihat / Sembunyikan API Key">👁️</button>
                        </div>
                        <div style="font-size:11px; color:#94a3b8; margin-top:2px;">Buka tab <em>Deploy</em> di Roboflow untuk mengambil Publishable Key.</div>
                    </div>

                    <!-- Tombol Muat Model -->
                    <button type="button" class="btn-simpan-produksi" id="btn-load-model" style="width:100%; padding:10px 16px; background:#0d3b8c; color:white; border:none; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                        📥 Muat Model Roboflow
                    </button>
                </div>

                <!-- CARD 2: Pengaturan Kamera & Stream ESP32-CAM -->
                <div style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:10px; padding:16px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                        <span style="font-size:18px;">📹</span>
                        <h4 style="margin:0; font-size:14px; color:#0d3b8c; font-weight:700;">Kamera ESP32-CAM (Output)</h4>
                    </div>

                    <!-- Input URL Stream -->
                    <div style="margin-bottom:12px;">
                        <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:4px;">URL Stream Kamera Output:</label>
                        <div style="display:flex; gap:8px;">
                            <input type="text" id="stream-url-input" class="filter-select" style="flex:1; padding:9px 12px; font-size:13px;" placeholder="http://192.168.1.89:81/stream">
                            <button type="button" class="btn-simpan-produksi" id="btn-start-stream" style="padding:9px 14px; background:#334155; color:white; border:none; border-radius:6px; font-weight:600; font-size:12.5px; cursor:pointer; white-space:nowrap;">
                                Buka Stream
                            </button>
                        </div>
                    </div>

                    <!-- Mode Pemicu Deteksi (Auto Shutter via RS-485 vs Manual Kontinu) -->
                    <div style="margin-bottom:14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px;">
                        <label style="display:flex; justify-content:space-between; align-items:center; cursor:pointer;">
                            <span style="font-size:12.5px; font-weight:700; color:#1e293b;">⚡ Auto-Trigger RS-485 Aktif</span>
                            <input type="checkbox" id="chk-auto-trigger" checked style="width:16px; height:16px; accent-color:#16a34a;">
                        </label>
                        <div style="font-size:11px; color:#64748b; margin-top:4px;">
                            Saat Photosensor 2 terpicu, sistem otomatis memotret dan menghitung jumlah part pada hanger.
                        </div>
                    </div>

                    <!-- Tombol Mulai Deteksi Kontinu -->
                    <button type="button" id="btn-start-ai" style="padding:12px 16px; width:100%; text-align:center; background:#16a34a; color:white; border:none; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; transition:all 0.2s;" disabled>
                        ▶ Mulai Deteksi Object (AI)
                    </button>
                </div>

                <!-- CARD 3: Status Sistem & Timeline Log Deteksi -->
                <div style="background:#f8fafc; border:1.5px solid #dbe4f3; border-radius:10px; padding:14px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <h4 style="margin:0; font-size:13.5px; color:#0d3b8c; font-weight:700;">Status & Riwayat Trigger</h4>
                        <span id="ai-conn-badge" style="font-size:11px; font-weight:700; background:#e2e8f0; color:#475569; padding:2px 7px; border-radius:4px;">Standby</span>
                    </div>
                    
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:12.5px;">
                        <span style="color:#64748b;">Status Model Roboflow:</span>
                        <span id="ai-model-status" style="font-weight:700; color:#b45309;">Belum Dimuat</span>
                    </div>
                    
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:12.5px;">
                        <span style="color:#64748b;">Koneksi Kamera:</span>
                        <span id="ai-cam-status" style="font-weight:700; color:#b45309;">Terputus</span>
                    </div>

                    <!-- Timeline Log Hanger -->
                    <div style="margin-top:12px; padding-top:10px; border-top:1px dashed #cbd5e1;">
                        <span style="font-size:12.5px; color:#334155; font-weight:700; display:block; margin-bottom:8px;">
                            Riwayat Event Hanger & Sensor:
                        </span>
                        <div id="ai-predictions-container" style="display:flex; flex-direction:column; gap:6px; max-height:190px; overflow-y:auto; padding-right:4px;">
                            <div style="font-size:12px; color:#94a3b8; font-style:italic;">Menunggu aktivitas sensor atau deteksi...</div>
                        </div>
                    </div>
                </div>

            </div>
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
    const btnStartStream = document.getElementById('btn-start-stream');
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

    const dotSensorInput = document.getElementById('dot-sensor-input');
    const dotSensorOutput = document.getElementById('dot-sensor-output');
    const textSensorInput = document.getElementById('status-sensor-input-text');
    const textSensorOutput = document.getElementById('status-sensor-output-text');
    const chkAutoTrigger = document.getElementById('chk-auto-trigger');

    const sliderConf = document.getElementById('rf-conf-slider');
    const labelConf = document.getElementById('label-conf-val');

    // Load Saved Settings dari LocalStorage
    const savedModel = localStorage.getItem('rf_model_id');
    const savedVersion = localStorage.getItem('rf_model_version');
    const savedKey = localStorage.getItem('rf_api_key');
    const savedStream = localStorage.getItem('rf_stream_url') || localStorage.getItem('painting_cam_output_ip') ? `http://${localStorage.getItem('painting_cam_output_ip')}:81/stream` : 'http://192.168.1.89:81/stream';
    const savedConf = localStorage.getItem('rf_confidence');

    if (savedModel) document.getElementById('rf-model-input').value = savedModel;
    if (savedVersion) document.getElementById('rf-version-input').value = savedVersion;
    if (savedKey) document.getElementById('rf-key-input').value = savedKey;
    if (savedStream) document.getElementById('stream-url-input').value = savedStream;
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

    // Sinkronisasi dengan master-painting
    document.getElementById('btn-selesai-konfigurasi').addEventListener('click', async () => {
        try {
            const res = await fetch('/api/get-cam-ip');
            const data = await res.json();
            const camIp = (data && data.ip) ? data.ip.trim() : (localStorage.getItem('painting_cam_output_ip') || '192.168.1.89');
            
            if (camIp) {
                const targetUrl = 'http://' + camIp + ':81/stream';
                document.getElementById('stream-url-input').value = targetUrl;
                btnStartStream.click();
                alert('Berhasil menyinkronkan konfigurasi kamera! URL: ' + targetUrl);
            }
        } catch (e) {
            alert('Gagal menyinkronkan kamera.');
        }
    });

    // Muat data statistik hanger saat halaman dibuka
    async function loadHangerStats() {
        try {
            const res = await fetch('/api/get-hanger-stats');
            const data = await res.json();
            if (data && data.stats) {
                kpiWipHangers.innerText = data.stats.wip_hangers || 0;
                kpiOutputHangers.innerText = data.stats.total_output_hangers || 0;
                kpiTotalParts.innerText = data.stats.total_output_pcs || 0;
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

    // 1. MUAT MODEL ROBOFLOW
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

        statusModel.innerText = 'Memuat Model...';
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
            checkEnableDetectButton();
            appendLog(`🤖 Model Roboflow [${modelId} v${version}] berhasil dimuat.`, '#16a34a');
        } catch (err) {
            console.error('Error memuat model Roboflow:', err);
            statusModel.innerText = 'Gagal memuat model!';
            statusModel.style.color = '#dc2626';
            alert('Gagal memuat model Roboflow. Pastikan Model ID, Versi, dan Publishable Key sudah benar.');
        } finally {
            btnLoadModel.disabled = false;
        }
    });

    // 2. KONTROL STREAM KAMERA
    btnStartStream.addEventListener('click', () => {
        const streamUrl = document.getElementById('stream-url-input').value.trim();
        if (!streamUrl) {
            alert('Masukkan URL Stream ESP32-CAM terlebih dahulu!');
            return;
        }

        localStorage.setItem('rf_stream_url', streamUrl);
        streamImg.src = streamUrl;

        streamImg.onload = () => {
            streamPlaceholder.style.display = 'none';
            streamWrapper.style.display = 'block';
            statusCam.innerText = 'Tersambung';
            statusCam.style.color = '#16a34a';
            
            canvasOverlay.width = streamImg.naturalWidth || streamImg.clientWidth || 640;
            canvasOverlay.height = streamImg.naturalHeight || streamImg.clientHeight || 480;

            checkEnableDetectButton();
            appendLog('📹 Stream ESP32-CAM Output berhasil tersambung.', '#16a34a');
        };

        streamImg.onerror = () => {
            statusCam.innerText = 'Error / Terputus';
            statusCam.style.color = '#dc2626';
            streamWrapper.style.display = 'none';
            streamPlaceholder.style.display = 'flex';
            streamPlaceholder.querySelector('span:nth-child(2)').innerText = '[ Gagal menyambung ke Stream Kamera ]';
            
            if (isDetecting) {
                isDetecting = false;
                updateDetectButtonState();
            }
        };
    });

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

                // Box
                ctxOverlay.strokeStyle = isShutterTrigger ? '#38bdf8' : '#22c55e';
                ctxOverlay.lineWidth = 3;
                ctxOverlay.strokeRect(left, top, width, height);

                // Label
                const labelText = `#${index + 1} ${className} ${confScore}%`;
                ctxOverlay.font = 'bold 12px Inter, sans-serif';
                const textWidth = ctxOverlay.measureText(labelText).width;

                ctxOverlay.fillStyle = isShutterTrigger ? '#38bdf8' : '#22c55e';
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

    // 5. LISTENER AUTO-TRIGGER RS-485 (Polling Real-Time dari Backend)
    async function pollSensorTrigger() {
        if (!chkAutoTrigger.checked) return;

        try {
            const res = await fetch('/api/get-sensor-trigger');
            const data = await res.json();
            const trig = data ? data.trigger : null;

            if (trig && trig.trigger_id && trig.trigger_id !== lastProcessedTriggerId) {
                lastProcessedTriggerId = trig.trigger_id;

                if (trig.type === 'input') {
                    // Flash Indikator Sensor 1 (Input)
                    dotSensorInput.style.background = '#22c55e';
                    dotSensorInput.style.boxShadow = '0 0 10px #22c55e';
                    textSensorInput.innerText = 'TRIGGER DETECTED!';
                    textSensorInput.style.color = '#16a34a';

                    appendLog('🚪 Photosensor 1 (Input) menandai Hanger Baru Masuk ke Line.', '#16a34a');
                    loadHangerStats();

                    setTimeout(() => {
                        dotSensorInput.style.background = '#94a3b8';
                        dotSensorInput.style.boxShadow = 'none';
                        textSensorInput.innerText = 'Standby';
                        textSensorInput.style.color = '#64748b';
                    }, 1200);

                } else if (trig.type === 'output' || trig.type === 'output_trigger') {
                    // Flash Indikator Sensor 2 (Output Shutter)
                    dotSensorOutput.style.background = '#38bdf8';
                    dotSensorOutput.style.boxShadow = '0 0 10px #38bdf8';
                    textSensorOutput.innerText = 'SHUTTER TRIGGER!';
                    textSensorOutput.style.color = '#0284c7';

                    // Efek Rana / Flash Kamera
                    videoContainer.style.borderColor = '#38bdf8';
                    shutterFlash.style.display = 'block';
                    setTimeout(() => {
                        shutterFlash.style.display = 'none';
                        videoContainer.style.borderColor = '#1e293b';
                    }, 200);

                    // Jalankan deteksi konfirmasi otomatis
                    await runObjectDetection(true);

                    setTimeout(() => {
                        dotSensorOutput.style.background = '#94a3b8';
                        dotSensorOutput.style.boxShadow = 'none';
                        textSensorOutput.innerText = 'Standby';
                        textSensorOutput.style.color = '#64748b';
                    }, 1200);
                }
            }
        } catch (e) {}
    }

    // Jalankan polling trigger sensor setiap 250ms
    triggerPollInterval = setInterval(pollSensorTrigger, 250);

    // 6. EVENT TOMBOL SIMULASI (Testing tanpa conveyor fisik)
    document.getElementById('btn-sim-input').addEventListener('click', async () => {
        try {
            await fetch('/api/sensor-trigger?type=input');
        } catch (e) {}
    });

    document.getElementById('btn-sim-output').addEventListener('click', async () => {
        try {
            await fetch('/api/sensor-trigger?type=output');
        } catch (e) {}
    });
</script>
