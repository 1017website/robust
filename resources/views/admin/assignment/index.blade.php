@extends('layouts.app')
@section('title', 'Assignment')
@section('content')
@php
    $acceptMap = collect($acceptance)->keyBy(fn($r) => $r['sales']->id);
    $previewUrl = fn($id) => route('admin.assignment.index', array_merge(request()->query(), ['sales' => $id])).'#assignment-detail';
@endphp
<div class="sales-admin-ui">
    <div class="sa-page-head">
        <div>
            <h1 class="page-title mb-1">Assignment</h1>
            <div class="page-subtitle">Monitoring distribusi workload, ownership, dan performa sales.</div>
        </div>
        <div class="page-actions">
            <span class="btn btn-soft"><i class="bi bi-calendar3 me-1"></i>Periode: {{ now()->startOfWeek()->translatedFormat('d') }} - {{ now()->endOfWeek()->translatedFormat('d M Y') }}</span>
            <a class="btn btn-soft" href="{{ route('admin.assignment.index', ['export' => 'excel']) }}"><i class="bi bi-file-earmark-excel me-1 text-success"></i>Export Excel</a>
        </div>
    </div>

    <div class="sa-stats four">
        <div class="sa-stat"><div class="sa-ico blue"><i class="bi bi-people"></i></div><div><small>Total Sales</small><strong>{{ $stats['total_sales'] }}</strong><span>Aktif</span></div></div>
        <div class="sa-stat"><div class="sa-ico green"><i class="bi bi-person-plus"></i></div><div><small>Total Leads</small><strong>{{ $stats['total_leads'] }}</strong><span>Data saat ini</span></div></div>
        <div class="sa-stat"><div class="sa-ico orange"><i class="bi bi-briefcase"></i></div><div><small>Active Projects</small><strong>{{ $stats['active_projects'] }}</strong><span>Data saat ini</span></div></div>
        <div class="sa-stat"><div class="sa-ico purple"><i class="bi bi-check2-circle"></i></div><div><small>Acceptance Rate</small><strong>{{ $stats['acceptance_rate'] }}%</strong><span>Berdasarkan assignment</span></div></div>
    </div>

    <div class="sa-assignment-grid">
        <div class="sa-assignment-main">
            <section class="sa-card">
                <div class="sa-card-head"><h2>Workload Distribution <i class="bi bi-info-circle text-muted-2"></i></h2></div>
                <div class="table-wrap">
                    <table class="sa-table align-middle">
                        <thead><tr><th>Sales</th><th>Request Masuk<br><small>(Belum Diterima)</small></th><th>Leads Aktif</th><th>Design Request Aktif</th><th>Penawaran Aktif</th><th>Project Aktif</th><th>Total Workload</th></tr></thead>
                        <tbody>
                            @foreach($workload as $row)
                                @php($total = $row['request_masuk'] + $row['leads_aktif'] + $row['design_request'] + $row['penawaran_aktif'] + $row['project_aktif'])
                                <tr class="{{ $selectedSales && $selectedSales->id === $row['sales']->id ? 'selected' : '' }}" data-detail-href="{{ $previewUrl($row['sales']->id) }}" tabindex="0" role="link" aria-label="Tampilkan workload sales">
                                    <td><div class="sa-person"><span class="sa-avatar">{{ strtoupper(substr($row['sales']->name,0,1)) }}</span><strong>{{ $row['sales']->name }}</strong></div></td>
                                    <td class="text-warning fw-bold">{{ $row['request_masuk'] }}</td>
                                    <td><div class="sa-inline-progress"><span>{{ $row['leads_aktif'] }}</span><div><b style="width:{{ min(100,$row['leads_aktif']*5) }}%"></b></div></div></td>
                                    <td><div class="sa-inline-progress purple"><span>{{ $row['design_request'] }}</span><div><b style="width:{{ min(100,$row['design_request']*12) }}%"></b></div></div></td>
                                    <td><div class="sa-inline-progress orange"><span>{{ $row['penawaran_aktif'] }}</span><div><b style="width:{{ min(100,$row['penawaran_aktif']*18) }}%"></b></div></div></td>
                                    <td class="text-success fw-bold">{{ $row['project_aktif'] }}</td>
                                    <td><span class="sa-workload-badge">{{ $total }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="small text-muted-2 mt-3">Total workload dihitung dari total semua pipeline aktif.</div>
            </section>

            <div class="sa-two-col mt-3">
                <section class="sa-card" id="acceptance">
                    <div class="sa-card-head"><h2>Lead Acceptance Monitoring <i class="bi bi-info-circle text-muted-2"></i></h2></div>
                    <div class="table-wrap">
                        <table class="sa-table">
                            <thead><tr><th>Sales</th><th>Assigned<br><small>(dikirim)</small></th><th>Accepted<br><small>(diterima)</small></th><th>Rejected<br><small>(ditolak)</small></th><th>Acceptance Rate</th></tr></thead>
                            <tbody>
                            @foreach($acceptance as $row)
                                <tr><td class="fw-bold">{{ $row['sales']->name }}</td><td>{{ $row['assigned'] }}</td><td>{{ $row['accepted'] }}</td><td>{{ $row['rejected'] }}</td><td><span class="status-soft {{ $row['rate'] >= 90 ? 'st-green' : ($row['rate'] >= 80 ? 'st-yellow' : 'st-red') }}">{{ $row['rate'] }}%</span></td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a class="sa-link" href="{{ route('admin.assignment.index') }}#acceptance">Lihat Detail Acceptance <i class="bi bi-arrow-right"></i></a>
                </section>

                <section class="sa-card">
                    <div class="sa-card-head"><h2>Top Sales Performance (Win Rate)</h2></div>
                    <div class="small fw-semibold text-muted-2 mb-3">Berdasarkan acceptance rate</div>
                    <div class="sa-progress-list compact">
                        @foreach($acceptance->sortByDesc('rate')->take(5) as $idx => $row)
                            <div class="sa-progress-row"><span>{{ $loop->iteration <= 3 ? ['🥇','🥈','🥉'][$loop->iteration-1] : $loop->iteration }} &nbsp;{{ $row['sales']->name }}</span><div class="sa-mini-bar"><b style="width:{{ $row['rate'] }}%"></b></div><strong>{{ $row['rate'] }}%</strong></div>
                        @endforeach
                    </div>
                    <a class="sa-link" href="{{ route('reports.index') }}">Lihat Semua Performa <i class="bi bi-arrow-right"></i></a>
                </section>
            </div>

            <section class="sa-card mt-3">
                <div class="sa-card-head"><h2>Projects by Sales (Ownership)</h2></div>
                <div class="table-wrap">
                    <table class="sa-table">
                        <thead><tr><th>No</th><th>Project</th><th>Customer / Instansi</th><th>Sales PIC</th><th>Stage</th><th>Nilai Project</th><th>Target Closing</th></tr></thead>
                        <tbody>
                        @forelse($projects as $project)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $project->name }}</td>
                                <td>{{ $project->quotation?->customer?->name ?? '-' }}</td>
                                <td>{{ $project->quotation?->sales?->name ?? '-' }}</td>
                                <td><x-status-badge :status="$project->status" /></td>
                                <td>{{ \App\Support\Format::rupiahShort($project->value ?? $project->quotation?->grand_total ?? 0) }}</td>
                                <td>{{ $project->end_date?->translatedFormat('d M Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty text="Belum ada project." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                    <a class="sa-link" href="{{ route('sales.projects.index') }}">Lihat Semua Project <i class="bi bi-arrow-right"></i></a>
            </section>
        </div>

        <aside class="sa-card sa-assignment-side" id="assignment-detail">
            @if($selectedSales)
                @php($selectedAccept = $acceptMap[$selectedSales->id] ?? ['rate'=>0])
                <div class="text-center">
                    <span class="sa-profile-avatar">{{ strtoupper(substr($selectedSales->name,0,1)) }}</span>
                    <h3 class="mt-3 mb-1">{{ $selectedSales->name }}</h3>
                    <div class="text-muted-2">{{ $selectedSales->job_title ?: 'Senior Sales' }}</div>
                    <span class="status-soft st-green mt-2">Active</span>
                </div>
                @php($selWork = collect($workload)->first(fn($r) => $r['sales']->id === $selectedSales->id))
                <div class="sa-mini-stat-grid mt-4">
                    <div><strong>{{ $selWork['request_masuk'] ?? 0 }}</strong><span>Request Masuk</span></div>
                    <div><strong>{{ $selWork['leads_aktif'] ?? 0 }}</strong><span>Leads Aktif</span></div>
                    <div><strong>{{ $selWork['design_request'] ?? 0 }}</strong><span>Design Request</span></div>
                    <div><strong>{{ $selWork['penawaran_aktif'] ?? 0 }}</strong><span>Penawaran Aktif</span></div>
                    <div><strong>{{ $selWork['project_aktif'] ?? 0 }}</strong><span>Project Aktif</span></div>
                    <div><strong>{{ $selectedAccept['rate'] ?? 0 }}%</strong><span>Win Rate</span></div>
                </div>
                <hr>
            <div class="sa-card-head"><h2>Lead Terbaru</h2><a href="{{ route('sales.leads.index') }}">Lihat Semua</a></div>
                <div class="sa-mini-list">
                    @foreach($leads->where('sales_id', $selectedSales->id)->take(3) as $lead)
                        <div><span>{{ $loop->iteration }}</span><strong>{{ $lead->instansi }}</strong><small><x-status-badge :status="$lead->status" /></small></div>
                    @endforeach
                </div>
                <hr>
                <h2 class="sa-small-title">Reassign Lead / Ownership</h2>
                <form method="POST" action="{{ route('admin.assignment.reassign') }}" class="sa-reassign-form">
                    @csrf
                    <label class="form-label small">Pilih Lead</label>
                    <select name="lead_id" class="form-select mb-3" required>
                        @foreach($leads as $lead)
                            <option value="{{ $lead->id }}">{{ $lead->instansi }}</option>
                        @endforeach
                    </select>
                    <div class="row g-2 align-items-end">
                        <div class="col"><label class="form-label small">Dari</label><input class="form-control" value="{{ $selectedSales->name }}" readonly></div>
                        <div class="col-auto pb-2"><i class="bi bi-arrow-right"></i></div>
                        <div class="col"><label class="form-label small">Ke</label><select name="to_sales_id" class="form-select" required><option value="">Pilih Sales</option>@foreach($salesList as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
                    </div>
                    <button class="btn btn-primary w-100 mt-3">Reassign</button>
                </form>
                <div class="sa-note mt-3"><i class="bi bi-info-circle"></i> Pastikan koordinasi dengan sales terkait sebelum reassign lead.</div>
            @endif
        </aside>
    </div>
</div>
@endsection
