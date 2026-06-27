@extends('layouts.app')
@section('title', 'Request Masuk')
@section('content')
<x-page-header title="Request Masuk" subtitle="Pra lead yang ditugaskan kepada Anda — terima atau tolak" />

<div class="stat-grid">
    <x-stat-card icon="bi-inbox" color="primary" label="Request Baru" :value="$stats['baru']" />
    <x-stat-card icon="bi-hourglass-split" color="warning" label="Menunggu Respon" :value="$stats['menunggu']" />
    <x-stat-card icon="bi-check2" color="success" label="Direspon Hari Ini" :value="$stats['hari_ini']" />
    <x-stat-card icon="bi-x-circle" color="danger" label="Ditolak (7 hari)" :value="$stats['ditolak']" />
</div>

<form class="filter-bar" method="GET">
    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari instansi / kebutuhan...">
    <select name="priority" class="form-select">
        <option value="">Semua Prioritas</option>
        <option value="high" @selected(request('priority')=='high')>High</option>
        <option value="medium" @selected(request('priority')=='medium')>Medium</option>
        <option value="low" @selected(request('priority')=='low')>Low</option>
    </select>
    <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
</form>

<div class="row g-3">
    @forelse($requests as $req)
        <div class="col-lg-6">
            <div class="card-r h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-bold">{{ $req->instansi }}</div>
                        <div class="small text-muted-2">{{ $req->code }} · {{ $req->location }}</div>
                    </div>
                    <x-status-badge :status="$req->priority" />
                </div>
                <div class="small mb-2"><i class="bi bi-person me-1"></i>{{ $req->pic_name }} @if($req->pic_position)· {{ $req->pic_position }}@endif</div>
                <div class="small mb-2"><i class="bi bi-telephone me-1"></i>{{ $req->phone ?? '—' }} <span class="ms-2"><i class="bi bi-tag me-1"></i>{{ ucfirst($req->source) }}</span></div>
                @if($req->initial_need)<div class="pipe-card mt-2 mb-3">{{ $req->initial_need }}</div>@endif
                @if($req->est_value_min)<div class="small mb-3"><i class="bi bi-cash-stack me-1"></i>Estimasi: <strong>{{ \App\Support\Format::rupiahShort($req->est_value_min) }}</strong> @if($req->est_value_max)– {{ \App\Support\Format::rupiahShort($req->est_value_max) }}@endif</div>@endif
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('sales.request-masuk.accept',$req) }}" class="flex-grow-1">
                        @csrf
                        <button class="btn btn-primary btn-sm w-100"><i class="bi bi-check-lg me-1"></i>Terima jadi Lead</button>
                    </form>
                    <button class="btn btn-soft btn-sm text-danger" data-bs-toggle="modal" data-bs-target="#reject{{ $req->id }}"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        </div>

        <div class="modal fade" id="reject{{ $req->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('sales.request-masuk.reject',$req) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Tolak Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <p class="small text-muted-2">Beri alasan penolakan untuk <strong>{{ $req->instansi }}</strong>.</p>
                            <textarea name="reject_reason" rows="3" class="form-control" placeholder="Alasan penolakan..." required></textarea>
                        </div>
                        <div class="modal-footer"><button class="btn btn-danger">Tolak Request</button></div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card-r"><x-empty text="Tidak ada request masuk saat ini." /></div></div>
    @endforelse
</div>
<div class="mt-3">{{ $requests->links() }}</div>
@endsection
