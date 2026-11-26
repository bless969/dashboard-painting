<div id="view-chart" style="{{ $view_aktif !== 'chart' ? 'display:none;' : '' }}">

    <!-- Summary KPI Header untuk Periode Chart yang Dipilih -->
    <div class="kpi-summary-grid">
        <div class="summary-chip">
            <span class="chip-label">Barang Masuk (IN)</span>
            <span class="chip-value" id="chart-kpi-input" style="color:#1e3a8a;">-</span>
        </div>

        <div class="summary-chip chip-green">
            <span class="chip-label">Output Selesai (OK)</span>
            <span class="chip-value" id="chart-kpi-output" style="color:#059669;">-</span>
        </div>

        <div class="summary-chip chip-red">
            <span class="chip-label" style="color:#991b1b;">Part Cacat (NG)</span>
            <span class="chip-value" id="chart-kpi-ng" style="color:#dc2626;">-</span>
        </div>

        <div class="summary-chip chip-amber">
            <span class="chip-label" style="color:#92400e;">Yield Produksi</span>
            <span class="chip-value" id="chart-kpi-yield" style="color:#b45309;">-%</span>
        </div>

        <div class="summary-chip highlight">
            <span class="chip-label">Rata-rata EFF</span>
            <span class="chip-value" id="chart-kpi-eff">-%</span>
        </div>
    </div>

    <!-- ===== GRAFIK UTAMA: PRODUKSI ===== -->
    <div class="chart-row">
        <div class="panel">
            <div class="panel-header-row" style="flex-wrap:wrap; gap:12px;">
                <div>
                    <div class="panel-title" id="judul-chart-produksi">Produksi</div>
                    <div class="panel-subtitle" id="label-part-produksi" style="display:none; font-size:13px; color:#64748b; margin-top:2px;"></div>
                </div>

                <div class="panel-controls">
                    <select id="periode-mode-select" class="filter-select" onchange="ubahModePeriode()" title="Pilih mode periode">
                        <option value="perjam">Per Jam</option>
                        <option value="perhari">Per Hari (Bulan Ini)</option>
                        <option value="perminggu">Per Minggu</option>
                        <option value="perbulan">Per Bulan</option>
                        <option value="pertahun">Per Tahun</option>
                        <option value="5tahun">5 Tahun Terakhir</option>
                    </select>

                    <select id="chart-type-select" class="filter-select" onchange="simpanBentukGrafik(this.value)" title="Pilih tipe grafik">
                        <option value="bar">Bar</option>
                        <option value="line">Line</option>
                        <option value="area">Area</option>
                        <option value="scatter">Scatter</option>
                        <option value="pie">Pie</option>
                    </select>

                    <button type="button" id="btn-toggle-yield-overlay" class="filter-select" onclick="toggleOverlayYield()" style="display:inline-flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #cbd5e1; color:#475569; font-weight:700;" title="Tampilkan atau sembunyikan garis % Yield">
                        <span id="icon-toggle-yield" style="display:none;"></span><span id="text-toggle-yield">+ Garis % Yield</span>
                    </button>

                    <div class="date-picker-wrapper" id="wrapper-tanggal-perjam">
                        <button type="button" class="filter-select" id="btn-tanggal" onclick="bukaPickerTanggal()" style="display:inline-flex; align-items:center; gap:6px;">
                            <svg viewBox="0 0 24 24" width="15" height="15" style="stroke:currentColor;fill:none;stroke-width:2;"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <span id="teks-tanggal">Pilih tanggal</span>
                        </button>
                        <input type="date" id="filter-tanggal" onchange="updateChart()" style="position:absolute; opacity:0; pointer-events:none; width:1px; height:1px;">
                    </div>

                    <div id="wrapper-minggu" style="display:none;">
                        <input type="week" id="filter-minggu" class="filter-select" onchange="updateChart()" title="Pilih minggu">
                    </div>

                    <div id="wrapper-bulan" style="display:none;">
                        <input type="month" id="filter-bulan-input" class="filter-select" onchange="updateChart()" title="Pilih bulan">
                    </div>

                    <div id="wrapper-tahun" style="display:none; gap:6px;">
                        <select id="filter-tahun" class="filter-select" onchange="updateChart()" title="Pilih tahun"></select>
                        <select id="filter-bulan-highlight" class="filter-select" onchange="ubahBulanTerpilih()" title="Lihat detail bulan tertentu">
                            <option value="">Semua bulan</option>
                        </select>
                    </div>

                    <button type="button" onclick="updateChartAktif()" class="filter-select" style="background:#f1f5f9; border:1px solid #cbd5e1; font-weight:700;" title="Segarkan Data Grafik">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" style="margin-right:6px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        Refresh
                    </button>
                </div>
            </div>

            <div style="position:relative; width:100%; min-height:300px; height:340px; margin-top:14px;">
                <canvas id="chartProduksi"></canvas>
            </div>
        </div>
    </div>

    <!-- ===== GRID: KOMPOSISI DEFECT & EFISIENSI LINI ===== -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(380px, 1fr)); gap:16px; margin-top:20px;">
        <!-- CHART: KOMPOSISI PART CACAT -->
        <div class="panel" style="margin-bottom:0;">
            <div class="panel-header-row">
                <div class="panel-title">Komposisi Part Cacat (NG)</div>
                <span id="badge-total-defect-chart" style="font-size:13px; background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; padding:4px 10px; border-radius:6px; font-weight:700;">0 Pcs NG</span>
            </div>
            <div id="deskripsi-chart-defect" style="display:none;"></div>
            <div id="empty-chartDefect" style="display:none; text-align:center; padding:36px 16px; color:#059669; font-size:14px; font-weight:600; background:#f0fdf4; border-radius:8px; border:1px dashed #bbf7d0; margin-bottom:12px;">
                Tidak ada part cacat (NG) pada periode ini.
            </div>
            <div id="wrapper-canvas-defect" style="position:relative; width:100%; height:250px;">
                <canvas id="chartDefect"></canvas>
            </div>
        </div>

        <!-- CHART: PERFORMA KUALITAS & EFISIENSI -->
        <div class="panel" style="margin-bottom:0;">
            <div class="panel-header-row" style="flex-wrap:wrap; gap:8px;">
                <div class="panel-title">Kualitas (% Yield) &amp; Efisiensi (% EFF)</div>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span id="badge-avg-yield-chart" style="font-size:13px; background:#fefce8; color:#854d0e; border:1px solid #fde047; padding:4px 10px; border-radius:6px; font-weight:700;">Yield: -%</span>
                    <span id="badge-avg-eff-chart" style="font-size:13px; background:#f5f3ff; color:#6d28d9; border:1px solid #ede9fe; padding:4px 10px; border-radius:6px; font-weight:700;">EFF: -%</span>
                    <div style="display:inline-flex; background:#f1f5f9; padding:3px; border-radius:8px; border:1px solid #cbd5e1; gap:2px;">
                        <button type="button" class="btn-sub-tab" id="tab-perf-all" onclick="setSubTabPerforma('all')" style="padding:4px 10px; font-size:12.5px; font-weight:700; border-radius:6px; border:none; cursor:pointer; background:#ffffff; color:#1e293b;">Semua</button>
                        <button type="button" class="btn-sub-tab" id="tab-perf-yield" onclick="setSubTabPerforma('yield')" style="padding:4px 10px; font-size:12.5px; font-weight:700; border-radius:6px; border:none; cursor:pointer; background:transparent; color:#854d0e;">% Yield</button>
                        <button type="button" class="btn-sub-tab" id="tab-perf-eff" onclick="setSubTabPerforma('eff')" style="padding:4px 10px; font-size:12.5px; font-weight:700; border-radius:6px; border:none; cursor:pointer; background:transparent; color:#6d28d9;">% EFF</button>
                    </div>
                </div>
            </div>
            <div id="deskripsi-chart-efficiency" style="display:none;"></div>
            <div id="empty-chartProductTime" style="display:none; text-align:center; padding:36px 16px; color:#64748b; font-size:14px; background:#f8fafc; border-radius:8px; border:1px dashed #e2e8f0; margin-bottom:12px;">
                Belum ada data sesi produksi pada periode ini.
            </div>
            <div id="wrapper-canvas-product-time" style="position:relative; width:100%; height:250px;">
                <canvas id="chartProductTime"></canvas>
            </div>
        </div>
    </div>
</div>
