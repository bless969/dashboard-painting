<!-- ===== KONTROL PRODUKSI PART: Part Aktif ===== -->
<div class="panel panel-choose-part">
    <div class="choose-part-header" style="margin-bottom:14px;">
        <div class="panel-title-large" style="font-size:18px;">Part Aktif</div>
        <a href="{{ url('/ai-vision?line=' . ($line_aktif ?? 'Painting')) }}" onclick="navigateTo(currentLine, 'ai-vision'); return false;" class="btn-manage-part" style="text-decoration:none; font-size:13.5px; padding:7px 14px;" title="Buka AI Vision &amp; Katalog Part">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
            AI Vision &amp; Kamera
        </a>
    </div>

    @if(!empty($active_part_ai))
        <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
            <div class="part-hero-row">
                <span id="choose-part-code" class="ct-detail-part-large" style="font-size:15.5px; padding:4px 12px;">
                    {{ $active_part_ai['code'] ?? 'P-01' }}
                </span>
                <span id="choose-part-name" style="font-size:21px; font-weight:800; color:#0f172a; letter-spacing:-0.2px;">
                    {{ $active_part_ai['name'] }}
                </span>
                <span id="mr-nama-part" style="display:none;">[{{ $active_part_ai['code'] ?? 'P-01' }}] {{ $active_part_ai['name'] }}</span>
            </div>

            <div class="ct-detail-values" style="margin-top:0; margin-bottom:14px;">
                <div class="ct-stat-card ct-stat-secondary">
                    <div class="ct-stat-label" style="font-size:12px;">Target / Hanger</div>
                    <div class="ct-stat-value" id="choose-part-target" style="font-size:20px; color:#15803d;">
                        {{ $active_part_ai['target_per_hanger'] ?? 4 }} pcs / hanger
                    </div>
                </div>

                <div class="ct-stat-card ct-stat-primary">
                    <div class="ct-stat-label" style="font-size:12px;">Model AI</div>
                    <div class="ct-stat-value" style="font-size:16.5px; color:#0d3b8c; word-break:break-word;">
                        <span id="choose-part-model-id">{{ $active_part_ai['model_file'] ?? $active_part_ai['model_id'] }}</span>
                        <span id="choose-part-model-version" style="font-size:12px; font-weight:800; background:#dbeafe; color:#1d4ed8; padding:2px 8px; border-radius:5px;">
                            v{{ $active_part_ai['model_version'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="part-changeover-bar">
            <label for="select-change-part-dashboard">Ganti Part</label>
            <select id="select-change-part-dashboard" class="filter-select">
                @foreach($parts_catalog_ai as $p)
                    <option value="{{ $p['id'] }}" {{ ($active_part_ai['id'] ?? '') === $p['id'] ? 'selected' : '' }}>
                        [{{ $p['code'] }}] {{ $p['name'] }}{{ ($active_part_ai['id'] ?? '') === $p['id'] ? ' (Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
            <button type="button" id="btn-change-part-dashboard" onclick="gantiPartDariDashboard()" class="btn-nav-action btn-blue" style="height:38px; padding:0 18px; border:none; font-size:13.5px;">
                Terapkan
            </button>
        </div>
    @else
        <div class="ct-detail-empty" style="margin-bottom:14px; font-size:14px;">
            Belum ada part yang dipilih. Silakan tambahkan atau pilih part di halaman AI Vision.
            <span id="mr-nama-part" style="display:none;">-</span>
        </div>
        <div>
            <a href="{{ url('/ai-vision?line=' . ($line_aktif ?? 'Painting')) }}" onclick="navigateTo(currentLine, 'ai-vision'); return false;" class="btn-nav-action btn-blue" style="font-size:13.5px; padding:9px 16px;">
                Kelola Part di AI Vision
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

        if (!confirm(`Konfirmasi pergantian part produksi ke:\n\n${partText}\n\nCounter sesi akan direset dan model AI akan disinkronkan. Lanjutkan?`)) {
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
                alert(`Berhasil ganti part ke ${data.active_part.name}.`);
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
