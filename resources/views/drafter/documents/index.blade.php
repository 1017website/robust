@extends('layouts.app')
@section('title', 'Documents')
@section('content')
@php($previewUrl = fn($id) => route('documents.index', array_merge(request()->query(), ['document' => $id])).'#document-detail')
<div class="drafter-ui">
    <div class="drafter-page-head"><div><h1 class="page-title mb-1">Documents</h1><div class="page-subtitle">Kelola semua dokumen project, drawing, BOQ, laporan dan revisi dalam satu tempat.</div></div></div>
    <div class="drafter-shell">
        <main class="drafter-main">
            <div class="drafter-stat-grid five">
                <div class="drafter-stat"><div class="ico blue"><i class="bi bi-file-earmark-text"></i></div><div><div class="label">Total Dokumen</div><div class="value">{{ $stats['total'] }}</div><div class="sub">Data saat ini</div></div></div>
                <div class="drafter-stat"><div class="ico blue"><i class="bi bi-image"></i></div><div><div class="label">Gambar / Drawing</div><div class="value">{{ $stats['drawing'] }}</div><div class="sub">Data saat ini</div></div></div>
                <div class="drafter-stat"><div class="ico green"><i class="bi bi-file-earmark-spreadsheet"></i></div><div><div class="label">BOQ & Estimasi</div><div class="value">{{ $stats['boq'] }}</div><div class="sub">Data saat ini</div></div></div>
                <div class="drafter-stat"><div class="ico orange"><i class="bi bi-clipboard-data"></i></div><div><div class="label">Laporan</div><div class="value">{{ $stats['laporan'] }}</div><div class="sub">Data saat ini</div></div></div>
                <div class="drafter-stat"><div class="ico purple"><i class="bi bi-file-earmark"></i></div><div><div class="label">Lainnya</div><div class="value">{{ $stats['lainnya'] }}</div><div class="sub">Data saat ini</div></div></div>
            </div>
            <div class="card-r">
                <form class="drafter-filter" method="GET"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari dokumen..."><select class="form-select" name="category"><option value="">Semua Kategori</option><option value="drawing" @selected(request('category')==='drawing')>Drawing</option><option value="boq" @selected(request('category')==='boq')>BOQ</option><option value="laporan" @selected(request('category')==='laporan')>Laporan</option><option value="gambar" @selected(request('category')==='gambar')>Gambar</option><option value="lainnya" @selected(request('category')==='lainnya')>Lainnya</option></select><button type="submit" class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button></form>
                <div class="table-wrap">
                    <table class="drafter-table">
                        <thead><tr><th><input type="checkbox" aria-label="Pilih semua dokumen"></th><th>Nama Dokumen</th><th>Project</th><th>Kategori</th><th>PIC / Upload</th><th>Tanggal Upload</th><th>Ukuran</th><th>Aksi</th></tr></thead>
                        <tbody>
                        @forelse($documents as $doc)
                            <tr class="{{ $selectedDocument && $selectedDocument->id === $doc->id ? 'selected' : '' }}" data-detail-href="{{ $previewUrl($doc->id) }}" tabindex="0" role="link" aria-label="Tampilkan preview dokumen">
                                <td><input type="checkbox" aria-label="Pilih dokumen"></td>
                                <td><div class="doc-name"><i class="bi bi-file-earmark-text"></i><div><strong>{{ $doc->name }}</strong><small>{{ $doc->description ?: \Illuminate\Support\Str::headline($doc->category ?? 'Dokumen') }}</small></div></div></td>
                                <td>{{ $doc->documentable?->code ?? $doc->documentable?->name ?? '—' }}<br><small class="text-muted-2">{{ $doc->documentable?->project_name ?? $doc->documentable?->customer_name ?? '' }}</small></td>
                                <td><x-status-badge :status="$doc->category ?: 'lainnya'" :label="\Illuminate\Support\Str::headline($doc->category ?: 'Lainnya')" /></td>
                                <td>{{ $doc->uploader?->name ?? '—' }}<br><small class="text-muted-2">{{ $doc->uploader?->roleLabel() ?? '' }}</small></td>
                                <td>{{ $doc->created_at?->translatedFormat('d M Y') }}<br><small>{{ $doc->created_at?->format('H:i') }}</small></td>
                                <td>{{ $doc->humanSize() }}</td>
                                <td><a href="{{ $previewUrl($doc->id) }}" class="btn btn-sm btn-soft" aria-label="Tampilkan preview dokumen"><i class="bi bi-chevron-right"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-empty text="Belum ada dokumen." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $documents->links() }}</div>
            </div>
        </main>
        <aside class="drafter-detail" id="document-detail">
            @if($selectedDocument)
                <div class="detail-top"><div><h2>{{ $selectedDocument->name }}</h2><div class="text-muted-2">{{ $selectedDocument->documentable?->code ?? 'Dokumen' }} / {{ \Illuminate\Support\Str::headline($selectedDocument->category ?: 'Lainnya') }}</div></div><a href="{{ route('documents.index', request()->except('document', 'page')) }}" class="btn btn-link" aria-label="Tutup detail"><i class="bi bi-x-lg"></i></a></div>
                <div class="info-card"><div class="detail-grid"><div><small>Nama Dokumen</small><strong>{{ $selectedDocument->name }}</strong></div><div><small>Kategori</small><x-status-badge :status="$selectedDocument->category ?: 'lainnya'" :label="\Illuminate\Support\Str::headline($selectedDocument->category ?: 'Lainnya')" /></div><div><small>Project</small><strong>{{ $selectedDocument->documentable?->code ?? '—' }}</strong></div><div><small>PIC / Upload</small><strong>{{ $selectedDocument->uploader?->name ?? '—' }}</strong></div><div><small>Tanggal Upload</small><strong>{{ $selectedDocument->created_at?->translatedFormat('d M Y, H:i') }}</strong></div><div><small>Ukuran File</small><strong>{{ $selectedDocument->humanSize() }}</strong></div><div><small>Versi</small><strong>{{ $selectedDocument->version }}</strong></div></div></div>
                <div class="detail-actions"><a href="{{ asset('storage/'.$selectedDocument->file_path) }}" target="_blank" class="btn btn-primary"><i class="bi bi-download me-1"></i>Download</a><form method="POST" action="{{ route('documents.destroy',$selectedDocument) }}" onsubmit="return confirm('Arsipkan dokumen ini?')">@csrf @method('DELETE')<button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Arsipkan</button></form></div>
            @else
                <x-empty text="Pilih dokumen untuk melihat detail." />
            @endif
        </aside>
    </div>
</div>
@endsection
