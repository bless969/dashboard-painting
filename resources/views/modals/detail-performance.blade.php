<!-- ===== MODAL: DETAIL SESI PRODUKSI (Riwayat Tabel Hasil Produksi) ===== -->
<div id="modal-detail-performance" class="modal-overlay" style="display:none;" onclick="tutupModalJikaDiLuarDetailPerformance(event)">
    <div class="modal-box" style="max-width:720px;">
        <div class="modal-header">
            <div class="panel-title">Detail Sesi Produksi — <span id="dp-part-label"></span></div>
            <button type="button" class="modal-close" onclick="tutupModalDetailPerformance()">&times;</button>
        </div>
        <div class="modal-ringkasan">
            <div class="ringkasan-chip">Sesi #<b id="dp-id"></b></div>
            <div class="ringkasan-chip">Jam Kerja: <b id="dp-jam"></b></div>
            <div class="ringkasan-chip">Disimpan: <b id="dp-disimpan"></b></div>
        </div>

        <div id="dp-catatan-rumus-lama" style="display:none; margin-bottom:14px; padding:10px 14px; background:#fff8e1; border-radius:8px; border:1.5px solid #ffe082; font-size:12.5px; color:#7a5800;">
            Sesi ini dibuat sebelum rumus "Card Tabel Hasil Produksi" diperbarui, jadi masih menampilkan hasil dengan rumus lama.
        </div>
        <div class="perf-hasil-grid">
            <div class="perf-hasil-card">
                <div class="perf-hasil-label">(B) Jam Produksi</div>
                <div class="perf-hasil-value" id="dp-val-B">-</div>
                <div class="perf-hasil-desc">menit, dari part dipilih s/d Selesai Produksi</div>
            </div>
            <div class="perf-hasil-card">
                <div class="perf-hasil-label">(C) Cycle Time Standar</div>
                <div class="perf-hasil-value" id="dp-val-C">-</div>
                <div class="perf-hasil-desc">menit/pcs, dari nilai standar daftar part yang dipilih <span id="dp-val-C-manual-tag" style="display:none; color:#b45309; font-weight:700;">(dikoreksi manual)</span></div>
            </div>
            <div class="perf-hasil-card">
                <div class="perf-hasil-label">(F) Finished Good</div>
                <div class="perf-hasil-value" id="dp-val-F">-</div>
                <div class="perf-hasil-desc">pcs terdeteksi sensor output <span id="dp-val-F-manual-tag" style="display:none; color:#b45309; font-weight:700;">(dikoreksi manual)</span></div>
            </div>
            <div class="perf-hasil-card">
                <div class="perf-hasil-label">(E) NG (Reject)</div>
                <div class="perf-hasil-value" id="dp-val-E">-</div>
                <div class="perf-hasil-desc">pcs gagal terdeteksi sensor output <span id="dp-val-E-manual-tag" style="display:none; color:#b45309; font-weight:700;">(dikoreksi manual)</span></div>
            </div>
            <div class="perf-hasil-card highlight">
                <div class="perf-hasil-label">(D) Actual Cycle Time</div>
                <div class="perf-hasil-value" id="dp-val-D">-</div>
                <div class="perf-hasil-desc">menit/pcs, = (B) ÷ (F)</div>
            </div>
            <div class="perf-hasil-card">
                <div class="perf-hasil-label">(A) Total Stop (P + I)</div>
                <div class="perf-hasil-value" id="dp-val-A">-</div>
                <div class="perf-hasil-desc">menit, = (P) + (I)</div>
            </div>
            <div class="perf-hasil-card">
                <div class="perf-hasil-label">(I) Total Stop Loss</div>
                <div class="perf-hasil-value" id="dp-val-I">-</div>
                <div class="perf-hasil-desc">menit, khusus kategori Loss</div>
            </div>
            <div class="perf-hasil-card">
                <div class="perf-hasil-label">(P) Total Stop Planned</div>
                <div class="perf-hasil-value" id="dp-val-P">-</div>
                <div class="perf-hasil-desc">menit, khusus kategori Planned</div>
            </div>
            <div class="perf-hasil-card">
                <div class="perf-hasil-label">(H) Finish Good Ratio</div>
                <div class="perf-hasil-value" id="dp-val-H">-</div>
                <div class="perf-hasil-desc">= (F) ÷ ((E) + (F))</div>
            </div>
            <div class="perf-hasil-card highlight">
                <div class="perf-hasil-label">(J) Ability Operation</div>
                <div class="perf-hasil-value" id="dp-val-J">-</div>
                <div class="perf-hasil-desc">= ((C) × (F)) ÷ ((B) − (A))</div>
            </div>
            <div class="perf-hasil-card">
                <div class="perf-hasil-label">(K) Operation Time Ratio</div>
                <div class="perf-hasil-value" id="dp-val-K">-</div>
                <div class="perf-hasil-desc">= ((B) − (A)) ÷ ((B) − (P))</div>
            </div>
            <div class="perf-hasil-card highlight">
                <div class="perf-hasil-label">(EFF)</div>
                <div class="perf-hasil-value" id="dp-val-L">-</div>
                <div class="perf-hasil-desc">= (H) × (J) × (K)</div>
            </div>
        </div>

        <div id="dp-koreksi-wrapper" style="margin-top:14px;">
            <button type="button" class="btn-toggle-maintenance" id="btn-koreksi-riwayat" onclick="bukaFormKoreksiRiwayat()">✏️ Koreksi F/E/C</button>
        </div>

        <div id="form-koreksi-riwayat" style="display:none; margin-top:12px; padding:14px 16px; background:#f8fafc; border-radius:10px; border:1.5px solid #dbe4f3;">
            <div class="settings-row-desc" style="margin-bottom:10px;">
                Koreksi manual sesi yang sudah tersimpan ini. Nilai (H, J, K, EFF) akan otomatis
                dihitung ulang begitu disimpan. Kosongkan field untuk kembali pakai nilai otomatis.
            </div>
            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div>
                    <label style="display:block; font-size:12px; color:#555; margin-bottom:4px;">(F) Finished Good — pcs</label>
                    <input type="number" id="dp-input-koreksi-f" class="filter-select" min="0" step="1" placeholder="Otomatis sensor">
                </div>
                <div>
                    <label style="display:block; font-size:12px; color:#555; margin-bottom:4px;">(E) NG (Reject) — pcs</label>
                    <input type="number" id="dp-input-koreksi-e" class="filter-select" min="0" step="1" placeholder="Otomatis sensor">
                </div>
                <div>
                    <label style="display:block; font-size:12px; color:#555; margin-bottom:4px;">(C) Cycle Time Standar — menit/pcs</label>
                    <input type="number" id="dp-input-koreksi-c" class="filter-select" min="0" step="0.0001" placeholder="Otomatis sensor">
                </div>
                <button type="button" class="btn-toggle-maintenance" onclick="simpanKoreksiRiwayat()">Hitung Ulang</button>
                <button type="button" class="btn-toggle-maintenance" onclick="batalKoreksiRiwayat()">Batal</button>
            </div>
        </div>

        <a id="dp-download-link" href="#" class="btn-simpan-keterangan" style="display:block; text-align:center; text-decoration:none; margin-top:18px;">Download Excel Sesi Ini</a>
    </div>
</div>
