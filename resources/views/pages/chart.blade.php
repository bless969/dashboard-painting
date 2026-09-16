<div id="view-chart" style="{{ $view_aktif !== 'chart' ? 'display:none;' : '' }}">
    <div class="chart-row">
        <div class="panel">
            <div class="panel-header-row">
                <div class="panel-title" id="judul-chart-produksi">Produksi</div>
                <div class="panel-subtitle" id="label-part-produksi" style="display:none; font-size:12px; color:#6b6b80; margin-top:2px;"></div>
                <div class="panel-controls">
                    <select id="periode-mode-select" class="filter-select" onchange="ubahModePeriode()" title="Pilih mode periode">
                        <option value="perjam">Per Jam</option>
                        <option value="perhari">Per Hari</option>
                        <option value="perminggu">Per Minggu</option>
                        <option value="perbulan">Per Bulan</option>
                        <option value="pertahun">Per Tahun</option>
                        <option value="5tahun">5 Tahun</option>
                    </select>

                    <!-- Per Jam: date picker tanggal -->
                    <div class="date-picker-wrapper" id="wrapper-tanggal-perjam">
                        <button type="button" class="filter-select" id="btn-tanggal" onclick="bukaPickerTanggal()">
                            <svg viewBox="0 0 24 24" width="14" height="14" style="stroke:currentColor;fill:none;stroke-width:2;vertical-align:-2px;margin-right:6px;"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <span id="teks-tanggal">Pilih tanggal</span>
                        </button>
                        <input type="date" id="filter-tanggal" onchange="updateChart()" style="position:absolute; opacity:0; pointer-events:none; width:1px; height:1px;" min="" max="">
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
                    <div id="wrapper-tahun" style="display:none;">
                        <select id="filter-tahun" class="filter-select" onchange="updateChart()" title="Pilih tahun"></select>
                        <select id="filter-bulan-highlight" class="filter-select" onchange="ubahBulanTerpilih()" title="Lihat detail bulan tertentu">
                            <option value="">Semua bulan (per bulan)</option>
                        </select>
                    </div>
                </div>
            </div>
            <canvas id="chartProduksi" height="80"></canvas>
        </div>
    </div>

    <!-- ===== CHART: LOSS TIME (per sesi) ===== -->
    <div class="panel">
        <div class="panel-header-row">
            <div class="panel-title">Loss Time</div>
        </div>
        <div class="settings-row-desc" style="margin-bottom:12px;" id="deskripsi-chart-loss-time">Total durasi stop kategori Loss (menit) tiap sesi produksi pada tanggal terpilih.</div>
        <div id="empty-chartLossTime" style="display:none; text-align:center; padding:28px 16px; color:#9a9ab0; font-size:13px; background:#f8fafc; border-radius:8px; border:1px dashed #e2e8f0; margin-bottom:12px;">
            Belum ada data sesi produksi yang tercatat pada periode ini.
        </div>
        <canvas id="chartLossTime" height="80"></canvas>
    </div>

    <!-- ===== CHART: PRODUCT TIME (per sesi) ===== -->
    <div class="panel">
        <div class="panel-header-row">
            <div class="panel-title">Product Time</div>
        </div>
        <div class="settings-row-desc" style="margin-bottom:12px;" id="deskripsi-chart-product-time">(D) Actual Cycle Time -- waktu rata-rata menghasilkan 1 pcs (menit/pcs) tiap sesi produksi.</div>
        <div id="empty-chartProductTime" style="display:none; text-align:center; padding:28px 16px; color:#9a9ab0; font-size:13px; background:#f8fafc; border-radius:8px; border:1px dashed #e2e8f0; margin-bottom:12px;">
            Belum ada data sesi produksi yang tercatat pada periode ini.
        </div>
        <canvas id="chartProductTime" height="80"></canvas>
    </div>
</div>
