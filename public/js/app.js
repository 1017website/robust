document.addEventListener('DOMContentLoaded', function () {
    // Sidebar drawer (mobile/tablet)
    var t = document.getElementById('sidebarToggle');
    var sb = document.getElementById('sidebar');
    var sidebarClose = document.getElementById('sidebarClose');
    var backdrop = document.getElementById('sidebarBackdrop');
    function setSidebar(open) {
        if (!sb) return;
        sb.classList.toggle('show', open);
        if (backdrop) backdrop.classList.toggle('show', open);
        document.body.classList.toggle('sidebar-open', open);
        if (t) t.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    if (t && sb) t.addEventListener('click', function () { setSidebar(!sb.classList.contains('show')); });
    if (sidebarClose) sidebarClose.addEventListener('click', function () {
        setSidebar(false);
        if (t) t.focus();
    });
    if (backdrop) backdrop.addEventListener('click', function () { setSidebar(false); });
    if (sb) sb.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () { setSidebar(false); });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setSidebar(false);
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1200) setSidebar(false);
    });

    // Dropdown aksi tabel perlu keluar dari container overflow agar seluruh menu terlihat.
    document.addEventListener('shown.bs.dropdown', function (e) {
        var toggle = e.target.closest('[data-bs-toggle="dropdown"]');
        if (!toggle || !toggle.closest('.table-wrap')) return;
        var menu = toggle.parentElement && toggle.parentElement.querySelector(':scope > .dropdown-menu');
        if (!menu) return;

        menu._tableDropdownHost = menu.parentNode;
        toggle._tableDropdownMenu = menu;
        document.body.appendChild(menu);
        menu.classList.add('table-dropdown-floating');

        requestAnimationFrame(function () {
            var triggerRect = toggle.getBoundingClientRect();
            var menuRect = menu.getBoundingClientRect();
            var gutter = 8;
            var left = Math.min(triggerRect.right - menuRect.width, window.innerWidth - menuRect.width - gutter);
            left = Math.max(gutter, left);
            var top = triggerRect.bottom + 5;
            if (top + menuRect.height > window.innerHeight - gutter) {
                top = Math.max(gutter, triggerRect.top - menuRect.height - 5);
            }
            menu.style.setProperty('left', left + 'px', 'important');
            menu.style.setProperty('top', top + 'px', 'important');
        });
    });

    document.addEventListener('hidden.bs.dropdown', function (e) {
        var toggle = e.target.closest('[data-bs-toggle="dropdown"]');
        var menu = toggle && toggle._tableDropdownMenu;
        if (!menu || !menu._tableDropdownHost) return;
        menu._tableDropdownHost.appendChild(menu);
        menu.classList.remove('table-dropdown-floating');
        menu.style.removeProperty('left');
        menu.style.removeProperty('top');
        delete toggle._tableDropdownMenu;
        delete menu._tableDropdownHost;
    });

    // CSRF for fetch
    var token = document.querySelector('meta[name="csrf-token"]');
    if (token) window.csrfToken = token.getAttribute('content');

    // ---------- Select2 ----------
    if (window.jQuery && jQuery.fn.select2) {
        initSelect2(document);

        // Re-init Select2 di dalam modal saat modal dibuka,
        // dan set dropdownParent ke modal agar tidak ketutup z-index.
        document.addEventListener('shown.bs.modal', function (e) {
            initSelect2(e.target, jQuery(e.target));
        });
        // Destroy saat modal ditutup agar tidak dobel saat dibuka lagi
        document.addEventListener('hidden.bs.modal', function (e) {
            jQuery(e.target).find('.select2').each(function () {
                if (jQuery(this).hasClass('select2-hidden-accessible')) {
                    jQuery(this).select2('destroy');
                }
            });
        });
    }

    // ---------- Auto-format input ----------
    bindNumberInputs(document);

    // Form menambah baris secara dinamis (item, biaya, termin, dan sejenisnya).
    // Observer menjaga input baru langsung mendapat separator tanpa kode per halaman.
    var numberObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node.nodeType === 1) bindNumberInputs(node);
            });
        });
    });
    numberObserver.observe(document.body, { childList: true, subtree: true });
});

// Inisialisasi Select2 dalam scope tertentu.
// parent: jQuery element untuk dropdownParent (mis. modal). Default: body.
function initSelect2(scope, parent) {
    jQuery(scope).find('.select2').each(function () {
        var $el = jQuery(this);
        if ($el.hasClass('select2-hidden-accessible')) return; // sudah init
        var opts = {
            width: '100%',
            placeholder: $el.data('placeholder') || '— Pilih —',
            allowClear: $el.data('allow-clear') !== undefined,
        };
        // dropdownParent: modal terdekat bila ada, agar dropdown muncul di atas modal
        var $modal = $el.closest('.modal');
        if (parent) opts.dropdownParent = parent;
        else if ($modal.length) opts.dropdownParent = $modal;
        $el.select2(opts);
    });
}

// Format input angka:
//  - [data-rupiah] / .input-rupiah / nama nominal -> ribuan titik (1.000.000)
//  - [data-qty] / .input-qty / nama decimal       -> ribuan titik + koma desimal
function bindNumberInputs(scope) {
    var root = scope || document;
    var inputs = root.matches && root.matches('input')
        ? [root].concat(Array.from(root.querySelectorAll('input')))
        : Array.from(root.querySelectorAll('input'));
    inputs.forEach(function (el) {
        var kind = numberInputKind(el);
        if (!kind || el.dataset.numberBound === 'true') return;

        el.dataset.numberBound = 'true';
        el.dataset.numberKind = kind;
        if (el.type === 'number') el.type = 'text';
        if (!el.getAttribute('inputmode')) el.setAttribute('inputmode', kind === 'currency' ? 'numeric' : 'decimal');

        formatNumberEl(el, kind);
        el.addEventListener('input', function () { formatNumberEl(el, el.dataset.numberKind || kind); });
        el.addEventListener('blur', function () { formatNumberEl(el, el.dataset.numberKind || kind); });
    });

    var forms = root.matches && root.matches('form')
        ? [root].concat(Array.from(root.querySelectorAll('form')))
        : Array.from(root.querySelectorAll('form'));
    forms.forEach(function (form) {
        if (form.dataset.numberSubmitBound === 'true') return;
        form.dataset.numberSubmitBound = 'true';
        form.addEventListener('submit', function () {
            form.querySelectorAll('[data-number-bound="true"]').forEach(function (el) {
                el.value = normalizeNumberValue(el.value, el.dataset.numberKind || 'decimal');
            });
        });
    });
}

function numberInputKind(el) {
    if (!el || el.disabled || el.readOnly) return null;
    var type = (el.getAttribute('type') || 'text').toLowerCase();
    if (['hidden', 'date', 'time', 'datetime-local', 'month', 'week', 'email', 'password', 'file', 'checkbox', 'radio', 'tel', 'url', 'search'].includes(type)) return null;

    var name = ((el.name || '') + ' ' + (el.id || '') + ' ' + (el.className || '')).toLowerCase();
    if (el.hasAttribute('data-rupiah') || el.classList.contains('input-rupiah')) return 'currency';
    if (el.hasAttribute('data-qty') || el.classList.contains('input-qty')) return 'decimal';
    if (/(amount|price|subtotal|grand_total|total_value|project_value|est_value|cost_|biaya|harga|nominal)/i.test(name)) return 'currency';
    if (/(^|[\[\]_\-\s])(qty|quantity|percent|percentage|margin|probability|duration|progress|tax_percent|target_margin)([\]\s_\-]|$)/i.test(name)) return 'decimal';
    if (type === 'number') return 'decimal';
    return null;
}

function formatNumberEl(el, kind) {
    var v = (el.value || '').trim();
    if (v === '') {
        el.dataset.raw = '';
        return;
    }

    var keepDecimalOpen = kind !== 'currency' && /[,.]$/.test(v);
    var raw = normalizeNumberValue(v, kind);
    if (!raw) {
        el.value = '';
        el.dataset.raw = '';
        return;
    }

    el.value = kind === 'currency' ? formatInteger(raw) : formatDecimal(raw, keepDecimalOpen);
    el.dataset.raw = raw;
}

function normalizeNumberValue(value, kind) {
    var v = String(value || '').trim();
    if (!v) return '';
    v = v.replace(/[^0-9,.\-]/g, '');
    var negative = v.startsWith('-') ? '-' : '';
    v = v.replace(/-/g, '');

    if (kind === 'currency') {
        if (v.indexOf(',') !== -1) {
            // Format Indonesia: titik ribuan, koma desimal.
            v = v.split(',')[0].replace(/\./g, '');
        } else {
            var currencyParts = v.split('.');
            var isDatabaseDecimal = currencyParts.length === 2
                && currencyParts[0].length > 3
                && currencyParts[1].length <= 2;
            v = isDatabaseDecimal ? currencyParts[0] : currencyParts.join('');
        }
        return negative + v.replace(/\D/g, '');
    }

    if (v.indexOf(',') !== -1) {
        v = v.replace(/\./g, '').replace(',', '.');
    } else {
        var parts = v.split('.');
        if (parts.length > 2 || (parts.length === 2 && parts[1].length === 3)) {
            v = v.replace(/\./g, '');
        }
    }

    var split = v.split('.');
    var intPart = (split[0] || '').replace(/\D/g, '');
    var decPart = split.slice(1).join('').replace(/\D/g, '').slice(0, 4);
    return negative + intPart + (decPart ? '.' + decPart : '');
}

function formatInteger(raw) {
    var negative = String(raw).startsWith('-') ? '-' : '';
    var intPart = String(raw).replace('-', '').replace(/\D/g, '');
    return intPart ? negative + new Intl.NumberFormat('id-ID').format(parseInt(intPart, 10)) : '';
}

function formatDecimal(raw, keepDecimalOpen) {
    var negative = String(raw).startsWith('-') ? '-' : '';
    var clean = String(raw).replace('-', '');
    var parts = clean.split('.');
    var intPart = parts[0].replace(/\D/g, '');
    var decPart = (parts[1] || '').replace(/\D/g, '').replace(/0+$/, '');
    var formatted = intPart ? new Intl.NumberFormat('id-ID').format(parseInt(intPart, 10)) : '0';
    if (decPart) return negative + formatted + ',' + decPart;
    return negative + formatted + (keepDecimalOpen ? ',' : '');
}

// Ambil nilai numeric murni dari input rupiah/angka yang diformat.
function numberValue(el) {
    return parseFloat(normalizeNumberValue(el.value, el.dataset.numberKind || 'decimal')) || 0;
}

function rupiahValue(el) {
    return parseInt(normalizeNumberValue(el.value, 'currency'), 10) || 0;
}

// Dipakai form edit yang mengisi value lewat JavaScript, bukan dari render Blade.
function setNumberInputValue(el, value) {
    if (!el) return;
    el.value = value ?? '';
    var kind = el.dataset.numberKind || numberInputKind(el);
    if (kind) {
        el.dataset.numberKind = kind;
        formatNumberEl(el, kind);
    }
}

// Helper: render chart
function robustChart(id, type, labels, data, colors) {
    var el = document.getElementById(id);
    if (!el || !window.Chart) return;
    var isRoundChart = type === 'doughnut' || type === 'pie';
    var showLegend = el.dataset.legend === 'true';
    return new Chart(el, {
        type: type,
        data: { labels: labels, datasets: [{ data: data, backgroundColor: colors || '#1d6fe0', borderRadius: isRoundChart ? 3 : 6, borderWidth: 0, hoverOffset: isRoundChart ? 4 : 0 }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: type === 'doughnut' ? '68%' : undefined,
            layout: { padding: isRoundChart ? 4 : 0 },
            plugins: {
                legend: {
                    display: isRoundChart ? showLegend : true,
                    position: 'bottom',
                    labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, padding: 12 }
                }
            },
            scales: type === 'bar' ? { y: { beginAtZero: true, ticks: { precision: 0 } } } : {}
        }
    });
}
