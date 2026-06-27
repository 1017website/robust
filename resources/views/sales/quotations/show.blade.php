@extends('layouts.app')
@section('title', 'Detail Penawaran')
@section('content')
<x-page-header :title="$quotation->code" :subtitle="$quotation->customer_name.' · '.$quotation->project_name">
    @if(in_array($quotation->status,['sent','negotiation','draft']))
        <form method="POST" action="{{ route('sales.quotations.won',$quotation) }}" class="d-inline">@csrf<button class="btn btn-success btn-sm"><i class="bi bi-trophy me-1"></i>Tandai Won</button></form>
        <form method="POST" action="{{ route('sales.quotations.lost',$quotation) }}" class="d-inline">@csrf<button class="btn btn-soft btn-sm text-danger">Tandai Lost</button></form>
    @endif
</x-page-header>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-r">
            <div class="card-head"><h2>Item Penawaran</h2><x-status-badge :status="$quotation->status" /></div>
            <div class="table-wrap">
                <table class="table-r">
                    <thead><tr><th>Item</th><th>Spesifikasi</th><th>Qty</th><th>Harga Satuan</th><th>Total</th></tr></thead>
                    <tbody>
                    @foreach($quotation->items as $it)
                        <tr><td class="fw-semibold">{{ $it->name }}</td><td class="small">{{ $it->specification }}</td><td>{{ rtrim(rtrim(number_format($it->qty,2),'0'),'.') }} {{ $it->unit }}</td><td class="fw-num">{{ \App\Support\Format::rupiah($it->unit_price) }}</td><td class="fw-num">{{ \App\Support\Format::rupiah($it->total) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
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
            <div class="card-head"><h2>Info</h2></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Tanggal</span><span class="fw-semibold">{{ $quotation->quote_date?->format('d M Y') }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Berlaku s/d</span><span class="fw-semibold">{{ $quotation->valid_until?->format('d M Y') }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Pengiriman</span><span class="fw-semibold">{{ ucfirst($quotation->delivery_method) }}</span></div>
        </div>
    </div>
</div>
@endsection
