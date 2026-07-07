@extends('layouts.app')
@section('title', 'Buat Penawaran Baru')
@section('content')
<div class="sales-ui">
    <div class="sales-page-head">
        <div class="sales-title-wrap"><div class="sales-title-icon"><i class="bi bi-file-earmark-text"></i></div><div><div class="small fw-bold text-primary mb-1">Penawaran &gt; Buat Penawaran Baru</div><h1 class="page-title mb-1">Buat Penawaran Baru</h1><div class="page-subtitle">Buat penawaran berdasarkan spesifikasi & detail dari tim produksi dan drafter.</div></div></div>
        <div class="page-actions"><a href="{{ route('sales.quotations.index') }}" class="btn btn-soft">Batal</a></div>
    </div>
    @include('sales.quotations._form', [
        'quotation' => null,
        'designRequest' => $designRequest ?? null,
        'customers' => $customers ?? collect(),
        'formAction' => route('sales.quotations.store'),
        'formMethod' => 'POST',
        'submitDraftLabel' => 'Simpan Draft',
        'submitApprovalLabel' => 'Simpan & Kirim Penawaran',
    ])
</div>
@endsection
