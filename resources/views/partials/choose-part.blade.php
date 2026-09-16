<!-- ===== KONTROL PRODUKSI PART: Choose Part ===== -->
<div class="panel panel-choose-part">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <div class="panel-title" style="margin-bottom:0; font-size:14.5px;">Choose Part</div>
        <button type="button" class="btn-manage-part" onclick="bukaModalManagePart()" title="Kelola dan tambah daftar part, cycle time &amp; pcs per sheet">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Manage Part
        </button>
    </div>

    <!-- Pilihan Part (C) -->
    <div class="settings-row-desc" style="margin-bottom:6px; font-weight:600; color:#0d3b8c; font-size:12px;">(C) Cycle Time Standar — Pilih Part:</div>
    <div class="ct-part-picker" style="margin-bottom:8px;">
        <div class="ct-search-wrapper">
            <input type="text" id="ct-part-search" class="filter-select ct-search-input"
                   placeholder="Cari kode part... (mis. LCA5DSC2)" autocomplete="off"
                   oninput="filterPartCt()" onfocus="filterPartCt()">
            <div id="ct-search-suggestions" class="ct-search-suggestions"></div>
        </div>
        <select id="ct-part-select" class="filter-select" onchange="tampilkanDetailCt(this.value)">
            <option value="">-- Pilih Part --</option>
        </select>
    </div>

    <div id="ct-detail-box" class="ct-detail-box" style="display:none; margin-bottom:0;">
        <div class="ct-detail-header" style="display:flex; justify-content:space-between; align-items:center;">
            <span class="ct-detail-part" id="ct-detail-part"></span>
            <button type="button" class="btn-edit-current-part" id="btn-edit-current-part" onclick="bukaEditPartIni()" title="Edit cycle time &amp; pcs per sheet part ini">
                ✏️ Edit Part
            </button>
        </div>
        <div id="ct-detail-values" class="ct-detail-values"></div>
    </div>
    <div id="ct-detail-empty" class="ct-detail-empty" style="margin-bottom:0; font-size:12px; padding:6px 10px;">Belum ada part yang dipilih.</div>

    <!-- State & input otomatis untuk sesi aktif kontrol produksi -->
    <input type="hidden" id="perf-tanggal">
    <input type="hidden" id="perf-jam-mulai">
    <input type="hidden" id="perf-jam-selesai">
    <select id="perf-ct-pilihan" style="display:none;"><option value="">-- Pilih part dulu --</option></select>
    <strong id="perf-part-label" style="display:none;">Belum ada</strong>
</div>
