<div class="panel choose-part-panel" style="background:#ffffff; border:1.5px solid #dbe4f3; border-radius:12px; padding:18px 20px; box-shadow:0 3px 12px rgba(13,59,140,0.05);">
    
    <!-- Header: Detail Keterangan Part yang Diproduksi -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:36px; height:36px; border-radius:8px; background:#eff6ff; display:flex; align-items:center; justify-content:center; color:#0d3b8c; font-size:19px; border:1.5px solid #bfdbfe;">
                🏷️
            </div>
            <div>
                <h3 style="margin:0; font-size:16px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    Detail Part Yang Sedang Diproduksi
                    <span style="font-size:11px; font-weight:700; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:12px;">Vision AI</span>
                </h3>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                    Fokus part terpilih yang dipantau & dihitung oleh Kamera Vision AI (Roboflow)
                </div>
            </div>
        </div>

        <div style="display:flex; gap:8px; align-items:center;">
            <a href="{{ url('/ai-vision?line=' . ($line_aktif ?? 'Painting')) }}" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; padding:7px 14px; background:#0d3b8c; color:white; border-radius:6px; font-weight:700; font-size:12.5px; transition:background 0.2s;" title="Buka Layar Kamera Vision AI">
                📹 Kamera Vision AI &rarr;
            </a>
        </div>
    </div>

    @if(!empty($active_part_ai))
        <!-- Card Detail Part Aktif -->
        <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; padding:14px 16px; margin-bottom:14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="background:#0d3b8c; color:white; font-size:13px; font-weight:800; padding:3px 10px; border-radius:6px; font-family:monospace;">
                        {{ $active_part_ai['code'] ?? 'P-01' }}
                    </span>
                    <span style="font-size:17px; font-weight:800; color:#0f172a;">
                        {{ $active_part_ai['name'] }}
                    </span>
                </div>
                <span style="font-size:12px; font-weight:700; background:#dcfce7; color:#15803d; padding:3px 10px; border-radius:12px; display:inline-flex; align-items:center; gap:5px;">
                    <span style="width:7px; height:7px; border-radius:50%; background:#22c55e;"></span>
                    Aktif Diproduksi
                </span>
            </div>

            <!-- Grid 3 Keterangan Detail Part -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:10px;">
                <!-- Keterangan 1: Model Roboflow -->
                <div style="background:#ffffff; padding:10px 12px; border-radius:8px; border:1px solid #cbd5e1;">
                    <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:2px;">Model Roboflow AI</div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span style="font-size:13.5px; font-weight:800; color:#0d3b8c; font-family:monospace;">
                            {{ $active_part_ai['model_id'] }}
                        </span>
                        <span style="font-size:11px; font-weight:800; background:#dbeafe; color:#1d4ed8; padding:1px 6px; border-radius:4px; font-family:monospace;">
                            v{{ $active_part_ai['model_version'] }}
                        </span>
                    </div>
                </div>

                <!-- Keterangan 2: Target per Hanger -->
                <div style="background:#ffffff; padding:10px 12px; border-radius:8px; border:1px solid #cbd5e1;">
                    <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:2px;">Target Pcs / Hanger</div>
                    <div style="font-size:14px; font-weight:800; color:#16a34a;">
                        {{ $active_part_ai['target_per_hanger'] ?? 4 }} pcs / hanger
                    </div>
                </div>

                <!-- Keterangan 3: Identifikasi Sensor -->
                <div style="background:#ffffff; padding:10px 12px; border-radius:8px; border:1px solid #cbd5e1;">
                    <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:2px;">Deteksi Konveyor</div>
                    <div style="font-size:12.5px; font-weight:700; color:#334155;">
                        Photosensor + Kamera Output
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian Pilih / Ganti Part untuk Vision AI (Quick Changeover) -->
        <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:12px 14px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div style="font-size:12.5px; color:#0369a1; font-weight:700;">
                🔄 Ganti Part Produksi (Changeover):
            </div>
            <div style="display:flex; gap:8px; align-items:center; flex:1; max-width:440px; min-width:260px;">
                <select id="select-change-part-dashboard" class="filter-select" style="flex:1; padding:7px 10px; font-size:13px; font-weight:700; border:1.5px solid #0284c7; border-radius:6px; background:#ffffff;">
                    @foreach($parts_catalog_ai as $p)
                        <option value="{{ $p['id'] }}" {{ ($active_part_ai['id'] ?? '') === $p['id'] ? 'selected' : '' }}>
                            [{{ $p['code'] }}] {{ $p['name'] }}{{ ($active_part_ai['id'] ?? '') === $p['id'] ? ' (Sedang Berjalan)' : '' }}
                        </option>
                    @endforeach
                </select>
                <button type="button" id="btn-change-part-dashboard" onclick="gantiPartDariDashboard()" style="padding:7px 14px; background:#0284c7; color:white; border:none; border-radius:6px; font-weight:700; font-size:12.5px; cursor:pointer; white-space:nowrap;">
                    Terapkan
                </button>
            </div>
        </div>

    @else
        <!-- Tampilan Ketika Belum Ada Part Terdaftar di Katalog -->
        <div style="padding:20px; background:#fef2f2; border:1.5px solid #fecaca; border-radius:10px; color:#dc2626; text-align:center;">
            <div style="font-size:15px; font-weight:800; margin-bottom:4px;">⚠️ Belum Ada Part yang Dipilih untuk Produksi</div>
            <div style="font-size:12.5px; color:#64748b; max-width:460px; margin:0 auto 12px; line-height:1.5;">
                Katalog saat ini belum memiliki data part hasil training Roboflow. Silakan buka halaman <strong>Kamera Vision AI</strong> untuk mendaftarkan model part baru.
            </div>
            <a href="{{ url('/ai-vision?line=' . ($line_aktif ?? 'Painting')) }}" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; padding:8px 18px; background:#0d3b8c; color:white; border-radius:6px; font-weight:700; font-size:13px;">
                ➕ Buka AI Vision & Daftarkan Part
            </a>
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
