@extends('layouts.app')
@section('title','Master Item')
@section('content')
<x-page-header title="Master Item Penawaran" subtitle="Detail item seperti jumlah laci, layout L/U, spesifikasi, HPP, dan margin default" />
<div class="card-r">
    <div class="card-head"><h2>Tambah Master Item</h2></div>
    <form method="POST" action="{{ route('admin.item-masters.store') }}" class="row g-2 align-items-end">@csrf
        <div class="col-md-2"><label class="form-label small">Kode (otomatis)</label><input name="code" class="form-control"></div>
        <div class="col-md-2"><label class="form-label small">Kategori *</label><input name="category" class="form-control" required placeholder="Meja / Lemari"></div>
        <div class="col-md-2"><label class="form-label small">Nama Item *</label><input name="name" class="form-control" required placeholder="Meja Layout"></div>
        <div class="col-md-2"><label class="form-label small">Detail / Varian</label><input name="variant" class="form-control" placeholder="Layout L / Laci 3"></div>
        <div class="col-md-1"><label class="form-label small">Unit</label><input name="unit" value="Unit" class="form-control" required></div>
        <div class="col-md-1"><label class="form-label small">HPP</label><input name="default_cost_price" type="number" min="0" class="form-control" value="0"></div>
        <div class="col-md-1"><label class="form-label small">Margin %</label><input name="default_margin" type="number" min="0" max="99.99" step="0.01" class="form-control" value="0"></div>
        <div class="col-md-1"><input type="hidden" name="is_active" value="1"><button class="btn btn-primary w-100">Tambah</button></div>
        <div class="col-12"><label class="form-label small">Spesifikasi Default</label><textarea name="specification" rows="2" class="form-control"></textarea></div>
    </form>
</div>
<div class="card-r">
    <form method="GET" class="filter-bar"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode, item, varian..."><button class="btn btn-soft">Cari</button></form>
    <div class="table-wrap"><table class="table-r"><thead><tr><th>Kode</th><th>Kategori</th><th>Item</th><th>Detail / Varian</th><th>Spesifikasi</th><th>Unit</th><th>HPP</th><th>Margin</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($items as $item)<tr>
        <form method="POST" action="{{ route('admin.item-masters.update',$item) }}">@csrf @method('PUT')
        <td><input name="code" value="{{ $item->code }}" class="form-control form-control-sm" required></td><td><input name="category" value="{{ $item->category }}" class="form-control form-control-sm" required></td><td><input name="name" value="{{ $item->name }}" class="form-control form-control-sm" required></td><td><input name="variant" value="{{ $item->variant }}" class="form-control form-control-sm"></td><td><input name="specification" value="{{ $item->specification }}" class="form-control form-control-sm"></td><td><input name="unit" value="{{ $item->unit }}" class="form-control form-control-sm" required></td><td><input name="default_cost_price" value="{{ $item->default_cost_price }}" type="number" min="0" class="form-control form-control-sm"></td><td><input name="default_margin" value="{{ $item->default_margin }}" type="number" min="0" max="99.99" class="form-control form-control-sm"></td><td><label class="small"><input type="checkbox" name="is_active" value="1" @checked($item->is_active)> Aktif</label></td><td><button class="btn btn-sm btn-soft"><i class="bi bi-save"></i></button></td>
        </form>
    </tr>@empty<tr><td colspan="10"><x-empty text="Belum ada master item." /></td></tr>@endforelse
    </tbody></table></div><div class="mt-3">{{ $items->links() }}</div>
</div>
@endsection
