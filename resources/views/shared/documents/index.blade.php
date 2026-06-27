@extends('layouts.app')
@section('title', 'Documents')
@section('content')
<x-page-header title="Documents" subtitle="Repositori dokumen proyek dan desain" />

<div class="stat-grid">
    <x-stat-card icon="bi-folder2-open" color="primary" label="Total" :value="$stats['total']" />
    <x-stat-card icon="bi-pencil-square" color="info" label="Drawing" :value="$stats['drawing']" />
    <x-stat-card icon="bi-list-check" color="warning" label="BOQ" :value="$stats['boq']" />
    <x-stat-card icon="bi-file-earmark" color="success" label="Laporan" :value="$stats['laporan']" />
</div>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari dokumen...">
        <select name="category" class="form-select"><option value="">Semua Kategori</option>@foreach(['drawing'=>'Drawing','boq'=>'BOQ','laporan'=>'Laporan','gambar'=>'Gambar','lainnya'=>'Lainnya'] as $k=>$v)<option value="{{ $k }}" @selected(request('category')==$k)>{{ $v }}</option>@endforeach</select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Nama</th><th>Kategori</th><th>Tipe</th><th>Ukuran</th><th>Diunggah</th><th></th></tr></thead>
            <tbody>
            @forelse($documents as $doc)
                <tr>
                    <td class="fw-semibold"><i class="bi bi-file-earmark me-2"></i>{{ $doc->name }}</td>
                    <td><span class="pill">{{ ucfirst($doc->category) }}</span></td>
                    <td>{{ strtoupper($doc->file_type) }}</td>
                    <td>{{ $doc->humanSize() }}</td>
                    <td>{{ $doc->created_at->format('d M Y') }}<div class="small text-muted-2">{{ $doc->uploader?->name }}</div></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" class="btn btn-sm btn-soft"><i class="bi bi-download"></i></a>
                            <form method="POST" action="{{ route('documents.destroy',$doc) }}" onsubmit="return confirm('Hapus dokumen?')">@csrf @method('DELETE')<button class="btn btn-sm btn-soft text-danger"><i class="bi bi-trash"></i></button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty text="Belum ada dokumen." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $documents->links() }}</div>
</div>
@endsection
