<!-- ===== MODAL: PILIH NILAI (C) SAAT SELESAI PRODUKSI ===== -->
<div id="modal-pilih-c" class="modal-overlay" style="display:none;" onclick="tutupModalJikaDiLuarPilihC(event)">
    <div class="modal-box">
        <div class="modal-header">
            <div class="panel-title">Pilih Nilai (C) Cycle Time Standar</div>
            <button type="button" class="modal-close" onclick="tutupModalPilihC()">&times;</button>
        </div>
        <div class="settings-row-desc" style="margin-bottom:16px;">
            Sesi produksi part <strong id="pilih-c-part-label">-</strong> akan diselesaikan & dihitung.
            Konfirmasi dulu nilai (C) Cycle Time Standar yang mau dipakai untuk sesi ini.
        </div>

        <div id="pilih-c-opsi-wrapper" class="pilih-c-opsi-wrapper"></div>

        <div class="settings-row-desc" style="margin:16px 0 8px;">Atau hitung otomatis dari data (rata-rata waktu barang masuk input → masuk output):</div>
        <button type="button" class="btn-toggle-maintenance" id="btn-hitung-c-otomatis" onclick="hitungNilaiCOtomatisDariData()">Hitung dari Data Input/Output</button>
        <div id="hasil-c-otomatis" class="ct-detail-empty" style="display:none; margin-top:10px;"></div>

        <div class="settings-row-desc" style="margin:16px 0 8px;">Atau isi manual (menit):</div>
        <input type="number" id="pilih-c-manual" class="filter-select" step="0.0001" min="0" placeholder="Contoh: 0.089" oninput="this.dataset.otomatis=''">

        <button type="button" class="btn-simpan-keterangan" style="width:100%; margin-top:16px;" onclick="konfirmasiNilaiC()">Konfirmasi &amp; Selesaikan Sesi</button>
    </div>
</div>
