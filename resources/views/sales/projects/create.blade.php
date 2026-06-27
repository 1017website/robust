@extends('layouts.app')
@section('title', 'Project Baru')
@section('content')
<x-page-header title="Buat Project" subtitle="Konversi penawaran menang menjadi project" />
<form method="POST" action="{{ route('sales.projects.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-r">
                <div class="card-head"><h2>Informasi Project</h2></div>
                <div class="row g-3">
                    <div class="col-12"><label class="form-label small fw-semibold">Sumber Penawaran *</label>
                        <select name="quotation_id" class="form-select select2" required>
                            <option value="">— Pilih penawaran Won —</option>
                            @foreach($wonQuotations as $q)<option value="{{ $q->id }}" @selected($quotation?->id==$q->id)>{{ $q->code }} · {{ $q->customer_name }} ({{ \App\Support\Format::rupiahShort($q->grand_total) }})</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-8"><label class="form-label small fw-semibold">Nama Project *</label><input name="name" value="{{ $quotation?->project_name }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Prioritas</label><select name="priority" class="form-select"><option value="medium">Medium</option><option value="high">High</option><option value="low">Low</option></select></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Deskripsi</label><textarea name="description" rows="2" class="form-control"></textarea></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Tanggal Mulai *</label><input name="start_date" type="date" value="{{ date('Y-m-d') }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Target Selesai *</label><input name="target_date" type="date" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Metode Kerja</label><input name="work_method" class="form-control" placeholder="mis. Turnkey"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Skema Pembayaran</label><input name="payment_scheme" class="form-control" placeholder="mis. DP 30% - 40% - 30%"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Lokasi</label><input name="location" class="form-control"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Scope of Work</label><textarea name="scope_of_work" rows="2" class="form-control"></textarea></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-r">
                <div class="card-head"><h2>Tim</h2></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Project Manager *</label>
                    <select name="project_manager_id" class="form-select select2" required>
                        <option value="">— Pilih —</option>
                        @foreach($managers as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Status Awal</label>
                    <select name="status" class="form-select">@foreach(\App\Models\Project::statuses() as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Tim Internal</label>
                    <select name="internal_team[]" class="form-select select2" multiple>
                        @foreach($team as $t)<option value="{{ $t->id }}">{{ $t->name }} ({{ $t->roleLabel() }})</option>@endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Catatan</label><textarea name="note" rows="2" class="form-control"></textarea></div>
            </div>
            <div class="card-r"><button class="btn btn-primary w-100">Buat Project</button></div>
        </div>
    </div>
</form>
@endsection
