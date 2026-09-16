<!-- ===== MODAL: MANAGE PART (CYCLE TIME & PCS PER SHEET) ===== -->
<div id="modal-manage-part" class="modal-overlay" style="display:none;" onclick="tutupModalJikaDiLuarManagePart(event)">
    <div class="modal-box modal-box-large">
        <div class="modal-header">
            <div>
                <div class="panel-title" style="margin-bottom:2px; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:20px;">⚙️</span> Manage Part
                    <span class="mp-count-badge" id="mp-count-badge">0 Part</span>
                </div>
                <div class="settings-row-desc" style="margin-top:0;">
                    Atur nilai Cycle Time Standar (menit) dan jumlah Pcs per Sheet untuk part baru maupun part lama.
                </div>
            </div>
            <button type="button" class="modal-close" onclick="tutupModalManagePart()">&times;</button>
        </div>

        <!-- Alert Notifikasi Modal -->
        <div id="mp-alert" class="mp-alert" style="display:none;"></div>

        <!-- Form Tambah / Edit Part -->
        <div class="mp-form-card" id="mp-form-card">
            <div class="mp-form-header">
                <span class="mp-form-title" id="mp-form-title">➕ Tambah Part Baru</span>
                <span class="mp-form-subtitle" id="mp-form-subtitle">Lengkapi kode part, cycle time standar, dan jumlah pcs per sheet</span>
            </div>

            <form id="form-manage-part" onsubmit="simpanPartDariModal(event)">
                <input type="hidden" id="mp-part-id" value="">
                <div class="mp-form-grid">
                    <div class="mp-form-group">
                        <label for="mp-kode-part" class="mp-label">Kode / Nama Part <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="mp-kode-part" class="filter-select mp-input" placeholder="Contoh: LCA5DSC2" autocomplete="off" required>
                        <span class="mp-input-hint">Kombinasi huruf/angka kode part</span>
                    </div>

                    <div class="mp-form-group">
                        <label for="mp-cycle-time" class="mp-label">CT Standar (Menit) <span style="color:#dc2626;">*</span></label>
                        <input type="number" id="mp-cycle-time" class="filter-select mp-input" step="0.0001" min="0" placeholder="Contoh: 0.039" required oninput="updateCtDetikHint(this.value)">
                        <span class="mp-input-hint" id="mp-ct-hint">= 0 detik / pcs</span>
                    </div>

                    <div class="mp-form-group">
                        <label for="mp-pcs-sheet" class="mp-label">Pcs per Sheet <span style="color:#dc2626;">*</span></label>
                        <input type="number" id="mp-pcs-sheet" class="filter-select mp-input" step="1" min="1" value="1" placeholder="1" required>
                        <span class="mp-input-hint">Unit dihasilkan per 1 panel/sheet</span>
                    </div>
                </div>

                <div class="mp-form-actions">
                    <button type="submit" id="btn-mp-simpan" class="btn-mp-primary">
                        <span id="btn-mp-icon">➕</span> <span id="btn-mp-text">Simpan Part Baru</span>
                    </button>
                    <button type="button" id="btn-mp-batal" class="btn-mp-secondary" style="display:none;" onclick="batalEditPart()">
                        Batal
                    </button>
                </div>
            </form>
        </div>

        <!-- Header Tabel & Filter Pencarian -->
        <div class="mp-table-controls">
            <div class="mp-search-wrap">
                <input type="text" id="mp-search-input" class="filter-select mp-search-input"
                       placeholder="Cari part di tabel... (mis. LCA24)" autocomplete="off"
                       oninput="filterTabelManagePart(this.value)">
                <span class="mp-search-icon">🔍</span>
            </div>
            <button type="button" class="btn-mp-add-shortcut" onclick="fokusTambahPart()">
                ➕ Tambah Baru
            </button>
        </div>

        <!-- Tabel Daftar Part -->
        <div class="mp-table-wrapper">
            <table class="mp-table">
                <thead>
                    <tr>
                        <th style="width:46px; text-align:center;">No</th>
                        <th>Kode Part</th>
                        <th style="width:160px;">CT Standar</th>
                        <th style="width:140px; text-align:center;">Pcs per Sheet</th>
                        <th style="width:120px; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="mp-table-body">
                    <tr>
                        <td colspan="5" style="text-align:center; padding:24px; color:#9a9ab0;">
                            Memuat daftar part...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
