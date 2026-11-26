@php
    $dashStatsFile = storage_path('app/hanger_stats.json');
    $dashStats = file_exists($dashStatsFile) ? (@json_decode(file_get_contents($dashStatsFile), true) ?: []) : [];
    $dashIsRunning = !empty($dashStats['is_produksi']);
@endphp
<div id="view-dashboard" style="{{ $view_aktif !== 'dashboard' ? 'display:none;' : '' }}">
    <!-- ===== TOP CONTROL BAR (Grid 2 Kolom: Status Mesin & Part Aktif) ===== -->
    <div class="dashboard-top-grid">
        <!-- Kolom Kiri: Status Mesin -->
        <div class="panel tower-light-panel" id="tower-light-panel-card">
            <div class="status-main-row">
                <div class="tower-light-visual" id="tower-light-visual" title="Patlite Status: {{ $dashIsRunning ? 'HIJAU (RUNNING)' : 'MERAH (STOP)' }}">
                    <div class="tower-lamp lamp-merah {{ !$dashIsRunning ? 'aktif' : '' }}" id="lamp-merah" title="Status Stop / Standby"></div>
                    <div class="tower-lamp lamp-hijau {{ $dashIsRunning ? 'aktif' : '' }}" id="lamp-hijau" title="Status Running / Produksi Aktif"></div>
                </div>
                <div class="tower-light-info" style="padding-right:0;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:4px;">
                        <div class="tower-light-label" style="margin-bottom:0;">Status Mesin</div>
                        <span id="mr-jam-mulai" class="status-session-pill">Sesi Berjalan</span>
                    </div>
                    <div class="tower-light-status" id="teks-status-mesin" style="color:{{ $dashIsRunning ? '#16a34a' : '#dc2626' }};">
                        {{ $dashIsRunning ? 'RUNNING (PRODUKSI AKTIF)' : 'STOP / STANDBY (OFF)' }}
                    </div>
                    <div class="tower-light-updated" id="teks-update-status">
                        Terakhir diperbarui: {{ !empty($dashStats['last_updated']) ? (date('H:i:s \W\I\B', strtotime($dashStats['last_updated'])) ?: 'Live') : 'Live' }}
                    </div>
                    <div class="tower-light-warning" id="stop-alasan-warning" style="display:none;" onclick="bukaModalAlasanStop()">
                        <span class="pulse-dot-warning"></span>
                        <span id="stop-alasan-warning-teks">Belum sempat isi alasan stop? Klik untuk isi alasan stop</span>
                    </div>
                </div>
            </div>

            <div class="status-pic-footer">
                <div class="status-pic-btn" onclick="bukaModalGantiShift()" title="Klik untuk ganti operator shift">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#0d3b8c" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>PIC: <strong id="dash-pic-operator" style="color:#0f172a;">Mukhamad Nawwaf</strong> <span id="dash-pic-shift" style="color:#64748b;">(Shift 1 - Pagi)</span></span>
                </div>
                <button type="button" class="status-pic-action" onclick="bukaModalGantiShift()">
                    Ganti Shift
                </button>
            </div>
        </div>

        <!-- Kolom Kanan: Part Aktif & Changeover -->
        @include('partials.choose-part')
    </div>

    <!-- ===== PANEL TARGET PRODUKSI BULANAN (PCS) ===== -->
    <div class="panel target-panel">
        <div class="target-header-row">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <div class="panel-title" style="margin-bottom:0;">Target Produksi Bulanan</div>
                <span id="badge-nama-bulan-target" style="font-size:12.5px; font-weight:700; background:#eff6ff; color:#1e40af; padding:4px 12px; border-radius:999px; border:1px solid #bfdbfe;">Bulan Ini</span>
            </div>

            <div class="target-form-group">
                <label for="input-target-pcs-bulan">Target (PCS)</label>
                <input type="number" id="input-target-pcs-bulan" placeholder="50000" min="1" step="100">
                <button type="button" id="btn-simpan-target-bulanan" class="btn-nav-action btn-blue" style="height:38px; padding:0 18px; border:none; font-size:13.5px;" onclick="simpanTargetBulanan()">
                    Simpan
                </button>
            </div>
        </div>

        <div class="target-stat-grid">
            <div class="target-stat-card accent-slate">
                <div class="target-stat-label">Target Bulan Ini</div>
                <div class="target-stat-val-row">
                    <span id="label-target-pcs-display" class="target-stat-val" style="color:#0f172a;">50.000</span>
                    <span class="target-stat-unit">PCS</span>
                </div>
            </div>

            <div class="target-stat-card accent-blue">
                <div class="target-stat-label">Output Aktual</div>
                <div class="target-stat-val-row">
                    <span id="label-output-aktual-bulan" class="target-stat-val" style="color:#0d3b8c;">0</span>
                    <span class="target-stat-unit">PCS</span>
                </div>
                <div id="label-sisa-target-text" class="target-stat-sub">-</div>
            </div>

            <div class="target-stat-card accent-green" style="flex-direction:row; align-items:center; justify-content:space-between; gap:10px;">
                <div>
                    <div class="target-stat-label">Pencapaian</div>
                    <span id="label-persentase-target" class="target-stat-val" style="color:#16a34a;">0.0%</span>
                </div>
                <span id="badge-status-pencapaian" style="font-size:12.5px; font-weight:700; padding:5px 12px; border-radius:8px; background:#fef9c3; color:#854d0e; border:1px solid #fef08a; white-space:nowrap;">
                    Dalam Proses
                </span>
            </div>
        </div>

        <div class="target-progress-track">
            <div id="progress-bar-target-bulan" class="target-progress-fill"></div>
        </div>
    </div>

    <!-- ===== KARTU METRIK PRODUKSI: 3 ATAS (PCS) & 3 BAWAH (HANGER) ===== -->
    <div class="metrics-split-container">
        <!-- BARIS 1 (ATAS): METRIK PRODUKSI UNIT (PCS) -->
        <div class="metric-section-group">
            <div class="metric-group-header">
                <div class="metric-group-badge badge-pcs">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>
                    <span>METRIK PRODUKSI <strong class="badge-unit-bold">(PCS)</strong></span>
                </div>
                <div class="metric-mode-pill-group">
                    <button type="button" class="metric-mode-btn active" id="btn-mode-shift" onclick="setDashboardModeProduksi('shift')">Shift Aktif</button>
                    <button type="button" class="metric-mode-btn" id="btn-mode-harian" onclick="setDashboardModeProduksi('harian')">Total 1 Hari</button>
                </div>
            </div>
            <div class="metric-grid-3">
                <!-- 1. KELUARAN TOTAL PRODUKSI (PCS) -->
                <div class="metric-card output">
                    <div class="metric-card-header">
                        <div class="icon">OUT</div>
                        <div class="metric-tanggal-label" id="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="output-value">0</div>
                    <span id="input-value" style="display:none;">0</span>
                    <div class="label">
                        <span>Keluaran Total</span>
                        <span class="metric-unit-tag">PCS</span>
                    </div>
                </div>

                <!-- 2. PART CACAT / DEFECT (NG) -->
                <div class="metric-card ng">
                    <div class="metric-card-header">
                        <div class="icon">NG</div>
                        <button type="button" class="btn-cat-ng-card" onclick="bukaModalInputNg()">
                            + Catat NG
                        </button>
                    </div>
                    <div class="value skeleton" id="ng-value" style="color:#ffffff;">0</div>
                    <div class="label" style="justify-content:space-between;">
                        <span>Part Cacat (NG) <span class="metric-unit-tag">PCS</span></span>
                        <span style="font-size:13px; font-weight:700; opacity:0.95;" id="ng-subtext">0.0% Defect</span>
                    </div>
                </div>

                <!-- 3. PCS: Yield -->
                <div class="metric-card yield">
                    <div class="metric-card-header">
                        <div class="icon">%</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="yield-value">0.0%</div>
                    <div class="label">
                        <span>Yield Produksi</span>
                        <span class="metric-unit-tag">%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- BARIS 2 (BAWAH): METRIK HANGER -->
        <div class="metric-section-group">
            <div class="metric-group-header">
                <div class="metric-group-badge badge-sheet">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>METRIK HANGER</span>
                </div>
            </div>
            <div class="metric-grid-3">
                <div class="metric-card sheet-input">
                    <div class="metric-card-header">
                        <div class="icon">HANGER</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="sheet-input-value">0</div>
                    <div class="label">Total Hanger <span class="metric-unit-tag sheet">HANGER</span></div>
                </div>

                <div class="metric-card sheet-output">
                    <div class="metric-card-header">
                        <div class="icon">TERISI</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="sheet-output-value">0</div>
                    <div class="label">Hanger Terisi <span class="metric-unit-tag sheet">TERISI</span></div>
                </div>

                <div class="metric-card sheet-yield">
                    <div class="metric-card-header">
                        <div class="icon">KOSONG</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="sheet-yield-value">0</div>
                    <div class="label">Hanger Kosong <span class="metric-unit-tag sheet">KOSONG</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
