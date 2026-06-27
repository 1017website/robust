@extends('layouts.app')
@section('title', 'Dashboard Produksi')
@section('content')
<x-page-header title="Dashboard Produksi / Drafter" subtitle="Antrian design request dan tugas Anda" />

<div class="stat-grid">
    <x-stat-card icon="bi-inbox" color="info" label="Request Baru" :value="$stats['request_baru']" />
    <x-stat-card icon="bi-pencil-square" color="warning" label="Sedang Drafting" :value="$stats['drafting']" />
    <x-stat-card icon="bi-hourglass-split" color="primary" label="Menunggu Approval" :value="$stats['waiting_approval']" />
    <x-stat-card icon="bi-check2-circle" color="success" label="Completed" :value="$stats['completed']" />
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card-r">
            <div class="card-head"><h2>Tugas Saya</h2></div>
            <div class="table-wrap">
                <table class="table-r">
                    <thead><tr><th>Kode</th><th>Customer</th><th>Deadline</th><th>Progress</th><th></th></tr></thead>
                    <tbody>
                    @forelse($myTasks as $dr)
                        <tr>
                            <td class="fw-semibold">{{ $dr->code }}</td>
                            <td>{{ $dr->customer_name }}</td>
                            <td>{{ $dr->deadline?->format('d M Y') ?? '—' }}</td>
                            <td style="min-width:120px"><div class="prog"><span style="width:{{ $dr->progress }}%"></span></div><small class="text-muted-2">{{ $dr->progress }}%</small></td>
                            <td><a href="{{ route('drafter.design-requests.show',$dr) }}" class="btn btn-sm btn-soft">Buka</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty text="Tidak ada tugas aktif." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-r">
            <div class="card-head"><h2>Antrian Masuk</h2><a href="{{ route('drafter.design-requests.index') }}" class="small">Semua</a></div>
            @forelse($queue as $dr)
                <div class="pipe-card d-flex justify-content-between align-items-center">
                    <div>
                        <div class="t">{{ $dr->code }} · {{ $dr->customer_name }}</div>
                        <div class="text-muted-2 small">dari {{ $dr->sales?->name ?? '—' }}</div>
                    </div>
                    <x-status-badge :status="$dr->status" />
                </div>
            @empty
                <x-empty text="Antrian kosong." />
            @endforelse
        </div>
    </div>
</div>
@endsection
