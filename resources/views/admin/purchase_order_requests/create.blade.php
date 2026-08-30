@extends('layouts.app')
@section('title', $requestPo ? 'Lanjutkan Draf Request PO' : 'Request PO Baru')
@section('content')
@php
    $isEditingDraft = (bool) $requestPo;
    $defaultSource = $requestPo?->quotation?->isExternal() ? 'external' : 'crm';
    $purchaseSource = old('purchase_source', $defaultSource);
    $value = fn (string $field, $fallback = null) => old($field, $requestPo?->{$field} ?? $fallback);
    $checklistItems = old('checklist')
        ? collect(old('checklist'))
            ->map(fn ($row) => [
                'key' => $row['key'] ?? '',
                'label' => $row['label'] ?? '',
                'checked' => (bool) ($row['checked'] ?? false),
            ])
            ->filter(fn ($row) => trim((string) $row['label']) !== '')
            ->values()
            ->all()
        : ($requestPo ? $requestPo->checklistItems() : (new \App\Models\PurchaseOrderRequest)->checklistItems());
@endphp
<x-page-header
    :title="$isEditingDraft ? 'Lanjutkan Draf '.$requestPo->code : 'Request PO Baru'"
    subtitle="Buat request monitoring untuk dilanjutkan menjadi PO di Accurate">
    <a href="{{ route('admin.purchase-order-requests.index') }}" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</x-page-header>

<form method="POST" action="{{ $isEditingDraft ? route('admin.purchase-order-requests.draft', $requestPo) : route('admin.purchase-order-requests.store') }}" enctype="multipart/form-data">
    @csrf
    @if($isEditingDraft)@method('PUT')@endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-r">
                <div class="card-head"><h2>Sumber Request PO</h2></div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="info-card d-flex gap-2 align-items-start h-100">
                            <input class="form-check-input mt-1" type="radio" name="purchase_source" value="crm" @checked($purchaseSource === 'crm')>
                            <span><strong>Penawaran CRM</strong><small class="d-block text-muted-2 mt-1">Gunakan penawaran yang sudah tersimpan di CRM.</small></span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="info-card d-flex gap-2 align-items-start h-100">
                            <input class="form-check-input mt-1" type="radio" name="purchase_source" value="external" @checked($purchaseSource === 'external')>
                            <span><strong>PO Existing / Non-CRM</strong><small class="d-block text-muted-2 mt-1">Untuk order yang penawarannya dibuat di luar CRM.</small></span>
                        </label>
                    </div>
                </div>

                <div id="crmQuotationFields" class="mb-3">
                    <label class="form-label small fw-semibold">Pilih Penawaran Siap / Customer Setuju <span class="text-danger">*</span></label>
                    <select name="quotation_id" id="quotationSelect" class="form-select">
                            <option value="">Pilih Penawaran</option>
                            @foreach($quotations as $q)
                                <option value="{{ $q->id }}" data-customer="{{ $q->customer_name }}" data-area="{{ $q->customer?->area ?: $q->customer?->city }}" data-division="{{ $q->customer?->division }}" data-address="{{ $q->customer?->address }}" data-pic="{{ $q->customer?->primaryPic?->name ?: $q->pic_name }}" data-phone="{{ $q->customer?->phone }}" @selected(old('quotation_id', $requestPo?->quotation_id ?? $quotation?->id) == $q->id)>
                                    {{ $q->code }} — {{ $q->customer_name }} — {{ $q->project_name }} — {{ \App\Support\Format::rupiah($q->grand_total) }}
                                </option>
                            @endforeach
                            @if($quotation && ! $quotations->contains('id', $quotation->id) && ! $quotation->isExternal())
                                <option value="{{ $quotation->id }}" selected>{{ $quotation->code }} — {{ $quotation->customer_name }} — {{ $quotation->project_name }}</option>
                            @endif
                        </select>
                        <div class="form-text">Hanya penawaran yang belum memiliki Request PO yang ditampilkan.</div>
                </div>

                <div id="externalQuotationFields" class="mb-3 {{ $purchaseSource === 'external' ? '' : 'd-none' }}">
                    <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i>CRM akan membuat catatan penawaran eksternal otomatis agar alur Accurate, Project, dan Invoice tetap terhubung.</div>
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label small fw-semibold">Nama Project / Order <span class="text-danger">*</span></label><input name="external_project_name" value="{{ old('external_project_name', $requestPo?->quotation?->project_name) }}" class="form-control" placeholder="Nama project pada PO customer" required></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">No Penawaran Eksternal</label><input name="external_quotation_number" value="{{ old('external_quotation_number') }}" class="form-control" placeholder="Opsional"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Total Nilai PO <span class="text-danger">*</span></label><input data-rupiah name="external_order_value" value="{{ old('external_order_value', $requestPo?->quotation?->grand_total) }}" class="form-control" inputmode="numeric" placeholder="Rp 0" required><div class="form-text">Masukkan total akhir termasuk pajak jika berlaku.</div></div>
                        @unless(auth()->user()->isSales())
                            <div class="col-md-6"><label class="form-label small fw-semibold">Sales Penanggung Jawab <span class="text-danger">*</span></label><select name="external_sales_id" class="form-select" required><option value="">Pilih Sales</option>@foreach($salesList as $sales)<option value="{{ $sales->id }}" @selected(old('external_sales_id', $requestPo?->quotation?->sales_id) == $sales->id)>{{ $sales->name }}</option>@endforeach</select></div>
                        @endunless
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label small fw-semibold">Nomor Proyek <span class="text-danger">*</span></label><input name="project_number" value="{{ $value('project_number') }}" class="form-control" placeholder="Isi manual" required></div>
                    <div class="col-md-8"><label class="form-label small fw-semibold">Nama Customer <span class="text-danger">*</span></label><input id="customerName" name="customer_name" value="{{ $value('customer_name', $quotation?->customer_name) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Area / Lokasi Customer</label><input id="customerArea" name="customer_area" value="{{ $value('customer_area', $quotation?->customer?->area ?: $quotation?->customer?->city) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Divisi Customer</label><input id="customerDivision" name="customer_division" value="{{ $value('customer_division', $quotation?->customer?->division) }}" class="form-control"></div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Tanggal Request <span class="text-danger">*</span></label>
                        <input type="date" name="request_date" value="{{ old('request_date', $requestPo?->request_date?->format('Y-m-d') ?? date('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">No PO Customer</label>
                        <input name="customer_po_number" value="{{ $value('customer_po_number') }}" class="form-control" placeholder="Jika customer sudah memberi nomor PO">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Upload PO Customer / Lampiran</label>
                        <input type="file" name="customer_po_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                        @if($requestPo?->customer_po_file)
                            <div class="form-text">Lampiran saat ini: <a href="{{ asset('storage/'.$requestPo->customer_po_file) }}" target="_blank">lihat file</a>. Unggah file baru hanya jika ingin mengganti.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-r">
                <div class="card-head"><h2>Data untuk Input Accurate</h2></div>
                <div class="row g-3">
                    <div class="col-md-12"><label class="form-label small fw-semibold">Alamat Pengiriman / Lokasi Project</label><textarea id="deliveryAddress" name="delivery_address" rows="2" class="form-control">{{ $value('delivery_address', $quotation?->customer?->address) }}</textarea></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">PIC Penerima / Project</label><input id="deliveryPic" name="delivery_pic_name" value="{{ $value('delivery_pic_name', $quotation?->customer?->primaryPic?->name ?: $quotation?->pic_name) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">No HP PIC</label><input id="deliveryPhone" name="delivery_pic_phone" value="{{ $value('delivery_pic_phone', $quotation?->customer?->phone) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Nama NPWP / Billing</label><input name="npwp_name" value="{{ $value('npwp_name') }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Nomor NPWP</label><input name="npwp_number" value="{{ $value('npwp_number') }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Termin Pembayaran</label><input name="payment_term" value="{{ $value('payment_term') }}" class="form-control" placeholder="Contoh: DP 50%, Pelunasan 50% sebelum kirim"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Estimasi Tanggal Kirim</label><input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date', $requestPo?->expected_delivery_date?->format('Y-m-d')) }}" class="form-control"></div>
                    <div class="col-md-12"><label class="form-label small fw-semibold">Catatan Internal</label><textarea name="admin_note" rows="4" class="form-control" placeholder="Catatan untuk proses input PO di Accurate">{{ $value('admin_note') }}</textarea></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-r">
                <div class="card-head"><h2>Checklist Kelengkapan</h2></div>
                <x-checklist-editor :items="$checklistItems" id-prefix="chk_create" />
                <div class="form-text">Checklist membantu memastikan data siap sebelum diinput ke Accurate.</div>
            </div>
            <div class="card-r">
                <div class="card-head"><h2>Alur</h2></div>
                <ol class="small mb-0 ps-3">
                    <li>Pilih penawaran CRM atau mode PO Existing / Non-CRM.</li>
                    <li>Lengkapi data customer, PO, dan pengiriman.</li>
                    <li>Ajukan Request PO — atau simpan sebagai draf jika belum lengkap.</li>
                    <li>PO resmi dibuat di Accurate.</li>
                </ol>
                <div class="form-text mt-2">Kolom bertanda <span class="text-danger">*</span> wajib diisi sebelum Request PO diajukan.</div>
            </div>
            <button name="action" value="submit" class="btn btn-primary w-100 mt-3"><i class="bi bi-send me-1"></i>{{ $isEditingDraft ? 'Ajukan Request PO' : 'Simpan & Ajukan Request PO' }}</button>
            <button name="action" value="draft" class="btn btn-soft w-100 mt-2" formnovalidate><i class="bi bi-journal-text me-1"></i>Simpan Draf (Pending)</button>
            <div class="form-text mt-2">Draf tersimpan tanpa validasi kelengkapan dan belum diteruskan ke Accurate.</div>
        </div>
    </div>
</form>
@push('scripts')<script>
const purchaseSourceInputs=document.querySelectorAll('input[name="purchase_source"]');
const crmFields=document.getElementById('crmQuotationFields');
const externalFields=document.getElementById('externalQuotationFields');
const quotationSelect=document.getElementById('quotationSelect');
function syncPurchaseSource(){
    const source=document.querySelector('input[name="purchase_source"]:checked')?.value||'crm';
    crmFields?.classList.toggle('d-none',source!=='crm');
    externalFields?.classList.toggle('d-none',source!=='external');
    if(quotationSelect){quotationSelect.required=source==='crm';quotationSelect.disabled=source!=='crm';}
    externalFields?.querySelectorAll('input,select').forEach(el=>{el.disabled=source!=='external';});
}
purchaseSourceInputs.forEach(el=>el.addEventListener('change',syncPurchaseSource));
syncPurchaseSource();
quotationSelect?.addEventListener('change', function(){
    const option=this.options[this.selectedIndex]; if(!option?.value) return;
    const values={customerName:'customer',customerArea:'area',customerDivision:'division',deliveryAddress:'address',deliveryPic:'pic',deliveryPhone:'phone'};
    Object.entries(values).forEach(([id,key])=>{ const el=document.getElementById(id); if(el && !el.value) el.value=option.dataset[key]||''; });
});
</script>@endpush
@endsection
