@extends('layouts.app')
@section('title','Master Item')
@section('content')
<x-page-header title="Master Item Penawaran" subtitle="Kelola identitas, harga dasar, dan spesifikasi produk per bagian" />

<div class="card-r">
    <div class="card-head">
        <div><h2>Tambah Master Item</h2><small class="text-muted-2">Spesifikasi dapat disusun tanpa mengetik format khusus.</small></div>
    </div>
    <form method="POST" action="{{ route('admin.item-masters.store') }}" class="row g-3">@csrf
        <div class="col-md-2"><label class="form-label small fw-semibold">Kode</label><input name="code" class="form-control" placeholder="Otomatis"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Kategori *</label><input name="category" class="form-control" required placeholder="Meja Laboratorium"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Nama Item *</label><input name="name" class="form-control" required placeholder="Wall Bench"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Detail / Varian</label><input name="variant" class="form-control" placeholder="WBF-200-S-SRF"></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">Unit</label><input name="unit" value="Unit" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">HPP</label><input name="default_cost_price" type="number" min="0" class="form-control" value="0"></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">Margin %</label><input name="default_margin" type="number" min="0" max="99.99" step="0.01" class="form-control" value="0"></div>
        <div class="col-md-3 d-flex align-items-end"><label class="form-check mb-2"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked><span class="form-check-label">Master item aktif</span></label></div>
        <div class="col-12">
            <x-specification-editor name="specification" label="Spesifikasi Default" />
        </div>
        <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Master Item</button></div>
    </form>
</div>

<div class="card-r">
    <div class="card-head"><h2>Daftar Master Item</h2><span class="badge text-bg-light">{{ $items->total() }} item</span></div>
    <form method="GET" class="filter-bar mb-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode, item, varian, atau kategori..."><button class="btn btn-soft"><i class="bi bi-search me-1"></i>Cari</button></form>
    <div class="master-item-list">
        @forelse($items as $item)
            <details class="master-item-card">
                <summary class="master-item-summary">
                    <div class="master-item-summary-main">
                        <strong>{{ $item->name }} @if($item->variant)<span class="text-primary">· {{ $item->variant }}</span>@endif</strong>
                        <small>{{ $item->code }} · {{ $item->category }} · {{ $item->unit }}</small>
                    </div>
                    <div class="master-item-summary-meta">
                        <span class="badge {{ $item->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        <span class="badge text-bg-light">HPP {{ \App\Support\Format::rupiah($item->default_cost_price) }}</span>
                        <span class="badge text-bg-light">Margin {{ rtrim(rtrim(number_format($item->default_margin, 2), '0'), '.') }}%</span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                </summary>
                <div class="master-item-edit">
                    <form method="POST" action="{{ route('admin.item-masters.update',$item) }}" class="row g-3">@csrf @method('PUT')
                        <div class="col-md-2"><label class="form-label small fw-semibold">Kode</label><input name="code" value="{{ $item->code }}" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Kategori</label><input name="category" value="{{ $item->category }}" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Nama Item</label><input name="name" value="{{ $item->name }}" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Detail / Varian</label><input name="variant" value="{{ $item->variant }}" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Unit</label><input name="unit" value="{{ $item->unit }}" class="form-control" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">HPP</label><input name="default_cost_price" value="{{ $item->default_cost_price }}" type="number" min="0" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Margin %</label><input name="default_margin" value="{{ $item->default_margin }}" type="number" min="0" max="99.99" step="0.01" class="form-control"></div>
                        <div class="col-md-3 d-flex align-items-end"><label class="form-check mb-2"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($item->is_active)><span class="form-check-label">Aktif</span></label></div>
                        <div class="col-12"><x-specification-editor name="specification" :value="$item->specification" label="Spesifikasi Default" /></div>
                        <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Perubahan</button></div>
                    </form>
                </div>
            </details>
        @empty
            <x-empty text="Belum ada master item." />
        @endforelse
    </div>
    <div class="mt-3">{{ $items->links() }}</div>
</div>
@endsection
