@extends('layouts.app')
@section('title', 'Aktivitas Baru')
@section('content')
<x-page-header title="Tambah Aktivitas" subtitle="Catat aktivitas sales" />
<form method="POST" action="{{ route('activities.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-r">
                <div class="card-head"><h2>Detail Aktivitas</h2></div>
                <div class="row g-3">
                    @if(!auth()->user()->isSales())<div class="col-md-6"><label class="form-label small fw-semibold">Sales PIC *</label><select name="sales_id" class="form-select" required><option value="">Pilih sales</option>@foreach($salesUsers as $sales)<option value="{{ $sales->id }}" @selected((string)old('sales_id')===(string)$sales->id)>{{ $sales->name }}</option>@endforeach</select></div>@endif
                    <div class="col-md-6"><label class="form-label small fw-semibold">Tipe *</label><select name="type" class="form-select" required>@foreach(\App\Models\Activity::types() as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Customer</label><select name="customer_id" class="form-select select2"><option value="">—</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected((string)old('customer_id')===(string)$c->id)>{{ $c->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Pipeline Stage <i class="bi bi-info-circle text-muted" title="Menentukan aktivitas ini dicatat pada tahap pipeline yang mana."></i></label><select name="pipeline_stage" class="form-select"><option value="">Otomatis — gunakan stage customer saat ini</option>@foreach(\App\Models\Customer::stages() as $k=>$v)<option value="{{ $k }}" @selected(old('pipeline_stage')===$k)>{{ $v }}</option>@endforeach</select><div class="form-text">Pilih “Otomatis” agar stage mengikuti customer yang dipilih. Ubah hanya jika aktivitas ini terjadi pada tahap yang berbeda.</div></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Judul *</label><input name="title" class="form-control" required></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Deskripsi</label><textarea name="description" rows="3" class="form-control"></textarea></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Tanggal *</label><input name="activity_date" type="date" value="{{ date('Y-m-d') }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Waktu</label><input name="activity_time" type="time" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Durasi (menit)</label><input name="duration_minutes" type="number" class="form-control"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-r">
                <div class="card-head"><h2>Status & Tindak Lanjut</h2></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Status *</label><select name="status" class="form-select">@foreach(\App\Models\Activity::statuses() as $key=>$label)<option value="{{ $key }}" @selected(old('status','scheduled')===$key)>{{ $label }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Tindak Lanjut</label><textarea name="next_action" rows="2" class="form-control"></textarea></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Tgl Follow Up</label><input name="next_followup_date" type="date" class="form-control"></div>
            </div>
            <div class="card-r"><button type="submit" class="btn btn-primary w-100">Simpan Aktivitas</button><a href="{{ route('activities.index') }}" class="btn btn-soft w-100 mt-2">Batal</a></div>
        </div>
    </div>
</form>
@endsection
