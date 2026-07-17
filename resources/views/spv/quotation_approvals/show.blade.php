@extends('layouts.app')
@section('title', 'Review Penawaran')
@section('content')
<x-page-header :title="'Review '.$quotation->code" :subtitle="$quotation->customer_name.' · '.$quotation->project_name">
    <a href="{{ route('spv.quotation-approvals.index') }}" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</x-page-header>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-r">
            <div class="card-head"><h2>Item Penawaran</h2><x-status-badge :status="$quotation->status" :label="$quotation->statusLabel()" /></div>
            <div class="table-wrap">
                <table class="table-r">
                    <thead><tr><th>Item</th><th>Spesifikasi</th><th>Qty</th><th>HPP</th><th>Margin</th><th>Harga Jual</th><th>Total</th></tr></thead>
                    <tbody>
                    @foreach($quotation->items as $it)
                        <tr>
                            <td class="fw-semibold">{{ $it->name }}</td>
                            <td class="small">{{ $it->specification ?: '—' }}</td>
                            <td>{{ rtrim(rtrim(number_format($it->qty,2),'0'),'.') }} {{ $it->unit }}</td>
                            <td class="fw-num">{{ \App\Support\Format::rupiah($it->cost_price) }}</td>
                            <td>{{ rtrim(rtrim(number_format($it->margin,2),'0'),'.') }}%</td>
                            <td class="fw-num">{{ \App\Support\Format::rupiah($it->unit_price) }}</td>
                            <td class="fw-num fw-semibold">{{ \App\Support\Format::rupiah($it->total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($quotation->designRequest)
            <div class="card-r">
                <div class="card-head"><h2>Estimasi Drafter / Costing</h2></div>
                <div class="row g-3 small">
                    <div class="col-md-3"><div class="text-muted-2">Material</div><div class="fw-num fw-semibold">{{ \App\Support\Format::rupiah($quotation->designRequest->cost_material ?? 0) }}</div></div>
                    <div class="col-md-3"><div class="text-muted-2">Produksi</div><div class="fw-num fw-semibold">{{ \App\Support\Format::rupiah($quotation->designRequest->cost_production ?? 0) }}</div></div>
                    <div class="col-md-3"><div class="text-muted-2">Instalasi</div><div class="fw-num fw-semibold">{{ \App\Support\Format::rupiah($quotation->designRequest->cost_installation ?? 0) }}</div></div>
                    <div class="col-md-3"><div class="text-muted-2">Total Cost</div><div class="fw-num fw-semibold">{{ \App\Support\Format::rupiah($quotation->designRequest->cost_total ?? 0) }}</div></div>
                </div>
                @if($quotation->designRequest->technical_note)
                    <hr><div class="small"><div class="text-muted-2">Catatan Teknis Drafter</div>{{ $quotation->designRequest->technical_note }}</div>
                @endif
            </div>
        @endif

        @if($quotation->internal_note || $quotation->customer_note)
            <div class="card-r">
                <div class="card-head"><h2>Catatan</h2></div>
                @if($quotation->internal_note)<div class="mb-2"><div class="text-muted-2 small">Catatan Internal</div>{{ $quotation->internal_note }}</div>@endif
                @if($quotation->customer_note)<div><div class="text-muted-2 small">Catatan Customer</div>{{ $quotation->customer_note }}</div>@endif
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

        @if(in_array($quotation->status, ['waiting_approval', 'revision', 'rejected']))
            <div class="card-r">
                <div class="card-head"><h2>Action SPV</h2></div>
                <form method="POST" action="{{ route('spv.quotation-approvals.approve', $quotation) }}" class="mb-3">
                    @csrf
                    <label class="form-label small fw-semibold">Catatan Approval</label>
                    <textarea name="approval_note" rows="2" class="form-control mb-2" placeholder="Opsional, contoh: nilai dan margin sudah sesuai."></textarea>
                    <button class="btn btn-success btn-sm"><i class="bi bi-check2-circle me-1"></i>Approve Penawaran</button>
                </form>
                <hr>
                <div class="row g-3">
                    <div class="col-md-6">
                        <form method="POST" action="{{ route('spv.quotation-approvals.revision', $quotation) }}">
                            @csrf
                            <label class="form-label small fw-semibold">Catatan Revisi *</label>
                            <textarea name="revision_note" rows="3" class="form-control mb-2" required placeholder="Contoh: diskon maksimal 3%, revisi harga item fume hood."></textarea>
                            <button class="btn btn-warning btn-sm"><i class="bi bi-pencil-square me-1"></i>Minta Revisi</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" action="{{ route('spv.quotation-approvals.reject', $quotation) }}">
                            @csrf
                            <label class="form-label small fw-semibold">Alasan Reject *</label>
                            <textarea name="rejection_note" rows="3" class="form-control mb-2" required placeholder="Contoh: scope belum lengkap / harga belum valid."></textarea>
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Reject</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <div class="col-lg-4">
        <div class="card-r">
            <div class="card-head"><h2>Ringkasan Harga</h2></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Subtotal</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->subtotal) }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Diskon</span><span class="fw-num text-danger">- {{ \App\Support\Format::rupiah($quotation->discount_amount) }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">PPN</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->tax_amount) }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Biaya Tambahan</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->additional_total) }}</span></div>
            <hr>
            <div class="d-flex justify-content-between"><strong>Grand Total</strong><strong class="fw-num">{{ \App\Support\Format::rupiah($quotation->grand_total) }}</strong></div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Kontrol Margin</h2></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Margin Total Otomatis</span><span class="fw-semibold">{{ rtrim(rtrim(number_format($quotation->target_margin,2),'0'),'.') }}%</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Estimasi Cost Drafter</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->estimatedCostTotal()) }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Estimasi Gross Profit</span><span class="fw-num">{{ \App\Support\Format::rupiah($quotation->estimatedGrossProfit()) }}</span></div>
            <div class="d-flex justify-content-between"><span class="text-muted-2">Estimasi Margin</span><strong>{{ number_format($quotation->estimatedGrossMarginPercent(), 2) }}%</strong></div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Relasi Pipeline</h2></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Lead</span><span class="fw-semibold">{{ $quotation->lead?->code ?: ($quotation->designRequest?->lead?->code ?: '—') }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Design Request</span><span class="fw-semibold">{{ $quotation->designRequest?->code ?: '—' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Sales</span><span class="fw-semibold">{{ $quotation->sales?->name ?: '—' }}</span></div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Info</h2></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Tanggal</span><span class="fw-semibold">{{ $quotation->quote_date?->format('d M Y') }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Berlaku</span><span class="fw-semibold">{{ $quotation->valid_until?->format('d M Y') }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Diajukan</span><span class="fw-semibold">{{ $quotation->submitted_for_approval_at?->format('d M Y H:i') ?: '—' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Approved By</span><span class="fw-semibold">{{ $quotation->approvedBy?->name ?: '—' }}</span></div>
        </div>
    </div>
</div>
@endsection
