@extends('layouts.app')
@section('title', 'Documents')
@section('content')
<div class="drafter-ui">
    <div class="drafter-page-head"><div><h1 class="page-title mb-1">Documents</h1><div class="page-subtitle">Kelola semua dokumen project, drawing, BOQ, laporan dan revisi dalam satu tempat.</div></div></div>
    <div class="drafter-shell">
        <main class="drafter-main">
            <div class="drafter-stat-grid five">
                <div class="drafter-stat"><div class="ico blue"><i class="bi bi-file-earmark-text"></i></div><div><div class="label">Total Dokumen</div><div class="value">{{ $stats['total'] }}</div><div class="sub up">↗ 16 dari minggu lalu</div></div></div>
                <div class="drafter-stat"><div class="ico blue"><i class="bi bi-image"></i></div><div><div class="label">Gambar / Drawing</div><div class="value">{{ $stats['drawing'] }}</div><div class="sub up">↗ 8 dari minggu lalu</div></div></div>
                <div class="drafter-stat"><div class="ico green"><i class="bi bi-file-earmark-spreadsheet"></i></div><div><div class="label">BOQ & Estimasi</div><div class="value">{{ $stats['boq'] }}</div><div class="sub up">↗ 4 dari minggu lalu</div></div></div>
                <div class="drafter-stat"><div class="ico orange"><i class="bi bi-clipboard-data"></i></div><div><div class="label">Laporan</div><div class="value">{{ $stats['laporan'] }}</div><div class="sub up">↗ 2 dari minggu lalu</div></div></div>
                <div class="drafter-stat"><div class="ico purple"><i class="bi bi-file-earmark"></i></div><div><div class="label">Lainnya</div><div class="value">{{ $stats['lainnya'] }}</div><div class="sub up">↗ 2 dari minggu lalu</div></div></div>
            </div>
            <div class="card-r">
                <form class="drafter-filter" method="GET"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari dokumen..."><select class="form-select" name="category"><option value="">Semua Kategori</option><option value="drawing" @selected(request('category')==='drawing')>Drawing</option><option value="boq" @selected(request('category')==='boq')>BOQ</option><option value="laporan" @selected(request('category')==='laporan')>Laporan</option><option value="gambar" @selected(request('category')==='gambar')>Gambar</option><option value="lainnya" @selected(request('category')==='lainnya')>Lainnya</option></select><button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button><button type="button" class="btn btn-primary"><i class="bi bi-list-ul"></i></button><button type="button" class="btn btn-soft"><i class="bi bi-grid"></i></button></form>
                <div class="table-wrap"><table class="drafter-table"><thead><tr><th><input type="checkbox"></th><th>Nama Dokumen</th><th>Project</th><th>Kategori</th><th>PIC / Upload</th><th>Tanggal Upload</th><th>Ukuran</th><th>Aksi</th></tr></thead><tbody>@forelse($documents as $doc)<tr><td><input type="checkbox"></td><td><div class="doc-name"><i class="bi bi-file-earmark-text"></i><div><strong>{{ $doc->name }}</strong><small>{{ $doc->description ?: \Illuminate\Support\Str::headline($doc->category ?? 'Dokumen') }}</small></div></div></td><td>{{ $doc->documentable?->code ?? $doc->documentable?->name ?? '—' }}<br><small class="text-muted-2">{{ $doc->documentable?->project_name ?? $doc->documentable?->customer_name ?? '' }}</small></td><td><x-status-badge :status="$doc->category ?: 'lainnya'" :label="\Illuminate\Support\Str::headline($doc->category ?: 'Lainnya')" /></td><td>{{ $doc->uploader?->name ?? '—' }}<br><small class="text-muted-2">{{ $doc->uploader?->roleLabel() ?? '' }}</small></td><td>{{ $doc->created_at?->translatedFormat('d M Y') }}<br><small>{{ $doc->created_at?->format('H:i') }}</small></td><td>{{ $doc->humanSize() }}</td><td>...</td></tr>@empty<tr><td colspan="8"><x-empty text="Belum ada dokumen." /></td></tr>@endforelse</tbody></table></div>
                <div class="mt-3">{{ $documents->links() }}</div>
            </div>
        </main>
        <aside class="drafter-detail">
            @if($selectedDocument)
                <div class="detail-top"><div><h2>{{ $selectedDocument->name }}</h2><div class="text-muted-2">{{ $selectedDocument->documentable?->code ?? 'Dokumen' }} / {{ \Illuminate\Support\Str::headline($selectedDocument->category ?: 'Lainnya') }}</div></div><button type="button" class="btn btn-link"><i class="bi bi-x-lg"></i></button></div>
                <div class="detail-tabs"><span class="active">Detail</span><span>Preview</span><span>Riwayat Versi</span><span>Komentar</span></div>
                <div class="info-card"><div class="detail-grid"><div><small>Nama Dokumen</small><strong>{{ $selectedDocument->name }}</strong></div><div><small>Kategori</small><x-status-badge :status="$selectedDocument->category ?: 'lainnya'" :label="\Illuminate\Support\Str::headline($selectedDocument->category ?: 'Lainnya')" /></div><div><small>Project</small><strong>{{ $selectedDocument->documentable?->code ?? '—' }}</strong></div><div><small>PIC / Upload</small><strong>{{ $selectedDocument->uploader?->name ?? '—' }}</strong></div><div><small>Tanggal Upload</small><strong>{{ $selectedDocument->created_at?->translatedFormat('d M Y, H:i') }}</strong></div><div><small>Ukuran File</small><strong>{{ $selectedDocument->humanSize() }}</strong></div><div><small>Versi</small><strong>{{ $selectedDocument->version }}</strong></div></div></div>
                <div class="detail-actions"><button class="btn btn-primary"><i class="bi bi-download me-1"></i>Download</button><button class="btn btn-success"><i class="bi bi-share me-1"></i>Bagikan</button><button class="btn btn-soft"><i class="bi bi-clock-history me-1"></i>Lihat Riwayat</button><button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button></div>
                <div class="info-card"><h6>Preview Dokumen</h6><div class="doc-preview"><i class="bi bi-file-earmark-text"></i><span>Preview dokumen</span></div></div>
                <div class="info-card"><h6>Komentar Terbaru</h6><div class="small text-muted-2">Belum ada komentar.</div><input class="form-control mt-3" placeholder="Tulis komentar..."></div>
            @else
                <x-empty text="Pilih dokumen untuk melihat detail." />
            @endif
        </aside>
    </div>
</div>
@endsection
