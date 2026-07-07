@extends('layouts.app')
@section('title', 'Tambah Lead Baru')
@section('content')
<div class="sales-ui">
    <form method="POST" action="{{ route('sales.leads.store') }}">
        @csrf
        <div class="sales-page-head">
            <div class="sales-title-wrap"><a href="{{ route('sales.leads.index') }}" class="btn btn-soft"><i class="bi bi-arrow-left"></i></a><div><h1 class="page-title mb-1">Tambah Lead Baru</h1><div class="page-subtitle">Lengkapi informasi lead untuk memulai proses penjualan.</div></div></div>
            <div class="page-actions"><a href="{{ route('sales.leads.index') }}" class="btn btn-soft">Batal</a><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Lead</button></div>
        </div>

        <div class="row g-3">
            <div class="col-xl-6">
                <div class="sales-form-card">
                    <h2 class="sales-form-title"><i class="bi bi-person sblue rounded p-2 me-2"></i>Informasi Customer</h2>
                    <div class="mb-3"><label class="form-label small fw-bold">Nama Instansi / Perusahaan *</label><input name="instansi" value="{{ old('instansi') }}" class="form-control" required placeholder="Masukkan nama instansi atau perusahaan"></div>
                    <div class="mb-3"><label class="form-label small fw-bold">PIC (Person In Charge) *</label><input name="pic_name" value="{{ old('pic_name') }}" class="form-control" required placeholder="Masukkan nama PIC"></div>
                    <div class="row g-3"><div class="col-md-6"><label class="form-label small fw-bold">No. WhatsApp *</label><input name="phone" value="{{ old('phone') }}" class="form-control" required placeholder="08xxxxxxxxxx"></div><div class="col-md-6"><label class="form-label small fw-bold">Email</label><input name="email" value="{{ old('email') }}" type="email" class="form-control" placeholder="contoh@email.com"></div></div>
                    <div class="mt-3"><label class="form-label small fw-bold">Lokasi *</label><textarea name="location" rows="3" class="form-control" required placeholder="Masukkan alamat lokasi">{{ old('location') }}</textarea></div>
                    <div class="row g-3 mt-0"><div class="col-md-6"><label class="form-label small fw-bold">Kota *</label><input name="city" value="{{ old('city') }}" class="form-control" required placeholder="Pilih kota"></div><div class="col-md-6"><label class="form-label small fw-bold">Tipe Instansi *</label><select name="instansi_type" class="form-select" required><option value="">Pilih tipe instansi</option>@foreach(['Universitas','Rumah Sakit','Industri','Pemerintah','Distributor','Kontraktor','Lainnya'] as $type)<option value="{{ $type }}" @selected(old('instansi_type')==$type)>{{ $type }}</option>@endforeach</select></div></div>
                </div>
                <div class="sales-form-card">
                    <h2 class="sales-form-title"><i class="bi bi-link-45deg spurple rounded p-2 me-2"></i>Sumber Lead</h2>
                    <div class="row g-3"><div class="col-md-6"><label class="form-label small fw-bold">Sumber Lead *</label><select name="source" class="form-select" required><option value="">Pilih sumber lead</option>@foreach(['whatsapp'=>'WhatsApp','website'=>'Website','referensi'=>'Referensi','telepon'=>'Telepon','email'=>'Email','lainnya'=>'Lainnya'] as $k=>$v)<option value="{{ $k }}" @selected(old('source')==$k)>{{ $v }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label small fw-bold">Referensi / Dari</label><input name="reference" value="{{ old('reference') }}" class="form-control" placeholder="Masukkan referensi jika ada"></div></div>
                </div>
                <div class="sales-form-card"><h2 class="sales-form-title"><i class="bi bi-info-circle sorange rounded p-2 me-2"></i>Informasi Tambahan</h2><label class="form-label small fw-bold">Catatan Awal</label><textarea name="initial_note" rows="5" class="form-control" placeholder="Tulis catatan awal tentang lead ini...">{{ old('initial_note') }}</textarea></div>
            </div>
            <div class="col-xl-6">
                <div class="sales-form-card">
                    <h2 class="sales-form-title"><i class="bi bi-clipboard-check sgreen rounded p-2 me-2"></i>Kebutuhan Awal</h2>
                    <div class="mb-3"><label class="form-label small fw-bold">Nama Laboratorium / Proyek *</label><input name="lab_name" value="{{ old('lab_name') }}" class="form-control" required placeholder="Contoh: Laboratorium Kimia"></div>
                    <label class="form-label small fw-bold">Deskripsi Kebutuhan</label><textarea name="need_description" rows="5" maxlength="500" class="form-control" placeholder="Jelaskan kebutuhan laboratorium / peralatan yang dibutuhkan...">{{ old('need_description') }}</textarea>
                    <div class="mt-3"><label class="form-label small fw-bold">Daftar Kebutuhan</label><div class="d-flex flex-wrap gap-2">
                        @foreach(['Wall Bench','Fume Hood','Storage Cabinet','Sink Area','Meja Praktikum','Meja Instrumen','Safety Equipment','Lainnya'] as $item)
                            <label class="tag-pill"><input type="checkbox" name="scope_items[]" value="{{ $item }}" @checked(in_array($item, old('scope_items', [])))> {{ $item }}</label>
                        @endforeach
                    </div></div>
                </div>
                <div class="sales-form-card">
                    <h2 class="sales-form-title"><i class="bi bi-flag sorange rounded p-2 me-2"></i>Estimasi & Prioritas</h2>
                    <div class="row g-3"><div class="col-md-4"><label class="form-label small fw-bold">Estimasi Dari (Rp)</label><input name="est_value_min" value="{{ old('est_value_min') }}" class="form-control" placeholder="500.000.000"></div><div class="col-md-4"><label class="form-label small fw-bold">Sampai (Rp)</label><input name="est_value_max" value="{{ old('est_value_max') }}" class="form-control" placeholder="1.000.000.000"></div><div class="col-md-4"><label class="form-label small fw-bold">Prioritas Lead *</label><select name="priority" class="form-select" required><option value="">Pilih prioritas</option><option value="high" @selected(old('priority')=='high')>High (Tinggi)</option><option value="medium" @selected(old('priority','medium')=='medium')>Medium</option><option value="low" @selected(old('priority')=='low')>Low</option></select></div></div>
                </div>
                <div class="sales-form-card">
                    <h2 class="sales-form-title"><i class="bi bi-cloud-arrow-up sblue rounded p-2 me-2"></i>Dokumen Pendukung <span class="text-muted-2 fw-normal">(Opsional)</span></h2>
                    <div class="row g-3"><div class="col-md-6"><div class="upload-box"><div><i class="bi bi-cloud-arrow-up fs-1 text-primary"></i><div class="fw-bold">Klik atau drag & drop file di sini</div><div class="small">PDF, JPG, PNG (Max 10MB)</div></div></div></div><div class="col-md-6"><div class="info-card h-100 d-flex align-items-center justify-content-center"><span class="text-muted-2">Belum ada file yang diunggah</span></div></div></div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
