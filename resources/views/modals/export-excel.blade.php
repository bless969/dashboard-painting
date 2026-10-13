<!-- ===== MODAL: PILIHAN EKSPOR EXCEL ===== -->
<div id="modal-export-excel" class="modal-overlay" style="display:none;" onclick="tutupModalJikaDiLuarExportExcel(event)">
    <div class="modal-box" style="max-width:460px; padding:20px 24px;">
        <div class="modal-header" style="margin-bottom:14px;">
            <div class="panel-title" style="font-size:16px; margin-bottom:0; display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#0d3b8c" stroke-width="2.2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="12" y1="18" x2="12" y2="12"></line>
                    <line x1="9" y1="15" x2="15" y2="15"></line>
                </svg>
                <span>Download Data Excel</span>
            </div>
            <button type="button" class="modal-close" onclick="tutupModalExportExcel()">&times;</button>
        </div>

        <div class="settings-row-desc" style="margin-bottom:16px; font-size:12.5px; color:#64748b;">
            Pilih periode data produksi yang ingin Anda unduh ke format Excel (.xls).
        </div>

        <form id="form-export-excel" onsubmit="eksekusiDownloadExcel(event)">
            <!-- Pilihan Periode -->
            <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">
                <!-- Opsi 1: Per Tahun -->
                <label style="display:flex; align-items:flex-start; gap:10px; padding:10px 12px; border:1.5px solid #dbe4f3; border-radius:8px; background:#f8fafc; cursor:pointer;">
                    <input type="radio" name="export_periode" value="tahun" checked onchange="ubahPilihanPeriodeExport(this.value)" style="margin-top:3px; cursor:pointer;">
                    <div style="flex:1;">
                        <div style="font-size:13.5px; font-weight:600; color:#1e293b;">Per Tahun</div>
                        <div style="font-size:11.5px; color:#64748b; margin-top:2px;">Dikelompokkan per bulan, lengkap dengan jeda pemisah & subtotal bulanan.</div>
                        <div id="export-field-tahun" style="margin-top:8px;">
                            <select id="export-input-tahun" class="filter-select" style="font-size:13px; padding:6px 10px; width:130px;">
                                <!-- Diisi via JS -->
                            </select>
                        </div>
                    </div>
                </label>

                <!-- Opsi 2: Per Bulan -->
                <label style="display:flex; align-items:flex-start; gap:10px; padding:10px 12px; border:1.5px solid #e2e8f0; border-radius:8px; background:#fff; cursor:pointer;">
                    <input type="radio" name="export_periode" value="bulan" onchange="ubahPilihanPeriodeExport(this.value)" style="margin-top:3px; cursor:pointer;">
                    <div style="flex:1;">
                        <div style="font-size:13.5px; font-weight:600; color:#1e293b;">Per Bulan</div>
                        <div style="font-size:11.5px; color:#64748b; margin-top:2px;">Rekap seluruh hari pada bulan dan tahun tertentu.</div>
                        <div id="export-field-bulan" style="margin-top:8px; display:none;">
                            <input type="month" id="export-input-bulan" class="filter-select" style="font-size:13px; padding:6px 10px; width:170px;">
                        </div>
                    </div>
                </label>

                <!-- Opsi 3: Per Minggu (Rentang Tanggal) -->
                <label style="display:flex; align-items:flex-start; gap:10px; padding:10px 12px; border:1.5px solid #e2e8f0; border-radius:8px; background:#fff; cursor:pointer;">
                    <input type="radio" name="export_periode" value="minggu" onchange="ubahPilihanPeriodeExport(this.value)" style="margin-top:3px; cursor:pointer;">
                    <div style="flex:1;">
                        <div style="font-size:13.5px; font-weight:600; color:#1e293b;">Per Minggu / Rentang Tanggal</div>
                        <div style="font-size:11.5px; color:#64748b; margin-top:2px;">Rekap tanggal dari rentang awal sampai akhir.</div>
                        <div id="export-field-minggu" style="margin-top:8px; display:none; display:none; align-items:center; gap:6px; flex-wrap:wrap;">
                            <input type="date" id="export-input-dari" class="filter-select" style="font-size:12.5px; padding:5px 8px;">
                            <span style="font-size:12px; color:#64748b;">s/d</span>
                            <input type="date" id="export-input-sampai" class="filter-select" style="font-size:12.5px; padding:5px 8px;">
                        </div>
                    </div>
                </label>

                <!-- Opsi 4: Semua Data -->
                <label style="display:flex; align-items:flex-start; gap:10px; padding:10px 12px; border:1.5px solid #e2e8f0; border-radius:8px; background:#fff; cursor:pointer;">
                    <input type="radio" name="export_periode" value="semua" onchange="ubahPilihanPeriodeExport(this.value)" style="margin-top:3px; cursor:pointer;">
                    <div style="flex:1;">
                        <div style="font-size:13.5px; font-weight:600; color:#1e293b;">Semua Data (Histori Lengkap)</div>
                        <div style="font-size:11.5px; color:#64748b; margin-top:2px;">Mengunduh seluruh rekap tanggal yang pernah tercatat.</div>
                    </div>
                </label>
            </div>

            <!-- Tombol Aksi -->
            <div style="display:flex; justify-content:flex-end; gap:8px; border-top:1px solid #eef1f7; padding-top:14px;">
                <button type="button" class="btn-secondary" onclick="tutupModalExportExcel()" style="padding:8px 16px; font-size:13px;">Batal</button>
                <button type="submit" class="btn-green" style="padding:8px 18px; font-size:13px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Download Excel
                </button>
            </div>
        </form>
    </div>
</div>
