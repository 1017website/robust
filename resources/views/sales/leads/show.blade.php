@extends('layouts.app')
@section('title', 'Detail Lead')

@section('content')
@php
    $stageLabel = match($lead->stage) {
        'lead', 'identify' => 'Lead',
        'design_request' => 'Design Request',
        'penawaran' => 'Penawaran',
        'negosiasi' => 'Negosiasi',
        'won', 'closing' => 'Won / Closing',
        'lost' => 'Lost',
        default => ucfirst(str_replace('_', ' ', $lead->stage ?? '-'))
    };
    $priorityLabel = ['high' => 'High (Tinggi)', 'medium' => 'Medium', 'low' => 'Low'][$lead->priority] ?? ucfirst($lead->priority ?? '-');
    $scopeItems = is_array($lead->scope_items) ? array_filter($lead->scope_items) : [];
    $budgetMin = $lead->est_value_min ? \App\Support\Format::rupiah($lead->est_value_min) : null;
    $budgetMax = $lead->est_value_max ? \App\Support\Format::rupiah($lead->est_value_max) : null;
    $budgetText = $budgetMin || $budgetMax ? trim(($budgetMin ?? '-').' - '.($budgetMax ?? '-')) : '-';
    $activities = $lead->activities->take(5);
    $newActivityUrl = route('activities.create', array_filter(['customer_id' => $lead->customer_id, 'lead_id' => $lead->id, 'sales_id' => $lead->sales_id]));
@endphp
<div class="sales-ui">
    <div class="sales-page-head">
        <div class="sales-title-wrap"><a href="{{ route('sales.leads.index') }}" class="btn btn-soft"><i class="bi bi-arrow-left"></i></a><div><div class="d-flex gap-2 align-items-center"><h1 class="page-title mb-0">Detail Lead</h1><span class="status-soft st-green">{{ ucfirst($lead->status) }}</span></div><div class="page-subtitle">ID Lead: {{ $lead->code }} · Dibuat: {{ $lead->created_at->translatedFormat('d M Y, H:i') }} oleh {{ $lead->creator?->name ?? '-' }}</div></div></div>
        <div class="page-actions lead-show-actions"><a href="{{ route('sales.leads.edit',$lead) }}" class="btn btn-soft"><i class="bi bi-pencil me-1"></i>Edit</a><a href="{{ route('sales.design-requests.create',['lead'=>$lead->id]) }}" class="btn btn-primary"><i class="bi bi-file-plus me-1"></i>Buat Design Request</a></div>
    </div>

    {{-- Navigasi cepat ke tiap bagian halaman. --}}
    <nav class="lead-detail-tabs" aria-label="Bagian detail lead">
        <a href="#leadSummary" class="active">Ringkasan</a>
        <a href="#leadNeeds">Kebutuhan</a>
        <a href="#leadNotes">Catatan</a>
        <a href="#leadDocuments">Dokumen</a>
        <a href="#leadActivities">Aktivitas</a>
        <a href="#leadHistory">Riwayat</a>
    </nav>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="info-card mb-3" id="leadSummary"><h6><i class="bi bi-person sblue rounded p-2 me-2"></i>Informasi Customer</h6><div class="row"><div class="col-md-6"><div class="kv"><div class="k">Nama Instansi</div><div class="v">{{ $lead->instansi }}</div></div><div class="kv"><div class="k">PIC</div><div class="v">{{ $lead->pic_name }}</div></div><div class="kv"><div class="k">No. WhatsApp</div><div class="v">{{ $lead->phone ?: '-' }}</div></div><div class="kv"><div class="k">Email</div><div class="v">{{ $lead->email ?: '-' }}</div></div></div><div class="col-md-6"><div class="kv"><div class="k">Jabatan PIC</div><div class="v">{{ $lead->pic_position ?: '-' }}</div></div><div class="kv"><div class="k">No. Telepon</div><div class="v">{{ $lead->phone ?: '-' }}</div></div><div class="kv"><div class="k">Lokasi</div><div class="v">{{ $lead->location ?: '-' }}</div></div><div class="kv"><div class="k">Tipe Instansi</div><div class="v">{{ $lead->instansi_type ?: '-' }}</div></div></div></div></div>
            <div class="info-card mb-3" id="leadNeeds">
                <h6><i class="bi bi-clipboard-check sgreen rounded p-2 me-2"></i>Kebutuhan Awal</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="kv"><div class="k">Nama Laboratorium / Proyek</div><div class="v">{{ $lead->lab_name ?: '-' }}</div></div>
                        <div class="kv"><div class="k">Deskripsi Kebutuhan</div><div class="v">{{ $lead->need_description ?: '-' }}</div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="k small text-muted-2 mb-2">Ruang Lingkup</div>
                        @foreach(($lead->scope_items ?? []) as $item)<span class="tag-pill">{{ $item }}</span>@endforeach
                        @if(empty($lead->scope_items))<span class="text-muted-2">-</span>@endif
                        @if(auth()->user()->canViewPrices())
                            <div class="kv mt-2"><div class="k">Estimasi Budget</div><div class="v">{{ \App\Support\Format::rupiah($lead->est_value_min) }} - {{ \App\Support\Format::rupiah($lead->est_value_max) }}</div></div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="info-card" id="leadNotes"><h6><i class="bi bi-info-circle sorange rounded p-2 me-2"></i>Informasi Tambahan</h6><div class="row"><div class="col-md-6"><div class="kv"><div class="k">Catatan Awal</div><div class="v">{{ $lead->initial_note ?: '-' }}</div></div></div><div class="col-md-6"><div class="kv"><div class="k">Rencana Tindak Lanjut Awal</div><div class="v">{{ $lead->initial_followup_date?->translatedFormat('d M Y') ?: '-' }}</div></div><div class="kv"><div class="k">Preferensi Kontak</div><div class="v">{{ $lead->contact_preference ?: '-' }}</div></div><div class="kv"><div class="k">Waktu Kontak Terbaik</div><div class="v">{{ $lead->best_contact_time ?: '-' }}</div></div></div></div></div>
        </div>
        <div class="col-xl-4">
            <div class="info-card mb-3"><h6>Ringkasan Lead</h6><div class="kv"><div class="k">Sumber Lead</div><div class="v">{{ \App\Models\PraLead::sources()[$lead->source] ?? \Illuminate\Support\Str::headline($lead->source) }}</div></div><div class="kv"><div class="k">Prioritas</div><div class="v">{{ ucfirst($lead->priority) }}</div></div><div class="kv"><div class="k">Tahap Saat Ini</div><div class="v"><span class="status-soft st-green">{{ ucfirst(str_replace('_',' ',$lead->stage)) }}</span></div></div><div class="kv"><div class="k">Sales</div><div class="v">{{ $lead->sales?->name ?? '-' }}</div></div><div class="kv"><div class="k">Dibuat Oleh</div><div class="v">{{ $lead->creator?->name ?? '-' }}</div></div><div class="kv"><div class="k">Terakhir Diperbarui</div><div class="v">{{ $lead->updated_at->translatedFormat('d M Y, H:i') }}</div></div></div>
            <div class="info-card mb-3" id="leadDocuments"><h6>Dokumen Pendukung</h6>@forelse($lead->documents as $doc)<div class="sales-row-card d-flex justify-content-between align-items-center"><span><i class="bi bi-file-earmark me-2"></i>{{ $doc->name }}</span><a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" aria-label="Download {{ $doc->name }}"><i class="bi bi-download"></i></a></div>@empty<div class="small text-muted-2">Belum ada dokumen.</div>@endforelse</div>
            <div class="info-card mb-3" id="leadActivities">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2"><h6 class="mb-0">Aktivitas</h6><a href="{{ $newActivityUrl }}" class="btn btn-sm btn-soft"><i class="bi bi-plus-lg me-1"></i>Tambah</a></div>
                @forelse($activities as $activity)
                    <a href="{{ route('activities.index', ['view' => 'list', 'activity' => $activity->id]) }}#activity-customer-detail" class="lead-activity-item text-reset text-decoration-none">
                        <span class="lead-activity-text">
                            <strong>{{ $activity->title }}</strong>
                            <small>{{ \App\Models\Activity::types()[$activity->type] ?? $activity->type }} · {{ $activity->activity_date?->translatedFormat('d M Y') ?? '-' }}{{ $activity->sales ? ' · '.$activity->sales->name : '' }}</small>
                        </span>
                        <x-status-badge :status="$activity->status" />
                    </a>
                @empty
                    <div class="small text-muted-2">Belum ada aktivitas untuk lead ini.</div>
                @endforelse
            </div>
            <div class="info-card mb-3" id="leadHistory"><h6>Riwayat Lead</h6><div class="timeline-list"><div class="timeline-item" style="grid-template-columns:26px 1fr"><span class="time-dot bg-primary"></span><div><div class="fw-bold">Lead dibuat</div><div class="small text-muted-2">{{ $lead->created_at->translatedFormat('d M Y, H:i') }}</div></div></div>@if($lead->praLead)<div class="timeline-item" style="grid-template-columns:26px 1fr"><span class="time-dot bg-success"></span><div><div class="fw-bold">Lead diterima dari Request Masuk</div><div class="small text-muted-2">{{ $lead->praLead->responded_at?->translatedFormat('d M Y, H:i') }}</div></div></div>@endif</div></div>
            @if(auth()->user()->isSales())<div class="info-card"><h6>Tindak Lanjut Terjadwal</h6><a href="{{ $newActivityUrl }}" class="btn btn-soft w-100">Buat Jadwal / Aktivitas</a></div>@endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.lead-detail-tabs a').forEach(function (tab, _, tabs) {
    tab.addEventListener('click', function () {
        tabs.forEach(function (other) { other.classList.toggle('active', other === tab); });
    });
});
</script>
@endpush
