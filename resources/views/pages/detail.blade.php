<div id="view-detail" style="{{ $view_aktif !== 'detail' ? 'display:none;' : '' }}">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
        <div style="font-size:15px; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px;">
            <span>⏱️</span>
            <span>Timeline Input & Output per Jam — <strong id="dt-tgl-label" style="color:#0d3b8c;">{{ isset($tanggal_aktif) ? \Carbon\Carbon::parse($tanggal_aktif)->translatedFormat('d F Y') : '-' }}</strong></span>
        </div>
    </div>

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
            <span class="chip-label">Yield Keseluruhan</span>
            <span class="chip-value" id="dt-yield">-</span>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
        <div class="panel">
            <div class="panel-title"><span class="badge-tipe tipe-input">Input (Painting Masuk)</span></div>
            <div id="dt-isi-input" style="max-height:480px; overflow-y:auto;">Memuat...</div>
        </div>
        <div class="panel">
            <div class="panel-title"><span class="badge-tipe tipe-output">Output (Painting Keluar)</span></div>
            <div id="dt-isi-output" style="max-height:480px; overflow-y:auto;">Memuat...</div>
        </div>
    </div>
</div>
