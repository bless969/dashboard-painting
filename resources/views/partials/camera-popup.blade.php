<!-- ===== FLOATING & DRAGGABLE DUAL ESP32-CAM MONITOR (INPUT & OUTPUT) ===== -->
<div id="camera-float-widget" class="camera-float-modal" style="display:none;">
    <!-- HEADER (Draggable Handle) -->
    <div class="camera-float-header" id="cameraDragHandle">
        <div class="camera-header-left">
            <span class="camera-drag-grip" title="Klik dan geser untuk memindahkan posisi">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                    <circle cx="8" cy="6" r="1.5"/><circle cx="16" cy="6" r="1.5"/>
                    <circle cx="8" cy="12" r="1.5"/><circle cx="16" cy="12" r="1.5"/>
                    <circle cx="8" cy="18" r="1.5"/><circle cx="16" cy="18" r="1.5"/>
                </svg>
            </span>
            <div class="camera-title-badge">
                <span class="camera-status-dot pulse-online" id="cam-status-dot"></span>
                <span class="camera-title-text" id="cam-modal-title">Kamera Live ESP32-CAM</span>
            </div>
            <span id="cam-stream-status-pill" style="font-size:10.5px; font-weight:700; background:rgba(34,197,94,0.18); color:#4ade80; padding:2px 8px; border-radius:4px; border:1px solid rgba(74,222,128,0.3); font-family:ui-monospace, monospace;">
                INPUT: ONLINE
            </span>
        </div>

        <div class="camera-header-actions">
            <!-- Tombol Pengaturan Kamera -->
            <button type="button" class="btn-cam-header-action" onclick="bukaSettingKameraDariPopup()" title="Buka Pengaturan Kamera & IP">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
            </button>

            <!-- Tombol Membesar / Resize -->
            <button type="button" class="btn-cam-header-action" id="btnCamMaximize" onclick="toggleMembesarKamera()" title="Perbesar / Perkecil Ukuran Jendela Kamera">
                <svg id="cam-icon-expand" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
                </svg>
                <svg id="cam-icon-compress" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" style="display:none;">
                    <path d="M4 14h6v6M20 10h-6V4M14 10l7-7M10 14l-7 7"/>
                </svg>
            </button>

            <!-- Tombol Minimize -->
            <button type="button" class="btn-cam-header-action" onclick="toggleMinimizeKamera()" title="Minimize Jendela Kamera">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </button>

            <!-- Tombol Tutup / Dock -->
            <button type="button" class="btn-cam-header-action btn-cam-close" onclick="tutupCameraPopup()" title="Sembunyikan Kamera">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- BODY VIEWPORT -->
    <div class="camera-float-body" id="cameraBody">
        <!-- CHANNEL NAVIGASI: INPUT vs OUTPUT vs DUAL VIEW -->
        <div class="camera-channel-nav">
            <button type="button" class="cam-channel-btn active" id="btn-cam-channel-input" onclick="switchCameraChannel('input')">
                <span class="cam-channel-dot online" id="dot-channel-input"></span>
                <span>📥 Kamera Input</span>
            </button>
            <button type="button" class="cam-channel-btn" id="btn-cam-channel-output" onclick="switchCameraChannel('output')">
                <span class="cam-channel-dot online" id="dot-channel-output"></span>
                <span>📤 Kamera Output</span>
            </button>
            <button type="button" class="cam-channel-btn" id="btn-cam-channel-dual" onclick="switchCameraChannel('dual')" title="Tampilkan kedua kamera (Input & Output) secara bersamaan">
                <span>⊞ Dual View</span>
            </button>
        </div>

        <!-- 1. VIEWPORT: KAMERA INPUT (Aktif Saat Channel = 'input') -->
        <div id="viewport-channel-input" class="camera-viewport-wrapper">
            <img id="cameraStreamImgInput" src="" alt="Stream Kamera Input" class="camera-stream-img" onload="onCameraStreamSuccess('input')" onerror="onCameraStreamError('input')">

            <!-- Overlay Offline Input -->
            <div id="cameraOfflineOverlayInput" style="display:none; position:absolute; inset:0; background:#0b1120; z-index:10; flex-direction:column; align-items:center; justify-content:center; padding:20px; text-align:center; color:#f8fafc;">
                <div style="font-size:26px; margin-bottom:8px;">📡</div>
                <div style="font-size:13.5px; font-weight:700; color:#f87171; margin-bottom:4px;">Kamera Input Belum Terhubung</div>
                <div style="font-size:11.5px; color:#94a3b8; max-width:320px; margin-bottom:12px; line-height:1.4;">
                    Tidak dapat memuat stream dari <code id="err-stream-url-input" style="color:#38bdf8; font-family:ui-monospace, monospace;">http://192.168.1.88/stream</code>.
                </div>
                <div style="display:flex; gap:8px; max-width:280px; width:100%;">
                    <input type="text" id="quick-reconnect-ip-input" class="camera-ip-input" placeholder="IP ESP Input" value="192.168.1.88">
                    <button type="button" class="camera-ip-btn" onclick="sambungkanIpCepat('input', 'quick-reconnect-ip-input')">Hubungkan</button>
                </div>
            </div>
        </div>

        <!-- 2. VIEWPORT: KAMERA OUTPUT (Aktif Saat Channel = 'output') -->
        <div id="viewport-channel-output" class="camera-viewport-wrapper" style="display:none;">
            <img id="cameraStreamImgOutput" src="" alt="Stream Kamera Output" class="camera-stream-img" onload="onCameraStreamSuccess('output')" onerror="onCameraStreamError('output')">

            <!-- Overlay Offline Output -->
            <div id="cameraOfflineOverlayOutput" style="display:none; position:absolute; inset:0; background:#0b1120; z-index:10; flex-direction:column; align-items:center; justify-content:center; padding:20px; text-align:center; color:#f8fafc;">
                <div style="font-size:26px; margin-bottom:8px;">📡</div>
                <div style="font-size:13.5px; font-weight:700; color:#f87171; margin-bottom:4px;">Kamera Output Belum Terhubung</div>
                <div style="font-size:11.5px; color:#94a3b8; max-width:320px; margin-bottom:12px; line-height:1.4;">
                    Tidak dapat memuat stream dari <code id="err-stream-url-output" style="color:#38bdf8; font-family:ui-monospace, monospace;">http://192.168.1.89/stream</code>.
                </div>
                <div style="display:flex; gap:8px; max-width:280px; width:100%;">
                    <input type="text" id="quick-reconnect-ip-output" class="camera-ip-input" placeholder="IP ESP Output" value="192.168.1.89">
                    <button type="button" class="camera-ip-btn" onclick="sambungkanIpCepat('output', 'quick-reconnect-ip-output')">Hubungkan</button>
                </div>
            </div>
        </div>

        <!-- 3. VIEWPORT: DUAL VIEW (Aktif Saat Channel = 'dual') -->
        <div id="viewport-channel-dual" style="display:none;">
            <div class="cam-dual-grid">
                <!-- Sisi Kiri: Input -->
                <div class="cam-dual-pane">
                    <div class="cam-dual-pane-header">
                        <span style="display:flex; align-items:center; gap:6px;">
                            <span class="cam-channel-dot online" id="dot-dual-input"></span>
                            <span>📥 Kamera Input (Infeed)</span>
                        </span>
                        <code id="dual-label-ip-input" style="font-size:10px; color:#38bdf8; font-family:ui-monospace, monospace;">192.168.1.88</code>
                    </div>
                    <div class="camera-viewport-wrapper" style="height:220px;">
                        <img id="cameraStreamImgDualInput" src="" alt="Stream Input" class="camera-stream-img" onload="onCameraStreamSuccess('input')" onerror="onCameraStreamError('input')">
                    </div>
                </div>

                <!-- Sisi Kanan: Output -->
                <div class="cam-dual-pane">
                    <div class="cam-dual-pane-header">
                        <span style="display:flex; align-items:center; gap:6px;">
                            <span class="cam-channel-dot online" id="dot-dual-output"></span>
                            <span>📤 Kamera Output (Outfeed)</span>
                        </span>
                        <code id="dual-label-ip-output" style="font-size:10px; color:#38bdf8; font-family:ui-monospace, monospace;">192.168.1.89</code>
                    </div>
                    <div class="camera-viewport-wrapper" style="height:220px;">
                        <img id="cameraStreamImgDualOutput" src="" alt="Stream Output" class="camera-stream-img" onload="onCameraStreamSuccess('output')" onerror="onCameraStreamError('output')">
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK CONTROLS BAR (Sesuai Channel yang Dipilih) -->
        <div class="camera-quick-controls" style="margin-top:4px;">
            <!-- Bar Kontrol Channel Input -->
            <div id="quickbar-input" class="camera-ip-quickbar">
                <span style="font-weight:700; color:#94a3b8; white-space:nowrap; font-size:11px;">IP Input:</span>
                <input type="text" id="cam-quick-ip-input" class="camera-ip-input" placeholder="192.168.1.88" value="192.168.1.88">
                <button type="button" class="camera-ip-btn" onclick="sambungkanIpCepat('input', 'cam-quick-ip-input')">Hubungkan</button>
                <button type="button" class="btn-cam-ctrl" onclick="refreshStreamKamera('input')" style="background:#334155; color:#f1f5f9; padding:5px 9px; font-size:11px;" title="Muat ulang stream video input">
                    🔄 Refresh
                </button>
                <button type="button" class="btn-cam-ctrl" onclick="bukaCaptureTab('input')" style="background:#1e293b; color:#cbd5e1; border:1px solid #334155; padding:5px 9px; font-size:11px;" title="Buka snapshot foto JPEG dari Kamera Input">
                    📸 Foto
                </button>
            </div>

            <!-- Bar Kontrol Channel Output -->
            <div id="quickbar-output" class="camera-ip-quickbar" style="display:none;">
                <span style="font-weight:700; color:#94a3b8; white-space:nowrap; font-size:11px;">IP Output:</span>
                <input type="text" id="cam-quick-ip-output" class="camera-ip-input" placeholder="192.168.1.89" value="192.168.1.89">
                <button type="button" class="camera-ip-btn" onclick="sambungkanIpCepat('output', 'cam-quick-ip-output')">Hubungkan</button>
                <button type="button" class="btn-cam-ctrl" onclick="refreshStreamKamera('output')" style="background:#334155; color:#f1f5f9; padding:5px 9px; font-size:11px;" title="Muat ulang stream video output">
                    🔄 Refresh
                </button>
                <button type="button" class="btn-cam-ctrl" onclick="bukaCaptureTab('output')" style="background:#1e293b; color:#cbd5e1; border:1px solid #334155; padding:5px 9px; font-size:11px;" title="Buka snapshot foto JPEG dari Kamera Output">
                    📸 Foto
                </button>
            </div>

            <!-- Bar Kontrol Channel Dual View -->
            <div id="quickbar-dual" class="camera-ip-quickbar" style="display:none; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:6px;">
                    <span style="font-size:11px; color:#94a3b8; font-weight:700;">Aksi Cepat:</span>
                    <button type="button" class="camera-ip-btn" onclick="refreshStreamKamera('both')" style="font-size:11px; padding:4px 10px;">
                        🔄 Refresh Kedua Kamera
                    </button>
                </div>
                <div style="display:flex; align-items:center; gap:6px;">
                    <button type="button" class="btn-cam-ctrl" onclick="bukaCaptureTab('input')" style="background:#1e293b; color:#cbd5e1; border:1px solid #334155; padding:4px 8px; font-size:11px;" title="Ambil snapshot foto Kamera Input">
                        📸 Foto Input
                    </button>
                    <button type="button" class="btn-cam-ctrl" onclick="bukaCaptureTab('output')" style="background:#1e293b; color:#cbd5e1; border:1px solid #334155; padding:4px 8px; font-size:11px;" title="Ambil snapshot foto Kamera Output">
                        📸 Foto Output
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
