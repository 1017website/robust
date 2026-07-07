@extends('layouts.app')
@section('title', 'Request Masuk')
@section('content')
@php
    $selected = $selectedRequest;
    $sourceClass = fn($source) => match(strtolower($source ?? '')) {
        'whatsapp' => 'st-green', 'website' => 'st-blue', 'email' => 'st-red', 'referensi' => 'st-purple', default => 'st-gray'
    };
@endphp
<div class="sales-ui">
    <div class="sales-main-grid">
        <div>
            <div class="sales-page-head">
                <div>
                    <h1 class="page-title mb-1">Request Masuk</h1>
                    <div class="page-subtitle">Daftar prospek baru yang dikirim oleh Sales Admin. Silakan tinjau dan terima untuk menjadi lead Anda.</div>
                </div>
            </div>

            <div class="sales-grid-4">
                <div class="sales-stat"><div class="ico sblue"><i class="bi bi-briefcase"></i></div><div><div class="label">Request Baru</div><div class="value">{{ $stats['baru'] }}</div><div class="sub">Total belum diterima</div></div></div>
                <div class="sales-stat"><div class="ico sgreen"><i class="bi bi-calendar2-check"></i></div><div><div class="label">Hari Ini</div><div class="value">{{ $stats['hari_ini'] }}</div><div class="sub">Diterima hari ini</div></div></div>
                <div class="sales-stat"><div class="ico sorange"><i class="bi bi-clock"></i></div><div><div class="label">Menunggu Respon</div><div class="value">{{ $stats['menunggu'] }}</div><div class="sub">Belum diproses</div></div></div>
                <div class="sales-stat"><div class="ico sred"><i class="bi bi-x-circle"></i></div><div><div class="label">Ditolak</div><div class="value">{{ $stats['ditolak'] }}</div><div class="sub">Dalam 7 hari terakhir</div></div></div>
            </div>

            <form class="sales-filter-row" method="GET" style="grid-template-columns:120px 120px 120px 160px 1fr">
                <a href="{{ route('sales.request-masuk.index') }}" class="sales-chip {{ !request()->hasAny(['priority','q']) ? 'active' : '' }} text-center">Semua Request</a>
                <a href="{{ route('sales.request-masuk.index',['today'=>1]) }}" class="sales-chip text-center">Hari Ini</a>
                <a href="{{ route('sales.request-masuk.index',['week'=>1]) }}" class="sales-chip text-center">Minggu Ini</a>
                <select name="priority" class="form-select">
                    <option value="">Semua Prioritas</option>
                    <option value="high" @selected(request('priority')=='high')>High</option>
                    <option value="medium" @selected(request('priority')=='medium')>Medium</option>
                    <option value="low" @selected(request('priority')=='low')>Low</option>
                </select>
                <div class="sales-search"><i class="bi bi-search"></i><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari request..."></div>
            </form>

            <div class="card-r p-0 overflow-hidden">
                <div class="table-wrap">
                    <table class="sales-table">
                        <thead><tr><th>Customer / Instansi</th><th>Kebutuhan Awal</th><th>Lokasi</th><th>Admin Pengirim</th><th>Prioritas</th><th>Status</th><th>Diterima</th><th></th></tr></thead>
                        <tbody>
                        @forelse($requests as $req)
                            <tr class="{{ $selected && $selected->id === $req->id ? 'selected' : '' }}">
                                <td>
                                    <div class="d-flex gap-3 align-items-center"><div class="logo-avatar"><i class="bi bi-building"></i></div><div><div class="fw-bold">{{ $req->instansi }}</div><div class="small text-muted-2">{{ $req->pic_name }}</div></div></div>
                                </td>
                                <td><div class="fw-bold text-truncate-cell">{{ $req->lab_type ?: 'Kebutuhan proyek' }}</div><div class="small text-muted-2 text-truncate-cell">{{ $req->initial_need ?: '—' }}</div></td>
                                <td class="fw-semibold">{{ $req->location ?: '—' }}</td>
                                <td><div class="d-flex align-items-center gap-2"><div class="mini-avatar">A</div><div>Admin<br><span class="small text-muted-2">{{ optional($req->sent_at ?? $req->created_at)->translatedFormat('d M Y') }}</span></div></div></td>
                                <td><span class="status-soft {{ $req->priority === 'high' ? 'st-red' : ($req->priority === 'low' ? 'st-green' : 'st-yellow') }}">{{ ucfirst($req->priority) }}</span></td>
                                <td><span class="status-soft st-blue">Baru</span></td>
                                <td>{{ optional($req->sent_at ?? $req->created_at)->isToday() ? 'Hari ini' : optional($req->sent_at ?? $req->created_at)->diffForHumans() }}<div class="small text-muted-2">{{ optional($req->sent_at ?? $req->created_at)->format('H:i') }}</div></td>
                                <td><a href="#detail-request" class="btn btn-sm btn-soft"><i class="bi bi-chevron-right"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-empty text="Tidak ada request masuk saat ini." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 d-flex justify-content-between align-items-center"><span class="small text-muted-2">Menampilkan {{ $requests->firstItem() ?? 0 }} - {{ $requests->lastItem() ?? 0 }} dari {{ $requests->total() }} request</span>{{ $requests->links() }}</div>
            </div>
        </div>

        <aside class="sales-detail" id="detail-request">
            @if($selected)
                <div class="sales-detail-head"><div><h5 class="mb-0 fw-black">Detail Request</h5></div><a class="btn btn-sm btn-soft" href="{{ route('sales.request-masuk.index') }}"><i class="bi bi-x-lg"></i></a></div>
                <div class="sales-detail-body">
                    <span class="status-soft st-blue">NEW REQUEST</span>
                    <div class="sales-detail-title">{{ $selected->instansi }}</div>
                    <div class="small text-muted-2 mb-3">Request dikirim oleh Admin pada {{ optional($selected->sent_at ?? $selected->created_at)->translatedFormat('d M Y, H:i') }}</div>

                    <div class="row g-3">
                        <div class="col-12"><div class="info-card"><h6><i class="bi bi-person sblue rounded p-2 me-2"></i>Informasi Customer</h6>
                            <div class="kv"><div class="k">Nama Instansi</div><div class="v">{{ $selected->instansi }}</div></div>
                            <div class="kv"><div class="k">PIC</div><div class="v">{{ $selected->pic_name }}</div></div>
                            <div class="kv"><div class="k">No. WA</div><div class="v">{{ $selected->phone ?: '—' }}</div></div>
                            <div class="kv"><div class="k">Email</div><div class="v">{{ $selected->email ?: '—' }}</div></div>
                            <div class="kv"><div class="k">Lokasi</div><div class="v">{{ $selected->location ?: '—' }}</div></div>
                        </div></div>
                        <div class="col-12"><div class="info-card"><h6><i class="bi bi-clipboard-check sgreen rounded p-2 me-2"></i>Kebutuhan Awal</h6>
                            <div class="fw-bold mb-2">{{ $selected->lab_type ?: 'Kebutuhan Laboratorium' }}</div>
                            <div class="small text-muted-2">{{ $selected->initial_need ?: 'Belum ada deskripsi kebutuhan.' }}</div>
                        </div></div>
                        <div class="col-md-6"><div class="info-card h-100"><h6>Catatan Sales Admin</h6><div class="p-3 rounded-3 bg-warning-subtle small">{{ $selected->admin_note ?: 'Belum ada catatan.' }}</div></div></div>
                        <div class="col-md-6"><div class="info-card h-100"><h6>Estimasi Potensi</h6><div class="text-success fw-bold fs-5">{{ \App\Support\Format::rupiah($selected->est_value_min ?? 0) }}</div><div class="text-center fw-bold my-1">-</div><div class="text-success fw-bold fs-5">{{ \App\Support\Format::rupiah($selected->est_value_max ?? 0) }}</div></div></div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-outline-danger flex-fill" data-bs-toggle="modal" data-bs-target="#reject{{ $selected->id }}"><i class="bi bi-x-lg me-1"></i>Tolak Request</button>
                        <form method="POST" action="{{ route('sales.request-masuk.accept',$selected) }}" class="flex-fill">@csrf<button class="btn btn-primary w-100"><i class="bi bi-check-circle me-1"></i>Terima Menjadi Lead</button></form>
                    </div>
                    <div class="small text-muted-2 text-center mt-3"><i class="bi bi-lock me-1"></i>Setelah diterima, request akan berpindah ke menu Leads</div>
                </div>
                <div class="modal fade" id="reject{{ $selected->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('sales.request-masuk.reject',$selected) }}">@csrf<div class="modal-header"><h5 class="modal-title">Tolak Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><textarea name="reject_reason" rows="4" class="form-control" required placeholder="Tulis alasan penolakan..."></textarea></div><div class="modal-footer"><button class="btn btn-danger">Tolak Request</button></div></form></div></div></div>
            @else
                <div class="sales-detail-body"><x-empty text="Pilih request untuk melihat detail." /></div>
            @endif
        </aside>
    </div>
</div>
@endsection
