import os
import json
from PIL import Image

SRC = 'shots'
DST = 'img'
MAX_W = 1500
MAX_H = 5200  # potong halaman yang terlalu panjang agar tetap terbaca di PDF

os.makedirs(DST, exist_ok=True)
meta = {}
total_before = total_after = 0

for name in sorted(os.listdir(SRC)):
    if not name.endswith('.png'):
        continue
    src = os.path.join(SRC, name)
    total_before += os.path.getsize(src)

    im = Image.open(src).convert('RGB')
    w, h = im.size
    if w > MAX_W:
        h = round(h * MAX_W / w)
        im = im.resize((MAX_W, h), Image.LANCZOS)
    cropped = False
    if h > MAX_H:
        im = im.crop((0, 0, im.size[0], MAX_H))
        cropped = True

    out = os.path.join(DST, name.replace('.png', '.jpg'))
    im.save(out, 'JPEG', quality=82, optimize=True, progressive=True)
    total_after += os.path.getsize(out)
    meta[name.replace('.png', '')] = {'file': os.path.basename(out), 'w': im.size[0], 'h': im.size[1], 'cropped': cropped}

json.dump(meta, open('img-meta.json', 'w'), indent=2)
print(f'{len(meta)} gambar')
print(f'sebelum: {total_before/1024/1024:.1f} MB  ->  sesudah: {total_after/1024/1024:.1f} MB')
print('dipotong (terlalu panjang):', [k for k, v in meta.items() if v['cropped']])
