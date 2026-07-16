@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<div class="drafter-ui">
    <div class="drafter-page-head">
        <div><h1 class="page-title mb-1">Reports</h1><div class="page-subtitle">Pantau kinerja produksi dan progress project.</div></div>
        <div class="page-actions"><input class="form-control" value="Bulan Ini ({{ now()->translatedFormat('F Y') }})" readonly><a href="{{ route('drafter.reports.index',['export'=>'csv']) }}" class="btn btn-soft"><i class="bi bi-download me-1"></i>Export CSV</a></div>
    </div>
    <div class="drafter-stat-grid six">
        <div class="drafter-stat"><div class="ico blue"><i class="bi bi-folder"></i></div><div><div class="label">Total Project Aktif</div><div class="value">{{ $summary['active_projects'] }}</div><div class="sub">Data saat ini</div></div></div>
        <div class="drafter-stat"><div class="ico green"><i class="bi bi-clipboard-check"></i></div><div><div class="label">Project Selesai</div><div class="value">{{ $summary['completed_projects'] }}</div><div class="sub">Data saat ini</div></div></div>
        <div class="drafter-stat"><div class="ico purple"><i class="bi bi-ui-checks"></i></div><div><div class="label">Task Selesai</div><div class="value">{{ $summary['completed_tasks'] }}</div><div class="sub">Data saat ini</div></div></div>
        <div class="drafter-stat"><div class="ico orange"><i class="bi bi-arrow-repeat"></i></div><div><div class="label">Revisi</div><div class="value">{{ $summary['revisi'] }}</div><div class="sub">Perlu ditindaklanjuti</div></div></div>
        <div class="drafter-stat"><div class="ico red"><i class="bi bi-exclamation-triangle"></i></div><div><div class="label">Overdue Task</div><div class="value">{{ $summary['overdue'] }}</div><div class="sub">Perlu ditindaklanjuti</div></div></div>
        <div class="drafter-stat"><div class="ico teal"><i class="bi bi-bullseye"></i></div><div><div class="label">On-Time Completion</div><div class="value">{{ $summary['on_time'] }}%</div><div class="sub">Berdasarkan task selesai</div></div></div>
    </div>

    <div class="drafter-report-grid">
        <div class="card-r"><div class="card-head"><h2>Project Status Summary</h2></div><div class="donut-wrap"><div style="height:220px;width:220px"><canvas id="statusDonut"></canvas></div><div class="sales-metric-list flex-grow-1">@foreach($statusSummary as $status=>$count)<div class="rowx"><span>{{ \Illuminate\Support\Str::headline($status) }}</span><strong>{{ $count }}</strong></div>@endforeach<div class="rowx"><span>Total</span><strong>{{ $statusSummary->sum() }}</strong></div></div></div></div>
        <div class="card-r"><div class="card-head"><h2>Progress Project (Completed) per Bulan</h2></div><div style="height:250px"><canvas id="completedBar"></canvas></div></div>
        <div class="card-r"><div class="card-head"><h2>Task Performance</h2></div><div class="table-wrap"><table class="drafter-table compact"><thead><tr><th>Status</th><th>Jumlah</th><th>Persentase</th></tr></thead><tbody>@php($total=max(1,$statusSummary->sum()))@foreach($statusSummary as $status=>$count)<tr><td>{{ \Illuminate\Support\Str::headline($status) }}</td><td>{{ $count }}</td><td>{{ round($count/$total*100) }}%</td></tr>@endforeach<tr><td class="fw-bold">Total</td><td class="fw-bold">{{ $total }}</td><td class="fw-bold">100%</td></tr></tbody></table></div></div>
        <div class="card-r"><div class="card-head"><h2>Revisi Report</h2></div><div class="split-metric"><div class="ico blue"><i class="bi bi-arrow-repeat"></i></div><div><div class="label">Total Revisi Bulan Ini</div><div class="value">{{ $summary['revisi'] }}</div><div class="label">Revisi</div></div></div></div>
        <div class="card-r"><div class="card-head"><h2>Productivity by PIC</h2></div><div class="progress-list">@forelse($productivity as $row)<div class="progress-row"><span>{{ $row->productionPic?->name ?? 'PIC' }}</span><div class="sales-progress"><span style="width:{{ min(100,$row->total) }}%"></span></div><strong>{{ $row->total }}</strong></div>@empty<div class="small text-muted-2">Belum ada produktivitas.</div>@endforelse</div></div>
        <div class="card-r"><div class="card-head"><h2>Deadline Performance</h2></div><div class="gauge-card"><div class="gauge-num">{{ $summary['on_time'] }}%</div><div>Project selesai tepat waktu</div><div class="sales-progress mt-3"><span style="width:{{ $summary['on_time'] }}%"></span></div></div></div>
        <div class="card-r"><div class="card-head"><h2>Upcoming Deadlines</h2></div><div class="table-wrap"><table class="drafter-table compact"><thead><tr><th>Project</th><th>Tahap</th><th>Deadline</th></tr></thead><tbody>@forelse($upcomingDeadlines as $d)<tr><td>{{ $d->code }}</td><td>{{ \Illuminate\Support\Str::headline($d->status) }}</td><td class="{{ $d->deadline && $d->deadline->isPast() ? 'text-danger fw-bold' : '' }}">{{ $d->deadline?->translatedFormat('d M Y') ?? '—' }}</td></tr>@empty<tr><td colspan="3"><x-empty text="Tidak ada deadline." /></td></tr>@endforelse</tbody></table></div><a href="{{ route('drafter.tasks.index') }}" class="btn btn-link w-100 fw-bold">Lihat Semua <i class="bi bi-arrow-right"></i></a></div>
        <div class="card-r wide-2"><div class="card-head"><h2>Most Active Projects</h2></div><div class="progress-list">@forelse($activeProjects as $project)<div class="progress-row"><span>{{ $project->code }} - {{ $project->name }}</span><div class="sales-progress"><span style="width:{{ $project->progress }}%"></span></div><strong>{{ $project->progress }}%</strong></div>@empty<div class="small text-muted-2">Belum ada project aktif.</div>@endforelse</div></div>
        <div class="card-r"><div class="card-head"><h2>Pekerjaan per Tahap</h2></div><div class="category-tile-grid"><div><i class="bi bi-inbox"></i><strong>Assigned</strong><span>{{ $statusSummary['assigned'] ?? 0 }}</span></div><div><i class="bi bi-pencil-square"></i><strong>Drafting</strong><span>{{ $statusSummary['drafting'] ?? 0 }}</span></div><div><i class="bi bi-check2-circle"></i><strong>Review</strong><span>{{ $statusSummary['review'] ?? 0 }}</span></div><div><i class="bi bi-file-earmark-check"></i><strong>Completed</strong><span>{{ $statusSummary['completed'] ?? 0 }}</span></div></div></div>
    </div>
    <div class="small text-muted-2 mt-3"><i class="bi bi-info-circle me-1"></i>Data diperbarui terakhir: {{ now()->translatedFormat('d M Y, H:i') }} WIB</div>
</div>
@endsection
@push('scripts')
<script>
(function(){
    const status=@json($statusSummary); robustChart('statusDonut','doughnut',Object.keys(status),Object.values(status),['#9ca3af','#0b5cff','#f59e0b','#8b5cf6','#10a561']);
    const monthly=@json($monthlyCompleted); const labels=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']; const values=[]; for(let i=1;i<=12;i++){values.push(monthly[i]?.total||0)} robustChart('completedBar','bar',labels,values,'#0b5cff');
})();
</script>
@endpush
