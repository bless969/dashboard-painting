<!-- ===== MODAL: RINCIAN CYCLE TIME PER SHEET ===== -->
<div id="modal-detail-ct" class="modal-overlay" style="display:none;" onclick="tutupModalJikaDiLuarDetailCt(event)">
    <div class="modal-box" style="max-width:640px;">
        <div class="modal-header">
            <div class="panel-title">Rincian Cycle Time per Sheet — <span id="dct-part-label">-</span></div>
            <button type="button" class="modal-close" onclick="tutupModalDetailCt()">&times;</button>
        </div>
        <div class="settings-row-desc" style="margin-bottom:14px;">
            Daftar tiap sheet yang lewat sensor output pada rentang sesi ini, beserta cycle time
            per pcs hasil konversi. Kalau nilai (C) rata-rata kelihatan kebesaran, cek di sini
            apakah ada satu-dua sheet dengan angka jauh di atas yang lain (biasanya karena sheet
            itu "kena" jeda saat mesin sempat berhenti sebelum keluar sensor output).
        </div>

        <div class="riwayat-summary" style="margin-bottom:14px;">
            <div class="summary-chip">
                <span class="chip-label">Jumlah Sheet</span>
                <span class="chip-value" id="dct-jumlah">-</span>
            </div>
            <div class="summary-chip">
                <span class="chip-label">Rata-rata</span>
                <span class="chip-value" id="dct-rata">-</span>
            </div>
            <div class="summary-chip">
                <span class="chip-label">Median</span>
                <span class="chip-value" id="dct-median">-</span>
            </div>
            <div class="summary-chip highlight">
                <span class="chip-label">Min / Max</span>
                <span class="chip-value" id="dct-minmax">-</span>
            </div>
        </div>

        <div id="dct-kosong" class="ct-detail-empty" style="display:none;">Belum ada event output pada rentang ini.</div>

        <div id="dct-tabel-wrapper" style="max-height:420px; overflow-y:auto;">
            <table id="dct-tabel" style="width:100%;">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Jam</th>
                        <th>Cycle Time Mentah (detik)</th>
                        <th>Per Sheet (menit)</th>
                        <th>Per Pcs (menit)</th>
                    </tr>
                </thead>
                <tbody id="dct-isi-tabel"></tbody>
            </table>
        </div>
    </div>
</div>
