@extends('layouts.app')
@section('title', 'Projects')
@section('content')
@php($previewUrl = fn($id) => route('drafter.projects.index', array_merge(request()->query(), ['project' => $id])).'#project-detail')
<div class="drafter-ui">
    <div class="drafter-page-head">
        <div><h1 class="page-title mb-1">Projects</h1><div class="page-subtitle">Pantau project produksi, deadline, progress dan dokumen pendukung.</div></div>
    </div>
    <div class="drafter-shell">
        <main class="drafter-main">
            <div class="drafter-stat-grid six">
                <div class="drafter-stat"><div class="ico blue"><i class="bi bi-folder"></i></div><div><div class="label">Project Aktif</div><div class="value">{{ $stats['aktif'] }}</div></div></div>
                <div class="drafter-stat"><div class="ico amber"><i class="bi bi-calendar-check"></i></div><div><div class="label">Planning</div><div class="value">{{ $stats['planning'] }}</div></div></div>
                <div class="drafter-stat"><div class="ico green"><i class="bi bi-building-gear"></i></div><div><div class="label">Ongoing</div><div class="value">{{ $stats['ongoing'] }}</div></div></div>
                <div class="drafter-stat"><div class="ico purple"><i class="bi bi-brush"></i></div><div><div class="label">Finishing</div><div class="value">{{ $stats['finishing'] }}</div></div></div>
                <div class="drafter-stat"><div class="ico green"><i class="bi bi-check-circle"></i></div><div><div class="label">Selesai</div><div class="value">{{ $stats['done'] }}</div></div></div>
                <div class="drafter-stat"><div class="ico red"><i class="bi bi-exclamation-triangle"></i></div><div><div class="label">Overdue</div><div class="value">{{ $stats['overdue'] }}</div></div></div>
            </div>
            <div class="card-r">
                <form class="drafter-filter" method="GET"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari project, customer, kode..."><select class="form-select" name="status"><option value="">Semua Status</option>@foreach(\App\Models\Project::statuses() as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>@endforeach</select><button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button></form>
                <div class="table-wrap">
                    <table class="drafter-table">
                        <thead><tr><th>Kode</th><th>Project</th><th>Customer</th><th>Status</th><th>Deadline</th><th>Progress</th><th>Nilai</th></tr></thead>
                        <tbody>
                        @forelse($projects as $project)
                            <tr class="{{ $selectedProject && $selectedProject->id === $project->id ? 'selected' : '' }}" data-detail-href="{{ $previewUrl($project->id) }}" tabindex="0" role="link" aria-label="Tampilkan preview project">
                                <td class="fw-bold">{{ $project->code }}</td>
                                <td>{{ $project->name }}</td>
                                <td>{{ $project->customer?->name ?? '—' }}</td>
                                <td><x-status-badge :status="$project->status" :label="\App\Models\Project::statuses()[$project->status] ?? $project->status" /></td>
                                <td>{{ $project->target_date?->translatedFormat('d M Y') ?? '—' }}</td>
                                <td style="min-width:150px"><div class="sales-progress"><span style="width:{{ $project->progress }}%"></span></div><small>{{ $project->progress }}%</small></td>
                                <td>{{ \App\Support\Format::rupiahShort($project->total_value) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty text="Belum ada project produksi." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $projects->links() }}</div>
            </div>
        </main>
        <aside class="drafter-detail" id="project-detail">
            @if($selectedProject)
                <div class="detail-top"><div><h2>{{ $selectedProject->name }}</h2><div class="text-muted-2">{{ $selectedProject->code }}</div></div><x-status-badge :status="$selectedProject->status" /></div>
                <div class="info-card"><h6>Informasi Project</h6><div class="detail-grid"><div><small>Customer</small><strong>{{ $selectedProject->customer?->name ?? '—' }}</strong></div><div><small>Project Manager</small><strong>{{ $selectedProject->projectManager?->name ?? '—' }}</strong></div><div><small>Tanggal Mulai</small><strong>{{ $selectedProject->start_date?->translatedFormat('d M Y') ?? '—' }}</strong></div><div><small>Target Selesai</small><strong>{{ $selectedProject->target_date?->translatedFormat('d M Y') ?? '—' }}</strong></div><div><small>Nilai Project</small><strong>{{ \App\Support\Format::rupiahShort($selectedProject->total_value) }}</strong></div></div></div>
                <div class="info-card"><h6>Progress Pekerjaan</h6><div class="fs-3 fw-black">{{ $selectedProject->workflow?->completionPercent() ?? 0 }}%</div><div class="sales-progress mt-2"><span style="width:{{ $selectedProject->workflow?->completionPercent() ?? 0 }}%"></span></div><p class="mt-3 mb-0 text-muted-2">{{ $selectedProject->scope_of_work ?: 'Belum ada ruang lingkup pekerjaan.' }}</p><a href="{{ route('project-workspace.show', $selectedProject) }}" class="btn btn-primary w-100 mt-3">Buka Project Workspace</a></div>
            @else
                <x-empty text="Pilih project untuk melihat detail." />
            @endif
        </aside>
    </div>
</div>
@endsection
