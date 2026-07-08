@extends('layouts.app')
@section('title', 'Design Request Baru')
@section('content')
@php
    $scopeOptions = ['Wall Bench', 'Island Bench', 'Fume Hood', 'Storage Cabinet', 'Sink Area', 'Meja Praktikum', 'Lemari Asam', 'Instalasi MEP'];
    $outputOptions = [
        'layout_2d' => ['label' => 'Layout 2D', 'desc' => 'Denah dan tata letak'],
        'rendering_3d' => ['label' => 'Rendering 3D', 'desc' => 'Visual interior lab'],
        'shop_drawing' => ['label' => 'Shop Drawing', 'desc' => 'Gambar kerja produksi'],
        'boq' => ['label' => 'BOQ', 'desc' => 'Bill of Quantity'],
        'cost_estimation' => ['label' => 'Costing', 'desc' => 'Estimasi biaya awal'],
    ];
    $selectedScopes = collect(old('scope_checklist', $lead?->scope_items ?? []))->filter()->values()->all();
    $selectedOutputs = collect(old('outputs', ['layout_2d', 'boq', 'cost_estimation']))->filter()->values()->all();
    $selectedCustomerId = old('customer_id', $lead?->customer_id);
    $defaultCustomerName = old('customer_name', $lead?->customer?->name ?? $lead?->instansi);
    $defaultPicName = old('pic_name', $lead?->pic_name ?? $lead?->customer?->primaryPic?->name);
@endphp

<div class="sales-ui">
    <div class="sales-page-head align-items-center">
        <div class="sales-title-wrap">
            <a href="{{ route('sales.design-requests.index') }}" class="btn btn-soft"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h1 class="page-title mb-1">Design Request Baru</h1>
                <div class="page-subtitle">Pilih customer, lengkapi kebutuhan, lalu tentukan drafter yang mengerjakan.</div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('sales.design-requests.store') }}" id="designRequestForm">
        @csrf
        @if($lead)
            <input type="hidden" name="lead_id" value="{{ $lead->id }}">
            <div class="alert alert-info border-0 shadow-sm mb-3">
                <i class="bi bi-link-45deg me-1"></i>
                Design request ini dibuat dari lead <strong>{{ $lead->code }}</strong> - {{ $lead->instansi }}.
            </div>
        @endif

        <div class="row g-3 align-items-start">
            <div class="col-xl-8">
                <div class="sales-form-card">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="ico sblue rounded-3 d-inline-grid" style="width:36px;height:36px;place-items:center"><i class="bi bi-building"></i></div>
                        <h2 class="sales-form-title mb-0">Customer</h2>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Pilih Customer</label>
                            <select name="customer_id" id="customer_id" class="form-select select2" data-placeholder="Pilih customer yang sudah ada">
                                <option value=""></option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                        data-name="{{ e($customer->name) }}"
                                        data-pic="{{ e($customer->primaryPic?->name) }}"
                                        data-type="{{ e($customer->type ?: $customer->category) }}"
                                        @selected((string) $selectedCustomerId === (string) $customer->id)>
                                        {{ $customer->name }}{{ $customer->city ? ' - '.$customer->city : '' }}{{ $customer->primaryPic?->name ? ' / '.$customer->primaryPic->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Jika customer belum ada, isi manual pada kolom nama customer di bawah. Sistem akan menghubungkannya ke master customer.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Customer / Instansi <span class="text-danger">*</span></label>
                            <input name="customer_name" id="customer_name" value="{{ $defaultCustomerName }}" class="form-control" placeholder="Contoh: Universitas Airlangga">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">PIC Customer</label>
                            <input name="pic_name" id="pic_name" value="{{ $defaultPicName }}" class="form-control" placeholder="Nama PIC customer">
                        </div>
                    </div>
                </div>

                <div class="sales-form-card">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="ico sgreen rounded-3 d-inline-grid" style="width:36px;height:36px;place-items:center"><i class="bi bi-clipboard2-check"></i></div>
                        <h2 class="sales-form-title mb-0">Kebutuhan Desain</h2>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Nama Laboratorium / Proyek <span class="text-danger">*</span></label>
                            <input name="project_name" value="{{ old('project_name', $lead?->lab_name) }}" class="form-control" required placeholder="Contoh: Laboratorium Kimia">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Jenis Kebutuhan</label>
                            <input name="lab_type" id="lab_type" value="{{ old('lab_type', $lead?->instansi_type) }}" class="form-control" placeholder="Furniture lab, renovasi, fume hood...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tanggal Request <span class="text-danger">*</span></label>
                            <input type="date" name="request_date" value="{{ old('request_date', now()->format('Y-m-d')) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Deadline <span class="text-danger">*</span></label>
                            <input type="date" name="deadline" value="{{ old('deadline', now()->addDays(7)->format('Y-m-d')) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Prioritas <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>
                                <option value="low" @selected(old('priority', $lead?->priority) === 'low')>Low</option>
                                <option value="medium" @selected(old('priority', $lead?->priority ?? 'medium') === 'medium')>Medium</option>
                                <option value="high" @selected(old('priority', $lead?->priority) === 'high')>High</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Kapasitas / Pengguna</label>
                            <input name="capacity" value="{{ old('capacity', $lead?->capacity) }}" class="form-control" placeholder="Contoh: 40 mahasiswa">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Ringkasan Singkat <span class="text-danger">*</span></label>
                            <input name="short_description" value="{{ old('short_description', $lead?->need_description) }}" class="form-control" maxlength="500" required placeholder="Ringkas kebutuhan utama customer">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Detail Kebutuhan <span class="text-danger">*</span></label>
                            <textarea name="detail_need" rows="5" class="form-control" required maxlength="1000" placeholder="Jelaskan kebutuhan detail, kondisi lokasi, ukuran awal, atau catatan teknis dari customer...">{{ old('detail_need', $lead?->need_description) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="sales-form-card">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                        <h2 class="sales-form-title mb-0">Daftar Scope & Output</h2>
                        <span class="small text-muted-2">Pilih item yang dibutuhkan drafter</span>
                    </div>
                    <label class="form-label small fw-bold">Scope Kebutuhan</label>
                    <div class="d-flex flex-wrap gap-2 mb-3" id="scopeList">
                        @foreach($scopeOptions as $scope)
                            <label class="tag-pill bg-white border text-dark m-0">
                                <input type="checkbox" class="form-check-input m-0" name="scope_checklist[]" value="{{ $scope }}" @checked(in_array($scope, $selectedScopes, true))>
                                {{ $scope }}
                            </label>
                        @endforeach
                        @foreach(array_diff($selectedScopes, $scopeOptions) as $customScope)
                            <label class="tag-pill bg-white border text-dark m-0">
                                <input type="checkbox" class="form-check-input m-0" name="scope_checklist[]" value="{{ $customScope }}" checked>
                                {{ $customScope }}
                            </label>
                        @endforeach
                    </div>
                    <div class="input-group mb-4">
                        <input type="text" id="scopeInput" class="form-control" placeholder="Tambah scope lain, lalu klik Tambah">
                        <button type="button" class="btn btn-soft" id="addScope"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
                    </div>

                    <label class="form-label small fw-bold">Output yang Diminta</label>
                    <div class="row g-2">
                        @foreach($outputOptions as $key => $output)
                            <div class="col-md-4">
                                <label class="info-card d-block h-100 mb-0" style="cursor:pointer">
                                    <div class="d-flex gap-2 align-items-start">
                                        <input type="checkbox" class="form-check-input mt-1" name="outputs[]" value="{{ $key }}" @checked(in_array($key, $selectedOutputs, true))>
                                        <span>
                                            <strong>{{ $output['label'] }}</strong>
                                            <small class="d-block text-muted-2 mt-1">{{ $output['desc'] }}</small>
                                        </span>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="sales-form-card">
                    <h2 class="sales-form-title">Catatan Tambahan</h2>
                    <textarea name="extra_note" rows="4" class="form-control" maxlength="500" placeholder="Catatan internal untuk drafter / produksi...">{{ old('extra_note') }}</textarea>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="sales-form-card">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="ico spurple rounded-3 d-inline-grid" style="width:36px;height:36px;place-items:center"><i class="bi bi-person-workspace"></i></div>
                        <h2 class="sales-form-title mb-0">Pilih Drafter</h2>
                    </div>
                    @if($drafterWorkloads->isNotEmpty())
                        <div class="sales-recommend-grid mb-3">
                            @foreach($drafterWorkloads->take(3) as $row)
                                <label class="sales-recommend-card">
                                    <input type="radio" name="drafter_quick_pick" value="{{ $row['drafter']->id }}" data-drafter-pick>
                                    <div class="avatar-sm">{{ strtoupper(substr($row['drafter']->name,0,1)) }}</div>
                                    <div>
                                        @if($loop->first)<span class="recommend-badge">Recommended</span>@endif
                                        <div class="fw-semibold small">{{ $row['drafter']->name }}</div>
                                        <div class="small text-muted-2">{{ $row['active_requests'] }} Request Aktif</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <label class="form-label small fw-bold">Drafter yang Mengerjakan <span class="text-danger">*</span></label>
                    <select name="production_pic_id" id="production_pic_id" class="form-select" required>
                        <option value="">Pilih Drafter</option>
                        @foreach($drafters as $drafter)
                            <option value="{{ $drafter->id }}" @selected((string) old('production_pic_id') === (string) $drafter->id)>{{ $drafter->name }}{{ $drafter->job_title ? ' - '.$drafter->job_title : '' }}</option>
                        @endforeach
                    </select>
                    @if($drafters->isEmpty())
                        <div class="alert alert-warning mt-3 mb-0">Belum ada user dengan role Drafter yang aktif.</div>
                    @endif
                    <label class="form-label small fw-bold mt-3">Catatan untuk Drafter</label>
                    <textarea name="production_note" rows="4" class="form-control" maxlength="300" placeholder="Contoh: prioritaskan layout dan BOQ terlebih dahulu...">{{ old('production_note') }}</textarea>
                </div>

                <div class="sales-form-card">
                    <h2 class="sales-form-title">Ringkasan</h2>
                    <div class="kv"><div class="k">Customer</div><div class="v" id="summaryCustomer">{{ $defaultCustomerName ?: '-' }}</div></div>
                    <div class="kv"><div class="k">PIC</div><div class="v" id="summaryPic">{{ $defaultPicName ?: '-' }}</div></div>
                    <div class="kv"><div class="k">Sales</div><div class="v">{{ auth()->user()->name }}</div></div>
                    <div class="kv"><div class="k">Status Awal</div><div class="v"><span class="status-soft st-yellow">Assigned ke Drafter</span></div></div>
                    <div class="alert alert-light border small mt-3 mb-0">Setelah disimpan, request akan masuk ke menu drafter yang dipilih.</div>
                </div>

                <div class="d-grid gap-2 sticky-actions" style="position:sticky;bottom:16px">
                    <button type="submit" name="action" value="send" class="btn btn-primary btn-lg"><i class="bi bi-send me-1"></i>Simpan & Kirim ke Drafter</button>
                    <button type="submit" name="action" value="draft" class="btn btn-soft">Simpan Draft</button>
                    <a href="{{ route('sales.design-requests.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function(){
    const customerSelect = document.getElementById('customer_id');
    const customerName = document.getElementById('customer_name');
    const picName = document.getElementById('pic_name');
    const labType = document.getElementById('lab_type');
    const summaryCustomer = document.getElementById('summaryCustomer');
    const summaryPic = document.getElementById('summaryPic');

    function selectedOption(select) {
        return select && select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
    }
    function refreshCustomerFromSelect() {
        const opt = selectedOption(customerSelect);
        if (!opt || !opt.value) return;
        if (opt.dataset.name) customerName.value = opt.dataset.name;
        if (opt.dataset.pic && !picName.value) picName.value = opt.dataset.pic;
        if (opt.dataset.type && !labType.value) labType.value = opt.dataset.type;
        refreshSummary();
    }
    function refreshSummary() {
        summaryCustomer.textContent = customerName.value || '-';
        summaryPic.textContent = picName.value || '-';
    }
    customerSelect?.addEventListener('change', refreshCustomerFromSelect);
    customerName?.addEventListener('input', refreshSummary);
    picName?.addEventListener('input', refreshSummary);
    refreshCustomerFromSelect();
    refreshSummary();

    document.querySelectorAll('[data-drafter-pick]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const select = document.getElementById('production_pic_id');
            if (select) select.value = this.value;
        });
    });
    document.getElementById('production_pic_id')?.addEventListener('change', function () {
        document.querySelectorAll('[data-drafter-pick]').forEach(function (radio) {
            radio.checked = radio.value === this.value;
        }, this);
    });

    document.getElementById('addScope')?.addEventListener('click', function(){
        const input = document.getElementById('scopeInput');
        const value = (input.value || '').trim();
        if (!value) return;
        const label = document.createElement('label');
        label.className = 'tag-pill bg-white border text-dark m-0';
        label.innerHTML = `<input type="checkbox" class="form-check-input m-0" name="scope_checklist[]" value="${value.replace(/"/g, '&quot;')}" checked> ${value}`;
        document.getElementById('scopeList').appendChild(label);
        input.value = '';
    });
})();
</script>
@endpush
@endsection
