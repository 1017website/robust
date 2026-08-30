const fs = require('fs');
const path = require('path');
const { ROLES, PAGES } = require('./pages');

const CONTENT = Object.assign({}, require('./content-1'), require('./content-2'), require('./content-3'));
const META = JSON.parse(fs.readFileSync('img-meta.json', 'utf8'));

const esc = (t) => String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
// Konten panduan ditulis sendiri dan boleh memuat <b>; hanya rapikan spasi berlebih.
const rich = (t) => String(t).replace(/\s+/g, ' ').trim();

const TANGGAL = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
  .format(new Date(process.env.GUIDE_DATE || Date.now()));

const ROLE_ORDER = ['administrator', 'sales', 'sales_spv', 'drafter', 'production', 'qc', 'delivery', 'administration'];

const AKUN = [
  ['Administrator', 'superadmin@robust.test', 'Seluruh menu, pengaturan sistem, dan data lintas sales.'],
  ['Sales', 'sales@robust.test', 'Penjualan dari prospek sampai order, ditambah pekerjaan back office.'],
  ['SPV Sales', 'spv@robust.test', 'Pengawasan penawaran dan pekerjaan tim sales.'],
  ['Drafter', 'drafter@robust.test', 'Mengunggah dan merevisi drawing serta dokumen desain.'],
  ['Produksi', 'production@robust.test', 'Spesifikasi teknis, HPP, Master Item, dan progres produksi.'],
  ['Quality Control', 'qc@robust.test', 'Pemeriksaan hasil produksi terhadap spesifikasi penawaran.'],
  ['Delivery', 'delivery@robust.test', 'Jadwal kirim, bukti terima, dan Delivery Order.'],
  ['Administration', 'administration@robust.test', 'Konfirmasi pembayaran, bukti potong PPh, catatan administrasi.'],
];

const ALUR = [
  ['1', 'Administrator', 'Membuat Pra Lead dan mengirimnya ke sales.'],
  ['2', 'Sales', 'Menerima Request Masuk sehingga menjadi Lead miliknya.'],
  ['3', 'Sales', 'Mencatat aktivitas follow up dan melengkapi data customer.'],
  ['4', 'Sales', 'Membuat Design Request dan menugaskannya ke Drafter.'],
  ['5', 'Drafter', 'Mengunggah drawing dan dokumen desain.'],
  ['6', 'Produksi', 'Mengisi spesifikasi rinci, item hasil, dan HPP.'],
  ['7', 'Sales', 'Menyusun penawaran dari hasil Design Request.'],
  ['8', 'SPV Sales', 'Meninjau isi dan nilai penawaran.'],
  ['9', 'Sales', 'Mengirim penawaran dan mencatat respons customer.'],
  ['10', 'Sales / Administrator', 'Membuat Request PO, dari penawaran CRM maupun PO existing.'],
  ['11', 'Administrator / Sales', 'Memproses PO di Accurate. Project terbit otomatis.'],
  ['12', 'Produksi', 'Mengerjakan produksi dan memperbarui progresnya.'],
  ['13', 'Quality Control', 'Memeriksa hasil produksi terhadap spesifikasi penawaran.'],
  ['14', 'Delivery', 'Mengirim barang, mencatat bukti terima, menerbitkan Delivery Order.'],
  ['15', 'Administration', 'Mengonfirmasi pembayaran dan bukti potong PPh.'],
  ['16', 'Administrator / Sales', 'Menerbitkan invoice dan memantau pelunasan tiap termin.'],
];

function figure(role, key, title) {
  const m = META[`${role}--${key}`];
  if (!m) return '';
  const tall = m.h / m.w > 1.36;
  return `<figure class="shot${tall ? ' tall' : ''}">
    <img src="img/${m.file}" alt="${esc(title)}">
    <figcaption>${esc(title)}</figcaption>
  </figure>`;
}

function section(role, key, title, no) {
  const c = (CONTENT[role] || {})[key] || {};
  const parts = [`<section class="page"><h3 id="s-${role}-${key}"><span class="no">${no}</span>${esc(title)}</h3>`];

  if (c.f) parts.push(`<p class="lead">${rich(c.f)}</p>`);
  parts.push(figure(role, key, title));

  if (c.s && c.s.length) {
    parts.push('<h4>Cara memakai</h4><ol class="steps">');
    c.s.forEach((step) => parts.push(`<li>${rich(step)}</li>`));
    parts.push('</ol>');
  }

  if (c.k && c.k.length) {
    parts.push('<h4>Kolom isian</h4><table class="fields"><thead><tr><th>Kolom</th><th>Wajib</th><th>Penjelasan</th></tr></thead><tbody>');
    c.k.forEach(([nama, wajib, ket]) => {
      parts.push(`<tr><td class="nm">${esc(nama)}</td><td class="wj">${wajib ? '<b>Wajib</b>' : 'Opsional'}</td><td>${rich(ket)}</td></tr>`);
    });
    parts.push('</tbody></table>');
  }

  if (c.n) parts.push(`<div class="note"><b>Penting.</b> ${rich(c.n)}</div>`);

  parts.push('</section>');
  return parts.join('\n');
}

// ---------------------------------------------------------------- daftar isi
let toc = '<section class="page"><h2 class="plain">Daftar Isi</h2><ol class="toc">';
toc += '<li><span>1</span> Tentang Panduan Ini</li>';
ROLE_ORDER.forEach((role, i) => {
  toc += `<li><span>${i + 2}</span> ${esc(ROLES[role].label)}<ol>`;
  PAGES[role].forEach(([key, title], j) => {
    toc += `<li><span>${i + 2}.${j + 1}</span> ${esc(title)}</li>`;
  });
  toc += '</ol></li>';
});
toc += '</ol></section>';

// ------------------------------------------------------------------- isi bab
let body = '';
ROLE_ORDER.forEach((role, i) => {
  const bab = i + 2;
  const label = ROLES[role].label;
  const intro = (CONTENT[role] || {}).intro || '';

  body += `<section class="page chapter"><div class="chapter-no">Bab ${bab}</div><h2>${esc(label)}</h2>
    <p class="chapter-intro">${rich(intro)}</p>
    <div class="chapter-akun"><b>Akun contoh:</b> ${esc(ROLES[role].email)} &nbsp;·&nbsp; <b>Jumlah halaman dibahas:</b> ${PAGES[role].length}</div>
    <h4>Menu yang dibahas pada bab ini</h4><ol class="chapter-menus">`;
  PAGES[role].forEach(([, title]) => { body += `<li>${esc(title)}</li>`; });
  body += '</ol></section>';

  PAGES[role].forEach(([key, title], j) => {
    body += section(role, key, title, `${bab}.${j + 1}`);
  });
});

const totalHalaman = ROLE_ORDER.reduce((n, r) => n + PAGES[r].length, 0);

// --------------------------------------------------------------------- HTML
const html = `<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title>Panduan Pengguna ROBUST Sales CRM</title>
<style>
  @page { size: A4; margin: 18mm 16mm 20mm 16mm; }
  * { box-sizing: border-box; }
  body { font-family: "Segoe UI", Arial, sans-serif; color: #1f2a37; font-size: 10.2pt; line-height: 1.55; margin: 0; }
  h1, h2, h3, h4 { color: #0a1f44; margin: 0 0 .5em; line-height: 1.25; }
  p { margin: 0 0 .7em; }

  .cover { height: 245mm; display: flex; flex-direction: column; justify-content: center; page-break-after: always; }
  .cover .brand { font-size: 34pt; font-weight: 800; letter-spacing: -1px; color: #0a1f44; }
  .cover .brand span { color: #0b63ce; }
  .cover .tag { font-size: 10pt; color: #5b6b82; letter-spacing: 2px; text-transform: uppercase; margin-top: 2mm; }
  .cover h1 { font-size: 26pt; margin: 18mm 0 3mm; }
  .cover .sub { font-size: 12pt; color: #44546a; max-width: 120mm; }
  .cover .rule { height: 4px; width: 60mm; background: #0b63ce; margin: 8mm 0; border-radius: 2px; }
  .cover .meta { font-size: 9.5pt; color: #5b6b82; margin-top: 12mm; }
  .cover .meta b { color: #0a1f44; }

  section.page { page-break-before: always; }
  h2 { font-size: 20pt; }
  h2.plain { margin-bottom: 6mm; }
  h3 { font-size: 13.5pt; margin-top: 0; padding-bottom: 2mm; border-bottom: 2px solid #e6ebf2; }
  h3 .no { color: #0b63ce; font-weight: 800; margin-right: 3mm; }
  h4 { font-size: 10.5pt; margin: 5mm 0 2mm; color: #0b63ce; text-transform: uppercase; letter-spacing: .6px; }

  .lead { color: #37455a; }

  .chapter { }
  .chapter-no { font-size: 9.5pt; letter-spacing: 3px; text-transform: uppercase; color: #0b63ce; font-weight: 700; }
  .chapter h2 { font-size: 30pt; margin: 1mm 0 5mm; }
  .chapter-intro { font-size: 11pt; color: #37455a; max-width: 150mm; }
  .chapter-akun { background: #f4f7fb; border-left: 4px solid #0b63ce; padding: 3mm 4mm; font-size: 9.5pt; margin: 5mm 0; }
  .chapter-menus { columns: 2; column-gap: 8mm; font-size: 10pt; margin: 0; padding-left: 5mm; }
  .chapter-menus li { margin-bottom: 1mm; break-inside: avoid; }

  figure.shot { margin: 4mm 0 5mm; text-align: center; break-inside: avoid; }
  figure.shot img { max-width: 100%; max-height: 170mm; border: 1px solid #d7dee8; border-radius: 3px; }
  figure.shot.tall img { max-height: 205mm; }
  figure.shot figcaption { font-size: 8.5pt; color: #6b7a90; margin-top: 2mm; font-style: italic; }

  ol.steps { margin: 0; padding-left: 6mm; }
  ol.steps li { margin-bottom: 1.6mm; }

  table.fields { width: 100%; border-collapse: collapse; font-size: 9.3pt; margin-top: 1mm; }
  table.fields th { background: #0a1f44; color: #fff; text-align: left; padding: 2mm 2.5mm; font-size: 8.8pt; text-transform: uppercase; letter-spacing: .5px; }
  table.fields td { border-bottom: 1px solid #e6ebf2; padding: 2mm 2.5mm; vertical-align: top; }
  table.fields tr { break-inside: avoid; }
  table.fields td.nm { font-weight: 600; color: #0a1f44; width: 34%; }
  table.fields td.wj { width: 14%; white-space: nowrap; color: #5b6b82; }
  table.fields td.wj b { color: #b42318; }

  .note { background: #fff8e6; border-left: 4px solid #f0a202; padding: 3mm 4mm; font-size: 9.5pt; margin-top: 4mm; break-inside: avoid; }

  ol.toc { list-style: none; padding: 0; margin: 0; font-size: 10pt; }
  ol.toc > li { font-weight: 700; color: #0a1f44; margin-top: 3mm; }
  ol.toc ol { list-style: none; padding-left: 8mm; margin: 1mm 0 0; font-weight: 400; color: #37455a; }
  ol.toc li span { display: inline-block; min-width: 12mm; color: #0b63ce; font-weight: 700; }
  ol.toc ol li { margin-bottom: .6mm; }

  table.simple { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin: 3mm 0 6mm; }
  table.simple th { background: #eef3f9; text-align: left; padding: 2mm 2.5mm; color: #0a1f44; }
  table.simple td { border-bottom: 1px solid #e6ebf2; padding: 2mm 2.5mm; vertical-align: top; }
  table.simple td.k { font-weight: 600; white-space: nowrap; }
  code { background: #f1f4f8; padding: .5mm 1.5mm; border-radius: 2px; font-size: 9pt; }
</style></head><body>

<div class="cover">
  <div class="brand">ROBUST<span>.</span></div>
  <div class="tag">Laboratory Furniture &amp; Equipment</div>
  <h1>Panduan Pengguna<br>Sales CRM</h1>
  <div class="rule"></div>
  <div class="sub">Petunjuk pemakaian setiap menu untuk seluruh role, lengkap dengan tampilan layar,
    langkah kerja, dan penjelasan tiap kolom isian.</div>
  <div class="meta">
    <b>Versi dokumen</b> &nbsp; ${TANGGAL}<br>
    <b>Cakupan</b> &nbsp; ${ROLE_ORDER.length} role &nbsp;·&nbsp; ${totalHalaman} halaman aplikasi
  </div>
</div>

<section class="page"><h2 class="plain">1. Tentang Panduan Ini</h2>
  <p>Dokumen ini menjelaskan pemakaian ROBUST Sales CRM menu demi menu. Setiap bagian memuat tampilan layar
  sesungguhnya, tujuan halaman, langkah pemakaian, dan penjelasan kolom isian bila halaman tersebut berupa
  form. Seluruh tangkapan layar diambil dari sistem yang berjalan menggunakan data contoh, sehingga angka dan
  nama yang tampil adalah data peragaan, bukan data customer sebenarnya.</p>

  <h4>Cara membaca</h4>
  <ul>
    <li>Dokumen disusun per role. Buka bab sesuai role akun Anda.</li>
    <li>Kolom bertanda <b>Wajib</b> harus diisi sebelum data dapat disimpan atau diajukan.</li>
    <li>Kotak kuning bertanda <b>Penting</b> memuat aturan yang sering menimbulkan kesalahan bila dilewatkan.</li>
  </ul>

  <h4>Masuk ke sistem</h4>
  <p>Buka alamat CRM yang diberikan Administrator, lalu masuk memakai email dan password akun Anda. Menu yang
  tampil di sisi kiri layar mengikuti role akun. Bila sebuah menu tidak terlihat, berarti role Anda memang tidak
  memiliki akses ke menu tersebut.</p>

  <h4>Role dan akun contoh</h4>
  <table class="simple"><thead><tr><th>Role</th><th>Akun contoh</th><th>Tanggung jawab utama</th></tr></thead><tbody>
  ${AKUN.map(([r, e, d]) => `<tr><td class="k">${esc(r)}</td><td><code>${esc(e)}</code></td><td>${esc(d)}</td></tr>`).join('')}
  </tbody></table>

  <div class="note"><b>Penting.</b> Akun di atas adalah akun peragaan. Ganti passwordnya, atau nonaktifkan
  akunnya, sebelum sistem dipakai untuk data sesungguhnya.</div>
</section>

<section class="page"><h2 class="plain">Alur Kerja Menyeluruh</h2>
  <p>Satu order berpindah tangan antar role dengan urutan berikut. Bab-bab selanjutnya menjelaskan pekerjaan
  tiap role secara rinci.</p>
  <table class="simple"><thead><tr><th>Tahap</th><th>Dikerjakan oleh</th><th>Pekerjaan</th></tr></thead><tbody>
  ${ALUR.map(([n, r, d]) => `<tr><td class="k">${esc(n)}</td><td class="k">${esc(r)}</td><td>${esc(d)}</td></tr>`).join('')}
  </tbody></table>
  <div class="note"><b>Penting.</b> Urutan operasional bersifat berjenjang: QC baru bisa memeriksa setelah
  Produksi selesai, Delivery baru bisa mengirim setelah QC selesai, dan Invoice baru bisa terbit setelah
  Delivery selesai serta penerimaan customer dikonfirmasi.</div>
</section>

${toc}
${body}

</body></html>`;

fs.writeFileSync(path.join(__dirname, 'guide.html'), html);
console.log(`guide.html ditulis — ${totalHalaman} halaman aplikasi, ${ROLE_ORDER.length} bab.`);
