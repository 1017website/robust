@extends('layouts.app')
@section('title', 'Monitoring Penawaran')
@section('content')
<x-page-header title="Monitoring Penawaran" subtitle="Informasi penawaran yang dibuat oleh setiap sales—tanpa proses approval." />

<div class="stat-grid">
    <x-stat-card label="Dibuat Hari Ini" :value="$stats['today']" icon="bi-file-earmark-plus" color="info" />
    <x-stat-card label="Bulan Ini" :value="$stats['month']" icon="bi-calendar-check" color="primary" />
    <x-stat-card label="Siap Dikirim" :value="$stats['ready']" icon="bi-send-check" color="success" />
    <x-stat-card label="Sales Aktif" :value="$stats['sales']" icon="bi-people" color="warning" />
</div>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode, customer, proyek, atau sales...">
        <select name="status" class="form-select"><option value="">Semua Status</option>@foreach(\App\Models\Quotation::statuses() as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>
    <div class="table-wrap"><table class="table-r"><thead><tr><th>Kode</th><th>Customer</th><th>Proyek</th><th>Dibuat oleh Sales</th><th>Waktu Dibuat</th><th>Jenis</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($quotations as $quotation)
        <tr><td class="fw-semibold">{{ $quotation->code }}</td><td>{{ $quotation->customer_name }}</td><td>{{ $quotation->project_name }}</td><td>{{ $quotation->sales?->name ?? '—' }}</td><td>{{ $quotation->created_at?->format('d M Y H:i') }}</td><td>{{ $quotation->isUploaded() ? 'Upload file' : 'Dibuat di sistem' }}</td><td><x-status-badge :status="$quotation->status" :label="$quotation->statusLabel()" /></td><td><a href="{{ route('spv.quotation-approvals.show',$quotation) }}" class="btn btn-sm btn-soft">Lihat Catatan</a></td></tr>
    @empty
        <tr><td colspan="8"><x-empty text="Belum ada penawaran yang dibuat sales." /></td></tr>
    @endforelse
    </tbody></table></div>
    <div class="mt-3">{{ $quotations->links() }}</div>
</div>
@endsection
