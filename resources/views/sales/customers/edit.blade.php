@extends('layouts.app')
@section('title', 'Edit Customer')
@section('content')
<x-page-header title="Edit Customer" :subtitle="$customer->name" />
<form method="POST" action="{{ route('sales.customers.update',$customer) }}">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-r">
                <div class="card-head"><h2>Informasi Customer</h2></div>
                <div class="row g-3">
                    <div class="col-md-8"><label class="form-label small fw-semibold">Nama *</label><input name="name" value="{{ old('name',$customer->name) }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Kategori</label>
                        <select name="category" class="form-select"><option value="">—</option>@foreach(['Universitas','Sekolah','Rumah Sakit','Industri','Pemerintah','Laboratorium Swasta'] as $cat)<option value="{{ $cat }}" @selected($customer->category==$cat)>{{ $cat }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input name="email" type="email" value="{{ $customer->email }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Telepon</label><input name="phone" value="{{ $customer->phone }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Website</label><input name="website" value="{{ $customer->website }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Kota</label><input name="city" value="{{ $customer->city }}" class="form-control"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Alamat</label><textarea name="address" rows="2" class="form-control">{{ $customer->address }}</textarea></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-r">
                <div class="card-head"><h2>Pipeline</h2></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Stage</label>
                    <select name="pipeline_stage" class="form-select">@foreach(\App\Models\Customer::stages() as $k=>$v)<option value="{{ $k }}" @selected($customer->pipeline_stage==$k)>{{ $v }}</option>@endforeach</select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Probability (%)</label><input name="probability" type="number" min="0" max="100" value="{{ $customer->probability }}" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Catatan</label><textarea name="notes" rows="3" class="form-control">{{ $customer->notes }}</textarea></div>
            </div>
            <div class="card-r"><button class="btn btn-primary w-100">Simpan</button><a href="{{ route('sales.customers.show',$customer) }}" class="btn btn-soft w-100 mt-2">Batal</a></div>
        </div>
    </div>
</form>
@endsection
