<div id="view-pengaturan" style="{{ $view_aktif !== 'pengaturan' ? 'display:none;' : '' }}">
    <div class="panel">
        <div class="panel-title">Tampilan Grafik</div>

        <div class="settings-row">
            <div class="settings-row-text">
                <div class="settings-row-label">Bentuk Grafik Produksi &amp; EFF</div>
                <div class="settings-row-desc">Mengatur bentuk grafik di halaman Chart.</div>
            </div>
            <select id="chart-type-select" class="filter-select" onchange="simpanBentukGrafik(this.value)" title="Pilih jenis grafik">
                <option value="bar">Bar Chart</option>
                <option value="line">Line Chart</option>
                <option value="area">Area Chart</option>
                <option value="scatter">Scatter Plot</option>
            </select>
        </div>
    </div>

    <div class="panel" style="margin-top:20px;">
        <div class="panel-title">Target Produksi Bulanan (PCS)</div>
        <div class="settings-row">
            <div class="settings-row-text">
                <div class="settings-row-label">Target Bulan Ini</div>
                <div class="settings-row-desc">Target total output PCS untuk bulan berjalan.</div>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                <input type="number" id="pengaturan-target-pcs" class="filter-select" style="width:170px; font-weight:700; color:#0d3b8c; background:#ffffff;" placeholder="50000" min="1" step="100">
                <button type="button" class="btn-simpan-produksi btn-nav-action btn-blue" onclick="simpanTargetDariPengaturan()" style="padding:9px 18px; border:none; font-size:14px;">
                    Simpan Target
                </button>
            </div>
        </div>
    </div>
</div>
