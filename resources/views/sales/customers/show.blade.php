@extends('layouts.app')
@section('title', 'Detail Customer')
@section('content')
<x-page-header :title="$customer->name" :subtitle="$customer->category">
    <a href="{{ route('sales.customers.edit',$customer) }}" class="btn btn-soft btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
</x-page-header>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-r">
            <div class="card-head"><h2>Informasi</h2><x-status-badge :status="$customer->pipeline_stage" /></div>
            <div class="row g-3">
                <div class="col-md-6"><div class="small text-muted-2">Email</div><div class="fw-semibold">{{ $customer->email ?? '—' }}</div></div>
                <div class="col-md-6"><div class="small text-muted-2">Telepon</div><div class="fw-semibold">{{ $customer->phone ?? '—' }}</div></div>
                <div class="col-md-6"><div class="small text-muted-2">Kota</div><div class="fw-semibold">{{ $customer->city ?? '—' }}</div></div>
                <div class="col-md-6"><div class="small text-muted-2">Area / Lokasi</div><div class="fw-semibold">{{ $customer->area ?? '—' }}</div></div>
                <div class="col-md-6"><div class="small text-muted-2">Divisi Customer</div><div class="fw-semibold">{{ $customer->division ?? '—' }}</div></div>
                <div class="col-md-6"><div class="small text-muted-2">Website</div><div class="fw-semibold">{{ $customer->website ?? '—' }}</div></div>
                <div class="col-12"><div class="small text-muted-2">Alamat</div><div>{{ $customer->address ?? '—' }}</div></div>
            </div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>PIC</h2></div>
            <div class="table-wrap">
                <table class="table-r"><thead><tr><th>Nama</th><th>Jabatan</th><th>Telepon</th><th>Email</th></tr></thead><tbody>
                @forelse($customer->pics as $pic)
                    <tr><td class="fw-semibold">{{ $pic->name }} @if($pic->is_primary)<span class="badge text-bg-primary ms-1">Utama</span>@endif</td><td>{{ $pic->position }}</td><td>{{ $pic->phone }}</td><td>{{ $pic->email }}</td></tr>
                @empty
                    <tr><td colspan="4"><x-empty text="Belum ada PIC." /></td></tr>
                @endforelse
                </tbody></table>
            </div>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Penawaran</h2></div>
            @forelse($customer->quotations as $q)
                <div class="pipe-card d-flex justify-content-between align-items-center"><div><div class="t">{{ $q->code }}</div><div class="small text-muted-2">{{ $q->project_name }}</div></div><div class="d-flex align-items-center gap-2"><span class="fw-num fw-semibold">{{ \App\Support\Format::rupiahShort($q->grand_total) }}</span><x-status-badge :status="$q->status" /></div></div>
            @empty
                <x-empty text="Belum ada penawaran." />
            @endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-r">
            <div class="card-head"><h2>Pipeline</h2></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Stage</span><x-status-badge :status="$customer->pipeline_stage" /></div>
            <div class="mb-2"><span class="text-muted-2 small">Probability</span><div class="prog mt-1"><span style="width:{{ $customer->probability }}%"></span></div></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Sales</span><span class="fw-semibold">{{ $customer->sales?->name ?? '—' }}</span></div>
        </div>
        @if($customer->notes)<div class="card-r"><div class="card-head"><h2>Catatan</h2></div><p class="mb-0 small">{{ $customer->notes }}</p></div>@endif
    </div>
</div>
@endsection
