@extends('layouts.app')
@section('title', 'Design Request')
@section('content')
@php
    $statusClass = fn($s) => match($s) {
        'completed' => 'st-green',
        'drafting' => 'st-blue',
        'costing' => 'st-yellow',
        'review' => 'st-purple',
        'assigned' => 'st-yellow',
        'rejected' => 'st-red',
        'draft' => 'st-gray',
        default => 'st-gray',
    };
    $priorityClass = fn($p) => match($p) {
        'high' => 'st-red',
        'medium' => 'st-yellow',
        'low' => 'st-green',
        default => 'st-gray',
    };
@endphp

<div class="sales-ui">
    <div class="sales-page-head align-items-center">
        <div>
            <h1 class="page-title mb-1">Design Request</h1>
            <div class="page-subtitle">Kelola permintaan desain, assignment drafter, progress produksi, dan generate penawaran.</div>
        </div>
        <a href="{{ route('sales.design-requests.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Design Request Baru</a>
    </div>

    <div class="sales-grid-4">
        <div class="sales-stat"><div class="ico sblue"><i class="bi bi-clipboard2-check"></i></div><div><div class="label">Total Request</div><div class="value">{{ $stats['total'] }}</div><div class="sub">Semua request</div></div></div>
        <div class="sales-stat"><div class="ico sorange"><i class="bi bi-person-workspace"></i></div><div><div class="label">Assigned</div><div class="value">{{ $stats['waiting'] }}</div><div class="sub">Menunggu dikerjakan</div></div></div>
        <div class="sales-stat"><div class="ico spurple"><i class="bi bi-arrow-repeat"></i></div><div><div class="label">Proses</div><div class="value">{{ $stats['progress'] }}</div><div class="sub">Drafting / costing / review</div></div></div>
        <div class="sales-stat"><div class="ico sgreen"><i class="bi bi-check-circle"></i></div><div><div class="label">Completed</div><div class="value">{{ $stats['completed'] }}</div><div class="sub">Siap penawaran</div></div></div>
    </div>

    <div class="card-r p-0 overflow-hidden">
        <form class="sales-filter-row p-3 pb-0" method="GET" style="grid-template-columns:minmax(240px,1fr) 150px 150px 190px {{ auth()->user()->isSales() ? '' : '190px' }} auto">
            <div class="sales-search">
                <i class="bi bi-search"></i>
                <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari DR, customer, PIC, proyek...">
            </div>
            <select name="status" class="form-select">
                <option value="">Semua Status</option>
                @foreach(\App\Models\DesignRequest::statuses() as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="priority" class="form-select">
                <option value="">Semua Prioritas</option>
                <option value="high" @selected(request('priority') === 'high')>High</option>
                <option value="medium" @selected(request('priority') === 'medium')>Medium</option>
                <option value="low" @selected(request('priority') === 'low')>Low</option>
            </select>
            <select name="production_pic_id" class="form-select">
                <option value="">Semua Drafter</option>
                @foreach($drafters as $drafter)
                    <option value="{{ $drafter->id }}" @selected((string) request('production_pic_id') === (string) $drafter->id)>{{ $drafter->name }}</option>
                @endforeach
            </select>
            @unless(auth()->user()->isSales())
                <select name="sales_id" class="form-select">
                    <option value="">Semua Sales</option>
                    @foreach($salesUsers as $sales)
                        <option value="{{ $sales->id }}" @selected((string) request('sales_id') === (string) $sales->id)>{{ $sales->name }}</option>
                    @endforeach
                </select>
            @endunless
            <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button>
        </form>

        <div class="table-wrap">
            <table class="sales-table">
                <thead>
                <tr>
                    <th style="width:110px">Kode</th>
                    <th>Customer / Project</th>
                    <th>Scope Kebutuhan</th>
                    <th>Sales</th>
                    <th>Drafter</th>
                    <th>Status</th>
                    <th>Prioritas</th>
                    <th>Deadline</th>
                    <th>Progress</th>
                    <th class="text-end">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($designRequests as $dr)
                    @php($late = $dr->deadline && $dr->deadline->isPast() && !in_array($dr->status, ['completed','rejected'], true))
                    <tr>
                        <td class="fw-bold"><a href="{{ route('sales.design-requests.show', $dr) }}">{{ $dr->code }}</a></td>
                        <td>
                            <div class="fw-bold">{{ $dr->customer_name }}</div>
                            <div class="small text-muted-2">{{ $dr->project_name }}</div>
                            @if($dr->customer_id)
                                <span class="status-soft st-blue mt-1"><i class="bi bi-link-45deg"></i>Customer terhubung</span>
                            @endif
                        </td>
                        <td class="text-truncate-cell">{{ implode(', ', $dr->scope_checklist ?? []) ?: ($dr->detail_need ?: '—') }}</td>
                        <td>{{ $dr->sales?->name ?? '—' }}</td>
                        <td>
                            @if($dr->productionPic)
                                <div class="d-flex align-items-center gap-2"><div class="mini-avatar">{{ strtoupper(substr($dr->productionPic->name,0,1)) }}</div><div><strong>{{ $dr->productionPic->name }}</strong><div class="small text-muted-2">Drafter</div></div></div>
                            @else
                                <span class="text-danger small fw-semibold">Belum dipilih</span>
                            @endif
                        </td>
                        <td><span class="status-soft {{ $statusClass($dr->status) }}">{{ \App\Models\DesignRequest::statuses()[$dr->status] ?? \Illuminate\Support\Str::headline($dr->status) }}</span></td>
                        <td><span class="status-soft {{ $priorityClass($dr->priority) }}">{{ ucfirst($dr->priority ?: 'medium') }}</span></td>
                        <td class="{{ $late ? 'text-danger fw-bold' : '' }}">
                            {{ $dr->deadline?->translatedFormat('d M Y') ?: '—' }}
                            @if($late)<div class="small">Terlambat</div>@endif
                        </td>
                        <td style="min-width:110px"><strong>{{ $dr->progress }}%</strong><div class="sales-progress mt-1"><span style="width:{{ $dr->progress }}%"></span></div></td>
                        <td>
                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                <a href="{{ route('sales.design-requests.show', $dr) }}" class="btn btn-sm btn-soft"><i class="bi bi-eye me-1"></i>Detail</a>
                                @if($dr->status === 'completed')
                                    <a href="{{ route('sales.quotations.create', ['dr' => $dr->id]) }}" class="btn btn-sm btn-primary"><i class="bi bi-file-earmark-text me-1"></i>Penawaran</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10"><x-empty text="Belum ada design request." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <span class="small text-muted-2">
                @if($designRequests->total())
                    Menampilkan {{ $designRequests->firstItem() }} - {{ $designRequests->lastItem() }} dari {{ $designRequests->total() }} request
                @else
                    Menampilkan 0 request
                @endif
            </span>
            {{ $designRequests->links() }}
        </div>
    </div>
</div>
@endsection
