<!-- ===== MODAL: ALASAN MESIN BERHENTI (P / LOSS / SELESAI / MAINTENANCE) ===== -->
<div id="modal-alasan-stop" class="modal-overlay" style="display:none;" onclick="tutupModalJikaDiLuarAlasanStop(event)">
    <div class="modal-box">
        <div class="modal-header">
            <div class="panel-title"><span id="alasan-stop-judul">Alasan Mesin Berhenti</span> — <span id="alasan-stop-line"></span></div>
            <button type="button" class="modal-close" onclick="tutupModalAlasanStop()">&times;</button>
        </div>
        <div class="settings-row-desc" style="margin-bottom:14px;">Pilih kategori dan alasan mesin berhenti.</div>

        <div id="alasan-stop-info-selesai" style="display:none; margin-bottom:14px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:8px 14px; font-size:13.5px; color:#1e40af; font-weight:600; align-items:center; gap:6px;">
            <span>Stop: <strong id="alasan-stop-ringkasan-teks" style="color:#1d4ed8;">-</strong></span>
        </div>

        <!-- Keterangan Warna Patlite saat Stop -->
        <div id="alasan-stop-patlite-banner" style="margin-bottom:14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:9px 14px; display:flex; align-items:center; justify-content:space-between; gap:8px;">
            <div style="display:flex; align-items:center; gap:8px;">
                <span id="alasan-stop-patlite-dot" style="display:inline-block; width:11px; height:11px; border-radius:50%; background:#ef4444;"></span>
                <span style="font-size:13.5px; color:#475569; font-weight:600;">Patlite:</span>
                <strong id="alasan-stop-patlite-label" style="font-size:14px; color:#0f172a;">Lampu Merah (Stop)</strong>
            </div>
            <span id="alasan-stop-patlite-badge" style="background:#fee2e2; color:#991b1b; border:1px solid #fecaca; padding:3px 10px; border-radius:6px; font-weight:700; font-size:12.5px;">
                Merah
            </span>
            <input type="hidden" id="alasan-stop-warna-patlite-val" value="Merah">
        </div>

        <div class="alasan-kategori-pilih">
            <button type="button" class="btn-kategori btn-kategori-p" id="btn-kategori-P" onclick="pilihKategoriAlasan('P')">P (Planned)</button>
            <button type="button" class="btn-kategori btn-kategori-loss" id="btn-kategori-Loss" onclick="pilihKategoriAlasan('Loss')">Loss</button>
            <button type="button" class="btn-kategori btn-kategori-selesai" id="btn-kategori-Selesai" onclick="pilihKategoriAlasan('Selesai')">Selesai</button>
            <button type="button" class="btn-kategori btn-kategori-maint" id="btn-kategori-Maintenance" onclick="pilihKategoriAlasan('Maintenance')">Maintenance</button>
        </div>

        <div id="alasan-detail-wrapper" style="display:none;">
            <select id="alasan-detail-select" class="filter-select alasan-detail-select"></select>
            <button type="button" class="btn-simpan-keterangan" style="width:100%; margin-top:12px; font-size:14px;" onclick="simpanAlasanStop()">Simpan Alasan</button>
        </div>

        <!-- Form Input Mode Maintenance -->
        <div id="maintenance-detail-wrapper" style="display:none; margin-top:16px; text-align:left;">
            <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:13.5px; color:#92400e;">
                <strong>Waktu Mati:</strong> <span id="maint-waktu-mati-label" style="font-weight:600;">-</span>
                <input type="hidden" id="maint-waktu-mati-val">
            </div>

            <label style="display:block; font-size:13.5px; font-weight:700; color:#1e293b; margin-bottom:6px;">Nama Mesin *:</label>
            <input type="text" id="maint-nama-mesin" class="filter-select" style="width:100%; margin-bottom:14px; padding:9px 12px; font-size:14px; background:#fff;" placeholder="Spray Booth 1, Konveyor Hanger, Oven..." autocomplete="off">

            <label style="display:block; font-size:13.5px; font-weight:700; color:#1e293b; margin-bottom:6px;">Kategori Masalah *:</label>
            <select id="maint-kategori-masalah" class="filter-select" style="width:100%; margin-bottom:14px; padding:9px 12px; font-size:14px; background:#fff;">
                <option value="">-- Pilih Kategori Masalah --</option>
                <option value="Konveyor">Konveyor</option>
                <option value="Kabel Putus">Kabel Putus</option>
            </select>

            <label style="display:block; font-size:13.5px; font-weight:700; color:#1e293b; margin-bottom:6px;">Detail Masalah *:</label>
            <textarea id="maint-note-error" class="filter-select" rows="3" style="width:100%; resize:vertical; font-family:inherit; padding:10px 12px; font-size:14px; margin-bottom:14px; background:#fff; box-sizing:border-box;" placeholder="Jelaskan kerusakan secara singkat..."></textarea>

            <button type="button" class="btn-simpan-keterangan" id="btn-kirim-maint" style="width:100%; background:#d97706; border-color:#d97706; color:#fff; font-weight:700; padding:11px; font-size:14.5px; margin-bottom:0;" onclick="kirimFormMaintenance()">
                Kirim Laporan Maintenance
            </button>
            <div id="maint-kirim-status" style="display:none; margin-top:12px; font-size:13px; padding:10px 12px; border-radius:6px;"></div>
        </div>
    </div>
</div>
