@extends('layouts.app')
@section('title','Invoice')
@section('content')
<x-page-header title="Invoice" subtitle="Monitoring invoice proyek dan pembayaran multi-termin" />
<div class="card-r">
    <form method="GET" class="filter-bar"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari invoice, customer, nomor proyek..."><select name="status" class="form-select"><option value="">Semua Status</option>@foreach(\App\Models\Invoice::statuses() as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>@endforeach</select><button class="btn btn-soft">Filter</button></form>
    <div class="table-wrap"><table class="table-r"><thead><tr><th>No Invoice</th><th>No Proyek</th><th>Customer</th><th>Project</th><th>Nilai</th><th>Terbayar</th><th>Saldo</th><th>Termin</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($invoices as $invoice)<tr><td class="fw-semibold">{{ $invoice->code }}</td><td>{{ $invoice->project_number ?: '—' }}</td><td>{{ $invoice->customer_name }}</td><td>{{ $invoice->project_name }}</td><td class="fw-num">{{ \App\Support\Format::rupiah($invoice->grand_total) }}</td><td class="fw-num">{{ \App\Support\Format::rupiah($invoice->paid_total) }}</td><td class="fw-num">{{ \App\Support\Format::rupiah($invoice->balance()) }}</td><td>{{ $invoice->terms->count() }}x</td><td><x-status-badge :status="$invoice->status" :label="\App\Models\Invoice::statuses()[$invoice->status] ?? $invoice->status" /></td><td><a href="{{ route('admin.invoices.show',$invoice) }}" class="btn btn-sm btn-soft">Detail</a></td></tr>
    @empty<tr><td colspan="10"><x-empty text="Belum ada invoice. Invoice dibuat dari detail Request PO." /></td></tr>@endforelse
    </tbody></table></div><div class="mt-3">{{ $invoices->links() }}</div>
</div>
@endsection
