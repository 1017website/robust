@extends('layouts.app')
@section('title', 'Design Request Baru')
@section('content')
<x-page-header title="Buat Design Request" subtitle="Kirim permintaan desain ke tim produksi" />

<form method="POST" action="{{ route('sales.design-requests.store') }}">
    @csrf
    <input type="hidden" name="lead_id" value="{{ $lead?->id }}">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-r">
                <div class="card-head"><h2>Informasi Customer & Proyek</h2></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Nama Customer *</label><input name="customer_name" value="{{ old('customer_name',$lead?->instansi) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">PIC</label><input name="pic_name" value="{{ old('pic_name',$lead?->pic_name) }}" class="form-control"></div>
                    <div class="col-md-12"><label class="form-label small fw-semibold">Nama Proyek / Lab *</label><input name="project_name" value="{{ old('project_name',$lead?->lab_name) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Jenis Lab</label><input name="lab_type" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Kapasitas</label><input name="capacity" class="form-control" placeholder="mis. 30 orang"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Deskripsi Singkat *</label><textarea name="short_description" rows="2" class="form-control" required>{{ $lead?->need_description }}</textarea></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Detail Kebutuhan *</label><textarea name="detail_need" rows="4" class="form-control" required></textarea></div>
                </div>
            </div>
            <div class="card-r">
                <div class="card-head"><h2>Scope & Output</h2></div>
                <label class="form-label small fw-semibold">Scope Checklist</label>
                <div class="row g-2 mb-3">
                    @foreach(['Wall Bench','Island Bench','Fume Hood','Sink Unit','Wall Cabinet','Storage Cabinet','Safety Shower','Eye Wash'] as $item)
                        <div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="scope_checklist[]" value="{{ $item }}" id="sck{{ $loop->index }}"><label class="form-check-label small" for="sck{{ $loop->index }}">{{ $item }}</label></div></div>
                    @endforeach
                </div>
                <label class="form-label small fw-semibold">Output yang Diminta</label>
                <div class="row g-2">
                    @foreach(['Layout 2D'=>'layout_2d','Rendering 3D'=>'rendering_3d','Shop Drawing'=>'shop_drawing','BOQ'=>'boq','Cost Estimation'=>'cost_estimation'] as $lbl=>$val)
                        <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="outputs[]" value="{{ $val }}" id="out{{ $loop->index }}"><label class="form-check-label small" for="out{{ $loop->index }}">{{ $lbl }}</label></div></div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-r">
                <div class="card-head"><h2>Penugasan</h2></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Drafter / Produksi *</label>
                    <select name="production_pic_id" class="form-select select2" required>
                        <option value="">— Pilih —</option>
                        @foreach($drafters as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Tanggal Request *</label><input name="request_date" type="date" value="{{ date('Y-m-d') }}" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Deadline *</label><input name="deadline" type="date" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Prioritas *</label>
                    <select name="priority" class="form-select"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Catatan untuk Produksi</label><textarea name="production_note" rows="3" class="form-control"></textarea></div>
            </div>
            <div class="card-r">
                <button type="submit" name="action" value="draft" class="btn btn-soft w-100 mb-2">Simpan Draft</button>
                <button type="submit" name="action" value="send" class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Kirim ke Produksi</button>
            </div>
        </div>
    </div>
</form>
@endsection
