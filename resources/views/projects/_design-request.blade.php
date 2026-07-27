@if(! $designRequest)
    <div class="alert alert-info">
        <i class="bi bi-lightning-charge-fill me-1"></i>
        Penawaran ini tidak memerlukan Request Gambar dan diteruskan langsung ke Produksi.
    </div>

    <section class="workflow-card mb-3">
        <div class="card-head">
            <h2>Item & Spesifikasi Penawaran</h2>
            <span class="status-soft st-green">Acuan Produksi</span>
        </div>
        <div class="quotation-item-list">
            @forelse($quotation?->items?->sortBy('sort_order') ?? collect() as $item)
                <x-quotation-item-card
                    :item="$item"
                    :show-cost="false"
                    :show-price="$showPrices"
                    price-label="Harga per Item"
                />
            @empty
                <x-empty text="Belum ada item penawaran sebagai acuan produksi." />
            @endforelse
        </div>
    </section>

    <section class="workflow-card">
        <div class="card-head"><h2>Dokumen Penawaran</h2></div>
        <div class="table-wrap">
            <table class="table-r compact">
                <thead><tr><th>Dokumen</th><th>Jenis</th><th>Ukuran</th><th>Uploader</th><th>Tanggal</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($quotation?->documents ?? collect() as $document)
                    <tr>
                        <td class="fw-semibold">{{ $document->name }}.{{ $document->file_type }}</td>
                        <td>{{ str($document->category)->headline() }}</td>
                        <td>{{ $document->humanSize() }}</td>
                        <td>{{ $document->uploader?->name ?? '-' }}</td>
                        <td>{{ $document->created_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                        <td><a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-soft"><i class="bi bi-download"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">Tidak ada dokumen tambahan pada penawaran.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@else
    @php
        $designRequestStatus = \App\Models\DesignRequest::statuses()[$designRequest->status]
            ?? \Illuminate\Support\Str::headline($designRequest->status);
        $costTotal = (float) $designRequest->cost_material
            + (float) $designRequest->cost_production
            + (float) $designRequest->cost_installation;
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <h2 class="h4 fw-black mb-0">{{ $designRequest->code }}</h2>
                <x-status-badge :status="$designRequest->status" :label="$designRequestStatus" />
            </div>
            <div class="text-muted-2 mt-1">{{ $designRequest->customer_name }} · {{ $designRequest->project_name }}</div>
        </div>
        <div style="min-width:220px">
            <div class="d-flex justify-content-between small mb-1"><span>Progress Design</span><strong>{{ $designRequest->progress }}%</strong></div>
            <div class="sales-progress"><span style="width:{{ $designRequest->progress }}%"></span></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <section class="workflow-card mb-3">
                <div class="card-head"><h2>Informasi Customer & Kebutuhan</h2></div>
                <div class="row g-3">
                    <div class="col-md-4"><div class="small text-muted-2">Customer</div><div class="fw-semibold">{{ $designRequest->customer_name }}</div></div>
                    <div class="col-md-4"><div class="small text-muted-2">PIC Customer</div><div class="fw-semibold">{{ $designRequest->pic_name ?: $designRequest->customer?->primaryPic?->name ?: '-' }}</div></div>
                    <div class="col-md-4"><div class="small text-muted-2">Kode Master Customer</div><div class="fw-semibold">{{ $designRequest->customer?->code ?: '-' }}</div></div>
                    <div class="col-md-6"><div class="small text-muted-2">Nama Laboratorium / Proyek</div><div class="fw-semibold">{{ $designRequest->project_name }}</div></div>
                    <div class="col-md-3"><div class="small text-muted-2">Jenis Kebutuhan</div><div class="fw-semibold">{{ $designRequest->lab_type ?: '-' }}</div></div>
                    <div class="col-md-3"><div class="small text-muted-2">Kapasitas</div><div class="fw-semibold">{{ $designRequest->capacity ?: '-' }}</div></div>
                    <div class="col-12"><div class="small text-muted-2">Ringkasan</div><div>{{ $designRequest->short_description ?: '-' }}</div></div>
                    <div class="col-12"><div class="small text-muted-2">Detail Kebutuhan</div><div class="text-break">{!! nl2br(e($designRequest->detail_need ?: '-')) !!}</div></div>
                    <div class="col-12">
                        <div class="small text-muted-2 mb-2">Scope</div>
                        @forelse($designRequest->scope_checklist ?? [] as $scope)
                            <span class="tag-pill me-1 mb-1">{{ $scope }}</span>
                        @empty
                            <span class="text-muted-2">Belum ada scope.</span>
                        @endforelse
                    </div>
                    <div class="col-12">
                        <div class="small text-muted-2 mb-2">Output Diminta</div>
                        @forelse($designRequest->outputs ?? [] as $output)
                            <span class="status-soft st-purple me-1 mb-1">{{ strtoupper(str_replace('_', ' ', $output)) }}</span>
                        @empty
                            <span class="text-muted-2">Belum ada output.</span>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="workflow-card mb-3">
                <div class="card-head"><h2>Item & Spesifikasi Hasil Design Request</h2></div>
                <div class="quotation-item-list design-request-production-items">
                    @forelse($designRequest->items->sortBy('sort_order') as $item)
                        <x-quotation-item-card
                            :item="$item"
                            :show-cost="false"
                            :show-price="$showPrices"
                            price-label="HPP per Item"
                        />
                    @empty
                        <x-empty text="Belum ada item hasil Design Request." />
                    @endforelse
                </div>
            </section>

            <section class="workflow-card mb-3">
                <div class="card-head"><h2>Drawing & Dokumen</h2></div>
                <div class="table-wrap">
                    <table class="table-r compact">
                        <thead><tr><th>Dokumen</th><th>Jenis</th><th>Revisi</th><th>Status</th><th>Uploader</th><th>Tanggal</th><th>Aksi</th></tr></thead>
                        <tbody>
                        @forelse($designRequest->documents->sortByDesc('created_at') as $document)
                            <tr>
                                <td class="fw-semibold">{{ $document->name }}</td>
                                <td>{{ str($document->category)->headline() }}</td>
                                <td>{{ $document->revisionLabel() }}@if($document->revision_note)<small class="d-block text-muted-2">{{ $document->revision_note }}</small>@endif</td>
                                <td><span class="status-soft {{ $document->is_current ? 'st-green' : 'st-gray' }}">{{ $document->is_current ? 'Aktif' : 'Riwayat' }}</span></td>
                                <td>{{ $document->uploader?->name ?? '-' }}</td>
                                <td>{{ $document->created_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td><a href="{{ asset('storage/'.$document->file_path) }}" target="_blank" class="btn btn-sm btn-soft"><i class="bi bi-download"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Belum ada drawing atau dokumen.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="workflow-card">
                <div class="card-head"><h2>Riwayat Request Revisi</h2></div>
                <div class="table-wrap">
                    <table class="table-r compact">
                        <thead><tr><th>Revisi</th><th>Catatan Sales</th><th>Status</th><th>Diminta Oleh</th><th>Request</th><th>Drawing Terbaru</th><th>Selesai</th></tr></thead>
                        <tbody>
                        @forelse($designRequest->revisionRequests as $revision)
                            <tr>
                                <td class="fw-bold">Rev {{ $revision->revision_number }}</td>
                                <td>{{ $revision->notes }}</td>
                                <td><x-status-badge :status="$revision->status" /></td>
                                <td>{{ $revision->requester?->name ?? '-' }}</td>
                                <td>{{ $revision->requested_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td>{{ $revision->drawing_uploaded_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td>{{ $revision->completed_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Belum ada request revisi.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <section class="workflow-card mb-3">
                <h3 class="mb-3">Assignment & Timeline</h3>
                <div class="kv"><div class="k">Sales</div><div class="v">{{ $designRequest->sales?->name ?? '-' }}</div></div>
                <div class="kv"><div class="k">Drafter</div><div class="v">{{ $designRequest->productionPic?->name ?? '-' }}</div></div>
                <div class="kv"><div class="k">Tanggal Request</div><div class="v">{{ $designRequest->request_date?->translatedFormat('d M Y') ?? $designRequest->created_at?->translatedFormat('d M Y') }}</div></div>
                <div class="kv"><div class="k">Deadline</div><div class="v">{{ $designRequest->deadline?->translatedFormat('d M Y') ?? '-' }}</div></div>
                <div class="kv"><div class="k">Urgensi</div><div class="v">{{ \App\Models\DesignRequest::urgencyLabel($designRequest->priority) }}</div></div>
                <div class="kv"><div class="k">Submit Final</div><div class="v">{{ $designRequest->submitted_at?->translatedFormat('d M Y H:i') ?? '-' }}</div></div>
                <div class="kv"><div class="k">Dibuat</div><div class="v">{{ $designRequest->created_at?->translatedFormat('d M Y H:i') ?? '-' }}</div></div>
                <div class="kv"><div class="k">Terakhir Diperbarui</div><div class="v">{{ $designRequest->updated_at?->translatedFormat('d M Y H:i') ?? '-' }}</div></div>
            </section>

            @if($showPrices)
                <section class="workflow-card mb-3">
                    <h3 class="mb-3">Estimasi Costing Awal</h3>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Material</span><span class="fw-num">{{ \App\Support\Format::rupiah($designRequest->cost_material) }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Produksi</span><span class="fw-num">{{ \App\Support\Format::rupiah($designRequest->cost_production) }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Instalasi</span><span class="fw-num">{{ \App\Support\Format::rupiah($designRequest->cost_installation) }}</span></div>
                    <hr>
                    <div class="d-flex justify-content-between"><strong>Total Estimasi</strong><strong class="fw-num">{{ \App\Support\Format::rupiah($costTotal) }}</strong></div>
                </section>
            @endif

            <section class="workflow-card mb-3">
                <h3 class="mb-3">Catatan</h3>
                <div class="small text-muted-2">Catatan Sales</div>
                <p class="text-break">{!! nl2br(e($designRequest->extra_note ?: '-')) !!}</p>
                <div class="small text-muted-2">Catatan untuk Drafter</div>
                <p class="text-break">{!! nl2br(e($designRequest->production_note ?: '-')) !!}</p>
                <div class="small text-muted-2">Catatan Teknis</div>
                <p class="text-break mb-0">{!! nl2br(e($designRequest->technical_note ?: '-')) !!}</p>
            </section>

            <section class="workflow-card">
                <h3 class="mb-3">Sumber Data</h3>
                <div class="kv"><div class="k">Lead</div><div class="v">{{ $designRequest->lead?->code ?? '-' }}</div></div>
                <div class="kv"><div class="k">Penawaran</div><div class="v">{{ $project->quotation?->code ?? '-' }}</div></div>
                <div class="kv"><div class="k">Status Pipeline Customer</div><div class="v">{{ $designRequest->customer ? (\App\Models\Customer::stages()[$designRequest->customer->pipeline_stage] ?? \Illuminate\Support\Str::headline($designRequest->customer->pipeline_stage)) : '-' }}</div></div>
            </section>
        </div>
    </div>
@endif
