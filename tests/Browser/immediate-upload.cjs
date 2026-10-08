const { chromium } = require('playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');

(async () => {
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    const page = await browser.newPage();
    let uploads = 0;
    let deletes = 0;
    let failNext = false;
    let releaseUpload;
    let delayNext = false;
    const errors = [];
    page.on('pageerror', e => errors.push(e.message));
    await page.route('http://upload.test/**', async route => {
        const req = route.request();
        const url = new URL(req.url());
        if (url.pathname.endsWith('.js')) return route.fulfill({ contentType: 'text/javascript', body: fs.readFileSync(`public/js/${url.pathname.slice(1)}`, 'utf8') });
        if (req.method() === 'DELETE') { deletes++; return route.fulfill({ status: 204 }); }
        if (req.method() === 'POST') {
            uploads++;
            if (delayNext) {
                delayNext = false;
                await new Promise(resolve => releaseUpload = resolve);
            }
            if (failNext) { failNext = false; return route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ message: 'File ditolak' }) }); }
            return route.fulfill({ status: 201, contentType: 'application/json', body: JSON.stringify({ token: `token-${uploads}` }) });
        }
        return route.fulfill({ contentType: 'text/html', body: `<html><head><meta name="csrf-token" content="test"></head><body>
            <form id="normal"><input name="title" value="test"><input id="single" name="file" type="file" required><input id="multi" name="documents[]" type="file" multiple data-multi-file><button type="submit">Simpan</button><button type="reset">Reset</button></form>
            <form id="dynamic"></form>
            <script>const send = XMLHttpRequest.prototype.send; XMLHttpRequest.prototype.send = function (data) { window.lastUpload = this; return send.call(this, data); };</script>
            <script src="/multi-file-input.js"></script><script src="/immediate-upload.js" data-upload-url="http://upload.test/temporary-uploads"></script>
            <script>window.saves=[];document.querySelectorAll('form').forEach(f=>f.addEventListener('submit',e=>{e.preventDefault();window.saves.push([...new FormData(f)].map(([k,v])=>[k,v instanceof File?'RAW FILE':v]));}));</script>
            </body></html>` });
    });
    await page.goto('http://upload.test/');
    const file = name => ({ name, mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4') });
    delayNext = true;
    await page.locator('#single').setInputFiles(file('awal.pdf'));
    await page.waitForFunction(() => document.body.textContent.includes('Mengunggah'));
    await page.evaluate(() => lastUpload.upload.dispatchEvent(new ProgressEvent('progress', { lengthComputable: true, loaded: 37, total: 100 })));
    const bar = page.getByRole('progressbar', { name: 'Upload awal.pdf', exact: true });
    assert.equal(await bar.getAttribute('aria-valuenow'), '37');
    assert.equal(await bar.evaluate(el => el.style.width), '37%');
    assert((await bar.getAttribute('class')).includes('progress-bar-animated'));
    await page.getByText('Simpan', { exact: true }).click();
    assert.equal(await page.evaluate(() => saves.length), 0, 'save blocked while uploading');
    releaseUpload();
    await page.waitForFunction(() => document.body.textContent.includes('Upload selesai'));
    assert.equal(await bar.getAttribute('aria-valuenow'), '100');
    assert((await bar.getAttribute('class')).includes('bg-success'));
    assert(!(await bar.getAttribute('class')).includes('progress-bar-animated'));
    assert.equal(uploads, 1, 'file uploaded on selection');
    await page.getByText('Simpan', { exact: true }).click();
    const firstSave = await page.evaluate(() => saves[0]);
    assert(!JSON.stringify(firstSave).includes('RAW FILE'));
    assert(firstSave.some(([key, value]) => key === '_uploaded_files[0][field]' && value === 'file'));
    assert.equal(uploads, 1, 'save does not reupload');
    await page.locator('#multi').setInputFiles(file('satu.pdf'));
    await page.waitForFunction(() => document.body.textContent.includes('satu.pdf: Upload selesai'));
    await page.locator('#multi').setInputFiles(file('dua.pdf'));
    await page.waitForFunction(() => document.body.textContent.includes('dua.pdf: Upload selesai'));
    assert.equal(uploads, 3, 'adding a file does not reupload previous selections');
    await page.getByRole('button', { name: 'Hapus satu.pdf dari daftar unggahan' }).click();
    await page.waitForFunction(() => !document.body.textContent.includes('satu.pdf: Upload selesai'));
    await page.getByText('Simpan', { exact: true }).click();
    assert((await page.evaluate(() => saves.at(-1))).some(([k,v])=>k.endsWith('[field]') && v==='documents.0'));
    failNext = true;
    await page.locator('#single').setInputFiles(file('ulang.pdf'));
    await page.getByRole('button', { name: 'Coba lagi' }).waitFor();
    assert((await page.getByRole('progressbar', { name: 'Upload ulang.pdf', exact: true }).getAttribute('class')).includes('bg-danger'));
    await page.getByText('Simpan', { exact: true }).click();
    assert.equal(await page.evaluate(() => saves.length), 2, 'failed upload prevents save');
    await page.getByRole('button', { name: 'Coba lagi' }).click();
    await page.waitForFunction(() => document.body.textContent.includes('ulang.pdf: Upload selesai'));
    await page.evaluate(() => { document.querySelector('#dynamic').innerHTML = '<input type="file" name="items[4][quotation_image]" id="nested">'; });
    await page.locator('#nested').setInputFiles(file('gambar.pdf'));
    await page.waitForFunction(() => document.body.textContent.includes('gambar.pdf: Upload selesai'));
    const nested = await page.evaluate(() => [...new FormData(document.querySelector('#dynamic'))]);
    assert(nested.some(([k,v]) => k.endsWith('[field]') && v==='items.4.quotation_image'));
    await page.locator('#nested').evaluate(el => el.remove());
    await page.getByText('Reset', { exact: true }).click();
    await page.locator('#multi').setInputFiles(file('baru.pdf'));
    await page.waitForFunction(() => document.body.textContent.includes('baru.pdf: Upload selesai'));
    assert.equal(await page.locator('#multi').evaluate(el => el.files.length), 1, 'reset clears multi-file state');
    assert.equal(errors.length, 0, errors.join('\n'));
    assert(deletes >= 3, 'removed and replaced files cleaned up');
    await page.route('http://upload.test/restored', route => route.fulfill({contentType:'text/html',body:`<html><head><meta name="csrf-token" content="test"></head><body>
        <form id="restored"><input name="file" type="file" required><input name="documents[]" type="file" multiple data-multi-file><button>Simpan ulang</button></form>
        <script type="application/json" id="staged-upload-input">[{"field":"file","token":"old-single","name":"lama.pdf"},{"field":"documents.0","token":"old-multi","name":"lampiran.pdf"}]</script>
        <script src="/multi-file-input.js"></script><script src="/immediate-upload.js" data-upload-url="http://upload.test/temporary-uploads"></script>
        <script>window.saves=[];document.querySelector('form').addEventListener('submit',e=>{e.preventDefault();saves.push([...new FormData(e.target)])});</script></body></html>`}));
    await page.goto('http://upload.test/restored');
    await page.getByText('Simpan ulang', { exact: true }).click();
    assert.equal(await page.evaluate(() => saves.length), 1, 'restored required file allows submit');
    assert((await page.evaluate(()=>saves[0])).some(([k,v])=>k.endsWith('[token]') && v==='old-single'));
    const previousCount = uploads;
    await page.locator('input[multiple]').setInputFiles(file('tambahan.pdf'));
    await page.waitForFunction(() => document.body.textContent.includes('tambahan.pdf: Upload selesai'));
    assert.equal(uploads, previousCount + 1);
    assert((await page.evaluate(()=>[...new FormData(document.querySelector('form'))])).some(([k,v])=>k.endsWith('[token]') && v==='old-multi'));
    await page.getByRole('button', { name: 'Hapus lama.pdf dari upload sebelumnya' }).click();
    assert(await page.locator('input[name="file"]').evaluate(el=>el.required), 'removing restored upload restores required validation');
    console.log('PASS: immediate selection, no binary on save, upload guard, multi-file add/remove, replacement, retry, dynamic nested inputs, reset, no browser errors.');
    await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
