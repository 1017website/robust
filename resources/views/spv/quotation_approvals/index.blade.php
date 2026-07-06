@extends('layouts.app')
@section('title', 'Approval Penawaran')
@section('content')
<x-page-header title="Approval Penawaran" subtitle="Review penawaran sales sebelum PDF bisa didownload dan dikirim ke customer" />

<div class="stat-grid">
    <x-stat-card label="Menunggu Approval" :value="$stats['waiting']" icon="bi-hourglass-split" color="warning" />
    <x-stat-card label="Approved Bulan Ini" :value="$stats['approved_month']" icon="bi-check-circle" color="success" />
    <x-stat-card label="Perlu Revisi" :value="$stats['revision']" icon="bi-pencil-square" color="info" />
    <x-stat-card label="Ditolak Bulan Ini" :value="$stats['rejected_month']" icon="bi-x-circle" color="danger" />
</div>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode / customer / proyek...">
        <select name="status" class="form-select">
            <option value="">Semua Status</option>
            @foreach(['waiting_approval'=>'Menunggu Approval','revision'=>'Perlu Revisi','approved'=>'Approved','rejected'=>'Ditolak'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Kode</th><th>Customer</th><th>Proyek</th><th>Sales</th><th>Diajukan</th><th>Grand Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($quotations as $q)
                <tr>
                    <td class="fw-semibold">{{ $q->code }}</td>
                    <td>{{ $q->customer_name }}</td>
                    <td>{{ $q->project_name }}</td>
                    <td>{{ $q->sales?->name ?? '—' }}</td>
                    <td>{{ $q->submitted_for_approval_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td class="fw-num fw-semibold">{{ \App\Support\Format::rupiah($q->grand_total) }}</td>
                    <td><x-status-badge :status="$q->status" :label="$q->statusLabel()" /></td>
                    <td><a href="{{ route('spv.quotation-approvals.show',$q) }}" class="btn btn-sm btn-soft">Review</a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty text="Belum ada data approval penawaran." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $quotations->links() }}</div>
</div>
@endsection
