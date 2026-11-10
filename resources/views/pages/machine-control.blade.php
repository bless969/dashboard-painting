<div id="view-machine-control" style="{{ $view_aktif !== 'machine-control' ? 'display:none;' : '' }}">
    <!-- PANEL 1: KONTROL PART & SUT (STANDARD UNIT TIME) ASLI PABRIK -->
    <div class="panel" style="border-top:4px solid #0d3b8c;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:14px; border-bottom:1.5px solid #eef2f6; padding-bottom:12px;">
            <div>
                <div class="panel-title" style="margin-bottom:2px; font-size:17px; display:flex; align-items:center; gap:8px;">
                    <span>⚙️</span>
                    <span>Pilih Part Produksi & Waktu Standar (SUT)</span>
                </div>
                <div style="font-size:12.5px; color:#64748b;">
                    Master katalog terhubung dengan <strong>95 Part Resmi Lini Painting</strong> dari lembar kerja Man-Time. Nilai SUT otomatis menentukan Cycle Time Standar (C).
                </div>
            </div>
            <div style="display:inline-flex; gap:8px; flex-wrap:wrap;">
                <button type="button" class="btn-nav-action btn-light" onclick="bukaModalMasterSut()" style="font-size:12.5px; padding:7px 14px; display:inline-flex; align-items:center; gap:6px; font-weight:700;">
                    <span>📋</span> Master SUT (95 Part)
                </button>
                <a href="/api/download-excel" class="btn-nav-action btn-green" style="font-size:12.5px; padding:7px 14px; display:inline-flex; align-items:center; gap:6px; font-weight:700; text-decoration:none;" title="Download template laporan bulanan resmi">
                    <span>📥</span> Unduh Excel (Format Pabrik)
                </a>
            </div>
        </div>

        <!-- FORM PILIH PART -->
        <div style="background:#f8fafc; border:1.5px solid #dbe4f3; border-radius:10px; padding:16px; margin-bottom:16px;">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:14px; align-items:end;">
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;">
                        Pilih Part yang Sedang Berjalan:
                    </label>
                    <select id="mc-select-part" class="filter-select" style="width:100%; font-size:13.5px; padding:8px 12px; font-weight:600; color:#0d3b8c; background:#fff;" onchange="gantiPartDariMC(this.value)">
                        @if(isset($parts_catalog_ai) && count($parts_catalog_ai) > 0)
                            @foreach($parts_catalog_ai as $p)
                                <option value="{{ $p['id'] }}" 
                                        data-sut="{{ $p['sut'] ?? 1.0 }}" 
                                        data-code="{{ $p['code'] }}" 
                                        data-target="{{ $p['target_per_hanger'] ?? 4 }}"
                                        {{ (isset($active_part_ai['id']) && $active_part_ai['id'] === $p['id']) ? 'selected' : '' }}>
                                    [{{ $p['code'] }}] SUT: {{ $p['sut'] ?? 1.0 }} min/pcs ({{ round(($p['sut'] ?? 1.0)*60) }}s) — {{ $p['name'] }}
                                </option>
                            @endforeach
                        @else
                            <option value="">Memuat katalog part...</option>
                        @endif
                    </select>
                </div>

                <!-- Live Info Card Part Terpilih -->
                <div style="background:#ffffff; border:1.5px solid #bfdbfe; border-radius:8px; padding:10px 14px; display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">(C) SUT Standar</div>
                        <div style="font-size:16px; font-weight:800; color:#0d3b8c;" id="mc-badge-sut">
                            {{ $active_part_ai['sut'] ?? '1.0' }} <span style="font-size:12px; font-weight:500;">menit/pcs</span>
                        </div>
                    </div>
                    <div style="border-left:1px solid #e2e8f0; padding-left:14px;">
                        <div style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Cycle Time (Detik)</div>
                        <div style="font-size:16px; font-weight:800; color:#16a34a;" id="mc-badge-ct-sec">
                            {{ round(($active_part_ai['sut'] ?? 1.0) * 60) }} <span style="font-size:12px; font-weight:500;">detik</span>
                        </div>
                    </div>
                    <div style="border-left:1px solid #e2e8f0; padding-left:14px;">
                        <div style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Target Pcs / Hanger</div>
                        <div style="font-size:16px; font-weight:800; color:#b45309;" id="mc-badge-target-hanger">
                            {{ $active_part_ai['target_per_hanger'] ?? 4 }} <span style="font-size:12px; font-weight:500;">pcs</span>
                        </div>
                    </div>
                    <div style="border-left:1px solid #e2e8f0; padding-left:14px;">
                        <div style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Kapasitas / Jam</div>
                        <div style="font-size:16px; font-weight:800; color:#475569;" id="mc-badge-capacity">
                            {{ ($active_part_ai['sut'] ?? 1.0) > 0 ? round(60.0 / ($active_part_ai['sut'] ?? 1.0), 1) : 0 }} <span style="font-size:12px; font-weight:500;">pcs/h</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATUS SESI PRODUKSI AKTIF -->
        <div id="mc-status-box" class="mc-status-box mc-status-idle" style="margin-bottom:16px;">
            <div class="mc-status-icon" id="mc-status-icon">⏸</div>
            <div class="mc-status-text">
                <div class="mc-status-judul" id="mc-status-judul">Sesi Produksi Aktif: <span id="mc-part-aktif-label">[{{ $active_part_ai['code'] ?? '-' }}] {{ $active_part_ai['name'] ?? '-' }}</span></div>
                <div class="mc-status-sub" id="mc-status-sub">Standar Shift: 485 menit | Manpower: 6 Operator (4 Tetap + 2 KWT) | Regulated Loss: 120 menit</div>
            </div>
            <div class="mc-live-timer" id="mc-live-timer" style="display:none;">00:00:00</div>
        </div>

        <!-- INPUT TERSEMBUNYI UNTUK KOMPATIBILITAS SKRIP LAMA -->
        <input type="hidden" id="perf-tanggal" value="{{ date('Y-m-d') }}">
        <input type="hidden" id="perf-jam-mulai" value="06:55">
        <input type="hidden" id="perf-jam-selesai" value="">
        <input type="hidden" id="perf-ct-pilihan" value="{{ $active_part_ai['sut'] ?? '1.0' }}">
        <span id="perf-part-label" style="display:none;">{{ $active_part_ai['code'] ?? 'M2SPCC0001IP' }}</span>

        <!-- GRID METRIK HASIL PRODUKSI & EFISIENSI (FORMAT STANDAR EXCEL) -->
        <div id="perf-hasil-wrapper">
            <div class="perf-hasil-grid" id="perf-hasil-grid">
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(B) Jam Kerja Shift</div>
                    <div class="perf-hasil-value" id="perf-val-B">485</div>
                    <div class="perf-hasil-desc">menit standar shift (Jumat: 440 mnt)</div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(C) SUT Standar</div>
                    <div class="perf-hasil-value" id="perf-val-C">{{ $active_part_ai['sut'] ?? '1.0' }}</div>
                    <div class="perf-hasil-desc">menit/pcs, dari master part SUT <span id="perf-val-C-manual-tag" style="display:none; color:#b45309; font-weight:700;">(manual)</span></div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(F) Good Output</div>
                    <div class="perf-hasil-value" id="perf-val-F">0</div>
                    <div class="perf-hasil-desc">pcs aktual terdeteksi kamera AI / sensor <span id="perf-val-F-manual-tag" style="display:none; color:#b45309; font-weight:700;">(manual)</span></div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(E) NG (Defect)</div>
                    <div class="perf-hasil-value" id="perf-val-E">0</div>
                    <div class="perf-hasil-desc">pcs part cacat/reject <span id="perf-val-E-manual-tag" style="display:none; color:#b45309; font-weight:700;">(manual)</span></div>
                </div>
                <div class="perf-hasil-card highlight">
                    <div class="perf-hasil-label">(D) Actual Cycle Time</div>
                    <div class="perf-hasil-value" id="perf-val-D">-</div>
                    <div class="perf-hasil-desc">menit/pcs, = (B) ÷ (F)</div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(P) Regulated Loss</div>
                    <div class="perf-hasil-value" id="perf-val-P">120</div>
                    <div class="perf-hasil-desc">menit (Meeting 30m, Break 60m, 5S 30m)</div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(I) Trouble Loss</div>
                    <div class="perf-hasil-value" id="perf-val-I">0</div>
                    <div class="perf-hasil-desc">menit waktu gangguan mesin/oven</div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(A) Total Loss (P+I)</div>
                    <div class="perf-hasil-value" id="perf-val-A">120</div>
                    <div class="perf-hasil-desc">menit total waktu henti</div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(H) Finish Good Ratio</div>
                    <div class="perf-hasil-value" id="perf-val-H">100.0%</div>
                    <div class="perf-hasil-desc">= (F) ÷ ((E) + (F))</div>
                </div>
                <div class="perf-hasil-card highlight">
                    <div class="perf-hasil-label">(J) Ability Operation</div>
                    <div class="perf-hasil-value" id="perf-val-J">-</div>
                    <div class="perf-hasil-desc">= ((C) × (F)) ÷ ((B) − (A))</div>
                </div>
                <div class="perf-hasil-card">
                    <div class="perf-hasil-label">(K) Time Ratio</div>
                    <div class="perf-hasil-value" id="perf-val-K">1.000</div>
                    <div class="perf-hasil-desc">= ((B) − (A)) ÷ ((B) − (P))</div>
                </div>
                <div class="perf-hasil-card highlight" style="background:#0d3b8c; color:white;">
                    <div class="perf-hasil-label" style="color:#bfdbfe;">(EFF) Efisiensi Lini</div>
                    <div class="perf-hasil-value" id="perf-val-L" style="color:#ffffff; font-size:26px;">-</div>
                    <div class="perf-hasil-desc" style="color:#e0e7ff;">Target Standar Pabrik: 95.2%</div>
                </div>
            </div>

            <!-- TOMBOL AKSI SIMPAN & KOREKSI -->
            <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <button type="button" class="btn-toggle-maintenance" id="btn-koreksi-fe" onclick="bukaFormKoreksiFE()" style="padding:10px 18px; font-weight:700;">
                    ✏️ Koreksi Manual F/E/C
                </button>
                <button type="button" class="btn-simpan-keterangan" id="btn-simpan-produksi" style="width:auto; padding:10px 22px; font-size:13.5px; font-weight:800; background:#16a34a;" onclick="simpanProduksiKeRiwayatMC()">
                    💾 Simpan Produksi ke Riwayat
                </button>
                <a href="/api/download-excel" class="btn-nav-action btn-green" style="padding:10px 18px; font-size:13px; font-weight:700; text-decoration:none;">
                    📥 Unduh Laporan Excel
                </a>
                <a href="{{ route('riwayat', ['line' => 'Painting']) }}" onclick="navigateTo(currentLine, 'riwayat'); return false;" class="btn-nav-action btn-light" style="padding:10px 18px; font-size:13px; font-weight:700;">
                    📂 Buka Tab Riwayat &rarr;
                </a>
            </div>

            <div class="settings-row-desc" id="simpan-produksi-status" style="margin-top:10px; display:none;"></div>

            <!-- FORM KOREKSI MANUAL -->
            <div id="form-koreksi-fe" style="display:none; margin-top:14px; padding:16px 18px; background:#f8fafc; border-radius:10px; border:1.5px solid #dbe4f3;">
                <div class="settings-row-desc" style="margin-bottom:10px; font-size:13px; color:#475569;">
                    Gunakan koreksi manual jika setelah pengecekan fisik di lapangan, jumlah (F) Good Output, (E) NG Defect, atau (C) SUT berbeda dari pembacaan sensor otomatis.
                </div>
                <div style="display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#555; margin-bottom:4px;">(F) Finished Good (pcs)</label>
                        <input type="number" id="input-koreksi-f" class="filter-select" min="0" step="1" placeholder="Otomatis sensor">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#555; margin-bottom:4px;">(E) NG / Defect (pcs)</label>
                        <input type="number" id="input-koreksi-e" class="filter-select" min="0" step="1" placeholder="0">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#555; margin-bottom:4px;">(C) SUT Standar (menit/pcs)</label>
                        <input type="number" id="input-koreksi-c" class="filter-select" min="0" step="0.0001" placeholder="Dari SUT part">
                    </div>
                    <button type="button" class="btn-toggle-maintenance" onclick="simpanKoreksiFE()" style="padding:8px 16px; font-weight:700;">Hitung Ulang</button>
                    <button type="button" class="btn-toggle-maintenance" onclick="batalKoreksiFE()" style="padding:8px 16px; background:#f1f5f9; color:#475569;">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- PANEL 2: RINGKASAN LOSS TIME (SESUAI FORMAT RESMI PABRIK) -->
    <div class="panel">
        <div class="panel-header-row">
            <div class="panel-title" id="alasan-ringkasan-title">Ringkasan Waktu Henti / Loss Time Lini Painting</div>
            <span class="filter-select" style="cursor:default;" id="alasan-ringkasan-line-label">Regulated (120m) &amp; Troubles</span>
        </div>
        <div class="settings-row-desc" id="alasan-ringkasan-desc" style="margin-bottom:16px;">
            Daftar waktu henti shift ini. Regulated Loss otomatis dialokasikan 120 menit (Meeting 30m, Istirahat 60m, 5S 30m).
        </div>

        <table class="tabel-alasan-stop" id="tabel-ringkasan-alasan-stop">
            <thead>
                <tr>
                    <th style="width:20%;">Kategori</th>
                    <th style="width:40%;">Alasan / Gangguan</th>
                    <th style="width:20%;">Indikator</th>
                    <th style="width:20%; text-align:right;">Durasi (Menit)</th>
                </tr>
            </thead>
            <tbody id="tbody-ringkasan-alasan-stop">
                <tr>
                    <td><span class="badge-shift" style="background:#dbeafe; color:#1e40af;">Regulated</span></td>
                    <td><strong>Meeting / Chokai Pagi</strong> (Briefing K3 &amp; Target)</td>
                    <td><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#3b82f6; margin-right:6px;"></span>Terjadwal</td>
                    <td style="text-align:right; font-weight:700;">30 mnt</td>
                </tr>
                <tr>
                    <td><span class="badge-shift" style="background:#dbeafe; color:#1e40af;">Regulated</span></td>
                    <td><strong>Break Time</strong> (Istirahat Siang &amp; Snack)</td>
                    <td><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#3b82f6; margin-right:6px;"></span>Terjadwal</td>
                    <td style="text-align:right; font-weight:700;">60 mnt</td>
                </tr>
                <tr>
                    <td><span class="badge-shift" style="background:#dbeafe; color:#1e40af;">Regulated</span></td>
                    <td><strong>Kebersihan (5S)</strong> (Booth Cat &amp; Area Oven)</td>
                    <td><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#3b82f6; margin-right:6px;"></span>Terjadwal</td>
                    <td style="text-align:right; font-weight:700;">30 mnt</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== MODAL MASTER SUT 95 PART PABRIK ===== -->
<div id="modal-master-sut" class="modal-overlay" style="display:none;" onclick="if(event.target.id === 'modal-master-sut') tutupModalMasterSut()">
    <div class="modal-box" style="max-width:820px; width:92%; max-height:88vh; display:flex; flex-direction:column; padding:22px 26px;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1.5px solid #e2e8f0; padding-bottom:14px; margin-bottom:14px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:24px;">📋</span>
                <div>
                    <h3 style="margin:0; font-size:17px; font-weight:800; color:#0d3b8c;">Master SUT (Standard Unit Time) — 95 Part Painting</h3>
                    <div style="font-size:12.5px; color:#64748b; margin-top:2px;">Diambil langsung dari lembar kerja resmi <code>08_MAN TIME_ PAINTING_AUG_26.xlsx</code></div>
                </div>
            </div>
            <button type="button" class="modal-close" onclick="tutupModalMasterSut()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#64748b;">&times;</button>
        </div>

        <!-- Filter Pencarian Cepat Part -->
        <div style="display:flex; gap:10px; margin-bottom:14px; align-items:center;">
            <input type="text" id="input-cari-sut" class="filter-select" style="flex:1; padding:8px 14px; font-size:13.5px;" placeholder="Ketik kode part... contoh: M2SPCC, L2RCVR, NNP..." oninput="filterTabelMasterSut(this.value)">
            <a href="/api/download-excel" class="btn-nav-action btn-green" style="padding:8px 16px; font-size:13px; font-weight:700; text-decoration:none; white-space:nowrap;">
                📥 Download Template Excel
            </a>
        </div>

        <div style="flex:1; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead style="position:sticky; top:0; background:#f8fafc; z-index:2; border-bottom:2px solid #cbd5e1;">
                    <tr>
                        <th style="padding:10px 14px; text-align:left; color:#475569;">No</th>
                        <th style="padding:10px 14px; text-align:left; color:#475569;">Kode Part</th>
                        <th style="padding:10px 14px; text-align:right; color:#475569;">SUT (Menit)</th>
                        <th style="padding:10px 14px; text-align:right; color:#475569;">Cycle Time (Detik)</th>
                        <th style="padding:10px 14px; text-align:right; color:#475569;">Target / Hanger</th>
                        <th style="padding:10px 14px; text-align:center; color:#475569;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-master-sut">
                    @if(isset($parts_catalog_ai))
                        @foreach($parts_catalog_ai as $idx => $p)
                            <tr style="border-bottom:1px solid #f1f5f9; transition:background 0.15s;" class="baris-sut-part" data-search="{{ strtolower($p['code']) }}">
                                <td style="padding:8px 14px; color:#64748b;">{{ $idx + 1 }}</td>
                                <td style="padding:8px 14px; font-weight:700; color:#0d3b8c; font-family:monospace;">{{ $p['code'] }}</td>
                                <td style="padding:8px 14px; text-align:right; font-weight:700; color:#1e293b;">{{ $p['sut'] ?? 1.0 }} mnt</td>
                                <td style="padding:8px 14px; text-align:right; color:#16a34a; font-weight:600;">{{ round(($p['sut'] ?? 1.0)*60) }}s</td>
                                <td style="padding:8px 14px; text-align:right; font-weight:600; color:#b45309;">{{ $p['target_per_hanger'] ?? 4 }} pcs</td>
                                <td style="padding:8px 14px; text-align:center;">
                                    <button type="button" class="btn-nav-action btn-light" style="padding:4px 10px; font-size:12px; font-weight:700;" onclick="pilihPartDariModalSut('{{ $p['id'] }}')">
                                        Pilih Part Ini
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:flex-end; margin-top:14px;">
            <button type="button" class="btn-secondary" onclick="tutupModalMasterSut()" style="padding:8px 20px; font-weight:700;">Tutup</button>
        </div>
    </div>
</div>
