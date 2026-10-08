@extends('layouts.app')
@section('title', 'Request Process')
@section('content')
@php
    $previewUrl = fn($id) => route('drafter.projects.index', array_merge(request()->query(), ['project' => $id])).'#project-detail';
    $workRole = auth()->user()->role;
    $workView = match ($workRole) {
        'production' => ['label' => 'Produksi', 'description' => 'Pantau status produksi, progres pekerjaan, deadline, dan dokumen produksi.', 'tab' => 'production', 'action' => 'Buka Produksi'],
        'qc' => ['label' => 'QC', 'description' => 'Pantau QC Produksi dan QC Pemasangan, checklist pemeriksaan, serta deadline proyek.', 'tab' => 'qc', 'action' => 'Buka Pemeriksaan QC'],
        'delivery' => ['label' => 'Delivery', 'description' => 'Pantau jadwal pengiriman, status Delivery, DO/BA, dan bukti penerimaan customer.', 'tab' => 'delivery', 'action' => 'Buka Delivery / DO'],
        default => null,
    };
@endphp
<div class="drafter-ui">
    <div class="drafter-page-head">
        <div><h1 class="page-title mb-1">Request Process @if($workView) · {{ $workView['label'] }}@endif</h1><div class="page-subtitle">{{ $workView['description'] ?? 'Pantau request process produksi, deadline, progress dan dokumen pendukung.' }}</div></div>
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
                <form class="drafter-filter flex-wrap" method="GET"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari project, customer, kode..."><select class="form-select" name="status"><option value="">Semua Status</option>@foreach(\App\Models\Project::statuses() as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>@endforeach</select><label class="d-flex align-items-center gap-2 small"><input type="checkbox" name="deadline" value="1" @checked(request()->boolean('deadline'))> Deadline H-3 / terlambat</label><button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button></form>
                <div class="table-wrap">
                    <table class="drafter-table">
                        <thead><tr><th>Kode</th><th>Project</th><th>Customer</th><th>Status Project</th><th>Deadline</th><th>{{ $workView ? 'Pekerjaan '.$workView['label'] : 'Progress' }}</th></tr></thead>
                        <tbody>
                        @forelse($projects as $project)
                            <tr class="{{ $selectedProject && $selectedProject->id === $project->id ? 'selected' : '' }}" data-detail-href="{{ $previewUrl($project->id) }}" tabindex="0" role="link" aria-label="Tampilkan preview project">
                                <td class="fw-bold">{{ $project->code }}</td>
                                <td>{{ $project->name }}</td>
                                <td>{{ $project->customer?->name ?? '—' }}</td>
                                <td><x-status-badge :status="$project->status" :label="\App\Models\Project::statuses()[$project->status] ?? $project->status" /></td>
                                <td>{{ $project->target_date?->translatedFormat('d M Y') ?? '—' }}<x-project-deadline :project="$project" /></td>
                                <td style="min-width:180px">@include('projects._role-progress', ['progressProject' => $project])</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty :text="$workView ? 'Belum ada Request Process yang siap ditangani tim '.$workView['label'].'.' : 'Belum ada request process produksi.'" /></td></tr>
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
                <div class="info-card"><h6>Informasi Project</h6><div class="detail-grid"><div><small>Customer</small><strong>{{ $selectedProject->customer?->name ?? '—' }}</strong></div><div><small>Project Manager</small><strong>{{ $selectedProject->projectManager?->name ?? '—' }}</strong></div><div><small>Tanggal Mulai</small><strong>{{ $selectedProject->start_date?->translatedFormat('d M Y') ?? '—' }}</strong></div><div><small>Target Selesai</small><strong>{{ $selectedProject->target_date?->translatedFormat('d M Y') ?? '—' }}</strong></div></div></div>
                <div class="info-card">
                    <h6>{{ $workView ? 'Pekerjaan '.$workView['label'] : 'Progress Pekerjaan' }}</h6>
                    @include('projects._role-progress', ['progressProject' => $selectedProject])
                    <x-project-deadline :project="$selectedProject" />
                    <p class="mt-3 mb-0 text-muted-2">{{ $selectedProject->scope_of_work ?: 'Belum ada ruang lingkup pekerjaan.' }}</p>
                    @if($workView)
                        <a href="{{ route('project-workspace.show', $selectedProject) }}#{{ $workView['tab'] }}" class="btn btn-primary w-100 mt-3">{{ $workView['action'] }}</a>
                        <a href="{{ route('project-workspace.show', $selectedProject) }}#project-info" class="btn btn-soft w-100 mt-2">Informasi Project</a>
                    @else
                        <a href="{{ route('project-workspace.show', $selectedProject) }}" class="btn btn-primary w-100 mt-3">Buka Project Workspace</a>
                    @endif
                    <a href="{{ route('project-workspace.show', $selectedProject) }}#workflow-history" class="btn btn-soft w-100 mt-2">Riwayat Pekerjaan</a>
                </div>
                <div class="info-card">
                    <h6>Pembaruan Terakhir</h6>
                    @if($latestWorkEntry)
                        <div class="fw-semibold">{{ $latestWorkEntry->meta['stage'] ?? $latestWorkEntry->description }}</div>
                        <div class="small mt-1">{{ $latestWorkEntry->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB · {{ $latestWorkEntry->user?->name ?? 'Pengguna tidak tersedia' }}</div>
                        <p class="small mt-2 mb-0 text-break">{{ $latestWorkEntry->meta['after']['note'] ?? $latestWorkEntry->meta['after']['notes'] ?? 'Tanpa catatan.' }}</p>
                    @else
                        <p class="small mb-0">Belum ada riwayat pembaruan. Lihat status pekerjaan di Informasi Project.</p>
                    @endif
                </div>
            @else
                <x-empty text="Pilih project untuk melihat detail." />
            @endif
        </aside>
    </div>
</div>
@endsection
