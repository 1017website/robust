@extends('layouts.app')
@section('title', 'Dashboard SPV')
@section('content')
<x-page-header title="Dashboard SPV Sales" subtitle="Monitoring approval penawaran dari tim sales">
    <a href="{{ route('spv.quotation-approvals.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-check2-square me-1"></i>Approval Penawaran</a>
</x-page-header>

<div class="stat-grid">
    <x-stat-card label="Menunggu Approval" :value="$stats['waiting_approval']" icon="bi-hourglass-split" color="warning" />
    <x-stat-card label="Approved Bulan Ini" :value="$stats['approved_month']" icon="bi-check-circle" color="success" />
    <x-stat-card label="Perlu Revisi" :value="$stats['revision']" icon="bi-pencil-square" color="info" />
    <x-stat-card label="Ditolak Bulan Ini" :value="$stats['rejected_month']" icon="bi-x-circle" color="danger" />
</div>

<div class="card-r">
    <div class="card-head"><h2>Antrian Approval</h2><a href="{{ route('spv.quotation-approvals.index') }}" class="btn btn-soft btn-sm">Lihat Semua</a></div>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>No</th><th>Customer</th><th>Project</th><th>Sales</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($approvalQueue as $q)
                <tr>
                    <td class="fw-semibold">{{ $q->code }}</td>
                    <td>{{ $q->customer_name }}</td>
                    <td>{{ $q->project_name }}</td>
                    <td>{{ $q->sales?->name ?? '—' }}</td>
                    <td class="fw-num fw-semibold">{{ \App\Support\Format::rupiah($q->grand_total) }}</td>
                    <td><x-status-badge :status="$q->status" :label="$q->statusLabel()" /></td>
                    <td><a href="{{ route('spv.quotation-approvals.show', $q) }}" class="btn btn-soft btn-sm">Review</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty text="Belum ada penawaran yang perlu di-review." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
