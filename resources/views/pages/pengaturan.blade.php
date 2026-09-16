<div id="view-pengaturan" style="{{ $view_aktif !== 'pengaturan' ? 'display:none;' : '' }}">
    <div class="panel">
        <div class="panel-title">Tampilan Grafik</div>

        <div class="settings-row">
            <div class="settings-row-text">
                <div class="settings-row-label">Bentuk Grafik Produksi &amp; EFF</div>
                <div class="settings-row-desc">Mengatur bentuk grafik Input/Output dan EFF di halaman Dashboard PCB.</div>
            </div>
            <select id="chart-type-select" class="filter-select" onchange="simpanBentukGrafik(this.value)" title="Pilih jenis grafik">
                <option value="bar">Bar Chart</option>
                <option value="line">Line Chart</option>
                <option value="area">Area Chart</option>
                <option value="scatter">Scatter Plot</option>
            </select>
        </div>
    </div>
</div>
