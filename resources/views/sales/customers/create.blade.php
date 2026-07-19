@extends('layouts.app')
@section('title', 'Tambah Customer')
@section('content')
<div class="sales-ui">
    <form method="POST" action="{{ route('sales.customers.store') }}">
        @csrf
        <div class="sales-page-head">
            <div class="sales-title-wrap">
                <a href="{{ route('sales.customers.index') }}" class="btn btn-soft" aria-label="Kembali"><i class="bi bi-arrow-left"></i></a>
                <div><h1 class="page-title mb-1">Tambah Customer</h1><div class="page-subtitle">Lengkapi data customer dan PIC utama.</div></div>
            </div>
            <div class="page-actions"><a href="{{ route('sales.customers.index') }}" class="btn btn-soft">Batal</a><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button></div>
        </div>

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="sales-form-card">
                    <h2 class="sales-form-title">Informasi Customer</h2>
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label small fw-bold">Nama Customer *</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Kategori</label><select name="category" class="form-select"><option value="">Pilih kategori</option>@foreach(\App\Models\Customer::categories() as $category)<option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Jenis Industri</label><input name="type" value="{{ old('type') }}" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Website</label><input name="website" value="{{ old('website') }}" class="form-control" placeholder="https://"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Email</label><input name="email" type="email" value="{{ old('email') }}" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">No. Telepon</label><input name="phone" value="{{ old('phone') }}" class="form-control"></div>
                        <div class="col-md-8"><label class="form-label small fw-bold">Alamat</label><textarea name="address" rows="3" class="form-control">{{ old('address') }}</textarea></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Kota</label><input name="city" value="{{ old('city') }}" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Area / Lokasi Customer</label><input name="area" value="{{ old('area') }}" class="form-control" placeholder="Contoh: Gedung A, Jakarta"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Divisi Customer</label><input name="division" value="{{ old('division') }}" class="form-control" placeholder="Contoh: Laboratorium / Engineering"></div>
                    </div>
                </div>
                <div class="sales-form-card">
                    <h2 class="sales-form-title">PIC Utama</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small fw-bold">Nama PIC</label><input name="pic_name" value="{{ old('pic_name') }}" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Jabatan PIC</label><input name="pic_position" value="{{ old('pic_position') }}" class="form-control"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="sales-form-card">
                    <h2 class="sales-form-title">Pipeline & Kepemilikan</h2>
                    <div class="mb-3"><label class="form-label small fw-bold">Pipeline Stage *</label><select name="pipeline_stage" class="form-select" required>@foreach(\App\Models\Customer::stages() as $key => $label)<option value="{{ $key }}" @selected(old('pipeline_stage', 'identify') === $key)>{{ $label }}</option>@endforeach</select></div>
                    <div class="mb-3"><label class="form-label small fw-bold">Probability (%)</label><input name="probability" type="number" min="0" max="100" value="{{ old('probability', 0) }}" class="form-control"></div>
                    @if(! auth()->user()->isSales())
                        <div class="mb-3"><label class="form-label small fw-bold">Sales Owner *</label><select name="sales_id" class="form-select" required><option value="">Pilih sales</option>@foreach($salesList as $sales)<option value="{{ $sales->id }}" @selected((string) old('sales_id') === (string) $sales->id)>{{ $sales->name }}</option>@endforeach</select></div>
                    @else
                        <div class="mb-3"><label class="form-label small fw-bold">Sales Owner</label><input class="form-control" value="{{ auth()->user()->name }}" readonly></div>
                    @endif
                    <div class="mb-3"><label class="form-label small fw-bold">Mulai Menjadi Partner</label><input type="date" name="partner_since" value="{{ old('partner_since') }}" class="form-control"></div>
                    <div><label class="form-label small fw-bold">Catatan</label><textarea name="notes" rows="5" class="form-control">{{ old('notes') }}</textarea></div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
