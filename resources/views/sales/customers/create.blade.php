@extends('layouts.app')
@section('title', 'Customer Baru')
@section('content')
<x-page-header title="Tambah Customer" subtitle="Daftarkan customer baru" />
<form method="POST" action="{{ route('sales.customers.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-r">
                <div class="card-head"><h2>Informasi Customer</h2></div>
                <div class="row g-3">
                    <div class="col-md-8"><label class="form-label small fw-semibold">Nama Instansi *</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Kategori</label>
                        <select name="category" class="form-select"><option value="">—</option>@foreach(['Universitas','Sekolah','Rumah Sakit','Industri','Pemerintah','Laboratorium Swasta'] as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input name="email" type="email" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Telepon</label><input name="phone" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Website</label><input name="website" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Kota</label><input name="city" class="form-control"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Alamat</label><textarea name="address" rows="2" class="form-control"></textarea></div>
                </div>
            </div>
            <div class="card-r">
                <div class="card-head"><h2>PIC Utama</h2></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Nama PIC</label><input name="pic_name" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Jabatan</label><input name="pic_position" class="form-control"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-r">
                <div class="card-head"><h2>Pipeline</h2></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Stage *</label>
                    <select name="pipeline_stage" class="form-select">@foreach(\App\Models\Customer::stages() as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Probability (%)</label><input name="probability" type="number" min="0" max="100" value="0" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Catatan</label><textarea name="notes" rows="3" class="form-control"></textarea></div>
            </div>
            <div class="card-r"><button class="btn btn-primary w-100">Simpan Customer</button><a href="{{ route('sales.customers.index') }}" class="btn btn-soft w-100 mt-2">Batal</a></div>
        </div>
    </div>
</form>
@endsection
