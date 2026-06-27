@extends('layouts.app')
@section('title', 'Customers')
@section('content')
<x-page-header title="Customers" subtitle="Database customer dan pipeline">
    <a href="{{ route('sales.customers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Customer Baru</a>
</x-page-header>

<div class="stat-grid">
    <x-stat-card icon="bi-people" color="primary" label="Total" :value="$stats['total']" />
    <x-stat-card icon="bi-search" color="info" label="Identify" :value="$stats['identify']" />
    <x-stat-card icon="bi-arrow-up-right" color="warning" label="Approaching" :value="$stats['approaching']" />
    <x-stat-card icon="bi-trophy" color="success" label="Won/Closing" :value="$stats['won']" />
</div>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama / email...">
        <select name="status" class="form-select">
            <option value="">Semua Stage</option>
            @foreach(\App\Models\Customer::stages() as $k=>$v)<option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>@endforeach
        </select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Customer</th><th>Kategori</th><th>PIC</th><th>Stage</th><th>Probability</th><th>Sales</th><th></th></tr></thead>
            <tbody>
            @forelse($customers as $c)
                <tr>
                    <td class="fw-semibold">{{ $c->name }}<div class="small text-muted-2">{{ $c->city }}</div></td>
                    <td><span class="pill">{{ $c->category ?? '—' }}</span></td>
                    <td>{{ $c->primaryPic?->name ?? '—' }}</td>
                    <td><x-status-badge :status="$c->pipeline_stage" /></td>
                    <td style="min-width:120px"><div class="prog"><span style="width:{{ $c->probability }}%"></span></div><small class="text-muted-2">{{ $c->probability }}%</small></td>
                    <td>{{ $c->sales?->name ?? '—' }}</td>
                    <td><a href="{{ route('sales.customers.show',$c) }}" class="btn btn-sm btn-soft">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty text="Belum ada customer." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $customers->links() }}</div>
</div>
@endsection
