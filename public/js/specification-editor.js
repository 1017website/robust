(function () {
    'use strict';

    const isNumber = value => /^-?[\d.,\s]+$/.test(String(value ?? '').trim());

    function parseBreakdown(line) {
        const parts = line.trim().replace(/^@\s*/, '').split('|').map(part => part.trim());
        if (parts.length === 3 && isNumber(parts[0])) {
            return {type: 'breakdown', label: '', qty: parts[0], unit: parts[1], price: parts[2]};
        }
        if (parts.length === 3 && isNumber(parts[1])) {
            return {type: 'breakdown', label: parts[0], qty: parts[1], unit: parts[2], price: ''};
        }
        if (parts.length === 4 && isNumber(parts[1])) {
            return {type: 'breakdown', label: parts[0], qty: parts[1], unit: parts[2], price: parts[3]};
        }
        return null;
    }

    function parse(value) {
        const sections = [];
        let current = null;
        String(value ?? '').split(/\r?\n/).forEach(rawLine => {
            const line = rawLine.trim();
            if (!line) return;
            const sectionMatch = line.match(/^\[(.+)]$/);
            if (sectionMatch) {
                current = {title: sectionMatch[1].trim(), rows: []};
                sections.push(current);
                return;
            }
            if (!current) {
                current = {title: 'Spesifikasi', rows: []};
                sections.push(current);
            }
            if (line.startsWith('@')) {
                const breakdown = parseBreakdown(line);
                if (breakdown) {
                    current.rows.push(breakdown);
                    return;
                }
            }
            const colon = line.indexOf(':');
            current.rows.push({
                type: 'detail',
                label: colon >= 0 ? line.slice(0, colon).trim() : '',
                value: colon >= 0 ? line.slice(colon + 1).trim() : line,
            });
        });
        if (!sections.length) {
            sections.push({
                title: 'General',
                rows: [{type: 'detail', label: 'Type', value: ''}],
            });
        }
        return sections;
    }

    function serialize(sections) {
        const lines = [];
        sections.forEach(section => {
            const title = String(section.title ?? '').trim() || 'Bagian';
            lines.push(`[${title}]`);
            section.rows.forEach(row => {
                if (row.type === 'breakdown') {
                    const label = String(row.label ?? '').trim();
                    const qty = String(row.qty ?? '').trim();
                    const unit = String(row.unit ?? '').trim();
                    const price = String(row.price ?? '').trim();
                    if (label) {
                        lines.push(price
                            ? `@ ${label} | ${qty || 0} | ${unit} | ${price}`
                            : `@ ${label} | ${qty || 0} | ${unit}`);
                    } else {
                        lines.push(`@ ${qty || 0} | ${unit} | ${price}`);
                    }
                    return;
                }
                const label = String(row.label ?? '').trim();
                const value = String(row.value ?? '').trim();
                if (label || value) lines.push(label ? `${label}: ${value}` : value);
            });
        });
        return lines.join('\n');
    }

    function input(className, placeholder, value, type = 'text') {
        const el = document.createElement('input');
        el.type = type;
        el.className = `form-control form-control-sm ${className}`;
        el.placeholder = placeholder;
        el.value = value ?? '';
        return el;
    }

    function actionButton(icon, label, className = 'btn btn-soft btn-sm') {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.innerHTML = `<i class="bi ${icon}"></i><span>${label}</span>`;
        return button;
    }

    function mount(root) {
        if (!root || root.dataset.specificationMounted === '1') return root?._specificationEditor;
        root.dataset.specificationMounted = '1';
        const raw = root.querySelector('[data-spec-raw]');
        const sectionsRoot = root.querySelector('[data-spec-sections]');
        let state = parse(raw?.value);

        function syncRaw() {
            if (raw) raw.value = serialize(state);
            root.dispatchEvent(new CustomEvent('specification:change', {
                bubbles: true,
                detail: {value: raw?.value ?? ''},
            }));
        }

        function renderRow(sectionIndex, rowIndex, row) {
            const rowEl = document.createElement('div');
            rowEl.className = `spec-editor-row is-${row.type}`;

            if (row.type === 'breakdown') {
                const badge = document.createElement('span');
                badge.className = 'spec-editor-row-badge';
                badge.textContent = 'Qty';
                const label = input('spec-row-label', 'Nama rincian (opsional)', row.label);
                const qty = input('spec-row-qty', 'Qty', row.qty, 'text');
                qty.inputMode = 'decimal';
                const unit = input('spec-row-unit', 'UoM', row.unit);
                const price = input('spec-row-price', 'Harga satuan (opsional)', row.price, 'text');
                price.inputMode = 'numeric';
                rowEl.append(badge, label, qty, unit, price);
                [
                    [label, 'label'],
                    [qty, 'qty'],
                    [unit, 'unit'],
                    [price, 'price'],
                ].forEach(([el, key]) => el.addEventListener('input', () => {
                    state[sectionIndex].rows[rowIndex][key] = el.value;
                    syncRaw();
                }));
            } else {
                const badge = document.createElement('span');
                badge.className = 'spec-editor-row-badge';
                badge.textContent = 'Detail';
                const label = input('spec-row-label', 'Label, mis. Material', row.label);
                const value = input('spec-row-value', 'Nilai spesifikasi', row.value);
                rowEl.append(badge, label, value);
                label.addEventListener('input', () => {
                    state[sectionIndex].rows[rowIndex].label = label.value;
                    syncRaw();
                });
                value.addEventListener('input', () => {
                    state[sectionIndex].rows[rowIndex].value = value.value;
                    syncRaw();
                });
            }

            const remove = actionButton('bi-x-lg', 'Hapus', 'btn btn-link btn-sm text-danger spec-editor-remove');
            remove.addEventListener('click', () => {
                state[sectionIndex].rows.splice(rowIndex, 1);
                render();
                syncRaw();
            });
            rowEl.append(remove);
            return rowEl;
        }

        function renderSection(section, sectionIndex) {
            const card = document.createElement('section');
            card.className = 'spec-editor-section';

            const head = document.createElement('div');
            head.className = 'spec-editor-section-head';
            const titleWrap = document.createElement('div');
            titleWrap.className = 'spec-editor-section-title';
            titleWrap.innerHTML = '<i class="bi bi-grip-vertical"></i>';
            const title = input('spec-section-title', 'Nama bagian, mis. General', section.title);
            title.addEventListener('input', () => {
                state[sectionIndex].title = title.value;
                syncRaw();
            });
            titleWrap.append(title);
            const removeSection = actionButton('bi-trash3', 'Hapus bagian', 'btn btn-link btn-sm text-danger');
            removeSection.addEventListener('click', () => {
                state.splice(sectionIndex, 1);
                if (!state.length) state = parse('');
                render();
                syncRaw();
            });
            head.append(titleWrap, removeSection);

            const rows = document.createElement('div');
            rows.className = 'spec-editor-row-list';
            section.rows.forEach((row, rowIndex) => rows.append(renderRow(sectionIndex, rowIndex, row)));

            const actions = document.createElement('div');
            actions.className = 'spec-editor-section-actions';
            const addDetail = actionButton('bi-plus-circle', 'Tambah Detail');
            addDetail.addEventListener('click', () => {
                state[sectionIndex].rows.push({type: 'detail', label: '', value: ''});
                render();
                syncRaw();
            });
            const addBreakdown = actionButton('bi-calculator', 'Tambah Qty / Harga');
            addBreakdown.addEventListener('click', () => {
                state[sectionIndex].rows.push({type: 'breakdown', label: '', qty: '1', unit: 'pcs', price: ''});
                render();
                syncRaw();
            });
            actions.append(addDetail, addBreakdown);
            card.append(head, rows, actions);
            return card;
        }

        function render() {
            sectionsRoot.innerHTML = '';
            state.forEach((section, index) => sectionsRoot.append(renderSection(section, index)));
        }

        root.querySelector('[data-spec-add-section]')?.addEventListener('click', () => {
            state.push({title: 'Bagian Baru', rows: [{type: 'detail', label: '', value: ''}]});
            render();
            syncRaw();
        });

        raw?.addEventListener('change', () => {
            state = parse(raw.value);
            render();
            syncRaw();
        });

        const api = {
            getValue: () => serialize(state),
            setValue: value => {
                state = parse(value);
                render();
                syncRaw();
            },
            getSections: () => state,
        };
        root._specificationEditor = api;
        render();
        syncRaw();
        return api;
    }

    function attach(container, options = {}) {
        const root = document.createElement('div');
        root.className = `structured-spec-editor${options.compact ? ' is-compact' : ''}`;
        root.dataset.specificationEditor = '';
        root.innerHTML = `
            <div class="spec-editor-heading">
                <div><strong>${options.label || 'Spesifikasi'}</strong><small>Susun per bagian; format export dibuat otomatis.</small></div>
                <button type="button" class="btn btn-soft btn-sm" data-spec-add-section><i class="bi bi-plus-lg me-1"></i>Tambah Bagian</button>
            </div>
            <div class="spec-editor-sections" data-spec-sections></div>
            <details class="spec-editor-raw-details">
                <summary><i class="bi bi-code-slash me-1"></i>Mode teks lanjutan</summary>
                <textarea rows="7" class="form-control form-control-sm spec-editor-raw" data-spec-raw></textarea>
                <small class="text-muted-2">Perubahan mode teks dibaca kembali ketika kolom selesai diedit.</small>
            </details>`;
        const raw = root.querySelector('[data-spec-raw]');
        if (options.name) raw.name = options.name;
        raw.value = options.value ?? '';
        container.replaceChildren(root);
        return mount(root);
    }

    window.StructuredSpecificationEditor = {attach, mount, parse, serialize};
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-specification-editor]').forEach(mount);
    });
})();
