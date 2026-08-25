@extends('layouts.app')
@section('title', 'Catatan Penawaran')
@section('content')
<x-page-header :title="$quotation->code" :subtitle="$quotation->customer_name.' · '.$quotation->project_name">
    <a href="{{ route('spv.quotation-approvals.index') }}" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</x-page-header>

<div class="alert alert-info"><i class="bi bi-info-circle me-1"></i>Penawaran ini dibuat oleh <strong>{{ $quotation->sales?->name ?? 'Sales' }}</strong> pada {{ $quotation->created_at?->format('d M Y H:i') }}. Tidak diperlukan approval SPV.</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-r"><div class="card-head"><h2>Item & Spesifikasi</h2><x-status-badge :status="$quotation->status" :label="$quotation->statusLabel()" /></div><div class="quotation-item-list">
            @forelse($quotation->items as $item)<x-quotation-item-card :item="$item" :show-cost="false" :show-price="false" />@empty<x-empty text="Rincian tersimpan pada file penawaran yang diupload sales." />@endforelse
        </div></div>
        <div class="card-r"><div class="card-head"><h2>Dokumentasi</h2><span class="badge text-bg-light">{{ $quotation->documents->count() }} file</span></div>
            @forelse($quotation->documents as $document)
                <div class="py-2 @unless($loop->last) border-bottom @endunless"><div class="fw-semibold"><i class="bi bi-file-earmark-text text-primary me-1"></i>{{ $document->name }}.{{ $document->file_type }}</div><div class="small text-muted-2">{{ $document->humanSize() }} · diupload {{ $document->uploader?->name ?? 'System' }}</div><div class="small text-muted-2 mt-1">Isi file penawaran tidak ditampilkan karena dapat memuat harga.</div></div>
            @empty<x-empty text="Belum ada dokumen." />@endforelse
        </div>
        <div class="card-r"><div class="card-head"><h2>Riwayat Pencatatan</h2></div><div class="table-wrap"><table class="table-r"><thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Status</th><th>Catatan</th></tr></thead><tbody>
            @forelse($quotation->approvalHistories as $history)<tr><td>{{ $history->created_at?->format('d M Y H:i') }}</td><td>{{ $history->user?->name ?? 'System' }}</td><td>{{ $history->actionLabel() }}</td><td>{{ $history->status_to ?: '—' }}</td><td>{{ $history->note ?: '—' }}</td></tr>@empty<tr><td colspan="5"><x-empty text="Belum ada riwayat." /></td></tr>@endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-lg-4"><div class="card-r"><div class="card-head"><h2>Informasi</h2></div><div class="mb-2"><small class="text-muted-2 d-block">Sales Pembuat</small><strong>{{ $quotation->sales?->name ?? '—' }}</strong></div><div class="mb-2"><small class="text-muted-2 d-block">Jenis Penawaran</small><strong>{{ $quotation->creationModeLabel() }}</strong></div><div class="mb-2"><small class="text-muted-2 d-block">Tanggal Penawaran</small><strong>{{ $quotation->quote_date?->format('d M Y') }}</strong></div><div><small class="text-muted-2 d-block">Berlaku Sampai</small><strong>{{ $quotation->valid_until?->format('d M Y') }}</strong></div></div></div>
</div>
@endsection
