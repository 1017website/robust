@extends('layouts.app')
@section('title', 'Dashboard Sales Admin')
@section('content')
@php
    $allPra = max(1, $stats['pra_leads'] ?? 0);
    $statusLabels = [
        'draft' => 'Draft',
        'assigned' => 'Assigned',
        'waiting_acceptance' => 'Waiting Acceptance',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
    ];
    $statusColors = ['draft'=>'#60a5fa','assigned'=>'#f59e0b','waiting_acceptance'=>'#8b5cf6','accepted'=>'#10b981','rejected'=>'#ef4444'];
@endphp
<div class="sales-admin-ui">
    <div class="sa-page-head">
        <div>
            <h1 class="page-title mb-1">Selamat pagi, {{ auth()->user()->isAdministrator() ? 'Administrator' : 'Admin Sales' }} <span>👋</span></h1>
            <div class="page-subtitle">Berikut ringkasan aktivitas dan pipeline prospek hari ini.</div>
        </div>
        <div class="page-actions">
            <span class="btn btn-soft"><i class="bi bi-calendar3 me-1"></i>Periode: {{ now()->startOfWeek()->translatedFormat('d') }} - {{ now()->endOfWeek()->translatedFormat('d M Y') }}</span>
        </div>
    </div>

    <div class="sa-stats six">
        <div class="sa-stat"><div class="sa-ico blue"><i class="bi bi-people"></i></div><div><small>Pra Leads</small><strong>{{ $stats['pra_leads'] }}</strong><span>Data saat ini</span></div></div>
        <div class="sa-stat"><div class="sa-ico orange"><i class="bi bi-send"></i></div><div><small>Assigned</small><strong>{{ $stats['assigned'] ?? 0 }}</strong><span>Data saat ini</span></div></div>
        <div class="sa-stat"><div class="sa-ico purple"><i class="bi bi-hourglass-split"></i></div><div><small>Waiting Acceptance</small><strong>{{ $stats['waiting'] }}</strong><span>Perlu ditindaklanjuti</span></div></div>
        <div class="sa-stat"><div class="sa-ico red"><i class="bi bi-x-circle"></i></div><div><small>Rejected</small><strong>{{ $stats['rejected'] ?? 0 }}</strong><span>Data saat ini</span></div></div>
        <div class="sa-stat"><div class="sa-ico green"><i class="bi bi-person-check"></i></div><div><small>Leads Aktif</small><strong>{{ $stats['leads_aktif'] }}</strong><span>Data saat ini</span></div></div>
        <div class="sa-stat"><div class="sa-ico blue"><i class="bi bi-briefcase"></i></div><div><small>Project Aktif</small><strong>{{ $stats['project_aktif'] }}</strong><span>Data saat ini</span></div></div>
    </div>

    <div class="sa-dashboard-grid">
        <section class="sa-card">
            <div class="sa-card-head"><h2>Pra Leads per Status</h2></div>
            <div class="sa-chart-donut">
                <div class="chart-box"><canvas id="saStatusChart"></canvas><div class="donut-center"><small>Total</small><b>{{ $stats['pra_leads'] }}</b></div></div>
                <div class="sa-legend-list">
                    @foreach($statusLabels as $key => $label)
                        @php($count = (int) ($praLeadByStatus[$key] ?? 0))
                        <div><i style="background:{{ $statusColors[$key] }}"></i><span>{{ $label }}</span><strong>{{ $count }} ({{ round($count / $allPra * 100) }}%)</strong></div>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('admin.pra-leads.index') }}" class="sa-link">Lihat Semua Pra Leads <i class="bi bi-arrow-right"></i></a>
        </section>

        <section class="sa-card">
            <div class="sa-card-head"><h2>Distribusi Pra Leads ke Sales</h2></div>
            <div class="sa-progress-list">
                @forelse($distribution as $row)
                    @php($maxDist = max(1, $distribution->max('total')))
                    <div class="sa-progress-row"><span>{{ $row->assignedSales?->name ?? 'Belum Sales' }}</span><div class="sa-mini-bar"><b style="width:{{ round($row->total / $maxDist * 100) }}%"></b></div><strong>{{ $row->total }}</strong></div>
                @empty
                    <x-empty text="Belum ada distribusi sales." />
                @endforelse
            </div>
            <a href="{{ route('admin.assignment.index') }}" class="sa-link">Lihat Detail Assignment <i class="bi bi-arrow-right"></i></a>
        </section>

        <section class="sa-card">
            <div class="sa-card-head"><h2>Sumber Pra Leads</h2></div>
            <div class="sa-chart-donut">
                <div class="chart-box"><canvas id="saSourceChart"></canvas><div class="donut-center"><small>Total</small><b>{{ $praLeadBySource->sum() }}</b></div></div>
                <div class="sa-legend-list">
                    @foreach($praLeadBySource as $source => $total)
                        <div><i></i><span>{{ ucfirst($source) }}</span><strong>{{ $total }} ({{ round($total / max(1,$praLeadBySource->sum()) * 100) }}%)</strong></div>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('admin.pra-leads.index') }}" class="sa-link">Lihat Semua Sumber <i class="bi bi-arrow-right"></i></a>
        </section>

        <aside class="sa-card sa-side" style="grid-row: span 2;">
            <div class="sa-card-head"><h2>Aktivitas Terbaru</h2><a href="{{ route('activities.index') }}">Lihat Semua</a></div>
            <div class="sa-activity-list">
                @forelse($recentActivities as $activity)
                    <div class="sa-activity-item">
                        <div class="sa-mini-ico green"><i class="bi bi-calendar-check"></i></div>
                        <div><strong>{{ $activity->title }}</strong><span>{{ $activity->customer?->name ?? $activity->lead?->instansi ?? '-' }}<br>Oleh {{ $activity->sales?->name ?? '-' }}</span></div>
                        <small>{{ $activity->updated_at?->isToday() ? $activity->updated_at->format('H:i') : 'Kemarin' }}</small>
                    </div>
                @empty
                    <x-empty text="Belum ada aktivitas." />
                @endforelse
            </div>
            <div class="sa-card-head mt-4"><h2>Quick Actions</h2></div>
            <div class="sa-quick-actions">
                <a href="{{ route('admin.pra-leads.index') }}#praLeadPanel"><i class="bi bi-plus-lg"></i>Tambah Pra Lead Baru</a>
                <a href="{{ route('admin.assignment.index') }}"><i class="bi bi-people"></i>Lihat Assignment Sales</a>
                <a href="{{ route('admin.pra-leads.index', ['status' => 'waiting_acceptance']) }}"><i class="bi bi-hourglass-split"></i>Lihat Waiting Acceptance</a>
                <a href="{{ route('admin.pra-leads.index') }}"><i class="bi bi-list-ul"></i>Lihat Semua Pra Leads</a>
            </div>
            <div class="sa-card-head mt-4"><h2>Reminder</h2></div>
            @forelse($upcomingActivities as $act)
                <div class="sa-reminder"><i class="bi bi-calendar3"></i><span>{{ $act->title }}<br><small>{{ $act->customer?->name ?? $act->lead?->instansi ?? '-' }} - {{ $act->activity_date?->translatedFormat('d M Y') }}</small></span></div>
            @empty
                <div class="sa-reminder"><i class="bi bi-info-circle"></i><span>Belum ada reminder terdekat.</span></div>
            @endforelse
        </aside>

        <section class="sa-card sa-wide">
            <div class="sa-card-head"><h2>Pra Leads Terbaru</h2><a href="{{ route('admin.pra-leads.index') }}">Lihat Semua</a></div>
            <div class="table-wrap">
                <table class="sa-table">
                    <thead><tr><th>No</th><th>Nama Instansi</th><th>PIC</th><th>Kebutuhan Awal</th><th>Sales Ditugaskan</th><th>Status</th><th>Tanggal Dibuat</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse($recentPraLeads as $lead)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $lead->instansi }}</strong></td>
                            <td>{{ $lead->pic_name }}</td>
                            <td>{{ $lead->lab_type ?: \Illuminate\Support\Str::limit($lead->initial_need, 30) }}</td>
                            <td>{{ $lead->assignedSales?->name ?? '-' }}</td>
                            <td><x-status-badge :status="$lead->status" /></td>
                            <td>{{ $lead->created_at?->translatedFormat('d M Y') }}<br><small>{{ $lead->created_at?->format('H:i') }}</small></td>
                            <td><a href="{{ route('admin.pra-leads.index') }}" class="btn btn-sm btn-link">...</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty text="Belum ada pra lead." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@push('scripts')
<script>
    robustChart('saStatusChart','doughnut', @json(array_values($statusLabels)), @json(collect(array_keys($statusLabels))->map(fn($k)=>(int)($praLeadByStatus[$k]??0))->values()), @json(array_values($statusColors)));
    robustChart('saSourceChart','doughnut', @json($praLeadBySource->keys()), @json($praLeadBySource->values()), ['#10b981','#0b5cff','#f59e0b','#8b5cf6','#ef4444','#14b8a6']);
</script>
@endpush
@endsection
