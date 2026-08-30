const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright-core');
const { ROLES, PAGES } = require('./pages');

const EDGE = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const BASE = process.env.BASE || 'http://127.0.0.1:8123';
const OUT = path.join(__dirname, 'shots');
const PASSWORD = 'password';

fs.mkdirSync(OUT, { recursive: true });

const only = process.argv[2]; // opsional: batasi ke satu role

(async () => {
  const browser = await chromium.launch({ executablePath: EDGE, headless: true });
  const report = [];

  for (const [role, info] of Object.entries(ROLES)) {
    if (only && only !== role) continue;

    const context = await browser.newContext({
      viewport: { width: 1680, height: 1000 },
      deviceScaleFactor: 2,
      locale: 'id-ID',
      timezoneId: 'Asia/Jakarta',
    });
    const page = await context.newPage();

    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    await page.fill('input[type=email]', info.email);
    await page.fill('input[type=password]', PASSWORD);
    await page.click('button[type=submit]');
    await page.waitForLoadState('networkidle');

    if (page.url().includes('/login')) {
      report.push({ role, key: 'LOGIN', status: 'GAGAL LOGIN' });
      await context.close();
      continue;
    }

    for (const [key, title, url, full, click] of PAGES[role]) {
      const file = path.join(OUT, `${role}--${key}.png`);
      try {
        const response = await page.goto(BASE + url, { waitUntil: 'networkidle', timeout: 45000 });
        const status = response ? response.status() : 0;

        // Tutup elemen yang mengganggu ketajaman screenshot.
        await page.addStyleTag({ content: `
          *,*::before,*::after{animation:none!important;transition:none!important}
          .lead-confetti,.modal-backdrop{display:none!important}
        `}).catch(() => {});
        // Buka tab tertentu bila halaman memakai tab (mis. workspace project).
        if (click) {
          await page.click(click, { timeout: 5000 }).catch(() => {});
          await page.waitForTimeout(400);
        }
        await page.waitForTimeout(500);

        await page.screenshot({ path: file, fullPage: Boolean(full) });
        report.push({ role, key, title, url, status, file: path.basename(file) });
        process.stdout.write(status === 200 ? '.' : `[${status} ${role}/${key}]`);
      } catch (error) {
        report.push({ role, key, title, url, status: 'ERROR', error: error.message.split('\n')[0] });
        process.stdout.write(`[ERR ${role}/${key}]`);
      }
    }

    await context.close();
  }

  await browser.close();
  fs.writeFileSync(path.join(__dirname, 'capture-report.json'), JSON.stringify(report, null, 2));

  const bad = report.filter(r => r.status !== 200);
  console.log(`\n\nTotal: ${report.length} halaman, gagal: ${bad.length}`);
  bad.forEach(r => console.log(`  ${r.role}/${r.key} -> ${r.status} ${r.error || ''} (${r.url || ''})`));
})().catch(e => { console.error('FATAL:', e); process.exit(1); });
