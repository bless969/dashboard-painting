<div class="panel choose-part-panel">
    <div class="choose-part-header">
        <div class="panel-title-large">Choose Part</div>
        <button type="button" class="btn-manage-part" onclick="bukaModalManagePart()" title="Kelola daftar part dan cycle time">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Manage Part
        </button>
    </div>

    <!-- Pilih Part & Tampilkan Detail -->
    <div class="choose-part-sub-title">(C) Cycle Time Standar — Pilih Part:</div>
    <div class="ct-part-picker">
        <div class="ct-search-box">
            <input type="text" id="ct-part-search" class="filter-select ct-search-input ct-input-large"
                   placeholder="Cari nama part..."
                   oninput="filterDaftarPartDropdown(this.value)"
                   autocomplete="off"
                   title="Ketik untuk memfilter daftar part">
        </div>
        <select id="ct-part-select" class="filter-select ct-select-large" onchange="tampilkanDetailCt(this.value)">
            <option value="">-- Pilih Part --</option>
            @foreach($daftar_part_ct as $nama_part => $detail)
                <option value="{{ $nama_part }}">{{ $nama_part }}</option>
            @endforeach
        </select>
    </div>

    <div id="ct-detail-box" class="ct-detail-box" style="display:none;">
        <div class="ct-detail-header">
            <div class="ct-part-badge-wrapper">
                <span class="ct-part-badge-label">Part Terpilih:</span>
                <span class="ct-detail-part-large" id="ct-detail-part">-</span>
            </div>
            <button type="button" class="btn-edit-current-part" id="btn-edit-current-part" onclick="bukaEditPartIni()" title="Edit cycle time &amp; pcs per sheet part ini">
                ✏️ Edit Part
            </button>
        </div>
        <div id="ct-detail-values" class="ct-detail-values"></div>
    </div>
    <div id="ct-detail-empty" class="ct-detail-empty">Belum ada part yang dipilih. Silakan cari atau pilih part di atas.</div>

    <!-- State & input otomatis untuk sesi aktif kontrol produksi -->
    <input type="hidden" id="perf-tanggal">
    <input type="hidden" id="perf-shift">
    <input type="hidden" id="perf-operator">
    <input type="hidden" id="perf-c-detik">
    <input type="hidden" id="perf-pcs-per-sheet">
</div>
