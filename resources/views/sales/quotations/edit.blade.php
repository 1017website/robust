@extends('layouts.app')
@section('title', 'Edit Penawaran')
@section('content')
<x-page-header :title="'Edit '.$quotation->code" :subtitle="$quotation->customer_name.' · '.$quotation->project_name">
    <a href="{{ route('sales.quotations.show', $quotation) }}" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</x-page-header>

@include('sales.quotations._form', [
    'quotation' => $quotation,
    'designRequest' => $designRequest ?? $quotation->designRequest,
    'customers' => $customers ?? collect(),
    'formAction' => route('sales.quotations.update', $quotation),
    'formMethod' => 'PUT',
    'submitDraftLabel' => 'Simpan Revisi sebagai Draft',
    'submitApprovalLabel' => 'Ajukan Ulang ke SPV',
])
@endsection
