@extends('layouts.app')
@section('title', 'Dashboard SPV')
@section('content')
<x-page-header title="Dashboard SPV Sales" subtitle="Monitoring penawaran yang dibuat tim sales tanpa proses approval.">
    <a href="{{ route('spv.quotation-approvals.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-eye me-1"></i>Monitoring Penawaran</a>
</x-page-header>
<div class="stat-grid">
    <x-stat-card label="Dibuat Hari Ini" :value="$stats['today']" icon="bi-file-earmark-plus" color="info" />
    <x-stat-card label="Bulan Ini" :value="$stats['month']" icon="bi-calendar-check" color="primary" />
    <x-stat-card label="Siap Dikirim" :value="$stats['ready']" icon="bi-send-check" color="success" />
    <x-stat-card label="Sales Aktif" :value="$stats['sales']" icon="bi-people" color="warning" />
</div>
<div class="card-r"><div class="card-head"><h2>Penawaran Terbaru</h2><a href="{{ route('spv.quotation-approvals.index') }}" class="btn btn-soft btn-sm">Lihat Semua</a></div><div class="table-wrap"><table class="table-r"><thead><tr><th>No</th><th>Customer</th><th>Project</th><th>Dibuat oleh Sales</th><th>Waktu</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($approvalQueue as $quotation)<tr><td class="fw-semibold">{{ $quotation->code }}</td><td>{{ $quotation->customer_name }}</td><td>{{ $quotation->project_name }}</td><td>{{ $quotation->sales?->name ?? '—' }}</td><td>{{ $quotation->created_at?->format('d M Y H:i') }}</td><td><x-status-badge :status="$quotation->status" :label="$quotation->statusLabel()" /></td><td><a href="{{ route('spv.quotation-approvals.show', $quotation) }}" class="btn btn-soft btn-sm">Lihat</a></td></tr>@empty<tr><td colspan="7"><x-empty text="Belum ada penawaran dari tim sales." /></td></tr>@endforelse
</tbody></table></div></div>
@endsection
