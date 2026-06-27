@extends('layouts.app')
@section('title', 'Dashboard Sales')
@section('content')
<x-page-header title="Dashboard Sales" subtitle="Ringkasan pipeline, lead, dan aktivitas Anda">
    <a href="{{ route('sales.leads.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Lead Baru</a>
</x-page-header>

@if($requestMasuk > 0)
    <div class="alert alert-info d-flex align-items-center">
        <i class="bi bi-inbox me-2"></i>Ada <strong class="mx-1">{{ $requestMasuk }}</strong> request masuk menunggu konfirmasi.
        <a href="{{ route('sales.request-masuk.index') }}" class="ms-auto btn btn-sm btn-primary">Lihat</a>
    </div>
@endif

<div class="stat-grid">
    <x-stat-card icon="bi-people" color="primary" label="Leads Aktif" :value="$stats['leads_aktif']" />
    <x-stat-card icon="bi-file-earmark-text" color="info" label="Penawaran Aktif" :value="$stats['penawaran_aktif']" />
    <x-stat-card icon="bi-folder" color="warning" label="Project Berjalan" :value="$stats['project_berjalan']" />
    <x-stat-card icon="bi-trophy" color="success" label="Deal Won (bln ini)" :value="$stats['deal_won']" />
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-r">
            <div class="card-head"><h2>Pipeline Penjualan</h2></div>
            <div class="row text-center g-2">
                @foreach(['leads'=>'Leads','design_request'=>'Design Req','penawaran'=>'Penawaran','won'=>'Won'] as $k=>$lbl)
                    <div class="col">
                        <div class="card-r" style="background:var(--bg)">
                            <div class="stat-value">{{ $pipeline[$k] }}</div>
                            <div class="stat-label">{{ $lbl }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Lead Terbaru</h2><a href="{{ route('sales.leads.index') }}" class="small">Semua</a></div>
            <div class="table-wrap">
                <table class="table-r">
                    <thead><tr><th>Instansi</th><th>PIC</th><th>Stage</th><th>Prioritas</th></tr></thead>
                    <tbody>
                    @forelse($recentLeads as $lead)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('sales.leads.show',$lead) }}">{{ $lead->instansi }}</a></td>
                            <td>{{ $lead->pic_name }}</td>
                            <td><x-status-badge :status="$lead->stage" /></td>
                            <td><x-status-badge :status="$lead->priority" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty text="Belum ada lead." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-r">
            <div class="card-head"><h2>Agenda Hari Ini</h2></div>
            @forelse($todayActivities as $act)
                <div class="pipe-card">
                    <div class="t">{{ $act->title }}</div>
                    <div class="text-muted-2 small mt-1"><i class="bi bi-clock me-1"></i>{{ $act->activity_time ? \Illuminate\Support\Carbon::parse($act->activity_time)->format('H:i') : 'Sepanjang hari' }} · {{ ucfirst($act->type) }}</div>
                </div>
            @empty
                <x-empty text="Tidak ada agenda hari ini." />
            @endforelse
            <a href="{{ route('activities.create') }}" class="btn btn-soft btn-sm w-100 mt-2"><i class="bi bi-plus-lg me-1"></i>Tambah Aktivitas</a>
        </div>
    </div>
</div>
@endsection
