@extends('layouts.app')
@section('title', 'Edit Lead')

@section('content')
@php
    $scopeItems = old('scope_items', $lead->scope_items ?: []);
    $cityOptions = ['Jakarta','Bandung','Surabaya','Semarang','Yogyakarta','Malang','Denpasar','Medan','Makassar','Balikpapan','Lainnya'];
    $selectedCity = old('city', $lead->city);
@endphp
<div class="sales-ui lead-layout-page">
    <form method="POST" action="{{ route('sales.leads.update', $lead) }}" enctype="multipart/form-data" class="lead-form-shell">
        @csrf
        @method('PUT')
        <div class="lead-page-head">
            <div class="lead-title-wrap">
                <a href="{{ route('sales.leads.show', $lead) }}" class="lead-back-btn"><i class="bi bi-arrow-left"></i></a>
                <div>
                    <h1 class="page-title mb-1">Edit Lead</h1>
                    <div class="page-subtitle">{{ $lead->code }} · Perbarui informasi lead dan customer yang terhubung.</div>
                </div>
            </div>
        </div>

        <div class="lead-form-grid">
            <div class="lead-form-col">
                <section class="lead-card">
                    <h2 class="lead-card-title"><span class="lead-icon sblue"><i class="bi bi-person"></i></span>Informasi Customer</h2>

                    <div class="mb-3">
                        <label class="form-label lead-label">Nama Instansi / Perusahaan <span>*</span></label>
                        <input name="instansi" value="{{ old('instansi', $lead->instansi) }}" class="form-control lead-control" required placeholder="Masukkan nama instansi atau perusahaan">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label lead-label">PIC (Person In Charge) <span>*</span></label>
                            <input name="pic_name" value="{{ old('pic_name', $lead->pic_name) }}" class="form-control lead-control" required placeholder="Masukkan nama PIC">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label lead-label">Jabatan PIC</label>
                            <input name="pic_position" value="{{ old('pic_position', $lead->pic_position) }}" class="form-control lead-control" placeholder="Contoh: Kepala Laboratorium">
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <label class="form-label lead-label">No. WhatsApp <span>*</span></label>
                            <div class="lead-input-icon">
                                <input name="phone" value="{{ old('phone', $lead->phone) }}" class="form-control lead-control" required placeholder="08xxxxxxxxxx">
                                <i class="bi bi-whatsapp"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label lead-label">Email</label>
                            <input name="email" value="{{ old('email', $lead->email) }}" type="email" class="form-control lead-control" placeholder="contoh@email.com">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label lead-label">Lokasi <span>*</span></label>
                        <textarea name="location" rows="3" class="form-control lead-control" required placeholder="Masukkan alamat lokasi">{{ old('location', $lead->location) }}</textarea>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <label class="form-label lead-label">Kota <span>*</span></label>
                            <select name="city" class="form-select lead-control" required>
                                <option value="">Pilih kota</option>
                                @if($selectedCity && !in_array($selectedCity, $cityOptions))
                                    <option value="{{ $selectedCity }}" selected>{{ $selectedCity }}</option>
                                @endif
                                @foreach($cityOptions as $city)
                                    <option value="{{ $city }}" @selected($selectedCity==$city)>{{ $city }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label lead-label">Tipe Instansi <span>*</span></label>
                            <select name="instansi_type" class="form-select lead-control" required>
                                <option value="">Pilih tipe instansi</option>
                                @foreach(['Universitas','Sekolah','Rumah Sakit','Industri','Pemerintah','Laboratorium Swasta','Distributor','Kontraktor','Lainnya'] as $type)
                                    <option value="{{ $type }}" @selected(old('instansi_type', $lead->instansi_type)==$type)>{{ $type }}</option>
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
                                    <option value="{{ $k }}" @selected(old('source', $lead->source)==$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label lead-label">Referensi / Dari</label>
                            <input name="reference" value="{{ old('reference', $lead->reference) }}" class="form-control lead-control" placeholder="Masukkan referensi jika ada">
                        </div>
                    </div>
                </section>

                <section class="lead-card">
                    <h2 class="lead-card-title"><span class="lead-icon sorange"><i class="bi bi-journal-text"></i></span>Informasi Tambahan</h2>
                    <label class="form-label lead-label">Catatan Awal</label>
                    <textarea name="initial_note" rows="5" class="form-control lead-control" placeholder="Tulis catatan awal tentang lead ini...">{{ old('initial_note', $lead->initial_note) }}</textarea>

                    <div class="row g-3 mt-0">
                        <div class="col-md-4">
                            <label class="form-label lead-label">Tanggal Follow Up Awal</label>
                            <input type="date" name="initial_followup_date" value="{{ old('initial_followup_date', optional($lead->initial_followup_date)->format('Y-m-d')) }}" class="form-control lead-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label lead-label">Preferensi Kontak</label>
                            <select name="contact_preference" class="form-select lead-control">
                                <option value="">Pilih preferensi</option>
                                @foreach(['WhatsApp','Telepon','Email','Meeting Offline','Meeting Online'] as $pref)
                                    <option value="{{ $pref }}" @selected(old('contact_preference', $lead->contact_preference)==$pref)>{{ $pref }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label lead-label">Waktu Kontak Terbaik</label>
                            <input name="best_contact_time" value="{{ old('best_contact_time', $lead->best_contact_time) }}" class="form-control lead-control" placeholder="Pagi (09.00 - 11.00)">
                        </div>
                    </div>
                </section>
            </div>

            <div class="lead-form-col">
                <section class="lead-card">
                    <h2 class="lead-card-title"><span class="lead-icon sgreen"><i class="bi bi-clipboard-check"></i></span>Kebutuhan Awal</h2>

                    <div class="mb-3">
                        <label class="form-label lead-label">Nama Laboratorium / Proyek <span>*</span></label>
                        <input name="lab_name" value="{{ old('lab_name', $lead->lab_name) }}" class="form-control lead-control" required placeholder="Contoh: Laboratorium Kimia">
                    </div>

                    <label class="form-label lead-label">Deskripsi Kebutuhan</label>
                    <div class="lead-textarea-wrap">
                        <textarea name="need_description" rows="5" maxlength="500" class="form-control lead-control lead-counter-field" data-counter-target="needCounter" placeholder="Jelaskan kebutuhan laboratorium / peralatan yang dibutuhkan...">{{ old('need_description', $lead->need_description) }}</textarea>
                        <span id="needCounter" class="lead-counter">{{ strlen(old('need_description', $lead->need_description ?? '')) }}/500</span>
                    </div>

                    <div class="mt-3">
                        <label class="form-label lead-label">Daftar Kebutuhan <span class="fw-normal">(contoh: Wall Bench, Fume Hood, Sink, dll)</span></label>
                        <div class="lead-chip-input">
                            <input type="text" id="scopeInput" class="form-control lead-control" placeholder="Ketik kebutuhan lalu tekan Enter">
                            <button type="button" class="btn btn-soft" id="scopeAddBtn"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
                        </div>
                        <div class="lead-chip-list" id="scopeChipList">
                            @foreach($scopeItems as $scope)
                                @if(trim($scope) !== '')
                                    <span class="lead-scope-chip" data-value="{{ $scope }}">{{ $scope }} <button type="button" aria-label="Hapus kebutuhan">×</button><input type="hidden" name="scope_items[]" value="{{ $scope }}"></span>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label lead-label">Kapasitas / Pengguna</label>
                        <input name="capacity" value="{{ old('capacity', $lead->capacity) }}" class="form-control lead-control" placeholder="Contoh: 40 Mahasiswa / 10 Peneliti">
                    </div>
                </section>

                <section class="lead-card">
                    <h2 class="lead-card-title"><span class="lead-icon sorange"><i class="bi bi-flag"></i></span>Estimasi & Prioritas</h2>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label lead-label">Estimasi Potensi <span class="fw-normal">(Opsional)</span></label>
                            <input data-rupiah name="est_value_min" value="{{ old('est_value_min', $lead->est_value_min) }}" class="form-control lead-control" placeholder="Contoh: 500.000.000">
                            <small class="text-muted-2">Dari (Rp)</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label lead-label d-none d-md-block">&nbsp;</label>
                            <input data-rupiah name="est_value_max" value="{{ old('est_value_max', $lead->est_value_max) }}" class="form-control lead-control" placeholder="Contoh: 1.000.000.000">
                            <small class="text-muted-2">Sampai (Rp)</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label lead-label">Prioritas Lead <span>*</span></label>
                            <select name="priority" class="form-select lead-control" required>
                                <option value="">Pilih prioritas</option>
                                <option value="high" @selected(old('priority', $lead->priority)=='high')>High (Tinggi)</option>
                                <option value="medium" @selected(old('priority', $lead->priority)=='medium')>Medium</option>
                                <option value="low" @selected(old('priority', $lead->priority)=='low')>Low</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="lead-card">
                    <h2 class="lead-card-title"><span class="lead-icon sblue"><i class="bi bi-file-earmark-arrow-up"></i></span>Dokumen Pendukung <span class="text-muted-2 fw-normal">(Opsional)</span></h2>
                    <div class="lead-upload-grid">
                        <label class="lead-upload-box">
                            <input type="file" name="documents[]" id="leadDocuments" multiple accept=".pdf,.jpg,.jpeg,.png" class="d-none" data-max-files="5">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <strong>Klik atau drag & drop file di sini</strong>
                            <span>PDF, JPG, PNG (Max 10MB)</span>
                        </label>
                        <div class="lead-file-panel">
                            <div class="lead-file-head"><span>File yang diunggah</span><b id="leadFileCount">0/5</b></div>
                            <div class="lead-file-list" id="leadFileList">
                                <div class="lead-empty-file">Belum ada file baru yang dipilih</div>
                            </div>
                        </div>
                    </div>
                    @if($lead->documents->count())
                        <div class="lead-existing-docs mt-3">
                            <div class="small fw-bold mb-2">Dokumen saat ini</div>
                            @foreach($lead->documents as $doc)
                                <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" class="lead-existing-doc">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span>{{ $doc->name }}</span>
                                    <small>{{ $doc->humanSize() }}</small>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>

        <div class="lead-sticky-actions">
            <a href="{{ route('sales.leads.show', $lead) }}" class="btn btn-soft">Batal</a>
            <button class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
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
                fileList.innerHTML = '<div class="lead-empty-file">Belum ada file baru yang dipilih</div>';
                return;
            }
            fileList.innerHTML = files.map(file => `<div class="lead-file-item"><i class="bi bi-file-earmark"></i><span>${file.name}</span></div>`).join('');
        });
    }
});
</script>
@endpush
