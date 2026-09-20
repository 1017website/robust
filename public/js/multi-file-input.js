/**
 * Input berkas yang bisa dipilih bertahap.
 *
 * Input file bawaan browser selalu menimpa pilihan sebelumnya setiap kali dialog
 * "Choose Files" dibuka. Skrip ini menampung pilihan lama, menggabungkannya dengan
 * pilihan baru, lalu menulis ulang FileList lewat DataTransfer sehingga seluruh
 * berkas tetap ikut terkirim saat form disubmit.
 *
 * Pemakaian: tambahkan atribut data-multi-file pada <input type="file" multiple>.
 * Daftar berkas terpilih dirender otomatis tepat di bawah input; beri
 * data-multi-file-list="off" bila halaman sudah punya panel daftarnya sendiri.
 */
(function () {
    'use strict';

    function formatBytes(bytes) {
        if (!Number.isFinite(bytes) || bytes <= 0) return '0 B';

        const units = ['B', 'KB', 'MB', 'GB'];
        const unitIndex = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        const value = bytes / (1024 ** unitIndex);

        return value.toLocaleString('id-ID', { maximumFractionDigits: unitIndex === 0 ? 0 : 1 }) + ' ' + units[unitIndex];
    }

    function signature(file) {
        return file.name + '|' + file.size + '|' + file.lastModified;
    }

    function bind(input) {
        if (input.dataset.multiFileBound === '1') return;
        input.dataset.multiFileBound = '1';

        const showList = input.dataset.multiFileList !== 'off';
        const list = document.createElement('div');
        list.className = 'mt-2 d-none';
        if (showList) (input.closest('.modern-file-field') || input).insertAdjacentElement('afterend', list);

        const files = [];

        function sync() {
            const transfer = new DataTransfer();
            files.forEach(function (file) { transfer.items.add(file); });
            input.files = transfer.files;
            render();
            input.dispatchEvent(new CustomEvent('multi-file:sync', {bubbles: true}));
        }

        function render() {
            if (!showList) return;

            list.replaceChildren();

            if (!files.length) {
                list.classList.add('d-none');
                return;
            }

            const total = files.reduce(function (sum, file) { return sum + file.size; }, 0);
            const head = document.createElement('div');
            head.className = 'small fw-bold mb-1';
            head.textContent = files.length + ' file dipilih (' + formatBytes(total) + ')';
            list.appendChild(head);

            const group = document.createElement('ul');
            group.className = 'list-group list-group-flush';

            files.forEach(function (file, index) {
                const item = document.createElement('li');
                item.className = 'list-group-item d-flex align-items-center justify-content-between gap-2 px-0 py-1 border-0 bg-transparent';

                const label = document.createElement('span');
                label.className = 'small text-truncate';
                label.textContent = file.name + ' — ' + formatBytes(file.size);
                item.appendChild(label);

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn-sm btn-soft flex-shrink-0';
                remove.textContent = 'Hapus';
                remove.setAttribute('aria-label', 'Hapus ' + file.name + ' dari daftar unggahan');
                remove.addEventListener('click', function () {
                    files.splice(index, 1);
                    sync();
                });
                item.appendChild(remove);

                group.appendChild(item);
            });

            list.appendChild(group);
            list.classList.remove('d-none');
        }

        input.addEventListener('change', function () {
            const picked = Array.from(input.files || []);
            if (!picked.length) return;

            const known = new Set(files.map(signature));
            picked.forEach(function (file) {
                if (known.has(signature(file))) return;
                known.add(signature(file));
                files.push(file);
            });

            sync();
        });
    }

    function init(root) {
        (root || document).querySelectorAll('input[type="file"][data-multi-file]').forEach(bind);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(document); });
    } else {
        init(document);
    }
})();
