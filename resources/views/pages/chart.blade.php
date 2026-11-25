<div id="view-chart" style="{{ $view_aktif !== 'chart' ? 'display:none;' : '' }}">

    <!-- Summary KPI Header untuk Periode Chart yang Dipilih -->
    <div class="riwayat-summary" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:16px;">
        <!-- Total Barang Masuk (IN) -->
        <div class="summary-chip" style="background:#ffffff; border-radius:12px; padding:12px 16px; border:1px solid #dbeafe; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="font-size:12px; color:#1e40af; font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#2563eb" stroke-width="2.5"><polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>
                Total Barang Masuk (IN)
            </div>
            <div class="chip-value" id="chart-kpi-input" style="font-size:22px; font-weight:800; color:#1e3a8a;">-</div>
            <div style="font-size:11px; color:#64748b; margin-top:2px;">Akumulasi Input Periode Ini</div>
        </div>

        <!-- Output Selesai (OK) -->
        <div class="summary-chip" style="background:#ffffff; border-radius:12px; padding:12px 16px; border:1px solid #d1fae5; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="font-size:12px; color:#065f46; font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#059669" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                Output Selesai (OK)
            </div>
            <div class="chip-value" id="chart-kpi-output" style="font-size:22px; font-weight:800; color:#059669;">-</div>
            <div style="font-size:11px; color:#64748b; margin-top:2px;">Finished Good lolos QC</div>
        </div>

        <!-- Part Cacat (NG) -->
        <div class="summary-chip" style="background:#ffffff; border-radius:12px; padding:12px 16px; border:1px solid #fee2e2; border-left:4px solid #ef4444; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="font-size:12px; color:#991b1b; font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#dc2626" stroke-width="2.5"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                Part Cacat (NG / Reject)
            </div>
            <div class="chip-value" id="chart-kpi-ng" style="font-size:22px; font-weight:800; color:#dc2626;">-</div>
            <div style="font-size:11px; color:#dc2626; font-weight:600; margin-top:2px;">Reject terdeteksi</div>
        </div>

        <!-- % Yield Produksi -->
        <div class="summary-chip highlight" style="background:linear-gradient(135deg, #fefce8 0%, #fef08a 100%); border-radius:12px; padding:12px 16px; border:1px solid #fde047; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="font-size:12px; color:#854d0e; font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#ca8a04" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                % Yield Produksi
            </div>
            <div class="chip-value" id="chart-kpi-yield" style="font-size:22px; font-weight:800; color:#854d0e;">-%</div>
            <div style="font-size:11px; color:#a16207; font-weight:600; margin-top:2px;">Rasio Output vs Input</div>
        </div>

        <!-- Rata-rata Efisiensi -->
        <div class="summary-chip" style="background:#ffffff; border-radius:12px; padding:12px 16px; border:1px solid #ede9fe; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="font-size:12px; color:#5b21b6; font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#7c3aed" stroke-width="2.5"><path d="M18 20V10"></path><path d="M12 20V4"></path><path d="M6 20v-6"></path></svg>
                Rata-rata EFF Line
            </div>
            <div class="chip-value" id="chart-kpi-eff" style="font-size:22px; font-weight:800; color:#6d28d9;">-%</div>
            <div style="font-size:11px; color:#64748b; margin-top:2px;">Overall Line Efficiency</div>
        </div>
    </div>

    <!-- ===== GRAFIK UTAMA: PRODUKSI ===== -->
    <div class="chart-row">
        <div class="panel">
            <div class="panel-header-row" style="flex-wrap:wrap; gap:12px;">
                <div>
                    <div class="panel-title" id="judul-chart-produksi" style="font-size:16px; font-weight:800; color:#1e293b; display:flex; align-items:center; gap:8px;">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#2563eb" stroke-width="2.2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                        <span>Produksi</span>
                    </div>
                    <div class="panel-subtitle" id="label-part-produksi" style="display:none; font-size:12px; color:#6b6b80; margin-top:2px;"></div>
                </div>

                <div class="panel-controls" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <!-- Dropdown Periode -->
                    <div style="display:inline-flex; align-items:center; gap:6px;">
                        <span style="font-size:12px; color:#64748b; font-weight:600;">Periode:</span>
                        <select id="periode-mode-select" class="filter-select" onchange="ubahModePeriode()" title="Pilih mode periode">
                            <option value="perjam">Per Jam</option>
                            <option value="perhari">Per Hari (Bulan Ini)</option>
                            <option value="perminggu">Per Minggu</option>
                            <option value="perbulan">Per Bulan</option>
                            <option value="pertahun">Per Tahun</option>
                            <option value="5tahun">5 Tahun Terakhir</option>
                        </select>
                    </div>

                    <!-- Dropdown Tipe Grafik -->
                    <div style="display:inline-flex; align-items:center; gap:6px;">
                        <span style="font-size:12px; color:#64748b; font-weight:600;">Bentuk:</span>
                        <select id="chart-type-select" class="filter-select" onchange="simpanBentukGrafik(this.value)" title="Pilih tipe grafik">
                            <option value="bar">📊 Bar</option>
                            <option value="line">📈 Line</option>
                            <option value="area">📉 Area</option>
                            <option value="scatter">⚪ Scatter</option>
                            <option value="pie">🥧 Pie</option>
                        </select>
                    </div>

                    <!-- Tombol Toggle Garis % Yield pada Grafik Produksi -->
                    <button type="button" id="btn-toggle-yield-overlay" class="filter-select" onclick="toggleOverlayYield()" style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; background:#f8fafc; border:1px solid #cbd5e1; color:#64748b; font-weight:700;" title="Tampilkan atau sembunyikan garis overlay % Yield pada grafik produksi">
                        <span id="icon-toggle-yield">📊</span> <span id="text-toggle-yield">+ Garis % Yield</span>
                    </button>

                    <!-- Per Jam: date picker tanggal -->
                    <div class="date-picker-wrapper" id="wrapper-tanggal-perjam">
                        <button type="button" class="filter-select" id="btn-tanggal" onclick="bukaPickerTanggal()" style="display:inline-flex; align-items:center; gap:6px;">
                            <svg viewBox="0 0 24 24" width="14" height="14" style="stroke:currentColor;fill:none;stroke-width:2;vertical-align:-2px;"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <span id="teks-tanggal">Pilih tanggal</span>
                        </button>
                        <input type="date" id="filter-tanggal" onchange="updateChart()" style="position:absolute; opacity:0; pointer-events:none; width:1px; height:1px;">
                    </div>

                    <!-- Per Minggu: week picker -->
                    <div id="wrapper-minggu" style="display:none;">
                        <input type="week" id="filter-minggu" class="filter-select" onchange="updateChart()" title="Pilih minggu">
                    </div>

                    <!-- Per Bulan: month picker -->
                    <div id="wrapper-bulan" style="display:none;">
                        <input type="month" id="filter-bulan-input" class="filter-select" onchange="updateChart()" title="Pilih bulan">
                    </div>

                    <!-- Per Tahun: dropdown tahun + drill-down bulan -->
                    <div id="wrapper-tahun" style="display:none; gap:6px;">
                        <select id="filter-tahun" class="filter-select" onchange="updateChart()" title="Pilih tahun"></select>
                        <select id="filter-bulan-highlight" class="filter-select" onchange="ubahBulanTerpilih()" title="Lihat detail bulan tertentu">
                            <option value="">Semua bulan (per bulan)</option>
                        </select>
                    </div>

                    <!-- Tombol Refresh Chart -->
                    <button type="button" onclick="updateChartAktif()" class="filter-select" style="background:#f1f5f9; border:1px solid #cbd5e1; cursor:pointer;" title="Segarkan Data Grafik">
                        🔄 Refresh
                    </button>
                </div>
            </div>

            <!-- Kontainer Canvas Grafik Produksi -->
            <div style="position:relative; width:100%; min-height:300px; height:340px; margin-top:14px;">
                <canvas id="chartProduksi"></canvas>
            </div>
        </div>
    </div>

    <!-- ===== GRID: KOMPOSISI DEFECT & EFISIENSI LINI (2 Kolom Sejajar & Responsif) ===== -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(380px, 1fr)); gap:16px; margin-top:16px;">
        <!-- ===== CHART: KOMPOSISI PART CACAT (DEFECT BREAKDOWN) ===== -->
        <div class="panel" style="margin-bottom:0;">
            <div class="panel-header-row">
                <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#ef4444" stroke-width="2.2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <span>Komposisi Part Cacat (Defect Breakdown)</span>
                </div>
                <span id="badge-total-defect-chart" style="font-size:11.5px; background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; padding:3px 8px; border-radius:6px; font-weight:700;">0 Pcs NG</span>
            </div>
            <div class="settings-row-desc" style="margin-bottom:12px;" id="deskripsi-chart-defect">Proporsi dan sebaran jenis cacat pengecatan (Cat Bintik, Scratch, Cat Meler, dll.) pada periode terpilih.</div>
            <div id="empty-chartDefect" style="display:none; text-align:center; padding:36px 16px; color:#10b981; font-size:13px; background:#f0fdf4; border-radius:8px; border:1px dashed #bbf7d0; margin-bottom:12px;">
                <span style="font-size:18px; display:block; margin-bottom:4px;">✨</span>
                <strong>Kualitas 100% OK!</strong> Tidak ada catatan part cacat (NG) pada periode ini.
            </div>
            <div id="wrapper-canvas-defect" style="position:relative; width:100%; height:240px;">
                <canvas id="chartDefect"></canvas>
            </div>
        </div>

        <!-- ===== CHART: TREN PERFORMA KUALITAS (% YIELD) & EFISIENSI LINI (% EFF) ===== -->
        <div class="panel" style="margin-bottom:0;">
            <div class="panel-header-row" style="flex-wrap:wrap; gap:8px;">
                <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#ca8a04" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                    <span>Performa Kualitas (% Yield) & Efisiensi (% EFF)</span>
                </div>
                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                    <span id="badge-avg-yield-chart" style="font-size:11.5px; background:#fefce8; color:#854d0e; border:1px solid #fde047; padding:3px 8px; border-radius:6px; font-weight:700;">Yield: -%</span>
                    <span id="badge-avg-eff-chart" style="font-size:11.5px; background:#f5f3ff; color:#6d28d9; border:1px solid #ede9fe; padding:3px 8px; border-radius:6px; font-weight:700;">EFF: -%</span>
                </div>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                <div class="settings-row-desc" style="margin-bottom:0;" id="deskripsi-chart-efficiency">Tren rasio mutu part OK (% Yield) dan produktivitas (% EFF) per sesi/hari.</div>
                <div style="display:inline-flex; background:#f1f5f9; padding:2px; border-radius:8px; border:1px solid #cbd5e1; gap:2px;">
                    <button type="button" class="btn-sub-tab" id="tab-perf-all" onclick="setSubTabPerforma('all')" style="padding:2px 8px; font-size:11px; font-weight:700; border-radius:6px; border:none; cursor:pointer; background:#ffffff; color:#1e293b; box-shadow:0 1px 2px rgba(0,0,0,0.06);">Semua</button>
                    <button type="button" class="btn-sub-tab" id="tab-perf-yield" onclick="setSubTabPerforma('yield')" style="padding:2px 8px; font-size:11px; font-weight:700; border-radius:6px; border:none; cursor:pointer; background:transparent; color:#854d0e;">% Yield</button>
                    <button type="button" class="btn-sub-tab" id="tab-perf-eff" onclick="setSubTabPerforma('eff')" style="padding:2px 8px; font-size:11px; font-weight:700; border-radius:6px; border:none; cursor:pointer; background:transparent; color:#6d28d9;">% EFF</button>
                </div>
            </div>
            <div id="empty-chartProductTime" style="display:none; text-align:center; padding:36px 16px; color:#9a9ab0; font-size:13px; background:#f8fafc; border-radius:8px; border:1px dashed #e2e8f0; margin-bottom:12px;">
                Belum ada data sesi produksi yang tercatat pada periode ini.
            </div>
            <div id="wrapper-canvas-product-time" style="position:relative; width:100%; height:240px;">
                <canvas id="chartProductTime"></canvas>
            </div>
        </div>
    </div>
</div>
