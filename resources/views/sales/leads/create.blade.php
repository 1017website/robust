@extends('layouts.app')
@section('title', 'Tambah Lead Baru')

@section('content')
@php
    $defaultScopes = ['Wall Bench', 'Fume Hood', 'Storage Cabinet', 'Sink Area', 'Meja Praktikum'];
    $scopeItems = old('scope_items', $defaultScopes);
    $cityOptions = ['Jakarta','Bandung','Surabaya','Semarang','Yogyakarta','Malang','Denpasar','Medan','Makassar','Balikpapan','Lainnya'];
@endphp
<div class="sales-ui lead-layout-page">
    <form method="POST" action="{{ route('sales.leads.store') }}" enctype="multipart/form-data" class="lead-form-shell">
        @csrf
        <div class="lead-page-head">
            <div class="lead-title-wrap">
                <a href="{{ route('sales.leads.index') }}" class="lead-back-btn"><i class="bi bi-arrow-left"></i></a>
                <div>
                    <h1 class="page-title mb-1">Tambah Lead Baru</h1>
                    <div class="page-subtitle">Lengkapi informasi lead untuk memulai proses penjualan.</div>
                </div>
            </div>
        </div>

        <div class="lead-form-grid">
            <div class="lead-form-col">
                <section class="lead-card">
                    <h2 class="lead-card-title"><span class="lead-icon sblue"><i class="bi bi-person"></i></span>Informasi Customer</h2>

                    <div class="mb-3">
                        <label class="form-label lead-label">Nama Instansi / Perusahaan <span>*</span></label>
                        <input name="instansi" value="{{ old('instansi') }}" class="form-control lead-control" required placeholder="Masukkan nama instansi atau perusahaan">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label lead-label">PIC (Person In Charge) <span>*</span></label>
                            <input name="pic_name" value="{{ old('pic_name') }}" class="form-control lead-control" required placeholder="Masukkan nama PIC">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label lead-label">Jabatan PIC</label>
                            <input name="pic_position" value="{{ old('pic_position') }}" class="form-control lead-control" placeholder="Contoh: Kepala Laboratorium">
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <label class="form-label lead-label">No. WhatsApp <span>*</span></label>
                            <div class="lead-input-icon">
                                <input name="phone" value="{{ old('phone') }}" class="form-control lead-control" required placeholder="08xxxxxxxxxx">
                                <i class="bi bi-whatsapp"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label lead-label">Email</label>
                            <input name="email" value="{{ old('email') }}" type="email" class="form-control lead-control" placeholder="contoh@email.com">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label lead-label">Lokasi <span>*</span></label>
                        <textarea name="location" rows="3" class="form-control lead-control" required placeholder="Masukkan alamat lokasi">{{ old('location') }}</textarea>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <label class="form-label lead-label">Kota <span>*</span></label>
                            <select name="city" class="form-select lead-control" required>
                                <option value="">Pilih kota</option>
                                @foreach($cityOptions as $city)
                                    <option value="{{ $city }}" @selected(old('city')==$city)>{{ $city }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label lead-label">Tipe Instansi <span>*</span></label>
                            <select name="instansi_type" class="form-select lead-control" required>
                                <option value="">Pilih tipe instansi</option>
                                @foreach(['Universitas','Sekolah','Rumah Sakit','Industri','Pemerintah','Laboratorium Swasta','Distributor','Kontraktor','Lainnya'] as $type)
                                    <option value="{{ $type }}" @selected(old('instansi_type')==$type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section class="lead-card">
                    <h2 class="lead-card-title"><span class="lead-icon spurple"><i class="bi bi-link-45deg"></i></span>Sumber Lead</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label lead-label">Sumber Lead <span>*</span></label>
                            <select name="source" class="form-select lead-control" required>
                                <option value="">Pilih sumber lead</option>
                                @foreach(['whatsapp'=>'WhatsApp','website'=>'Website','referensi'=>'Referensi','telepon'=>'Telepon','email'=>'Email','lainnya'=>'Lainnya'] as $k=>$v)
                                    <option value="{{ $k }}" @selected(old('source')==$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label lead-label">Referensi / Dari</label>
                            <input name="reference" value="{{ old('reference') }}" class="form-control lead-control" placeholder="Masukkan referensi jika ada">
                        </div>
                    </div>
                </section>

                <section class="lead-card">
                    <h2 class="lead-card-title"><span class="lead-icon sorange"><i class="bi bi-journal-text"></i></span>Informasi Tambahan</h2>
                    <label class="form-label lead-label">Catatan Awal</label>
                    <textarea name="initial_note" rows="5" class="form-control lead-control" placeholder="Tulis catatan awal tentang lead ini...">{{ old('initial_note') }}</textarea>

                    <div class="row g-3 mt-0">
                        <div class="col-md-4">
                            <label class="form-label lead-label">Tanggal Follow Up Awal</label>
                            <input type="date" name="initial_followup_date" value="{{ old('initial_followup_date') }}" class="form-control lead-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label lead-label">Preferensi Kontak</label>
                            <select name="contact_preference" class="form-select lead-control">
                                <option value="">Pilih preferensi</option>
                                @foreach(['WhatsApp','Telepon','Email','Meeting Offline','Meeting Online'] as $pref)
                                    <option value="{{ $pref }}" @selected(old('contact_preference')==$pref)>{{ $pref }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label lead-label">Waktu Kontak Terbaik</label>
                            <input name="best_contact_time" value="{{ old('best_contact_time') }}" class="form-control lead-control" placeholder="Pagi (09.00 - 11.00)">
                        </div>
                    </div>
                </section>
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
                @if(! auth()->user()->isSales())<div class="sales-form-card"><h2 class="sales-form-title">Sales Owner</h2><select name="sales_id" class="form-select" required><option value="">Pilih sales</option>@foreach($salesList as $sales)<option value="{{ $sales->id }}" @selected((string)old('sales_id')===(string)$sales->id)>{{ $sales->name }}</option>@endforeach</select></div>@endif
            </div>
        </div>

        <div class="lead-sticky-actions">
            <a href="{{ route('sales.leads.index') }}" class="btn btn-soft">Batal</a>
            <button class="btn btn-primary">Simpan Lead</button>
        </div>
    </form>
</div>

@if(session('created_lead_id'))
<div class="modal fade lead-success-modal" id="leadSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="lead-confetti" aria-hidden="true">
                <span></span><span></span><span></span><span></span><span></span><span></span>
            </div>
            <div class="lead-success-check"><i class="bi bi-check-lg"></i></div>
            <h3>Lead Berhasil Disimpan!</h3>
            <p>Lead <strong>“{{ session('created_lead_name') }}”</strong> telah berhasil ditambahkan.</p>
            <div class="lead-success-code">
                <span>ID Lead</span>
                <button type="button" class="lead-code-copy" data-copy-text="{{ session('created_lead_code') }}">
                    {{ session('created_lead_code') }} <i class="bi bi-copy"></i>
                </button>
            </div>
            <a href="{{ route('sales.leads.show', session('created_lead_id')) }}" class="btn btn-primary w-100 mt-3">Lihat Detail Lead</a>
            <button type="button" class="btn btn-link w-100 mt-2" data-bs-dismiss="modal">Tutup</button>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('scopeInput');
    const addBtn = document.getElementById('scopeAddBtn');
    const chipList = document.getElementById('scopeChipList');

    function addScope(value) {
        const clean = (value || '').trim();
        if (!clean || !chipList) return;
        const exists = Array.from(chipList.querySelectorAll('.lead-scope-chip')).some(chip => chip.dataset.value.toLowerCase() === clean.toLowerCase());
        if (exists) { if (input) input.value = ''; return; }
        const chip = document.createElement('span');
        chip.className = 'lead-scope-chip';
        chip.dataset.value = clean;
        chip.innerHTML = `${clean} <button type="button" aria-label="Hapus kebutuhan">×</button><input type="hidden" name="scope_items[]" value="${clean.replace(/"/g, '&quot;')}">`;
        chipList.appendChild(chip);
        if (input) input.value = '';
    }

    if (addBtn) addBtn.addEventListener('click', () => addScope(input.value));
    if (input) input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); addScope(input.value); }
    });
    if (chipList) chipList.addEventListener('click', function (e) {
        if (e.target.matches('button')) e.target.closest('.lead-scope-chip').remove();
    });

    document.querySelectorAll('.lead-counter-field').forEach(function (field) {
        const counter = document.getElementById(field.dataset.counterTarget);
        const update = () => { if (counter) counter.textContent = `${field.value.length}/${field.maxLength || 500}`; };
        field.addEventListener('input', update); update();
    });

    const fileInput = document.getElementById('leadDocuments');
    const fileList = document.getElementById('leadFileList');
    const fileCount = document.getElementById('leadFileCount');
    if (fileInput && fileList) {
        fileInput.addEventListener('change', function () {
            const files = Array.from(fileInput.files).slice(0, parseInt(fileInput.dataset.maxFiles || 5));
            if (fileCount) fileCount.textContent = `${files.length}/5`;
            if (!files.length) {
                fileList.innerHTML = '<div class="lead-empty-file">Belum ada file yang diunggah</div>';
                return;
            }
            fileList.innerHTML = files.map(file => `<div class="lead-file-item"><i class="bi bi-file-earmark"></i><span>${file.name}</span></div>`).join('');
        });
    }

    const modalEl = document.getElementById('leadSuccessModal');
    if (modalEl && window.bootstrap) new bootstrap.Modal(modalEl).show();

    document.querySelectorAll('[data-copy-text]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            navigator.clipboard?.writeText(btn.dataset.copyText);
        });
    });
});
</script>
@endpush
