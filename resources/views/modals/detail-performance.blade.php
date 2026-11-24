<!-- ===== MODAL: DETAIL SESI PRODUKSI (Riwayat Tabel Hasil Produksi) ===== -->
<div id="modal-detail-performance" class="modal-overlay" style="display:none;" onclick="tutupModalJikaDiLuarDetailPerformance(event)">
    <div class="modal-box" style="max-width:720px;">
        <div class="modal-header">
            <div class="panel-title">Detail Sesi Produksi — <span id="dp-part-label"></span></div>
            <button type="button" class="modal-close" onclick="tutupModalDetailPerformance()">&times;</button>
        </div>
        <div class="modal-ringkasan" style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
            <div class="ringkasan-chip">Sesi #<b id="dp-id"></b></div>
            <div class="ringkasan-chip">Jam Kerja: <b id="dp-jam"></b></div>
            <div class="ringkasan-chip">Disimpan: <b id="dp-disimpan"></b></div>
            <div class="ringkasan-chip" style="background:#eff6ff; border-color:#bfdbfe; color:#1e40af;">
                👤 PIC: <b id="dp-operator">Mukhamad Nawwaf</b> (<span id="dp-shift">Shift 1</span>)
            </div>
            <div class="ringkasan-chip" style="background:#f0fdf4; border-color:#bbf7d0; color:#166534;">
                👥 Total MP: <b id="dp-total-mp">6 Orang</b>
            </div>
        </div>

        <!-- Section Alokasi Posisi Manpower Sesi -->
        <div id="dp-posisi-container" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 14px; margin-bottom:14px;">
            <div style="font-size:11.5px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; display:flex; align-items:center; justify-content:space-between;">
                <span>Alokasi Posisi & Penanggung Jawab Sesi</span>
                <span id="dp-posisi-summary" style="font-size:11px; font-weight:600; color:#64748b;">Line Painting</span>
            </div>
            <div id="dp-posisi-list" style="display:flex; flex-wrap:wrap; gap:8px;">
                <!-- Diisi otomatis oleh JavaScript -->
            </div>
        </div>

        <!-- Section Detail Kualitas & Rincian Part Cacat (NG) Sesi Ini -->
        <div id="dp-ng-section" style="background:#ffffff; border:1px solid #fee2e2; border-radius:10px; padding:12px 14px; margin-bottom:14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <div style="font-size:12px; font-weight:700; color:#991b1b; text-transform:uppercase; letter-spacing:0.5px; display:flex; align-items:center; gap:6px;">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="#dc2626" stroke-width="2.2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <span>Rincian Part Cacat (NG) Sesi Ini</span>
                </div>
                <div id="dp-ng-summary-badge" style="font-size:11.5px; font-weight:700; padding:2px 8px; border-radius:6px; background:#fef2f2; color:#dc2626; border:1px solid #fecaca;">
                    0 pcs (0.0% Reject)
                </div>
            </div>

            <!-- Mini Summary Grid: Masuk, OK, NG, Yield -->
            <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:8px; margin-bottom:10px;">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:6px 10px; text-align:center;">
                    <div style="font-size:10.5px; color:#64748b; font-weight:600;">Barang Masuk</div>
                    <div id="dp-kpi-in" style="font-size:15px; font-weight:800; color:#1e3a8a;">0 pcs</div>
                </div>
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; padding:6px 10px; text-align:center;">
                    <div style="font-size:10.5px; color:#166534; font-weight:600;">Output (OK)</div>
                    <div id="dp-kpi-ok" style="font-size:15px; font-weight:800; color:#15803d;">0 pcs</div>
                </div>
                <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:6px 10px; text-align:center;">
                    <div style="font-size:10.5px; color:#991b1b; font-weight:600;">Cacat (NG)</div>
                    <div id="dp-kpi-ng" style="font-size:15px; font-weight:800; color:#dc2626;">0 pcs</div>
                </div>
                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; padding:6px 10px; text-align:center;">
                    <div style="font-size:10.5px; color:#1e40af; font-weight:600;">Yield (%)</div>
                    <div id="dp-kpi-yield" style="font-size:15px; font-weight:800; color:#2563eb;">100.0%</div>
                </div>
            </div>

            <!-- List / Table Detail Defect -->
            <div id="dp-ng-table-container">
                <div id="dp-ng-empty" style="font-size:12px; color:#059669; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; padding:8px 12px; text-align:center; font-weight:600;">
                    ✓ Zero Defect — Seluruh part lolos sortir dengan kualitas 100% OK.
                </div>
                <div id="dp-ng-list" style="display:none; overflow-x:auto;">
                    <table style="width:100%; font-size:12px; border-collapse:collapse; text-align:left;">
                        <thead>
                            <tr style="background:#f1f5f9; color:#475569;">
                                <th style="padding:6px 8px;">Jenis Cacat</th>
                                <th style="padding:6px 8px; text-align:center;">Jumlah</th>
                                <th style="padding:6px 8px;">Penanggung Jawab</th>
                                <th style="padding:6px 8px;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="dp-ng-tbody"></tbody>
                    </table>
                </div>
            </div>
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
