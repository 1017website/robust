@extends('layouts.app')
@section('title', 'Design Request')
@section('content')
<x-page-header title="Design Request" subtitle="Permintaan desain ke tim produksi">
    <a href="{{ route('sales.design-requests.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Design Request Baru</a>
</x-page-header>

<div class="stat-grid">
    <x-stat-card icon="bi-collection" color="primary" label="Total" :value="$stats['total']" />
    <x-stat-card icon="bi-hourglass" color="info" label="Menunggu Produksi" :value="$stats['waiting']" />
    <x-stat-card icon="bi-gear" color="warning" label="Dikerjakan" :value="$stats['progress']" />
    <x-stat-card icon="bi-check2-circle" color="success" label="Completed" :value="$stats['completed']" />
</div>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari customer / proyek...">
        <select name="status" class="form-select">
            <option value="">Semua Status</option>
            @foreach(\App\Models\DesignRequest::statuses() as $k=>$v)<option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>@endforeach
        </select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Kode</th><th>Customer</th><th>Proyek</th><th>Drafter</th><th>Deadline</th><th>Progress</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($designRequests as $dr)
                <tr>
                    <td class="fw-semibold">{{ $dr->code }}</td>
                    <td>{{ $dr->customer_name }}</td>
                    <td>{{ $dr->project_name }}</td>
                    <td>{{ $dr->productionPic?->name ?? '—' }}</td>
                    <td>{{ $dr->deadline?->format('d M Y') ?? '—' }}</td>
                    <td style="min-width:110px"><div class="prog"><span style="width:{{ $dr->progress }}%"></span></div></td>
                    <td><x-status-badge :status="$dr->status" /></td>
                    <td><a href="{{ route('sales.design-requests.show',$dr) }}" class="btn btn-sm btn-soft">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty text="Belum ada design request." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $designRequests->links() }}</div>
</div>
@endsection
