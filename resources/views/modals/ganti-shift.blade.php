<!-- ===== MODAL SERAH TERIMA SHIFT & PIC OPERATOR ===== -->
<div id="modal-ganti-shift" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#ffffff; border-radius:16px; width:100%; max-width:620px; max-height:92vh; display:flex; flex-direction:column; box-shadow:0 20px 40px rgba(0,0,0,0.25); border:1px solid #cbd5e1; overflow:hidden;">
        
        <div style="background:#0d3b8c; color:#ffffff; padding:18px 22px; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
            <h3 style="margin:0; font-size:18px; font-weight:800;">Serah Terima Shift &amp; Manpower</h3>
            <button type="button" onclick="tutupModalGantiShift()" style="background:transparent; border:none; color:#ffffff; font-size:22px; cursor:pointer; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center;">&times;</button>
        </div>

        <div style="padding:22px; overflow-y:auto; flex:1;">
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:14px; font-weight:700; color:#1e293b; margin-bottom:6px;">
                    Nama Operator / PIC Shift *
                </label>
                <input type="text" id="input-shift-operator-name" list="operator-suggestions" placeholder="Ketik atau pilih nama operator..." style="width:100%; box-sizing:border-box; padding:10px 14px; border-radius:8px; border:1.5px solid #cbd5e1; font-size:14.5px; font-weight:700; color:#0f172a; outline:none;">
                <datalist id="operator-suggestions">
                    <option value="Mukhamad Nawwaf">
                    <option value="Ahmad Fauzi">
                    <option value="Budi Santoso">
                    <option value="Ika">
                    <option value="Iin">
                    <option value="Yessy">
                    <option value="Operator Painting 1">
                    <option value="Operator Painting 2">
                </datalist>
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:14px; font-weight:700; color:#1e293b; margin-bottom:8px;">
                    Shift Produksi Aktif *
                </label>
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px;">
                    <label class="shift-option-card" style="border:1.5px solid #cbd5e1; border-radius:10px; padding:10px 12px; cursor:pointer; text-align:center; display:block;">
                        <input type="radio" name="radio_shift" value="Shift 1" checked style="display:none;" onchange="updateShiftSelectionUI()">
                        <div style="font-size:14px; font-weight:800; color:#0d3b8c;">Shift 1</div>
                        <div style="font-size:12.5px; color:#475569; margin-top:2px;">Pagi (06:55–15:00)</div>
                    </label>
                    <label class="shift-option-card" style="border:1.5px solid #cbd5e1; border-radius:10px; padding:10px 12px; cursor:pointer; text-align:center; display:block;">
                        <input type="radio" name="radio_shift" value="Shift 2" style="display:none;" onchange="updateShiftSelectionUI()">
                        <div style="font-size:14px; font-weight:800; color:#0d3b8c;">Shift 2</div>
                        <div style="font-size:12.5px; color:#475569; margin-top:2px;">Sore (15:00–23:00)</div>
                    </label>
                    <label class="shift-option-card" style="border:1.5px solid #cbd5e1; border-radius:10px; padding:10px 12px; cursor:pointer; text-align:center; display:block;">
                        <input type="radio" name="radio_shift" value="Shift 3" style="display:none;" onchange="updateShiftSelectionUI()">
                        <div style="font-size:14px; font-weight:800; color:#0d3b8c;">Shift 3</div>
                        <div style="font-size:12.5px; color:#475569; margin-top:2px;">Malam (23:00–07:00)</div>
                    </label>
                </div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
                    <div style="font-size:13.5px; font-weight:800; color:#1e293b; text-transform:uppercase;">Alokasi Manpower</div>
                    <div style="display:flex; gap:6px; align-items:center;">
                        <span id="badge-total-manpower" style="background:#0d3b8c; color:#ffffff; font-size:12.5px; font-weight:800; padding:4px 10px; border-radius:6px;">
                            Total: 6 Orang
                        </span>
                        <span id="badge-sub-manpower" style="background:#e0e7ff; color:#1e40af; font-size:12px; font-weight:700; padding:4px 9px; border-radius:6px;">
                            4 Tetap · 2 KWT
                        </span>
                    </div>
                </div>

                <div id="container-posisi-manpower" style="display:flex; flex-direction:column; gap:8px; margin-bottom:12px;"></div>

                <button type="button" onclick="tambahBarisPosisi()" style="background:#ffffff; border:1.5px dashed #0d3b8c; color:#0d3b8c; padding:8px 14px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer;">
                    + Tambah Posisi Stasiun
                </button>

                <input type="hidden" id="input-mp-tetap" value="4">
                <input type="hidden" id="input-mp-kwt" value="2">
                <input type="hidden" id="input-mp-daily" value="0">
            </div>

            <datalist id="posisi-suggestions">
                <option value="Loading / Hanging (Gantung Part)">
                <option value="Sprayer / Painter (Pengecatan)">
                <option value="Unloading (Turun Part)">
                <option value="QC & Packing (Inspeksi)">
                <option value="Pre-treatment / Masking">
                <option value="Touch-up / Koreksi Cat">
                <option value="Helper / Feeder Konveyor">
                <option value="Leader / Maintenance">
            </datalist>

            <div>
                <label style="display:block; font-size:13.5px; font-weight:700; color:#475569; margin-bottom:5px;">
                    Catatan Shift (Opsional)
                </label>
                <input type="text" id="input-shift-notes" placeholder="Catatan produksi..." style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:8px; border:1px solid #cbd5e1; font-size:14px; outline:none;">
            </div>
        </div>

        <div style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:14px 22px; display:flex; justify-content:flex-end; gap:10px; align-items:center; flex-shrink:0;">
            <button type="button" onclick="tutupModalGantiShift()" style="padding:9px 18px; border-radius:8px; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; font-size:14px; font-weight:700; cursor:pointer;">
                Batal
            </button>
            <button type="button" id="btn-save-shift" onclick="simpanSesiShift()" style="padding:9px 20px; border-radius:8px; background:#0d3b8c; color:#ffffff; border:none; font-size:14px; font-weight:700; cursor:pointer;">
                Simpan &amp; Mulai Sesi
            </button>
        </div>

    </div>
</div>
