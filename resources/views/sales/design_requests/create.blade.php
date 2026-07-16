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
    <form method="POST" action="{{ route('sales.design-requests.store') }}">
        @csrf
        <input type="hidden" name="lead_id" value="{{ $lead?->id }}">
        <div class="sales-page-head"><div class="sales-title-wrap"><div class="sales-title-icon"><i class="bi bi-calendar-plus"></i></div><div><div class="small fw-bold text-primary mb-1">Design Request &gt; Design Request Baru</div><h1 class="page-title mb-1">Design Request Baru</h1><div class="page-subtitle">Buat permintaan desain dan spesifikasi teknis ke tim produksi.</div></div></div><div class="page-actions"><a href="{{ route('sales.design-requests.index') }}" class="btn btn-soft">Batal</a><button name="action" value="send" class="btn btn-primary"><i class="bi bi-send me-1"></i>Simpan & Kirim ke Produksi</button></div></div>
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="sales-form-card"><h2 class="sales-form-title">1. Informasi Dasar</h2><div class="row g-3"><div class="col-md-4"><label class="form-label small fw-bold">Customer / Instansi *</label><input name="customer_name" value="{{ old('customer_name',$lead?->instansi) }}" class="form-control" required placeholder="Pilih customer"></div><div class="col-md-4"><label class="form-label small fw-bold">PIC (Contact Person) *</label><input name="pic_name" value="{{ old('pic_name',$lead?->pic_name) }}" class="form-control" required placeholder="Pilih PIC"></div><div class="col-md-4"><label class="form-label small fw-bold">Project / Nama Proyek *</label><input name="project_name" value="{{ old('project_name',$lead?->lab_name) }}" class="form-control" required placeholder="Masukkan nama proyek"></div><div class="col-md-3"><label class="form-label small fw-bold">Sales *</label><input class="form-control" value="{{ auth()->user()->name }}" readonly></div><div class="col-md-3"><label class="form-label small fw-bold">Tanggal Request *</label><input type="date" name="request_date" value="{{ old('request_date',date('Y-m-d')) }}" class="form-control" required></div><div class="col-md-3"><label class="form-label small fw-bold">Target Selesai / Deadline *</label><input type="date" name="deadline" value="{{ old('deadline') }}" class="form-control" required></div><div class="col-md-3"><label class="form-label small fw-bold">Prioritas *</label><select name="priority" class="form-select" required><option value="high" @selected(old('priority',$lead?->priority)==='high')>High (Tinggi)</option><option value="medium" @selected(old('priority',$lead?->priority ?? 'medium')==='medium')>Medium</option><option value="low" @selected(old('priority',$lead?->priority)==='low')>Low</option></select></div><div class="col-12"><label class="form-label small fw-bold">Deskripsi Singkat Request *</label><textarea name="short_description" rows="4" maxlength="500" class="form-control" required placeholder="Jelaskan secara singkat kebutuhan desain dan tujuan proyek ini...">{{ old('short_description',$lead?->need_description) }}</textarea></div></div></div>
                <div class="sales-form-card"><h2 class="sales-form-title">2. Kebutuhan Customer</h2><div class="row g-3"><div class="col-md-4"><label class="form-label small fw-bold">Jenis Laboratorium / Area *</label><input name="lab_type" value="{{ old('lab_type',$lead?->lab_name) }}" class="form-control" required placeholder="Pilih jenis laboratorium / area"></div><div class="col-md-8"><label class="form-label small fw-bold">Ruang Lingkup (Checklist) *</label><div class="d-flex flex-wrap gap-2">@foreach(['Wall Bench','Island Bench','Fume Hood','Storage Cabinet','Sink Area','Meja Persiapan','Meja Instrumen','Meja Komputer','Safety Equipment','Lainnya'] as $scope)<label class="tag-pill"><input type="checkbox" name="scope_checklist[]" value="{{ $scope }}" @checked(in_array($scope, old('scope_checklist',$lead?->scope_items ?? [])))> {{ $scope }}</label>@endforeach</div></div><div class="col-md-4"><label class="form-label small fw-bold">Kapasitas / Pengguna</label><input name="capacity" value="{{ old('capacity',$lead?->capacity) }}" class="form-control" placeholder="Contoh: 40 Mahasiswa / 10 Peneliti"></div><div class="col-md-8"><label class="form-label small fw-bold">Deskripsi Kebutuhan Detail *</label><textarea name="detail_need" rows="4" maxlength="1000" class="form-control" required>{{ old('detail_need',$lead?->need_description) }}</textarea></div></div></div>
                <div class="sales-form-card"><h2 class="sales-form-title">3. Output / Deliverables yang Diminta</h2><div class="row g-3">@foreach(['layout_2d'=>'Layout 2D','rendering_3d'=>'Rendering 3D','shop_drawing'=>'Shop Drawing','boq'=>'BOQ','cost_estimation'=>'Cost Estimation'] as $k=>$v)<div class="col-md"><label class="info-card d-block h-100"><input type="checkbox" name="outputs[]" value="{{ $k }}" @checked(in_array($k, old('outputs',['layout_2d','rendering_3d','boq','cost_estimation'])))> <strong class="ms-1">{{ $v }}</strong><div class="small text-muted-2 mt-2">{{ $k==='layout_2d'?'Layout denah & tata letak':($k==='rendering_3d'?'Visual 3D interior':($k==='shop_drawing'?'Gambar kerja produksi':($k==='boq'?'Bill of Quantity':'Estimasi biaya'))) }}</div></label></div>@endforeach</div><div class="mt-3"><label class="form-label small fw-bold">Catatan Tambahan</label><textarea name="extra_note" rows="4" class="form-control" maxlength="500">{{ old('extra_note') }}</textarea></div></div>
            </div>
            <div class="col-xl-4"><div class="sales-form-card"><h2 class="sales-form-title">Ringkasan Request</h2><div class="kv"><div class="k">Customer</div><div class="v">{{ $lead?->instansi ?? '-' }}</div></div><div class="kv"><div class="k">Project</div><div class="v">{{ $lead?->lab_name ?? '-' }}</div></div><div class="kv"><div class="k">Tanggal Request</div><div class="v">{{ now()->translatedFormat('d M Y') }}</div></div><div class="kv"><div class="k">Status</div><div class="v"><span class="status-soft st-purple">Draft</span></div></div></div>@if(! auth()->user()->isSales())<div class="sales-form-card"><h2 class="sales-form-title">Sales Owner</h2><select name="sales_id" class="form-select" required><option value="">Pilih sales</option>@foreach($salesList as $sales)<option value="{{ $sales->id }}" @selected((string)old('sales_id',$lead?->sales_id)===(string)$sales->id)>{{ $sales->name }}</option>@endforeach</select></div>@endif<div class="sales-form-card"><h2 class="sales-form-title">Assignment Produksi</h2><label class="form-label small fw-bold">PIC Produksi *</label><select name="production_pic_id" class="form-select select2" required data-placeholder="Pilih PIC Produksi"><option></option>@foreach($drafters as $d)<option value="{{ $d->id }}" @selected(old('production_pic_id')==$d->id)>{{ $d->name }}</option>@endforeach</select><label class="form-label small fw-bold mt-3">Catatan untuk Produksi</label><textarea name="production_note" rows="4" class="form-control" maxlength="300" placeholder="Sampaikan catatan penting untuk tim produksi...">{{ old('production_note') }}</textarea></div></div>
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
