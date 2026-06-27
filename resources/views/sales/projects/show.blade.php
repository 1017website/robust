@extends('layouts.app')
@section('title', 'Detail Project')
@section('content')
<x-page-header :title="$project->name" :subtitle="$project->code.' · '.($project->customer?->name ?? '')">
    <x-status-badge :status="$project->status" />
</x-page-header>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-r">
            <div class="card-head"><h2>Informasi Project</h2></div>
            <div class="row g-3">
                <div class="col-md-6"><div class="small text-muted-2">Project Manager</div><div class="fw-semibold">{{ $project->projectManager?->name ?? '—' }}</div></div>
                <div class="col-md-6"><div class="small text-muted-2">Penawaran</div><div class="fw-semibold">{{ $project->quotation?->code ?? '—' }}</div></div>
                <div class="col-md-6"><div class="small text-muted-2">Mulai</div><div class="fw-semibold">{{ $project->start_date?->format('d M Y') }}</div></div>
                <div class="col-md-6"><div class="small text-muted-2">Target</div><div class="fw-semibold">{{ $project->target_date?->format('d M Y') }}</div></div>
                <div class="col-12"><div class="small text-muted-2">Scope of Work</div><div>{{ $project->scope_of_work ?? '—' }}</div></div>
            </div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Termin Pembayaran</h2></div>
            <div class="table-wrap">
                <table class="table-r"><thead><tr><th>Termin</th><th>Persen</th><th>Nilai</th><th>Jatuh Tempo</th><th>Status</th></tr></thead><tbody>
                @forelse($project->terms as $t)
                    <tr><td class="fw-semibold">{{ $t->name }}</td><td>{{ rtrim(rtrim(number_format($t->percentage,2),'0'),'.') }}%</td><td class="fw-num">{{ \App\Support\Format::rupiah($t->amount) }}</td><td>{{ $t->due_date?->format('d M Y') ?? '—' }}</td><td><x-status-badge :status="$t->status" /></td></tr>
                @empty
                    <tr><td colspan="5"><x-empty text="Belum ada termin." /></td></tr>
                @endforelse
                </tbody></table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-r">
            <div class="card-head"><h2>Nilai Project</h2></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Nilai Project</span><span class="fw-num">{{ \App\Support\Format::rupiah($project->project_value) }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">PPN</span><span class="fw-num">{{ \App\Support\Format::rupiah($project->tax_amount) }}</span></div>
            <hr>
            <div class="d-flex justify-content-between"><strong>Total</strong><strong class="fw-num">{{ \App\Support\Format::rupiah($project->total_value) }}</strong></div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Progress</h2></div>
            <div class="prog mb-2"><span style="width:{{ $project->progress }}%"></span></div>
            <div class="small text-muted-2">{{ $project->progress }}% selesai</div>
        </div>
    </div>
</div>
@endsection
