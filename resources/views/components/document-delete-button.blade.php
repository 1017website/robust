@props(['document', 'class' => 'btn btn-sm btn-soft text-danger'])
{{-- Tombol hapus file unggahan: dipakai saat file salah unggah atau batal dipakai. --}}
@if($document->canBeDeletedBy(auth()->user()))
    <form method="POST" action="{{ route('documents.destroy', $document) }}" class="d-inline" onsubmit="return confirm('Hapus file ini? File akan diarsipkan dan hilang dari daftar.')">
        @csrf
        @method('DELETE')
        <button type="submit" class="{{ $class }}" aria-label="Hapus file {{ $document->name }}"><i class="bi bi-trash"></i></button>
    </form>
@endif
