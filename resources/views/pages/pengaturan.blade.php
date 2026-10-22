<div id="view-pengaturan" style="{{ $view_aktif !== 'pengaturan' ? 'display:none;' : '' }}">
    <div class="panel">
        <div class="panel-title">Tampilan Grafik</div>

        <div class="settings-row">
            <div class="settings-row-text">
                <div class="settings-row-label">Bentuk Grafik Produksi &amp; EFF</div>
                <div class="settings-row-desc">Mengatur bentuk grafik Input/Output dan EFF di halaman Dashboard Painting.</div>
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
                <div class="settings-row-label">Kapasitas Target Bulan Ini</div>
                <div class="settings-row-desc">Menentukan target total output PCS lini painting untuk bulan berjalan. Nilai ini akan menghitung persentase pencapaian target di dashboard utama.</div>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                <input type="number" id="pengaturan-target-pcs" class="filter-select" style="width:160px; font-weight:700; color:#0d3b8c;" placeholder="50000" min="1" step="100">
                <button type="button" class="btn-simpan-produksi" onclick="simpanTargetDariPengaturan()" style="padding:8px 16px; background:#0d3b8c; color:white; border:none; border-radius:6px; font-weight:600; cursor:pointer;">
                    Simpan Target
                </button>
            </div>
        </div>
    </div>
</div>
