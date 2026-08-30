const path = require('path');
const { chromium } = require('playwright-core');

const EDGE = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const SRC = 'file:///' + path.join(__dirname, 'guide.html').replace(/\\/g, '/');
const OUT = process.argv[2] || path.join(__dirname, 'Panduan-Pengguna-ROBUST-CRM.pdf');

(async () => {
  const browser = await chromium.launch({ executablePath: EDGE, headless: true });
  const page = await browser.newPage();
  await page.goto(SRC, { waitUntil: 'networkidle' });
  await page.emulateMedia({ media: 'print' });

  const options = {
    path: OUT,
    format: 'A4',
    printBackground: true,
    displayHeaderFooter: true,
    margin: { top: '18mm', right: '16mm', bottom: '20mm', left: '16mm' },
    headerTemplate: '<div></div>',
    footerTemplate: `
      <div style="width:100%;font-family:Segoe UI,Arial,sans-serif;font-size:7.5pt;color:#8a97a8;
                  padding:0 16mm;display:flex;justify-content:space-between;">
        <span>Panduan Pengguna ROBUST Sales CRM</span>
        <span>Halaman <span class="pageNumber"></span> dari <span class="totalPages"></span></span>
      </div>`,
  };

  // Bookmark PDF dibuat dari struktur heading bila versi Playwright mendukung.
  try {
    await page.pdf({ ...options, outline: true });
  } catch (e) {
    await page.pdf(options);
  }

  await browser.close();
  console.log('PDF ditulis:', OUT);
})().catch((e) => { console.error('FATAL:', e); process.exit(1); });
