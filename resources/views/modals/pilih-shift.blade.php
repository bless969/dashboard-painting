<!-- ===== MODAL: PILIH SESI SHIFT ===== -->
<div id="modal-pilih-shift" class="modal-overlay" style="display:none;" onclick="tutupModalJikaDiLuarPilihShift(event)">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-header">
            <div class="panel-title">Pilih Shift &mdash; <span id="mps-label-tanggal" style="color:#0d3b8c;">-</span></div>
            <button type="button" class="modal-close" onclick="tutupModalPilihShift()">&times;</button>
        </div>

        <div id="mps-kartu-container" style="display:flex; flex-direction:column; gap:8px; margin:14px 0;">
            <!-- Diisi dinamis -->
        </div>

        <div style="border-top:1px solid #eef1f7; padding-top:12px; display:flex; justify-content:flex-end;">
            <button type="button" class="btn-secondary" onclick="tutupModalPilihShift()" style="font-size:12.5px; padding:7px 16px;">Tutup</button>
        </div>
    </div>
</div>
