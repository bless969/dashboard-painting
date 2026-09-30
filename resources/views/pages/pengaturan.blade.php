<div id="view-pengaturan" style="{{ $view_aktif !== 'pengaturan' ? 'display:none;' : '' }}">
    <!-- HEADER PENGATURAN -->
    <div class="panel" style="margin-bottom: 16px;">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div>
                <div class="panel-title" style="margin-bottom:4px; font-size:18px;">⚙️ Menu Pengaturan Sistem — Line Painting</div>
                <div style="font-size:13px; color:#64748b;">Kelola konfigurasi perangkat kamera ESP32-CAM (Kamera Input &amp; Output), status tombol operator, dan preferensi dashboard.</div>
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
                📷 Kamera ESP32-CAM (Input &amp; Output)
            </button>
            <button type="button" class="settings-tab-btn" id="tab-btn-grafik" onclick="switchSettingsTab('grafik')">
                📊 Tampilan &amp; Grafik
            </button>
            <button type="button" class="settings-tab-btn" id="tab-btn-target" onclick="switchSettingsTab('target')">
                🏭 Target &amp; Shift Produksi
            </button>
        </div>
    </div>

    <!-- ===== TAB 1: PENGATURAN KAMERA ESP32-CAM (INPUT & OUTPUT) ===== -->
    <div id="tab-panel-camera" class="settings-tab-panel">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 16px;">
            <!-- KOLOM KIRI: FORM PENGATURAN KEDUA KAMERA & SENSOR -->
            <div style="display:flex; flex-direction:column; gap:16px;">
                <!-- 1. KAMERA INPUT (INFEED WADAH) -->
                <div class="panel">
                    <div class="panel-title" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span>📥</span>
                            <span>1. Kamera Input (Infeed Wadah)</span>
                        </div>
                        <span id="cfg-cam-input-status-pill" style="font-size:11px; font-weight:700; background:#f1f5f9; color:#64748b; padding:3px 9px; border-radius:6px; border:1px solid #cbd5e1; font-family:ui-monospace, monospace;">
                            MEMUAT...
                        </span>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Alamat IP Kamera Input</div>
                            <div class="settings-row-desc">IP modul ESP32-CAM pada posisi konveyor masuk (infeed).</div>
                        </div>
                        <input type="text" id="cfg-cam-input-ip" class="filter-select" style="max-width:200px;" placeholder="192.168.1.88" value="192.168.1.88" oninput="onInputCamIpChange('input', this.value)">
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">URL Stream Video Input (MJPEG)</div>
                            <div class="settings-row-desc">Endpoint live stream video kamera infeed.</div>
                        </div>
                        <input type="text" id="cfg-cam-input-url" class="filter-select" style="max-width:240px;" placeholder="http://192.168.1.88/stream" value="http://192.168.1.88/stream">
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">URL Snapshot Foto Input (JPEG)</div>
                            <div class="settings-row-desc">Endpoint pengambilan foto snapshot resolusi tinggi.</div>
                        </div>
                        <input type="text" id="cfg-cam-input-capture-url" class="filter-select" style="max-width:240px;" placeholder="http://192.168.1.88/capture" value="http://192.168.1.88/capture">
                    </div>

                    <div style="display:flex; align-items:center; gap:8px; margin-top:10px;">
                        <button type="button" class="btn-nav-action btn-blue" onclick="sambungkanIpCepat('input', 'cfg-cam-input-ip')" style="font-size:11.5px; padding:6px 14px;">
                            🔗 Tes &amp; Hubungkan Kamera Input
                        </button>
                    </div>
                </div>

                <!-- 2. KAMERA OUTPUT (OUTFEED WADAH) -->
                <div class="panel">
                    <div class="panel-title" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span>📤</span>
                            <span>2. Kamera Output (Outfeed Wadah)</span>
                        </div>
                        <span id="cfg-cam-output-status-pill" style="font-size:11px; font-weight:700; background:#f1f5f9; color:#64748b; padding:3px 9px; border-radius:6px; border:1px solid #cbd5e1; font-family:ui-monospace, monospace;">
                            MEMUAT...
                        </span>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Alamat IP Kamera Output</div>
                            <div class="settings-row-desc">IP modul ESP32-CAM pada posisi konveyor keluar (outfeed).</div>
                        </div>
                        <input type="text" id="cfg-cam-output-ip" class="filter-select" style="max-width:200px;" placeholder="192.168.1.89" value="192.168.1.89" oninput="onInputCamIpChange('output', this.value)">
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">URL Stream Video Output (MJPEG)</div>
                            <div class="settings-row-desc">Endpoint live stream video kamera outfeed.</div>
                        </div>
                        <input type="text" id="cfg-cam-output-url" class="filter-select" style="max-width:240px;" placeholder="http://192.168.1.89/stream" value="http://192.168.1.89/stream">
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">URL Snapshot Foto Output (JPEG)</div>
                            <div class="settings-row-desc">Endpoint pengambilan foto snapshot resolusi tinggi.</div>
                        </div>
                        <input type="text" id="cfg-cam-output-capture-url" class="filter-select" style="max-width:240px;" placeholder="http://192.168.1.89/capture" value="http://192.168.1.89/capture">
                    </div>

                    <div style="display:flex; align-items:center; gap:8px; margin-top:10px;">
                        <button type="button" class="btn-nav-action btn-blue" onclick="sambungkanIpCepat('output', 'cfg-cam-output-ip')" style="font-size:11.5px; padding:6px 14px;">
                            🔗 Tes &amp; Hubungkan Kamera Output
                        </button>
                    </div>
                </div>

                <!-- 3. HARDWARE TOMBOL OPERATOR & PHOTOSENSOR -->
                <div class="panel">
                    <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                        <span>🔴🟢</span>
                        <span>3. Tombol Operator &amp; Photosensor Hanger</span>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Status Operator Saat Ini</div>
                            <div class="settings-row-desc">Status aktif yang terbaca dari penekanan tombol fisik di lini painting.</div>
                        </div>
                        <span id="cfg-operator-status-badge" class="operator-status-badge active-green" style="font-size:12px; padding:4px 10px;">
                            🟢 Operator Memproduksi
                        </span>
                    </div>

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Uji Respons Tombol Operator</div>
                            <div class="settings-row-desc">Simulasikan penekanan tombol fisik operator (Hijau = Hadir, Merah = Kosong).</div>
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

                    <div class="settings-row">
                        <div class="settings-row-text">
                            <div class="settings-row-label">Tipe Sinyal Photosensor Overhead</div>
                            <div class="settings-row-desc">Sensor optik pendeteksi gantungan hanger konveyor dari atas.</div>
                        </div>
                        <select id="cfg-sensor-type" class="filter-select">
                            <option value="LOW" selected>Active LOW (NPN Normally Open)</option>
                            <option value="HIGH">Active HIGH (PNP Normally Open)</option>
                        </select>
                    </div>
                </div>

                <!-- TOMBOL SIMPAN GLOBAL -->
                <div style="display:flex; align-items:center; gap:10px;">
                    <button type="button" class="btn-nav-action" style="background:#0d3b8c; color:#fff; border-color:#0d3b8c; font-size:13px; font-weight:700; padding:10px 24px;" onclick="simpanPengaturanKameraLengkap()">
                        💾 Simpan Semua Konfigurasi Kamera
                    </button>
                </div>

                <div id="cfg-cam-status-msg" style="display:none; padding:10px 14px; border-radius:6px; font-size:12.5px; font-weight:600;"></div>
            </div>

            <!-- KOLOM KANAN: PRATINJAU LIVE STREAM KEDUA KAMERA -->
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div class="panel" style="display:flex; flex-direction:column;">
                    <div class="panel-title" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span>📹</span>
                            <span>Pratinjau Live Stream Kamera</span>
                        </div>
                        <span id="cfg-preview-live-badge" style="font-size:11px; font-weight:700; background:rgba(34,197,94,0.15); color:#16a34a; padding:3px 9px; border-radius:6px; border:1px solid #bbf7d0;">
                            ● LIVE PREVIEW
                        </span>
                    </div>

                    <!-- TOGGLE TAB PREVIEW: INPUT vs OUTPUT vs DUAL -->
                    <div class="camera-channel-nav" style="margin-bottom:12px;">
                        <button type="button" class="cam-channel-btn active" id="btn-cfg-tab-input" onclick="switchSettingsPreviewTab('input')">
                            <span>📥 Kamera Input</span>
                        </button>
                        <button type="button" class="cam-channel-btn" id="btn-cfg-tab-output" onclick="switchSettingsPreviewTab('output')">
                            <span>📤 Kamera Output</span>
                        </button>
                        <button type="button" class="cam-channel-btn" id="btn-cfg-tab-dual" onclick="switchSettingsPreviewTab('dual')">
                            <span>⊞ Kedua Kamera (Dual)</span>
                        </button>
                    </div>

                    <!-- 1. PREVIEW CONTAINER: INPUT -->
                    <div id="cfg-preview-container-input" style="position:relative; width:100%; border-radius:8px; overflow:hidden; background:#0b1120; min-height:280px; display:flex; align-items:center; justify-content:center; border:1px solid #334155;">
                        <img id="settingsCameraPreviewInput" src="" alt="Pratinjau Stream Input" style="width:100%; height:auto; max-height:420px; object-fit:contain; display:block;" onload="onSettingsPreviewSuccess('input')" onerror="onSettingsPreviewError('input')">
                        <div id="settingsPreviewErrorInput" style="display:none; position:absolute; inset:0; background:rgba(11,17,32,0.92); flex-direction:column; align-items:center; justify-content:center; padding:24px; text-align:center; color:#f8fafc;">
                            <div style="font-size:30px; margin-bottom:6px;">📡</div>
                            <div style="font-size:13.5px; font-weight:700; color:#f87171; margin-bottom:4px;">Kamera Input Belum Terhubung</div>
                            <div style="font-size:11.5px; color:#94a3b8; max-width:300px; margin-bottom:12px;">Pastikan ESP32-CAM Input menyala dan terhubung ke WiFi.</div>
                            <button type="button" class="btn-nav-action btn-blue" onclick="refreshStreamKamera('input')" style="font-size:11.5px; padding:5px 12px;">🔄 Coba Ulang</button>
                        </div>
                    </div>

                    <!-- 2. PREVIEW CONTAINER: OUTPUT -->
                    <div id="cfg-preview-container-output" style="display:none; position:relative; width:100%; border-radius:8px; overflow:hidden; background:#0b1120; min-height:280px; align-items:center; justify-content:center; border:1px solid #334155;">
                        <img id="settingsCameraPreviewOutput" src="" alt="Pratinjau Stream Output" style="width:100%; height:auto; max-height:420px; object-fit:contain; display:block;" onload="onSettingsPreviewSuccess('output')" onerror="onSettingsPreviewError('output')">
                        <div id="settingsPreviewErrorOutput" style="display:none; position:absolute; inset:0; background:rgba(11,17,32,0.92); flex-direction:column; align-items:center; justify-content:center; padding:24px; text-align:center; color:#f8fafc;">
                            <div style="font-size:30px; margin-bottom:6px;">📡</div>
                            <div style="font-size:13.5px; font-weight:700; color:#f87171; margin-bottom:4px;">Kamera Output Belum Terhubung</div>
                            <div style="font-size:11.5px; color:#94a3b8; max-width:300px; margin-bottom:12px;">Pastikan ESP32-CAM Output menyala dan terhubung ke WiFi.</div>
                            <button type="button" class="btn-nav-action btn-blue" onclick="refreshStreamKamera('output')" style="font-size:11.5px; padding:5px 12px;">🔄 Coba Ulang</button>
                        </div>
                    </div>

                    <!-- 3. PREVIEW CONTAINER: DUAL (KEDUA KAMERA) -->
                    <div id="cfg-preview-container-dual" style="display:none; flex-direction:column; gap:10px;">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <!-- Box Input -->
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <div style="font-size:11px; font-weight:700; color:#38bdf8;">📥 Input (Infeed)</div>
                                <div style="position:relative; width:100%; height:180px; background:#0b1120; border-radius:6px; overflow:hidden; border:1px solid #334155;">
                                    <img id="settingsCameraPreviewDualInput" src="" alt="Preview Dual Input" style="width:100%; height:100%; object-fit:cover; display:block;" onload="onSettingsPreviewSuccess('input')" onerror="onSettingsPreviewError('input')">
                                </div>
                            </div>
                            <!-- Box Output -->
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <div style="font-size:11px; font-weight:700; color:#38bdf8;">📤 Output (Outfeed)</div>
                                <div style="position:relative; width:100%; height:180px; background:#0b1120; border-radius:6px; overflow:hidden; border:1px solid #334155;">
                                    <img id="settingsCameraPreviewDualOutput" src="" alt="Preview Dual Output" style="width:100%; height:100%; object-fit:cover; display:block;" onload="onSettingsPreviewSuccess('output')" onerror="onSettingsPreviewError('output')">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ACTION BAR PREVIEW -->
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-top:14px; flex-wrap:wrap; gap:10px;">
                        <div style="font-size:11.5px; color:#64748b;">
                            Endpoint Input: <code id="cfg-preview-endpoint-input" style="color:#0284c7; font-family:ui-monospace, monospace; font-weight:600;">http://192.168.1.88/stream</code>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button type="button" class="btn-nav-action btn-light" onclick="refreshStreamKamera('both')" style="font-size:12px; padding:6px 12px;" title="Muat ulang kedua stream kamera">
                                🔄 Refresh Stream
                            </button>
                            <button type="button" class="btn-nav-action btn-light" onclick="bukaCaptureTab('input')" style="font-size:12px; padding:6px 12px;" title="Ambil foto snapshot Kamera Input">
                                📸 Foto Input
                            </button>
                            <button type="button" class="btn-nav-action btn-light" onclick="bukaCaptureTab('output')" style="font-size:12px; padding:6px 12px;" title="Ambil foto snapshot Kamera Output">
                                📸 Foto Output
                            </button>
                            <button type="button" class="btn-nav-action btn-blue" onclick="bukaCameraPopup()" style="font-size:12px; padding:6px 14px;" title="Buka jendela monitor mengambang">
                                🗖 Buka Monitor Floating
                            </button>
                        </div>
                    </div>
                </div>
            </div>
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
</div>
