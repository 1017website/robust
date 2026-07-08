@extends('layouts.app')
@section('title', 'Leads')
@section('content')
@php
    $stageLabel = fn($s) => match($s) {
        'lead' => 'Lead',
        'design_request' => 'Design Request',
        'penawaran' => 'Penawaran',
        'negosiasi' => 'Negosiasi',
        'won' => 'Won / Closing',
        'lost' => 'Lost',
        default => ucfirst(str_replace('_', ' ', $s ?? '-'))
    };
@endphp
<div class="sales-ui">
    <div class="sales-page-head">
        <div class="sales-title-wrap">
            <a href="{{ route('dashboard') }}" class="btn btn-soft"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h1 class="page-title mb-1">Leads</h1>
                <div class="page-subtitle">Kelola lead aktif, tindak lanjut, dan proses menuju design request.</div>
            </div>
        </div>
        <a href="{{ route('sales.leads.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Lead Baru</a>
    </div>

    <div class="sales-grid-5">
        <div class="sales-stat"><div class="ico sblue"><i class="bi bi-people"></i></div><div><div class="label">Total Leads</div><div class="value">{{ $stats['total'] }}</div><div class="sub">Semua lead Anda</div></div></div>
        <div class="sales-stat"><div class="ico sgreen"><i class="bi bi-person-check"></i></div><div><div class="label">Lead Baru</div><div class="value">{{ $stats['lead'] }}</div><div class="sub">Tahap identifikasi</div></div></div>
        <div class="sales-stat"><div class="ico spurple"><i class="bi bi-pencil-square"></i></div><div><div class="label">Design Request</div><div class="value">{{ $stats['design_request'] }}</div><div class="sub">Sudah ke drafter</div></div></div>
        <div class="sales-stat"><div class="ico sorange"><i class="bi bi-file-text"></i></div><div><div class="label">Penawaran</div><div class="value">{{ $stats['penawaran'] }}</div><div class="sub">Proses proposal</div></div></div>
        <div class="sales-stat"><div class="ico sgreen"><i class="bi bi-trophy"></i></div><div><div class="label">Won / Closing</div><div class="value">{{ $stats['won'] }}</div><div class="sub">Berhasil closing</div></div></div>
    </div>

    <div class="card-r p-0">
        <form class="sales-filter-row p-3 pb-0" method="GET" style="grid-template-columns:minmax(260px,1fr) 190px 190px auto">
            <div class="sales-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari instansi, PIC, kebutuhan...">
            </div>
            <select name="stage" class="form-select">
                <option value="">Semua Stage</option>
                @foreach(['lead'=>'Lead','design_request'=>'Design Request','penawaran'=>'Penawaran','negosiasi'=>'Negosiasi','won'=>'Won','lost'=>'Lost'] as $k=>$v)
                    <option value="{{ $k }}" @selected(request('stage')==$k)>{{ $v }}</option>
                @endforeach
            </select>
            <select name="priority" class="form-select">
                <option value="">Semua Prioritas</option>
                @foreach(['high'=>'High','medium'=>'Medium','low'=>'Low'] as $k=>$v)
                    <option value="{{ $k }}" @selected(request('priority')==$k)>{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button>
        </form>

        <div class="table-wrap">
            <table class="sales-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Instansi / PIC</th>
                        <th>Kebutuhan Awal</th>
                        <th>Lokasi</th>
                        <th>Stage</th>
                        <th>Prioritas</th>
                        <th>Tanggal</th>
                        <th style="min-width:260px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td>{{ $leads->firstItem()+$loop->index }}</td>
                        <td>
                            <a class="fw-bold" href="{{ route('sales.leads.show',$lead) }}">{{ $lead->instansi }}</a>
                            <div class="small text-muted-2">{{ $lead->pic_name }} · {{ $lead->phone }}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-truncate-cell">{{ $lead->lab_name ?: '-' }}</div>
                            <div class="small text-muted-2 text-truncate-cell">{{ $lead->need_description ?: '-' }}</div>
                        </td>
                        <td>{{ $lead->city ?: $lead->location }}</td>
                        <td><span class="status-soft st-blue">{{ $stageLabel($lead->stage) }}</span></td>
                        <td><span class="status-soft {{ $lead->priority==='high'?'st-red':($lead->priority==='low'?'st-green':'st-yellow') }}">{{ ucfirst($lead->priority) }}</span></td>
                        <td>{{ $lead->created_at->translatedFormat('d M Y') }}<div class="small text-muted-2">{{ $lead->created_at->format('H:i') }}</div></td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">
                                <a class="btn btn-sm btn-soft text-nowrap" href="{{ route('sales.leads.show',$lead) }}"><i class="bi bi-eye me-1"></i>Detail</a>
                                <a class="btn btn-sm btn-soft text-nowrap" href="{{ route('sales.leads.edit',$lead) }}"><i class="bi bi-pencil me-1"></i>Edit</a>
                                <a class="btn btn-sm btn-primary text-nowrap" href="{{ route('sales.design-requests.create',['lead'=>$lead->id]) }}"><i class="bi bi-pencil-square me-1"></i>Design Request</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty text="Belum ada lead." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 d-flex justify-content-between align-items-center">
            <span class="small text-muted-2">Menampilkan {{ $leads->firstItem() ?? 0 }} - {{ $leads->lastItem() ?? 0 }} dari {{ $leads->total() }} data</span>
            {{ $leads->links() }}
        </div>
    </div>
</div>
@endsection
