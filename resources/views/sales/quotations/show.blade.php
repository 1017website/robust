@extends('layouts.app')
@section('title', 'Detail Penawaran')
@section('content')
<x-page-header :title="$quotation->code" :subtitle="$quotation->customer_name.' · '.$quotation->project_name">
    @if($quotation->canBeEdited())
        <a href="{{ route('sales.quotations.edit', $quotation) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square me-1"></i>Edit/Revisi</a>
    @endif
    @if($quotation->canBeSubmittedForApproval())
        <form method="POST" action="{{ route('sales.quotations.submit-approval',$quotation) }}" class="d-inline">@csrf<button class="btn btn-primary btn-sm"><i class="bi bi-send-check me-1"></i>Ajukan Approval SPV</button></form>
    @endif
    @if($quotation->canDownloadPdf())
        <a href="{{ route('sales.quotations.excel',$quotation) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a>
        <a href="{{ route('sales.quotations.pdf',$quotation) }}" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>
    @else
        <button class="btn btn-soft btn-sm" disabled title="Excel aktif setelah approval SPV"><i class="bi bi-lock me-1"></i>Excel Terkunci</button>
        <button class="btn btn-soft btn-sm" disabled title="PDF aktif setelah approval SPV"><i class="bi bi-lock me-1"></i>PDF Terkunci</button>
    @endif
    @if($quotation->status === 'approved')
        <form method="POST" action="{{ route('sales.quotations.sent-to-customer',$quotation) }}" class="d-inline">@csrf<button class="btn btn-soft btn-sm"><i class="bi bi-send me-1"></i>Tandai Dikirim</button></form>
    @endif
    @if(in_array($quotation->status,['approved','sent_to_customer','sent','negotiation']))
        <form method="POST" action="{{ route('sales.quotations.won',$quotation) }}" class="d-inline">@csrf<button class="btn btn-success btn-sm"><i class="bi bi-check2-circle me-1"></i>Customer Setuju</button></form>
        <form method="POST" action="{{ route('sales.quotations.lost',$quotation) }}" class="d-inline">@csrf<button class="btn btn-soft btn-sm text-danger">Customer Tidak Setuju</button></form>
    @endif
    @if($quotation->canCreatePurchaseOrderRequest())
        <a href="{{ route('admin.purchase-order-requests.create',['quotation'=>$quotation->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-receipt me-1"></i>Buat Request PO</a>
    @endif
</x-page-header>

@if($quotation->status === 'revision')
    <div class="alert alert-warning"><strong>Perlu revisi:</strong> {{ $quotation->revision_note ?: 'SPV meminta revisi penawaran.' }} Gunakan tombol <strong>Edit/Revisi</strong>, lalu ajukan ulang ke SPV.</div>
@endif
@if($quotation->status === 'rejected')
    <div class="alert alert-danger"><strong>Ditolak SPV:</strong> {{ $quotation->rejection_note ?: 'Tidak ada catatan.' }} Penawaran dapat diedit lalu diajukan ulang bila diperlukan.</div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-r">
            <div class="card-head"><h2>Item Penawaran</h2><x-status-badge :status="$quotation->status" :label="$quotation->statusLabel()" /></div>
            <div class="quotation-item-list">
                @foreach($quotation->items as $it)
                    <x-quotation-item-card :item="$it" />
                @endforeach
            </div>
            <div class="table-wrap d-none">
                <table class="table-r">
                    <thead><tr><th>Item</th><th>Spesifikasi</th><th>Qty</th><th>HPP</th><th>Margin</th><th>Harga Jual</th><th>Total</th></tr></thead>
                    <tbody>
                    @foreach($quotation->items as $it)
                        <tr>
                            <td class="fw-semibold">{{ $it->name }}@if($it->is_optional)<span class="badge text-bg-secondary ms-1">Opsional</span>@endif @if($it->variant)<small class="d-block text-primary">{{ $it->variant }}</small>@endif @if($it->itemMaster)<small class="d-block text-muted-2">{{ $it->itemMaster->code }}</small>@endif @if($it->quotation_image_path)<img src="{{ asset('storage/'.$it->quotation_image_path) }}" alt="{{ $it->name }}" class="d-block mt-2" style="width:120px;height:90px;object-fit:contain">@endif</td>
                            <td class="small" style="white-space:pre-line">{{ $it->specification ?: '—' }}</td>
                            <td>{{ rtrim(rtrim(number_format($it->qty,2),'0'),'.') }} {{ $it->unit }}</td>
                            <td class="fw-num">{{ \App\Support\Format::rupiah($it->cost_price) }}</td>
                            <td>{{ rtrim(rtrim(number_format($it->margin,2),'0'),'.') }}%</td>
                            <td class="fw-num">{{ \App\Support\Format::rupiah($it->unit_price) }}</td>
                            <td class="fw-num">{{ \App\Support\Format::rupiah($it->total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-r">
            <div class="card-head">
                <h2>Dokumen Penawaran</h2>
                <span class="badge text-bg-light">{{ $quotation->documents->count() }} file</span>
            </div>
            @forelse($quotation->documents as $document)
                <div class="d-flex align-items-center justify-content-between gap-3 py-2 @unless($loop->last) border-bottom @endunless">
                    <div>
                        <div class="fw-semibold"><i class="bi bi-file-earmark-text text-primary me-1"></i>{{ $document->name }}.{{ $document->file_type }}</div>
                        <div class="small text-muted-2">{{ $document->humanSize() }} · {{ $document->uploader?->name ?: 'System' }} · {{ $document->created_at?->format('d M Y H:i') }}</div>
                    </div>
                    <a href="{{ route('documents.download', $document) }}" class="btn btn-soft btn-sm" download>
                        <i class="bi bi-download me-1"></i>Download
                    </a>
                </div>
            @empty
                <x-empty text="Belum ada dokumen pendukung penawaran." />
            @endforelse
        </div>

        @if($quotation->revision_note || $quotation->rejection_note || $quotation->approval_note)
            <div class="card-r">
                <div class="card-head"><h2>Catatan Approval SPV</h2></div>
                @if($quotation->approval_note)
                    <div class="alert alert-success mb-2"><strong>Approved:</strong> {{ $quotation->approval_note }}</div>
                @endif
                @if($quotation->revision_note)
                    <div class="alert alert-warning mb-2"><strong>Revisi:</strong> {{ $quotation->revision_note }}</div>
                @endif
                @if($quotation->rejection_note)
                    <div class="alert alert-danger mb-0"><strong>Ditolak:</strong> {{ $quotation->rejection_note }}</div>
                @endif
            </div>
        @endif

        <div class="card-r">
            <div class="card-head"><h2>Riwayat Approval & Revisi</h2></div>
            <div class="table-wrap">
                <table class="table-r">
                    <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Status</th><th>Catatan</th></tr></thead>
                    <tbody>
                    @forelse($quotation->approvalHistories as $history)
                        <tr>
                            <td>{{ $history->created_at?->format('d M Y H:i') }}</td>
                            <td>{{ $history->user?->name ?: 'System' }}</td>
                            <td class="fw-semibold">{{ $history->actionLabel() }}</td>
                            <td class="small">{{ $history->status_from ?: '—' }} → {{ $history->status_to ?: '—' }}</td>
                            <td class="small">{{ $history->note ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty text="Belum ada riwayat approval." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($quotation->purchaseOrderRequest)
            <div class="card-r">
                <div class="card-head"><h2>Request PO</h2><x-status-badge :status="$quotation->purchaseOrderRequest->status" :label="\App\Models\PurchaseOrderRequest::statuses()[$quotation->purchaseOrderRequest->status] ?? $quotation->purchaseOrderRequest->status" /></div>
                <div class="row g-2 small">
                    <div class="col-md-4"><div class="text-muted-2">No Request PO</div><div class="fw-semibold">{{ $quotation->purchaseOrderRequest->code }}</div></div>
                    <div class="col-md-4"><div class="text-muted-2">No PO Accurate</div><div class="fw-semibold">{{ $quotation->purchaseOrderRequest->accurate_po_number ?: '—' }}</div></div>
                    <div class="col-md-4"><div class="text-muted-2">Tanggal Request</div><div class="fw-semibold">{{ $quotation->purchaseOrderRequest->request_date?->format('d M Y') ?: '—' }}</div></div>
                </div>
                <a href="{{ route('admin.purchase-order-requests.show',$quotation->purchaseOrderRequest) }}" class="btn btn-soft btn-sm mt-3">Lihat Request PO</a>
            </div>
        @endif
    </div>
    <div class="col-lg-4">
        <div class="card-r">
            <div class="card-head"><h2>Ringkasan Harga</h2></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Subtotal</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->subtotal) }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Diskon</span><span class="fw-num text-danger">- {{ \App\Support\Format::rupiah($quotation->discount_amount) }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">PPN ({{ rtrim(rtrim(number_format($quotation->tax_percent,2),'0'),'.') }}%)</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->tax_amount) }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Biaya Tambahan</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->additional_total) }}</span></div>
            <hr>
            <div class="d-flex justify-content-between"><strong>Grand Total</strong><strong class="fw-num">{{ \App\Support\Format::rupiah($quotation->grand_total) }}</strong></div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Kontrol Margin</h2></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Margin Kotor Total <small class="d-block">(dari harga jual)</small></span><span class="fw-semibold">{{ rtrim(rtrim(number_format($quotation->target_margin,2),'0'),'.') }}%</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Estimasi Cost Produksi</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->estimatedCostTotal()) }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Estimasi Gross Profit</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->estimatedGrossProfit()) }}</span></div>
            <div class="d-flex justify-content-between"><span class="text-muted-2">Estimasi Margin</span><strong>{{ number_format($quotation->estimatedGrossMarginPercent(), 2) }}%</strong></div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Relasi Pipeline</h2></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Lead</span><span class="fw-semibold">{{ $quotation->lead?->code ?: ($quotation->designRequest?->lead?->code ?: '—') }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Design Request</span><span class="fw-semibold">{{ $quotation->designRequest?->code ?: '—' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Customer Master</span><span class="fw-semibold">{{ $quotation->customer?->name ?: '—' }}</span></div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Info Approval</h2></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Status</span><span class="fw-semibold">{{ $quotation->statusLabel() }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Diajukan</span><span class="fw-semibold">{{ $quotation->submitted_for_approval_at?->format('d M Y H:i') ?: '—' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Approved By</span><span class="fw-semibold">{{ $quotation->approvedBy?->name ?: '—' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Approved At</span><span class="fw-semibold">{{ $quotation->approved_at?->format('d M Y H:i') ?: '—' }}</span></div>
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
