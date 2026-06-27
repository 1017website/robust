@extends('layouts.app')
@section('title', 'Detail Lead')
@section('content')
<x-page-header :title="$lead->instansi" :subtitle="$lead->code.' · '.$lead->lab_name">
    <a href="{{ route('sales.leads.edit',$lead) }}" class="btn btn-soft btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
    <a href="{{ route('sales.design-requests.create',['lead'=>$lead->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square me-1"></i>Buat Design Request</a>
</x-page-header>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-r">
            <div class="card-head"><h2>Informasi Lead</h2><x-status-badge :status="$lead->stage" /></div>
            <div class="row g-3">
                <div class="col-md-6"><div class="small text-muted-2">PIC</div><div class="fw-semibold">{{ $lead->pic_name }} @if($lead->pic_position)<span class="text-muted-2">({{ $lead->pic_position }})</span>@endif</div></div>
                <div class="col-md-6"><div class="small text-muted-2">Kontak</div><div class="fw-semibold">{{ $lead->phone }} · {{ $lead->email ?? '—' }}</div></div>
                <div class="col-md-6"><div class="small text-muted-2">Lokasi</div><div class="fw-semibold">{{ $lead->location }}, {{ $lead->city }}</div></div>
                <div class="col-md-6"><div class="small text-muted-2">Sumber</div><div class="fw-semibold">{{ ucfirst($lead->source) }}</div></div>
                <div class="col-12"><div class="small text-muted-2">Deskripsi Kebutuhan</div><div>{{ $lead->need_description ?? '—' }}</div></div>
                @if($lead->scope_items)
                <div class="col-12"><div class="small text-muted-2 mb-1">Scope Item</div>
                    @foreach($lead->scope_items as $item)<span class="pill me-1 mb-1">{{ $item }}</span>@endforeach
                </div>
                @endif
            </div>
        </div>

        <div class="card-r">
            <div class="card-head"><h2>Design Request Terkait</h2></div>
            @forelse($lead->designRequests as $dr)
                <div class="pipe-card d-flex justify-content-between align-items-center">
                    <div><div class="t">{{ $dr->code }} · {{ $dr->project_name }}</div><div class="small text-muted-2">{{ $dr->created_at->format('d M Y') }}</div></div>
                    <x-status-badge :status="$dr->status" />
                </div>
            @empty
                <x-empty text="Belum ada design request." />
            @endforelse
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-r">
            <div class="card-head"><h2>Ringkasan</h2></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Prioritas</span><x-status-badge :status="$lead->priority" /></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Estimasi Min</span><span class="fw-semibold">{{ $lead->est_value_min ? \App\Support\Format::rupiah($lead->est_value_min) : '—' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Estimasi Max</span><span class="fw-semibold">{{ $lead->est_value_max ? \App\Support\Format::rupiah($lead->est_value_max) : '—' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Dibuat</span><span class="fw-semibold">{{ $lead->created_at->format('d M Y') }}</span></div>
        </div>
        @if($lead->initial_note)
        <div class="card-r"><div class="card-head"><h2>Catatan</h2></div><p class="mb-0">{{ $lead->initial_note }}</p></div>
        @endif
    </div>
</div>
@endsection
