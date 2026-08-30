@props([
    'items' => [],
    'idPrefix' => 'chk',
])
{{-- Editor checklist Request PO: tiap item punya icon hapus, dan item baru bisa ditambah. --}}
<div data-checklist-editor>
    {{-- Penanda agar "semua item dihapus" tidak dianggap "form tanpa checklist". --}}
    <input type="hidden" name="checklist_present" value="1">

    <div data-checklist-rows>
        @foreach($items as $index => $item)
            <div class="d-flex align-items-center gap-2 mb-2" data-checklist-row>
                <input type="hidden" name="checklist[{{ $index }}][key]" value="{{ $item['key'] }}">
                <input type="hidden" name="checklist[{{ $index }}][label]" value="{{ $item['label'] }}">
                <input class="form-check-input mt-0 flex-shrink-0" type="checkbox" name="checklist[{{ $index }}][checked]" value="1" id="{{ $idPrefix }}_{{ $index }}" @checked($item['checked'])>
                <label class="form-check-label small flex-grow-1 mb-0" for="{{ $idPrefix }}_{{ $index }}">{{ $item['label'] }}</label>
                <button type="button" class="btn btn-sm btn-soft text-danger px-2 py-1 flex-shrink-0" data-checklist-remove aria-label="Hapus item checklist" title="Hapus item checklist"><i class="bi bi-trash"></i></button>
            </div>
        @endforeach
    </div>

    <div data-checklist-empty class="form-text fst-italic {{ count($items) ? 'd-none' : '' }}">Semua item checklist sudah dihapus.</div>

    <div class="input-group input-group-sm mt-2">
        <input type="text" class="form-control" maxlength="255" data-checklist-new placeholder="Tambah item checklist...">
        <button type="button" class="btn btn-soft" data-checklist-add aria-label="Tambah item checklist"><i class="bi bi-plus-lg"></i></button>
    </div>
    <div class="form-text">Hapus item yang tidak diperlukan lewat ikon <i class="bi bi-trash"></i>, atau tambahkan item sendiri.</div>
</div>

@once
@push('scripts')
<script>
document.querySelectorAll('[data-checklist-editor]').forEach(function (editor, editorIndex) {
    const rows = editor.querySelector('[data-checklist-rows]');
    const emptyNote = editor.querySelector('[data-checklist-empty]');
    const newInput = editor.querySelector('[data-checklist-new]');
    const addButton = editor.querySelector('[data-checklist-add]');
    let nextIndex = rows.querySelectorAll('[data-checklist-row]').length + 100;

    function syncEmptyNote() {
        if (emptyNote) emptyNote.classList.toggle('d-none', rows.querySelectorAll('[data-checklist-row]').length > 0);
    }

    function addItem(label) {
        const clean = (label || '').trim();
        if (!clean) return;
        const index = nextIndex++;
        const id = `chk_new_${editorIndex}_${index}`;
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2 mb-2';
        row.setAttribute('data-checklist-row', '');
        row.innerHTML = `
            <input type="hidden" name="checklist[${index}][key]" value="">
            <input type="hidden" name="checklist[${index}][label]" data-checklist-label>
            <input class="form-check-input mt-0 flex-shrink-0" type="checkbox" name="checklist[${index}][checked]" value="1" id="${id}">
            <label class="form-check-label small flex-grow-1 mb-0" for="${id}"></label>
            <button type="button" class="btn btn-sm btn-soft text-danger px-2 py-1 flex-shrink-0" data-checklist-remove aria-label="Hapus item checklist" title="Hapus item checklist"><i class="bi bi-trash"></i></button>`;
        row.querySelector('[data-checklist-label]').value = clean;
        row.querySelector('label').textContent = clean;
        rows.appendChild(row);
        if (newInput) newInput.value = '';
        syncEmptyNote();
    }

    rows.addEventListener('click', function (event) {
        const button = event.target.closest('[data-checklist-remove]');
        if (!button) return;
        button.closest('[data-checklist-row]').remove();
        syncEmptyNote();
    });
    addButton?.addEventListener('click', () => addItem(newInput?.value));
    newInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') { event.preventDefault(); addItem(newInput.value); }
    });
});
</script>
@endpush
@endonce
