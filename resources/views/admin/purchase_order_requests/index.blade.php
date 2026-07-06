@extends('layouts.app')
@section('title', 'Request PO')
@section('content')
<x-page-header title="Request PO" subtitle="Monitoring request PO dari penawaran approved menuju input PO di Accurate">
    <a href="{{ route('admin.purchase-order-requests.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Request PO Baru</a>
</x-page-header>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari request / customer / no PO...">
        <select name="status" class="form-select">
            <option value="">Semua Status</option>
            @foreach(\App\Models\PurchaseOrderRequest::statuses() as $k=>$v)
                <option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>No Request</th><th>Penawaran</th><th>Customer</th><th>Project</th><th>Sales</th><th>No PO Accurate</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($requests as $r)
                <tr>
                    <td class="fw-semibold">{{ $r->code }}</td>
                    <td>{{ $r->quotation?->code ?: '—' }}</td>
                    <td>{{ $r->quotation?->customer_name ?: '—' }}</td>
                    <td>{{ $r->quotation?->project_name ?: '—' }}</td>
                    <td>{{ $r->quotation?->sales?->name ?: '—' }}</td>
                    <td class="fw-semibold">{{ $r->accurate_po_number ?: '—' }}</td>
                    <td><x-status-badge :status="$r->status" :label="\App\Models\PurchaseOrderRequest::statuses()[$r->status] ?? $r->status" /></td>
                    <td><a href="{{ route('admin.purchase-order-requests.show',$r) }}" class="btn btn-sm btn-soft">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty text="Belum ada Request PO." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $requests->links() }}</div>
</div>
@endsection
