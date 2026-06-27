@extends('layouts.app')
@section('title', 'Lead Baru')
@section('content')
<x-page-header title="Buat Lead Baru" subtitle="Input lead secara manual" />

<form method="POST" action="{{ route('sales.leads.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-r">
                <div class="card-head"><h2>Informasi Instansi</h2></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Instansi *</label><input name="instansi" value="{{ old('instansi') }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Jenis Instansi *</label>
                        <select name="instansi_type" class="form-select" required>
                            @foreach(['Universitas','Sekolah','Rumah Sakit','Industri','Pemerintah','Laboratorium Swasta','Lainnya'] as $t)
                                <option value="{{ $t }}" @selected(old('instansi_type')==$t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8"><label class="form-label small fw-semibold">Lokasi / Alamat *</label><input name="location" value="{{ old('location') }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Kota *</label><input name="city" value="{{ old('city') }}" class="form-control" required></div>
                </div>
            </div>
            <div class="card-r">
                <div class="card-head"><h2>PIC & Kontak</h2></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Nama PIC *</label><input name="pic_name" value="{{ old('pic_name') }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Jabatan</label><input name="pic_position" value="{{ old('pic_position') }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">No. Telepon *</label><input name="phone" value="{{ old('phone') }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input name="email" type="email" value="{{ old('email') }}" class="form-control"></div>
                </div>
            </div>
            <div class="card-r">
                <div class="card-head"><h2>Kebutuhan</h2></div>
                <div class="row g-3">
                    <div class="col-md-12"><label class="form-label small fw-semibold">Nama Lab / Proyek *</label><input name="lab_name" value="{{ old('lab_name') }}" class="form-control" required></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Deskripsi Kebutuhan</label><textarea name="need_description" rows="3" class="form-control">{{ old('need_description') }}</textarea></div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Scope Item (kebutuhan furnitur)</label>
                        <div class="row g-2">
                            @foreach(['Wall Bench','Island Bench','Fume Hood','Sink Unit','Wall Cabinet','Storage','Safety Equipment','Lemari Asam'] as $item)
                                <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="scope_items[]" value="{{ $item }}" id="sc{{ $loop->index }}"><label class="form-check-label small" for="sc{{ $loop->index }}">{{ $item }}</label></div></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-r">
                <div class="card-head"><h2>Klasifikasi</h2></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Sumber *</label>
                    <select name="source" class="form-select" required>
                        @foreach(['whatsapp','website','referensi','telepon','email','lainnya'] as $src)<option value="{{ $src }}">{{ ucfirst($src) }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Referensi (jika ada)</label><input name="reference" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Prioritas *</label>
                    <select name="priority" class="form-select"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Estimasi Min</label><input data-rupiah name="est_value_min" type="text" inputmode="numeric" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Estimasi Max</label><input data-rupiah name="est_value_max" type="text" inputmode="numeric" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Catatan</label><textarea name="initial_note" rows="3" class="form-control"></textarea></div>
            </div>
            <div class="card-r">
                <button class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Simpan Lead</button>
                <a href="{{ route('sales.leads.index') }}" class="btn btn-soft w-100 mt-2">Batal</a>
            </div>
        </div>
    </div>
</form>
@endsection
