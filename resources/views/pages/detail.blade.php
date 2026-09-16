<div id="view-detail" style="{{ $view_aktif !== 'detail' ? 'display:none;' : '' }}">
    <div class="riwayat-summary">
        <div class="summary-chip">
            <span class="chip-label">Total Input</span>
            <span class="chip-value" id="dt-total-input">-</span>
        </div>
        <div class="summary-chip">
            <span class="chip-label">Total Output</span>
            <span class="chip-value" id="dt-total-output">-</span>
        </div>
        <div class="summary-chip highlight">
            <span class="chip-label">Yield</span>
            <span class="chip-value" id="dt-yield">-</span>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
        <div class="panel">
            <div class="panel-title"><span class="badge-tipe tipe-input">Input (PCB Masuk)</span></div>
            <div id="dt-isi-input">Memuat...</div>
        </div>
        <div class="panel">
            <div class="panel-title"><span class="badge-tipe tipe-output">Output (PCB Keluar)</span></div>
            <div id="dt-isi-output">Memuat...</div>
        </div>
    </div>
</div>
