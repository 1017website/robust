(function () {
    'use strict';

    const endpoint = document.currentScript.dataset.uploadUrl;
    const states = new Map();
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

    function discard(record) {
        record.removed = true;
        record.xhr?.abort();
        if (record.token) {
            fetch(`${endpoint}/${record.token}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            }).catch(() => {});
        }
    }

    function render(state) {
        state.input.dataset.uploadCount = String(state.records.filter((record) => record.token).length);
        state.input.dataset.uploadNames = JSON.stringify(state.records.filter((record) => record.token).map((record) => record.file.name));
        state.status.replaceChildren();
        state.records.forEach((record) => {
            const row = document.createElement('div');
            row.className = 'mb-2';
            const progress = record.token ? 100 : Math.max(0, Math.min(100, record.percent || 0));
            const label = document.createElement('span');
            label.textContent = `${record.file.name}: ${record.token ? 'Upload selesai' : record.error || (progress === 100 ? 'Memproses file...' : `Mengunggah ${progress}%`)}`;
            row.appendChild(label);
            if (record.error) {
                const retry = document.createElement('button');
                retry.type = 'button';
                retry.className = 'btn btn-sm btn-soft ms-2';
                retry.textContent = 'Coba lagi';
                retry.addEventListener('click', () => upload(record, state));
                row.appendChild(retry);
            }
            if (record.restored) {
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn-sm btn-soft ms-2';
                remove.textContent = 'Hapus';
                remove.setAttribute('aria-label', `Hapus ${record.file.name} dari upload sebelumnya`);
                remove.addEventListener('click', () => {
                    discard(record);
                    state.records = state.records.filter((item) => item !== record);
                    if (!state.records.some((item) => item.restored)) state.input.required = state.required;
                    render(state);
                });
                row.appendChild(remove);
            }
            const track = document.createElement('div');
            track.className = 'progress mt-1';
            track.style.height = '10px';
            const bar = document.createElement('div');
            bar.className = `progress-bar ${record.error ? 'bg-danger' : record.token ? 'bg-success' : 'progress-bar-striped progress-bar-animated'}`;
            bar.style.width = `${progress}%`;
            bar.setAttribute('role', 'progressbar');
            bar.setAttribute('aria-label', `Upload ${record.file.name}`);
            bar.setAttribute('aria-valuemin', '0');
            bar.setAttribute('aria-valuemax', '100');
            bar.setAttribute('aria-valuenow', String(progress));
            bar.setAttribute('aria-valuetext', record.error ? `Upload gagal: ${record.error}` : record.token ? 'Upload selesai' : progress === 100 ? 'Memproses file di server' : `${progress}%`);
            track.appendChild(bar);
            row.appendChild(track);
            state.status.appendChild(row);
        });
    }

    function upload(record, state) {
        record.error = null;
        record.percent = 0;
        const xhr = new XMLHttpRequest();
        record.xhr = xhr;
        xhr.open('POST', endpoint);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf());
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.addEventListener('progress', (event) => {
            record.percent = event.lengthComputable ? Math.round(event.loaded / event.total * 100) : 0;
            render(state);
        });
        xhr.addEventListener('load', () => {
            if (record.removed) return;
            let payload = {};
            try { payload = JSON.parse(xhr.responseText); } catch (_) { /* Server dapat mengembalikan halaman error. */ }
            if (xhr.status >= 200 && xhr.status < 300 && payload.token) {
                record.token = payload.token;
            } else {
                record.error = Object.values(payload.errors || {}).flat().join(' ') || payload.message || (xhr.status === 413
                    ? 'Ukuran file melebihi batas server.' : 'Upload gagal. Silakan coba lagi.');
            }
            render(state);
        });
        xhr.addEventListener('error', () => {
            record.error = 'Koneksi terputus. Silakan coba lagi.';
            render(state);
        });
        const data = new FormData();
        data.append('file', record.file);
        xhr.send(data);
        render(state);
    }

    function sync(input) {
        if (!input.form || !input.name) return;
        let state = states.get(input);
        if (!state) {
            const status = document.createElement('div');
            status.className = 'form-text';
            status.setAttribute('aria-live', 'polite');
            (input.closest('.modern-file-field') || input).insertAdjacentElement('afterend', status);
            state = { records: [], status, input, required: input.required };
            states.set(input, state);
        }
        const files = Array.from(input.files || []);
        const previous = state.records;
        const restored = previous.filter((record) => record.restored && (input.multiple || !files.length));
        state.records = restored.concat(files.map((file) => previous.find((record) => record.file === file) || { file, percent: 0 }));
        previous.filter((record) => !state.records.includes(record)).forEach(discard);
        if (previous.some((record) => record.restored) && !restored.length) input.required = state.required;
        state.records.filter((record) => !record.xhr && !record.token).forEach((record) => upload(record, state));
        render(state);
    }

    function inputs(form) {
        return Array.from(form.elements).filter((input) => input.type === 'file' && input.name && !input.matches(':disabled'));
    }

    // FileList tetap tersedia untuk preview dan daftar file; hanya payload simpan yang diganti token.
    document.addEventListener('formdata', (event) => {
        let index = 0;
        inputs(event.target).forEach((input) => {
            event.formData.delete(input.name);
            const records = states.get(input)?.records || [];
            records.forEach((record, fileIndex) => {
                if (!record.token) return;
                const name = input.name.endsWith('[]') ? `${input.name.slice(0, -2)}[${fileIndex}]` : input.name;
                const field = name.replace(/\[([^\]]+)\]/g, '.$1');
                event.formData.append(`_uploaded_files[${index}][field]`, field);
                event.formData.append(`_uploaded_files[${index}][token]`, record.token);
                index++;
            });
        });
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('input[type="file"]')) sync(event.target);
    });
    document.addEventListener('multi-file:sync', (event) => sync(event.target));

    document.addEventListener('submit', (event) => {
        const fileInputs = inputs(event.target);
        fileInputs.forEach(sync);
        const unfinished = fileInputs.find((input) => states.get(input)?.records.some((record) => !record.token));
        if (!unfinished) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const state = states.get(unfinished);
        render(state);
        const message = document.createElement('div');
        message.className = 'text-danger';
        message.textContent = 'Tunggu upload selesai sebelum menyimpan. Jika gagal, klik Coba lagi.';
        state.status.prepend(message);
        state.status.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }, true);

    document.addEventListener('reset', (event) => {
        inputs(event.target).forEach((input) => {
            const state = states.get(input);
            if (!state) return;
            state.records.forEach(discard);
            state.records = [];
            input.required = state.required;
            render(state);
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        const stored = document.getElementById('staged-upload-input');
        if (!stored) return;
        let restored;
        try { restored = JSON.parse(stored.textContent); } catch (_) { return; }
        if (!Array.isArray(restored)) return;
        document.querySelectorAll('input[type="file"]').forEach((input) => {
            const field = input.name.replace(/\[([^\]]*)\]/g, (_, key) => key ? `.${key}` : '');
            const matching = restored.filter((record) => input.multiple
                ? record.field.startsWith(`${field}.`) && /^\d+$/.test(record.field.slice(field.length + 1))
                : record.field === field);
            if (!matching.length) return;
            sync(input);
            const state = states.get(input);
            if (!state) return;
            state.records = matching.map((record) => ({ token: record.token, file: { name: record.name || 'File sebelumnya' }, restored: true }));
            input.required = false;
            render(state);
        });
    });

    new MutationObserver(() => {
        document.querySelectorAll('input[type="file"]').forEach((input) => {
            const state = states.get(input);
            if (!state) return;
            if (!state.status.isConnected) (input.closest('.modern-file-field') || input).insertAdjacentElement('afterend', state.status);
        });
        states.forEach((state, input) => {
            if (input.isConnected) return;
            state.records.forEach(discard);
            state.status.remove();
            states.delete(input);
        });
    }).observe(document.body, { childList: true, subtree: true });
})();
