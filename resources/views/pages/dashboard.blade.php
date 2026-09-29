<div id="view-dashboard" style="{{ $view_aktif !== 'dashboard' ? 'display:none;' : '' }}">
    <!-- ===== TOP CONTROL BAR (Grid 2 Kolom: Status Mesin & Choose Part) ===== -->
    <div class="dashboard-top-grid">
        <!-- Kolom Kiri: Status Mesin (Tower Light) -->
        <div class="panel tower-light-panel">
            <div class="tower-light-visual">
                <div class="tower-lamp lamp-merah" id="lamp-merah"></div>
                <div class="tower-lamp lamp-kuning" id="lamp-kuning"></div>
                <div class="tower-lamp lamp-hijau" id="lamp-hijau"></div>
            </div>
            <div class="tower-light-info">
                <div class="tower-light-label">Status Mesin Painting</div>
                <div class="tower-light-status" id="teks-status-mesin">Memuat...</div>
                <div class="tower-light-updated" id="teks-update-status">-</div>

                <!-- Tombol Fisik Operator: Hijau (Memproduksi) vs Merah (Tidak Ada Operator) -->
                <div class="operator-station-box">
                    <div class="operator-station-header">
                        <span class="operator-station-title">Status Tombol Operator:</span>
                        <span class="operator-status-badge active-green" id="dash-operator-badge">🟢 Memproduksi</span>
                    </div>
                    <div class="operator-btn-pair">
                        <button type="button" class="btn-push-hw btn-push-green active" id="dash-btn-op-green" onclick="setOperatorStatus('hadir')" title="Tekan Tombol Hijau: Operator Hadir & Aktif Memproduksi">
                            <span class="push-hw-lamp"></span>
                            <span class="push-hw-label">HIJAU (HADIR)</span>
                        </button>
                        <button type="button" class="btn-push-hw btn-push-red" id="dash-btn-op-red" onclick="setOperatorStatus('tidak_ada')" title="Tekan Tombol Merah: Tidak Ada Operator / Istirahat">
                            <span class="push-hw-lamp"></span>
                            <span class="push-hw-label">MERAH (KOSONG)</span>
                        </button>
                    </div>
                </div>

                <div class="tower-light-warning" id="stop-alasan-warning" style="display:none;" onclick="bukaModalAlasanStop()">
                    <span class="pulse-dot-warning"></span>
                    <span id="stop-alasan-warning-teks">Belum sempat isi alasan stop? Klik untuk isi alasan stop</span>
                </div>
                <!-- Tombol maintenance jika mesin dalam mode maintenance -->
                <button type="button" class="btn-toggle-maintenance" id="btn-toggle-maintenance" style="display:none;" onclick="toggleMaintenanceManual()">
                    ✅ Selesai Maintenance
                </button>
            </div>
        </div>

        <!-- Kolom Kanan: Kontrol Produksi & Pilih Part -->
        @include('partials.choose-part')
    </div>

    <!-- ===== ESP-CAM & HARDWARE STATUS BANNER (Line Painting Exclusive) ===== -->
    <div class="panel camera-live-banner" style="margin-bottom:18px; padding:12px 18px; background:linear-gradient(135deg, #0b2554 0%, #153e7e 100%); color:#ffffff; border:1px solid rgba(255,255,255,0.15); border-radius:12px; box-shadow:0 4px 14px rgba(11,37,84,0.15);">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="camera-status-dot pulse-online" style="width:10px; height:10px;"></span>
                    <strong style="font-size:14px; letter-spacing:0.2px;">ESP-CAM Inspeksi Hanger:</strong>
                    <span id="dash-cam-status-pill" style="font-size:12px; background:rgba(34,197,94,0.22); color:#4ade80; border:1px solid rgba(74,222,128,0.4); padding:2px 8px; border-radius:6px; font-weight:700;">ONLINE</span>
                </div>

                <div class="dash-cam-telemetry-tags" style="display:flex; align-items:center; gap:10px; font-size:12.5px; color:#cbd5e1;">
                    <span style="display:inline-flex; align-items:center; gap:5px;">
                        <span>👤</span> Operator: <strong id="dash-operator-state" style="color:#4ade80;">HADIR (HIJAU)</strong>
                    </span>
                    <span style="color:rgba(255,255,255,0.3);">|</span>
                    <span style="display:inline-flex; align-items:center; gap:5px;">
                        <span>🟢</span> Sensor: <strong id="dash-sensor-state" style="color:#ffffff;">READY</strong>
                    </span>
                    <span style="color:rgba(255,255,255,0.3);">|</span>
                    <span style="display:inline-flex; align-items:center; gap:5px;">
                        <span>⚡</span> Flash IRF520: <strong id="dash-flash-state" style="color:#ffffff;">PWM 85%</strong>
                    </span>
                    <span style="color:rgba(255,255,255,0.3);">|</span>
                    <span style="display:inline-flex; align-items:center; gap:5px;">
                        <span>📦</span> Deteksi Terakhir: <strong id="dash-last-wadah" style="color:#fde047;">2 Wadah (#H-1049)</strong>
                    </span>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <button type="button" class="btn-nav-action" style="background:#ffffff; color:#0b2554; border-color:#ffffff; font-weight:700; font-size:12.5px; padding:6px 14px; box-shadow:0 2px 8px rgba(0,0,0,0.15);" onclick="bukaCameraPopup()" title="Buka jendela kamera melayang yang dapat digeser dan diperbesar">
                    📷 Buka Monitor Kamera (Floating)
                </button>
                <button type="button" class="btn-nav-action" style="background:rgba(255,255,255,0.15); color:#ffffff; border-color:rgba(255,255,255,0.25); font-weight:600; font-size:12.5px; padding:6px 12px;" onclick="navigateTo(currentLine, 'pengaturan'); switchSettingsTab('camera');" title="Pengaturan Kamera ESP">
                    ⚙️ Setting
                </button>
            </div>
        </div>
    </div>

    <!-- ===== KARTU METRIK PRODUKSI: 3 ATAS (PCS WADAH) & 3 BAWAH (HANGER KONVEYOR) ===== -->
    <div class="metrics-split-container">
        <!-- BARIS 1 (ATAS): METRIK PRODUKSI UNIT (PCS WADAH LAMPU) -->
        <div class="metric-section-group">
            <div class="metric-group-header">
                <div class="metric-group-badge badge-pcs">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>
                    <span>METRIK PRODUKSI WADAH LAMPU (PCS)</span>
                </div>
                <div class="metric-mode-pill-group">
                    <button type="button" class="metric-mode-btn active" id="btn-mode-shift" onclick="setDashboardModeProduksi('shift')">⏱️ Shift Aktif</button>
                    <button type="button" class="metric-mode-btn" id="btn-mode-harian" onclick="setDashboardModeProduksi('harian')">📅 Total 1 Hari</button>
                </div>
            </div>
            <div class="metric-grid-3">
                <!-- PCS: Input Produksi -->
                <div class="metric-card input">
                    <div class="metric-card-header">
                        <div class="icon">IN</div>
                        <div class="metric-tanggal-label" id="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="input-value">0</div>
                    <div class="label">Input Wadah Lampu <span class="metric-unit-tag">PCS</span></div>
                </div>

                <!-- PCS: Output Produksi -->
                <div class="metric-card output">
                    <div class="metric-card-header">
                        <div class="icon">OUT</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="output-value">0</div>
                    <div class="label">Output Wadah Lampu <span class="metric-unit-tag">PCS</span></div>
                </div>

                <!-- PCS: Yield -->
                <div class="metric-card yield">
                    <div class="metric-card-header">
                        <div class="icon">%</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="yield-value">0</div>
                    <div class="label">Yield Painting <span class="metric-unit-tag">%</span></div>
                </div>
            </div>
        </div>

        <!-- BARIS 2 (BAWAH): METRIK KONVEYOR & HANGER (INSPEKSI ESP-CAM) -->
        <div class="metric-section-group">
            <div class="metric-group-header">
                <div class="metric-group-badge badge-hanger" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2v4M12 6a4 4 0 0 0-4 4v1H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-2v-1a4 4 0 0 0-4-4z"/><circle cx="12" cy="15" r="2"/></svg>
                    <span>SISTEM KONVEYOR &amp; INSPEKSI ESP-CAM (HANGER)</span>
                </div>
            </div>
            <div class="metric-grid-3">
                <!-- Hanger: Total Hanger Lewat -->
                <div class="metric-card sheet-input">
                    <div class="metric-card-header">
                        <div class="icon">HNG IN</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="sheet-input-value">0</div>
                    <div class="label">Total Hanger Lewat <span class="metric-unit-tag sheet">HANGER</span></div>
                </div>

                <!-- Hanger: Hanger Terisi Wadah -->
                <div class="metric-card sheet-output">
                    <div class="metric-card-header">
                        <div class="icon">HNG OK</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="sheet-output-value">0</div>
                    <div class="label">Hanger Terisi Wadah <span class="metric-unit-tag sheet">ADA WADAH</span></div>
                </div>

                <!-- Hanger: Hanger Kosong (Tanpa Wadah) -->
                <div class="metric-card sheet-yield">
                    <div class="metric-card-header">
                        <div class="icon">HNG 0</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="sheet-yield-value">0</div>
                    <div class="label">Hanger Kosong <span class="metric-unit-tag sheet">TANPA WADAH</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MACHINE RATIO: Sesi Part Aktif ===== -->
    <div class="panel panel-machine-ratio">
        <div id="mr-tidak-ada-sesi" class="mr-empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <p>Belum ada sesi produksi part yang sedang berjalan.</p>
            <span>Mulai sesi lewat halaman <strong>Machine Control</strong> dulu — begitu mesin Running dan part dipilih, angka di sini otomatis muncul dan ter-update tiap beberapa detik.</span>
        </div>

        <div id="mr-konten" style="display:none;">
            <div class="mr-header-part">
                <div class="mr-header-left">
                    <span class="mr-header-label">Part Aktif</span>
                    <span class="mr-header-value" id="mr-nama-part">-</span>
                    <span class="mr-header-jam" id="mr-jam-mulai">Mulai —</span>
                </div>
                <a href="{{ route('machine-control', ['line' => $line_aktif]) }}" class="btn-mr-detail" onclick="navigateTo(currentLine, 'machine-control'); return false;">Detail Machine Control &rarr;</a>
            </div>

            <div class="mr-grid">
                <div class="mr-card mr-card-wide">
                    <div class="mr-card-label">Total Product</div>
                    <div class="mr-card-value mr-card-dark" id="mr-total-product">- PCS</div>
                </div>
                <div class="mr-card mr-card-wide">
                    <div class="mr-card-label">OK Product</div>
                    <div class="mr-card-value mr-card-dark" id="mr-ok-product">- PCS</div>
                </div>
                <div class="mr-card mr-card-wide">
                    <div class="mr-card-label">NG Ratio</div>
                    <div class="mr-card-value mr-card-red" id="mr-ng-ratio">-%</div>
                </div>
                <div class="mr-card mr-card-wide">
                    <div class="mr-card-label">NG Product</div>
                    <div class="mr-card-value mr-card-dark" id="mr-ng-product">- PCS</div>
                </div>

                <div class="mr-card mr-card-highlight">
                    <div class="mr-card-label">Product Rate <span class="mr-card-sub">(H)</span></div>
                    <div class="mr-card-value mr-card-yellow" id="mr-product-rate">-%</div>
                </div>
                <div class="mr-card mr-card-highlight">
                    <div class="mr-card-label">Machine Rate <span class="mr-card-sub">(Working Time %)</span></div>
                    <div class="mr-card-value mr-card-dark" id="mr-machine-rate">-%</div>
                </div>

                <div class="mr-card">
                    <div class="mr-card-label">Product Time <span class="mr-card-sub">(B)</span></div>
                    <div class="mr-card-value mr-card-dark" id="mr-product-time">-</div>
                </div>
                <div class="mr-card">
                    <div class="mr-card-label">Run Time <span class="mr-card-sub">(Shift)</span></div>
                    <div class="mr-card-value mr-card-dark" id="mr-run-time">-</div>
                </div>
                <div class="mr-card">
                    <div class="mr-card-label">Planned <span class="mr-card-sub">(P)</span></div>
                    <div class="mr-card-value mr-card-dark" id="mr-waiting-loss">-</div>
                </div>
                <div class="mr-card">
                    <div class="mr-card-label">Loss Time <span class="mr-card-sub">(I)</span></div>
                    <div class="mr-card-value mr-card-dark" id="mr-machine-trouble">-</div>
                </div>
            </div>

            <a href="{{ route('machine-control', ['line' => $line_aktif]) }}" class="btn-lihat-detail-lain" style="display:inline-block; text-decoration:none; text-align:center;" onclick="navigateTo(currentLine, 'machine-control'); return false;">Lihat detail rumus lengkap di Machine Control &rarr;</a>
        </div>
    </div>
</div>
