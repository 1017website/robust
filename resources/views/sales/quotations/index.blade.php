@extends('layouts.app')
@section('title', 'Penawaran')
@section('content')
<x-page-header title="Penawaran" subtitle="Kelola quotation untuk customer">
    <a href="{{ route('sales.quotations.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Penawaran Baru</a>
</x-page-header>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode / customer / proyek...">
        <select name="status" class="form-select">
            <option value="">Semua Status</option>
            @foreach(\App\Models\Quotation::statuses() as $k=>$v)<option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>@endforeach
        </select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Kode</th><th>Customer</th><th>Proyek</th><th>Berlaku s/d</th><th>Grand Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($quotations as $q)
                <tr>
                    <td class="fw-semibold">{{ $q->code }}</td>
                    <td>{{ $q->customer_name }}</td>
                    <td>{{ $q->project_name }}</td>
                    <td>{{ $q->valid_until?->format('d M Y') ?? '—' }}</td>
                    <td class="fw-num fw-semibold">{{ \App\Support\Format::rupiah($q->grand_total) }}</td>
                    <td><x-status-badge :status="$q->status" /></td>
                    <td><a href="{{ route('sales.quotations.show',$q) }}" class="btn btn-sm btn-soft">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty text="Belum ada penawaran." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $quotations->links() }}</div>
</div>
@endsection
