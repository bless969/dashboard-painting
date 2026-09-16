<div id="view-machine-control" style="{{ $view_aktif !== 'machine-control' ? 'display:none;' : '' }}">
    <div class="panel">
        <div class="panel-title">Card Tabel Hasil Produksi</div>

        <!-- Kotak info nilai C -->
        <div id="mc-nilai-c-box" style="display:none; margin-bottom:16px; padding:12px 16px; background:#eef3ff; border-radius:10px; border:1.5px solid #b3c6f7; align-items:center; gap:12px;">
            <span style="font-size:22px;">&#9989;</span>
            <div>
                <div style="font-size:13px; color:#555; margin-bottom:2px;">(C) Cycle Time Standar yang dipakai:</div>
                <div style="font-size:17px; font-weight:700; color:#0d3b8c;"><span id="mc-nilai-c-angka">-</span> <span style="font-weight:400; font-size:13px;">menit/pcs</span></div>
                <div style="font-size:12px; color:#777; margin-top:2px;">Part: <span id="mc-nilai-c-part">-</span></div>
            </div>
            <button type="button" class="btn-nav-action btn-light" style="margin-left:auto; font-size:12px; padding:6px 12px;" onclick="navigateTo(currentLine, 'dashboard'); setTimeout(() => document.getElementById('ct-part-search')?.focus(), 50);">Ganti Part &rarr;</button>
        </div>
        <div id="mc-nilai-c-kosong" style="margin-bottom:16px; padding:12px 16px; background:#fff8e1; border-radius:10px; border:1.5px solid #ffe082; display:flex; align-items:center; gap:12px; cursor:pointer;" onclick="navigateTo(currentLine, 'dashboard'); setTimeout(() => document.getElementById('ct-part-search')?.focus(), 50);" title="Klik untuk memilih part di Dashboard">
            <span style="font-size:20px;">&#9888;</span>
            <div style="font-size:13px; color:#7a5800; font-weight:600;">Pilih part dulu sebelum mesin di-running. Nilai (C) belum tersedia. <span style="text-decoration:underline;">Klik untuk pilih part &rarr;</span></div>
        </div>

        <div class="settings-row-desc" style="margin-bottom:16px;">
            <strong>Jam Produksi</strong> tercatat otomatis mengikuti awal shift (sesi pertama) atau begitu part dipilih,
            dan berakhir saat "Selesai Produksi" ditekan -- durasinya (dalam menit) itulah nilai (B) di bawah.
            Kalau mesin berhenti sebentar karena alasan P/Loss, jam produksi tetap berjalan (tidak berhenti/reset).
        </div>

        <div id="mc-status-box" class="mc-status-box mc-status-idle">
            <div class="mc-status-icon" id="mc-status-icon">⏸</div>
            <div class="mc-status-text">
                <div class="mc-status-judul" id="mc-status-judul">Belum ada sesi produksi</div>
                <div class="mc-status-sub" id="mc-status-sub">Pilih part di atas untuk memulai sesi produksi.</div>
            </div>
            <div class="mc-live-timer" id="mc-live-timer" style="display:none;">00:00:00</div>
        </div>

        <div id="perf-hasil-kosong" class="ct-detail-empty">Belum ada sesi produksi. Mulai mesin dan pilih part untuk melihat hasil perhitungan live.</div>

        <div id="perf-hasil-wrapper" style="display:none;">
            <div class="perf-hasil-grid" id="perf-hasil-grid">
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(B) Jam Produksi</div>
                    <div class="perf-hasil-value" id="perf-val-B">-</div>
                    <div class="perf-hasil-desc">menit, dari awal shift (sesi 1) / part dipilih s/d Selesai Produksi, utuh tanpa dikurangi</div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(C) Cycle Time Standar</div>
                    <div class="perf-hasil-value" id="perf-val-C">-</div>
                    <div class="perf-hasil-desc">menit/pcs, dari nilai standar daftar part yang dipilih <span id="perf-val-C-manual-tag" style="display:none; color:#b45309; font-weight:700;">(dikoreksi manual)</span></div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(F) Finished Good</div>
                    <div class="perf-hasil-value" id="perf-val-F">-</div>
                    <div class="perf-hasil-desc">pcs terdeteksi sensor output <span id="perf-val-F-manual-tag" style="display:none; color:#b45309; font-weight:700;">(dikoreksi manual)</span></div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(E) NG (Reject)</div>
                    <div class="perf-hasil-value" id="perf-val-E">-</div>
                    <div class="perf-hasil-desc">pcs gagal terdeteksi sensor output <span id="perf-val-E-manual-tag" style="display:none; color:#b45309; font-weight:700;">(dikoreksi manual)</span></div>
                </div>
                <div class="perf-hasil-card highlight">
                    <div class="perf-hasil-label">(D) Actual Cycle Time</div>
                    <div class="perf-hasil-value" id="perf-val-D">-</div>
                    <div class="perf-hasil-desc">menit/pcs, = (B) ÷ (F)</div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(A) Total Stop (P + I)</div>
                    <div class="perf-hasil-value" id="perf-val-A">-</div>
                    <div class="perf-hasil-desc">menit, = (P) + (I)</div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(I) Total Stop Loss</div>
                    <div class="perf-hasil-value" id="perf-val-I">-</div>
                    <div class="perf-hasil-desc">menit, khusus kategori Loss</div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(P) Total Stop Planned</div>
                    <div class="perf-hasil-value" id="perf-val-P">-</div>
                    <div class="perf-hasil-desc">menit, khusus kategori Planned</div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(H) Finish Good Ratio</div>
                    <div class="perf-hasil-value" id="perf-val-H">-</div>
                    <div class="perf-hasil-desc">= (F) ÷ ((E) + (F))</div>
                </div>
                <div class="perf-hasil-card highlight">
                    <div class="perf-hasil-label">(J) Ability Operation</div>
                    <div class="perf-hasil-value" id="perf-val-J">-</div>
                    <div class="perf-hasil-desc">= ((C) × (F)) ÷ ((B) − (A))</div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(K) Operation Time Ratio</div>
                    <div class="perf-hasil-value" id="perf-val-K">-</div>
                    <div class="perf-hasil-desc">= ((B) − (A)) ÷ ((B) − (P))</div>
                </div>
                <div class="perf-hasil-card highlight">
                    <div class="perf-hasil-label">(EFF)</div>
                    <div class="perf-hasil-value" id="perf-val-L">-</div>
                    <div class="perf-hasil-desc">= (H) × (J) × (K), (H) desimal saat dikalikan lalu hasilnya di-%</div>
                </div>
            </div>

            <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
                <button type="button" class="btn-toggle-maintenance" id="btn-koreksi-fe" onclick="bukaFormKoreksiFE()">✏️ Koreksi F/E/C</button>
                <button type="button" class="btn-simpan-keterangan" id="btn-simpan-produksi" style="width:auto; padding:10px 18px;" onclick="simpanProduksiFinal()">💾 Simpan Produksi</button>
            </div>
            <div class="settings-row-desc" id="simpan-produksi-status" style="margin-top:6px; display:none;"></div>

            <div id="form-koreksi-fe" style="display:none; margin-top:12px; padding:14px 16px; background:#f8fafc; border-radius:10px; border:1.5px solid #dbe4f3;">
                <div class="settings-row-desc" style="margin-bottom:10px;">
                    Koreksi manual dipakai kalau setelah pengecekan fisik, jumlah (F) Finished Good, (E) NG,
                    atau (C) Cycle Time Standar ternyata beda dari yang terdeteksi/dihitung otomatis dari
                    sensor. Nilai lain (H, J, K, EFF) akan otomatis dihitung ulang begitu disimpan.
                    Kosongkan field untuk kembali pakai nilai otomatis.
                </div>
                <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                    <div>
                        <label style="display:block; font-size:12px; color:#555; margin-bottom:4px;">(F) Finished Good — pcs</label>
                        <input type="number" id="input-koreksi-f" class="filter-select" min="0" step="1" placeholder="Otomatis sensor">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; color:#555; margin-bottom:4px;">(E) NG (Reject) — pcs</label>
                        <input type="number" id="input-koreksi-e" class="filter-select" min="0" step="1" placeholder="Otomatis sensor">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; color:#555; margin-bottom:4px;">(C) Cycle Time Standar — menit/pcs</label>
                        <input type="number" id="input-koreksi-c" class="filter-select" min="0" step="0.0001" placeholder="Dari daftar part (isi manual jika ingin override)">
                    </div>
                    <button type="button" class="btn-toggle-maintenance" onclick="simpanKoreksiFE()">Hitung Ulang</button>
                    <button type="button" class="btn-toggle-maintenance" onclick="batalKoreksiFE()">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header-row">
            <div class="panel-title">Ringkasan Alasan Stop Hari Ini</div>
            <span class="filter-select" style="cursor:default;" id="alasan-ringkasan-line-label">-</span>
        </div>
        <div class="settings-row-desc" style="margin-bottom:16px;">Total waktu berhenti pada line ini hari ini, dikelompokkan berdasarkan kategori dan alasan.</div>

        <table class="tabel-alasan-stop" id="tabel-ringkasan-alasan-stop">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Alasan</th>
                    <th>Warna Patlite</th>
                    <th>Durasi</th>
                </tr>
            </thead>
            <tbody id="tbody-ringkasan-alasan-stop">
                <tr><td colspan="4" class="ct-detail-empty">Memuat...</td></tr>
            </tbody>
        </table>
    </div>
</div>
