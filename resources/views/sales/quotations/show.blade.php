@extends('layouts.app')
@section('title', 'Detail Penawaran')
@section('content')
<x-page-header :title="$quotation->code" :subtitle="$quotation->customer_name.' · '.$quotation->project_name">
    @if($quotation->canBeEdited())
        <a href="{{ route('sales.quotations.edit', $quotation) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square me-1"></i>Edit</a>
    @endif
    @if($quotation->canBePublished())
        <form method="POST" action="{{ route('sales.quotations.ready',$quotation) }}" class="d-inline">@csrf<button class="btn btn-primary btn-sm"><i class="bi bi-check2-circle me-1"></i>Siapkan Penawaran</button></form>
    @endif
    @if($quotation->canDownloadPdf())
        <a href="{{ route('sales.quotations.excel',$quotation) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a>
        <a href="{{ route('sales.quotations.pdf',$quotation) }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>
    @endif
    @if($quotation->status === 'ready')
        <form method="POST" action="{{ route('sales.quotations.sent-to-customer',$quotation) }}" class="d-inline">@csrf<button class="btn btn-soft btn-sm"><i class="bi bi-send me-1"></i>Tandai Dikirim</button></form>
    @endif
    @if(in_array($quotation->status,['sent_to_customer','sent','negotiation']))
        <div class="dropdown">
            <button class="btn btn-soft btn-sm" data-bs-toggle="dropdown"><i class="bi bi-chat-square-text me-1"></i>Respon Customer</button>
            <div class="dropdown-menu dropdown-menu-end">
                <form method="POST" action="{{ route('sales.quotations.won',$quotation) }}">@csrf<button class="dropdown-item text-success"><i class="bi bi-check2-circle me-2"></i>Customer Setuju</button></form>
                <form method="POST" action="{{ route('sales.quotations.lost',$quotation) }}">@csrf<button class="dropdown-item text-danger"><i class="bi bi-x-circle me-2"></i>Customer Tidak Setuju</button></form>
            </div>
        </div>
    @endif
    @if($quotation->canCreatePurchaseOrderRequest())
        <a href="{{ route('admin.purchase-order-requests.create',['quotation'=>$quotation->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-receipt me-1"></i>Buat Project</a>
    @endif
    @if(auth()->user()->canCreateProject() && $quotation->canCreateProject())
        <a href="{{ route('sales.projects.create',['quotation'=>$quotation->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-folder-plus me-1"></i>Buat Request Process</a>
    @endif
</x-page-header>

@if(auth()->user()->canCreateProject() && in_array($quotation->status, \App\Models\Quotation::wonStatuses(), true) && ! $quotation->project && ! $quotation->canCreateProject())
    <div class="alert alert-light border small"><i class="bi bi-info-circle me-1"></i>Request Process baru dapat dibuat setelah Project diajukan, karena produksi berjalan atas dasar PO yang sudah tercatat.</div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        @if(!$quotation->isUploaded() || $quotation->items->isNotEmpty())
            <div class="card-r">
                <div class="card-head"><h2>Item Penawaran</h2><x-status-badge :status="$quotation->status" :label="$quotation->statusLabel()" /></div>
                <div class="quotation-item-list">
                    @forelse($quotation->items as $item)
                        <x-quotation-item-card :item="$item" :show-cost="false" />
                    @empty
                        <x-empty text="Belum ada item penawaran." />
                    @endforelse
                </div>
            </div>
        @endif

        <div class="card-r">
            <div class="card-head"><h2>Dokumen Penawaran</h2><span class="badge text-bg-light">{{ $quotation->documents->count() }} file</span></div>
            @forelse($quotation->documents as $document)
                <div class="d-flex align-items-center justify-content-between gap-3 py-2 @unless($loop->last) border-bottom @endunless">
                    <div>
                        <div class="fw-semibold"><i class="bi {{ $document->category === 'quotation_file' ? 'bi-file-earmark-check' : 'bi-file-earmark-text' }} text-primary me-1"></i>{{ $document->name }}.{{ $document->file_type }}</div>
                        <div class="small text-muted-2">{{ $document->category === 'quotation_file' ? 'File penawaran utama · ' : '' }}{{ $document->humanSize() }} · {{ $document->uploader?->name ?: 'System' }} · {{ $document->created_at?->format('d M Y H:i') }}</div>
                    </div>
                    <div class="d-flex gap-2"><a href="{{ route('documents.preview', $document) }}" target="_blank" class="btn btn-primary btn-sm"><i class="bi bi-eye me-1"></i>Preview</a><a href="{{ route('documents.download', $document) }}" class="btn btn-soft btn-sm" download><i class="bi bi-download"></i></a><x-document-delete-button :document="$document" class="btn btn-soft btn-sm text-danger" /></div>
                </div>
            @empty
                <x-empty text="Belum ada dokumen penawaran." />
            @endforelse
        </div>

        @if($quotation->customer_note || $quotation->internal_note || $quotation->customer_response_note)
            <div class="card-r">
                <div class="card-head"><h2>Catatan</h2></div>
                @if($quotation->customer_note)
                    <div class="mb-3">
                        <div class="text-muted-2 small fw-semibold mb-1">Catatan untuk Customer</div>
                        <div class="small">{!! nl2br(e($quotation->customer_note)) !!}</div>
                    </div>
                @endif
                @if($quotation->internal_note)
                    <div class="mb-3">
                        <div class="text-muted-2 small fw-semibold mb-1">Catatan Internal</div>
                        <div class="small">{!! nl2br(e($quotation->internal_note)) !!}</div>
                    </div>
                @endif
                @if($quotation->customer_response_note)
                    <div>
                        <div class="text-muted-2 small fw-semibold mb-1">Respon Customer</div>
                        <div class="small">{!! nl2br(e($quotation->customer_response_note)) !!}</div>
                    </div>
                @endif
            </div>
        @endif

        <div class="card-r">
            <div class="card-head"><h2>Riwayat Penawaran</h2></div>
            <div class="table-wrap"><table class="table-r"><thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Status</th><th>Catatan</th></tr></thead><tbody>
            @forelse($quotation->approvalHistories as $history)
                <tr><td>{{ $history->created_at?->format('d M Y H:i') }}</td><td>{{ $history->user?->name ?: 'System' }}</td><td class="fw-semibold">{{ $history->actionLabel() }}</td><td class="small">{{ $history->status_from ?: '—' }} → {{ $history->status_to ?: '—' }}</td><td class="small">{{ $history->note ?: '—' }}</td></tr>
            @empty
                <tr><td colspan="5"><x-empty text="Belum ada riwayat penawaran." /></td></tr>
            @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-r">
            <div class="card-head"><h2>Ringkasan Harga</h2></div>
            @unless($quotation->isUploaded())
                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Subtotal</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->subtotal) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Diskon</span><span class="fw-num text-danger">- {{ \App\Support\Format::rupiah($quotation->discount_amount) }}</span></div>
                @if($quotation->discount_reason)
                    <div class="form-text mb-2">Alasan diskon: {{ $quotation->discount_reason }}</div>
                @endif
                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">PPN ({{ rtrim(rtrim(number_format($quotation->tax_percent,2),'0'),'.') }}%)</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->tax_amount) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Biaya Tambahan</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->additional_total) }}</span></div><hr>
            @endunless
            <div class="d-flex justify-content-between"><strong>Grand Total</strong><strong class="fw-num">{{ \App\Support\Format::rupiah($quotation->grand_total) }}</strong></div>
            @if($quotation->isUploaded())
                <div class="form-text mt-2">Penawaran diunggah sebagai file, jadi rincian harga per item tidak tercatat di sistem.</div>
            @endif
        </div>

        <div class="card-r">
            <div class="card-head"><h2>Customer &amp; PIC</h2></div>
            <div class="mb-2 d-flex justify-content-between gap-2"><span class="text-muted-2">Customer</span><span class="fw-semibold text-end">@if($quotation->customer)<a href="{{ route('sales.customers.show', $quotation->customer) }}">{{ $quotation->customer_name }}</a>@else{{ $quotation->customer_name ?: '—' }}@endif</span></div>
            <div class="mb-2 d-flex justify-content-between gap-2"><span class="text-muted-2">Project</span><span class="fw-semibold text-end">{{ $quotation->project_name ?: '—' }}</span></div>
            @php($pic = $quotation->customer?->primaryPic)
            <div class="mb-2 d-flex justify-content-between gap-2"><span class="text-muted-2">PIC</span><span class="fw-semibold text-end">{{ $quotation->pic_name ?: ($pic?->name ?: '—') }}</span></div>
            @if($pic?->position)
                <div class="mb-2 d-flex justify-content-between gap-2"><span class="text-muted-2">Jabatan PIC</span><span class="fw-semibold text-end">{{ $pic->position }}</span></div>
            @endif
            <div class="mb-2 d-flex justify-content-between gap-2"><span class="text-muted-2">Telepon</span><span class="fw-semibold text-end">{{ $pic?->phone ?: ($quotation->customer?->phone ?: '—') }}</span></div>
            <div class="mb-2 d-flex justify-content-between gap-2"><span class="text-muted-2">Email</span><span class="fw-semibold text-end text-break">{{ $pic?->email ?: ($quotation->customer?->email ?: '—') }}</span></div>
            <div class="mb-2 d-flex justify-content-between gap-2"><span class="text-muted-2">Kota</span><span class="fw-semibold text-end">{{ $quotation->customer?->city ?: '—' }}</span></div>
            @if($quotation->customer?->address)
                <div class="mt-3"><div class="text-muted-2 small">Alamat</div><div class="small">{{ $quotation->customer->address }}</div></div>
            @endif
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Pencatatan Penawaran</h2></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Status</span><span class="fw-semibold">{{ $quotation->statusLabel() }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Dibuat oleh</span><span class="fw-semibold">{{ $quotation->sales?->name ?: '—' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Jenis</span><span class="fw-semibold">{{ $quotation->creationModeLabel() }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Dibuat</span><span class="fw-semibold">{{ $quotation->created_at?->format('d M Y H:i') }}</span></div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Info Penawaran</h2></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Tanggal</span><span class="fw-semibold">{{ $quotation->quote_date?->format('d M Y') }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Berlaku s/d</span><span class="fw-semibold">{{ $quotation->valid_until?->format('d M Y') }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Pengiriman</span><span class="fw-semibold">{{ ucfirst($quotation->delivery_method) }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Prioritas</span><span class="fw-semibold">{{ ['low'=>'Rendah','medium'=>'Sedang','high'=>'Tinggi'][$quotation->priority] ?? ucfirst((string) $quotation->priority) }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Mata Uang</span><span class="fw-semibold">{{ $quotation->currency ?: 'IDR' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Dikirim</span><span class="fw-semibold">{{ $quotation->sent_at?->format('d M Y H:i') ?: 'Belum dikirim' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Respon Customer</span><span class="fw-semibold">{{ $quotation->customer_response_at?->format('d M Y H:i') ?: 'Belum ada' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Sales</span><span class="fw-semibold">{{ $quotation->sales?->name ?: '—' }}</span></div>
        </div>

        <div class="card-r">
            <div class="card-head"><h2>Terhubung Dengan</h2></div>
            <div class="mb-2 d-flex justify-content-between gap-2"><span class="text-muted-2">Lead</span><span class="fw-semibold text-end">@if($quotation->lead)<a href="{{ route('sales.leads.show', $quotation->lead) }}">{{ $quotation->lead->code ?: $quotation->lead->instansi }}</a>@else—@endif</span></div>
            <div class="mb-2 d-flex justify-content-between gap-2"><span class="text-muted-2">Design Request</span><span class="fw-semibold text-end">@if($quotation->designRequest)<a href="{{ route('sales.design-requests.show', $quotation->designRequest) }}">{{ $quotation->designRequest->code }}</a>@else Tanpa Design Request @endif</span></div>
            <div class="mb-2 d-flex justify-content-between gap-2"><span class="text-muted-2">Project</span><span class="fw-semibold text-end">@if($quotation->purchaseOrderRequest)<a href="{{ route('admin.purchase-order-requests.show', $quotation->purchaseOrderRequest) }}">{{ $quotation->purchaseOrderRequest->projectNumber() }}</a>@else Belum dibuat @endif</span></div>
            <div class="mb-2 d-flex justify-content-between gap-2"><span class="text-muted-2">Request Process</span><span class="fw-semibold text-end">@if($quotation->project)<a href="{{ route('sales.projects.show', $quotation->project) }}">{{ $quotation->project->code }}</a>@else Belum dibuat @endif</span></div>
        </div>
    </div>
</div>
@endsection
