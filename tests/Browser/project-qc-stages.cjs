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
        await page.goto('http://localhost/project-workspace/1#qc', { waitUntil: 'networkidle' });
        await page.getByRole('heading', { name: 'QC Produksi', exact: true }).waitFor({ state: 'visible' });
        await page.getByRole('heading', { name: 'QC Pemasangan', exact: true }).waitFor({ state: 'visible' });
        const installation = page.locator('form[data-qc-stage]').nth(1);
        const checks = installation.locator('[data-qc-check]');
        assert.equal(await installation.locator('[data-qc-result]:checked').count(), 0);
        assert.equal(await installation.evaluate(form => form.checkValidity()), false, 'result is required');
        await installation.locator('[data-qc-result][value="failed"]').check();
        assert.equal(await installation.locator('[data-qc-note]').getAttribute('required'), '');
        assert.equal(await installation.evaluate(form => form.checkValidity()), false, 'failed QC requires notes');
        await installation.locator('[data-qc-note]').fill('Sambungan perlu diperbaiki.');
        assert.equal(await installation.evaluate(form => form.checkValidity()), true);
        await installation.locator('[data-qc-result][value="in_progress"]').check();
        await installation.locator('[data-qc-note]').fill('');
        assert.equal(await installation.evaluate(form => form.checkValidity()), true, 'partial QC can be saved without notes');
        assert.equal(await installation.locator('[data-qc-bar], [data-item-progress], input[type="range"]').count(), 0);
        assert.equal(await installation.locator('[data-qc-value]').textContent(), '33%');
        await checks.nth(2).focus();
        await page.keyboard.press('Space');
        assert.equal(await installation.locator('[data-qc-value]').textContent(), '50%');
        assert.equal(await page.locator('form[data-qc-stage]').first().locator('[data-qc-value]').textContent(), '100%');
        assert.equal(await installation.locator('[data-qc-completed]').isDisabled(), true);
        for (let i = 0; i < await checks.count(); i++) await checks.nth(i).check();
        assert.equal(await installation.locator('[data-qc-value]').textContent(), '100%');
        await installation.locator('[data-qc-completed]').check();
        await checks.first().uncheck();
        assert.equal(await installation.locator('[data-qc-value]').textContent(), '83%');
        assert.equal(await installation.locator('[data-qc-completed]').isChecked(), false);
        assert.equal(await installation.evaluate(form => form.checkValidity()), false, 'choose a result again after undoing passed QC');
        await installation.locator('[data-qc-result][value="in_progress"]').check();
        const radioBox = await installation.locator('[data-qc-result]').first().boundingBox();
        assert(radioBox.width >= 28 && radioBox.height >= 28, 'result control is large');
        const saveBox = await installation.locator('.qc-save').boundingBox();
        assert(saveBox.height >= 52, 'save button has a large click target');
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
        assert(saves[0].fields.some(key => key.startsWith('qc_installation_checklist[')));
        assert(saves[0].fields.includes('qc_installation_result'));
        assert(!saves[0].fields.includes('qc_progress'));
        assert(saves[1].fields.some(key => key.startsWith('qc_checklist[')));
        assert(saves[1].fields.includes('qc_result'));
        assert(!saves[1].fields.includes('qc_installation_progress'));
        await page.screenshot({ path: 'tmp/project-qc-desktop.png', fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'no horizontal overflow on mobile');
        await page.screenshot({ path: 'tmp/project-qc-mobile.png', fullPage: true });
        assert.deepEqual(errors, []);
        console.log('PASS: both QC forms render, checklist percentages and independent saves, completion validation, keyboard checkboxes, mobile layout, no browser errors.');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exit(1); });
