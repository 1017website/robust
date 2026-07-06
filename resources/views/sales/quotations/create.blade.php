@extends('layouts.app')
@section('title', 'Penawaran Baru')
@section('content')
<x-page-header title="Buat Penawaran" subtitle="Wizard pembuatan quotation dan pengajuan approval SPV" />

@include('sales.quotations._form', [
    'quotation' => null,
    'designRequest' => $designRequest ?? null,
    'customers' => $customers ?? collect(),
    'formAction' => route('sales.quotations.store'),
    'formMethod' => 'POST',
    'submitDraftLabel' => 'Simpan Draft',
    'submitApprovalLabel' => 'Ajukan Approval SPV',
])
@endsection
