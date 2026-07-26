@extends('layouts.app')
@section('title', 'Detail Design Request')
@section('content')
@php
    $statusClass = match($designRequest->status) {
        'completed' => 'st-green',
        'drafting' => 'st-blue',
        'drawing_uploaded', 'revision_drawing_uploaded' => 'st-blue',
        'revision_requested' => 'st-purple',
        'costing' => 'st-yellow',
        'review' => 'st-purple',
        'assigned' => 'st-yellow',
        'rejected' => 'st-red',
        default => 'st-gray',
    };
    $priorityClass = in_array($designRequest->priority, ['urgent', 'high'], true) ? 'st-red' : 'st-blue';
    $openRevision = $designRequest->revisionRequests->first(
        fn ($revision) => in_array($revision->status, ['requested', 'drawing_uploaded'], true)
    );
@endphp

<div class="sales-ui">
    <div class="sales-page-head align-items-center">
        <div class="sales-title-wrap">
            <a href="{{ route('sales.design-requests.index') }}" class="btn btn-soft"><i class="bi bi-arrow-left"></i></a>
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h1 class="page-title mb-0">{{ $designRequest->code }}</h1>
                    <span class="status-soft {{ $statusClass }}">{{ \App\Models\DesignRequest::statuses()[$designRequest->status] ?? \Illuminate\Support\Str::headline($designRequest->status) }}</span>
                </div>
                <div class="page-subtitle">{{ $designRequest->customer_name }} · {{ $designRequest->project_name }}</div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($designRequest->status === 'completed')
                <a href="{{ route('sales.quotations.create',['dr'=>$designRequest->id]) }}" class="btn btn-primary"><i class="bi bi-file-earmark-text me-1"></i>Generate Penawaran</a>
            @else
                <button type="button" class="btn btn-soft" disabled><i class="bi bi-hourglass-split me-1"></i>Menunggu completed</button>
            @endif
        </div>
    </div>

    <nav class="d-tabs big mb-3" aria-label="Bagian design request">
        <a href="#detail" class="active"><i class="bi bi-shield-check me-1"></i>Detail</a>
        <a href="#history"><i class="bi bi-clock-history me-1"></i>Riwayat</a>
        <a href="#revisions"><i class="bi bi-arrow-repeat me-1"></i>Revisi</a>
        <a href="#documents"><i class="bi bi-folder2-open me-1"></i>Dokumen</a>
    </nav>

    <div class="row g-3 align-items-start">
        <div class="col-xl-8" id="detail">
            <div class="info-card mb-3">
                <h6><i class="bi bi-building me-1 text-primary"></i>Informasi Customer</h6>
                <div class="row g-3">
                    <div class="col-md-6"><div class="small text-muted-2">Customer</div><div class="fw-bold">{{ $designRequest->customer_name }}</div></div>
                    <div class="col-md-6"><div class="small text-muted-2">PIC</div><div class="fw-bold">{{ $designRequest->pic_name ?: $designRequest->customer?->primaryPic?->name ?: '—' }}</div></div>
                    <div class="col-md-6"><div class="small text-muted-2">Master Customer</div><div class="fw-bold">@if($designRequest->customer)<span class="status-soft st-blue"><i class="bi bi-link-45deg"></i>Terhubung</span> {{ $designRequest->customer->code }}@else<span class="text-muted-2">Belum terhubung</span>@endif</div></div>
                    <div class="col-md-6"><div class="small text-muted-2">Pipeline Customer</div><div class="fw-bold">{{ $designRequest->customer ? (\App\Models\Customer::stages()[$designRequest->customer->pipeline_stage] ?? \Illuminate\Support\Str::headline($designRequest->customer->pipeline_stage)) : '—' }}</div></div>
                </div>
            </div>

            <div class="info-card mb-3">
                <h6><i class="bi bi-clipboard2-check me-1 text-success"></i>Kebutuhan Desain</h6>
                <div class="row g-3">
                    <div class="col-md-6"><div class="small text-muted-2">Nama Laboratorium / Proyek</div><div class="fw-bold">{{ $designRequest->project_name }}</div></div>
                    <div class="col-md-3"><div class="small text-muted-2">Jenis Kebutuhan</div><div class="fw-bold">{{ $designRequest->lab_type ?: '—' }}</div></div>
                    <div class="col-md-3"><div class="small text-muted-2">Kapasitas</div><div class="fw-bold">{{ $designRequest->capacity ?: '—' }}</div></div>
                    <div class="col-12"><div class="small text-muted-2">Ringkasan</div><div>{{ $designRequest->short_description ?: '—' }}</div></div>
                    <div class="col-12"><div class="small text-muted-2">Detail Kebutuhan</div><div>{{ $designRequest->detail_need ?: '—' }}</div></div>
                    <div class="col-12">
                        <div class="small text-muted-2 mb-2">Scope</div>
                        @forelse(($designRequest->scope_checklist ?? []) as $scope)
                            <span class="tag-pill">{{ $scope }}</span>
                        @empty
                            <span class="text-muted-2">Belum ada scope.</span>
                        @endforelse
                    </div>
                    <div class="col-12">
                        <div class="small text-muted-2 mb-2">Output Diminta</div>
                        @forelse(($designRequest->outputs ?? []) as $output)
                            <span class="status-soft st-purple me-1 mb-1">{{ strtoupper(str_replace('_',' ', $output)) }}</span>
                        @empty
                            <span class="text-muted-2">Belum ada output.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            @if($designRequest->items->count())
                <div class="info-card mb-3">
                    <div class="card-head px-0 pt-0"><h2>Item Hasil Produksi</h2></div>
                    <div class="quotation-item-list design-request-production-items">
                        @foreach($designRequest->items as $item)
                            <x-quotation-item-card
                                :item="$item"
                                :show-cost="false"
                                price-label="Harga Satuan"
                            />
                        @endforeach
                    </div>
                </div>
            @endif

            @if($designRequest->extra_note || $designRequest->technical_note)
                <div class="info-card mb-3">
                    <h6><i class="bi bi-chat-left-text me-1 text-warning"></i>Catatan</h6>
                    @if($designRequest->extra_note)
                        <div class="small text-muted-2">Catatan Sales</div>
                        <p>{{ $designRequest->extra_note }}</p>
                    @endif
                    @if($designRequest->technical_note)
                        <div class="small text-muted-2">Catatan Teknis Drafter</div>
                        <p class="mb-0">{{ $designRequest->technical_note }}</p>
                    @endif
                </div>
            @endif

            <div class="info-card mb-3" id="revisions">
                <div class="card-head px-0 pt-0">
                    <div>
                        <h2>Request & Riwayat Revisi</h2>
                        <small class="text-muted-2">Setelah revisi diajukan, Drafter mengunggah drawing terbaru dan Produksi mengisi ulang seluruh spesifikasi serta HPP.</small>
                    </div>
                </div>

                @if($openRevision)
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-hourglass-split me-2"></i>
                        Revisi {{ $openRevision->revision_number }} sedang diproses:
                        {{ $openRevision->status === 'requested' ? 'menunggu drawing dari Drafter' : 'drawing tersedia, menunggu Produksi' }}.
                    </div>
                @elseif($designRequest->status === 'completed')
                    <form method="POST" action="{{ route('sales.design-requests.revision', $designRequest) }}" class="mb-4">
                        @csrf
                        <label for="revisionNotes" class="form-label fw-bold">Catatan revisi *</label>
                        <textarea id="revisionNotes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" required placeholder="Jelaskan bagian yang harus direvisi...">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="d-flex justify-content-end mt-2">
                            <button class="btn btn-warning"><i class="bi bi-arrow-repeat me-1"></i>Ajukan Request Revisi</button>
                        </div>
                    </form>
                @else
                    <div class="alert alert-light border mb-3">Request revisi tersedia setelah Design Request selesai diproses Produksi.</div>
                @endif

                <div class="table-wrap">
                    <table class="table-r compact">
                        <thead><tr><th>Revisi</th><th>Catatan</th><th>Status</th><th>Request</th><th>Drawing Baru</th><th>Selesai</th></tr></thead>
                        <tbody>
                        @forelse($designRequest->revisionRequests as $revision)
                            <tr>
                                <td class="fw-bold">Rev {{ $revision->revision_number }}</td>
                                <td>{{ $revision->notes }}</td>
                                <td><x-status-badge :status="$revision->status" /></td>
                                <td>{{ $revision->requested_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td>{{ $revision->drawing_uploaded_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td>{{ $revision->completed_at?->format('d M Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Belum ada request revisi.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="info-card mb-3" id="history">
                <h6><i class="bi bi-clock-history me-1 text-primary"></i>Riwayat Proses</h6>
                <div class="d-timeline small">
                    @foreach($designRequest->revisionRequests as $revision)
                        <div>
                            <time>{{ $revision->requested_at?->format('d M H:i') }}</time><span></span>
                            <p><strong>Revisi {{ $revision->revision_number }}</strong><small>{{ $revision->notes }}</small></p>
                        </div>
                    @endforeach
                    <div>
                        <time>{{ $designRequest->updated_at->format('d M H:i') }}</time><span></span>
                        <p><strong>{{ \App\Models\DesignRequest::statuses()[$designRequest->status] ?? \Illuminate\Support\Str::headline($designRequest->status) }}</strong><small>Pembaruan terakhir</small></p>
                    </div>
                    <div>
                        <time>{{ $designRequest->created_at->format('d M H:i') }}</time><span></span>
                        <p><strong>Design Request dibuat</strong><small>Oleh {{ $designRequest->sales?->name ?? 'Sales' }}</small></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="info-card mb-3">
                <h6><i class="bi bi-person-workspace me-1 text-primary"></i>Assignment</h6>
                <div class="kv"><div class="k">Sales</div><div class="v">{{ $designRequest->sales?->name ?? '—' }}</div></div>
                <div class="kv"><div class="k">Drafter</div><div class="v">{{ $designRequest->productionPic?->name ?? '—' }}</div></div>
                <div class="kv"><div class="k">Tanggal Request</div><div class="v">{{ $designRequest->request_date?->translatedFormat('d M Y') ?? $designRequest->created_at?->translatedFormat('d M Y') }}</div></div>
                <div class="kv"><div class="k">Deadline</div><div class="v">{{ $designRequest->deadline?->translatedFormat('d M Y') ?? '—' }}</div></div>
                <div class="kv"><div class="k">Status Urgensi</div><div class="v"><span class="status-soft {{ $priorityClass }}">{{ \App\Models\DesignRequest::urgencyLabel($designRequest->priority) }}</span></div></div>
                @if($designRequest->production_note)
                    <hr>
                    <div class="small text-muted-2 mb-1">Catatan untuk Drafter</div>
                    <p class="small mb-0">{{ $designRequest->production_note }}</p>
                @endif
            </div>

            <div class="info-card mb-3">
                <h6><i class="bi bi-activity me-1 text-success"></i>Progress</h6>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <strong class="fs-3">{{ $designRequest->progress }}%</strong>
                    <div class="sales-progress flex-grow-1"><span style="width:{{ $designRequest->progress }}%"></span></div>
                </div>
                <div class="small text-muted-2">Status terakhir: {{ \App\Models\DesignRequest::statuses()[$designRequest->status] ?? $designRequest->status }}</div>
            </div>

            @if($designRequest->cost_total)
                <div class="info-card mb-3">
                    <h6><i class="bi bi-cash-stack me-1 text-warning"></i>Estimasi Biaya</h6>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Material</span><span class="fw-num">{{ \App\Support\Format::rupiah($designRequest->cost_material) }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Produksi</span><span class="fw-num">{{ \App\Support\Format::rupiah($designRequest->cost_production) }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Instalasi</span><span class="fw-num">{{ \App\Support\Format::rupiah($designRequest->cost_installation) }}</span></div>
                    <hr>
                    <div class="d-flex justify-content-between"><strong>Total</strong><strong class="fw-num">{{ \App\Support\Format::rupiah($designRequest->cost_total) }}</strong></div>
                </div>
            @endif

            <div class="info-card" id="documents">
                <h6><i class="bi bi-folder2-open me-1 text-primary"></i>Dokumen</h6>
                @forelse($designRequest->documents->sortByDesc('created_at') as $document)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <strong class="small">{{ $document->name }}</strong>
                            <div class="small text-muted-2">{{ $document->revisionLabel() }} · {{ $document->created_at?->format('d M Y H:i') }} · {{ $document->humanSize() }}</div>
                        </div>
                        <a href="{{ asset('storage/'.$document->file_path) }}" class="btn btn-sm btn-soft" target="_blank"><i class="bi bi-download"></i></a>
                    </div>
                @empty
                    <div class="small text-muted-2">Belum ada dokumen pendukung.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
