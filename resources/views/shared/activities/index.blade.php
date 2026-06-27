@extends('layouts.app')
@section('title', 'Activities')
@section('content')
<x-page-header title="Activities" subtitle="Aktivitas sales dan pipeline customer">
    <a href="{{ route('activities.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Aktivitas Baru</a>
</x-page-header>

<div class="stat-grid">
    <x-stat-card icon="bi-calendar-day" color="primary" label="Hari Ini" :value="$stats['today']" />
    <x-stat-card icon="bi-hourglass" color="warning" label="Pending" :value="$stats['pending']" />
    <x-stat-card icon="bi-check2-circle" color="success" label="Selesai Hari Ini" :value="$stats['completed_today']" />
    <x-stat-card icon="bi-exclamation-triangle" color="danger" label="Terlambat" :value="$stats['overdue']" />
</div>

<div class="card-r">
    <div class="card-head"><h2>Pipeline Customer</h2></div>
    <div class="pipeline">
        @foreach($pipeline as $stage => $data)
            <div class="pipe-col">
                <h4>{{ $data['label'] }} <span>{{ $data['customers']->count() }}</span></h4>
                @forelse($data['customers']->take(5) as $cust)
                    <div class="pipe-card"><div class="t">{{ $cust->name }}</div><div class="small text-muted-2">{{ $cust->probability }}% · {{ $cust->category }}</div></div>
                @empty
                    <div class="small text-muted-2">—</div>
                @endforelse
            </div>
        @endforeach
    </div>
</div>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <select name="type" class="form-select"><option value="">Semua Tipe</option>@foreach(\App\Models\Activity::types() as $k=>$v)<option value="{{ $k }}" @selected(request('type')==$k)>{{ $v }}</option>@endforeach</select>
        <select name="status" class="form-select"><option value="">Semua Status</option>@foreach(['scheduled','in_progress','completed','pending','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Tanggal</th><th>Aktivitas</th><th>Tipe</th><th>Customer</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($activities as $act)
                <tr>
                    <td>{{ $act->activity_date->format('d M Y') }}<div class="small text-muted-2">{{ $act->activity_time ? \Illuminate\Support\Carbon::parse($act->activity_time)->format('H:i') : '' }}</div></td>
                    <td class="fw-semibold">{{ $act->title }}</td>
                    <td><span class="pill">{{ \App\Models\Activity::types()[$act->type] ?? $act->type }}</span></td>
                    <td>{{ $act->customer?->name ?? '—' }}</td>
                    <td><x-status-badge :status="$act->status" /></td>
                    <td>
                        @if($act->status !== 'completed')
                        <form method="POST" action="{{ route('activities.status',$act) }}">@csrf @method('PUT')<input type="hidden" name="status" value="completed"><button class="btn btn-sm btn-soft text-success"><i class="bi bi-check-lg"></i></button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty text="Belum ada aktivitas." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $activities->links() }}</div>
</div>
@endsection
