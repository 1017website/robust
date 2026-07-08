@extends('layouts.app')
@section('title', 'Dashboard Produksi / Drafter')
@section('content')
@php
    $progressRows = collect($progress ?? $progressStages ?? []);
    $revisions = $revisions ?? $revisionRequests ?? collect();
    $approvalQueue = $approvalQueue ?? $waitingSalesApproval ?? collect();
    $timeline = $timeline ?? $activityTimeline ?? collect();
    $maxProgress = max(1, (int) $progressRows->max());
    $statusText = fn($s) => \App\Models\DesignRequest::statuses()[$s] ?? \Illuminate\Support\Str::headline($s);
@endphp
<div class="drafter-ui">
    <div class="drafter-page-head">
        <div>
            <h1 class="page-title mb-1">Dashboard Produksi / Drafter</h1>
            <div class="page-subtitle">Pantau pekerjaan design, produksi, dan progress project secara real-time.</div>
        </div>
        <div class="page-actions">
            <input class="form-control" style="width:210px" value="{{ now()->startOfMonth()->translatedFormat('j M') }} - {{ now()->endOfMonth()->translatedFormat('j M Y') }}" readonly>
        </div>
    </div>

    <div class="drafter-stat-grid six">
        <div class="drafter-stat"><div class="ico purple"><i class="bi bi-file-earmark-plus"></i></div><div><div class="label">Design Request Baru</div><div class="value">{{ $stats['request_baru'] ?? 0 }}</div><div class="sub up">↗ 2 dari kemarin</div></div></div>
        <div class="drafter-stat"><div class="ico blue"><i class="bi bi-pencil-square"></i></div><div><div class="label">Drawing Dalam Progress</div><div class="value">{{ $stats['drawing_progress'] ?? $stats['drafting'] ?? 0 }}</div><div class="sub up">↗ 1 dari kemarin</div></div></div>
        <div class="drafter-stat"><div class="ico orange"><i class="bi bi-hourglass-split"></i></div><div><div class="label">Menunggu Approval</div><div class="value">{{ $stats['waiting_approval'] ?? 0 }}</div><div class="sub up">↗ 1 dari kemarin</div></div></div>
        <div class="drafter-stat"><div class="ico green"><i class="bi bi-buildings"></i></div><div><div class="label">Project Produksi Aktif</div><div class="value">{{ $stats['project_aktif'] ?? 0 }}</div><div class="sub up">↗ 3 dari kemarin</div></div></div>
        <div class="drafter-stat"><div class="ico red"><i class="bi bi-shield-exclamation"></i></div><div><div class="label">QC Pending</div><div class="value">{{ $stats['qc_pending'] ?? 0 }}</div><div class="sub up">↗ 2 dari kemarin</div></div></div>
        <div class="drafter-stat"><div class="ico amber"><i class="bi bi-calendar2-check"></i></div><div><div class="label">Deadline Hari Ini</div><div class="value">{{ $stats['deadline_today'] ?? 0 }}</div><a href="{{ route('drafter.design-requests.index') }}" class="small fw-bold">Lihat detail</a></div></div>
    </div>

    <div class="drafter-dashboard-grid">
        <section class="card-r wide-2">
            <div class="card-head"><h2>My Tasks Today</h2><a href="{{ route('drafter.tasks.index') }}" class="btn btn-soft btn-sm">Lihat Semua Tasks <i class="bi bi-arrow-right ms-1"></i></a></div>
            <div class="table-wrap">
                <table class="drafter-table">
                    <thead><tr><th>Task</th><th>Project</th><th>Tipe Pekerjaan</th><th>Deadline</th><th>Status</th><th>Prioritas</th><th></th></tr></thead>
                    <tbody>
                    @forelse($myTasks as $task)
                        @php($late = $task->deadline && $task->deadline->isPast() && !$task->deadline->isToday())
                        <tr>
                            <td class="fw-bold">{{ $task->project_name ?: $task->code }}</td>
                            <td>{{ $task->customer_name }}</td>
                            <td>{{ \Illuminate\Support\Str::headline($task->status === 'costing' ? 'BOQ / Costing' : ($task->outputs[0] ?? 'Layout')) }}</td>
                            <td class="{{ $late ? 'text-danger fw-bold' : '' }}"><i class="bi bi-calendar-event me-1"></i>{{ $task->deadline?->isToday() ? 'Hari ini' : ($task->deadline?->isTomorrow() ? 'Besok' : ($task->deadline?->translatedFormat('d M Y') ?? '—')) }}</td>
                            <td><x-status-badge :status="$task->status" :label="$statusText($task->status)" /></td>
                            <td><x-status-badge :status="$task->priority" :label="ucfirst($task->priority)" /></td>
                            <td><a class="btn btn-soft btn-sm" href="{{ route('drafter.design-requests.show', $task) }}">Buka</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty text="Tidak ada tugas aktif." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="card-r">
            <div class="card-head"><h2>Quick Access</h2></div>
            <div class="quick-list">
                <a href="{{ route('documents.index') }}" class="quick-row"><span class="quick-ico blue"><i class="bi bi-cloud-upload"></i></span><span><strong>Upload Drawing</strong><small>Upload file drawing terbaru</small></span><i class="bi bi-chevron-right"></i></a>
                <a href="{{ route('documents.index', ['category' => 'boq']) }}" class="quick-row"><span class="quick-ico green"><i class="bi bi-file-earmark-spreadsheet"></i></span><span><strong>Upload BOQ</strong><small>Upload Bill of Quantity</small></span><i class="bi bi-chevron-right"></i></a>
                <a href="{{ route('drafter.design-requests.index') }}" class="quick-row"><span class="quick-ico orange"><i class="bi bi-graph-up-arrow"></i></span><span><strong>Update Progress</strong><small>Perbarui progress project</small></span><i class="bi bi-chevron-right"></i></a>
                <a href="{{ route('drafter.tasks.index') }}" class="quick-row"><span class="quick-ico purple"><i class="bi bi-pencil-square"></i></span><span><strong>Tambah Revisi</strong><small>Buat permintaan revisi</small></span><i class="bi bi-chevron-right"></i></a>
            </div>
        </aside>

        <section class="card-r">
            <div class="card-head"><h2>Design Request Queue</h2><a href="{{ route('drafter.design-requests.index') }}" class="small fw-bold">Lihat Semua <i class="bi bi-arrow-right"></i></a></div>
            <div class="table-wrap">
                <table class="drafter-table compact">
                    <thead><tr><th>DR No</th><th>Customer</th><th>Sales</th><th>Status</th><th>Deadline</th></tr></thead>
                    <tbody>
                    @forelse($queue as $dr)
                        <tr><td class="fw-bold">{{ $dr->code }}</td><td>{{ $dr->customer_name }}</td><td>{{ $dr->sales?->name ?? '—' }}</td><td><x-status-badge :status="$dr->status" /></td><td>{{ $dr->deadline?->translatedFormat('d M Y') ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="5"><x-empty text="Antrian kosong." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <a class="btn btn-link w-100 fw-bold" href="{{ route('drafter.design-requests.index') }}">Buka Design Request <i class="bi bi-arrow-right"></i></a>
        </section>

        <section class="card-r">
            <div class="card-head"><h2>Progress Produksi (Semua Project)</h2><a href="{{ route('drafter.reports.index') }}" class="small fw-bold">Lihat Detail <i class="bi bi-arrow-right"></i></a></div>
            <div class="progress-list">
                @foreach($progressRows as $label => $count)
                    @php($pct = $maxProgress ? round($count / $maxProgress * 100) : 0)
                    <div class="progress-row"><span class="stage-dot"></span><span>{{ $label }}</span><div class="sales-progress"><span style="width:{{ $pct }}%"></span></div><strong>{{ $count }}</strong></div>
                @endforeach
            </div>
        </section>

        <section class="card-r">
            <div class="card-head"><h2>Deadline Alert</h2><a href="{{ route('drafter.tasks.index') }}" class="small fw-bold">Lihat Semua <i class="bi bi-arrow-right"></i></a></div>
            <div class="alert-list">
                @forelse($deadlineAlerts as $item)
                    @php($diff = $item->deadline ? today()->diffInDays($item->deadline, false) : null)
                    <div class="deadline-alert"><i class="bi bi-exclamation-triangle"></i><div><strong>{{ $item->project_name }} - {{ $item->customer_name }}</strong><small>{{ $statusText($item->status) }}</small></div><b>{{ $diff < 0 ? abs($diff).' Hari Terlambat' : ($diff === 0 ? 'Hari Ini' : $diff.' Hari lagi') }}</b></div>
                @empty
                    <x-empty text="Tidak ada deadline dekat." />
                @endforelse
            </div>
        </section>

        <section class="card-r">
            <div class="split-metric"><div class="ico purple"><i class="bi bi-shuffle"></i></div><div><div class="value">{{ $revisions->count() }}</div><div class="label">Project Revisi</div></div></div>
            <div class="mini-list mt-3">@forelse($revisions as $r)<div><strong>{{ $r->customer_name }}</strong><small>{{ $r->project_name }}</small><x-status-badge :status="$r->status" label="Revisi" /></div>@empty<div class="small text-muted-2">Belum ada revisi.</div>@endforelse</div>
            <a href="{{ route('drafter.tasks.index') }}" class="btn btn-link w-100 fw-bold">Lihat Semua Revisi <i class="bi bi-arrow-right"></i></a>
        </section>

        <section class="card-r">
            <div class="split-metric"><div class="ico orange"><i class="bi bi-hourglass-split"></i></div><div><div class="value">{{ $approvalQueue->count() }}</div><div class="label">Drawing Menunggu Sales</div></div></div>
            <div class="table-wrap mt-3"><table class="drafter-table compact"><thead><tr><th>Project</th><th>Tanggal Upload</th><th>Tipe</th></tr></thead><tbody>@forelse($approvalQueue as $a)<tr><td>{{ $a->customer_name }}</td><td>{{ $a->submitted_at?->isToday() ? 'Hari ini, '.$a->submitted_at->format('H:i') : $a->submitted_at?->translatedFormat('d M H:i') }}</td><td>{{ \Illuminate\Support\Str::headline($a->outputs[0] ?? 'Drawing') }}</td></tr>@empty<tr><td colspan="3"><x-empty text="Belum ada drawing menunggu sales." /></td></tr>@endforelse</tbody></table></div>
        </section>

        <section class="card-r">
            <div class="card-head"><h2>Activity Timeline</h2><a href="{{ route('drafter.calendar.index') }}" class="small fw-bold">Lihat Semua <i class="bi bi-arrow-right"></i></a></div>
            @forelse($timeline as $t)
                <div class="timeline-line"><span>{{ $t->updated_at?->format('H:i') }}</span><i></i><div><strong>{{ $statusText($t->status) }} - {{ $t->customer_name }}</strong><small>{{ $t->project_name }}</small></div></div>
            @empty
                <x-empty text="Belum ada aktivitas." />
            @endforelse
        </section>
    </div>
</div>
@endsection
