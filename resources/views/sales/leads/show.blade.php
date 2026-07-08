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
    $activities = $lead->activities ?? collect();
@endphp
<div class="sales-ui lead-detail-page">
    <div class="lead-detail-head">
        <div>
            <a href="{{ route('sales.leads.index') }}" class="lead-back-link"><i class="bi bi-arrow-left"></i> Kembali</a>
            <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                <h1 class="page-title mb-0">Detail Lead</h1>
                <span class="status-soft st-green">{{ ucfirst($lead->status) }}</span>
            </div>
            <div class="page-subtitle mt-1">ID Lead: {{ $lead->code }} · Dibuat: {{ $lead->created_at->translatedFormat('d M Y, H:i') }} oleh {{ $lead->creator?->name ?? '-' }}</div>
        </div>
        <div class="lead-detail-actions">
            <a href="{{ route('sales.leads.edit', $lead) }}" class="btn btn-soft"><i class="bi bi-pencil me-1"></i>Edit</a>
            <a href="{{ route('sales.design-requests.create', ['lead' => $lead->id]) }}" class="btn btn-primary"><i class="bi bi-file-plus me-1"></i>Buat Design Request</a>
        </div>
    </div>

    <div class="lead-detail-tabs">
        <a href="#" class="active">Ringkasan</a>
        <a href="#leadActivities">Aktivitas</a>
        <a href="#leadNotes">Catatan</a>
        <a href="#leadDocuments">Dokumen</a>
        <a href="#leadHistory">Riwayat</a>
        <a href="#leadFollowUp">Tindak Lanjut</a>
    </div>

    <div class="lead-detail-grid">
        <main class="lead-detail-main">
            <section class="lead-card lead-info-card">
                <h2 class="lead-card-title"><span class="lead-icon sblue"><i class="bi bi-person"></i></span>Informasi Customer</h2>
                <div class="lead-info-grid two">
                    <div class="lead-info-item"><span>Nama Instansi</span><strong>{{ $lead->instansi }}</strong></div>
                    <div class="lead-info-item"><span>Jabatan PIC</span><strong>{{ $lead->pic_position ?: '-' }}</strong></div>
                    <div class="lead-info-item"><span>PIC</span><strong>{{ $lead->pic_name }}</strong></div>
                    <div class="lead-info-item"><span>No. Telepon</span><strong>{{ $lead->phone ?: '-' }}</strong></div>
                    <div class="lead-info-item"><span>No. WhatsApp</span><strong>{{ $lead->phone ?: '-' }} @if($lead->phone)<a class="lead-wa-link" href="https://wa.me/{{ preg_replace('/\D+/', '', $lead->phone) }}" target="_blank"><i class="bi bi-whatsapp"></i></a>@endif</strong></div>
                    <div class="lead-info-item"><span>Lokasi</span><strong>{{ $lead->location ?: '-' }}</strong></div>
                    <div class="lead-info-item"><span>Email</span><strong>{{ $lead->email ?: '-' }}</strong></div>
                    <div class="lead-info-item"><span>Tipe Instansi</span><strong>{{ $lead->instansi_type ?: '-' }}</strong></div>
                </div>
            </section>

            <section class="lead-card lead-info-card">
                <h2 class="lead-card-title"><span class="lead-icon sgreen"><i class="bi bi-clipboard-check"></i></span>Kebutuhan Awal</h2>
                <div class="lead-info-grid two">
                    <div class="lead-info-item"><span>Nama Laboratorium / Proyek</span><strong>{{ $lead->lab_name ?: '-' }}</strong></div>
                    <div class="lead-info-item"><span>Ruang Lingkup</span>
                        <strong class="lead-scope-wrap">
                            @forelse($scopeItems as $scope)
                                <em>{{ $scope }}</em>
                            @empty
                                -
                            @endforelse
                        </strong>
                    </div>
                    <div class="lead-info-item"><span>Deskripsi Kebutuhan</span><strong>{{ $lead->need_description ?: '-' }}</strong></div>
                    <div class="lead-info-item"><span>Kapasitas / Pengguna</span><strong>{{ $lead->capacity ?: '-' }}</strong></div>
                    <div class="lead-info-item"><span>Estimasi Budget (IDR)</span><strong>{{ $budgetText }}</strong></div>
                </div>
            </section>

            <section class="lead-card lead-info-card" id="leadNotes">
                <h2 class="lead-card-title"><span class="lead-icon sorange"><i class="bi bi-journal-text"></i></span>Informasi Tambahan</h2>
                <div class="lead-info-grid three">
                    <div class="lead-info-item wide"><span>Catatan Awal</span><strong>{{ $lead->initial_note ?: '-' }}</strong></div>
                    <div class="lead-info-item"><span>Rencana Tindak Lanjut Awal</span><strong>{{ $lead->initial_followup_date?->translatedFormat('d M Y') ?: '-' }}</strong></div>
                    <div class="lead-info-item"><span>Preferensi Kontak</span><strong>{{ $lead->contact_preference ?: '-' }}</strong></div>
                    <div class="lead-info-item"><span>Waktu Kontak Terbaik</span><strong>{{ $lead->best_contact_time ?: '-' }}</strong></div>
                </div>
            </section>
        </main>

        <aside class="lead-detail-summary">
            <section class="lead-card">
                <h2 class="lead-card-title"><span class="lead-icon spurple"><i class="bi bi-clipboard-data"></i></span>Ringkasan Lead</h2>
                <div class="lead-summary-list">
                    <div><i class="bi bi-link-45deg text-primary"></i><span>Sumber Lead</span><strong>{{ ucfirst($lead->source) }}</strong></div>
                    <div><i class="bi bi-flag text-danger"></i><span>Prioritas</span><strong>{{ $priorityLabel }}</strong></div>
                    <div><i class="bi bi-cash-coin text-success"></i><span>Potensi Deal (IDR)</span><strong>{{ $budgetText }}</strong></div>
                    <div><i class="bi bi-diagram-3 text-warning"></i><span>Tahap Saat Ini</span><strong><span class="status-soft st-green">{{ $stageLabel }}</span></strong></div>
                    <div><i class="bi bi-person-check text-primary"></i><span>Sales</span><strong>{{ $lead->sales?->name ?? '-' }}</strong></div>
                    <div><i class="bi bi-building text-primary"></i><span>Customer Terhubung</span><strong>@if($lead->customer)<a href="{{ route('sales.customers.show', $lead->customer) }}">{{ $lead->customer->code ?? $lead->customer->name }}</a><small>Pipeline: {{ \App\Models\Customer::stages()[$lead->customer->pipeline_stage] ?? $lead->customer->pipeline_stage }}</small>@else - @endif</strong></div>
                    <div><i class="bi bi-person text-muted"></i><span>Dibuat Oleh</span><strong>{{ $lead->creator?->name ?? '-' }}</strong></div>
                    <div><i class="bi bi-clock-history text-primary"></i><span>Terakhir Diperbarui</span><strong>{{ $lead->updated_at->translatedFormat('d M Y, H:i') }}</strong></div>
                </div>
            </section>
        </aside>

        <aside class="lead-detail-side">
            <section class="lead-card" id="leadDocuments">
                <h2 class="lead-card-title"><span class="lead-icon sblue"><i class="bi bi-file-earmark-text"></i></span>Dokumen Pendukung</h2>
                @forelse($lead->documents as $doc)
                    <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" class="lead-doc-row">
                        <i class="bi {{ strtolower($doc->file_type) === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-primary' }}"></i>
                        <span>{{ $doc->name }}</span>
                        <b><i class="bi bi-download"></i></b>
                    </a>
                @empty
                    <div class="lead-empty-file py-3">Belum ada dokumen.</div>
                @endforelse
                @if($lead->documents->count())
                    <a href="{{ route('documents.index', ['q' => $lead->code]) }}" class="lead-link-more">Lihat semua ({{ $lead->documents->count() }})</a>
                @endif
            </section>

            <section class="lead-card" id="leadActivities">
                <h2 class="lead-card-title"><span class="lead-icon sblue"><i class="bi bi-shield-check"></i></span>Aktivitas Terakhir</h2>
                <div class="lead-timeline">
                    <div class="lead-timeline-item">
                        <span><i class="bi bi-person-plus"></i></span>
                        <div><strong>Lead dibuat</strong><small>oleh {{ $lead->creator?->name ?? '-' }}</small><small>{{ $lead->created_at->translatedFormat('d M Y, H:i') }}</small></div>
                    </div>
                    @if($lead->praLead)
                        <div class="lead-timeline-item">
                            <span><i class="bi bi-check2-circle"></i></span>
                            <div><strong>Lead diterima dari Request Masuk</strong><small>{{ $lead->praLead->responded_at?->translatedFormat('d M Y, H:i') ?: '-' }}</small></div>
                        </div>
                    @endif
                    @foreach($activities->take(3) as $activity)
                        <div class="lead-timeline-item">
                            <span><i class="bi bi-calendar-check"></i></span>
                            <div><strong>{{ $activity->title }}</strong><small>{{ $activity->activity_date?->translatedFormat('d M Y') }} {{ $activity->activity_time }}</small></div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="lead-card" id="leadFollowUp">
                <h2 class="lead-card-title"><span class="lead-icon sgreen"><i class="bi bi-calendar2-check"></i></span>Tindak Lanjut Terjadwal</h2>
                @php($nextActivity = $activities->where('status', 'scheduled')->sortBy('activity_date')->first())
                @if($nextActivity)
                    <div class="lead-next-box">
                        <strong>{{ ucfirst(str_replace('_', ' ', $nextActivity->type)) }}</strong>
                        <span>{{ $nextActivity->activity_date?->translatedFormat('d M Y') }}{{ $nextActivity->activity_time ? ', '.$nextActivity->activity_time : '' }}</span>
                        <small>Bersama: {{ $lead->pic_name }}</small>
                    </div>
                @else
                    <div class="lead-empty-file py-2">Belum ada jadwal tindak lanjut.</div>
                @endif
                <a href="{{ route('activities.create', ['lead_id' => $lead->id]) }}" class="btn btn-soft w-100 mt-2">Lihat / Buat Jadwal</a>
            </section>
        </aside>
    </div>
</div>
@endsection
