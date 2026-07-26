@extends('layouts.app')
@section('title', 'Project Workspace')

@php
    $role = auth()->user()->role;
    $canProduction = in_array($role, ['administrator', 'production'], true);
    $canQc = in_array($role, ['administrator', 'qc'], true);
    $canDelivery = in_array($role, ['administrator', 'delivery'], true);
    $canFabrication = in_array($role, ['administrator', 'drafter'], true);
    $canRevision = in_array($role, ['administrator', 'drafter', 'administration'], true);
    $designRequest = $project->quotation?->designRequest;
    $statusLabel = \App\Models\ProjectWorkflow::productionStatuses()[$workflow->production_status] ?? $workflow->production_status;
    $deliveryStatusLabel = \App\Models\ProjectWorkflow::deliveryStatuses()[$workflow->delivery_status] ?? $workflow->delivery_status;
@endphp

@push('styles')
<style>
    .workspace-tabs { border-bottom: 1px solid #e6eaf0; gap: .35rem; }
    .workspace-tabs .nav-link { color: #667085; font-weight: 700; border: 0; border-bottom: 3px solid transparent; padding: .85rem 1rem; }
    .workspace-tabs .nav-link.active { color: #0b63ce; border-bottom-color: #0b63ce; background: transparent; }
    .workflow-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
    .workflow-card { border: 1px solid #e7ebf1; border-radius: 14px; padding: 1rem; background: #fff; }
    .workflow-card h3 { font-size: 1rem; font-weight: 800; margin: 0; }
    .attachment-box { border: 1px dashed #cfd7e3; border-radius: 10px; padding: .8rem; background: #f8fafc; }
    .qc-checklist { max-height: 420px; overflow: auto; border: 1px solid #e7ebf1; border-radius: 10px; padding: .75rem; background: #fbfcfe; }
    .qc-item + .qc-item { border-top: 1px solid #e7ebf1; margin-top: .75rem; padding-top: .75rem; }
    .revision-note { max-width: 430px; white-space: normal; }
    @media (max-width: 991px) { .workflow-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<x-page-header :title="$project->name" :subtitle="$project->code.' · '.($project->customer?->name ?? 'Tanpa customer')">
    <x-status-badge :status="$project->status" :label="\App\Models\Project::statuses()[$project->status] ?? $project->status" />
</x-page-header>

<div class="card-r p-0 overflow-hidden">
    <ul class="nav workspace-tabs px-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#project-info" type="button">Informasi Project</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#design-request" type="button">Design Request <span class="badge text-bg-light ms-1">{{ $designRequest ? 1 : 0 }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#operations" type="button">Production, QC & Delivery</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#design-revisions" type="button">Design Revision <span class="badge text-bg-light ms-1">{{ $project->designRevisions->count() }}</span></button></li>
    </ul>

    <div class="tab-content p-3 p-lg-4">
        <div class="tab-pane fade show active" id="project-info">
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="workflow-card h-100">
                        <div class="card-head"><h2>Informasi Project</h2></div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="small text-muted-2">Project Manager</div><div class="fw-semibold">{{ $project->projectManager?->name ?? '-' }}</div></div>
                            <div class="col-md-6"><div class="small text-muted-2">Penawaran</div><div class="fw-semibold">{{ $project->quotation?->code ?? '-' }}</div></div>
                            <div class="col-md-6"><div class="small text-muted-2">Tanggal Mulai</div><div class="fw-semibold">{{ $project->start_date?->translatedFormat('d M Y') ?? '-' }}</div></div>
                            <div class="col-md-6"><div class="small text-muted-2">Target Selesai</div><div class="fw-semibold">{{ $project->target_date?->translatedFormat('d M Y') ?? '-' }}</div></div>
                            <div class="col-md-6"><div class="small text-muted-2">Lokasi</div><div>{{ $project->location ?: '-' }}</div></div>
                            <div class="col-md-6"><div class="small text-muted-2">Prioritas</div><div class="text-capitalize">{{ $project->priority }}</div></div>
                            <div class="col-12"><div class="small text-muted-2">Scope of Work</div><div>{{ $project->scope_of_work ?: '-' }}</div></div>
                            <div class="col-12"><div class="small text-muted-2">Catatan / Follow-up</div><div>{{ $project->note ?: '-' }}</div></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="workflow-card mb-3">
                        <h3 class="mb-3">Nilai Project</h3>
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Subtotal</span><span class="fw-num">{{ \App\Support\Format::rupiah($project->project_value) }}</span></div>
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">PPN</span><span class="fw-num">{{ \App\Support\Format::rupiah($project->tax_amount) }}</span></div>
                        <hr>
                        <div class="d-flex justify-content-between"><strong>Total</strong><strong class="fw-num">{{ \App\Support\Format::rupiah($project->total_value) }}</strong></div>
                    </div>
                    <div class="workflow-card">
                        <div class="d-flex justify-content-between align-items-center mb-2"><h3>Progress Operasional</h3><strong>{{ $workflow->completionPercent() }}%</strong></div>
                        <div class="prog"><span style="width:{{ $workflow->completionPercent() }}%"></span></div>
                        <div class="small text-muted-2 mt-2">Produksi, QC, DO/BA keluar, dan DO/BA kembali.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="design-request">
            @include('projects._design-request', ['designRequest' => $designRequest])
        </div>

        <div class="tab-pane fade" id="operations">
            <section class="workflow-card mb-3">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div><h3>Gambar Fabrikasi</h3><small class="text-muted-2">Drafter mengunggah gambar final setelah PO Accurate terbit. Produksi dapat mengunduh file aktif dari sini.</small></div>
                    <x-status-badge :status="$fabricationDocuments->isNotEmpty() ? 'completed' : 'pending'" :label="$fabricationDocuments->isNotEmpty() ? 'Siap Produksi' : 'Menunggu Drafter'" />
                </div>
                @if($canFabrication)
                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
                        @csrf
                        <input type="hidden" name="documentable_type" value="{{ \App\Models\Project::class }}">
                        <input type="hidden" name="documentable_id" value="{{ $project->id }}">
                        <input type="hidden" name="category" value="fabrication_drawing">
                        <div class="col-md-4"><label class="form-label">Nama Gambar</label><input name="name" class="form-control" value="{{ old('name', 'Gambar Fabrikasi '.$project->code) }}" required></div>
                        <div class="col-md-5"><label class="form-label">File Gambar Fabrikasi</label><input type="file" name="file" class="form-control" accept=".pdf,.dwg,.dxf,.jpg,.jpeg,.png,.zip" required></div>
                        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-cloud-arrow-up me-1"></i>Upload Gambar</button></div>
                    </form>
                @endif
                <div class="row g-2">
                    @forelse($fabricationDocuments as $document)
                        <div class="col-md-6 col-xl-4"><div class="attachment-box h-100"><div class="fw-semibold">{{ $document->name }}</div><div class="small text-muted-2">{{ $document->revisionLabel() }} - {{ $document->uploader?->name ?? '-' }} - {{ $document->created_at?->format('d/m/Y H:i') }}</div><a class="btn btn-sm btn-soft mt-2" target="_blank" href="{{ asset('storage/'.$document->file_path) }}"><i class="bi bi-download me-1"></i>Download</a></div></div>
                    @empty
                        <div class="col-12"><x-empty text="Gambar fabrikasi belum diunggah." /></div>
                    @endforelse
                </div>
            </section>

            <div class="workflow-grid">
                <section class="workflow-card">
                    <div class="d-flex justify-content-between align-items-start mb-3"><div><h3>Laporan Produksi</h3><small class="text-muted-2">Status dan Checklist Produksi</small></div><x-status-badge :status="$workflow->production_status" :label="$statusLabel" /></div>
                    @if($canProduction)
                    <form method="POST" action="{{ route('project-workflow.production', $project) }}" enctype="multipart/form-data">@csrf @method('PUT')
                        <label class="form-label">Status Produksi</label>
                        <select class="form-select mb-3" name="production_status" required>@foreach(\App\Models\ProjectWorkflow::productionStatuses() as $value => $label)<option value="{{ $value }}" @selected($workflow->production_status === $value)>{{ $label }}</option>@endforeach</select>
                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="production_report_completed" value="1" id="productionComplete" @checked($workflow->production_report_completed)><label class="form-check-label fw-semibold" for="productionComplete">Laporan produksi lengkap</label></div>
                        <label class="form-label">Checklist Produksi (PDF)</label><input class="form-control mb-3" type="file" name="production_report" accept="application/pdf,.pdf">
                        <button class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Simpan Produksi</button>
                    </form>
                    @else
                        <div class="d-flex align-items-center gap-2 mb-3"><i class="bi {{ $workflow->production_report_completed ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }}"></i><span>{{ $workflow->production_report_completed ? 'Laporan lengkap' : 'Laporan belum lengkap' }}</span></div>
                    @endif
                    @if($workflow->production_report_path)
                        <div class="attachment-box mt-3"><div class="small fw-semibold text-truncate">{{ $workflow->production_report_name }}</div><div class="mt-2"><a target="_blank" href="{{ route('project-workflow.attachment', [$project, 'production']) }}" class="btn btn-sm btn-soft">Lihat</a> <a href="{{ route('project-workflow.attachment', [$project, 'production', 'download' => 1]) }}" class="btn btn-sm btn-soft">Unduh</a></div></div>
                    @endif
                </section>

                <section class="workflow-card">
                    <div class="d-flex justify-content-between align-items-start mb-3"><div><h3>Quality Control</h3><small class="text-muted-2">Checklist otomatis dari spesifikasi penawaran</small></div><x-status-badge :status="$workflow->qc_completed ? 'approved' : 'pending'" :label="$workflow->qc_completed ? 'QC Selesai' : 'QC Pending'" /></div>
                    @if($canQc)
                    <form method="POST" action="{{ route('project-workflow.qc', $project) }}" enctype="multipart/form-data">@csrf @method('PUT')
                        <div class="qc-checklist mb-3">
                            @forelse($qcChecklistDefinition as $qcItem)
                                <div class="qc-item">
                                    <div class="fw-bold mb-1">{{ $qcItem['item_name'] }} @if($qcItem['variant'])<small class="text-muted-2">- {{ $qcItem['variant'] }}</small>@endif</div>
                                    @foreach($qcItem['checks'] as $check)
                                        <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="qc_checklist[{{ $check['key'] }}]" value="1" id="qc_{{ $check['key'] }}" @checked(old('qc_checklist.'.$check['key'], $workflow->qc_checklist[$check['key']] ?? false))><label class="form-check-label small" for="qc_{{ $check['key'] }}">{{ $check['label'] }}</label></div>
                                    @endforeach
                                </div>
                            @empty
                                <div class="small text-muted-2">Belum ada item penawaran untuk diperiksa.</div>
                            @endforelse
                        </div>
                        <label class="form-label">Catatan QC</label><textarea name="qc_note" class="form-control mb-3" rows="2">{{ old('qc_note', $workflow->qc_note) }}</textarea>
                        <label class="form-label">Lampiran QC (opsional, PDF)</label><input class="form-control mb-3" type="file" name="qc_document" accept="application/pdf,.pdf">
                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="qc_completed" value="1" id="qcComplete" @checked($workflow->qc_completed)><label class="form-check-label fw-semibold" for="qcComplete">Semua pemeriksaan selesai dan lolos QC</label></div>
                        <button class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Simpan QC</button>
                    </form>
                    @else
                        <div class="d-flex align-items-center gap-2 mb-3"><i class="bi {{ $workflow->qc_completed ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }}"></i><span>{{ $workflow->qc_completed ? 'QC selesai' : 'QC belum selesai' }}</span></div>
                    @endif
                    @if($workflow->qc_document_path)
                        <div class="attachment-box mt-3"><div class="small fw-semibold text-truncate">{{ $workflow->qc_document_name }}</div><div class="mt-2"><a target="_blank" href="{{ route('project-workflow.attachment', [$project, 'qc']) }}" class="btn btn-sm btn-soft">Lihat</a> <a href="{{ route('project-workflow.attachment', [$project, 'qc', 'download' => 1]) }}" class="btn btn-sm btn-soft">Unduh</a></div></div>
                    @endif
                </section>

                <section class="workflow-card">
                    <div class="d-flex justify-content-between align-items-start mb-3"><div><h3>Delivery</h3><small class="text-muted-2">Jadwal, POD, dan penerimaan customer</small></div><x-status-badge :status="$workflow->delivery_status === 'completed' ? 'completed' : 'pending'" :label="$deliveryStatusLabel" /></div>
                    @if($canDelivery)
                    <form method="POST" action="{{ route('project-workflow.delivery', $project) }}" enctype="multipart/form-data">@csrf @method('PUT')
                        <label class="form-label">Status Delivery</label><select name="delivery_status" class="form-select mb-3" required>@foreach(\App\Models\ProjectWorkflow::deliveryStatuses() as $value=>$label)<option value="{{ $value }}" @selected(old('delivery_status', $workflow->delivery_status) === $value)>{{ $label }}</option>@endforeach</select>
                        <label class="form-label">Jadwal Pengiriman</label><input type="datetime-local" name="delivery_scheduled_at" value="{{ old('delivery_scheduled_at', $workflow->delivery_scheduled_at?->format('Y-m-d\TH:i')) }}" class="form-control mb-3">
                        <label class="form-label">POD / Bukti Terkirim</label><input class="form-control mb-3" type="file" name="pod" accept=".pdf,image/jpeg,image/png,image/webp">
                        <label class="form-label">Nama Penerima Customer</label><input name="customer_receiver_name" value="{{ old('customer_receiver_name', $workflow->customer_receiver_name) }}" class="form-control mb-3">
                        <label class="form-label">Tanggal Diterima Customer</label><input type="datetime-local" name="customer_received_at" value="{{ old('customer_received_at', $workflow->customer_received_at?->format('Y-m-d\TH:i')) }}" class="form-control mb-3">
                        <label class="form-label">Catatan Delivery</label><textarea name="delivery_note" class="form-control mb-3" rows="2">{{ old('delivery_note', $workflow->delivery_note) }}</textarea>
                        <button class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Simpan Delivery</button>
                    </form>
                    @else
                        <div class="mb-2"><span class="text-muted-2">Jadwal:</span> {{ $workflow->delivery_scheduled_at?->format('d/m/Y H:i') ?? '-' }}</div>
                        <div class="mb-2"><span class="text-muted-2">Penerima:</span> {{ $workflow->customer_receiver_name ?: '-' }}</div>
                    @endif
                    @if($workflow->pod_path || $workflow->delivery_out_photo_path || $workflow->delivery_returned_photo_path)
                        <div class="attachment-box mt-3">
                            @if($workflow->pod_path)<div class="mb-2"><span class="small fw-semibold">POD: {{ $workflow->pod_name }}</span><br><a target="_blank" href="{{ route('project-workflow.attachment', [$project, 'delivery-pod']) }}">Lihat POD</a> - <a href="{{ route('project-workflow.attachment', [$project, 'delivery-pod', 'download' => 1]) }}">Download</a></div>@endif
                            @if($workflow->delivery_out_photo_path)<div class="mb-2"><span class="small fw-semibold">Keluar: {{ $workflow->delivery_out_photo_name }}</span><br><a target="_blank" href="{{ route('project-workflow.attachment', [$project, 'delivery-out']) }}">Lihat foto</a></div>@endif
                            @if($workflow->delivery_returned_photo_path)<div><span class="small fw-semibold">Kembali: {{ $workflow->delivery_returned_photo_name }}</span><br><a target="_blank" href="{{ route('project-workflow.attachment', [$project, 'delivery-returned']) }}">Lihat foto</a></div>@endif
                        </div>
                    @endif
                </section>
            </div>
        </div>

        <div class="tab-pane fade" id="design-revisions">
            @if($canRevision)
            <div class="workflow-card mb-3">
                <div class="card-head"><h2>Tambah Design Revision</h2></div>
                <form method="POST" action="{{ route('design-revisions.store', $project) }}" enctype="multipart/form-data" class="row g-3">@csrf
                    <div class="col-md-3"><label class="form-label">Tanggal Revisi</label><input type="date" class="form-control" name="revision_date" value="{{ old('revision_date', now()->format('Y-m-d')) }}" required></div>
                    <div class="col-md-4"><label class="form-label">File Revisi</label><input type="file" class="form-control" name="revision_file" accept=".pdf,.dwg,.dxf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.rar" required></div>
                    <div class="col-md-5"><label class="form-label">Keterangan Perubahan</label><textarea class="form-control" name="notes" rows="2" required>{{ old('notes') }}</textarea></div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-cloud-arrow-up me-1"></i>Simpan Revision {{ $project->designRevisions->count() + 1 }}</button></div>
                </form>
            </div>
            @endif

            <div class="table-wrap">
                <table class="table-r">
                    <thead><tr><th>Revisi</th><th>Tanggal</th><th>Keterangan Perubahan</th><th>File</th><th>Dibuat Oleh</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($project->designRevisions as $revision)
                        <tr>
                            <td class="fw-bold">{{ $revision->label() }}</td>
                            <td>{{ $revision->revision_date->translatedFormat('d M Y') }}</td>
                            <td class="revision-note">{{ $revision->notes }}</td>
                            <td><div class="fw-semibold">{{ $revision->original_name }}</div><div class="mt-1"><a target="_blank" href="{{ route('design-revisions.attachment', [$project, $revision]) }}">Lihat</a> · <a href="{{ route('design-revisions.attachment', [$project, $revision, 'download' => 1]) }}">Unduh</a></div></td>
                            <td>{{ $revision->creator?->name ?? '-' }}</td>
                            <td>
                                @if($canRevision)
                                <form method="POST" action="{{ route('design-revisions.status', [$project, $revision]) }}" class="d-flex gap-2">@csrf @method('PUT')
                                    <select class="form-select form-select-sm" name="status">@foreach(\App\Models\DesignRevision::statuses() as $value => $label)<option value="{{ $value }}" @selected($revision->status === $value)>{{ $label }}</option>@endforeach</select><button class="btn btn-sm btn-soft">Simpan</button>
                                </form>
                                @else
                                    <x-status-badge :status="$revision->status" :label="\App\Models\DesignRevision::statuses()[$revision->status] ?? $revision->status" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty text="Belum ada histori design revision." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!location.hash) return;
    const trigger = document.querySelector('[data-bs-target="' + location.hash + '"]');
    if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
});
</script>
@endpush
