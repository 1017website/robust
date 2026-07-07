@extends('layouts.app')
@section('title', 'Edit Penawaran')
@section('content')
<div class="sales-ui">
    <div class="sales-page-head">
        <div class="sales-title-wrap"><a href="{{ route('sales.quotations.show', $quotation) }}" class="btn btn-soft"><i class="bi bi-arrow-left"></i></a><div><div class="small fw-bold text-primary mb-1">Penawaran &gt; Edit</div><h1 class="page-title mb-1">Edit {{ $quotation->code }}</h1><div class="page-subtitle">{{ $quotation->customer_name }} · {{ $quotation->project_name }}</div></div></div>
        <div class="page-actions"><a href="{{ route('sales.quotations.show', $quotation) }}" class="btn btn-soft">Batal</a></div>
    </div>
    @include('sales.quotations._form', [
        'quotation' => $quotation,
        'designRequest' => $designRequest ?? $quotation->designRequest,
        'customers' => $customers ?? collect(),
        'formAction' => route('sales.quotations.update', $quotation),
        'formMethod' => 'PUT',
        'submitDraftLabel' => 'Simpan Revisi sebagai Draft',
        'submitApprovalLabel' => 'Ajukan Ulang ke SPV',
    ])
</div>
@endsection
