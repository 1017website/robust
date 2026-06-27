@extends('layouts.app')
@section('title', 'Detail Design Request')
@section('content')
<x-page-header :title="$designRequest->project_name" :subtitle="$designRequest->code.' · '.$designRequest->customer_name">
    @if($designRequest->status === 'completed')
        <a href="{{ route('sales.quotations.create',['dr'=>$designRequest->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-file-earmark-text me-1"></i>Buat Penawaran</a>
    @endif
</x-page-header>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-r">
            <div class="card-head"><h2>Informasi Request</h2><x-status-badge :status="$designRequest->status" /></div>
            <div class="row g-3">
                <div class="col-md-6"><div class="small text-muted-2">Drafter</div><div class="fw-semibold">{{ $designRequest->productionPic?->name ?? '—' }}</div></div>
                <div class="col-md-6"><div class="small text-muted-2">Deadline</div><div class="fw-semibold">{{ $designRequest->deadline?->format('d M Y') ?? '—' }}</div></div>
                <div class="col-12"><div class="small text-muted-2">Deskripsi</div><div>{{ $designRequest->short_description }}</div></div>
                <div class="col-12"><div class="small text-muted-2">Detail Kebutuhan</div><div>{{ $designRequest->detail_need }}</div></div>
                @if($designRequest->scope_checklist)
                <div class="col-12"><div class="small text-muted-2 mb-1">Scope</div>@foreach($designRequest->scope_checklist as $s)<span class="pill me-1 mb-1">{{ $s }}</span>@endforeach</div>
                @endif
            </div>
        </div>

        @if($designRequest->items->count())
        <div class="card-r">
            <div class="card-head"><h2>Item Hasil Produksi</h2></div>
            <div class="table-wrap">
                <table class="table-r">
                    <thead><tr><th>Item</th><th>Spesifikasi</th><th>Qty</th><th>Harga Satuan</th><th>Total</th></tr></thead>
                    <tbody>
                    @foreach($designRequest->items as $it)
                        <tr><td class="fw-semibold">{{ $it->name }}</td><td class="small">{{ $it->specification }}</td><td>{{ rtrim(rtrim(number_format($it->qty,2),'0'),'.') }} {{ $it->unit }}</td><td class="fw-num">{{ \App\Support\Format::rupiah($it->unit_price) }}</td><td class="fw-num">{{ \App\Support\Format::rupiah($it->total) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
    <div class="col-lg-4">
        <div class="card-r">
            <div class="card-head"><h2>Progress</h2></div>
            <div class="prog mb-2"><span style="width:{{ $designRequest->progress }}%"></span></div>
            <div class="small text-muted-2">{{ $designRequest->progress }}% selesai</div>
        </div>
        @if($designRequest->cost_total)
        <div class="card-r">
            <div class="card-head"><h2>Estimasi Biaya</h2></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Material</span><span class="fw-num">{{ \App\Support\Format::rupiah($designRequest->cost_material) }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Produksi</span><span class="fw-num">{{ \App\Support\Format::rupiah($designRequest->cost_production) }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Instalasi</span><span class="fw-num">{{ \App\Support\Format::rupiah($designRequest->cost_installation) }}</span></div>
            <hr>
            <div class="d-flex justify-content-between"><strong>Total</strong><strong class="fw-num">{{ \App\Support\Format::rupiah($designRequest->cost_total) }}</strong></div>
        </div>
        @endif
        @if($designRequest->technical_note)
        <div class="card-r"><div class="card-head"><h2>Catatan Teknis</h2></div><p class="mb-0 small">{{ $designRequest->technical_note }}</p></div>
        @endif
    </div>
</div>
@endsection
