<div id="view-dashboard" style="{{ $view_aktif !== 'dashboard' ? 'display:none;' : '' }}">
    <!-- ===== TOP CONTROL BAR (Grid 2 Kolom: Status Mesin & Choose Part) ===== -->
    <div class="dashboard-top-grid">
        <!-- Kolom Kiri: Status Mesin (Tower Light) -->
        <div class="panel tower-light-panel">
            <div class="tower-light-visual">
                <div class="tower-lamp lamp-merah" id="lamp-merah"></div>
                <div class="tower-lamp lamp-kuning" id="lamp-kuning"></div>
                <div class="tower-lamp lamp-hijau" id="lamp-hijau"></div>
            </div>
            <div class="tower-light-info">
                <div class="tower-light-label">Status Mesin</div>
                <div class="tower-light-status" id="teks-status-mesin">Memuat...</div>
                <div class="tower-light-updated" id="teks-update-status">-</div>
                <div class="tower-light-warning" id="stop-alasan-warning" style="display:none;" onclick="bukaModalAlasanStop()">
                    <span class="pulse-dot-warning"></span>
                    <span id="stop-alasan-warning-teks">Belum sempat isi alasan stop? Klik untuk isi alasan stop</span>
                </div>
                <!-- Tombol maintenance jika mesin dalam mode maintenance -->
                <button type="button" class="btn-toggle-maintenance" id="btn-toggle-maintenance" style="display:none;" onclick="toggleMaintenanceManual()">
                    ✅ Selesai Maintenance
                </button>
            </div>
        </div>

        <!-- Kolom Kanan: Kontrol Produksi & Pilih Part -->
        @include('partials.choose-part')
    </div>

    <!-- ===== KARTU METRIK PRODUKSI (6 KARTU SEJAJAR: PCS & SHEET) ===== -->
    <div class="metric-grid-6">
        <!-- PCS: Input Produksi -->
        <div class="metric-card input">
            <div class="metric-card-header">
                <div class="icon">IN</div>
                <div class="metric-tanggal-label" id="metric-tanggal-label">Hari Ini</div>
            </div>
            <div class="value skeleton" id="input-value">0</div>
            <div class="label">Input Produksi</div>
        </div>

        <!-- PCS: Output Produksi -->
        <div class="metric-card output">
            <div class="metric-card-header">
                <div class="icon">OUT</div>
                <div class="metric-tanggal-label"></div>
            </div>
            <div class="value skeleton" id="output-value">0</div>
            <div class="label">Output Produksi</div>
        </div>

        <!-- PCS: Yield -->
        <div class="metric-card yield">
            <div class="metric-card-header">
                <div class="icon">%</div>
                <div class="metric-tanggal-label"></div>
            </div>
            <div class="value skeleton" id="yield-value">0</div>
            <div class="label">Yield Produksi</div>
        </div>

        <!-- SHEET: Sheet In -->
        <div class="metric-card sheet-input">
            <div class="metric-card-header">
                <div class="icon">SHT IN</div>
                <div class="metric-tanggal-label"></div>
            </div>
            <div class="value skeleton" id="sheet-input-value">0</div>
            <div class="label">Sheet In</div>
        </div>

        <!-- SHEET: Sheet Out -->
        <div class="metric-card sheet-output">
            <div class="metric-card-header">
                <div class="icon">SHT OUT</div>
                <div class="metric-tanggal-label"></div>
            </div>
            <div class="value skeleton" id="sheet-output-value">0</div>
            <div class="label">Sheet Out</div>
        </div>

        <!-- SHEET: Sheet Yield -->
        <div class="metric-card sheet-yield">
            <div class="metric-card-header">
                <div class="icon">SHT %</div>
                <div class="metric-tanggal-label"></div>
            </div>
            <div class="value skeleton" id="sheet-yield-value">0</div>
            <div class="label">Sheet Yield</div>
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
        </div>
    </div>
</div>
