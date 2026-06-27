document.addEventListener('DOMContentLoaded', function () {
    // Sidebar toggle (mobile)
    var t = document.getElementById('sidebarToggle');
    var sb = document.getElementById('sidebar');
    if (t && sb) t.addEventListener('click', function () { sb.classList.toggle('show'); });

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
//  - [data-rupiah] / .input-rupiah  -> ribuan titik (1.000.000)
//  - [data-qty] / .input-qty        -> angka desimal, ribuan titik
function bindNumberInputs(scope) {
    (scope || document).querySelectorAll('[data-rupiah], .input-rupiah').forEach(function (el) {
        formatRupiahEl(el);
        el.addEventListener('input', function () { formatRupiahEl(el); });
        el.addEventListener('blur', function () { formatRupiahEl(el); });
    });
    (scope || document).querySelectorAll('[data-qty], .input-qty').forEach(function (el) {
        el.addEventListener('input', function () {
            // izinkan angka & satu titik/koma desimal
            var v = el.value.replace(/[^0-9.,]/g, '').replace(',', '.');
            var parts = v.split('.');
            if (parts.length > 2) v = parts[0] + '.' + parts.slice(1).join('');
            el.value = v;
        });
    });
}

function formatRupiahEl(el) {
    var v = (el.value || '').trim();
    // buang bagian desimal (mis. "150000000.00" -> "150000000") sebelum format ribuan
    v = v.replace(',', '.');
    if (v.indexOf('.') !== -1) v = v.split('.')[0];
    var raw = v.replace(/[^0-9]/g, '');
    el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
    el.dataset.raw = raw;
}

// Ambil nilai numeric murni dari input rupiah (hapus titik)
function rupiahValue(el) {
    return parseInt((el.value || '').replace(/[^0-9]/g, ''), 10) || 0;
}

// Helper: render chart
function robustChart(id, type, labels, data, colors) {
    var el = document.getElementById(id);
    if (!el || !window.Chart) return;
    return new Chart(el, {
        type: type,
        data: { labels: labels, datasets: [{ data: data, backgroundColor: colors || '#1d6fe0', borderRadius: 6, borderWidth: 0 }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: type === 'doughnut' || type === 'pie' } },
            scales: type === 'bar' ? { y: { beginAtZero: true, ticks: { precision: 0 } } } : {}
        }
    });
}
