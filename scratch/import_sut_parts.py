import zipfile
import xml.etree.ElementTree as ET
import json
import re

z = zipfile.ZipFile('excel_data.xlsx')
sst = [t.text or '' for t in ET.fromstring(z.read('xl/sharedStrings.xml')).iter('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t')]
tree = ET.fromstring(z.read('xl/worksheets/sheet5.xml'))

extracted = []
for r in tree.iter('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row'):
    cell_dict = {}
    for c in r.iter('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}c'):
        ref = c.get('r')
        col = ''.join([ch for ch in ref if ch.isalpha()])
        t = c.get('t')
        v = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}v')
        val = ''
        if v is not None and v.text:
            val = sst[int(v.text)] if t == 's' else v.text
        cell_dict[col] = val
    
    item = cell_dict.get('C', '').strip()
    sut_str = cell_dict.get('E', '').strip()
    if item and item != 'Item' and not item.startswith('Working') and not item.startswith('Pasuruan') and not item.startswith('Bogor'):
        try:
            sut = float(sut_str) if sut_str else 1.0
        except:
            sut = 1.0
        extracted.append({'code': item, 'sut': round(sut, 4)})

# Remove duplicates while preserving order
seen = set()
unique_parts = []
for p in extracted:
    if p['code'] not in seen:
        seen.add(p['code'])
        unique_parts.append(p)

print(f"Extracted {len(unique_parts)} unique parts.")

# Format as catalog items
catalog = []
for i, p in enumerate(unique_parts):
    code = p['code']
    sut = p['sut']
    
    # Assign reasonable target per hanger based on SUT (Cycle time)
    if sut >= 6.0:
        target_hanger = 1
    elif sut >= 2.0:
        target_hanger = 2
    elif sut >= 0.8:
        target_hanger = 4
    elif sut >= 0.3:
        target_hanger = 6
    else:
        target_hanger = 8

    part_id = 'part_' + re.sub(r'[^a-z0-9_]', '_', code.lower())
    catalog.append({
        'id': part_id,
        'code': code,
        'name': f"Part {code}",
        'sut': sut,
        'ct_seconds': round(sut * 60, 2),
        'per_hour': round(60.0 / sut, 1) if sut > 0 else 0,
        'target_per_hanger': target_hanger,
        'model_id': 'hanger-object-detection',
        'model_version': 1,
        'api_key': 'rf_wY80fQsM8OTq0l6HnhFVRI4pjUb2',
        'publishable_key': 'rf_wY80fQsM8OTq0l6HnhFVRI4pjUb2'
    })

with open('storage/app/parts_catalog.json', 'w') as f:
    json.dump(catalog, f, indent=4)

# Set active part to the most produced one: M2SPCC0001IP or the first one
active_id = catalog[0]['id']
for c in catalog:
    if c['code'] == 'M2SPCC0001IP':
        active_id = c['id']
        break

with open('storage/app/active_part.json', 'w') as f:
    f.write(active_id)

print(f"Successfully wrote {len(catalog)} parts to storage/app/parts_catalog.json!")
print(f"Active part set to: {active_id}")

