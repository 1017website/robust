@extends('layouts.app')
@section('title', 'Design Request Baru')
@section('content')
@php
    $selectedScopes = old('scope_checklist', $lead?->scope_items ?? []);
    $selectedOutputs = old('outputs', ['layout_2d', 'rendering_3d', 'boq', 'cost_estimation']);
@endphp
<div class="sales-ui">
    <form method="POST" action="{{ route('sales.design-requests.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="lead_id" value="{{ $lead?->id }}">
        <div class="sales-page-head">
            <div class="sales-title-wrap"><div class="sales-title-icon"><i class="bi bi-calendar-plus"></i></div><div><div class="small fw-bold text-primary mb-1">Design Request &gt; Design Request Baru</div><h1 class="page-title mb-1">Design Request Baru</h1><div class="page-subtitle">Kirim brief, sketsa, dan assignment langsung ke drafter.</div></div></div>
            <div class="page-actions"><a href="{{ route('sales.design-requests.index') }}" class="btn btn-soft">Batal</a><button name="action" value="send" class="btn btn-primary"><i class="bi bi-send me-1"></i>Simpan & Kirim ke Drafter</button></div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="sales-form-card">
                    <h2 class="sales-form-title">1. Informasi Dasar</h2>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label small fw-bold">Customer / Instansi *</label><input id="customer_name" name="customer_name" value="{{ old('customer_name',$lead?->instansi) }}" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">PIC Customer *</label><input name="pic_name" value="{{ old('pic_name',$lead?->pic_name) }}" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Nama Proyek *</label><input id="project_name" name="project_name" value="{{ old('project_name',$lead?->lab_name) }}" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label small fw-bold">Sales</label><input class="form-control" value="{{ auth()->user()->name }}" readonly></div>
                        <div class="col-md-3"><label class="form-label small fw-bold">Tanggal Request *</label><input type="date" name="request_date" value="{{ old('request_date',date('Y-m-d')) }}" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label small fw-bold">Deadline *</label><input type="date" name="deadline" value="{{ old('deadline') }}" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label small fw-bold">Prioritas *</label><select name="priority" class="form-select" required>@foreach(['high'=>'High','medium'=>'Medium','low'=>'Low'] as $k=>$v)<option value="{{ $k }}" @selected(old('priority',$lead?->priority ?? 'medium')===$k)>{{ $v }}</option>@endforeach</select></div>
                        <div class="col-12"><label class="form-label small fw-bold">Deskripsi Singkat *</label><textarea name="short_description" rows="3" maxlength="500" class="form-control" required>{{ old('short_description',$lead?->need_description) }}</textarea></div>
                    </div>
                </div>

                <div class="sales-form-card">
                    <h2 class="sales-form-title">2. Kebutuhan Customer</h2>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label small fw-bold">Jenis Laboratorium / Area *</label><input name="lab_type" value="{{ old('lab_type',$lead?->lab_name) }}" class="form-control" required></div>
                        <div class="col-md-8"><label class="form-label small fw-bold">Ruang Lingkup *</label><div class="d-flex flex-wrap gap-2">@foreach(['Wall Bench','Island Bench','Fume Hood','Storage Cabinet','Sink Area','Meja Persiapan','Meja Instrumen','Meja Komputer','Safety Equipment','Lainnya'] as $scope)<label class="tag-pill"><input type="checkbox" name="scope_checklist[]" value="{{ $scope }}" @checked(in_array($scope,$selectedScopes))> {{ $scope }}</label>@endforeach</div></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Kapasitas / Pengguna</label><input name="capacity" value="{{ old('capacity',$lead?->capacity) }}" class="form-control"></div>
                        <div class="col-md-8"><label class="form-label small fw-bold">Detail Kebutuhan *</label><textarea name="detail_need" rows="4" maxlength="1000" class="form-control" required>{{ old('detail_need',$lead?->need_description) }}</textarea></div>
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
</script>
@endpush
@endsection
