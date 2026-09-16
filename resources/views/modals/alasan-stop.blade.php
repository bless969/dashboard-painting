<!-- ===== MODAL: ALASAN MESIN BERHENTI (P / LOSS / SELESAI / MAINTENANCE) ===== -->
<div id="modal-alasan-stop" class="modal-overlay" style="display:none;" onclick="tutupModalJikaDiLuarAlasanStop(event)">
    <div class="modal-box">
        <div class="modal-header">
            <div class="panel-title"><span id="alasan-stop-judul">Alasan Mesin Berhenti</span> — <span id="alasan-stop-line"></span></div>
            <button type="button" class="modal-close" onclick="tutupModalAlasanStop()">&times;</button>
        </div>
        <div class="settings-row-desc" style="margin-bottom:12px;">Pilih kategori lalu pilih alasan spesifik kenapa mesin berhenti. Waktu berhenti akan otomatis terhitung sampai alasan disimpan.</div>

        <div id="alasan-stop-info-selesai" style="display:none; margin-bottom:14px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:6px 12px; font-size:12.5px; color:#1e40af; font-weight:600; align-items:center; gap:6px;">
            <span>⏱️</span>
            <span>Stop: <strong id="alasan-stop-ringkasan-teks" style="color:#1d4ed8;">-</strong></span>
        </div>

        <!-- Keterangan Warna Patlite saat Stop -->
        <div id="alasan-stop-patlite-banner" style="margin-bottom:14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; display:flex; align-items:center; justify-content:space-between; gap:8px;">
            <div style="display:flex; align-items:center; gap:8px;">
                <span id="alasan-stop-patlite-dot" style="display:inline-block; width:11px; height:11px; border-radius:50%; background:#ef4444; box-shadow:0 0 6px rgba(239,68,68,0.7);"></span>
                <span style="font-size:12.5px; color:#475569; font-weight:500;">Kondisi Patlite:</span>
                <strong id="alasan-stop-patlite-label" style="font-size:13px; color:#0f172a;">Lampu Merah (Stop)</strong>
            </div>
            <span id="alasan-stop-patlite-badge" style="display:inline-flex; align-items:center; gap:5px; background:#fee2e2; color:#991b1b; border:1px solid #fecaca; padding:2px 8px; border-radius:6px; font-weight:600; font-size:11.5px;">
                🔴 Merah
            </span>
            <input type="hidden" id="alasan-stop-warna-patlite-val" value="Merah">
        </div>

        <div class="alasan-kategori-pilih">
            <button type="button" class="btn-kategori btn-kategori-p" id="btn-kategori-P" onclick="pilihKategoriAlasan('P')">P (Planned)</button>
            <button type="button" class="btn-kategori btn-kategori-loss" id="btn-kategori-Loss" onclick="pilihKategoriAlasan('Loss')">Loss</button>
            <button type="button" class="btn-kategori btn-kategori-selesai" id="btn-kategori-Selesai" onclick="pilihKategoriAlasan('Selesai')">Selesai Produksi</button>
            <button type="button" class="btn-kategori btn-kategori-maint" id="btn-kategori-Maintenance" onclick="pilihKategoriAlasan('Maintenance')">🔧 Maintenance</button>
        </div>

        <div id="alasan-detail-wrapper" style="display:none;">
            <select id="alasan-detail-select" class="filter-select alasan-detail-select"></select>
            <button type="button" class="btn-simpan-keterangan" style="width:100%; margin-top:12px;" onclick="simpanAlasanStop()">Simpan Alasan</button>
        </div>

        <!-- Form Input Mode Maintenance -->
        <div id="maintenance-detail-wrapper" style="display:none; margin-top:16px; text-align:left;">
            <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:13px; color:#92400e; display:flex; align-items:center; gap:8px;">
                <span>⏱️</span>
                <div>
                    <strong>Waktu Padlight Mati:</strong> <span id="maint-waktu-mati-label" style="font-weight:600;">-</span>
                    <input type="hidden" id="maint-waktu-mati-val">
                </div>
            </div>

            <label style="display:block; font-size:13px; font-weight:600; color:#1e293b; margin-bottom:6px;">Nama Mesin yang Maintenance <span style="color:#dc2626;">*</span>:</label>
            <input type="text" id="maint-nama-mesin" class="filter-select" style="width:100%; margin-bottom:14px; padding:8px 12px; font-size:13.5px;" placeholder="Contoh: SMT-01, Wave Soldering, Auto Insert, Konveyor..." autocomplete="off">

            <label style="display:block; font-size:13px; font-weight:600; color:#1e293b; margin-bottom:6px;">Kategori Permasalahan <span style="color:#dc2626;">*</span>:</label>
            <select id="maint-kategori-masalah" class="filter-select" style="width:100%; margin-bottom:14px; padding:8px 12px; font-size:13.5px;">
                <option value="">-- Pilih Kategori Masalah --</option>
                <option value="Konveyor">Konveyor</option>
                <option value="Kabel Putus">Kabel Putus</option>
            </select>

            <label style="display:block; font-size:13px; font-weight:600; color:#1e293b; margin-bottom:6px;">Note Error / Detail Masalah (Wajib diisi):</label>
            <textarea id="maint-note-error" class="filter-select" rows="3" style="width:100%; resize:vertical; font-family:inherit; padding:10px 12px; font-size:13px; margin-bottom:12px; border-radius:6px; box-sizing:border-box;" placeholder="Jelaskan apa error atau kerusakannya secara singkat..."></textarea>

            <div style="font-size:12px; color:#64748b; line-height:1.4; margin-bottom:14px;">
                ℹ️ Waktu maintenance ini otomatis dihitung sebagai <strong>Loss Time</strong> dan data akan dikirim ke website tujuan.
            </div>

            <button type="button" class="btn-simpan-keterangan" id="btn-kirim-maint" style="width:100%; background:#d97706; border-color:#d97706; color:#fff; font-weight:700; padding:10px; font-size:14px;" onclick="kirimFormMaintenance()">
                🚀 Kirim
            </button>
            <div id="maint-kirim-status" style="display:none; margin-top:12px; font-size:12.5px; padding:10px 12px; border-radius:6px;"></div>
        </div>
    </div>
</div>
