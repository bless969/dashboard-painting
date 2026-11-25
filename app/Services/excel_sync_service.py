"""
Service untuk Sinkronisasi Otomatis Riwayat Produksi ke File Excel Pabrik (excel_data.xlsx).
Membaca data dari storage/app/production_history.json & ng_logs.json,
lalu menyuntikkan ke sheet 'Riwayat Shift (Real-Time)', sheet 'Output', sheet 'Input Time', dan sheet 'NG'.
"""

import os
import sys
import json
import datetime
from openpyxl import load_workbook
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

BASE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), '../..'))
EXCEL_PATH = os.path.join(BASE_DIR, 'excel_data.xlsx')
HISTORY_JSON = os.path.join(BASE_DIR, 'storage', 'app', 'production_history.json')
NG_LOGS_JSON = os.path.join(BASE_DIR, 'storage', 'app', 'ng_logs.json')
ACTIVE_SHIFT_JSON = os.path.join(BASE_DIR, 'storage', 'app', 'active_shift.json')
HANGER_STATS_JSON = os.path.join(BASE_DIR, 'storage', 'app', 'hanger_stats.json')

def load_json(filepath, default=None):
    if default is None:
        default = []
    if os.path.exists(filepath):
        try:
            with open(filepath, 'r', encoding='utf-8') as f:
                return json.load(f)
        except Exception as e:
            print(f"Error loading {filepath}: {e}", file=sys.stderr)
    return default

def sync_excel():
    if not os.path.exists(EXCEL_PATH):
        print(f"Excel file not found at: {EXCEL_PATH}", file=sys.stderr)
        return False

    history = load_json(HISTORY_JSON, [])
    ng_logs = load_json(NG_LOGS_JSON, [])
    active_shift = load_json(ACTIVE_SHIFT_JSON, {})
    hanger_stats = load_json(HANGER_STATS_JSON, {})

    try:
        wb = load_workbook(EXCEL_PATH)
    except Exception as e:
        print(f"Failed to load workbook: {e}", file=sys.stderr)
        return False

    # =========================================================================
    # 1. Sheet 'Riwayat Shift (Real-Time)' - Rekap lengkap seluruh sesi produksi
    # =========================================================================
    sheet_name = 'Riwayat Shift'
    if sheet_name in wb.sheetnames:
        ws_rw = wb[sheet_name]
    else:
        # Sisipkan sebagai sheet kedua setelah summary
        ws_rw = wb.create_sheet(title=sheet_name, index=1)

    # Kosongkan konten sheet lama untuk re-render rapi
    ws_rw.delete_rows(1, ws_rw.max_row + 10)

    # Styles
    title_font = Font(name='Segoe UI', size=15, bold=True, color='1E3A8A')
    sub_font = Font(name='Segoe UI', size=9, italic=True, color='64748B')
    header_font = Font(name='Segoe UI', size=10, bold=True, color='FFFFFF')
    header_fill = PatternFill(start_color='1E40AF', end_color='1E40AF', fill_type='solid')
    data_font = Font(name='Segoe UI', size=9, color='0F172A')
    bold_data_font = Font(name='Segoe UI', size=9, bold=True, color='0F172A')
    row_alt_fill = PatternFill(start_color='F8FAFC', end_color='F8FAFC', fill_type='solid')
    ongoing_fill = PatternFill(start_color='FEF3C7', end_color='FEF3C7', fill_type='solid') # Kuning lembut

    thin_border = Border(
        left=Side(style='thin', color='E2E8F0'),
        right=Side(style='thin', color='E2E8F0'),
        top=Side(style='thin', color='E2E8F0'),
        bottom=Side(style='thin', color='E2E8F0')
    )

    # Title Block
    ws_rw.merge_cells('A1:Q1')
    ws_rw['A1'] = "REKAPITULASI RIWAYAT PRODUKSI & SHIFT — LINE PAINTING"
    ws_rw['A1'].font = title_font
    ws_rw['A1'].alignment = Alignment(vertical='center')
    ws_rw.row_dimensions[1].height = 28

    ws_rw.merge_cells('A2:Q2')
    ws_rw['A2'] = f"Update Otomatis Sistem IoT & Roboflow AI Vision | Terakhir disinkronkan: {datetime.datetime.now().strftime('%d-%m-%Y %H:%M:%S')} WIB"
    ws_rw['A2'].font = sub_font
    ws_rw['A2'].alignment = Alignment(vertical='center')
    ws_rw.row_dimensions[2].height = 18

    headers = [
        "No", "Tanggal", "Shift", "Jam Mulai", "Jam Selesai",
        "PIC Operator", "ID PIC", "Total MP", "Kode Part", "Nama Part",
        "Barang Masuk (IN)", "Output OK (PCS)", "Part Cacat (NG)",
        "Defect Rate (%)", "Yield Produksi (%)", "Eff Man Time (%)",
        "Status & Rincian Defect"
    ]

    ws_rw.row_dimensions[4].height = 24
    for col_idx, h in enumerate(headers, 1):
        cell = ws_rw.cell(row=4, column=col_idx, value=h)
        cell.font = header_font
        cell.fill = header_fill
        cell.alignment = Alignment(horizontal='center', vertical='center', wrap_text=True)
        cell.border = thin_border

    # Sort history terbaru di atas atau kronologis
    sorted_history = sorted(history, key=lambda x: (x.get('tanggal', ''), str(x.get('jam_mulai', ''))), reverse=True)

    current_row = 5
    for idx, item in enumerate(sorted_history, 1):
        tgl = item.get('tanggal', '')
        shift = item.get('shift', 'Shift 1')
        mulai = item.get('jam_mulai', '06:55')
        selesai = item.get('jam_selesai', '15:00')
        pic = item.get('operator_name', 'Mukhamad Nawwaf')
        pic_id = item.get('operator_id', 'OP-01')
        mp = item.get('total_manpower', 6)
        part_code = item.get('part_code', item.get('part', 'PART-001'))
        part_name = item.get('part_name', f"Part {part_code}")

        out_ok = int(item.get('nilai_f', 0))
        ng_pcs = int(item.get('nilai_e', 0))

        # Cari log NG yang relevan untuk sesi/tanggal ini
        defect_details = []
        for nl in ng_logs:
            if nl.get('tanggal') == tgl and (nl.get('part_code') == part_code or not nl.get('part_code')):
                defect_details.append(f"{nl.get('defect_type', 'NG')}: {nl.get('pcs', 1)} pcs")

        # Jika ada log tapi nilai_e nol
        if not ng_pcs and defect_details:
            ng_pcs = sum(int(nl.get('pcs', 1)) for nl in ng_logs if nl.get('tanggal') == tgl)

        total_in = out_ok + ng_pcs
        if total_in < int(item.get('total_input_hangers', 0)):
            # Jika dihitung dari hanger
            pass

        yield_pct = round((out_ok / total_in * 100), 1) if total_in > 0 else 100.0
        defect_pct = round((ng_pcs / total_in * 100), 1) if total_in > 0 else 0.0
        eff_pct = float(item.get('eff', 95.0))
        status_val = item.get('status', 'Final')

        defect_str = ", ".join(defect_details) if defect_details else "Nihil (0 defect)"
        status_str = f"[{status_val}] {defect_str}"

        row_data = [
            idx, tgl, shift, mulai, selesai,
            pic, pic_id, mp, part_code, part_name,
            total_in, out_ok, ng_pcs,
            f"{defect_pct}%", f"{yield_pct}%", f"{eff_pct}%",
            status_str
        ]

        ws_rw.row_dimensions[current_row].height = 20
        is_ongoing = (status_val.lower() == 'ongoing')

        for col_idx, val in enumerate(row_data, 1):
            cell = ws_rw.cell(row=current_row, column=col_idx, value=val)
            cell.font = bold_data_font if col_idx in [2, 11, 12, 13, 15] else data_font
            cell.border = thin_border

            if is_ongoing:
                cell.fill = ongoing_fill
            elif current_row % 2 == 0:
                cell.fill = row_alt_fill

            # Alignment
            if col_idx in [1, 2, 3, 4, 5, 7, 8]:
                cell.alignment = Alignment(horizontal='center', vertical='center')
            elif col_idx in [11, 12, 13]:
                cell.alignment = Alignment(horizontal='right', vertical='center')
            elif col_idx in [14, 15, 16]:
                cell.alignment = Alignment(horizontal='center', vertical='center')
            else:
                cell.alignment = Alignment(horizontal='left', vertical='center')

        current_row += 1

    # Auto-adjust column widths
    for col in ws_rw.columns:
        col_letter = get_column_letter(col[0].column)
        max_len = max(len(str(cell.value or '')) for cell in col)
        ws_rw.column_dimensions[col_letter].width = max(max_len + 3, 11)
    ws_rw.column_dimensions['A'].width = 6
    ws_rw.column_dimensions['B'].width = 13
    ws_rw.column_dimensions['J'].width = 20
    ws_rw.column_dimensions['Q'].width = 32

    # =========================================================================
    # 2. Sheet 'Output' - Matriks Output Shift Pabrik Asli
    # =========================================================================
    if 'Output' in wb.sheetnames:
        ws_out = wb['Output']
        # Cari atau cocokkan tanggal hari ini di row 4
        today_str = datetime.date.today().strftime('%Y-%m-%d')
        target_col = None

        for col_idx in range(3, min(ws_out.max_column + 5, 130)):
            val = str(ws_out.cell(row=4, column=col_idx).value or '')
            if today_str in val:
                target_col = col_idx
                break

        # Jika tanggal hari ini belum ada di header Output, pasang di kolom kosong pertama
        if not target_col:
            for col_idx in range(3, 130, 4):
                if not ws_out.cell(row=4, column=col_idx).value:
                    target_col = col_idx
                    ws_out.cell(row=3, column=col_idx, value=datetime.date.today().strftime('%a'))
                    ws_out.cell(row=4, column=col_idx, value=f"{today_str} 00:00:00")
                    ws_out.cell(row=5, column=col_idx, value="A")
                    ws_out.cell(row=5, column=col_idx+1, value="B")
                    ws_out.cell(row=5, column=col_idx+2, value="C")
                    ws_out.cell(row=5, column=col_idx+3, value="Total")
                    break

        if target_col:
            # Shift col offset: A=0, B=1, C=2
            cur_shift = active_shift.get('shift', 'Shift 1')
            shift_offset = 0
            if '2' in cur_shift or 'Sore' in cur_shift:
                shift_offset = 1
            elif '3' in cur_shift or 'Malam' in cur_shift:
                shift_offset = 2

            col_to_write = target_col + shift_offset
            active_part_code = active_shift.get('part_code', 'PART-001')
            out_pcs = int(hanger_stats.get('total_output_pcs', 0))

            # Cari row part code
            part_row = None
            for r in range(6, ws_out.max_row + 1):
                p_val = str(ws_out.cell(row=r, column=1).value or '').strip()
                if p_val and (p_val.lower() == active_part_code.lower() or active_part_code.lower() in p_val.lower()):
                    part_row = r
                    break

            if not part_row:
                part_row = ws_out.max_row + 1
                ws_out.cell(row=part_row, column=1, value=active_part_code)
                ws_out.cell(row=part_row, column=2, value=1.0) # Standard CT

            if out_pcs > 0:
                ws_out.cell(row=part_row, column=col_to_write, value=out_pcs)

    # =========================================================================
    # 3. Sheet 'NG' - Log Defect
    # =========================================================================
    if 'NG' in wb.sheetnames:
        ws_ng = wb['NG']
        # Isi log defect terbaru
        existing_ng_rows = ws_ng.max_row
        if existing_ng_rows < 5:
            existing_ng_rows = 5

        # Cari row kosong
        r = 6
        for nl in ng_logs[:15]:
            ws_ng.cell(row=r, column=2, value=nl.get('tanggal', ''))
            ws_ng.cell(row=r, column=3, value=nl.get('part_code', ''))
            ws_ng.cell(row=r, column=4, value=f"{nl.get('defect_type', '')} ({nl.get('pcs', 1)} pcs)")
            ws_ng.cell(row=r, column=5, value=nl.get('operator', ''))
            r += 1

    try:
        wb.save(EXCEL_PATH)
        print(f"Successfully synced production history to {EXCEL_PATH}")

        # Simpan juga ke template path jika ada agar konsisten di semua rute export
        template_copy = os.path.join(BASE_DIR, 'storage', 'app', 'templates', 'MAN_TIME_PAINTING_TEMPLATE.xlsx')
        if os.path.exists(os.path.dirname(template_copy)):
            try:
                wb.save(template_copy)
            except Exception:
                pass
        return True
    except Exception as e:
        print(f"Error saving workbook: {e}", file=sys.stderr)
        return False

if __name__ == '__main__':
    success = sync_excel()
    sys.exit(0 if success else 1)
