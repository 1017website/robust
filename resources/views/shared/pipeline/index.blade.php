@extends('layouts.app')
@section('title', 'Monitoring Pipeline')
@section('content')
<x-page-header title="Monitoring Pipeline" subtitle="Pantauan end-to-end dari Pra Lead sampai Request PO Accurate" />

<div class="row g-3 mb-3">
    @foreach($cards as $card)
        <div class="col-md-6 col-xl-2">
            @if($card['route'] !== '#')
                <a href="{{ $card['route'] }}" class="text-decoration-none text-reset">
            @endif
            <div class="stat-card h-100">
                <div class="stat-icon"><i class="bi {{ $card['icon'] }}"></i></div>
                <div class="stat-label">{{ $card['label'] }}</div>
                <div class="stat-value">{{ $card['count'] }}</div>
            </div>
            @if($card['route'] !== '#')
                </a>
            @endif
        </div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card-r"><div class="card-head"><h2>SLA Design Overdue</h2></div><div class="display-6 fw-semibold">{{ $sla['design_overdue'] }}</div><div class="text-muted-2 small">Design request melewati deadline dan belum completed.</div></div></div>
    <div class="col-md-4"><div class="card-r"><div class="card-head"><h2>SLA Approval Overdue</h2></div><div class="display-6 fw-semibold">{{ $sla['approval_overdue'] }}</div><div class="text-muted-2 small">Penawaran menunggu SPV lebih dari 2 hari.</div></div></div>
    <div class="col-md-4"><div class="card-r"><div class="card-head"><h2>SLA Request PO Overdue</h2></div><div class="display-6 fw-semibold">{{ $sla['po_overdue'] }}</div><div class="text-muted-2 small">Request PO belum selesai lebih dari 3 hari.</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-r">
            <div class="card-head"><h2>Pipeline Lead</h2></div>
            @forelse($leadPipeline as $stage => $total)
                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">{{ str($stage ?: 'unknown')->headline() }}</span><strong>{{ $total }}</strong></div>
            @empty
                <x-empty text="Belum ada data lead." />
            @endforelse
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-r">
            <div class="card-head"><h2>Design Request Berjalan</h2></div>
            <div class="table-wrap">
                <table class="table-r">
                    <thead><tr><th>Kode</th><th>Customer</th><th>Project</th><th>Sales</th><th>Deadline</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($designPipeline as $dr)
                        <tr>
                            <td class="fw-semibold">{{ $dr->code }}</td>
                            <td>{{ $dr->customer_name }}</td>
                            <td>{{ $dr->project_name }}</td>
                            <td>{{ $dr->sales?->name ?: '—' }}</td>
                            <td>{{ $dr->deadline?->format('d M Y') ?: '—' }}</td>
                            <td><x-status-badge :status="$dr->status" :label="\App\Models\DesignRequest::statuses()[$dr->status] ?? $dr->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty text="Tidak ada design request berjalan." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="card-r">
            <div class="card-head"><h2>Penawaran Aktif</h2></div>
            <div class="table-wrap">
                <table class="table-r">
                    <thead><tr><th>Kode</th><th>Customer</th><th>Project</th><th>Sales</th><th>Nilai</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($quotationPipeline as $q)
                        <tr>
                            <td class="fw-semibold">{{ $q->code }}</td>
                            <td>{{ $q->customer_name }}</td>
                            <td>{{ $q->project_name }}</td>
                            <td>{{ $q->sales?->name ?: '—' }}</td>
                            <td class="fw-num">{{ \App\Support\Format::rupiah($q->grand_total) }}</td>
                            <td><x-status-badge :status="$q->status" :label="$q->statusLabel()" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty text="Belum ada penawaran aktif." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-r">
            <div class="card-head"><h2>Request PO Open</h2></div>
            @forelse($requestPoPipeline as $po)
                <div class="border-bottom py-2">
                    <div class="d-flex justify-content-between gap-2"><strong>{{ $po->code }}</strong><x-status-badge :status="$po->status" :label="\App\Models\PurchaseOrderRequest::statuses()[$po->status] ?? $po->status" /></div>
                    <div class="small text-muted-2">{{ $po->quotation?->customer_name }} · {{ $po->quotation?->sales?->name }}</div>
                    <div class="small">Checklist: {{ $po->checklistProgress()['done'] }}/{{ $po->checklistProgress()['total'] }}</div>
                </div>
            @empty
                <x-empty text="Tidak ada Request PO open." />
            @endforelse
        </div>
    </div>
</div>
@endsection
