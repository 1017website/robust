@extends('layouts.app')
@section('title', 'Design Request')
@section('content')
@php
    $statusText = fn($s) => \App\Models\DesignRequest::statuses()[$s] ?? \Illuminate\Support\Str::headline($s);
    $selected = $selected ?? $designRequests->first();
@endphp
<div class="drafter-ui">
    <div class="drafter-page-head">
        <div>
            <h1 class="page-title mb-1">Design Request</h1>
            <div class="page-subtitle">Kelola permintaan desain, drawing, BOQ dan costing dari tim sales.</div>
        </div>
    </div>

    <div class="drafter-shell">
        <main class="drafter-main">
            <div class="drafter-stat-grid five">
                <div class="drafter-stat"><div class="ico blue"><i class="bi bi-pencil-square"></i></div><div><div class="label">Request Baru</div><div class="value">{{ $stats['baru'] }}</div><div class="sub">Data saat ini</div></div></div>
                <div class="drafter-stat"><div class="ico orange"><i class="bi bi-shield-check"></i></div><div><div class="label">Drafting</div><div class="value">{{ $stats['drafting'] }}</div><div class="sub">Data saat ini</div></div></div>
                <div class="drafter-stat"><div class="ico purple"><i class="bi bi-hourglass-split"></i></div><div><div class="label">Review</div><div class="value">{{ $stats['review'] }}</div><div class="sub">Perlu ditindaklanjuti</div></div></div>
                <div class="drafter-stat"><div class="ico green"><i class="bi bi-check-circle"></i></div><div><div class="label">Completed</div><div class="value">{{ $stats['completed'] }}</div><div class="sub">Data saat ini</div></div></div>
                <div class="drafter-stat"><div class="ico red"><i class="bi bi-shield-exclamation"></i></div><div><div class="label">Terlambat</div><div class="value">{{ $stats['terlambat'] }}</div><div class="sub">Perlu ditindaklanjuti</div></div></div>
            </div>

            <div class="card-r">
                <form method="GET" class="drafter-filter">
                    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari DR, customer, project...">
                    <select class="form-select" name="status"><option value="">Semua Status</option>@foreach(\App\Models\DesignRequest::statuses() as $key => $label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select>
                    <select class="form-select" name="sales_id"><option value="">Semua Sales</option>@foreach($salesUsers as $sales)<option value="{{ $sales->id }}" @selected(request('sales_id')==$sales->id)>{{ $sales->name }}</option>@endforeach</select>
                    <select class="form-select" name="priority"><option value="">Semua Prioritas</option><option value="high" @selected(request('priority')==='high')>High</option><option value="medium" @selected(request('priority')==='medium')>Medium</option><option value="low" @selected(request('priority')==='low')>Low</option></select>
                    <input type="date" class="form-control" name="date" value="{{ request('date') }}">
                    <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button>
                </form>
                <div class="table-wrap">
                    <table class="drafter-table">
                        <thead><tr><th>DR No</th><th>Customer</th><th>Project</th><th>Sales</th><th>Status</th><th>Prioritas</th><th>Deadline</th><th>Tanggal Masuk</th><th></th></tr></thead>
                        <tbody>
                        @forelse($designRequests as $dr)
                            @php($late = $dr->deadline && $dr->deadline->isPast() && !in_array($dr->status, ['completed','rejected']))
                            <tr class="{{ $selected && $selected->id === $dr->id ? 'selected' : '' }}">
                                <td class="fw-bold"><a href="{{ route('drafter.design-requests.show', $dr) }}">{{ $dr->code }}</a></td>
                                <td>{{ $dr->customer_name }}</td>
                                <td>{{ $dr->project_name }}</td>
                                <td>{{ $dr->sales?->name ?? '—' }}</td>
                                <td><x-status-badge :status="$dr->status" :label="$statusText($dr->status)" /></td>
                                <td><x-status-badge :status="$dr->priority" :label="ucfirst($dr->priority)" /></td>
                                <td class="{{ $late ? 'text-danger fw-bold' : '' }}">{{ $dr->deadline?->translatedFormat('d M Y') ?? '—' }} @if($late)<br><small>({{ abs(today()->diffInDays($dr->deadline, false)) }} hari terlambat)</small>@endif</td>
                                <td>{{ $dr->request_date?->translatedFormat('d M Y') ?? $dr->created_at?->translatedFormat('d M Y') }}</td>
                                <td><a class="btn btn-soft btn-sm" href="{{ route('drafter.design-requests.show', $dr) }}"><i class="bi bi-chevron-right"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9"><x-empty text="Tidak ada design request." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $designRequests->links() }}</div>
            </div>
        </main>

        <aside class="drafter-detail">
            @if($selected)
                <div class="detail-top">
                    <div><h2>{{ $selected->code }}</h2><div class="text-muted-2">{{ $selected->customer_name }}</div></div>
                    <x-status-badge :status="$selected->status" :label="$statusText($selected->status)" />
                </div>
                <div class="detail-tabs"><span class="active">Detail</span><span>Riwayat</span><span>Revisi</span><span>Dokumen</span></div>
                <div class="info-card">
                    <h6>Informasi Umum</h6>
                    <div class="detail-grid two">
                        <div><small>Customer</small><strong>{{ $selected->customer_name }}</strong></div>
                        <div><small>Tanggal Masuk</small><strong>{{ $selected->request_date?->translatedFormat('d M Y') ?? $selected->created_at?->translatedFormat('d M Y') }}</strong></div>
                        <div><small>Project</small><strong>{{ $selected->project_name }}</strong></div>
                        <div><small>Deadline</small><strong>{{ $selected->deadline?->translatedFormat('d M Y') ?? '—' }}</strong></div>
                        <div><small>Sales</small><strong>{{ $selected->sales?->name ?? '—' }}</strong></div>
                        <div><small>Prioritas</small><x-status-badge :status="$selected->priority" :label="ucfirst($selected->priority)" /></div>
                        <div><small>PIC Customer</small><strong>{{ $selected->pic_name ?? '—' }}</strong></div>
                    </div>
                </div>
                <div class="side-card-grid">
                    <div class="info-card"><h6>Kebutuhan Customer</h6><ul class="clean-list">@forelse(($selected->scope_checklist ?? []) as $scope)<li>{{ $scope }}</li>@empty<li>{{ $selected->detail_need ?: 'Belum ada kebutuhan.' }}</li>@endforelse</ul></div>
                    <div class="info-card"><h6>Output yang Diminta</h6><ul class="check-list">@forelse(($selected->outputs ?? []) as $out)<li>{{ \Illuminate\Support\Str::headline($out) }}</li>@empty<li>Layout / BOQ</li>@endforelse</ul></div>
                    <div class="info-card"><h6>Status Progress</h6><div class="progress-stack">@foreach(['assigned'=>'New','drafting'=>'Drafting','costing'=>'Costing','review'=>'Review','completed'=>'Completed'] as $key=>$label)<div class="{{ array_search($selected->status, array_keys(['assigned'=>1,'drafting'=>1,'costing'=>1,'review'=>1,'completed'=>1])) >= array_search($key, array_keys(['assigned'=>1,'drafting'=>1,'costing'=>1,'review'=>1,'completed'=>1])) ? 'done' : '' }}"><i></i><span>{{ $label }}</span></div>@endforeach</div></div>
                    <div class="info-card"><h6>Lampiran dari Sales</h6>@forelse($selected->documents->take(3) as $doc)<div class="doc-row"><i class="bi bi-file-earmark-pdf"></i><span>{{ $doc->name }}<small>{{ $doc->humanSize() }}</small></span></div>@empty<div class="small text-muted-2">Belum ada lampiran.</div>@endforelse</div>
                    <div class="info-card note-card"><h6>Catatan dari Sales</h6><p>{{ $selected->extra_note ?: $selected->production_note ?: 'Belum ada catatan.' }}</p></div>
                </div>
                <div class="info-card">
                    <h6>Aksi Cepat</h6>
                    <div class="quick-actions-grid">
                        <a href="{{ route('drafter.design-requests.show', $selected) }}#upload" class="quick-action"><i class="bi bi-cloud-upload"></i><span>Upload Drawing</span></a>
                        <a href="{{ route('drafter.design-requests.show', $selected) }}#boq" class="quick-action"><i class="bi bi-file-earmark-spreadsheet"></i><span>Upload BOQ</span></a>
                        <a href="{{ route('drafter.design-requests.show', $selected) }}#progress" class="quick-action"><i class="bi bi-graph-up-arrow"></i><span>Update Progress</span></a>
                        <a href="{{ route('drafter.design-requests.show', $selected) }}#feedback" class="quick-action"><i class="bi bi-pencil-square"></i><span>Tambah Revisi</span></a>
                    </div>
                </div>
            @else
                <x-empty text="Pilih design request untuk melihat detail." />
            @endif
        </aside>
    </div>
</div>
@endsection
