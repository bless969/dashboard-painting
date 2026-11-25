<div class="panel choose-part-panel panel-choose-part" style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:14px; padding:18px 20px; box-shadow:0 3px 12px rgba(13,59,140,0.05); display:flex; flex-direction:column; justify-content:space-between; height:100%; margin-bottom:0;">
    
    <!-- Header: Detail Keterangan Part yang Diproduksi -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:36px; height:36px; border-radius:8px; background:#eff6ff; display:flex; align-items:center; justify-content:center; color:#0d3b8c; font-size:18px; border:1.5px solid #bfdbfe;">
                🏷️
            </div>
            <div>
                <h3 style="margin:0; font-size:15.5px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    Detail Part Sedang Diproduksi
                    <span style="font-size:11px; font-weight:700; background:#e0e7ff; color:#3730a3; padding:2px 8px; border-radius:12px;">Vision AI</span>
                </h3>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                    Part terpilih yang dipantau & dihitung otomatis oleh Kamera AI
                </div>
            </div>
        </div>

        <div>
            <a href="{{ url('/ai-vision?line=' . ($line_aktif ?? 'Painting')) }}" onclick="navigateTo(currentLine, 'ai-vision'); return false;" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; padding:7px 14px; background:#0d3b8c; color:white; border-radius:8px; font-weight:700; font-size:12px; transition:all 0.2s; box-shadow:0 2px 6px rgba(13,59,140,0.25);" title="Buka Layar Kamera Vision AI">
                📹 Kamera Vision AI &rarr;
            </a>
        </div>
    </div>

    @if(!empty($active_part_ai))
        <!-- Body: Spesifikasi Part Rapi & Proporsional (Tanpa Nested Boxes Bertingkat) -->
        <div style="flex:1; display:flex; flex-direction:column; justify-content:center; padding:4px 0;">
            <!-- Hero Part Name & Status -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <span id="choose-part-code" style="background:#0d3b8c; color:white; font-size:13px; font-weight:800; padding:4px 10px; border-radius:6px; letter-spacing:0.3px;">
                        {{ $active_part_ai['code'] ?? 'P-01' }}
                    </span>
                    <span id="choose-part-name" style="font-size:19px; font-weight:800; color:#0f172a; letter-spacing:-0.2px;">
                        {{ $active_part_ai['name'] }}
                    </span>
                </div>
                <span style="font-size:11.5px; font-weight:700; background:#dcfce7; color:#15803d; padding:4px 10px; border-radius:12px; display:inline-flex; align-items:center; gap:6px; border:1px solid #bbf7d0;">
                    <span style="width:7px; height:7px; border-radius:50%; background:#22c55e;"></span>
                    Aktif Diproduksi
                </span>
            </div>

            <!-- 3 Clean Specs Chips -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px; margin-bottom:14px;">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px;">
                    <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:3px;">Target / Hanger</div>
                    <div id="choose-part-target" style="font-size:14px; font-weight:800; color:#16a34a;">
                        {{ $active_part_ai['target_per_hanger'] ?? 4 }} pcs / hanger
                    </div>
                </div>

                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px;">
                    <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:3px;">Model AI Offline</div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span id="choose-part-model-id" style="font-size:13px; font-weight:800; color:#0d3b8c;">
                            {{ $active_part_ai['model_file'] ?? $active_part_ai['model_id'] }}
                        </span>
                        <span id="choose-part-model-version" style="font-size:10.5px; font-weight:800; background:#dbeafe; color:#1d4ed8; padding:1px 5px; border-radius:4px;">
                            v{{ $active_part_ai['model_version'] }}
                        </span>
                    </div>
                </div>

                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px;">
                    <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:3px;">Deteksi Konveyor</div>
                    <div style="font-size:12.5px; font-weight:700; color:#334155;">
                        Photosensor + Kamera
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer: Ganti Part (Changeover) Rapi & Seimbang -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
            <div style="font-size:12px; color:#475569; font-weight:700; display:flex; align-items:center; gap:6px;">
                <span>🔄 Changeover Part:</span>
            </div>
            <div style="display:flex; gap:8px; align-items:center; flex:1; max-width:440px; min-width:240px;">
                <select id="select-change-part-dashboard" class="filter-select" style="flex:1; padding:6px 10px; font-size:12.5px; font-weight:700; border:1.5px solid #0d3b8c; border-radius:6px; background:#ffffff; color:#0f172a;">
                    @foreach($parts_catalog_ai as $p)
                        <option value="{{ $p['id'] }}" {{ ($active_part_ai['id'] ?? '') === $p['id'] ? 'selected' : '' }}>
                            [{{ $p['code'] }}] {{ $p['name'] }}{{ ($active_part_ai['id'] ?? '') === $p['id'] ? ' (Sedang Berjalan)' : '' }}
                        </option>
                    @endforeach
                </select>
                <button type="button" id="btn-change-part-dashboard" onclick="gantiPartDariDashboard()" style="padding:6px 14px; background:#0d3b8c; color:white; border:none; border-radius:6px; font-weight:700; font-size:12px; cursor:pointer; white-space:nowrap; transition:all 0.2s;">
                    Terapkan
                </button>
            </div>
        </div>

    @else
        <!-- Tampilan Ketika Belum Ada Part Terdaftar di Katalog -->
        <div style="padding:16px 20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; text-align:center; flex:1; display:flex; flex-direction:column; justify-content:center; align-items:center; margin-bottom:10px;">
            <div style="font-size:14.5px; font-weight:800; color:#0f172a; margin-bottom:4px;">Belum Ada Part Aktif Terpilih</div>
            <div style="font-size:12px; color:#64748b; max-width:440px; margin:0 auto; line-height:1.5;">
                Daftarkan part baru atau pilih part hasil training dari halaman AI Vision untuk memulai pemantauan.
            </div>
        </div>

        <!-- Footer: Bar Symmetrical dengan Card Kiri -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
            <div style="font-size:12px; color:#475569; font-weight:700;">
                <span>📦 Status Katalog: 0 Part Terdaftar</span>
            </div>
            <div>
                <a href="{{ url('/ai-vision?line=' . ($line_aktif ?? 'Painting')) }}" onclick="navigateTo(currentLine, 'ai-vision'); return false;" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; padding:5px 12px; background:#0d3b8c; color:white; border-radius:6px; font-weight:700; font-size:11.5px; box-shadow:0 2px 4px rgba(13,59,140,0.2);">
                    ➕ Kelola Part di AI Vision
                </a>
            </div>
        </div>
    @endif

    <!-- State & input kompatibilitas untuk background scripts -->
    <input type="hidden" id="perf-tanggal">
    <input type="hidden" id="perf-shift">
    <input type="hidden" id="perf-operator">
    <input type="hidden" id="perf-c-detik">
    <input type="hidden" id="perf-pcs-per-sheet" value="{{ $active_part_ai['target_per_hanger'] ?? 4 }}">
    <input type="hidden" id="ct-part-search" value="{{ $active_part_ai['name'] ?? '' }}">
    <select id="ct-part-select" style="display:none;">
        <option value="{{ $active_part_ai['name'] ?? '' }}" selected>{{ $active_part_ai['name'] ?? '' }}</option>
    </select>
    <span id="ct-detail-part" style="display:none;">{{ $active_part_ai['name'] ?? '' }}</span>
    <div id="ct-detail-box" style="display:none;"><div id="ct-detail-values"></div></div>
    <div id="ct-detail-empty" style="display:none;"></div>
</div>

<script>
    async function gantiPartDariDashboard() {
        const sel = document.getElementById('select-change-part-dashboard');
        if (!sel || !sel.value) return;
        const newPartId = sel.value;
        const partText = sel.options[sel.selectedIndex].text;

        if (!confirm(`Konfirmasi pergantian part produksi ke:\n\n${partText}\n\nPergantian part akan otomatis mereset counter untuk part baru dan mengaktifkan model Roboflow di Kamera Vision AI. Lanjutkan?`)) {
            return;
        }

        const btn = document.getElementById('btn-change-part-dashboard');
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'Mengganti...';
        }

        try {
            const res = await fetch('/api/change-part', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ part_id: newPartId })
            });
            const data = await res.json();
            if (data && data.status === 'ok') {
                alert(`✅ Berhasil ganti part ke:\n${data.active_part.name}\n\nKamera Vision AI & Dashboard telah disinkronkan.`);
                location.reload();
            } else {
                alert('Gagal mengganti part: ' + ((data && data.message) ? data.message : 'Error'));
            }
        } catch (e) {
            alert('Gagal mengganti part: ' + e.message);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerText = 'Terapkan';
            }
        }
    }
</script>
