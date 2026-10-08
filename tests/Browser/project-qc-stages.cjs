const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

// ProjectQcStagesTest menghasilkan fixture HTML dari Blade dan database tes terisolasi.
(async () => {
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('http://localhost/**', route => {
            const url = new URL(route.request().url());
            const asset = path.resolve('public', url.pathname.slice(1));
            if (asset.startsWith(path.resolve('public') + path.sep) && fs.existsSync(asset) && fs.statSync(asset).isFile()) {
                return route.fulfill({ path: asset });
            }
            if (url.pathname === '/session/keep-alive') return route.fulfill({ status: 204 });
            return route.fulfill({ contentType: 'text/html', body: fs.readFileSync('tmp/qc-workspace-test.html', 'utf8') });
        });
        await page.goto('http://localhost/project-workspace/1#operations', { waitUntil: 'networkidle' });
        await page.getByRole('heading', { name: 'QC Produksi', exact: true }).waitFor({ state: 'visible' });
        await page.getByRole('heading', { name: 'QC Pemasangan', exact: true }).waitFor({ state: 'visible' });
        const installation = page.locator('form[data-qc-stage]').nth(1);
        const range = installation.locator('[data-qc-range]');
        await range.fill('60');
        assert.equal(await installation.locator('[data-qc-value]').textContent(), '60%');
        assert.equal(await installation.locator('[data-qc-bar]').getAttribute('aria-valuenow'), '60');
        assert.equal(await page.locator('#qc_progress').inputValue(), '100', 'production progress remains independent');
        await installation.locator('[data-qc-completed]').check();
        assert.equal(await range.inputValue(), '100');
        await range.fill('40');
        assert.equal(await installation.locator('[data-qc-completed]').isChecked(), false);
        await range.focus();
        await page.keyboard.press('ArrowRight');
        assert.equal(await range.inputValue(), '45', 'progress supports keyboard input');
        await page.evaluate(() => {
            window.savedStages = [];
            document.querySelectorAll('form[data-qc-stage]').forEach(form => form.addEventListener('submit', event => {
                event.preventDefault();
                savedStages.push({ action: form.getAttribute('action'), fields: [...new FormData(form)].map(([key]) => key) });
            }));
        });
        await page.getByRole('button', { name: 'Simpan QC Pemasangan', exact: true }).click();
        await page.getByRole('button', { name: 'Simpan QC Produksi', exact: true }).click();
        const saves = await page.evaluate(() => savedStages);
        assert(saves[0].action.endsWith('/qc-installation'));
        assert(saves[0].fields.includes('qc_installation_progress'));
        assert(!saves[0].fields.includes('qc_progress'));
        assert(saves[1].fields.includes('qc_progress'));
        assert(!saves[1].fields.includes('qc_installation_progress'));
        await page.screenshot({ path: 'tmp/project-qc-desktop.png', fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'no horizontal overflow on mobile');
        await page.screenshot({ path: 'tmp/project-qc-mobile.png', fullPage: true });
        assert.deepEqual(errors, []);
        console.log('PASS: both QC forms render, independent progress and saves, completion sync, keyboard controls, mobile layout, no browser errors.');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exit(1); });
