<!-- ===== MODAL: INPUT PART CACAT / NG (DEFECT) MANUAL ===== -->
<div id="modal-input-ng" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#ffffff; border-radius:16px; width:100%; max-width:580px; max-height:92vh; display:flex; flex-direction:column; box-shadow:0 20px 40px rgba(0,0,0,0.25); border:1.5px solid #fca5a5; overflow:hidden;">
        
        <div style="background:#b91c1c; color:#ffffff; padding:18px 22px; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
            <h3 style="margin:0; font-size:18px; font-weight:800;">Catat Part Cacat (NG)</h3>
            <button type="button" onclick="tutupModalInputNg()" style="background:transparent; border:none; color:#ffffff; font-size:22px; cursor:pointer; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center;">&times;</button>
        </div>

        <div style="padding:20px 24px; overflow-y:auto; flex:1;">
            <div style="background:#fef2f2; border:1px solid #fee2e2; border-radius:8px; padding:10px 14px; margin-bottom:18px; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-size:13px; color:#991b1b; font-weight:700;">Part Aktif:</div>
                <div id="modal-ng-part-label" style="font-size:14.5px; font-weight:800; color:#b91c1c; font-family:monospace;">
                    [PART-001] PART-Tester
                </div>
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:14px; font-weight:700; color:#1e293b; margin-bottom:8px;">
                    Jumlah Part Cacat (Pcs) *
                </label>
                
                <div style="display:flex; gap:8px; margin-bottom:10px;">
                    <button type="button" onclick="setNgPcs(1)" class="btn-quick-ng" style="flex:1; padding:8px 0; border:1.5px solid #cbd5e1; border-radius:8px; background:#f8fafc; font-weight:800; color:#0f172a; cursor:pointer; font-size:13.5px;">+1</button>
                    <button type="button" onclick="setNgPcs(2)" class="btn-quick-ng" style="flex:1; padding:8px 0; border:1.5px solid #cbd5e1; border-radius:8px; background:#f8fafc; font-weight:800; color:#0f172a; cursor:pointer; font-size:13.5px;">+2</button>
                    <button type="button" onclick="setNgPcs(5)" class="btn-quick-ng" style="flex:1; padding:8px 0; border:1.5px solid #cbd5e1; border-radius:8px; background:#f8fafc; font-weight:800; color:#0f172a; cursor:pointer; font-size:13.5px;">+5</button>
                    <button type="button" onclick="setNgPcs(10)" class="btn-quick-ng" style="flex:1; padding:8px 0; border:1.5px solid #cbd5e1; border-radius:8px; background:#f8fafc; font-weight:800; color:#0f172a; cursor:pointer; font-size:13.5px;">+10</button>
                </div>

                <input type="number" id="input-ng-pcs" value="1" min="1" max="999" style="width:100%; box-sizing:border-box; padding:10px 14px; border-radius:8px; border:1.5px solid #cbd5e1; font-size:18px; font-weight:800; color:#b91c1c; outline:none;">
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:14px; font-weight:700; color:#1e293b; margin-bottom:8px;">
                    Jenis Defect *
                </label>
                <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:8px;">
                    <label class="defect-radio-card" style="border:1.5px solid #ef4444; background:#fef2f2; border-radius:8px; padding:10px 12px; cursor:pointer; display:flex; align-items:center; gap:8px;">
                        <input type="radio" name="radio_defect_type" value="Cat Bintik (Dust/Dirt)" checked style="accent-color:#dc2626;" onchange="updateDefectSelectionUI()">
                        <span style="font-size:13.5px; font-weight:700; color:#991b1b;">Cat Bintik (Dust/Dirt)</span>
                    </label>
                    <label class="defect-radio-card" style="border:1.5px solid #cbd5e1; background:#ffffff; border-radius:8px; padding:10px 12px; cursor:pointer; display:flex; align-items:center; gap:8px;">
                        <input type="radio" name="radio_defect_type" value="Cat Meler (Run/Sag)" style="accent-color:#dc2626;" onchange="updateDefectSelectionUI()">
                        <span style="font-size:13.5px; font-weight:700; color:#334155;">Cat Meler (Run/Sag)</span>
                    </label>
                    <label class="defect-radio-card" style="border:1.5px solid #cbd5e1; background:#ffffff; border-radius:8px; padding:10px 12px; cursor:pointer; display:flex; align-items:center; gap:8px;">
                        <input type="radio" name="radio_defect_type" value="Scratch / Gores / Dent" style="accent-color:#dc2626;" onchange="updateDefectSelectionUI()">
                        <span style="font-size:13.5px; font-weight:700; color:#334155;">Scratch / Gores / Dent</span>
                    </label>
                    <label class="defect-radio-card" style="border:1.5px solid #cbd5e1; background:#ffffff; border-radius:8px; padding:10px 12px; cursor:pointer; display:flex; align-items:center; gap:8px;">
                        <input type="radio" name="radio_defect_type" value="Cat Belang / Tipis" style="accent-color:#dc2626;" onchange="updateDefectSelectionUI()">
                        <span style="font-size:13.5px; font-weight:700; color:#334155;">Cat Belang / Tipis</span>
                    </label>
                    <label class="defect-radio-card" style="border:1.5px solid #cbd5e1; background:#ffffff; border-radius:8px; padding:10px 12px; cursor:pointer; display:flex; align-items:center; gap:8px;">
                        <input type="radio" name="radio_defect_type" value="Cat Mengelupas (Peeling)" style="accent-color:#dc2626;" onchange="updateDefectSelectionUI()">
                        <span style="font-size:13.5px; font-weight:700; color:#334155;">Cat Mengelupas (Peeling)</span>
                    </label>
                    <label class="defect-radio-card" style="border:1.5px solid #cbd5e1; background:#ffffff; border-radius:8px; padding:10px 12px; cursor:pointer; display:flex; align-items:center; gap:8px;">
                        <input type="radio" name="radio_defect_type" value="Defect Lainnya" style="accent-color:#dc2626;" onchange="updateDefectSelectionUI()">
                        <span style="font-size:13.5px; font-weight:700; color:#334155;">Defect Lainnya</span>
                    </label>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:13.5px; font-weight:700; color:#475569; margin-bottom:5px;">
                    Catatan (Opsional)
                </label>
                <input type="text" id="input-ng-notes" placeholder="Lokasi atau keterangan singkat..." style="width:100%; box-sizing:border-box; padding:9px 12px; border-radius:6px; border:1px solid #cbd5e1; font-size:14px; color:#334155; outline:none;">
            </div>

            <div style="display:flex; gap:10px; margin-bottom:22px;">
                <button type="button" id="btn-simpan-ng" onclick="simpanInputNg()" style="flex:2; padding:12px; background:#dc2626; color:white; border:none; border-radius:8px; font-weight:800; font-size:14.5px; cursor:pointer;">
                    Simpan Part NG
                </button>
                <button type="button" onclick="tutupModalInputNg()" style="flex:1; padding:12px; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer;">
                    Batal
                </button>
            </div>

            <div style="border-top:1.5px solid #f1f5f9; padding-top:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div style="font-size:13.5px; font-weight:800; color:#0f172a;">Catatan Defect Shift Ini</div>
                    <div id="modal-ng-total-summary" style="font-size:13px; font-weight:700; color:#dc2626;">Total: 0 Pcs</div>
                </div>
                <div id="modal-ng-log-container" style="max-height:160px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;">
                    <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                        <thead>
                            <tr style="background:#e2e8f0; color:#475569; font-weight:700;">
                                <th style="padding:8px 10px;">Jam</th>
                                <th style="padding:8px 10px;">Part</th>
                                <th style="padding:8px 10px;">Defect</th>
                                <th style="padding:8px 10px; text-align:center;">Qty</th>
                                <th style="padding:8px 10px; text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-modal-ng-logs">
                            <tr>
                                <td colspan="5" style="padding:16px; text-align:center; color:#64748b;">Belum ada catatan NG untuk sesi ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
