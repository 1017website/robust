# ROBUST Sales CRM

Sales CRM berbasis **Laravel 12 + MySQL** untuk perusahaan Laboratory Furniture & Equipment.
Frontend menggunakan Bootstrap 5 via CDN (tanpa build step), cocok untuk shared hosting / cPanel.

## Role
- **Sales Admin** — kelola pra lead, distribusi ke sales, monitoring workload & acceptance rate
- **Sales** — request masuk, lead, design request, quotation (wizard 4 langkah), customer, project
- **Drafter / Produksi** — kerjakan design request, input biaya & item hasil, submit final ke sales

## Alur Bisnis
Pra Lead (Admin) → Request Masuk (Sales terima) → Lead → Design Request (ke Drafter) →
Drafter submit item & biaya → Quotation (Sales) → Won → Project.

## Setup

```bash
# 1. Install dependency
composer install

# 2. Salin environment & generate key
cp .env.example .env
php artisan key:generate

# 3. Atur koneksi database di .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD)

# 4. Migrasi + data demo
php artisan migrate --seed

# 5. Symlink storage (untuk file dokumen)
php artisan storage:link

# 6. Jalankan
php artisan serve
```

> Frontend memakai CDN, **tidak perlu** `npm install` / `npm run build`.

## Akun Demo (password: `password`)
| Role        | Email               |
|-------------|---------------------|
| Sales Admin | admin@robust.test   |
| Sales       | sales@robust.test   |
| Sales 2     | sales2@robust.test  |
| Drafter     | drafter@robust.test |

## Deploy ke cPanel (tanpa SSH)
1. Upload seluruh isi project ke folder di luar `public_html` (mis. `~/robust-crm`).
2. Pindahkan isi folder `public/` ke `public_html`, lalu sesuaikan path di `index.php`:
   - `require __DIR__.'/../robust-crm/vendor/autoload.php';`
   - `$app = require_once __DIR__.'/../robust-crm/bootstrap/app.php';`
3. Buat database via cPanel MySQL, isi kredensial di `.env`.
4. Jalankan migrasi via terminal cPanel atau gunakan paket "artisan runner" berbasis browser bila SSH tidak tersedia.
5. Pastikan folder `storage` dan `bootstrap/cache` writable (755/775).

## Struktur Modul
- `app/Http/Controllers/{Auth,Admin,Sales,Drafter,Shared}` — controller per peran
- `app/Services/QuotationCalculator.php` — kalkulasi subtotal, diskon, PPN, biaya tambahan
- `app/Services/CodeGenerator.php` — generator kode dokumen (PL-, LD-, DR-, Q-, PRJ-)
- `resources/views/{admin,sales,drafter,shared}` — Blade per modul
