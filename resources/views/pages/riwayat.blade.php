<div id="view-riwayat" style="{{ $view_aktif !== 'riwayat' ? 'display:none;' : '' }}">
    <!-- Opsi Bar: Pilihan Antara Riwayat Produksi, Matriks Output Part, & Log Hanger AI -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
        <div class="riwayat-opsi-bar" style="margin-bottom:0; display:flex; gap:6px; flex-wrap:wrap;">
            <button type="button" class="riwayat-opsi-btn aktif" id="opsi-produksi" onclick="pilihTabRiwayat('produksi')">
                <svg viewBox="0 0 24 24" width="15" height="15"><path d="M3 3v18h18" stroke="currentColor" stroke-width="2" fill="none"/><path d="M7 14l4-4 4 2 5-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                Summary Man Time
            </button>
            <button type="button" class="riwayat-opsi-btn" id="opsi-mc" onclick="pilihTabRiwayat('mc')">
                <svg viewBox="0 0 24 24" width="15" height="15"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 8v4l3 2" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                Matriks Output Part (Shift A/B/C)
            </button>
            <button type="button" class="riwayat-opsi-btn" id="opsi-hanger" onclick="pilihTabRiwayat('hanger')">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                Log Hanger AI (Audit Trail)
            </button>
            <button type="button" class="riwayat-opsi-btn" id="opsi-ng" onclick="pilihTabRiwayat('ng')" style="color:#b91c1c;">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Detail Part Cacat (NG)
            </button>
        </div>

        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <!-- Badge Operator PIC & Shift Bertugas -->
            <div style="background:#eef2ff; border:1px solid #c7d2fe; border-radius:8px; padding:6px 12px; font-size:12.5px; color:#3730a3; display:inline-flex; align-items:center; gap:8px;">
                <span>👤 PIC:</span>
                <strong id="rw-active-operator-badge">Mukhamad Nawwaf</strong>
                <span style="color:#6366f1;">•</span>
                <span id="rw-active-shift-badge">Shift 1 (Pagi)</span>
                <button type="button" onclick="bukaModalGantiShift()" style="background:#4f46e5; color:white; border:none; padding:3px 8px; border-radius:5px; font-size:11px; font-weight:700; cursor:pointer;" title="Ganti PIC Operator Shift">Ganti</button>
            </div>

            <a href="/api/download-excel" class="btn-nav-action btn-green" style="padding:9px 16px; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px;" title="Unduh file laporan bulanan resmi sesuai format 08_MAN TIME_ PAINTING_AUG_26.xlsx">
                <span>📥</span> Download Excel (Format Pabrik Asli)
            </a>
        </div>
    </div>

    <!-- ===== TAB: RIWAYAT PRODUKSI ===== -->
    <div id="riwayat-tab-produksi">
        <!-- Panel Pencarian Sesi Produksi & Filter Tanggal -->
        <div class="riwayat-search-panel">
            <div class="panel-header-row" style="margin-bottom:12px;">
                <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#0d3b8c" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <span>Cari Sesi Produksi Berdasarkan Tanggal</span>
                </div>
                <div class="riwayat-search-badges" style="font-size:12.5px; color:#6b6b80;">
                    Menampilkan: <strong id="rw-label-tanggal-aktif" style="color:#0d3b8c;">Semua Tanggal</strong>
                </div>
            </div>

            <div class="riwayat-search-bar">
                <div class="riwayat-date-input-box">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="#0d3b8c" fill="none" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <input type="date" id="filter-tanggal-produksi" class="riwayat-date-input" title="Pilih tanggal sesi produksi" onchange="cariSesiProduksiTanggal(this.value)">
                </div>

                <button type="button" class="btn-cari-sesi-produksi" id="btn-cari-sesi" onclick="cariSesiProduksiTanggal()">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Cari Sesi
                </button>

                <div class="btn-group-quick-date" style="display:inline-flex; gap:6px; flex-wrap:wrap;">
                    <button type="button" class="riwayat-quick-btn" onclick="setTanggalProduksiCari('hari_ini')">Hari Ini</button>
                    <button type="button" class="riwayat-quick-btn" onclick="setTanggalProduksiCari('kemarin')">Kemarin</button>
                    <button type="button" class="riwayat-quick-btn reset" onclick="resetTanggalProduksiCari()">Semua Data</button>
                </div>
            </div>

            <!-- Kontainer Hasil Sesi Produksi Tanggal Terpilih -->
            <div id="rw-hasil-sesi-container" class="riwayat-sesi-box" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <div style="font-size:14px; font-weight:700; color:#2b2b3a; display:flex; align-items:center; gap:8px;">
                        <span>Daftar Sesi Produksi — <span id="rw-sesi-tgl-label" style="color:#0d3b8c;">-</span></span>
                        <span id="rw-sesi-count-badge" class="badge-shift badge-shift-malam" style="font-size:11px;">0 sesi</span>
                    </div>
                    <div id="rw-sesi-summary-stats" style="font-size:12.5px; color:#6b6b80;">
                        Rata-rata EFF: <strong id="rw-sesi-rata-eff" style="color:#0d3b8c;">0%</strong>
                    </div>
                </div>
                <div id="rw-daftar-sesi-list" class="riwayat-ph-list"></div>
                <div id="rw-sesi-kosong" style="display:none; color:#9a9ab0; text-align:center; padding:24px 0; font-size:13px; background:#f8fafc; border-radius:12px; border:1px dashed #dbe4f3;">
                    Belum ada sesi produksi yang tercatat pada tanggal ini.
                </div>
            </div>
        </div>

        <div class="riwayat-summary" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:12px; margin-bottom:16px;">
            <div class="summary-chip" style="background:#ffffff; border-radius:12px; padding:12px 16px; border:1px solid #dbeafe; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <div style="font-size:12px; color:#1e40af; font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#2563eb" stroke-width="2.5"><polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>
                    Total Barang Masuk (IN)
                </div>
                <div class="chip-value" id="rw-total-input" style="font-size:24px; font-weight:800; color:#1e3a8a;">-</div>
                <div style="font-size:11.5px; color:#64748b; margin-top:2px;">Output OK + NG (Total Masuk)</div>
            </div>

            <div class="summary-chip" style="background:#ffffff; border-radius:12px; padding:12px 16px; border:1px solid #d1fae5; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <div style="font-size:12px; color:#065f46; font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#059669" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Output Selesai (OK)
                </div>
                <div class="chip-value" id="rw-total-output" style="font-size:24px; font-weight:800; color:#059669;">-</div>
                <div style="font-size:11.5px; color:#64748b; margin-top:2px;">Finished Good lolos QC</div>
            </div>

            <div class="summary-chip" style="background:#ffffff; border-radius:12px; padding:12px 16px; border:1px solid #fee2e2; border-left:4px solid #ef4444; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <div style="font-size:12px; color:#991b1b; font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#dc2626" stroke-width="2.5"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    Part Cacat (NG / Reject)
                </div>
                <div class="chip-value" id="rw-total-ng" style="font-size:24px; font-weight:800; color:#dc2626;">-</div>
                <div id="rw-defect-rate" style="font-size:11.5px; color:#dc2626; font-weight:700; margin-top:2px;">Defect Rate: 0.0%</div>
            </div>

            <div class="summary-chip highlight" style="background:linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%); border-radius:12px; padding:12px 16px; border:1px solid #bae6fd; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <div style="font-size:12px; color:#0369a1; font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#0284c7" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    Yield Produksi (%)
                </div>
                <div class="chip-value" id="rw-yield" style="font-size:24px; font-weight:800; color:#0369a1;">-</div>
                <div style="font-size:11.5px; color:#059669; font-weight:700; margin-top:2px;">(Output OK ÷ Barang Masuk)</div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header-row" style="margin-bottom:12px;">
                <div>
                    <div class="panel-title">Histori Data Produksi & Kualitas</div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">Monitoring riwayat total barang masuk, output OK, part cacat (NG), persentase yield, serta penanggung jawab (PIC & Shift).</div>
                </div>
            </div>
            <table id="tabel-histori">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th>Tanggal</th>
                        <th>Penanggung Jawab (PIC & Shift)</th>
                        <th style="text-align:right;">Barang Masuk (IN)</th>
                        <th style="text-align:right;">Output Selesai (OK)</th>
                        <th style="text-align:right;">Part Cacat (NG)</th>
                        <th style="text-align:center;">% Yield</th>
                        <th style="text-align:center;">Detail & Rincian NG</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="isi-tabel-histori"></tbody>
            </table>
        </div>
    </div>

    <!-- ===== TAB: RIWAYAT TABEL HASIL PRODUKSI ===== -->
    <div id="riwayat-tab-mc" style="display:none;">
        <div class="riwayat-summary">
            <div class="summary-chip">
                <span class="chip-label">Jumlah Perhitungan</span>
                <span class="chip-value" id="ph-jumlah">-</span>
            </div>
            <div class="summary-chip highlight">
                <span class="chip-label">Rata-rata EFF</span>
                <span class="chip-value" id="ph-rata-eff">-</span>
            </div>
        </div>

        <div class="panel" id="panel-riwayat-performance">
            <div class="panel-header-row">
                <div class="panel-title">Riwayat Tabel Hasil Produksi (Jam Kerja) — <span id="ph-tgl-label" style="color:#0d3b8c;">Hari Ini</span></div>
                <div class="panel-controls">
                    <div class="date-picker-wrapper" style="display:inline-flex; align-items:center; gap:6px;">
                        <button type="button" class="riwayat-quick-btn" onclick="setTanggalPh('hari_ini')" style="padding:6px 12px; font-size:12px;">Hari Ini</button>
                        <button type="button" class="riwayat-quick-btn" onclick="setTanggalPh('kemarin')" style="padding:6px 12px; font-size:12px;">Kemarin</button>
                        <button type="button" class="filter-select" id="btn-tanggal-ph" onclick="bukaPickerPerformance()">
                            <svg viewBox="0 0 24 24" width="14" height="14" style="stroke:currentColor;fill:none;stroke-width:2;vertical-align:-2px;margin-right:6px;"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <span id="teks-tanggal-ph">Hari Ini</span>
                        </button>
                        <input type="date" id="filter-tanggal-ph" onchange="loadRiwayatPerformance()" style="position:absolute; opacity:0; pointer-events:none; width:1px; height:1px;">
                    </div>
                </div>
            </div>
            <div class="settings-row-desc" style="margin-bottom:14px;">
                Setiap sesi produksi part di Machine Control (dari mesin mulai <strong>Running</strong> sampai benar-benar berhenti) otomatis tercatat sebagai satu sesi di sini. Klik <strong>Lihat Detail</strong> untuk melihat rincian hasil kontrol produksinya, atau <strong>Download Excel</strong> untuk mengunduh sesi tersebut.
            </div>
            <div id="ph-kosong" style="display:none; color:#9a9ab0; text-align:center; padding:24px 0; font-size:13px;">
                Belum ada riwayat kalkulasi untuk tanggal ini.
            </div>
            <div id="daftar-riwayat-ph" class="riwayat-ph-list"></div>
        </div>
    </div>

    <!-- ===== TAB: LOG VERIFIKASI HANGER AI VISION (AUDIT TRAIL) ===== -->
    <div id="riwayat-tab-hanger" style="display:none;">
        <div class="panel">
            <div class="panel-header-row" style="margin-bottom:12px;">
                <div>
                    <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#0d3b8c" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                        <span>Log Verifikasi Hanger AI Vision (Audit Trail Real-Time)</span>
                    </div>
                    <div style="font-size:12.5px; color:#64748b; margin-top:2px;">
                        Setiap kali Photosensor 2 mendeteksi hanger lewat, AI Vision menghitung jumlah part dan mencatat operator shift yang bertugas.
                    </div>
                </div>
                <button type="button" onclick="loadHangerDetectionLogs()" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; background:#f1f5f9; color:#0d3b8c; border:1px solid #cbd5e1; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer;">
                    🔄 Refresh Log
                </button>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0; color:#475569;">
                            <th style="padding:10px 12px;">Waktu</th>
                            <th style="padding:10px 12px;">No. Hanger</th>
                            <th style="padding:10px 12px;">Kode Part</th>
                            <th style="padding:10px 12px; text-align:center;">Hasil AI (Pcs)</th>
                            <th style="padding:10px 12px; text-align:center;">Target Hanger</th>
                            <th style="padding:10px 12px; text-align:center;">Status</th>
                            <th style="padding:10px 12px;">Operator PIC</th>
                            <th style="padding:10px 12px;">Shift</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-hanger-detection-logs">
                        <tr>
                            <td colspan="8" style="text-align:center; padding:24px; color:#94a3b8; font-style:italic;">
                                Memuat log verifikasi hanger AI...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== TAB: DETAIL PART CACAT (NG DEFECT QUALITY TRACKING) ===== -->
    <div id="riwayat-tab-ng" style="display:none;">
        <!-- Header Panel & Filter Tanggal -->
        <div class="riwayat-search-panel" style="margin-bottom:16px;">
            <div class="panel-header-row" style="margin-bottom:12px;">
                <div>
                    <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#dc2626" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                        <span style="color:#b91c1c;">Analisis & Detail Part Cacat (NG Defect Log)</span>
                    </div>
                    <div style="font-size:12.5px; color:#64748b; margin-top:2px;">
                        Tracking riwayat kecacatan painting (Cat Bintik, Gores, Belang, Kulit Jeruk, Sagging, dll.), jumlah pcs, penanggung jawab shift, dan detail reject.
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" onclick="bukaModalCatatNg()" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#dc2626; color:#ffffff; border:none; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer; box-shadow:0 1px 2px rgba(220,38,38,0.2);">
                        <span>+</span> Catat Part Cacat (Manual)
                    </button>
                    <button type="button" onclick="loadRiwayatNg()" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#f1f5f9; color:#0d3b8c; border:1px solid #cbd5e1; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer;">
                        🔄 Refresh
                    </button>
                </div>
            </div>

            <!-- Filter Tanggal NG -->
            <div class="riwayat-search-bar" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <div class="riwayat-date-input-box">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="#dc2626" fill="none" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <input type="date" id="filter-tanggal-ng" class="riwayat-date-input" title="Filter berdasarkan tanggal cacat" onchange="loadRiwayatNg(this.value)">
                </div>
                <div class="btn-group-quick-date" style="display:inline-flex; gap:6px; flex-wrap:wrap;">
                    <button type="button" class="riwayat-quick-btn" onclick="setTanggalNgCari('hari_ini')">Hari Ini</button>
                    <button type="button" class="riwayat-quick-btn" onclick="setTanggalNgCari('kemarin')">Kemarin</button>
                    <button type="button" class="riwayat-quick-btn reset" onclick="resetTanggalNgCari()">Semua Data</button>
                </div>
                <div style="font-size:12.5px; color:#64748b; margin-left:auto;">
                    Menampilkan Log: <strong id="rw-ng-label-tanggal-aktif" style="color:#b91c1c;">Semua Tanggal</strong>
                </div>
            </div>
        </div>

        <!-- Metric KPI Cards Khusus Defect -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:16px;">
            <div style="background:#fff; border:1px solid #fee2e2; border-left:4px solid #ef4444; border-radius:10px; padding:12px 16px; box-shadow:0 1px 2px rgba(0,0,0,0.03);">
                <div style="font-size:11.5px; font-weight:700; color:#991b1b; text-transform:uppercase;">Total Defect (NG)</div>
                <div id="rw-ng-kpi-total-pcs" style="font-size:24px; font-weight:800; color:#dc2626; margin-top:2px;">0 pcs</div>
                <div style="font-size:11px; color:#64748b;">Jumlah seluruh part yang cacat</div>
            </div>
            <div style="background:#fff; border:1px solid #fed7aa; border-left:4px solid #f97316; border-radius:10px; padding:12px 16px; box-shadow:0 1px 2px rgba(0,0,0,0.03);">
                <div style="font-size:11.5px; font-weight:700; color:#9a3412; text-transform:uppercase;">Defect Ratio</div>
                <div id="rw-ng-kpi-defect-rate" style="font-size:24px; font-weight:800; color:#ea580c; margin-top:2px;">0.0%</div>
                <div style="font-size:11px; color:#64748b;">Persentase cacat terhadap output</div>
            </div>
            <div style="background:#fff; border:1px solid #e2e8f0; border-left:4px solid #6366f1; border-radius:10px; padding:12px 16px; box-shadow:0 1px 2px rgba(0,0,0,0.03);">
                <div style="font-size:11.5px; font-weight:700; color:#3730a3; text-transform:uppercase;">Defect Terbanyak (Pareto Top)</div>
                <div id="rw-ng-kpi-top-defect" style="font-size:18px; font-weight:800; color:#4f46e5; margin-top:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">-</div>
                <div style="font-size:11px; color:#64748b;">Paling sering dilaporkan</div>
            </div>
        </div>

        <!-- Pareto Breakdown Chips -->
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; margin-bottom:16px;">
            <div style="font-size:12.5px; font-weight:700; color:#334155; margin-bottom:10px; display:flex; align-items:center; justify-content:space-between;">
                <span>📊 Distribusi Jenis Cacat (Pareto Defect Breakdown)</span>
                <span id="rw-ng-count-types" style="font-size:11.5px; color:#64748b;">0 jenis terdeteksi</span>
            </div>
            <div id="rw-ng-pareto-chips" style="display:flex; flex-wrap:wrap; gap:8px;">
                <span style="font-size:12px; color:#94a3b8; font-style:italic;">Belum ada catatan cacat pada periode ini.</span>
            </div>
        </div>

        <!-- Tabel Log Rincian Part Cacat -->
        <div class="panel">
            <div class="panel-header-row" style="margin-bottom:12px;">
                <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Log Detail Part Cacat (NG)</span>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0; color:#475569;">
                            <th style="padding:10px 12px; width:140px;">Waktu & Tanggal</th>
                            <th style="padding:10px 12px;">Part</th>
                            <th style="padding:10px 12px;">Jenis Cacat (Defect)</th>
                            <th style="padding:10px 12px; text-align:center;">Jumlah (Pcs)</th>
                            <th style="padding:10px 12px;">Penanggung Jawab (PIC & Shift)</th>
                            <th style="padding:10px 12px;">Catatan / Lokasi</th>
                            <th style="padding:10px 12px; text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-riwayat-ng-logs">
                        <tr>
                            <td colspan="7" style="text-align:center; padding:24px; color:#94a3b8; font-style:italic;">
                                Memuat log part cacat...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
