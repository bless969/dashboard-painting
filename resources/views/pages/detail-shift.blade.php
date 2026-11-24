<div id="view-detail-shift" style="{{ $view_aktif !== 'detail-shift' ? 'display:none;' : '' }}">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
        <button type="button" class="btn-kembali-riwayat" onclick="kembaliKeRiwayat(event)">
            &larr; Kembali
        </button>

        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <!-- Badge Operator PIC & Shift Bertugas -->
            <div style="background:#eef2ff; border:1px solid #c7d2fe; border-radius:8px; padding:6px 12px; font-size:12.5px; color:#3730a3; display:inline-flex; align-items:center; gap:8px;">
                <span>👤 PIC:</span>
                <strong id="ds-active-operator-badge">Mukhamad Nawwaf</strong>
                <span style="color:#6366f1;">•</span>
                <span id="ds-active-shift-badge">Shift 1 (Pagi)</span>
                <span style="color:#6366f1;">•</span>
                <span id="ds-active-mp-badge" style="color:#166534; font-weight:700;">6 MP</span>
            </div>

            <div class="ds-nav-shift" style="margin-bottom:0;">
                <a href="#" id="ds-link-prev" onclick="return false;">&larr; Shift</a>
                <span class="ds-nav-divider">|</span>
                <a href="#" id="ds-link-next" onclick="return false;">Shift &rarr;</a>
            </div>
        </div>
    </div>

    <!-- 3 Pilihan Shift -->
    <div class="ds-session-tabs" id="ds-session-tabs">
        <button type="button" class="ds-session-btn aktif" id="ds-tab-pagi" onclick="dsPilihShift('Pagi')">
            <span class="badge-shift badge-shift-pagi">Pagi</span>
            <span class="ds-session-time">06:55 – 15:50</span>
        </button>
        <button type="button" class="ds-session-btn" id="ds-tab-sore" onclick="dsPilihShift('Sore')">
            <span class="badge-shift badge-shift-sore">Sore</span>
            <span class="ds-session-time">15:50 – 23:20</span>
        </button>
        <button type="button" class="ds-session-btn" id="ds-tab-malam" onclick="dsPilihShift('Malam')">
            <span class="badge-shift badge-shift-malam">Malam</span>
            <span class="ds-session-time">23:20 – 06:55</span>
        </button>
    </div>

    <div class="ds-filter-bar">
        <div class="ds-filter-field">
            <label>Tanggal</label>
            <input type="date" id="ds-filter-tanggal">
        </div>
        <div class="ds-filter-field" style="display:none;">
            <label>Shift</label>
            <select id="ds-filter-shift">
                <option value="Pagi">Pagi (06:55–15:50)</option>
                <option value="Sore">Sore (15:50–23:20)</option>
                <option value="Malam">Malam (23:20–06:55)</option>
            </select>
        </div>
        <button type="button" class="ds-btn-tampilkan" id="ds-btn-tampilkan" onclick="muatUlangDetailShift()">Tampilkan</button>
        <span class="ds-filter-loading" id="ds-filter-loading" style="display:none;">Memuat...</span>
    </div>

    <div class="ds-summary-grid" id="ds-summary-grid">
        <div class="ds-summary-card">
            <div class="ds-label">Total Barang Masuk (IN)</div>
            <div class="ds-val" id="ds-val-input" style="color:#1e3a8a;">-</div>
            <div class="ds-unit">pcs masuk line</div>
        </div>
        <div class="ds-summary-card">
            <div class="ds-label">Output Selesai (OK)</div>
            <div class="ds-val" id="ds-val-output" style="color:#059669;">-</div>
            <div class="ds-unit">Finished Good (pcs)</div>
        </div>
        <div class="ds-summary-card" style="border-left:3px solid #ef4444;">
            <div class="ds-label" style="color:#991b1b;">Part Cacat (NG)</div>
            <div class="ds-val" id="ds-val-ng" style="color:#dc2626;">-</div>
            <div class="ds-unit" id="ds-ng-subtext">pcs reject</div>
        </div>
        <div class="ds-summary-card highlight">
            <div class="ds-label" style="color:#0284c7;">Yield Produksi</div>
            <div class="ds-val" id="ds-val-yield" style="color:#0369a1;">-</div>
            <div class="ds-unit">(Output OK ÷ Masuk)</div>
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
