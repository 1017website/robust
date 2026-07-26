@extends('layouts.app')
@section('title', 'Design Request Baru')
@section('content')
@php
    $selectedScopes = old('scope_checklist', $lead?->scope_items ?? []);
    $selectedOutputs = old('outputs', ['layout_2d', 'rendering_3d', 'boq', 'cost_estimation']);
    $selectedMasterSource = old('master_source', $lead ? 'lead:'.$lead->id : '');
    $defaultUrgency = in_array($lead?->priority, ['high', 'urgent'], true) ? 'urgent' : 'normal';
@endphp
<div class="sales-ui">
    <form method="POST" action="{{ route('sales.design-requests.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" id="lead_id" name="lead_id" value="{{ old('lead_id', $lead?->id) }}">
        <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id', $lead?->customer_id) }}">
        <div class="sales-page-head">
            <div class="sales-title-wrap"><div class="sales-title-icon"><i class="bi bi-calendar-plus"></i></div><div><div class="small fw-bold text-primary mb-1">Design Request &gt; Design Request Baru</div><h1 class="page-title mb-1">Design Request Baru</h1><div class="page-subtitle">Kirim brief, sketsa, dan assignment langsung ke drafter.</div></div></div>
            <div class="page-actions"><a href="{{ route('sales.design-requests.index') }}" class="btn btn-soft">Batal</a><button name="action" value="send" class="btn btn-primary"><i class="bi bi-send me-1"></i>Simpan & Kirim ke Drafter</button></div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="sales-form-card">
                    <h2 class="sales-form-title">1. Informasi Dasar</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Ambil dari Master Lead / Customer</label>
                            <select id="master_source" name="master_source" class="form-select">
                                <option value="">Pilih Lead atau Customer</option>
                                <optgroup label="Master Leads">
                                    @foreach($leads as $sourceLead)
                                        <option
                                            value="lead:{{ $sourceLead->id }}"
                                            data-source-type="lead"
                                            data-source-id="{{ $sourceLead->id }}"
                                            data-customer-id="{{ $sourceLead->customer_id }}"
                                            data-customer="{{ e($sourceLead->instansi) }}"
                                            data-pic="{{ e($sourceLead->pic_name) }}"
                                            data-project="{{ e($sourceLead->lab_name) }}"
                                            data-lab="{{ e($sourceLead->lab_name) }}"
                                            data-capacity="{{ e($sourceLead->capacity) }}"
                                            data-description="{{ e($sourceLead->need_description) }}"
                                            data-scopes="{{ e(json_encode($sourceLead->scope_items ?? [])) }}"
                                            @selected($selectedMasterSource === 'lead:'.$sourceLead->id)
                                        >{{ $sourceLead->code }} — {{ $sourceLead->instansi }}{{ $sourceLead->lab_name ? ' / '.$sourceLead->lab_name : '' }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Master Customers">
                                    @foreach($customers as $sourceCustomer)
                                        @php
                                            $customerLead = $sourceCustomer->leads->first();
                                            $customerProject = $customerLead?->lab_name ?: $sourceCustomer->projects->first()?->name ?: $sourceCustomer->type;
                                            $customerPic = $sourceCustomer->primaryPic?->name ?: $customerLead?->pic_name;
                                        @endphp
                                        <option
                                            value="customer:{{ $sourceCustomer->id }}"
                                            data-source-type="customer"
                                            data-source-id="{{ $sourceCustomer->id }}"
                                            data-customer-id="{{ $sourceCustomer->id }}"
                                            data-customer="{{ e($sourceCustomer->name) }}"
                                            data-pic="{{ e($customerPic) }}"
                                            data-project="{{ e($customerProject) }}"
                                            data-lab="{{ e($sourceCustomer->type ?: $customerLead?->lab_name) }}"
                                            data-capacity="{{ e($customerLead?->capacity) }}"
                                            data-description="{{ e($customerLead?->need_description ?: $sourceCustomer->notes) }}"
                                            data-scopes="{{ e(json_encode($customerLead?->scope_items ?? [])) }}"
                                            @selected($selectedMasterSource === 'customer:'.$sourceCustomer->id)
                                        >{{ $sourceCustomer->code }} — {{ $sourceCustomer->name }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                            <div class="form-text">Pilih data master untuk mengisi Customer, PIC, Nama Proyek, dan kebutuhan secara otomatis.</div>
                        </div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Customer / Instansi *</label><input id="customer_name" name="customer_name" value="{{ old('customer_name',$lead?->instansi) }}" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">PIC Customer *</label><input id="pic_name" name="pic_name" value="{{ old('pic_name',$lead?->pic_name) }}" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Nama Proyek *</label><input id="project_name" name="project_name" value="{{ old('project_name',$lead?->lab_name) }}" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label small fw-bold">Sales</label><input class="form-control" value="{{ auth()->user()->name }}" readonly></div>
                        <div class="col-md-3"><label class="form-label small fw-bold">Tanggal Request *</label><input type="date" name="request_date" value="{{ old('request_date',date('Y-m-d')) }}" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label small fw-bold">Deadline *</label><input type="date" name="deadline" value="{{ old('deadline') }}" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label small fw-bold">Status Urgensi *</label><select name="priority" class="form-select" required>@foreach(\App\Models\DesignRequest::urgencyOptions() as $k=>$v)<option value="{{ $k }}" @selected(old('priority',$defaultUrgency)===$k)>{{ $v }}</option>@endforeach</select></div>
                        <div class="col-12"><label class="form-label small fw-bold">Deskripsi Singkat *</label><textarea id="short_description" name="short_description" rows="3" maxlength="500" class="form-control" required>{{ old('short_description',$lead?->need_description) }}</textarea></div>
                    </div>
                </div>

                <div class="sales-form-card">
                    <h2 class="sales-form-title">2. Kebutuhan Customer</h2>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label small fw-bold">Jenis Laboratorium / Area *</label><input id="lab_type" name="lab_type" value="{{ old('lab_type',$lead?->lab_name) }}" class="form-control" required></div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Ruang Lingkup *</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(['Wall Bench','Island Bench','Fume Hood','Storage Cabinet','Sink Area','Meja Persiapan','Meja Instrumen','Meja Komputer','Safety Equipment','Lainnya'] as $scope)
                                    <label class="tag-pill"><input type="checkbox" name="scope_checklist[]" value="{{ $scope }}" @checked(in_array($scope,$selectedScopes)) @if($scope === 'Lainnya') id="scope_other_checkbox" @endif> {{ $scope }}</label>
                                @endforeach
                            </div>
                            <div id="scope_other_wrap" class="mt-2 {{ in_array('Lainnya', $selectedScopes) ? '' : 'd-none' }}">
                                <label class="form-label small fw-bold">Ruang Lingkup Lainnya *</label>
                                <input id="scope_other" name="scope_other" value="{{ old('scope_other') }}" class="form-control" placeholder="Tuliskan ruang lingkup lainnya">
                            </div>
                        </div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Kapasitas / Pengguna</label><input id="capacity" name="capacity" value="{{ old('capacity',$lead?->capacity) }}" class="form-control"></div>
                        <div class="col-md-8"><label class="form-label small fw-bold">Detail Kebutuhan *</label><textarea id="detail_need" name="detail_need" rows="4" maxlength="1000" class="form-control" required>{{ old('detail_need',$lead?->need_description) }}</textarea></div>
                    </div>
                </div>

                <div class="sales-form-card">
                    <h2 class="sales-form-title">3. Sketsa & Lampiran dari Sales</h2>
                    <label class="form-label small fw-bold">Upload sketsa (maks. 5 file, 10 MB/file)</label>
                    <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.heic,.doc,.docx,.xls,.xlsx">
                    <div class="form-text">Bisa berupa foto coretan, layout awal, PDF, atau dokumen referensi customer.</div>
                </div>

                <div class="sales-form-card">
                    <h2 class="sales-form-title">4. Output yang Diminta</h2>
                    <div class="row g-2">@foreach(['layout_2d'=>'Layout 2D','rendering_3d'=>'Rendering 3D','shop_drawing'=>'Shop Drawing','boq'=>'BOQ','cost_estimation'=>'Cost Estimation'] as $k=>$v)<div class="col-md"><label class="info-card d-block h-100"><input type="checkbox" name="outputs[]" value="{{ $k }}" @checked(in_array($k,$selectedOutputs))> <strong class="ms-1">{{ $v }}</strong></label></div>@endforeach</div>
                    <div class="mt-3"><label class="form-label small fw-bold">Catatan Tambahan</label><textarea name="extra_note" rows="3" class="form-control" maxlength="500">{{ old('extra_note') }}</textarea></div>
                </div>
            </div>

            <div class="col-xl-4">
                @unless(auth()->user()->isSales())
                    <div class="sales-form-card"><h2 class="sales-form-title">Sales Owner</h2><select name="sales_id" class="form-select" required><option value="">Pilih sales</option>@foreach($salesList as $sales)<option value="{{ $sales->id }}" @selected((string)old('sales_id',$lead?->sales_id)===(string)$sales->id)>{{ $sales->name }}</option>@endforeach</select></div>
                @endunless
                <div class="sales-form-card">
                    <h2 class="sales-form-title">Assignment Drafter</h2>
                    <label class="form-label small fw-bold">Drafter *</label>
                    <select id="production_pic_id" name="production_pic_id" class="form-select" required><option value="">Pilih Drafter</option>@foreach($drafters as $drafter)<option value="{{ $drafter->id }}" @selected((string)old('production_pic_id')===(string)$drafter->id)>{{ $drafter->name }}</option>@endforeach</select>
                    <div class="small fw-bold mt-3 mb-2">Suggest Drafter (workload terendah)</div>
                    <div class="d-grid gap-2">
                        @forelse($drafterWorkloads as $row)
                            <label class="info-card py-2 d-flex align-items-center justify-content-between gap-2">
                                <span><input type="radio" data-drafter-pick value="{{ $row['drafter']->id }}" @checked((string)old('production_pic_id')===(string)$row['drafter']->id)> <strong>{{ $row['drafter']->name }}</strong></span>
                                <span class="status-soft {{ $row['active_requests'] <= 2 ? 'st-green' : ($row['active_requests'] <= 5 ? 'st-yellow' : 'st-red') }}">{{ $row['active_requests'] }} aktif</span>
                            </label>
                        @empty
                            <div class="alert alert-warning small mb-0">Belum ada user Drafter aktif.</div>
                        @endforelse
                    </div>
                    <label class="form-label small fw-bold mt-3">Catatan untuk Drafter</label><textarea name="production_note" rows="4" class="form-control" maxlength="300">{{ old('production_note') }}</textarea>
                </div>
            </div>
        </div>
    </form>
</div>
@push('scripts')
<script>
document.querySelectorAll('[data-drafter-pick]').forEach(radio => radio.addEventListener('change', () => {
    document.getElementById('production_pic_id').value = radio.value;
}));
document.getElementById('production_pic_id')?.addEventListener('change', event => {
    document.querySelectorAll('[data-drafter-pick]').forEach(radio => radio.checked = radio.value === event.target.value);
});

const masterSource = document.getElementById('master_source');
const scopeOtherCheckbox = document.getElementById('scope_other_checkbox');
const scopeOtherWrap = document.getElementById('scope_other_wrap');
const scopeOtherInput = document.getElementById('scope_other');

function toggleScopeOther() {
    const active = Boolean(scopeOtherCheckbox?.checked);
    scopeOtherWrap?.classList.toggle('d-none', !active);
    if (scopeOtherInput) {
        scopeOtherInput.required = active;
        if (!active) scopeOtherInput.value = '';
    }
}

function fillFromMaster(option) {
    if (!option?.value) return;

    const setValue = (id, value) => {
        const field = document.getElementById(id);
        if (field) field.value = value || '';
    };

    setValue('lead_id', option.dataset.sourceType === 'lead' ? option.dataset.sourceId : '');
    setValue('customer_id', option.dataset.customerId || (option.dataset.sourceType === 'customer' ? option.dataset.sourceId : ''));
    setValue('customer_name', option.dataset.customer);
    setValue('pic_name', option.dataset.pic);
    setValue('project_name', option.dataset.project);
    setValue('lab_type', option.dataset.lab);
    setValue('capacity', option.dataset.capacity);
    setValue('short_description', option.dataset.description);
    setValue('detail_need', option.dataset.description);

    let scopes = [];
    try {
        scopes = JSON.parse(option.dataset.scopes || '[]');
    } catch (error) {
        scopes = [];
    }

    document.querySelectorAll('input[name="scope_checklist[]"]').forEach(checkbox => {
        const exactMatch = scopes.includes(checkbox.value);
        const otherMatch = checkbox.value === 'Lainnya' && scopes.some(scope => scope.startsWith('Lainnya:'));
        checkbox.checked = exactMatch || otherMatch;
    });

    const otherScope = scopes.find(scope => scope.startsWith('Lainnya:'));
    if (scopeOtherInput) scopeOtherInput.value = otherScope ? otherScope.replace(/^Lainnya:\s*/, '') : '';
    toggleScopeOther();
}

masterSource?.addEventListener('change', event => {
    fillFromMaster(event.target.options[event.target.selectedIndex]);
});
scopeOtherCheckbox?.addEventListener('change', toggleScopeOther);
toggleScopeOther();
</script>
@endpush
@endsection
