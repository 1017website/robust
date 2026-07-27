@props([
    'name',
    'value' => '',
    'compact' => false,
    'label' => 'Spesifikasi',
])

<div
    {{ $attributes->class(['structured-spec-editor', 'is-compact' => $compact]) }}
    data-specification-editor
>
    <div class="spec-editor-heading">
        <div>
            <strong>{{ $label }}</strong>
            <small>Susun per bagian dan tambahkan sub-detail bila diperlukan.</small>
        </div>
        <button type="button" class="btn btn-soft btn-sm" data-spec-add-section>
            <i class="bi bi-plus-lg me-1"></i>Tambah Bagian
        </button>
    </div>
    <div class="spec-editor-sections" data-spec-sections></div>
    <details class="spec-editor-raw-details">
        <summary><i class="bi bi-code-slash me-1"></i>Mode teks lanjutan</summary>
        <textarea name="{{ $name }}" rows="7" class="form-control form-control-sm spec-editor-raw" data-spec-raw>{{ $value }}</textarea>
        <small class="text-muted-2">Perubahan pada mode teks akan dibaca kembali ketika kolom selesai diedit.</small>
    </details>
</div>
