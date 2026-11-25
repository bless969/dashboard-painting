@php
    $dashStatsFile = storage_path('app/hanger_stats.json');
    $dashStats = file_exists($dashStatsFile) ? (@json_decode(file_get_contents($dashStatsFile), true) ?: []) : [];
    $dashIsRunning = !empty($dashStats['is_produksi']);
@endphp
<div id="view-dashboard" style="{{ $view_aktif !== 'dashboard' ? 'display:none;' : '' }}">
    <!-- ===== TOP CONTROL BAR (Grid 2 Kolom: Status Mesin & Choose Part) ===== -->
    <div class="dashboard-top-grid">
        <!-- Kolom Kiri: Status Mesin Painting (Unified Modern Card) -->
        <div class="panel tower-light-panel panel-status-mesin-card" id="tower-light-panel-card" style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:14px; padding:18px 20px; box-shadow:0 3px 12px rgba(13, 59, 140, 0.05); display:flex; flex-direction:column; justify-content:space-between; align-items:stretch; height:100%; margin-bottom:0; width:100%; box-sizing:border-box;">
            <!-- Header Kartu: Status Mesin Painting -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px; border-bottom:1px solid #f1f5f9; padding-bottom:10px; width:100%; box-sizing:border-box;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; border-radius:8px; background:#eff6ff; display:flex; align-items:center; justify-content:center; color:#0d3b8c; font-size:18px; border:1.5px solid #bfdbfe;">
                        🚦
                    </div>
                    <div>
                        <h3 style="margin:0; font-size:15.5px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                            Status Mesin Painting
                            <span style="font-size:11px; font-weight:700; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:12px;">RS-485 Live</span>
                        </h3>
                        <div style="font-size:12px; color:#64748b; margin-top:2px;">
                            Monitoring status konveyor, patlite & booth painting
                        </div>
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    <span id="mr-jam-mulai" style="font-size:12px; font-weight:700; background:#dcfce7; color:#15803d; padding:4px 10px; border-radius:12px; display:inline-flex; align-items:center; gap:6px; border:1px solid #bbf7d0;">
                        <span style="width:7px; height:7px; border-radius:50%; background:#22c55e;"></span>
                        Sesi Berjalan (Mulai 06:55)
                    </span>
                </div>
            </div>

            <!-- Body: Visual Tower Lamp & Status Utama -->
            <div style="display:flex; align-items:center; justify-content:space-between; gap:20px; padding:6px 0; flex:1; width:100%; box-sizing:border-box;">
                <div style="display:flex; align-items:center; gap:18px;">
                    <!-- Sleek Industrial Patlite Tower Visual -->
                    <div class="tower-light-visual" id="tower-light-visual" title="Patlite Status: {{ $dashIsRunning ? 'HIJAU (RUNNING)' : 'MERAH (STOP)' }}">
                        <div class="tower-lamp lamp-merah {{ !$dashIsRunning ? 'aktif' : '' }}" id="lamp-merah" title="Status Stop / Standby"></div>
                        <div class="tower-lamp lamp-hijau {{ $dashIsRunning ? 'aktif' : '' }}" id="lamp-hijau" title="Status Running / Produksi Aktif"></div>
                    </div>

                    <div class="tower-light-info">
                        <div class="tower-light-label" style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Kondisi Operasional Lini</div>
                        <div class="tower-light-status" id="teks-status-mesin" style="font-size:20px; font-weight:900; color:{{ $dashIsRunning ? '#16a34a' : '#dc2626' }}; line-height:1.2; margin-bottom:6px;">
                            {{ $dashIsRunning ? 'RUNNING (PRODUKSI AKTIF)' : 'STOP / STANDBY (OFF)' }}
                        </div>
                        <div class="tower-light-updated" id="teks-update-status" style="font-size:12px; color:#64748b; font-weight:600; display:flex; align-items:center; gap:5px;">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span>Terakhir diperbarui: {{ !empty($dashStats['last_updated']) ? (date('H:i:s \W\I\B', strtotime($dashStats['last_updated'])) ?: 'Live') : 'Live' }}</span>
                        </div>

                        <div class="tower-light-warning" id="stop-alasan-warning" style="display:none; margin-top:8px;" onclick="bukaModalAlasanStop()">
                            <span class="pulse-dot-warning"></span>
                            <span id="stop-alasan-warning-teks">Belum sempat isi alasan stop? Klik untuk isi alasan stop</span>
                        </div>
                    </div>
                </div>

                <!-- Industrial Telemetry Mini-Chips -->
                <div style="display:flex; flex-direction:column; gap:8px; min-width:145px;">
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:6px 12px;">
                        <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.3px;">Konveyor Lini</div>
                        <div style="font-size:12.5px; font-weight:800; color:{{ $dashIsRunning ? '#16a34a' : '#dc2626' }}; display:flex; align-items:center; gap:5px; margin-top:1px;">
                            <span style="width:7px; height:7px; border-radius:50%; background:{{ $dashIsRunning ? '#22c55e' : '#ef4444' }};"></span>
                            {{ $dashIsRunning ? 'Normal Berjalan' : 'Standby / Berhenti' }}
                        </div>
                    </div>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:6px 12px;">
                        <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.3px;">Hardware Sinyal</div>
                        <div style="font-size:12.5px; font-weight:800; color:#0d3b8c; display:flex; align-items:center; gap:5px; margin-top:1px;">
                            <span>⚡ RS-485 Modbus</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer: PIC Operator & Patlite Fisik (Full Width, Flush Rata Kiri & Kanan) -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; width:100%; box-sizing:border-box;">
                <div onclick="bukaModalGantiShift()" style="display:flex; align-items:center; gap:8px; cursor:pointer;" title="Klik untuk Ganti Operator Shift">
                    <span style="font-size:12px; color:#475569; font-weight:700;">👤 PIC Shift:</span>
                    <strong style="font-size:12.5px; color:#0d3b8c;">Mukhamad Nawwaf</strong>
                    <span style="font-size:11.5px; color:#64748b;">(Shift 1 - Pagi)</span>
                </div>
                <div style="font-size:11.5px; font-weight:600; color:#475569; display:inline-flex; align-items:center; gap:4px;">
                    <span>⚡ Patlite Standar 2 Warna</span>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Kontrol Produksi & Pilih Part -->
        @include('partials.choose-part')
    </div>

    <!-- ===== PANEL TARGET PRODUKSI BULANAN (PCS) & PENCAPAIAN (%) ===== -->
    <div class="panel panel-target-bulanan" style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:14px; padding:18px 20px; margin-bottom:20px; box-shadow:0 3px 12px rgba(13, 59, 140, 0.05);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:14px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:36px; height:36px; border-radius:8px; background:#eff6ff; display:flex; align-items:center; justify-content:center; color:#0d3b8c; font-size:18px; border:1.5px solid #bfdbfe;">
                    🎯
                </div>
                <div>
                    <h3 style="margin:0; font-size:15.5px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                        Target Produksi Bulanan (Line Painting)
                        <span id="badge-nama-bulan-target" style="font-size:11px; font-weight:700; background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:5px; border:1px solid #cbd5e1;">Bulan Ini</span>
                    </h3>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">
                        Pencapaian output aktual terhadap kuota target produksi bulan berjalan.
                    </div>
                </div>
            </div>

            <!-- Form Input Target Cepat -->
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="position:relative; display:flex; align-items:center;">
                    <span style="position:absolute; left:10px; font-size:11px; font-weight:700; color:#94a3b8; pointer-events:none;">TARGET:</span>
                    <input type="number" id="input-target-pcs-bulan" class="filter-select" style="padding:6px 10px 6px 62px; font-size:12.5px; font-weight:700; width:160px; border-radius:6px; color:#0d3b8c; border:1.5px solid #cbd5e1;" placeholder="50000" min="1" step="100">
                </div>
                <button type="button" id="btn-simpan-target-bulanan" class="btn-simpan-produksi" style="padding:6px 14px; background:#0d3b8c; color:white; border:none; border-radius:6px; font-weight:700; font-size:12px; cursor:pointer;" onclick="simpanTargetBulanan()">
                    💾 Simpan
                </button>
            </div>
        </div>

        <!-- 3 Metrik Utama: Target vs Output Aktual vs Persentase -->
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:14px;">
            <!-- 1. Target Bulanan -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px;">
                <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:3px;">Target Bulan Ini</div>
                <div style="display:flex; align-items:baseline; gap:6px;">
                    <span id="label-target-pcs-display" style="font-size:22px; font-weight:800; color:#0f172a; line-height:1.1;">50.000</span>
                    <span style="font-size:12px; font-weight:600; color:#64748b;">PCS</span>
                </div>
                <div style="font-size:11px; color:#64748b; margin-top:3px;">Kuota yang ditentukan</div>
            </div>

            <!-- 2. Output Aktual Bulan Ini -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px;">
                <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:3px;">Output Aktual Bulan Ini</div>
                <div style="display:flex; align-items:baseline; gap:6px;">
                    <span id="label-output-aktual-bulan" style="font-size:22px; font-weight:800; color:#0d3b8c; line-height:1.1;">0</span>
                    <span style="font-size:12px; font-weight:600; color:#64748b;">PCS</span>
                </div>
                <div style="font-size:11px; color:#16a34a; font-weight:600; margin-top:3px;" id="label-sisa-target-text">Menghitung...</div>
            </div>

            <!-- 3. Persentase Pencapaian Target -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:3px;">Pencapaian Target</div>
                    <div style="display:flex; align-items:baseline; gap:4px;">
                        <span id="label-persentase-target" style="font-size:24px; font-weight:800; color:#16a34a; line-height:1.1;">0.0%</span>
                    </div>
                </div>
                <div>
                    <span id="badge-status-pencapaian" style="display:inline-block; font-size:11px; font-weight:700; padding:4px 10px; border-radius:6px; background:#fef9c3; color:#854d0e; border:1px solid #fef08a;">
                        Dalam Proses
                    </span>
                </div>
            </div>
        </div>

        <!-- Progress Bar Pencapaian Bulanan -->
        <div style="background:#e2e8f0; border-radius:8px; height:8px; width:100%; overflow:hidden; position:relative;">
            <div id="progress-bar-target-bulan" style="height:100%; width:0%; background:linear-gradient(90deg, #0d3b8c, #16a34a); border-radius:8px; transition:width 0.8s cubic-bezier(0.4, 0, 0.2, 1);"></div>
        </div>
    </div>

    <!-- ===== KARTU METRIK PRODUKSI: 3 ATAS (PCS) & 3 BAWAH (HANGER / WADAH) ===== -->
    <div class="metrics-split-container">
        <!-- BARIS 1 (ATAS): METRIK PRODUKSI UNIT (PCS) -->
        <div class="metric-section-group">
            <div class="metric-group-header">
                <div class="metric-group-badge badge-pcs">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>
                    <span>METRIK PRODUKSI (PCS)</span>
                </div>
                <div class="metric-mode-pill-group">
                    <button type="button" class="metric-mode-btn active" id="btn-mode-shift" onclick="setDashboardModeProduksi('shift')">⏱️ Shift Aktif</button>
                    <button type="button" class="metric-mode-btn" id="btn-mode-harian" onclick="setDashboardModeProduksi('harian')">📅 Total 1 Hari</button>
                </div>
            </div>
            <div class="metric-grid-3">
                <!-- 1. KELUARAN TOTAL PRODUKSI (PCS) -->
                <div class="metric-card output">
                    <div class="metric-card-header">
                        <div class="icon">OUT TOTAL</div>
                        <div class="metric-tanggal-label" id="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="output-value">0</div>
                    <span id="input-value" style="display:none;">0</span>
                    <div class="label" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px;">
                        <span>Keluaran Total <span class="metric-unit-tag">PCS</span></span>
                        <span style="font-size:11px; opacity:0.92; font-weight:700; font-family:monospace; background:rgba(255,255,255,0.22); padding:2px 8px; border-radius:6px;">• Kamera AI</span>
                    </div>
                </div>

                <!-- 2. PART CACAT / DEFECT (NG) -->
                <div class="metric-card ng">
                    <div class="metric-card-header">
                        <div class="icon">NG / REJECT</div>
                        <button type="button" onclick="bukaModalInputNg()" style="background:#ffffff; color:#b91c1c; border:none; border-radius:6px; font-size:11.5px; font-weight:800; padding:0 12px; height:28px; cursor:pointer; box-shadow:0 2px 6px rgba(0,0,0,0.15); display:inline-flex; align-items:center; gap:4px; transition:transform 0.15s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            ➕ Catat NG
                        </button>
                    </div>
                    <div class="value skeleton" id="ng-value" style="color:#ffffff;">0</div>
                    <div class="label" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px;">
                        <span>Part Cacat (NG) <span class="metric-unit-tag">PCS</span></span>
                        <span style="font-size:11px; opacity:0.92; font-weight:700; font-family:monospace; background:rgba(255,255,255,0.22); padding:2px 8px; border-radius:6px;" id="ng-subtext">0.0% Defect</span>
                    </div>
                </div>

                <!-- 3. PCS: Yield -->
                <div class="metric-card yield">
                    <div class="metric-card-header">
                        <div class="icon">% YIELD</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="yield-value">0.0%</div>
                    <div class="label" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:6px;">
                        <span>Yield Produksi <span class="metric-unit-tag">%</span></span>
                        <span style="font-size:11px; opacity:0.92; font-weight:700; font-family:monospace; background:rgba(0,0,0,0.12); padding:2px 8px; border-radius:6px;" id="yield-formula-text">OK ÷ Masuk</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- BARIS 2 (BAWAH): METRIK PRODUKSI MATERIAL / HANGER -->
        <div class="metric-section-group">
            <div class="metric-group-header">
                <div class="metric-group-badge badge-sheet">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>METRIK HANGER</span>
                </div>
            </div>
            <div class="metric-grid-3">
                <!-- Hanger: Total Hanger Lewat -->
                <div class="metric-card sheet-input">
                    <div class="metric-card-header">
                        <div class="icon">HNG TOT</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="sheet-input-value">0</div>
                    <div class="label">Total Hanger <span class="metric-unit-tag sheet">HANGER</span></div>
                </div>

                <!-- Hanger: Hanger Terisi -->
                <div class="metric-card sheet-output">
                    <div class="metric-card-header">
                        <div class="icon">HNG OK</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="sheet-output-value">0</div>
                    <div class="label">Hanger Terisi <span class="metric-unit-tag sheet">TERISI</span></div>
                </div>

                <!-- Hanger: Hanger Kosong -->
                <div class="metric-card sheet-yield">
                    <div class="metric-card-header">
                        <div class="icon">HNG 0</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="sheet-yield-value">0</div>
                    <div class="label">Hanger Kosong <span class="metric-unit-tag sheet">KOSONG</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
