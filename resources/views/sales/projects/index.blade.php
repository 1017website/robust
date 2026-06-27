@extends('layouts.app')
@section('title', 'Projects')
@section('content')
<x-page-header title="Projects" subtitle="Project hasil penawaran yang menang" />
<div class="card-r">
    <form class="filter-bar" method="GET">
        <select name="status" class="form-select">
            <option value="">Semua Status</option>
            @foreach(\App\Models\Project::statuses() as $k=>$v)<option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>@endforeach
        </select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Kode</th><th>Project</th><th>Customer</th><th>Nilai</th><th>Target</th><th>Progress</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($projects as $p)
                <tr>
                    <td class="fw-semibold">{{ $p->code }}</td>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->customer?->name ?? '—' }}</td>
                    <td class="fw-num">{{ \App\Support\Format::rupiahShort($p->total_value) }}</td>
                    <td>{{ $p->target_date?->format('d M Y') ?? '—' }}</td>
                    <td style="min-width:110px"><div class="prog"><span style="width:{{ $p->progress }}%"></span></div></td>
                    <td><x-status-badge :status="$p->status" /></td>
                    <td><a href="{{ route('sales.projects.show',$p) }}" class="btn btn-sm btn-soft">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty text="Belum ada project." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $projects->links() }}</div>
</div>
@endsection
