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
    @if(in_array($quotation->status,['ready','sent_to_customer','sent','negotiation']))
        <form method="POST" action="{{ route('sales.quotations.won',$quotation) }}" class="d-inline">@csrf<button class="btn btn-success btn-sm"><i class="bi bi-check2-circle me-1"></i>Customer Setuju</button></form>
        <form method="POST" action="{{ route('sales.quotations.lost',$quotation) }}" class="d-inline">@csrf<button class="btn btn-soft btn-sm text-danger">Customer Tidak Setuju</button></form>
    @endif
    @if($quotation->canCreatePurchaseOrderRequest())
        <a href="{{ route('admin.purchase-order-requests.create',['quotation'=>$quotation->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-receipt me-1"></i>Buat Request PO</a>
    @endif
</x-page-header>

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
                    <div class="d-flex gap-2"><a href="{{ route('documents.preview', $document) }}" target="_blank" class="btn btn-primary btn-sm"><i class="bi bi-eye me-1"></i>Preview</a><a href="{{ route('documents.download', $document) }}" class="btn btn-soft btn-sm" download><i class="bi bi-download"></i></a></div>
                </div>
            @empty
                <x-empty text="Belum ada dokumen penawaran." />
            @endforelse
        </div>

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
        @unless($quotation->isUploaded())
            <div class="card-r">
                <div class="card-head"><h2>Ringkasan Harga</h2></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Subtotal</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->subtotal) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Diskon</span><span class="fw-num text-danger">- {{ \App\Support\Format::rupiah($quotation->discount_amount) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">PPN ({{ rtrim(rtrim(number_format($quotation->tax_percent,2),'0'),'.') }}%)</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->tax_amount) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Biaya Tambahan</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->additional_total) }}</span></div><hr>
                <div class="d-flex justify-content-between"><strong>Grand Total</strong><strong class="fw-num">{{ \App\Support\Format::rupiah($quotation->grand_total) }}</strong></div>
            </div>
        @endunless
        <div class="card-r">
            <div class="card-head"><h2>Pencatatan Penawaran</h2></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Status</span><span class="fw-semibold">{{ $quotation->statusLabel() }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Dibuat oleh</span><span class="fw-semibold">{{ $quotation->sales?->name ?: '—' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Jenis</span><span class="fw-semibold">{{ $quotation->isUploaded() ? 'Upload file' : 'Dibuat di sistem' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Dibuat</span><span class="fw-semibold">{{ $quotation->created_at?->format('d M Y H:i') }}</span></div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Info Penawaran</h2></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Tanggal</span><span class="fw-semibold">{{ $quotation->quote_date?->format('d M Y') }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Berlaku s/d</span><span class="fw-semibold">{{ $quotation->valid_until?->format('d M Y') }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Pengiriman</span><span class="fw-semibold">{{ ucfirst($quotation->delivery_method) }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Sales</span><span class="fw-semibold">{{ $quotation->sales?->name ?: '—' }}</span></div>
        </div>
    </div>
</div>
@endsection
