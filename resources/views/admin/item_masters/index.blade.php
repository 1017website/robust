@extends('layouts.app')
@section('title','Master Item')
@section('content')
<x-page-header title="Master Item Penawaran" subtitle="Kelola identitas, harga dasar, dan spesifikasi produk per bagian">
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#masterItemCreate"><i class="bi bi-plus-lg me-1"></i>Tambah Master Item</button>
</x-page-header>

<div class="modal fade master-item-modal" id="masterItemCreate" tabindex="-1" aria-labelledby="masterItemCreateTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.item-masters.store') }}">@csrf
                <div class="modal-header">
                    <div><h5 class="modal-title" id="masterItemCreateTitle">Tambah Master Item</h5><small class="text-muted-2">Lengkapi identitas, harga dasar, dan spesifikasi item.</small></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-2"><label class="form-label small fw-semibold">Kode</label><input name="code" value="{{ old('code') }}" class="form-control" placeholder="Otomatis"></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Kategori *</label><input name="category" value="{{ old('category') }}" class="form-control" required placeholder="Meja Laboratorium"></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Nama Item *</label><input name="name" value="{{ old('name') }}" class="form-control" required placeholder="Wall Bench"></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Detail / Varian</label><input name="variant" value="{{ old('variant') }}" class="form-control" placeholder="WBF-200-S-SRF"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Unit</label><input name="unit" value="{{ old('unit', 'Unit') }}" class="form-control" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">HPP</label><input name="default_cost_price" type="number" min="0" class="form-control" value="{{ old('default_cost_price', 0) }}"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Margin %</label><input name="default_margin" type="number" min="0" max="99.99" step="0.01" class="form-control" value="{{ old('default_margin', 0) }}"></div>
                        <div class="col-md-3 d-flex align-items-end"><label class="form-check mb-2"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', true))><span class="form-check-label">Master item aktif</span></label></div>
                        <div class="col-12"><x-specification-editor name="specification" :value="old('specification', '')" label="Spesifikasi Default" /></div>
                    </div>
                    <div class="modal-form-actions">
                        <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Master Item</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card-r">
    <div class="card-head"><h2>Daftar Master Item</h2><span class="badge text-bg-light">{{ $items->total() }} item</span></div>
    <form method="GET" class="filter-bar mb-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode, item, varian, atau kategori..."><button class="btn btn-soft"><i class="bi bi-search me-1"></i>Cari</button></form>
    <div class="master-item-list">
        @forelse($items as $item)
            <div class="master-item-row">
                <div class="master-item-row-main">
                    <strong>{{ $item->name }} @if($item->variant)<span class="text-primary">· {{ $item->variant }}</span>@endif</strong>
                    <small>{{ $item->code }} · {{ $item->category }} · {{ $item->unit }}</small>
                </div>
                <div class="master-item-row-meta">
                    <span class="badge {{ $item->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    <span class="badge text-bg-light">HPP {{ \App\Support\Format::rupiah($item->default_cost_price) }}</span>
                    <span class="badge text-bg-light">Margin {{ rtrim(rtrim(number_format($item->default_margin, 2), '0'), '.') }}%</span>
                </div>
                <button type="button" class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#masterItemEdit{{ $item->id }}"><i class="bi bi-pencil-square me-1"></i>Edit</button>
            </div>

            <div class="modal fade master-item-modal" id="masterItemEdit{{ $item->id }}" tabindex="-1" aria-labelledby="masterItemEditTitle{{ $item->id }}" aria-hidden="true">
              <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
               <div class="modal-content">
                <form method="POST" action="{{ route('admin.item-masters.update',$item) }}">@csrf @method('PUT')
                    <div class="modal-header">
                        <div><h5 class="modal-title" id="masterItemEditTitle{{ $item->id }}">Edit Master Item</h5><small class="text-muted-2">{{ $item->code }} · {{ $item->name }}</small></div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body"><div class="row g-3">
                        <div class="col-md-2"><label class="form-label small fw-semibold">Kode</label><input name="code" value="{{ $item->code }}" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Kategori</label><input name="category" value="{{ $item->category }}" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Nama Item</label><input name="name" value="{{ $item->name }}" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Detail / Varian</label><input name="variant" value="{{ $item->variant }}" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Unit</label><input name="unit" value="{{ $item->unit }}" class="form-control" required></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">HPP</label><input name="default_cost_price" value="{{ $item->default_cost_price }}" type="number" min="0" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label small fw-semibold">Margin %</label><input name="default_margin" value="{{ $item->default_margin }}" type="number" min="0" max="99.99" step="0.01" class="form-control"></div>
                        <div class="col-md-3 d-flex align-items-end"><label class="form-check mb-2"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($item->is_active)><span class="form-check-label">Aktif</span></label></div>
                        <div class="col-12"><x-specification-editor name="specification" :value="$item->specification" label="Spesifikasi Default" /></div>
                    </div>
                    <div class="modal-form-actions">
                        <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
                    </div></div>
                </form>
               </div>
              </div>
            </div>
        @empty
            <x-empty text="Belum ada master item." />
        @endforelse
    </div>
    <div class="mt-3">{{ $items->links() }}</div>
</div>
@endsection

@if($errors->any())
@push('scripts')
<script>document.addEventListener('DOMContentLoaded', function () { window.RobustModal.getOrCreateInstance(document.getElementById('masterItemCreate')).show(); });</script>
@endpush
@endif
