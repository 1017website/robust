# Generator Panduan Pengguna (PDF)

Perangkat untuk membuat ulang [Panduan-Pengguna-ROBUST-CRM.pdf](../Panduan-Pengguna-ROBUST-CRM.pdf)
secara otomatis: login sebagai setiap role, ambil screenshot tiap menu, lalu susun menjadi PDF.

Jalankan ulang setiap kali tampilan aplikasi berubah agar isi panduan tetap sesuai.

## Kebutuhan

- Node.js 18 atau lebih baru
- Python 3 dengan Pillow (`pip install pillow`)
- Microsoft Edge (dipakai sebagai browser headless — tidak perlu mengunduh browser lain)

## Langkah

```bash
# 1. Siapkan data demo agar screenshot tidak menampilkan tabel kosong
php artisan db:seed --class=DemoDataSeeder

# 2. Jalankan aplikasi pada port 8123
php artisan serve --port=8123

# 3. Dari folder ini: pasang dependency sekali saja
npm init -y && npm install playwright-core

# 4. Ambil screenshot seluruh role (hasil di folder shots/)
node capture.js

# 5. Perkecil gambar untuk PDF (hasil di folder img/)
python optimize.py

# 6. Susun HTML lalu cetak ke PDF
node build.js && node topdf.js
```

Batasi ke satu role saja saat menguji: `node capture.js sales`.

## Isi berkas

| Berkas | Fungsi |
|---|---|
| `pages.js` | Daftar halaman yang di-screenshot per role, ID data contoh, dan akun tiap role. |
| `capture.js` | Login per role lalu ambil screenshot seluruh halaman pada `pages.js`. |
| `optimize.py` | Memperkecil PNG menjadi JPEG lebar 1500px untuk menekan ukuran PDF. |
| `content-1.js` | Naskah panduan untuk Administrator. |
| `content-2.js` | Naskah panduan untuk Sales. |
| `content-3.js` | Naskah panduan untuk SPV Sales, Drafter, Produksi, QC, Delivery, Administration. |
| `build.js` | Menggabungkan naskah dan gambar menjadi `guide.html`. |
| `topdf.js` | Mencetak `guide.html` menjadi PDF A4 bernomor halaman. |

## Menyesuaikan isi

- **Menambah halaman baru:** tambahkan barisnya pada `pages.js`, lalu tulis naskahnya pada
  `content-*.js` memakai key yang sama.
- **Format naskah:** `f` = fungsi halaman, `s` = daftar langkah, `k` = tabel kolom isian
  `[nama, wajib, penjelasan]`, `n` = kotak catatan penting.
- **Halaman bertab** (misalnya Workspace Project): tambahkan selector tab sebagai elemen kelima,
  contoh `'[data-bs-target="#operations"]'`.

## Catatan

ID data contoh pada `pages.js` menunjuk ke record hasil `DemoDataSeeder`. Bila database di-reset,
periksa ulang ID-nya:

```bash
php artisan tinker --execute="echo App\Models\Quotation::where('code','Q-DEMO-07')->value('id');"
```
