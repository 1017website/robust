/**
 * Indikator progres unggahan berkas besar.
 *
 * Dipasang pada form yang diberi atribut data-upload-progress. Form dikirim lewat
 * XHR agar kemajuan unggahan dapat ditampilkan, lalu halaman dialihkan mengikuti
 * URL yang dikembalikan server.
 *
 * Atribut yang dibaca dari form:
 *   data-upload-progress            penanda wajib
 *   data-max-file-size="83886080"   batas ukuran per berkas dalam byte
 *   data-max-files="5"              batas jumlah berkas sekali kirim (0 = tanpa batas)
 *   data-redirect="/url"            tujuan cadangan bila server tidak mengirim redirect
 *
 * Markup panel progres disediakan komponen <x-upload-progress />.
 */
(function () {
    'use strict';

    function formatBytes(bytes) {
        if (!Number.isFinite(bytes) || bytes <= 0) return '0 B';

        const units = ['B', 'KB', 'MB', 'GB'];
        const unitIndex = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        const value = bytes / (1024 ** unitIndex);

        return `${value.toLocaleString('id-ID', { maximumFractionDigits: unitIndex === 0 ? 0 : 1 })} ${units[unitIndex]}`;
    }

    function bind(form) {
        const panel = form.querySelector('[data-upload-progress-panel]');
        const bar = form.querySelector('[data-upload-bar]');
        const status = form.querySelector('[data-upload-status]');
        const percent = form.querySelector('[data-upload-percent]');
        const detail = form.querySelector('[data-upload-detail]');
        const errors = form.querySelector('[data-upload-errors]');

        if (!panel || !bar) return;

        const maxFileSize = Number(form.dataset.maxFileSize || 0);
        const maxFiles = Number(form.dataset.maxFiles || 0);

        function setProgress(value, statusText, detailText = '') {
            const safe = Math.max(0, Math.min(100, Math.round(value)));
            panel.classList.remove('d-none');
            bar.style.width = `${safe}%`;
            bar.setAttribute('aria-valuenow', safe.toString());
            if (percent) percent.textContent = `${safe}%`;
            if (status) status.textContent = statusText;
            if (detail) detail.textContent = detailText;
        }

        function showErrors(messages) {
            if (!errors) return;

            errors.replaceChildren();
            const title = document.createElement('div');
            title.className = 'fw-bold mb-1';
            title.textContent = 'Data belum dapat disimpan:';
            errors.appendChild(title);

            const list = document.createElement('ul');
            list.className = 'mb-0 ps-3';
            messages.forEach(function (message) {
                const item = document.createElement('li');
                item.textContent = message;
                list.appendChild(item);
            });
            errors.appendChild(list);
            errors.classList.remove('d-none');
            errors.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function setBusy(isBusy) {
            form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (button) {
                button.disabled = isBusy;
            });
        }

        function selectedFiles() {
            const files = [];
            form.querySelectorAll('input[type="file"]').forEach(function (input) {
                Array.from(input.files || []).forEach(function (file) { files.push(file); });
            });

            return files;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const files = selectedFiles();
            const clientErrors = [];
            const limitLabel = formatBytes(maxFileSize);

            if (maxFiles > 0 && files.length > maxFiles) {
                clientErrors.push(`Maksimal ${maxFiles} file dapat diunggah sekaligus.`);
            }
            if (maxFileSize > 0) {
                files.filter((file) => file.size > maxFileSize).forEach(function (file) {
                    clientErrors.push(`${file.name} melebihi batas ${limitLabel} per file.`);
                });
            }

            errors?.classList.add('d-none');
            if (clientErrors.length) {
                showErrors(clientErrors);
                return;
            }

            const formData = new FormData(form);
            const submitter = event.submitter;
            if (submitter?.name) formData.set(submitter.name, submitter.value);

            // Jangan pakai form.action: kontrol bernama "action" di dalam form
            // membayangi properti tersebut sehingga yang terbaca elemennya, bukan URL.
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.getAttribute('action'), true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            setBusy(true);
            bar.classList.add('progress-bar-animated');
            bar.classList.remove('bg-danger');
            setProgress(
                0,
                files.length ? 'Menyiapkan upload...' : 'Menyimpan data...',
                files.length ? `${files.length} file dipilih` : 'Tanpa lampiran'
            );

            xhr.upload.addEventListener('progress', function (progressEvent) {
                if (!progressEvent.lengthComputable) {
                    setProgress(0, 'Mengunggah lampiran...', `${formatBytes(progressEvent.loaded)} terkirim`);
                    return;
                }

                setProgress(
                    (progressEvent.loaded / progressEvent.total) * 100,
                    'Mengunggah ke server...',
                    `${formatBytes(progressEvent.loaded)} dari ${formatBytes(progressEvent.total)} terkirim`
                );
            });

            xhr.upload.addEventListener('load', function () {
                setProgress(100, 'Upload selesai, memproses data...', 'Menunggu konfirmasi dari server.');
            });

            xhr.addEventListener('load', function () {
                let payload = {};
                try {
                    payload = JSON.parse(xhr.responseText || '{}');
                } catch (error) {
                    payload = {};
                }

                if (xhr.status >= 200 && xhr.status < 300) {
                    setProgress(100, 'Upload selesai. Mengalihkan...', payload.message || 'Data berhasil disimpan.');
                    bar.classList.remove('progress-bar-animated');

                    const target = new URL(
                        payload.redirect || form.dataset.redirect || window.location.href,
                        window.location.href
                    );
                    // Bila tujuannya halaman yang sama dan hanya berbeda anchor, browser
                    // tidak memuat ulang. Paksa muat ulang agar data baru ikut tampil.
                    if (target.href.split('#')[0] === window.location.href.split('#')[0]) {
                        if (target.hash) window.location.hash = target.hash;
                        window.location.reload();
                    } else {
                        window.location.assign(target.href);
                    }
                    return;
                }

                setBusy(false);
                bar.classList.remove('progress-bar-animated');
                bar.classList.add('bg-danger');
                setProgress(0, 'Upload gagal', 'Periksa pesan kesalahan di bawah.');

                if (xhr.status === 422 && payload.errors) {
                    showErrors(Object.values(payload.errors).flat());
                } else if (xhr.status === 413) {
                    showErrors(['Total ukuran upload melebihi batas yang diizinkan server. Kurangi jumlah atau ukuran file, lalu coba lagi.']);
                } else if (xhr.status === 419) {
                    showErrors(['Ukuran kiriman melebihi batas server sehingga data tidak sampai, atau sesi Anda telah berakhir. Kecilkan file lalu muat ulang halaman dan coba kembali.']);
                } else {
                    showErrors([payload.message || 'Upload gagal karena terjadi gangguan pada server. Silakan coba kembali.']);
                }
            });

            xhr.addEventListener('error', function () {
                setBusy(false);
                bar.classList.remove('progress-bar-animated');
                bar.classList.add('bg-danger');
                setProgress(0, 'Koneksi terputus', 'Upload belum selesai.');
                showErrors(['Koneksi ke server terputus. Periksa jaringan Anda, lalu coba kembali.']);
            });

            xhr.send(formData);
        });
    }

    document.querySelectorAll('form[data-upload-progress]').forEach(bind);
})();
