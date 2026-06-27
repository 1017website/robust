@extends('layouts.app')
@section('title', 'Design Request')
@section('content')
<x-page-header title="Design Request" subtitle="Antrian permintaan desain dari sales" />

<div class="stat-grid">
    <x-stat-card icon="bi-inbox" color="info" label="Baru" :value="$stats['baru']" />
    <x-stat-card icon="bi-pencil-square" color="warning" label="Drafting" :value="$stats['drafting']" />
    <x-stat-card icon="bi-eye" color="primary" label="Review" :value="$stats['review']" />
    <x-stat-card icon="bi-exclamation-triangle" color="danger" label="Terlambat" :value="$stats['terlambat']" />
</div>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari customer / kode...">
        <select name="status" class="form-select"><option value="">Semua Status</option>@foreach(\App\Models\DesignRequest::statuses() as $k=>$v)<option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>@endforeach</select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Kode</th><th>Customer</th><th>Proyek</th><th>Sales</th><th>Deadline</th><th>Progress</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($designRequests as $dr)
                <tr>
                    <td class="fw-semibold">{{ $dr->code }}</td>
                    <td>{{ $dr->customer_name }}</td>
                    <td>{{ $dr->project_name }}</td>
                    <td>{{ $dr->sales?->name ?? '—' }}</td>
                    <td>{{ $dr->deadline?->format('d M Y') ?? '—' }}</td>
                    <td style="min-width:110px"><div class="prog"><span style="width:{{ $dr->progress }}%"></span></div></td>
                    <td><x-status-badge :status="$dr->status" /></td>
                    <td><a href="{{ route('drafter.design-requests.show',$dr) }}" class="btn btn-sm btn-primary">Kerjakan</a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty text="Tidak ada design request." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $designRequests->links() }}</div>
</div>
@endsection
