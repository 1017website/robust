@extends('layouts.app')
@section('title', 'Detail Request PO')
@section('content')
@php($progress = $requestPo->checklistProgress())
<x-page-header :title="$requestPo->code" :subtitle="($requestPo->quotation?->customer_name ?: 'Customer').' · '.($requestPo->quotation?->project_name ?: 'Project')">
    <a href="{{ route('admin.purchase-order-requests.index') }}" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</x-page-header>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-r">
            <div class="card-head"><h2>Data Penawaran</h2><x-status-badge :status="$requestPo->status" :label="\App\Models\PurchaseOrderRequest::statuses()[$requestPo->status] ?? $requestPo->status" /></div>
            <div class="row g-3 small">
                <div class="col-md-4"><div class="text-muted-2">No Penawaran</div><div class="fw-semibold">{{ $requestPo->quotation?->code ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted-2">Customer</div><div class="fw-semibold">{{ $requestPo->quotation?->customer_name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted-2">Sales</div><div class="fw-semibold">{{ $requestPo->quotation?->sales?->name ?: '—' }}</div></div>
                <div class="col-md-8"><div class="text-muted-2">Project</div><div class="fw-semibold">{{ $requestPo->quotation?->project_name ?: '—' }}</div></div>
                <div class="col-md-4"><div class="text-muted-2">Nilai Penawaran</div><div class="fw-semibold fw-num">{{ \App\Support\Format::rupiah($requestPo->quotation?->grand_total ?? 0) }}</div></div>
            </div>
        </div>

        <div class="card-r">
            <div class="card-head"><h2>Data Input Accurate</h2></div>
            <form method="POST" action="{{ route('admin.purchase-order-requests.update', $requestPo) }}">
                @csrf @method('PUT')
                <input type="hidden" name="status" value="{{ $requestPo->status }}">
                <input type="hidden" name="accurate_po_number" value="{{ $requestPo->accurate_po_number }}">
                <input type="hidden" name="accurate_po_date" value="{{ $requestPo->accurate_po_date?->format('Y-m-d') }}">
                <input type="hidden" name="accurate_note" value="{{ $requestPo->accurate_note }}">
                <div class="row g-3">
                    <div class="col-md-12"><label class="form-label small fw-semibold">Alamat Pengiriman / Lokasi Project</label><textarea name="delivery_address" rows="2" class="form-control">{{ old('delivery_address', $requestPo->delivery_address) }}</textarea></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">PIC Penerima / Project</label><input name="delivery_pic_name" value="{{ old('delivery_pic_name', $requestPo->delivery_pic_name) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">No HP PIC</label><input name="delivery_pic_phone" value="{{ old('delivery_pic_phone', $requestPo->delivery_pic_phone) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Nama NPWP / Billing</label><input name="npwp_name" value="{{ old('npwp_name', $requestPo->npwp_name) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Nomor NPWP</label><input name="npwp_number" value="{{ old('npwp_number', $requestPo->npwp_number) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Termin Pembayaran</label><input name="payment_term" value="{{ old('payment_term', $requestPo->payment_term) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Estimasi Tanggal Kirim</label><input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date', $requestPo->expected_delivery_date?->format('Y-m-d')) }}" class="form-control"></div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Checklist Kelengkapan</strong>
                    <span class="pill">{{ $progress['done'] }}/{{ $progress['total'] }} selesai</span>
                </div>
                @foreach(\App\Models\PurchaseOrderRequest::checklistItems() as $key => $label)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="checklist[{{ $key }}]" value="1" id="chk_{{ $key }}" @checked(old('checklist.'.$key, $requestPo->checklist[$key] ?? false))>
                        <label class="form-check-label small" for="chk_{{ $key }}">{{ $label }}</label>
                    </div>
                @endforeach
                <button class="btn btn-primary mt-2"><i class="bi bi-save me-1"></i>Simpan Data & Checklist</button>
            </form>
        </div>

        <div class="card-r">
            <div class="card-head"><h2>Item Penawaran</h2></div>
            <div class="table-wrap">
                <table class="table-r">
                    <thead><tr><th>Item</th><th>Spesifikasi</th><th>Qty</th><th>Total</th></tr></thead>
                    <tbody>
                    @foreach($requestPo->quotation?->items ?? [] as $it)
                        <tr>
                            <td class="fw-semibold">{{ $it->name }}</td>
                            <td class="small">{{ $it->specification ?: '—' }}</td>
                            <td>{{ rtrim(rtrim(number_format($it->qty,2),'0'),'.') }} {{ $it->unit }}</td>
                            <td class="fw-num">{{ \App\Support\Format::rupiah($it->total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-r">
            <div class="card-head"><h2>Update Accurate</h2></div>
            <form method="POST" action="{{ route('admin.purchase-order-requests.update', $requestPo) }}">
                @csrf @method('PUT')
                @foreach(($requestPo->checklist ?? []) as $key => $checked)
                    @if($checked)<input type="hidden" name="checklist[{{ $key }}]" value="1">@endif
                @endforeach
                <input type="hidden" name="delivery_address" value="{{ $requestPo->delivery_address }}">
                <input type="hidden" name="delivery_pic_name" value="{{ $requestPo->delivery_pic_name }}">
                <input type="hidden" name="delivery_pic_phone" value="{{ $requestPo->delivery_pic_phone }}">
                <input type="hidden" name="npwp_name" value="{{ $requestPo->npwp_name }}">
                <input type="hidden" name="npwp_number" value="{{ $requestPo->npwp_number }}">
                <input type="hidden" name="payment_term" value="{{ $requestPo->payment_term }}">
                <input type="hidden" name="expected_delivery_date" value="{{ $requestPo->expected_delivery_date?->format('Y-m-d') }}">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status *</label>
                    <select name="status" class="form-select" required>
                        @foreach(\App\Models\PurchaseOrderRequest::statuses() as $k=>$v)
                            <option value="{{ $k }}" @selected($requestPo->status === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">No PO Accurate</label><input name="accurate_po_number" value="{{ old('accurate_po_number', $requestPo->accurate_po_number) }}" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Tanggal PO Accurate</label><input type="date" name="accurate_po_date" value="{{ old('accurate_po_date', $requestPo->accurate_po_date?->format('Y-m-d')) }}" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Catatan Accurate</label><textarea name="accurate_note" rows="3" class="form-control">{{ old('accurate_note', $requestPo->accurate_note) }}</textarea></div>
                <button class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Update Status Accurate</button>
            </form>
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Progress Checklist</h2></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Kelengkapan</span><strong>{{ $progress['percent'] }}%</strong></div>
            <div class="progress" style="height:8px"><div class="progress-bar" style="width: {{ $progress['percent'] }}%"></div></div>
            @if($requestPo->checklist_completed_at)
                <div class="form-text mt-2">Lengkap pada {{ $requestPo->checklist_completed_at->format('d M Y H:i') }}</div>
            @endif
        </div>
        <div class="card-r">
            <div class="card-head"><h2>Info Request</h2></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Tanggal Request</span><span class="fw-semibold">{{ $requestPo->request_date?->format('d M Y') }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">Dibuat oleh</span><span class="fw-semibold">{{ $requestPo->requester?->name ?: '—' }}</span></div>
            <div class="mb-2 d-flex justify-content-between"><span class="text-muted-2">No PO Customer</span><span class="fw-semibold">{{ $requestPo->customer_po_number ?: '—' }}</span></div>
            @if($requestPo->customer_po_file)
                <a href="{{ asset('storage/'.$requestPo->customer_po_file) }}" target="_blank" class="btn btn-soft btn-sm w-100"><i class="bi bi-paperclip me-1"></i>Lihat Lampiran</a>
            @endif
            @if($requestPo->admin_note)
                <hr><div class="small"><div class="text-muted-2">Catatan</div>{{ $requestPo->admin_note }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
