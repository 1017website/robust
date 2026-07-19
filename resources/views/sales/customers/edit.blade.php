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
                    <div class="col-md-4"><label class="form-label small fw-semibold">Kategori</label><select name="category" class="form-select"><option value="">-</option>@foreach(\App\Models\Customer::categories() as $category)<option value="{{ $category }}" @selected(old('category',$customer->category)===$category)>{{ $category }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Jenis Industri</label><input name="type" value="{{ old('type',$customer->type) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Website</label><input name="website" value="{{ old('website',$customer->website) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input name="email" type="email" value="{{ old('email',$customer->email) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Telepon</label><input name="phone" value="{{ old('phone',$customer->phone) }}" class="form-control"></div>
                    <div class="col-md-8"><label class="form-label small fw-semibold">Alamat</label><textarea name="address" rows="3" class="form-control">{{ old('address',$customer->address) }}</textarea></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Kota</label><input name="city" value="{{ old('city',$customer->city) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Area / Lokasi Customer</label><input name="area" value="{{ old('area',$customer->area) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Divisi Customer</label><input name="division" value="{{ old('division',$customer->division) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Nama PIC Utama</label><input name="pic_name" value="{{ old('pic_name',$customer->primaryPic?->name) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Jabatan PIC</label><input name="pic_position" value="{{ old('pic_position',$customer->primaryPic?->position) }}" class="form-control"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-r">
                <div class="card-head"><h2>Pipeline</h2></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Stage</label><select name="pipeline_stage" class="form-select">@foreach(\App\Models\Customer::stages() as $key=>$label)<option value="{{ $key }}" @selected(old('pipeline_stage',$customer->pipeline_stage)===$key)>{{ $label }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Probability (%)</label><input name="probability" type="number" min="0" max="100" value="{{ old('probability',$customer->probability) }}" class="form-control"></div>
                @if(! auth()->user()->isSales())<div class="mb-3"><label class="form-label small fw-semibold">Sales Owner</label><select name="sales_id" class="form-select" required>@foreach($salesList as $sales)<option value="{{ $sales->id }}" @selected((string)old('sales_id',$customer->sales_id)===(string)$sales->id)>{{ $sales->name }}</option>@endforeach</select></div>@endif
                <div class="mb-3"><label class="form-label small fw-semibold">Mulai Menjadi Partner</label><input name="partner_since" type="date" value="{{ old('partner_since',$customer->partner_since?->format('Y-m-d')) }}" class="form-control"></div>
                <div><label class="form-label small fw-semibold">Catatan</label><textarea name="notes" rows="4" class="form-control">{{ old('notes',$customer->notes) }}</textarea></div>
            </div>
            <div class="card-r"><button type="submit" class="btn btn-primary w-100">Simpan</button><a href="{{ route('sales.customers.show',$customer) }}" class="btn btn-soft w-100 mt-2">Batal</a></div>
        </div>
    </div>
</form>
@endsection
