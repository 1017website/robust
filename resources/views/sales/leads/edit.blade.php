@extends('layouts.app')
@section('title', 'Edit Lead')
@section('content')
<x-page-header title="Edit Lead" :subtitle="$lead->code" />
<form method="POST" action="{{ route('sales.leads.update',$lead) }}">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-r">
                <div class="card-head"><h2>Informasi Instansi</h2></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Instansi *</label><input name="instansi" value="{{ old('instansi',$lead->instansi) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Jenis Instansi *</label>
                        <select name="instansi_type" class="form-select" required>
                            @foreach(['Universitas','Sekolah','Rumah Sakit','Industri','Pemerintah','Laboratorium Swasta','Lainnya'] as $t)
                                <option value="{{ $t }}" @selected(old('instansi_type',$lead->instansi_type)==$t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8"><label class="form-label small fw-semibold">Lokasi *</label><input name="location" value="{{ old('location',$lead->location) }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Kota *</label><input name="city" value="{{ old('city',$lead->city) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Nama PIC *</label><input name="pic_name" value="{{ old('pic_name',$lead->pic_name) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Jabatan</label><input name="pic_position" value="{{ old('pic_position',$lead->pic_position) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">No. Telepon *</label><input name="phone" value="{{ old('phone',$lead->phone) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input name="email" type="email" value="{{ old('email',$lead->email) }}" class="form-control"></div>
                    <div class="col-md-12"><label class="form-label small fw-semibold">Nama Lab/Proyek *</label><input name="lab_name" value="{{ old('lab_name',$lead->lab_name) }}" class="form-control" required></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Deskripsi Kebutuhan</label><textarea name="need_description" rows="3" class="form-control">{{ old('need_description',$lead->need_description) }}</textarea></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-r">
                <div class="card-head"><h2>Klasifikasi</h2></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Sumber *</label>
                    <select name="source" class="form-select" required>
                        @foreach(['whatsapp','website','referensi','telepon','email','lainnya'] as $src)<option value="{{ $src }}" @selected($lead->source==$src)>{{ ucfirst($src) }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Prioritas *</label>
                    <select name="priority" class="form-select">
                        @foreach(['low'=>'Low','medium'=>'Medium','high'=>'High'] as $k=>$v)<option value="{{ $k }}" @selected($lead->priority==$k)>{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Estimasi Min</label><input data-rupiah name="est_value_min" type="text" inputmode="numeric" value="{{ $lead->est_value_min }}" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Estimasi Max</label><input data-rupiah name="est_value_max" type="text" inputmode="numeric" value="{{ $lead->est_value_max }}" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Catatan</label><textarea name="initial_note" rows="3" class="form-control">{{ $lead->initial_note }}</textarea></div>
            </div>
            <div class="card-r">
                <button class="btn btn-primary w-100">Simpan Perubahan</button>
                <a href="{{ route('sales.leads.show',$lead) }}" class="btn btn-soft w-100 mt-2">Batal</a>
            </div>
        </div>
    </div>
</form>
@endsection
