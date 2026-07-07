@extends('layouts.app')
@section('title', 'Dashboard Sales')
@section('content')
@php
    $fmtShort = fn($v) => \App\Support\Format::rupiahShort($v ?? 0);
    $targetPercent = $stats['target_percent'] ?? 0;
@endphp
<div class="sales-ui">
    <div class="sales-page-head">
        <div>
            <h1 class="page-title mb-1">Halo, {{ auth()->user()->name }}! 👋</h1>
            <div class="page-subtitle">Berikut ringkasan performa dan aktivitas hari ini.</div>
        </div>
        <div class="page-actions">
            <a href="{{ route('activities.create') }}" class="btn btn-soft btn-sm"><i class="bi bi-calendar-plus me-1"></i>Tambah Activity</a>
            <a href="{{ route('sales.leads.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Lead Baru</a>
        </div>
    </div>

    @if($requestMasuk > 0)
        <div class="alert alert-info border-0 rounded-4 d-flex align-items-center shadow-sm">
            <i class="bi bi-inbox-fill fs-5 me-2"></i>
            Ada <strong class="mx-1">{{ $requestMasuk }}</strong> request masuk menunggu respon Anda.
            <a href="{{ route('sales.request-masuk.index') }}" class="ms-auto btn btn-sm btn-primary">Tinjau Request</a>
        </div>
    @endif

    <div class="sales-grid-5">
        <div class="sales-stat up"><div class="ico sblue"><i class="bi bi-people"></i></div><div><div class="label">Leads Aktif</div><div class="value">{{ $stats['leads_aktif'] }}</div><div class="sub">↑ 18% dari bulan lalu</div></div></div>
        <div class="sales-stat up"><div class="ico sgreen"><i class="bi bi-file-earmark-check"></i></div><div><div class="label">Penawaran Aktif</div><div class="value">{{ $stats['penawaran_aktif'] }}</div><div class="sub">↑ 20% dari bulan lalu</div></div></div>
        <div class="sales-stat up"><div class="ico sorange"><i class="bi bi-briefcase"></i></div><div><div class="label">Project Berjalan</div><div class="value">{{ $stats['project_berjalan'] }}</div><div class="sub">↑ 12% dari bulan lalu</div></div></div>
        <div class="sales-stat up"><div class="ico spurple"><i class="bi bi-trophy"></i></div><div><div class="label">Deal Won (Bulan Ini)</div><div class="value">{{ $stats['deal_won'] }}</div><div class="sub">↑ 33% dari bulan lalu</div></div></div>
        <div class="sales-stat"><div class="ico steal"><i class="bi bi-bullseye"></i></div><div class="w-100"><div class="label">Target (Bulan Ini)</div><div class="value fs-4">{{ $fmtShort($stats['won_value']) }}</div><div class="sales-progress mt-2"><span style="width:{{ $targetPercent }}%"></span></div><div class="sub">Progress {{ $targetPercent }}%</div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-5">
            <div class="card-r h-100">
                <div class="card-head"><h2>Pipeline Penjualan</h2><span class="sales-chip active">Bulan Ini</span></div>
                <div class="row g-3 align-items-center">
                    <div class="col-md-6"><div class="sales-funnel">
                        <div class="funnel-step funnel-1"><div>Leads<br><small>{{ $pipeline['leads'] }}</small></div></div>
                        <div class="funnel-step funnel-2"><div>Design Request<br><small>{{ $pipeline['design_request'] }}</small></div></div>
                        <div class="funnel-step funnel-3"><div>Penawaran<br><small>{{ $pipeline['penawaran'] }}</small></div></div>
                        <div class="funnel-step funnel-4"><div>Negosiasi<br><small>{{ $pipeline['negosiasi'] }}</small></div></div>
                        <div class="funnel-step funnel-5"><div>Won<br><small>{{ $pipeline['won'] }}</small></div></div>
                    </div></div>
                    <div class="col-md-6">
                        <div class="sales-metric-list">
                            <div class="rowx"><span>Tahap</span><strong>Nilai (Rp)</strong></div>
                            <div class="rowx"><span><i class="bi bi-pencil-square text-success me-1"></i>Design Request</span><strong>{{ $fmtShort($pipelineValues['design_request']) }}</strong></div>
                            <div class="rowx"><span><i class="bi bi-file-text text-warning me-1"></i>Penawaran</span><strong>{{ $fmtShort($pipelineValues['penawaran']) }}</strong></div>
                            <div class="rowx"><span><i class="bi bi-chat-dots text-purple me-1"></i>Negosiasi</span><strong>{{ $fmtShort($pipelineValues['negosiasi']) }}</strong></div>
                            <div class="rowx"><span><i class="bi bi-trophy text-success me-1"></i>Won</span><strong>{{ $fmtShort($pipelineValues['won']) }}</strong></div>
                        </div>
                        <div class="mt-3 pt-3 border-top d-flex justify-content-between"><span>Total Pipeline Value</span><strong class="fs-5">{{ $fmtShort(array_sum($pipelineValues)) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card-r h-100">
                <div class="card-head"><h2>Performa Penjualan (6 Bulan Terakhir)</h2><span class="sales-chip">6 Bulan</span></div>
                <div style="height:285px"><canvas id="salesMonthlyChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card-r h-100">
                <div class="card-head"><h2>Leads Terbaru</h2><a href="{{ route('sales.leads.index') }}" class="small fw-bold">Lihat Semua</a></div>
                @forelse($recentLeads as $lead)
                    <a href="{{ route('sales.leads.show',$lead) }}" class="sales-row-card d-flex gap-3 align-items-center text-reset">
                        <div class="logo-avatar"><i class="bi bi-building"></i></div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-bold text-truncate">{{ $lead->instansi }}</div>
                            <div class="small text-muted-2 text-truncate">{{ $lead->pic_name }}</div>
                        </div>
                        <span class="status-soft st-blue">{{ $lead->created_at->isToday() ? 'Baru' : $lead->created_at->diffForHumans() }}</span>
                    </a>
                @empty
                    <x-empty text="Belum ada lead terbaru." />
                @endforelse
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card-r h-100">
                <div class="card-head"><h2>Ringkasan Aktivitas (Bulan Ini)</h2></div>
                <div class="donut-wrap">
                    <div style="width:150px;height:150px"><canvas id="activityDonut"></canvas></div>
                    <div class="sales-metric-list flex-grow-1">
                        @foreach(\App\Models\Activity::types() as $key => $label)
                            @if(($activitySummary[$key] ?? 0) > 0)
                                <div class="rowx"><span>{{ $label }}</span><strong>{{ $activitySummary[$key] }}</strong></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card-r h-100">
                <div class="card-head"><h2>To Do / Tindak Lanjut</h2></div>
                @forelse($todos as $todo)
                    <div class="todo-item">
                        <form method="POST" action="{{ route('activities.status',$todo) }}">@csrf @method('PUT')<input type="hidden" name="status" value="completed"><button class="btn btn-sm btn-light border"><i class="bi bi-square"></i></button></form>
                        <div><div class="fw-bold">{{ $todo->title }}</div><div class="small text-muted-2">{{ $todo->customer?->name ?? $todo->lead?->instansi ?? '-' }}</div></div>
                        <span class="status-soft {{ $todo->activity_date->isPast() && $todo->status !== 'completed' ? 'st-red' : 'st-yellow' }}">{{ $todo->activity_date->isToday() ? 'Hari ini' : $todo->activity_date->translatedFormat('d M') }}</span>
                    </div>
                @empty
                    <x-empty text="Tidak ada tindak lanjut." />
                @endforelse
                <a href="{{ route('activities.index') }}" class="btn btn-link w-100 fw-bold mt-2">Lihat Semua To Do <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-8">
            <div class="card-r">
                <div class="card-head"><h2>Aktivitas Hari Ini</h2><a href="{{ route('calendar.index') }}" class="btn btn-sm btn-soft">Lihat Calendar</a></div>
                <div class="timeline-list">
                    @forelse($todayActivities as $act)
                        <div class="timeline-item">
                            <strong>{{ $act->activity_time ? \Illuminate\Support\Carbon::parse($act->activity_time)->format('H:i') : '--:--' }}</strong>
                            <div class="d-flex gap-2 align-items-center"><span class="time-dot bg-{{ \App\Support\Format::badgeClass($act->status) }}"></span><div><div class="fw-bold">{{ $act->title }}</div><div class="small text-muted-2">{{ $act->customer?->name ?? $act->lead?->instansi ?? '-' }}</div></div></div>
                            <x-status-badge :status="$act->status" />
                        </div>
                    @empty
                        <x-empty text="Tidak ada aktivitas hari ini." />
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card-r h-100">
                <div class="card-head"><h2>Meeting Mendatang</h2><a href="{{ route('calendar.index') }}" class="small fw-bold">Lihat Semua</a></div>
                @forelse($upcomingMeetings as $act)
                    <div class="sales-row-card d-flex gap-3 align-items-center">
                        <div class="text-center status-soft st-blue d-block" style="min-width:74px">{{ $act->activity_date->translatedFormat('d M') }}<br><strong>{{ $act->activity_time ? \Illuminate\Support\Carbon::parse($act->activity_time)->format('H:i') : '-' }}</strong></div>
                        <div><div class="fw-bold">{{ $act->title }}</div><div class="small text-muted-2">{{ $act->customer?->name ?? $act->lead?->instansi ?? '-' }}</div></div>
                    </div>
                @empty
                    <x-empty text="Belum ada meeting mendatang." />
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
(function(){
    const monthly = @json($monthly);
    const labels = Object.keys(monthly);
    const pipe = labels.map(k => monthly[k].pipeline / 1000000);
    const won = labels.map(k => monthly[k].won / 1000000);
    const el = document.getElementById('salesMonthlyChart');
    if(el && window.Chart){ new Chart(el,{type:'line',data:{labels,datasets:[{label:'Nilai Pipeline (Jt)',data:pipe,borderColor:'#0b5cff',backgroundColor:'rgba(11,92,255,.08)',tension:.35,fill:true},{label:'Deal Won (Jt)',data:won,borderColor:'#10a561',backgroundColor:'rgba(16,165,97,.08)',tension:.35,fill:true}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true}}}}); }
    const act = @json($activitySummary);
    const dLabels = Object.keys(act).map(k => (k||'').replace('_',' '));
    const dData = Object.keys(act).map(k => act[k]);
    robustChart('activityDonut','doughnut',dLabels,dData,['#0b5cff','#10a561','#ff9d18','#8b5cf6','#f04444','#14b8a6']);
})();
</script>
@endpush
