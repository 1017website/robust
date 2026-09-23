@extends('layouts.app')
@section('title', 'Tambah Request Process')
@section('content')
@php
    $quotation = $sourceProject?->quotation;
    $designRequest = $quotation?->designRequest;
    $quotationDocuments = $quotation?->documents ?? collect();
    $defaultManager = $designRequest?->production_pic_id;
    $defaultTeam = $defaultManager ? [(string) $defaultManager] : [];
    $defaultStatus = $designRequest ? 'planning' : 'ongoing';
    $defaultScope = $designRequest?->detail_need ?: $quotation?->items?->pluck('name')->filter()->implode(', ');
@endphp
<div class="sales-ui request-process-create">
    <form method="POST" action="{{ route('sales.projects.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="sales-page-head">
            <div class="sales-title-wrap">
                <a href="{{ route('sales.projects.index') }}" class="btn btn-soft" aria-label="Kembali"><i class="bi bi-arrow-left"></i></a>
                <div>
                    <div class="small fw-bold text-primary mb-1">Request Process &gt; Tambah Request Process</div>
                    <h1 class="page-title mb-1">Tambah Request Process</h1>
                    <div class="page-subtitle">Pilih Project yang sudah diajukan. Data yang tersedia akan dimuat otomatis.</div>
                </div>
            </div>
        </div>

        <section class="sales-form-card">
            <h2 class="sales-form-title">1. Sumber Project</h2>
            <label class="form-label small fw-bold" for="sourceProjectSelect">Project *</label>
            <select name="purchase_order_request_id" id="sourceProjectSelect" class="form-select" data-create-url="{{ route('sales.projects.create') }}" required>
                <option value="">Pilih Project</option>
                @foreach($availableProjects as $projectOption)
                    <option value="{{ $projectOption->id }}" @selected((string) old('purchase_order_request_id', $sourceProject?->id) === (string) $projectOption->id)>
                        {{ $projectOption->projectNumber() }} — {{ $projectOption->quotation?->project_name ?: $projectOption->customer_name }} — {{ $projectOption->customer_name ?: $projectOption->quotation?->customer_name }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">Hanya Project yang sudah diajukan dan belum memiliki Request Process yang dapat dipilih.</div>

            @if($sourceProject)
                <div class="request-source-summary mt-3">
                    <span class="status-soft st-green">Project dipilih</span>
                    <div class="kv"><div class="k">Nomor Project</div><div class="v">{{ $sourceProject->projectNumber() }}</div></div>
                    <div class="kv"><div class="k">Customer</div><div class="v">{{ $sourceProject->customer_name ?: $quotation?->customer_name ?: '-' }}</div></div>
                    <div class="kv"><div class="k">PIC Customer</div><div class="v">{{ $sourceProject->delivery_pic_name ?: $quotation?->pic_name ?: '-' }}</div></div>
                    <div class="kv"><div class="k">Penawaran</div><div class="v">{{ $quotation?->code ?: '-' }}</div></div>
                    <div class="kv"><div class="k">Sales</div><div class="v">{{ $quotation?->sales?->name ?: '-' }}</div></div>
                </div>
            @elseif($availableProjects->isEmpty())
                <div class="alert alert-light border mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Belum ada Project diajukan yang menunggu pembuatan Request Process.</div>
            @endif
        </section>

        <fieldset class="border-0 p-0 m-0" @disabled(!$sourceProject)>
            <div class="request-process-grid">
                <div class="request-process-column">
                    <section class="sales-form-card">
                        <h2 class="sales-form-title">2. Informasi Request Process</h2>
                        <label class="form-label small fw-bold">Nama Project *</label>
                        <input name="name" value="{{ old('name', $quotation?->project_name) }}" class="form-control" required>

                        <label class="form-label small fw-bold mt-3">Kode Request Process</label>
                        <input name="code" value="{{ old('code', $sourceProject?->projectNumber()) }}" class="form-control" placeholder="Mengikuti nomor Project">

                        <label class="form-label small fw-bold mt-3">Deskripsi Project</label>
                        <textarea name="description" rows="4" class="form-control">{{ old('description', $designRequest?->short_description) }}</textarea>

                        <div class="row g-3 mt-0">
                            <div class="col-md-6"><label class="form-label small fw-bold">Kategori</label><input name="category" class="form-control" value="{{ old('category', 'Produksi') }}"></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Jenis Project</label><input name="type" class="form-control" value="{{ old('type', 'Purchase Order') }}"></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Prioritas *</label><select name="priority" class="form-select" required>@foreach(['high'=>'Tinggi','medium'=>'Medium','low'=>'Low'] as $key=>$label)<option value="{{ $key }}" @selected(old('priority', $quotation?->priority ?? 'medium') === $key)>{{ $label }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Status Awal *</label><select name="status" class="form-select" required>@foreach(\App\Models\Project::statuses() as $key=>$label)<option value="{{ $key }}" @selected(old('status', $defaultStatus) === $key)>{{ $label }}</option>@endforeach</select></div>
                        </div>
                    </section>

                    <section class="sales-form-card">
                        <h2 class="sales-form-title">3. Informasi Pelaksanaan</h2>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label small fw-bold">Tanggal Mulai *</label><input name="start_date" type="date" value="{{ old('start_date', optional($sourceProject?->accurate_po_date ?: $sourceProject?->request_date)->format('Y-m-d') ?? date('Y-m-d')) }}" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Tanggal Target *</label><input name="target_date" type="date" value="{{ old('target_date', optional($sourceProject?->expected_delivery_date)->format('Y-m-d')) }}" class="form-control" required></div>
                            <div class="col-12"><label class="form-label small fw-bold">Metode Pengerjaan</label><select name="work_method" class="form-select"><option value="production_order" @selected(old('work_method', 'production_order') === 'production_order')>Production Order</option><option value="Turnkey" @selected(old('work_method') === 'Turnkey')>Turnkey</option><option value="Supply Only" @selected(old('work_method') === 'Supply Only')>Supply Only</option><option value="Instalasi" @selected(old('work_method') === 'Instalasi')>Instalasi</option></select></div>
                            <div class="col-12"><label class="form-label small fw-bold">Lokasi Project</label><textarea name="location" rows="3" class="form-control">{{ old('location', $sourceProject?->delivery_address ?: $quotation?->customer?->address) }}</textarea></div>
                            <div class="col-12"><label class="form-label small fw-bold">Ruang Lingkup Pekerjaan</label><textarea name="scope_of_work" rows="5" class="form-control">{{ old('scope_of_work', $defaultScope) }}</textarea></div>
                        </div>
                    </section>
                </div>

                <div class="request-process-column">
                    <section class="sales-form-card">
                        <h2 class="sales-form-title">4. Nilai &amp; Pembayaran</h2>
                        <label class="form-label small fw-bold">Nilai Project</label>
                        <input class="form-control" value="{{ $quotation ? \App\Support\Format::rupiah($quotation->subtotal - $quotation->discount_amount) : '' }}" readonly>
                        <div class="row g-3 mt-0">
                            <div class="col-md-6"><label class="form-label small fw-bold">PPN</label><input class="form-control" value="{{ $quotation ? \App\Support\Format::rupiah($quotation->tax_amount) : '' }}" readonly></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Total Project</label><input class="form-control fw-bold text-primary" value="{{ $quotation ? \App\Support\Format::rupiah($quotation->grand_total) : '' }}" readonly></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Mata Uang</label><input class="form-control" value="{{ $quotation?->currency ?: 'IDR' }}" readonly></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Skema Pembayaran</label><input name="payment_scheme" class="form-control" value="{{ old('payment_scheme', $sourceProject?->payment_term) }}"></div>
                        </div>
                    </section>

                    <section class="sales-form-card">
                        <h2 class="sales-form-title">5. Tim Project</h2>
                        <label class="form-label small fw-bold">Project Manager *</label>
                        <select name="project_manager_id" class="form-select" required><option value="">Pilih Project Manager</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected((string) old('project_manager_id', $defaultManager) === (string) $manager->id)>{{ $manager->name }} — {{ $manager->roleLabel() }}</option>@endforeach</select>
                        <fieldset class="mt-3">
                            <legend class="form-label small fw-bold mb-2">Tim Internal</legend>
                            <div class="team-choice-grid">
                                @foreach($team as $member)
                                    <label class="team-choice">
                                        <input type="checkbox" name="internal_team[]" value="{{ $member->id }}" @checked(in_array((string) $member->id, array_map('strval', old('internal_team', $defaultTeam)), true))>
                                        <span><strong>{{ $member->name }}</strong><small>{{ $member->roleLabel() }}</small></span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="form-text mt-2">Pilih satu atau beberapa anggota yang terlibat dalam Request Process.</div>
                        </fieldset>
                        <label class="form-label small fw-bold mt-3">Tim Eksternal / Vendor</label>
                        <input name="external_vendor" value="{{ old('external_vendor') }}" class="form-control" placeholder="Vendor jika ada">
                    </section>

                    <section class="sales-form-card">
                        <h2 class="sales-form-title">6. Dokumen &amp; Lampiran</h2>
                        <div class="request-document-list">
                            @if($sourceProject?->customer_po_file)
                                <a href="{{ asset('storage/'.$sourceProject->customer_po_file) }}" target="_blank" rel="noopener" class="request-document-row">
                                    <i class="bi bi-file-earmark-check text-success"></i><span><strong>Dokumen PO Customer</strong><small>{{ basename($sourceProject->customer_po_file) }}</small></span><i class="bi bi-box-arrow-up-right ms-auto"></i>
                                </a>
                            @endif
                            @foreach($quotationDocuments as $document)
                                <a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" class="request-document-row">
                                    <i class="bi bi-file-earmark-text text-primary"></i><span><strong>{{ $document->name }}.{{ $document->file_type }}</strong><small>{{ $document->humanSize() }} · {{ $document->category === 'quotation_file' ? 'Penawaran utama' : 'Dokumen pendukung' }}</small></span><i class="bi bi-box-arrow-up-right ms-auto"></i>
                                </a>
                            @endforeach
                            @if(!$sourceProject?->customer_po_file && $quotationDocuments->isEmpty())
                                <div class="request-document-empty"><i class="bi bi-paperclip"></i><span>Belum ada dokumen yang terlampir pada Project ini.</span></div>
                            @endif
                        </div>
                        <div class="mt-3">
                            <label class="form-label small fw-bold" for="requestProcessPoFile">{{ $sourceProject?->customer_po_file ? 'Ganti Dokumen PO' : 'Upload Dokumen PO' }}</label>
                            <input id="requestProcessPoFile" type="file" name="customer_po_file" class="form-control @error('customer_po_file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                            @error('customer_po_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">PDF, JPG, PNG, Word, atau Excel. File ini tersimpan sebagai Dokumen PO Project sumber.</div>
                        </div>
                    </section>

                    <section class="sales-form-card">
                        <h2 class="sales-form-title">7. Catatan Tambahan</h2>
                        <textarea name="note" rows="5" class="form-control" placeholder="Catatan tambahan terkait proses project">{{ old('note') }}</textarea>
                    </section>
                </div>
            </div>
        </fieldset>
        <div class="form-submit-actions">
            <a href="{{ route('sales.projects.index') }}" class="btn btn-soft">Batal</a>
            <button type="submit" class="btn btn-primary" @disabled(!$sourceProject)><i class="bi bi-check-square me-1"></i>Simpan Request Process</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('sourceProjectSelect');
    select?.addEventListener('change', function () {
        var url = new URL(select.dataset.createUrl, window.location.origin);
        if (select.value) url.searchParams.set('project', select.value);
        window.location.assign(url.toString());
    });
});
</script>
@endpush
