<div id="view-riwayat" style="{{ $view_aktif !== 'riwayat' ? 'display:none;' : '' }}">
    <!-- Opsi Bar: Pilihan Antara Riwayat Produksi vs Riwayat Tabel Hasil Produksi -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
        <div class="riwayat-opsi-bar" style="margin-bottom:0;">
            <button type="button" class="riwayat-opsi-btn aktif" id="opsi-produksi" onclick="pilihTabRiwayat('produksi')">
                <svg viewBox="0 0 24 24" width="15" height="15"><path d="M3 3v18h18" stroke="currentColor" stroke-width="2" fill="none"/><path d="M7 14l4-4 4 2 5-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                Riwayat Produksi
            </button>
            <button type="button" class="riwayat-opsi-btn" id="opsi-mc" onclick="pilihTabRiwayat('mc')">
                <svg viewBox="0 0 24 24" width="15" height="15"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 8v4l3 2" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                Riwayat Tabel Hasil Produksi
            </button>
        </div>
        <a href="/api/download-excel" class="btn-nav-action btn-green" style="padding:9px 16px; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px;" title="Unduh file laporan bulanan resmi sesuai format 08_MAN TIME_ PAINTING_AUG_26.xlsx">
            <span>📥</span> Download Excel (Format Pabrik Asli)
        </a>
    </div>

    <!-- ===== TAB: RIWAYAT PRODUKSI ===== -->
    <div id="riwayat-tab-produksi">
        <!-- Panel Pencarian Sesi Produksi & Filter Tanggal -->
        <div class="riwayat-search-panel">
            <div class="panel-header-row" style="margin-bottom:12px;">
                <div class="panel-title" style="display:flex; align-items:center; gap:8px;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#0d3b8c" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <span>Cari Sesi Produksi Berdasarkan Tanggal</span>
                </div>
                <div class="riwayat-search-badges" style="font-size:12.5px; color:#6b6b80;">
                    Menampilkan: <strong id="rw-label-tanggal-aktif" style="color:#0d3b8c;">Semua Tanggal</strong>
                </div>
            </div>

            <div class="riwayat-search-bar">
                <div class="riwayat-date-input-box">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="#0d3b8c" fill="none" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <input type="date" id="filter-tanggal-produksi" class="riwayat-date-input" title="Pilih tanggal sesi produksi" onchange="cariSesiProduksiTanggal(this.value)">
                </div>

                <button type="button" class="btn-cari-sesi-produksi" id="btn-cari-sesi" onclick="cariSesiProduksiTanggal()">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Cari Sesi
                </button>

                <div class="btn-group-quick-date" style="display:inline-flex; gap:6px; flex-wrap:wrap;">
                    <button type="button" class="riwayat-quick-btn" onclick="setTanggalProduksiCari('hari_ini')">Hari Ini</button>
                    <button type="button" class="riwayat-quick-btn" onclick="setTanggalProduksiCari('kemarin')">Kemarin</button>
                    <button type="button" class="riwayat-quick-btn reset" onclick="resetTanggalProduksiCari()">Semua Data</button>
                </div>
            </div>

            <!-- Kontainer Hasil Sesi Produksi Tanggal Terpilih -->
            <div id="rw-hasil-sesi-container" class="riwayat-sesi-box" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <div style="font-size:14px; font-weight:700; color:#2b2b3a; display:flex; align-items:center; gap:8px;">
                        <span>Daftar Sesi Produksi — <span id="rw-sesi-tgl-label" style="color:#0d3b8c;">-</span></span>
                        <span id="rw-sesi-count-badge" class="badge-shift badge-shift-malam" style="font-size:11px;">0 sesi</span>
                    </div>
                    <div id="rw-sesi-summary-stats" style="font-size:12.5px; color:#6b6b80;">
                        Rata-rata EFF: <strong id="rw-sesi-rata-eff" style="color:#0d3b8c;">0%</strong>
                    </div>
                </div>
                <div id="rw-daftar-sesi-list" class="riwayat-ph-list"></div>
                <div id="rw-sesi-kosong" style="display:none; color:#9a9ab0; text-align:center; padding:24px 0; font-size:13px; background:#f8fafc; border-radius:12px; border:1px dashed #dbe4f3;">
                    Belum ada sesi produksi yang tercatat pada tanggal ini.
                </div>
            </div>
        </div>

        <div class="riwayat-summary">
            <div class="summary-chip">
                <span class="chip-label">Total Input</span>
                <span class="chip-value" id="rw-total-input">-</span>
            </div>
            <div class="summary-chip">
                <span class="chip-label">Total Output</span>
                <span class="chip-value" id="rw-total-output">-</span>
            </div>
            <div class="summary-chip highlight">
                <span class="chip-label">Yield Keseluruhan</span>
                <span class="chip-value" id="rw-yield">-</span>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header-row" style="margin-bottom:12px;">
                <div class="panel-title">Histori Data Produksi</div>
                <div style="font-size:12px; color:#9a9ab0;">Klik tombol <strong>Lihat Detail</strong> untuk memilih 3 sesi shift (Pagi, Sore, Malam)</div>
            </div>
            <table id="tabel-histori">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th>Tanggal</th>
                        <th>Total Input</th>
                        <th>Total Output</th>
                        <th>EFF Rata-rata</th>
                        <th>Detail Sesi Shift</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="isi-tabel-histori"></tbody>
            </table>
        </div>
    </div>

    <!-- ===== TAB: RIWAYAT TABEL HASIL PRODUKSI ===== -->
    <div id="riwayat-tab-mc" style="display:none;">
        <div class="riwayat-summary">
            <div class="summary-chip">
                <span class="chip-label">Jumlah Perhitungan</span>
                <span class="chip-value" id="ph-jumlah">-</span>
            </div>
            <div class="summary-chip highlight">
                <span class="chip-label">Rata-rata EFF</span>
                <span class="chip-value" id="ph-rata-eff">-</span>
            </div>
        </div>

        <div class="panel" id="panel-riwayat-performance">
            <div class="panel-header-row">
                <div class="panel-title">Riwayat Tabel Hasil Produksi (Jam Kerja) — <span id="ph-tgl-label" style="color:#0d3b8c;">Hari Ini</span></div>
                <div class="panel-controls">
                    <div class="date-picker-wrapper" style="display:inline-flex; align-items:center; gap:6px;">
                        <button type="button" class="riwayat-quick-btn" onclick="setTanggalPh('hari_ini')" style="padding:6px 12px; font-size:12px;">Hari Ini</button>
                        <button type="button" class="riwayat-quick-btn" onclick="setTanggalPh('kemarin')" style="padding:6px 12px; font-size:12px;">Kemarin</button>
                        <button type="button" class="filter-select" id="btn-tanggal-ph" onclick="bukaPickerPerformance()">
                            <svg viewBox="0 0 24 24" width="14" height="14" style="stroke:currentColor;fill:none;stroke-width:2;vertical-align:-2px;margin-right:6px;"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <span id="teks-tanggal-ph">Hari Ini</span>
                        </button>
                        <input type="date" id="filter-tanggal-ph" onchange="loadRiwayatPerformance()" style="position:absolute; opacity:0; pointer-events:none; width:1px; height:1px;">
                    </div>
                </div>
            </div>
            <div class="settings-row-desc" style="margin-bottom:14px;">
                Setiap sesi produksi part di Machine Control (dari mesin mulai <strong>Running</strong> sampai benar-benar berhenti) otomatis tercatat sebagai satu sesi di sini. Klik <strong>Lihat Detail</strong> untuk melihat rincian hasil kontrol produksinya, atau <strong>Download Excel</strong> untuk mengunduh sesi tersebut.
            </div>
            <div id="ph-kosong" style="display:none; color:#9a9ab0; text-align:center; padding:24px 0; font-size:13px;">
                Belum ada riwayat kalkulasi untuk tanggal ini.
            </div>
            <div id="daftar-riwayat-ph" class="riwayat-ph-list"></div>
        </div>
    </div>
</div>
