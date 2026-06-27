@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('content')
<x-page-header title="Dashboard Sales Admin" subtitle="Monitoring pra lead, distribusi, dan performa tim sales">
    <a href="{{ route('admin.pra-leads.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Pra Lead Baru</a>
</x-page-header>

<div class="stat-grid">
    <x-stat-card icon="bi-percent" color="primary" label="Total Pra Lead" :value="$stats['pra_leads']" />
    <x-stat-card icon="bi-send" color="info" label="Menunggu Acceptance" :value="$stats['waiting']" />
    <x-stat-card icon="bi-people" color="success" label="Leads Aktif" :value="$stats['leads_aktif']" />
    <x-stat-card icon="bi-folder" color="warning" label="Project Aktif" :value="$stats['project_aktif']" />
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card-r">
            <div class="card-head"><h2>Distribusi Pra Lead per Sales</h2></div>
            <div style="height:260px"><canvas id="distChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-r">
            <div class="card-head"><h2>Sumber Pra Lead</h2></div>
            <div style="height:260px"><canvas id="sourceChart"></canvas></div>
        </div>
    </div>
</div>

<div class="card-r mt-3">
    <div class="card-head"><h2>Pra Lead Terbaru</h2><a href="{{ route('admin.pra-leads.index') }}" class="small">Lihat semua</a></div>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Instansi</th><th>PIC</th><th>Sumber</th><th>Sales</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($recentPraLeads as $pl)
                <tr>
                    <td class="fw-semibold">{{ $pl->instansi }}</td>
                    <td>{{ $pl->pic_name }}</td>
                    <td><span class="pill">{{ ucfirst($pl->source) }}</span></td>
                    <td>{{ $pl->assignedSales?->name ?? '—' }}</td>
                    <td><x-status-badge :status="$pl->status" /></td>
                </tr>
            @empty
                <tr><td colspan="5"><x-empty text="Belum ada pra lead." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    robustChart('distChart','bar',
        @json($distribution->pluck('assignedSales.name')),
        @json($distribution->pluck('total')),
        '#1d6fe0');
    robustChart('sourceChart','doughnut',
        @json($praLeadBySource->keys()),
        @json($praLeadBySource->values()),
        ['#1d6fe0','#1aa563','#e8a33d','#e0524a','#3aa0c9','#8e64d6']);
</script>
@endpush
@endsection
