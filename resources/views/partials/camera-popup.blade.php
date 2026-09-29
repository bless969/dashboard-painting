<!-- ===== FLOATING & DRAGGABLE ESP-CAM POP-UP WIDGET ===== -->
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
                <span class="camera-title-text">ESP-CAM • Inspeksi Hanger Painting</span>
            </div>
        </div>

        <div class="camera-header-actions">
            <!-- Mode Stream / Simulasi Indicator (Dapat diklik untuk beralih mode langsung) -->
            <span class="camera-mode-tag" id="cam-mode-tag" onclick="toggleModeKameraLangsung()" title="Klik untuk beralih antara Mode Simulasi dan Stream IP Fisik" style="cursor:pointer;">SIMULASI LIVE</span>

            <!-- Tombol Buka Menu Setting Kamera -->
            <button type="button" class="btn-cam-header-action" onclick="bukaSettingKameraDariPopup()" title="Buka Menu Pengaturan Kamera">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
            </button>

            <!-- Tombol Membesar / Resize (Membesar) -->
            <button type="button" class="btn-cam-header-action" id="btnCamMaximize" onclick="toggleMembesarKamera()" title="Perbesar / Perkecil Ukuran Jendela Kamera">
                <svg id="cam-icon-expand" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
                </svg>
                <svg id="cam-icon-compress" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" style="display:none;">
                    <path d="M4 14h6v6M20 10h-6V4M14 10l7-7M10 14l-7 7"/>
                </svg>
            </button>

            <!-- Tombol Minimize -->
            <button type="button" class="btn-cam-header-action" onclick="toggleMinimizeKamera()" title="Minimize Kamera">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </button>

            <!-- Tombol Tutup / Dock -->
            <button type="button" class="btn-cam-header-action btn-cam-close" onclick="tutupCameraPopup()" title="Sembunyikan Kamera (Bisa dibuka lagi via tombol atas)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- BODY VIEWPORT -->
    <div class="camera-float-body" id="cameraBody">
        <!-- CANVAS STREAM / VIEWPORT -->
        <div class="camera-viewport-wrapper">
            <!-- Simulated Inspection Canvas -->
            <canvas id="cameraCanvas" width="640" height="420" class="camera-canvas"></canvas>

            <!-- Real Stream Image fallback/target (jika terhubung ke IP riil ESP32-CAM) -->
            <img id="cameraStreamImg" src="" alt="Live Stream ESP-CAM" class="camera-stream-img" style="display:none;" onload="sembunyikanErrorStreamKamera()" onerror="tanganiErrorStreamKamera(this)">

            <!-- Overlay Pesan Saat Stream IP Belum Tersambung -->
            <div id="cameraStreamErrorOverlay" style="display:none; position:absolute; inset:0; background:rgba(15,23,42,0.92); z-index:15; flex-direction:column; align-items:center; justify-content:center; padding:20px; text-align:center; color:#f8fafc;">
                <div style="font-size:26px; margin-bottom:6px;">📡</div>
                <div style="font-size:13.5px; font-weight:700; color:#f87171; margin-bottom:4px;">Stream ESP32-CAM Belum Terhubung</div>
                <div style="font-size:11.5px; color:#94a3b8; max-width:320px; margin-bottom:12px; line-height:1.4;">
                    Tidak dapat memuat stream dari <code id="err-stream-url" style="color:#38bdf8; font-weight:700;">http://192.168.1.88/stream</code>. Pastikan ESP32-CAM menyala dan terhubung ke WiFi yang sama.
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="btn-nav-action" onclick="bukaSettingKameraDariPopup(); sembunyikanErrorStreamKamera();" style="font-size:11px; padding:5px 12px; background:#0284c7; color:#fff; border:none; font-weight:700; border-radius:6px; cursor:pointer;">
                        ⚙️ Ubah IP Kamera
                    </button>
                    <button type="button" class="btn-nav-action" onclick="toggleModeKameraLangsung(); sembunyikanErrorStreamKamera();" style="font-size:11px; padding:5px 12px; background:#334155; color:#fff; border:none; border-radius:6px; cursor:pointer;">
                        🔄 Kembali ke Simulasi
                    </button>
                </div>
            </div>

            <!-- Simulated Flash Overlay (LED HPL Cool White via IRF520 MOSFET) -->
            <div id="cameraFlashOverlay" class="camera-flash-overlay"></div>

            <!-- HUD Top Overlay: Hardware Telemetry -->
            <div class="camera-hud-top">
                <div class="hud-chip">
                    <span class="hud-dot green"></span>
                    <span id="hud-cam-ip">192.168.1.88</span>
                </div>
                <div class="hud-chip" style="background:rgba(56, 189, 248, 0.18); border-color:rgba(56, 189, 248, 0.38); color:#38bdf8;">
                    <span>POV:</span> <strong>TOP-DOWN (ATAS)</strong>
                </div>
                <div class="hud-chip">
                    <span>RES:</span> <strong id="hud-cam-res">SVGA 800x600</strong>
                </div>
                <div class="hud-chip">
                    <span>FPS:</span> <strong id="hud-cam-fps">24</strong>
                </div>
                <div class="hud-chip highlight">
                    <span>HANGER:</span> <strong id="hud-hanger-id">#H-1049</strong>
                </div>
                <div class="hud-chip" id="hud-chip-operator">
                    <span class="hud-dot green" id="hud-operator-dot"></span>
                    <span>OP:</span> <strong id="hud-operator-text">HADIR</strong>
                </div>
            </div>

            <!-- HUD Bottom Overlay: Detection & Hardware State -->
            <div class="camera-hud-bottom">
                <div class="hud-status-banner" id="hudStatusBanner">
                    <div class="hud-detection-info">
                        <span class="hud-label">DETEKSI WADAH LAMPU:</span>
                        <span class="hud-value" id="hudWadahText">Memuat...</span>
                    </div>
                    <div class="hud-meta-tags">
                        <span class="hud-tag" id="hudPhotosensorTag">
                            <span class="pulse-indicator" id="hudSensorDot"></span> Photosensor: STANDBY
                        </span>
                        <span class="hud-tag" id="hudMosfetTag">
                            ⚡ Flash IRF520: READY
                        </span>
                        <span class="hud-tag" id="hudStepDownTag">
                            🔋 LM2596: 5.02V
                        </span>
                    </div>
                </div>
            </div>

            <!-- Crosshair & Scan Guides -->
            <div class="camera-crosshairs">
                <div class="ch-line-h"></div>
                <div class="ch-line-v"></div>
                <div class="ch-bracket-tl"></div>
                <div class="ch-bracket-tr"></div>
                <div class="ch-bracket-bl"></div>
                <div class="ch-bracket-br"></div>
            </div>
        </div>

        <!-- QUICK CONTROLS BAR -->
        <div class="camera-quick-controls">
            <div class="camera-control-btns">
                <button type="button" class="btn-cam-ctrl btn-cam-trigger" onclick="simulasiTriggerPhotosensorManual()" title="Simulasikan kedatangan gantungan hanger melewati photosensor">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                    <span>Trigger Sensor</span>
                </button>

                <button type="button" class="btn-cam-ctrl btn-cam-flash" onclick="ujiFlashLedManual()" title="Uji nyala lampu flash LED HPL Cool White via Modul MOSFET IRF520">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    <span>Test Flash LED</span>
                </button>

                <button type="button" class="btn-cam-ctrl btn-cam-ai" onclick="bukaPanduanAIDariPopup()" title="Panduan Lengkap Pelatihan Model AI ESP-CAM untuk Sudut Pandang Tampak Atas (Top-Down)">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>Panduan AI</span>
                </button>

                <button type="button" class="btn-cam-ctrl btn-cam-snap" onclick="ambilSnapshotDataset()" title="Ambil foto snapshot saat ini untuk koleksi dataset pelatihan AI (Top-Down)">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <span>Foto Dataset</span>
                </button>

                <button type="button" class="btn-cam-ctrl btn-cam-enlarge" onclick="toggleMembesarKamera()" title="Perbesar jendela kamera agar detail inspeksi lebih jelas">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                    <span id="label-btn-enlarge">Membesar</span>
                </button>

                <button type="button" class="btn-cam-ctrl btn-cam-settings" onclick="bukaSettingKameraDariPopup()" title="Buka menu konfigurasi lengkap ESP-CAM">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Setting</span>
                </button>
            </div>

            <!-- Tombol Fisik Operator (Hijau = Memproduksi, Merah = Tidak Ada Operator) -->
            <div class="camera-operator-switch-bar">
                <span class="cam-op-label">Tombol Operator:</span>
                <div class="cam-op-btn-group">
                    <button type="button" class="btn-op-mini btn-op-mini-green active" id="btn-op-mini-green" onclick="setOperatorStatus('hadir')" title="Tekan Tombol Hijau: Operator Hadir & Memproduksi">
                        <span>🟢</span> HIJAU (Hadir)
                    </button>
                    <button type="button" class="btn-op-mini btn-op-mini-red" id="btn-op-mini-red" onclick="setOperatorStatus('tidak_ada')" title="Tekan Tombol Merah: Tidak Ada Operator">
                        <span>🔴</span> MERAH (Kosong)
                    </button>
                </div>
            </div>
        </div>

        <!-- ENLARGED EXTRA PANEL: Riwayat & Log Deteksi Hanger (Hanya tampil saat Membesar) -->
        <div class="camera-enlarged-details" id="cameraEnlargedDetails" style="display:none;">
            <div class="enlarged-panel-grid">
                <!-- Statistik Real-time Hanger & Wadah -->
                <div class="enlarged-card">
                    <div class="enlarged-card-title">📦 Statistik Inspeksi Shift Ini</div>
                    <div class="enlarged-stats-mini-grid">
                        <div class="mini-stat-item">
                            <span class="mini-label">Total Hanger</span>
                            <span class="mini-val" id="cam-stat-total-hanger">0</span>
                        </div>
                        <div class="mini-stat-item">
                            <span class="mini-label">Hanger Terisi</span>
                            <span class="mini-val green" id="cam-stat-hanger-terisi">0</span>
                        </div>
                        <div class="mini-stat-item">
                            <span class="mini-label">Hanger Kosong</span>
                            <span class="mini-val orange" id="cam-stat-hanger-kosong">0</span>
                        </div>
                        <div class="mini-stat-item">
                            <span class="mini-label">Wadah Terdeteksi</span>
                            <span class="mini-val blue" id="cam-stat-total-wadah">0 pcs</span>
                        </div>
                    </div>
                </div>

                <!-- Log 5 Hanger Terakhir -->
                <div class="enlarged-card">
                    <div class="enlarged-card-title">⏱️ Riwayat 5 Hanger Terakhir Lewat</div>
                    <div class="recent-hanger-list" id="recentHangerList">
                        <div class="recent-hanger-empty">Menunggu deteksi hanger pertama...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
