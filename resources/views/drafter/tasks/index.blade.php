@extends('layouts.app')
@section('title', 'Tasks')
@section('content')
@php($statusText = fn($s) => \App\Models\DesignRequest::statuses()[$s] ?? \Illuminate\Support\Str::headline($s))
<div class="drafter-ui">
    <div class="drafter-page-head"><div><h1 class="page-title mb-1">Tasks</h1><div class="page-subtitle">Daftar pekerjaan drafter/produksi berdasarkan Design Request yang ditugaskan.</div></div></div>
    <div class="drafter-stat-grid four">
        <div class="drafter-stat"><div class="ico blue"><i class="bi bi-list-task"></i></div><div><div class="label">To Do / Progress</div><div class="value">{{ $stats['todo'] }}</div></div></div>
        <div class="drafter-stat"><div class="ico purple"><i class="bi bi-eye"></i></div><div><div class="label">Review</div><div class="value">{{ $stats['review'] }}</div></div></div>
        <div class="drafter-stat"><div class="ico green"><i class="bi bi-check-circle"></i></div><div><div class="label">Completed</div><div class="value">{{ $stats['completed'] }}</div></div></div>
        <div class="drafter-stat"><div class="ico red"><i class="bi bi-exclamation-triangle"></i></div><div><div class="label">Overdue</div><div class="value">{{ $stats['overdue'] }}</div></div></div>
    </div>
    <div class="card-r">
        <form class="drafter-filter" method="GET"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari task, customer, DR..."><select class="form-select" name="status"><option value="">Semua Status</option>@foreach(\App\Models\DesignRequest::statuses() as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>@endforeach</select><button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button></form>
        <div class="table-wrap"><table class="drafter-table"><thead><tr><th>Task</th><th>Customer</th><th>Project</th><th>Deadline</th><th>Status</th><th>Progress</th><th>Aksi</th></tr></thead><tbody>@forelse($tasks as $task)<tr><td class="fw-bold">{{ $task->code }}</td><td>{{ $task->customer_name }}</td><td>{{ $task->project_name }}</td><td>{{ $task->deadline?->translatedFormat('d M Y') ?? '—' }}</td><td><x-status-badge :status="$task->status" :label="$statusText($task->status)" /></td><td style="min-width:160px"><div class="sales-progress"><span style="width:{{ $task->progress }}%"></span></div><small>{{ $task->progress }}%</small></td><td><a class="btn btn-primary btn-sm" href="{{ route('drafter.design-requests.show', $task) }}">Kerjakan</a></td></tr>@empty<tr><td colspan="7"><x-empty text="Tidak ada task." /></td></tr>@endforelse</tbody></table></div>
        <div class="mt-3">{{ $tasks->links() }}</div>
    </div>
</div>
@endsection
