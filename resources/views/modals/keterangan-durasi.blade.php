<!-- ===== MODAL: KETERANGAN DURASI PENYIMPANGAN WAKTU KERJA ===== -->
<div id="modal-keterangan-durasi" class="modal-overlay" style="display:none;" onclick="tutupModalJikaDiLuarKeteranganDurasi(event)">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-header">
            <div class="panel-title">Waktu Kerja Di Bawah Standar</div>
            <button type="button" class="modal-close" onclick="tutupModalKeteranganDurasi()">&times;</button>
        </div>
        <div class="settings-row-desc" id="keterangan-durasi-pesan" style="margin-bottom:16px;"></div>

        <label style="font-size:13px; font-weight:600; color:#2b2b3a; display:block; margin-bottom:8px;">Alasan Penyimpangan (wajib diisi):</label>
        <textarea id="keterangan-durasi-input" class="filter-select" rows="3"
            style="width:100%; resize:vertical; font-family:inherit;"
            placeholder="Contoh: menunggu kedatangan material dari gudang"></textarea>
        <div id="keterangan-durasi-error" style="display:none; color:#c0392b; font-size:12.5px; margin-top:6px;">Keterangan wajib diisi.</div>

        <button type="button" class="btn-simpan-keterangan" style="width:100%; margin-top:16px;" onclick="konfirmasiKeteranganDurasi()">Simpan &amp; Lanjutkan</button>
    </div>
</div>
