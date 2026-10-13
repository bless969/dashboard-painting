<div id="view-dashboard" style="{{ $view_aktif !== 'dashboard' ? 'display:none;' : '' }}">
    <!-- ===== TOP CONTROL BAR (Grid 2 Kolom: Status Mesin & Choose Part) ===== -->
    <div class="dashboard-top-grid">
        <!-- Kolom Kiri: Status Mesin (Tower Light) -->
        <div class="panel tower-light-panel" id="tower-light-panel-card">
            <div class="tower-light-visual" id="tower-light-visual">
                <div class="tower-lamp lamp-merah" id="lamp-merah"></div>
                <div class="tower-lamp lamp-kuning" id="lamp-kuning"></div>
                <div class="tower-lamp lamp-hijau" id="lamp-hijau"></div>
            </div>
            <div class="tower-light-info">
                <div class="tower-light-label">Status Mesin Painting</div>
                <div class="tower-light-status" id="teks-status-mesin">Memuat...</div>
                <div class="tower-light-updated" id="teks-update-status">-</div>
                <div class="tower-light-warning" id="stop-alasan-warning" style="display:none;" onclick="bukaModalAlasanStop()">
                    <span class="pulse-dot-warning"></span>
                    <span id="stop-alasan-warning-teks">Belum sempat isi alasan stop? Klik untuk isi alasan stop</span>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Kontrol Produksi & Pilih Part -->
        @include('partials.choose-part')
    </div>

    <!-- ===== KARTU METRIK PRODUKSI: 3 ATAS (PCS) & 3 BAWAH (HANGER / WADAH) ===== -->
    <div class="metrics-split-container">
        <!-- BARIS 1 (ATAS): METRIK PRODUKSI UNIT (PCS) -->
        <div class="metric-section-group">
            <div class="metric-group-header">
                <div class="metric-group-badge badge-pcs">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>
                    <span>METRIK PRODUKSI (PCS)</span>
                </div>
                <div class="metric-mode-pill-group">
                    <button type="button" class="metric-mode-btn active" id="btn-mode-shift" onclick="setDashboardModeProduksi('shift')">⏱️ Shift Aktif</button>
                    <button type="button" class="metric-mode-btn" id="btn-mode-harian" onclick="setDashboardModeProduksi('harian')">📅 Total 1 Hari</button>
                </div>
            </div>
            <div class="metric-grid-3">
                <!-- PCS: Input Produksi -->
                <div class="metric-card input">
                    <div class="metric-card-header">
                        <div class="icon">IN</div>
                        <div class="metric-tanggal-label" id="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="input-value">0</div>
                    <div class="label">Input Produksi <span class="metric-unit-tag">PCS</span></div>
                </div>

                <!-- PCS: Output Produksi -->
                <div class="metric-card output">
                    <div class="metric-card-header">
                        <div class="icon">OUT</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="output-value">0</div>
                    <div class="label">Output Produksi <span class="metric-unit-tag">PCS</span></div>
                </div>

                <!-- PCS: Yield -->
                <div class="metric-card yield">
                    <div class="metric-card-header">
                        <div class="icon">%</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="yield-value">0</div>
                    <div class="label">Yield Produksi <span class="metric-unit-tag">%</span></div>
                </div>
            </div>
        </div>

        <!-- BARIS 2 (BAWAH): METRIK PRODUKSI MATERIAL / HANGER -->
        <div class="metric-section-group">
            <div class="metric-group-header">
                <div class="metric-group-badge badge-sheet">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>METRIK HANGER / WADAH</span>
                </div>
            </div>
            <div class="metric-grid-3">
                <!-- Hanger: Total Hanger Lewat -->
                <div class="metric-card sheet-input">
                    <div class="metric-card-header">
                        <div class="icon">HNG TOT</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="sheet-input-value">0</div>
                    <div class="label">Total Hanger <span class="metric-unit-tag sheet">HANGER</span></div>
                </div>

                <!-- Hanger: Hanger Terisi Wadah -->
                <div class="metric-card sheet-output">
                    <div class="metric-card-header">
                        <div class="icon">HNG OK</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="sheet-output-value">0</div>
                    <div class="label">Hanger Terisi Wadah <span class="metric-unit-tag sheet">ADA WADAH</span></div>
                </div>

                <!-- Hanger: Hanger Kosong (Tanpa Wadah) -->
                <div class="metric-card sheet-yield">
                    <div class="metric-card-header">
                        <div class="icon">HNG 0</div>
                        <div class="metric-tanggal-label">Hari Ini</div>
                    </div>
                    <div class="value skeleton" id="sheet-yield-value">0</div>
                    <div class="label">Hanger Kosong <span class="metric-unit-tag sheet">TANPA WADAH</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MACHINE RATIO: Sesi Part Aktif ===== -->
    <div class="panel panel-machine-ratio">
        <div id="mr-tidak-ada-sesi" class="mr-empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <p>Belum ada sesi produksi part yang sedang berjalan.</p>
            <span>Mulai sesi lewat halaman <strong>Machine Control</strong> dulu — begitu mesin Running dan part dipilih, angka di sini otomatis muncul dan ter-update tiap beberapa detik.</span>
        </div>

        <div id="mr-konten" style="display:none;">
            <div class="mr-header-part">
                <div class="mr-header-left">
                    <span class="mr-header-label">Part Aktif</span>
                    <span class="mr-header-value" id="mr-nama-part">-</span>
                    <span class="mr-header-jam" id="mr-jam-mulai">Mulai —</span>
                </div>
                <a href="{{ route('machine-control', ['line' => $line_aktif]) }}" class="btn-mr-detail" onclick="navigateTo(currentLine, 'machine-control'); return false;">Detail Machine Control &rarr;</a>
            </div>

            <div class="mr-grid">
                <div class="mr-card mr-card-wide">
                    <div class="mr-card-label">Total Product</div>
                    <div class="mr-card-value mr-card-dark" id="mr-total-product">- PCS</div>
                </div>
                <div class="mr-card mr-card-wide">
                    <div class="mr-card-label">OK Product</div>
                    <div class="mr-card-value mr-card-dark" id="mr-ok-product">- PCS</div>
                </div>
                <div class="mr-card mr-card-wide">
                    <div class="mr-card-label">NG Ratio</div>
                    <div class="mr-card-value mr-card-red" id="mr-ng-ratio">-%</div>
                </div>
                <div class="mr-card mr-card-wide">
                    <div class="mr-card-label">NG Product</div>
                    <div class="mr-card-value mr-card-dark" id="mr-ng-product">- PCS</div>
                </div>

                <div class="mr-card mr-card-highlight">
                    <div class="mr-card-label">Product Rate <span class="mr-card-sub">(H)</span></div>
                    <div class="mr-card-value mr-card-yellow" id="mr-product-rate">-%</div>
                </div>
                <div class="mr-card mr-card-highlight">
                    <div class="mr-card-label">Machine Rate <span class="mr-card-sub">(Working Time %)</span></div>
                    <div class="mr-card-value mr-card-dark" id="mr-machine-rate">-%</div>
                </div>

                <div class="mr-card">
                    <div class="mr-card-label">Product Time <span class="mr-card-sub">(B)</span></div>
                    <div class="mr-card-value mr-card-dark" id="mr-product-time">-</div>
                </div>
                <div class="mr-card">
                    <div class="mr-card-label">Run Time <span class="mr-card-sub">(Shift)</span></div>
                    <div class="mr-card-value mr-card-dark" id="mr-run-time">-</div>
                </div>
                <div class="mr-card">
                    <div class="mr-card-label">Planned <span class="mr-card-sub">(P)</span></div>
                    <div class="mr-card-value mr-card-dark" id="mr-waiting-loss">-</div>
                </div>
                <div class="mr-card">
                    <div class="mr-card-label">Loss Time <span class="mr-card-sub">(I)</span></div>
                    <div class="mr-card-value mr-card-dark" id="mr-machine-trouble">-</div>
                </div>
            </div>

            <a href="{{ route('machine-control', ['line' => $line_aktif]) }}" class="btn-lihat-detail-lain" style="display:inline-block; text-decoration:none; text-align:center;" onclick="navigateTo(currentLine, 'machine-control'); return false;">Lihat detail rumus lengkap di Machine Control &rarr;</a>
        </div>
    </div>
</div>
