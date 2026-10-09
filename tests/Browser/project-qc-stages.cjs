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
        await page.getByRole('tab', { name: 'QC Pemasangan', exact: true }).click();
        assert.equal(await page.getByRole('heading', { name: 'QC Produksi', exact: true }).isVisible(), false);
        await page.getByRole('heading', { name: 'QC Pemasangan', exact: true }).waitFor({ state: 'visible' });
        const installation = page.locator('form[data-qc-stage]').first();
        assert.equal(await page.getByRole('button', { name: 'Simpan QC Produksi', exact: true }).count(), 0);
        assert.equal(await page.locator('input[name="qc_result"]').count(), 0);
        assert(await page.getByText('Hasil QC dikunci dan tidak dapat diubah.', { exact: false }).count());
        const checks = installation.locator('[data-qc-check]');
        assert.equal(await installation.locator('[name="qc_installation_target_date"]').getAttribute('required'), '');
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
        await installation.locator('[name="qc_installation_target_date"]').fill('');
        assert.equal(await installation.evaluate(form => form.checkValidity()), false, 'target date is required');
        await installation.locator('[name="qc_installation_target_date"]').fill('2026-10-25');
        assert.equal(await installation.locator('[data-qc-bar], [data-item-progress], input[type="range"]').count(), 0);
        assert.equal(await installation.locator('[data-qc-value]').textContent(), '33%');
        await checks.nth(2).focus();
        await page.keyboard.press('Space');
        assert.equal(await installation.locator('[data-qc-value]').textContent(), '50%');
        assert.equal(await page.locator('[id^="qc_saved_"]').first().isChecked(), true, 'locked production checklist is unchanged');
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
        const saves = await page.evaluate(() => savedStages);
        assert(saves[0].action.endsWith('/qc-installation'));
        assert(saves[0].fields.some(key => key.startsWith('qc_installation_checklist[')));
        assert(saves[0].fields.includes('qc_installation_result'));
        assert(!saves[0].fields.includes('qc_progress'));
        assert.equal(saves.length, 1, 'only unfinished QC can be submitted');
        await page.getByRole('tab', { name: 'QC Produksi', exact: true }).click();
        const targetForm = page.locator('form[data-target-date-form][action$="/target-date/qc"]');
        await targetForm.locator('..').locator('summary').click();
        assert.equal(await targetForm.locator('[name="qc_target_date"]').getAttribute('required'), '');
        await targetForm.locator('[name="qc_target_date"]').fill('2026-11-01');
        await targetForm.locator('[name="target_date_reason"]').fill('Lokasi customer belum siap.');
        await targetForm.evaluate(form => form.addEventListener('submit', event => {
            event.preventDefault();
            window.targetDateSave = Object.fromEntries(new FormData(form));
        }));
        await targetForm.getByRole('button', { name: 'Simpan tanggal target', exact: true }).click();
        const dateSave = await page.evaluate(() => window.targetDateSave);
        assert.equal(dateSave.qc_target_date, '2026-11-01');
        assert.equal(dateSave.target_date_reason, 'Lokasi customer belum siap.');
        assert.equal(dateSave.qc_result, undefined, 'date-only form cannot update locked QC results');
        await page.getByRole('tab', { name: 'QC Pemasangan', exact: true }).click();
        await page.screenshot({ path: 'tmp/project-qc-desktop.png', fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'no horizontal overflow on mobile');
        await page.screenshot({ path: 'tmp/project-qc-mobile.png', fullPage: true });
        assert.deepEqual(errors, []);
        console.log('PASS: passed production QC is locked, unfinished installation QC saves checklist percentages, completion validation, keyboard checkboxes, mobile layout, no browser errors.');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exit(1); });
