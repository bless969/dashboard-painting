<div id="view-detail-shift" style="{{ $view_aktif !== 'detail-shift' ? 'display:none;' : '' }}">
    <div class="ds-nav-shift">
        <a href="#" id="ds-link-prev" onclick="return false;">&#8592; Shift</a>
        <span class="ds-nav-divider">|</span>
        <a href="#" id="ds-link-next" onclick="return false;">Shift &#8594;</a>
    </div>

    <div class="ds-filter-bar">
        <div class="ds-filter-field">
            <label>Tanggal</label>
            <input type="date" id="ds-filter-tanggal">
        </div>
        <div class="ds-filter-field">
            <label>Shift</label>
            <select id="ds-filter-shift">
                <option value="Pagi">Pagi (06:55–15:50)</option>
                <option value="Sore">Sore (15:50–23:20)</option>
                <option value="Malam">Malam (23:20–06:55)</option>
            </select>
        </div>
        <button class="ds-btn-tampilkan" id="ds-btn-tampilkan" onclick="muatUlangDetailShift()">Tampilkan</button>
        <span class="ds-filter-loading" id="ds-filter-loading" style="display:none;">Memuat...</span>
    </div>

    <div class="ds-summary-grid" id="ds-summary-grid">
        <div class="ds-summary-card">
            <div class="ds-label">Input</div>
            <div class="ds-val" id="ds-val-input">-</div>
            <div class="ds-unit">pcs masuk</div>
        </div>
        <div class="ds-summary-card">
            <div class="ds-label">Output</div>
            <div class="ds-val" id="ds-val-output">-</div>
            <div class="ds-unit">pcs keluar</div>
        </div>
        <div class="ds-summary-card">
            <div class="ds-label">NG / Cacat</div>
            <div class="ds-val" id="ds-val-ng">-</div>
            <div class="ds-unit">pcs</div>
        </div>
        <div class="ds-summary-card">
            <div class="ds-label">Yield</div>
            <div class="ds-val" id="ds-val-yield">-</div>
            <div class="ds-unit">output / input</div>
        </div>
        <div class="ds-summary-card ds-eff-card" id="ds-eff-card">
            <div class="ds-label">EFF Shift Ini</div>
            <div class="ds-val" id="ds-val-eff">-</div>
            <div class="ds-unit" id="ds-eff-unit">Belum ada data</div>
        </div>
    </div>

    <div class="ds-section">
        <div class="ds-section-header">
            <div class="ds-section-title">Performance Sesi (<span id="ds-judul-shift-performance">Shift</span>)</div>
            <div class="ds-section-meta" id="ds-meta-performance">0 sesi tercatat</div>
        </div>
        <div id="ds-konten-performance"><div class="ds-kosong">Memuat...</div></div>
    </div>

    <div class="ds-section">
        <div class="ds-section-header">
            <div class="ds-section-title">Alasan Stop (<span id="ds-judul-shift-stop">Shift</span>)</div>
            <div class="ds-section-meta" id="ds-meta-total-stop">-</div>
        </div>
        <div id="ds-konten-stop"><div class="ds-kosong">Memuat...</div></div>
    </div>
</div>
