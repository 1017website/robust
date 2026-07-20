@extends('layouts.app')
@section('title', 'Project Monitoring Dashboard')

@push('styles')
<style>
    .monitoring-table { min-width: 2500px; font-size: .76rem; }
    .monitoring-table th { white-space: nowrap; vertical-align: middle; text-align: center; }
    .monitoring-table td { vertical-align: middle; }
    .monitoring-table .sticky-project { position: sticky; left: 0; z-index: 2; background: #fff; min-width: 190px; }
    .monitoring-table thead .sticky-project { z-index: 3; background: #eef3f9; }
    .monitor-check { font-size: 1.05rem; }
    .monitor-kpi { border: 1px solid #e7ebf1; border-radius: 12px; background: #fff; padding: 1rem; height: 100%; }
    .monitor-kpi small { color: #667085; font-weight: 600; }
    .monitor-kpi strong { display: block; margin-top: .2rem; font-size: 1.35rem; }
</style>
@endpush

@section('content')
<x-page-header title="Project Monitoring Dashboard" subtitle="Monitoring administrasi, invoice, produksi, QC, dan delivery dalam satu tampilan." />

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-2"><div class="monitor-kpi"><small>Total Project</small><strong>{{ $stats['projects'] }}</strong></div></div>
    <div class="col-6 col-lg-2"><div class="monitor-kpi"><small>Project Aktif</small><strong>{{ $stats['active'] }}</strong></div></div>
    <div class="col-6 col-lg-2"><div class="monitor-kpi"><small>Produksi Selesai</small><strong>{{ $stats['production_finished'] }}</strong></div></div>
    <div class="col-6 col-lg-2"><div class="monitor-kpi"><small>QC Selesai</small><strong>{{ $stats['qc_complete'] }}</strong></div></div>
    <div class="col-6 col-lg-2"><div class="monitor-kpi"><small>DO/BA Kembali</small><strong>{{ $stats['delivery_complete'] }}</strong></div></div>
    <div class="col-6 col-lg-2"><div class="monitor-kpi"><small>Belum Tertagih</small><strong>{{ \App\Support\Format::rupiahShort($stats['receivable']) }}</strong></div></div>
</div>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari project, customer, lokasi...">
        <select name="status" class="form-select"><option value="">Semua Status</option>@foreach(\App\Models\Project::statuses() as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
        <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>

    <div class="alert alert-info py-2 small"><i class="bi bi-arrows me-1"></i>Geser tabel ke kanan untuk melihat termin invoice dan checklist proses seperti pada spreadsheet monitoring.</div>
    <div class="table-wrap">
        <table class="table-r monitoring-table">
            <thead><tr>
                <th class="sticky-project">Project</th><th>Customer</th><th>Lokasi</th><th>Target / Kontrak</th><th>Nilai Subtotal</th><th>PPN</th><th>Install</th>
                @for($i = 1; $i <= 3; $i++)<th>INV {{ $i }}</th><th>Jatuh Tempo {{ $i }}</th>@endfor
                <th>Total Bayar</th><th>Saldo</th><th>Production</th><th>QC</th><th>DO/BA Keluar</th><th>DO/BA Kembali</th><th>Comment / Follow-up</th><th>PIC</th><th>Detail</th>
            </tr></thead>
            <tbody>
            @forelse($projects as $project)
                @php
                    $invoice = $project->quotation?->purchaseOrderRequest?->invoice;
                    $terms = $invoice?->terms ?? collect();
                    $workflow = $project->workflow;
                @endphp
                <tr>
                    <td class="sticky-project"><div class="fw-bold">{{ $project->code }}</div><div>{{ $project->name }}</div></td>
                    <td>{{ $project->customer?->name ?? '-' }}</td><td>{{ $project->location ?: '-' }}</td><td>{{ $project->target_date?->format('d-m-y') ?? '-' }}</td>
                    <td class="fw-num">{{ \App\Support\Format::rupiah($project->project_value, false) }}</td><td class="fw-num">{{ \App\Support\Format::rupiah($project->tax_amount, false) }}</td><td class="fw-num">{{ \App\Support\Format::rupiah($invoice?->installation_amount ?? 0, false) }}</td>
                    @for($i = 0; $i < 3; $i++)
                        @php($term = $terms->get($i))
                        <td class="fw-num">{{ $term ? \App\Support\Format::rupiah($term->amount, false) : '-' }}</td><td>{{ $term?->due_date?->format('d-m-y') ?? '-' }}</td>
                    @endfor
                    <td class="fw-num">{{ \App\Support\Format::rupiah($invoice?->paid_total ?? 0, false) }}</td><td class="fw-num">{{ \App\Support\Format::rupiah($invoice?->balance() ?? $project->total_value, false) }}</td>
                    <td><x-status-badge :status="$workflow?->production_status ?? 'stock'" :label="\App\Models\ProjectWorkflow::productionStatuses()[$workflow?->production_status ?? 'stock']" /></td>
                    <td class="text-center monitor-check"><i class="bi {{ $workflow?->qc_completed ? 'bi-check-square-fill text-success' : 'bi-square text-muted' }}"></i></td>
                    <td class="text-center monitor-check"><i class="bi {{ $workflow?->delivery_out_completed ? 'bi-check-square-fill text-success' : 'bi-square text-muted' }}"></i></td>
                    <td class="text-center monitor-check"><i class="bi {{ $workflow?->delivery_returned_completed ? 'bi-check-square-fill text-success' : 'bi-square text-muted' }}"></i></td>
                    <td style="max-width:240px;white-space:normal">{{ $project->note ?: '-' }}</td><td>{{ $project->projectManager?->name ?? '-' }}</td>
                    <td><a class="btn btn-sm btn-soft" href="{{ route('project-workspace.show', $project) }}">Buka</a></td>
                </tr>
            @empty
                <tr><td colspan="20"><x-empty text="Belum ada project untuk dimonitor." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $projects->links() }}</div>
</div>
@endsection
