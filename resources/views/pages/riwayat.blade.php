<div id="view-riwayat" style="{{ $view_aktif !== 'riwayat' ? 'display:none;' : '' }}">
    <!-- Opsi Bar: Pilihan Tab Riwayat -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
        <div class="riwayat-opsi-bar" style="margin-bottom:0; flex-wrap:wrap;">
            <button type="button" class="riwayat-opsi-btn aktif" id="opsi-produksi" onclick="pilihTabRiwayat('produksi')">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 2 5-6" stroke-linecap="round"/></svg>
                Summary Man Time
            </button>
            <button type="button" class="riwayat-opsi-btn" id="opsi-mc" onclick="pilihTabRiwayat('mc')">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2" stroke-linecap="round"/></svg>
                Matriks Output Part
            </button>
            <button type="button" class="riwayat-opsi-btn" id="opsi-hanger" onclick="pilihTabRiwayat('hanger')">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                Log Hanger AI
            </button>
            <button type="button" class="riwayat-opsi-btn" id="opsi-ng" onclick="pilihTabRiwayat('ng')" style="color:#b91c1c;">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                Detail Part Cacat (NG)
            </button>
        </div>

        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:9px; padding:7px 14px; font-size:13.5px; color:#1e293b; display:inline-flex; align-items:center; gap:8px; height:40px; box-sizing:border-box;">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#0d3b8c" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <strong id="rw-active-operator-badge">Mukhamad Nawwaf</strong>
                <span style="color:#94a3b8;">·</span>
                <span id="rw-active-shift-badge" style="font-weight:600; color:#475569;">Shift 1 (Pagi)</span>
                <button type="button" onclick="bukaModalGantiShift()" class="status-pic-action">Ganti</button>
            </div>

            <a href="/api/download-excel" class="btn-nav-action btn-green" style="height:40px; padding:0 16px; font-size:13.5px; box-sizing:border-box;">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download Excel
            </a>
        </div>
    </div>

    <!-- ===== TAB: RIWAYAT PRODUKSI ===== -->
    <div id="riwayat-tab-produksi">
        <div class="riwayat-search-panel">
            <div class="panel-header-row" style="margin-bottom:12px;">
                <div class="panel-title">Cari Sesi Produksi</div>
                <div class="riwayat-search-badges" style="font-size:13.5px; color:#475569;">
                    Menampilkan: <strong id="rw-label-tanggal-aktif" style="color:#0d3b8c;">Semua Tanggal</strong>
                </div>
            </div>

            <div class="riwayat-search-bar">
                <div class="riwayat-date-input-box">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="#0d3b8c" fill="none" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <input type="date" id="filter-tanggal-produksi" class="riwayat-date-input" style="font-size:14px;" title="Pilih tanggal sesi produksi" onchange="cariSesiProduksiTanggal(this.value)">
                </div>

                <button type="button" class="btn-cari-sesi-produksi" id="btn-cari-sesi" style="font-size:13.5px;" onclick="cariSesiProduksiTanggal()">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Cari Sesi
                </button>

                <div class="btn-group-quick-date" style="display:inline-flex; gap:6px; flex-wrap:wrap;">
                    <button type="button" class="riwayat-quick-btn" style="font-size:13px;" onclick="setTanggalProduksiCari('hari_ini')">Hari Ini</button>
                    <button type="button" class="riwayat-quick-btn" style="font-size:13px;" onclick="setTanggalProduksiCari('kemarin')">Kemarin</button>
                    <button type="button" class="riwayat-quick-btn reset" style="font-size:13px;" onclick="resetTanggalProduksiCari()">Semua Data</button>
                </div>
            </div>

            <!-- Kontainer Hasil Sesi Produksi Tanggal Terpilih -->
            <div id="rw-hasil-sesi-container" class="riwayat-sesi-box" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <div style="font-size:15px; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px;">
                        <span>Daftar Sesi Produksi — <span id="rw-sesi-tgl-label" style="color:#0d3b8c;">-</span></span>
                        <span id="rw-sesi-count-badge" class="badge-shift badge-shift-malam" style="font-size:12px;">0 sesi</span>
                    </div>
                    <div id="rw-sesi-summary-stats" style="font-size:13.5px; color:#475569;">
                        Rata-rata EFF: <strong id="rw-sesi-rata-eff" style="color:#0d3b8c;">0%</strong>
                    </div>
                </div>
                <div id="rw-daftar-sesi-list" class="riwayat-ph-list"></div>
                <div id="rw-sesi-kosong" style="display:none; color:#64748b; text-align:center; padding:24px 0; font-size:14px; background:#f8fafc; border-radius:12px; border:1px dashed #dbe4f3;">
                    Belum ada sesi produksi yang tercatat pada tanggal ini.
                </div>
            </div>
        </div>

        <div class="kpi-summary-grid">
            <div class="summary-chip">
                <span class="chip-label">Barang Masuk (IN)</span>
                <span class="chip-value" id="rw-total-input" style="color:#1e3a8a;">-</span>
            </div>

            <div class="summary-chip chip-green">
                <span class="chip-label">Output Selesai (OK)</span>
                <span class="chip-value" id="rw-total-output" style="color:#059669;">-</span>
            </div>

            <div class="summary-chip chip-red">
                <span class="chip-label" style="color:#991b1b;">Part Cacat (NG)</span>
                <span class="chip-value" id="rw-total-ng" style="color:#dc2626;">-</span>
                <div id="rw-defect-rate" style="font-size:12.5px; color:#dc2626; font-weight:700; margin-top:2px;">Defect Rate: 0.0%</div>
            </div>

            <div class="summary-chip highlight">
                <span class="chip-label">Yield Produksi</span>
                <span class="chip-value" id="rw-yield">-</span>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header-row" style="margin-bottom:12px;">
                <div class="panel-title">Histori Data Produksi</div>
            </div>
            <table id="tabel-histori">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th>Tanggal</th>
                        <th>PIC &amp; Shift</th>
                        <th style="text-align:right;">Masuk (IN)</th>
                        <th style="text-align:right;">OK</th>
                        <th style="text-align:right;">NG</th>
                        <th style="text-align:center;">Yield</th>
                        <th style="text-align:center;">Detail</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="isi-tabel-histori"></tbody>
            </table>
        </div>
    </div>

    <!-- ===== TAB: MATRIKS OUTPUT PART ===== -->
    <div id="riwayat-tab-mc" style="display:none;">
        <div class="kpi-summary-grid" style="grid-template-columns:repeat(auto-fit, minmax(220px, 300px));">
            <div class="summary-chip">
                <span class="chip-label">Jumlah Sesi</span>
                <span class="chip-value" id="ph-jumlah">-</span>
            </div>
            <div class="summary-chip highlight">
                <span class="chip-label">Rata-rata EFF</span>
                <span class="chip-value" id="ph-rata-eff">-</span>
            </div>
        </div>

        <div class="panel" id="panel-riwayat-performance">
            <div class="panel-header-row">
                <div class="panel-title">Riwayat Hasil Produksi — <span id="ph-tgl-label" style="color:#0d3b8c;">Hari Ini</span></div>
                <div class="panel-controls">
                    <div class="date-picker-wrapper" style="display:inline-flex; align-items:center; gap:6px;">
                        <button type="button" class="riwayat-quick-btn" onclick="setTanggalPh('hari_ini')" style="padding:7px 14px; font-size:13px;">Hari Ini</button>
                        <button type="button" class="riwayat-quick-btn" onclick="setTanggalPh('kemarin')" style="padding:7px 14px; font-size:13px;">Kemarin</button>
                        <button type="button" class="filter-select" id="btn-tanggal-ph" onclick="bukaPickerPerformance()">
                            <svg viewBox="0 0 24 24" width="14" height="14" style="stroke:currentColor;fill:none;stroke-width:2;vertical-align:-2px;margin-right:6px;"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <span id="teks-tanggal-ph">Hari Ini</span>
                        </button>
                        <input type="date" id="filter-tanggal-ph" onchange="loadRiwayatPerformance()" style="position:absolute; opacity:0; pointer-events:none; width:1px; height:1px;">
                    </div>
                </div>
            </div>
            <div id="ph-kosong" style="display:none; color:#64748b; text-align:center; padding:24px 0; font-size:14px;">
                Belum ada riwayat sesi untuk tanggal ini.
            </div>
            <div id="daftar-riwayat-ph" class="riwayat-ph-list"></div>
        </div>
    </div>

    <!-- ===== TAB: LOG VERIFIKASI HANGER AI ===== -->
    <div id="riwayat-tab-hanger" style="display:none;">
        <div class="panel">
            <div class="panel-header-row" style="margin-bottom:14px;">
                <div class="panel-title">Log Verifikasi Hanger AI</div>
                <button type="button" onclick="loadHangerDetectionLogs()" class="riwayat-quick-btn" style="font-size:13.5px; display:inline-flex; align-items:center; gap:6px;">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    Refresh Log
                </button>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:14px; text-align:left;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0; color:#475569;">
                            <th style="padding:12px;">Waktu</th>
                            <th style="padding:12px;">No. Hanger</th>
                            <th style="padding:12px;">Kode Part</th>
                            <th style="padding:12px; text-align:center;">Hasil AI (Pcs)</th>
                            <th style="padding:12px; text-align:center;">Target</th>
                            <th style="padding:12px; text-align:center;">Status</th>
                            <th style="padding:12px;">Operator PIC</th>
                            <th style="padding:12px;">Shift</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-hanger-detection-logs">
                        <tr>
                            <td colspan="8" style="text-align:center; padding:24px; color:#64748b;">
                                Memuat log verifikasi hanger...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== TAB: DETAIL PART CACAT (NG) ===== -->
    <div id="riwayat-tab-ng" style="display:none;">
        <div class="riwayat-search-panel" style="margin-bottom:18px;">
            <div class="panel-header-row" style="margin-bottom:12px;">
                <div class="panel-title" style="color:#b91c1c;">Log Part Cacat (NG)</div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" onclick="bukaModalCatatNg()" class="btn-nav-action" style="height:38px; padding:0 16px; background:#dc2626; color:#ffffff; border:none; font-size:13.5px;">
                        + Catat NG Manual
                    </button>
                    <button type="button" onclick="loadRiwayatNg()" class="riwayat-quick-btn" style="height:38px; font-size:13.5px; display:inline-flex; align-items:center; gap:6px;">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        Refresh
                    </button>
                </div>
            </div>

            <div class="riwayat-search-bar" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <div class="riwayat-date-input-box">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="#dc2626" fill="none" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <input type="date" id="filter-tanggal-ng" class="riwayat-date-input" style="font-size:14px;" title="Filter berdasarkan tanggal cacat" onchange="loadRiwayatNg(this.value)">
                </div>
                <div class="btn-group-quick-date" style="display:inline-flex; gap:6px; flex-wrap:wrap;">
                    <button type="button" class="riwayat-quick-btn" style="font-size:13px;" onclick="setTanggalNgCari('hari_ini')">Hari Ini</button>
                    <button type="button" class="riwayat-quick-btn" style="font-size:13px;" onclick="setTanggalNgCari('kemarin')">Kemarin</button>
                    <button type="button" class="riwayat-quick-btn reset" style="font-size:13px;" onclick="resetTanggalNgCari()">Semua Data</button>
                </div>
                <div style="font-size:13.5px; color:#475569; margin-left:auto;">
                    Menampilkan: <strong id="rw-ng-label-tanggal-aktif" style="color:#b91c1c;">Semua Tanggal</strong>
                </div>
            </div>
        </div>

        <div class="kpi-summary-grid">
            <div class="summary-chip chip-red">
                <span class="chip-label" style="color:#991b1b;">Total Defect (NG)</span>
                <span class="chip-value" id="rw-ng-kpi-total-pcs" style="color:#dc2626;">0 pcs</span>
            </div>
            <div class="summary-chip chip-amber">
                <span class="chip-label" style="color:#9a3412;">Defect Ratio</span>
                <span class="chip-value" id="rw-ng-kpi-defect-rate" style="color:#ea580c;">0.0%</span>
            </div>
            <div class="summary-chip">
                <span class="chip-label" style="color:#1e40af;">Defect Terbanyak</span>
                <span class="chip-value" id="rw-ng-kpi-top-defect" style="font-size:21px; color:#0d3b8c; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">-</span>
            </div>
        </div>

        <div class="panel" style="padding:16px 20px; margin-bottom:18px;">
            <div style="font-size:15px; font-weight:700; color:#1e293b; margin-bottom:10px; display:flex; align-items:center; justify-content:space-between;">
                <span>Distribusi Jenis Cacat</span>
                <span id="rw-ng-count-types" style="font-size:13px; color:#64748b; font-weight:600;">0 jenis terdeteksi</span>
            </div>
            <div id="rw-ng-pareto-chips" style="display:flex; flex-wrap:wrap; gap:8px;">
                <span style="font-size:13.5px; color:#64748b;">Belum ada catatan cacat pada periode ini.</span>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header-row" style="margin-bottom:12px;">
                <div class="panel-title">Daftar Log Part Cacat (NG)</div>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:14px; text-align:left;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0; color:#475569;">
                            <th style="padding:12px; width:150px;">Waktu &amp; Tanggal</th>
                            <th style="padding:12px;">Part</th>
                            <th style="padding:12px;">Jenis Cacat</th>
                            <th style="padding:12px; text-align:center;">Jumlah (Pcs)</th>
                            <th style="padding:12px;">PIC &amp; Shift</th>
                            <th style="padding:12px;">Catatan</th>
                            <th style="padding:12px; text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-riwayat-ng-logs">
                        <tr>
                            <td colspan="7" style="text-align:center; padding:24px; color:#64748b;">
                                Memuat log part cacat...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
