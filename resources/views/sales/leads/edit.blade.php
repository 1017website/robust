@extends('layouts.app')
@section('title', 'Edit Lead')

@section('content')
@php
    $scopeItems = old('scope_items', $lead->scope_items ?: []);
    $selectedCity = old('city', $lead->city);
    // Pilihan baku sama dengan form tambah lead; item lama di luar daftar ikut ditampilkan agar tidak hilang saat disimpan.
    $scopeOptions = collect(['Wall Bench','Fume Hood','Storage Cabinet','Sink Area','Meja Praktikum','Meja Instrumen','Safety Equipment','Lainnya']);
    $scopeSelected = collect($scopeItems)->map(fn ($item) => trim((string) $item))->filter();
    $scopeOptions = $scopeOptions->merge($scopeSelected->reject(fn ($item) => $scopeOptions->contains(fn ($option) => strcasecmp($option, $item) === 0)))->values();
    $scopeChecked = fn ($option) => $scopeSelected->contains(fn ($item) => strcasecmp($option, $item) === 0);
@endphp
<div class="sales-ui lead-layout-page lead-create-page">
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
                            <label class="form-label lead-label">Divisi</label>
                            <input name="division" value="{{ old('division', $lead->division) }}" class="form-control lead-control" placeholder="Contoh: Laboratorium, Procurement, R&D">
                        </div>
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
                            <input name="city" list="indonesianCityOptions" value="{{ $selectedCity }}" class="form-control lead-control" required placeholder="Pilih atau ketik nama kota/kabupaten">
                            <x-city-datalist />
                            <div class="form-text">Seluruh kota &amp; kabupaten di Indonesia tersedia sebagai saran; nama lain tetap dapat diketik manual.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label lead-label">Tipe Instansi <span>*</span></label>
                            <select name="instansi_type" class="form-select lead-control" required>
                                <option value="">Pilih tipe instansi</option>
                                @foreach(\App\Models\Lead::instansiTypes() as $type)
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
                                @foreach(\App\Models\PraLead::sources() as $k=>$v)
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
                    <textarea name="need_description" rows="5" maxlength="500" class="form-control lead-control" placeholder="Jelaskan kebutuhan laboratorium / peralatan yang dibutuhkan...">{{ old('need_description', $lead->need_description) }}</textarea>
                    <div class="mt-3">
                        <label class="form-label lead-label">Daftar Kebutuhan</label>
                        <div class="lead-scope-options">
                            @foreach($scopeOptions as $item)
                                <label class="lead-scope-option"><input type="checkbox" name="scope_items[]" value="{{ $item }}" @checked($scopeChecked($item))><span>{{ $item }}</span></label>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label lead-label">Kapasitas / Pengguna</label>
                        <input name="capacity" value="{{ old('capacity', $lead->capacity) }}" class="form-control lead-control" placeholder="Contoh: 40 Mahasiswa / 10 Peneliti">
                    </div>
                </section>

                <section class="lead-card">
                    <h2 class="lead-card-title"><span class="lead-icon sorange"><i class="bi bi-flag"></i></span>Estimasi &amp; Prioritas</h2>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label lead-label">Estimasi Dari (Rp)</label><input data-rupiah name="est_value_min" value="{{ old('est_value_min', $lead->est_value_min) }}" class="form-control lead-control" placeholder="500.000.000"></div>
                        <div class="col-md-4"><label class="form-label lead-label">Sampai (Rp)</label><input data-rupiah name="est_value_max" value="{{ old('est_value_max', $lead->est_value_max) }}" class="form-control lead-control" placeholder="1.000.000.000"></div>
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
                    <label class="form-label lead-label" for="leadDocuments">Tambah Dokumen</label>
                    <input type="file" name="documents[]" id="leadDocuments" multiple data-multi-file accept=".pdf,.jpg,.jpeg,.png" class="form-control lead-control">
                    <div class="form-text">PDF, JPG, PNG. Dokumen baru ditambahkan tanpa menghapus dokumen yang sudah ada.</div>
                    @if($lead->documents->count())
                        <div class="lead-existing-docs mt-3">
                            <div class="lead-label mb-2">Dokumen saat ini</div>
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

                @if(! auth()->user()->isSales())
                    <section class="lead-card">
                        <h2 class="lead-card-title"><span class="lead-icon sblue"><i class="bi bi-person-badge"></i></span>Sales Owner</h2>
                        <label class="form-label lead-label" for="leadSalesOwner">Sales yang Ditugaskan <span>*</span></label>
                        <select name="sales_id" id="leadSalesOwner" class="form-select lead-control" required><option value="">Pilih sales</option>@foreach($salesList as $sales)<option value="{{ $sales->id }}" @selected((string)old('sales_id',$lead->sales_id)===(string)$sales->id)>{{ $sales->name }}</option>@endforeach</select>
                    </section>
                @endif
            </div>
        </div>

        <div class="form-submit-actions">
            <a href="{{ route('sales.leads.show',$lead) }}" class="btn btn-soft">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
