@extends('layouts.app')
@section('title', 'Design Request')
@section('content')
@php
    $selected = $selectedRequest;
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
    $previewUrl = fn($id) => route('sales.design-requests.index', array_merge(request()->query(), ['design_request' => $id])).'#design-request-detail';
@endphp

<div class="sales-ui">
    <div class="sales-page-head align-items-center">
        <div>
            <div class="sales-page-head">
                <div><h1 class="page-title mb-1">Design Request</h1><div class="page-subtitle">Kelola permintaan desain dan spesifikasi teknis ke tim produksi.</div></div>
                <a href="{{ route('sales.design-requests.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Design Request Baru</a>
            </div>
            <div class="sales-grid-4">
                <div class="sales-stat"><div class="ico sblue"><i class="bi bi-calendar2-check"></i></div><div><div class="label">Total Request</div><div class="value">{{ $stats['total'] }}</div><div class="sub">Semua Request</div></div></div>
                <div class="sales-stat"><div class="ico sorange"><i class="bi bi-clock"></i></div><div><div class="label">Menunggu Produksi</div><div class="value">{{ $stats['waiting'] }}</div><div class="sub">Menunggu assignment</div></div></div>
                <div class="sales-stat"><div class="ico spurple"><i class="bi bi-arrow-repeat"></i></div><div><div class="label">Sedang Dikerjakan</div><div class="value">{{ $stats['progress'] }}</div><div class="sub">Dalam proses</div></div></div>
                <div class="sales-stat"><div class="ico sgreen"><i class="bi bi-check-circle"></i></div><div><div class="label">Selesai</div><div class="value">{{ $stats['completed'] }}</div><div class="sub">Completed</div></div></div>
            </div>
            <div class="card-r p-0 overflow-hidden">
                <form class="sales-filter-row p-3 pb-0" method="GET">
                    <select name="status" class="form-select"><option value="">Semua Status</option>@foreach(\App\Models\DesignRequest::statuses() as $k=>$v)<option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>@endforeach</select>
                    @if(!auth()->user()->isSales())<select name="sales_id" class="form-select"><option value="">Semua Sales</option>@foreach($salesList as $sales)<option value="{{ $sales->id }}" @selected((string)request('sales_id')===(string)$sales->id)>{{ $sales->name }}</option>@endforeach</select>@endif
                    <select name="production_pic_id" class="form-select"><option value="">Semua Produksi PIC</option>@foreach($drafters as $drafter)<option value="{{ $drafter->id }}" @selected((string)request('production_pic_id')===(string)$drafter->id)>{{ $drafter->name }}</option>@endforeach</select>
                    <div class="sales-search"><i class="bi bi-search"></i><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari design request..."></div>
                    <button type="submit" class="btn btn-soft" aria-label="Filter"><i class="bi bi-funnel"></i></button>
                </form>
                <div class="table-wrap">
                    <table class="sales-table">
                        <thead><tr><th>No</th><th>Customer / Project</th><th>Kebutuhan</th><th>Status</th><th>PIC Produksi</th><th>Deadline</th><th>Progress</th><th>Terakhir Update</th><th></th></tr></thead>
                        <tbody>
                        @forelse($designRequests as $dr)
                            <tr class="{{ $selected && $selected->id === $dr->id ? 'selected' : '' }}" data-detail-href="{{ $previewUrl($dr->id) }}" tabindex="0" role="link" aria-label="Tampilkan preview design request">
                                <td class="fw-bold">{{ $dr->code }}</td>
                                <td><a class="fw-bold" href="{{ route('sales.design-requests.show',$dr) }}">{{ $dr->customer_name }}</a><div class="small text-muted-2">{{ $dr->project_name }}</div></td>
                                <td class="text-truncate-cell">{{ implode(', ', $dr->scope_checklist ?? []) ?: $dr->detail_need }}</td>
                                <td><span class="status-soft {{ $statusClass($dr->status) }}">{{ \App\Models\DesignRequest::statuses()[$dr->status] ?? $dr->status }}</span></td>
                                <td><div class="d-flex align-items-center gap-2">@if($dr->productionPic)<div class="mini-avatar">{{ strtoupper(substr($dr->productionPic->name,0,1)) }}</div><div>{{ $dr->productionPic->name }}<div class="small text-muted-2">Drafter</div></div>@else<span class="text-muted-2">Belum ditugaskan</span>@endif</div></td>
                                <td>{{ $dr->deadline?->translatedFormat('d M Y') ?: '-' }}</td>
                                <td><strong>{{ $dr->progress }}%</strong><div class="sales-progress mt-1"><span style="width:{{ $dr->progress }}%"></span></div></td>
                                <td>{{ $dr->updated_at->translatedFormat('d M Y') }}<div class="small text-muted-2">{{ $dr->updated_at->format('H:i') }}</div></td>
                                <td><a href="{{ route('sales.design-requests.show',$dr) }}" class="btn btn-sm btn-soft" aria-label="Buka detail"><i class="bi bi-chevron-right"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9"><x-empty text="Belum ada design request." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 d-flex justify-content-between"><span class="small text-muted-2">Menampilkan {{ $designRequests->firstItem() ?? 0 }} - {{ $designRequests->lastItem() ?? 0 }} dari {{ $designRequests->total() }} request</span>{{ $designRequests->links() }}</div>
            </div>
        </div>
        <aside class="sales-detail" id="design-request-detail">
            @if($selected)
                <div class="sales-detail-head"><div><h4 class="fw-black mb-0">{{ $selected->code }}</h4><div class="small text-muted-2">{{ $selected->customer_name }}</div></div><a href="{{ route('sales.design-requests.show',$selected) }}" class="btn btn-sm btn-soft" aria-label="Buka detail"><i class="bi bi-chevron-right"></i></a></div>
                <div class="sales-detail-body design-request-detail-body">
                    <span class="status-soft {{ $statusClass($selected->status) }}">{{ \App\Models\DesignRequest::statuses()[$selected->status] ?? $selected->status }}</span>
                    <div class="design-request-detail-sections">
                        <div class="info-card"><h6>Informasi Customer</h6><div class="kv"><div class="k">Customer</div><div class="v">{{ $selected->customer_name }}</div></div><div class="kv"><div class="k">PIC</div><div class="v">{{ $selected->pic_name ?: '-' }}</div></div><div class="kv"><div class="k">Sales</div><div class="v">{{ $selected->sales?->name ?? '-' }}</div></div></div>
                        <div class="info-card"><h6>Kebutuhan Customer</h6><div class="fw-bold mb-2">{{ $selected->project_name }}</div>@foreach(($selected->scope_checklist ?? []) as $item)<div class="small mb-1"><i class="bi bi-check text-success me-1"></i>{{ $item }}</div>@endforeach<div class="small mt-2">{{ $selected->detail_need }}</div></div>
                        <div class="info-card"><h6>Output yang Diminta</h6>@forelse(($selected->outputs ?? []) as $out)<div class="small mb-1"><i class="bi bi-check-square text-success me-1"></i>{{ strtoupper(str_replace('_',' ',$out)) }}</div>@empty<div class="small text-muted-2">Belum ada output yang dipilih.</div>@endforelse</div>
                        <div class="info-card"><h6>Assignment Drafter</h6><div class="d-flex gap-2 align-items-center">@if($selected->productionPic)<div class="mini-avatar">{{ strtoupper(substr($selected->productionPic->name,0,1)) }}</div><div><strong>{{ $selected->productionPic->name }}</strong><div class="small text-muted-2">Deadline {{ $selected->deadline?->translatedFormat('d M Y') }}</div></div>@else<span class="text-muted-2">Belum ditugaskan</span>@endif</div></div>
                        <div class="info-card"><h6>Progress Pekerjaan</h6><div class="d-flex align-items-center gap-3"><strong class="fs-3">{{ $selected->progress }}%</strong><div class="sales-progress flex-grow-1"><span style="width:{{ $selected->progress }}%"></span></div></div></div>
                    </div>
                    <div class="design-request-detail-actions">
                        <a href="{{ route('sales.design-requests.show',$selected) }}" class="btn btn-soft"><i class="bi bi-eye me-1"></i>Lihat Progress</a>
                        @if($selected->status==='completed')<a href="{{ route('sales.quotations.create',['dr'=>$selected->id]) }}" class="btn btn-primary"><i class="bi bi-file-earmark-text me-1"></i>Generate Penawaran</a>@endif
                    </div>
                </div>
            @else
                <div class="sales-detail-body"><x-empty text="Belum ada design request." /></div>
            @endif
        </aside>
    </div>
</div>
@endsection
