(function () {
    'use strict';

    var monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    var dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    var activeDateInput = null;
    var viewDate = new Date();
    var calendar = null;

    function modalFormState(element) {
        var form = element && element.querySelector('form');
        if (!form) return null;

        return Array.from(form.elements).filter(function (field) {
            return field.name && !field.disabled && field.name !== '_token' && field.name !== '_method';
        }).map(function (field) {
            if (field.type === 'checkbox' || field.type === 'radio') return field.name + ':' + field.type + ':' + field.checked + ':' + field.value;
            if (field.type === 'file') {
                return field.name + ':file:' + Array.from(field.files || []).map(function (file) {
                    return file.name + ':' + file.size + ':' + file.lastModified;
                }).join('|');
            }
            if (field.tagName === 'SELECT' && field.multiple) {
                return field.name + ':select:' + Array.from(field.selectedOptions).map(function (option) { return option.value; }).join('|');
            }
            return field.name + ':' + field.type + ':' + field.value;
        }).join('\n');
    }

    function rememberModalState(element) {
        element._robustInitialFormState = modalFormState(element);
    }

    function modalHasUnsavedChanges(element) {
        var current = modalFormState(element);
        return current !== null && element._robustInitialFormState !== undefined && current !== element._robustInitialFormState;
    }

    function guardModalClose(event) {
        var element = event.target;
        if (!element.classList || !element.classList.contains('modal')) return;
        if (element._robustAllowClose) {
            element._robustAllowClose = false;
            return;
        }
        if (!modalHasUnsavedChanges(element)) return;

        event.preventDefault();
        if (!window.confirm('Data yang sudah diisi belum disimpan. Tutup popup dan buang perubahan?')) return;
        element._robustAllowClose = true;
        window.setTimeout(function () { modalInstance(element).hide(); }, 0);
    }

    function modalInstance(element) {
        if (window.bootstrap && window.bootstrap.Modal) return window.bootstrap.Modal.getOrCreateInstance(element);
        return {
            show: function () {
                if (!element || element.classList.contains('show')) return;
                element.dispatchEvent(new CustomEvent('show.bs.modal', {bubbles: true}));
                element.style.display = 'block';
                element.removeAttribute('aria-hidden');
                element.setAttribute('aria-modal', 'true');
                element.setAttribute('role', 'dialog');
                element.classList.add('show');
                document.body.classList.add('modal-open');
                element._robustOpener = document.activeElement;
                var backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show robust-modal-backdrop';
                backdrop.addEventListener('click', function () { modalInstance(element).hide(); });
                document.body.appendChild(backdrop);
                element._robustBackdrop = backdrop;
                element.dispatchEvent(new CustomEvent('shown.bs.modal', {bubbles: true}));
                element.querySelector('[data-bs-dismiss="modal"], input:not([type="hidden"]), select, textarea, button')?.focus();
            },
            hide: function () {
                if (!element || !element.classList.contains('show')) return;
                var hideEvent = new CustomEvent('hide.bs.modal', {bubbles: true, cancelable: true});
                element.dispatchEvent(hideEvent);
                if (hideEvent.defaultPrevented) return;
                element.classList.remove('show');
                element.style.display = 'none';
                element.setAttribute('aria-hidden', 'true');
                element.removeAttribute('aria-modal');
                element.removeAttribute('role');
                element._robustBackdrop?.remove();
                element._robustBackdrop = null;
                if (!document.querySelector('.modal.show')) document.body.classList.remove('modal-open');
                element.dispatchEvent(new CustomEvent('hidden.bs.modal', {bubbles: true}));
                element._robustOpener?.focus();
                element._robustOpener = null;
            }
        };
    }

    window.RobustModal = {getOrCreateInstance: modalInstance};

    function parseIso(value) {
        var match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return null;
        return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
    }

    function iso(date) {
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
    }

    function displayDate(value) {
        var date = parseIso(value);
        if (!date) return 'Pilih tanggal';
        return new Intl.DateTimeFormat('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}).format(date);
    }

    function ensureCalendar() {
        if (calendar) return calendar;
        calendar = document.createElement('div');
        calendar.className = 'modern-calendar';
        calendar.setAttribute('role', 'dialog');
        calendar.setAttribute('aria-modal', 'false');
        calendar.setAttribute('aria-label', 'Pilih tanggal');
        calendar.hidden = true;
        calendar.innerHTML = '<div class="modern-calendar-head"><button type="button" data-cal-prev aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></button><strong data-cal-title></strong><button type="button" data-cal-next aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></button></div><div class="modern-calendar-week"></div><div class="modern-calendar-days"></div><div class="modern-calendar-foot"><button type="button" class="btn btn-link btn-sm" data-cal-clear>Hapus</button><button type="button" class="btn btn-soft btn-sm" data-cal-today>Hari ini</button></div>';
        calendar.querySelector('.modern-calendar-week').innerHTML = dayNames.map(function (day) { return '<span>' + day + '</span>'; }).join('');
        calendar.querySelector('[data-cal-prev]').addEventListener('click', function () { viewDate.setMonth(viewDate.getMonth() - 1); renderCalendar(); });
        calendar.querySelector('[data-cal-next]').addEventListener('click', function () { viewDate.setMonth(viewDate.getMonth() + 1); renderCalendar(); });
        calendar.querySelector('[data-cal-today]').addEventListener('click', function () { selectDate(new Date()); });
        calendar.querySelector('[data-cal-clear]').addEventListener('click', function () {
            if (!activeDateInput || activeDateInput.required) return;
            activeDateInput.value = '';
            syncDateButton(activeDateInput);
            activeDateInput.dispatchEvent(new Event('change', {bubbles: true}));
            closeCalendar();
        });
        document.body.appendChild(calendar);
        return calendar;
    }

    function renderCalendar() {
        var selected = parseIso(activeDateInput && activeDateInput.value);
        calendar.querySelector('[data-cal-title]').textContent = monthNames[viewDate.getMonth()] + ' ' + viewDate.getFullYear();
        calendar.querySelector('[data-cal-clear]').hidden = Boolean(activeDateInput && activeDateInput.required);
        var days = calendar.querySelector('.modern-calendar-days');
        days.innerHTML = '';
        var first = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
        var offset = (first.getDay() + 6) % 7;
        var cursor = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1 - offset);
        var todayIso = iso(new Date());
        for (var i = 0; i < 42; i += 1) {
            var date = new Date(cursor);
            var button = document.createElement('button');
            button.type = 'button';
            button.textContent = date.getDate();
            button.dataset.date = iso(date);
            button.className = 'modern-calendar-day';
            if (date.getMonth() !== viewDate.getMonth()) button.classList.add('is-outside');
            if (button.dataset.date === todayIso) button.classList.add('is-today');
            if (selected && button.dataset.date === iso(selected)) {
                button.classList.add('is-selected');
                button.setAttribute('aria-current', 'date');
            }
            var min = activeDateInput && activeDateInput.min;
            var max = activeDateInput && activeDateInput.max;
            if ((min && button.dataset.date < min) || (max && button.dataset.date > max)) button.disabled = true;
            button.addEventListener('click', function (event) { selectDate(parseIso(event.currentTarget.dataset.date)); });
            days.appendChild(button);
            cursor.setDate(cursor.getDate() + 1);
        }
    }

    function positionCalendar(trigger) {
        var rect = trigger.getBoundingClientRect();
        calendar.style.left = '0px';
        calendar.style.top = '0px';
        var calendarRect = calendar.getBoundingClientRect();
        var left = Math.min(rect.left, window.innerWidth - calendarRect.width - 12);
        var top = rect.bottom + 8;
        if (top + calendarRect.height > window.innerHeight - 12) top = Math.max(12, rect.top - calendarRect.height - 8);
        calendar.style.left = Math.max(12, left) + 'px';
        calendar.style.top = top + 'px';
    }

    function openCalendar(input) {
        activeDateInput = input;
        var selected = parseIso(input.value);
        viewDate = selected || new Date();
        ensureCalendar().hidden = false;
        renderCalendar();
        positionCalendar(input._modernDateButton);
        input._modernDateButton.setAttribute('aria-expanded', 'true');
        calendar.querySelector('.is-selected, .is-today, .modern-calendar-day:not(.is-outside)')?.focus();
    }

    function closeCalendar() {
        if (!calendar || calendar.hidden) return;
        calendar.hidden = true;
        if (activeDateInput && activeDateInput._modernDateButton) activeDateInput._modernDateButton.setAttribute('aria-expanded', 'false');
        activeDateInput = null;
    }

    function selectDate(date) {
        if (!activeDateInput || !date) return;
        activeDateInput.value = iso(date);
        syncDateButton(activeDateInput);
        activeDateInput.dispatchEvent(new Event('input', {bubbles: true}));
        activeDateInput.dispatchEvent(new Event('change', {bubbles: true}));
        var trigger = activeDateInput._modernDateButton;
        closeCalendar();
        trigger.focus();
    }

    function syncDateButton(input) {
        if (!input._modernDateButton) return;
        var label = input._modernDateButton.querySelector('[data-date-label]');
        label.textContent = displayDate(input.value);
        input._modernDateButton.classList.toggle('is-empty', !input.value);
    }

    function bindDate(input) {
        if (input.dataset.modernDateBound === 'true' || input.disabled || input.readOnly) return;
        input.dataset.modernDateBound = 'true';
        var wrapper = document.createElement('div');
        wrapper.className = 'modern-date-field';
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'modern-date-trigger';
        button.setAttribute('aria-haspopup', 'dialog');
        button.setAttribute('aria-expanded', 'false');
        button.innerHTML = '<span data-date-label></span><i class="bi bi-calendar3" aria-hidden="true"></i>';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.append(input, button);
        input.classList.add('modern-date-native');
        input.setAttribute('tabindex', '-1');
        input.setAttribute('aria-hidden', 'true');
        input._modernDateButton = button;
        syncDateButton(input);
        button.addEventListener('click', function () {
            if (activeDateInput === input && calendar && !calendar.hidden) closeCalendar();
            else openCalendar(input);
        });
        input.addEventListener('change', function () { syncDateButton(input); });
        input.addEventListener('invalid', function (event) {
            event.preventDefault();
            openCalendar(input);
            button.focus();
        });
    }

    function fileSummary(input) {
        var files = Array.from(input.files || []);
        if (!files.length) return {title: 'Pilih atau jatuhkan file', detail: 'Belum ada file dipilih'};
        var total = files.reduce(function (sum, file) { return sum + file.size; }, 0);
        var size = total < 1048576 ? Math.max(1, Math.round(total / 1024)) + ' KB' : (total / 1048576).toFixed(1) + ' MB';
        return {title: files.length === 1 ? files[0].name : files.length + ' file dipilih', detail: size};
    }

    function syncFile(input) {
        if (!input._modernFileDropzone) return;
        var summary = fileSummary(input);
        input._modernFileDropzone.querySelector('[data-file-title]').textContent = summary.title;
        input._modernFileDropzone.querySelector('[data-file-detail]').textContent = summary.detail;
        input._modernFileDropzone.classList.toggle('has-files', Boolean(input.files && input.files.length));
    }

    function bindFile(input) {
        if (input.dataset.modernFileBound === 'true' || input.dataset.fileUi === 'off' || input.classList.contains('d-none')) return;
        input.dataset.modernFileBound = 'true';
        var wrapper = document.createElement('div');
        wrapper.className = 'modern-file-field';
        var dropzone = document.createElement('button');
        dropzone.type = 'button';
        dropzone.className = 'modern-file-dropzone';
        dropzone.innerHTML = '<span class="modern-file-icon"><i class="bi bi-cloud-arrow-up"></i></span><span class="modern-file-copy"><strong data-file-title>Pilih atau jatuhkan file</strong><small data-file-detail>Belum ada file dipilih</small></span><span class="modern-file-action">Pilih File</span>';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.append(input, dropzone);
        input.classList.add('modern-file-native');
        input.setAttribute('tabindex', '-1');
        input.setAttribute('aria-hidden', 'true');
        input._modernFileDropzone = dropzone;
        syncFile(input);
        dropzone.addEventListener('click', function () { input.click(); });
        input.addEventListener('change', function () { syncFile(input); });
        input.addEventListener('multi-file:sync', function () { syncFile(input); });
        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) { event.preventDefault(); dropzone.classList.add('is-dragging'); });
        });
        ['dragleave', 'drop'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) { event.preventDefault(); dropzone.classList.remove('is-dragging'); });
        });
        dropzone.addEventListener('drop', function (event) {
            if (!event.dataTransfer || !event.dataTransfer.files.length) return;
            try {
                var transfer = new DataTransfer();
                Array.from(event.dataTransfer.files).slice(0, input.multiple ? undefined : 1).forEach(function (file) { transfer.items.add(file); });
                input.files = transfer.files;
                input.dispatchEvent(new Event('change', {bubbles: true}));
            } catch (error) {
                input.click();
            }
        });
    }

    function bindControls(scope) {
        var root = scope || document;
        var dateInputs = root.matches && root.matches('input[type="date"]') ? [root] : Array.from(root.querySelectorAll('input[type="date"]'));
        var fileInputs = root.matches && root.matches('input[type="file"]') ? [root] : Array.from(root.querySelectorAll('input[type="file"]'));
        dateInputs.forEach(bindDate);
        fileInputs.forEach(bindFile);
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindControls(document);
        document.querySelectorAll('.modal').forEach(function (modal) {
            modal.addEventListener('shown.bs.modal', function () { rememberModalState(modal); });
            modal.addEventListener('hide.bs.modal', guardModalClose);
            modal.addEventListener('submit', function () { modal._robustAllowClose = true; });
        });
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) { if (node.nodeType === 1) bindControls(node); });
            });
        }).observe(document.body, {childList: true, subtree: true});

        if (!window.bootstrap) {
            document.addEventListener('click', function (event) {
                var opener = event.target.closest('[data-bs-toggle="modal"]');
                if (opener) {
                    var target = document.querySelector(opener.getAttribute('data-bs-target'));
                    if (target) { event.preventDefault(); modalInstance(target).show(); }
                    return;
                }
                var closer = event.target.closest('[data-bs-dismiss="modal"]');
                if (closer) {
                    var modal = closer.closest('.modal');
                    if (modal) { event.preventDefault(); modalInstance(modal).hide(); }
                }
            });
        }
    });
    document.addEventListener('click', function (event) {
        if (!calendar || calendar.hidden || calendar.contains(event.target) || event.target.closest('.modern-date-trigger')) return;
        closeCalendar();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && calendar && !calendar.hidden) {
            var trigger = activeDateInput && activeDateInput._modernDateButton;
            closeCalendar();
            trigger?.focus();
            return;
        }
        if (event.key === 'Escape' && !window.bootstrap) {
            var openModal = document.querySelector('.modal.show');
            if (openModal) modalInstance(openModal).hide();
        }
        if (event.key === 'Tab' && !window.bootstrap) {
            var currentModal = document.querySelector('.modal.show');
            if (!currentModal) return;
            var focusable = Array.from(currentModal.querySelectorAll('button:not([disabled]), [href], input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')).filter(function (el) { return el.offsetParent !== null; });
            if (!focusable.length) return;
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    window.addEventListener('resize', function () { if (activeDateInput) positionCalendar(activeDateInput._modernDateButton); });
    window.addEventListener('scroll', function () { if (activeDateInput) positionCalendar(activeDateInput._modernDateButton); }, true);
})();
