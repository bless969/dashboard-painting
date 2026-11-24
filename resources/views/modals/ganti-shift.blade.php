<!-- ===== MODAL SERAH TERIMA SHIFT & PIC OPERATOR ===== -->
<div id="modal-ganti-shift" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#ffffff; border-radius:16px; width:100%; max-width:620px; max-height:92vh; display:flex; flex-direction:column; box-shadow:0 20px 40px rgba(0,0,0,0.25); border:1.5px solid #c7d2fe; overflow:hidden; animation:modalPopIn 0.25s cubic-bezier(0.16,1,0.3,1);">
        
        <!-- Header Modal -->
        <div style="background:linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color:#ffffff; padding:18px 22px; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:10px; background:rgba(255,255,255,0.12); display:flex; align-items:center; justify-content:center; font-size:20px; border:1px solid rgba(255,255,255,0.2);">
                    👤
                </div>
                <div>
                    <h3 style="margin:0; font-size:17px; font-weight:800; letter-spacing:-0.2px;">Serah Terima Shift &amp; Alokasi Manpower</h3>
                    <div style="font-size:12px; color:#c7d2fe; margin-top:2px;">Atur identitas PIC &amp; alokasi personel stasiun kerja produksi</div>
                </div>
            </div>
            <button type="button" onclick="tutupModalGantiShift()" style="background:transparent; border:none; color:#ffffff; font-size:22px; cursor:pointer; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; opacity:0.8; transition:all 0.2s;" onmouseover="this.style.opacity='1'; this.style.background='rgba(255,255,255,0.15)';" onmouseout="this.style.opacity='0.8'; this.style.background='transparent';">&times;</button>
        </div>

        <!-- Body Modal (Scrollable) -->
        <div style="padding:22px; overflow-y:auto; flex:1;">
            <!-- Input Nama Operator PIC -->
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:13px; font-weight:700; color:#1e293b; margin-bottom:6px;">
                    Nama Operator / PIC Shift <span style="color:#ef4444;">*</span>
                </label>
                <div style="position:relative;">
                    <input type="text" id="input-shift-operator-name" list="operator-suggestions" placeholder="Ketik atau pilih nama operator..." style="width:100%; box-sizing:border-box; padding:10px 14px 10px 38px; border-radius:8px; border:1.5px solid #cbd5e1; font-size:14px; font-weight:700; color:#0f172a; outline:none; transition:border 0.2s;" onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#cbd5e1'">
                    <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:16px; opacity:0.6;">👤</span>
                </div>
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

            <!-- Pilihan Shift Produksi -->
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:700; color:#1e293b; margin-bottom:8px;">
                    Shift Produksi Aktif <span style="color:#ef4444;">*</span>
                </label>
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px;">
                    <label class="shift-option-card" style="border:1.5px solid #cbd5e1; border-radius:10px; padding:10px 12px; cursor:pointer; text-align:center; transition:all 0.2s; display:block;">
                        <input type="radio" name="radio_shift" value="Shift 1" checked style="display:none;" onchange="updateShiftSelectionUI()">
                        <div style="font-size:13px; font-weight:800; color:#0d3b8c;">Shift 1</div>
                        <div style="font-size:11px; color:#64748b; margin-top:2px;">Pagi</div>
                        <div style="font-size:10px; color:#94a3b8; font-family:monospace; margin-top:2px;">06:55–15:00</div>
                    </label>
                    <label class="shift-option-card" style="border:1.5px solid #cbd5e1; border-radius:10px; padding:10px 12px; cursor:pointer; text-align:center; transition:all 0.2s; display:block;">
                        <input type="radio" name="radio_shift" value="Shift 2" style="display:none;" onchange="updateShiftSelectionUI()">
                        <div style="font-size:13px; font-weight:800; color:#0d3b8c;">Shift 2</div>
                        <div style="font-size:11px; color:#64748b; margin-top:2px;">Sore</div>
                        <div style="font-size:10px; color:#94a3b8; font-family:monospace; margin-top:2px;">15:00–23:00</div>
                    </label>
                    <label class="shift-option-card" style="border:1.5px solid #cbd5e1; border-radius:10px; padding:10px 12px; cursor:pointer; text-align:center; transition:all 0.2s; display:block;">
                        <input type="radio" name="radio_shift" value="Shift 3" style="display:none;" onchange="updateShiftSelectionUI()">
                        <div style="font-size:13px; font-weight:800; color:#0d3b8c;">Shift 3</div>
                        <div style="font-size:11px; color:#64748b; margin-top:2px;">Malam</div>
                        <div style="font-size:10px; color:#94a3b8; font-family:monospace; margin-top:2px;">23:00–07:00</div>
                    </label>
                </div>
            </div>

            <!-- ALOKASI POSISI & MANPOWER LINE PAINTING (HYBRID) -->
            <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:8px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:16px;">👥</span>
                        <div style="font-size:13px; font-weight:800; color:#1e293b; text-transform:uppercase; letter-spacing:0.5px;">
                            Alokasi Posisi &amp; Manpower
                        </div>
                    </div>
                    <div style="display:flex; gap:6px; align-items:center;">
                        <span id="badge-total-manpower" style="background:#4f46e5; color:#ffffff; font-size:12px; font-weight:800; padding:3px 10px; border-radius:6px;">
                            Total: 6 Orang
                        </span>
                        <span id="badge-sub-manpower" style="background:#e0e7ff; color:#3730a3; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px;">
                            4 Tetap · 2 KWT
                        </span>
                    </div>
                </div>
                <div style="font-size:11.5px; color:#64748b; margin-bottom:12px; line-height:1.4;">
                    Tentukan jumlah personel, nama operator, dan status kepegawaian di setiap stasiun kerja.
                </div>

                <!-- Container Baris Posisi Dinamis -->
                <div id="container-posisi-manpower" style="display:flex; flex-direction:column; gap:8px; margin-bottom:12px;">
                    <!-- Di-render otomatis via JavaScript -->
                </div>

                <!-- Tombol Tambah Posisi & Keterangan -->
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; padding-top:4px;">
                    <button type="button" onclick="tambahBarisPosisi()" style="background:#ffffff; border:1.5px dashed #6366f1; color:#4f46e5; padding:7px 14px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s;" onmouseover="this.style.background='#eef2ff';" onmouseout="this.style.background='#ffffff';">
                        <span>➕</span> Tambah Posisi Stasiun
                    </button>
                    <div style="font-size:11px; color:#64748b; font-style:italic;">
                        * Terhubung otomatis ke rekap Man Time
                    </div>
                </div>

                <!-- Hidden Input untuk Kompatibilitas Backend Tetap / KWT / Daily -->
                <input type="hidden" id="input-mp-tetap" value="4">
                <input type="hidden" id="input-mp-kwt" value="2">
                <input type="hidden" id="input-mp-daily" value="0">
            </div>

            <!-- Datalist Rekomendasi Nama Posisi Line Painting -->
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

            <!-- Catatan Sesi Shift -->
            <div>
                <label style="display:block; font-size:12.5px; font-weight:700; color:#475569; margin-bottom:4px;">
                    Catatan Shift / Memo (Opsional)
                </label>
                <input type="text" id="input-shift-notes" placeholder="Misal: Rencana produksi part L2RCVR0240AIP target 500 pcs" style="width:100%; box-sizing:border-box; padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1; font-size:13px; outline:none;">
            </div>
        </div>

        <!-- Footer Modal -->
        <div style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:14px 22px; display:flex; justify-content:flex-end; gap:10px; align-items:center; flex-shrink:0;">
            <button type="button" onclick="tutupModalGantiShift()" style="padding:9px 16px; border-radius:8px; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; font-size:13px; font-weight:700; cursor:pointer;">
                Batal
            </button>
            <button type="button" id="btn-save-shift" onclick="simpanSesiShift()" style="padding:9px 20px; border-radius:8px; background:#4f46e5; color:#ffffff; border:none; font-size:13px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:6px; box-shadow:0 2px 8px rgba(79,70,229,0.35);">
                <span>💾</span> Simpan &amp; Mulai Sesi
            </button>
        </div>

    </div>
</div>
