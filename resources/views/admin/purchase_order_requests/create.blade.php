@extends('layouts.app')
@section('title', 'Request PO Baru')
@section('content')
@php($purchaseSource = old('purchase_source', 'crm'))
<x-page-header title="Request PO Baru" subtitle="Buat request monitoring untuk dilanjutkan menjadi PO di Accurate">
    <a href="{{ route('admin.purchase-order-requests.index') }}" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</x-page-header>

<form method="POST" action="{{ route('admin.purchase-order-requests.store') }}" enctype="multipart/form-data">
    @csrf
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
                    <label class="form-label small fw-semibold">Pilih Penawaran Siap / Customer Setuju *</label>
                    <select name="quotation_id" id="quotationSelect" class="form-select">
                            <option value="">Pilih Penawaran</option>
                            @foreach($quotations as $q)
                                <option value="{{ $q->id }}" data-customer="{{ $q->customer_name }}" data-area="{{ $q->customer?->area ?: $q->customer?->city }}" data-division="{{ $q->customer?->division }}" data-address="{{ $q->customer?->address }}" data-pic="{{ $q->customer?->primaryPic?->name ?: $q->pic_name }}" data-phone="{{ $q->customer?->phone }}" @selected(old('quotation_id', $quotation?->id) == $q->id)>
                                    {{ $q->code }} — {{ $q->customer_name }} — {{ $q->project_name }} — {{ \App\Support\Format::rupiah($q->grand_total) }}
                                </option>
                            @endforeach
                            @if($quotation && ! $quotations->contains('id', $quotation->id))
                                <option value="{{ $quotation->id }}" selected>{{ $quotation->code }} — {{ $quotation->customer_name }} — {{ $quotation->project_name }}</option>
                            @endif
                        </select>
                        <div class="form-text">Hanya penawaran yang belum memiliki Request PO yang ditampilkan.</div>
                </div>

                <div id="externalQuotationFields" class="mb-3 {{ $purchaseSource === 'external' ? '' : 'd-none' }}">
                    <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-1"></i>CRM akan membuat catatan penawaran eksternal otomatis agar alur Accurate, Project, dan Invoice tetap terhubung.</div>
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label small fw-semibold">Nama Project / Order *</label><input name="external_project_name" value="{{ old('external_project_name') }}" class="form-control" placeholder="Nama project pada PO customer"></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">No Penawaran Eksternal</label><input name="external_quotation_number" value="{{ old('external_quotation_number') }}" class="form-control" placeholder="Opsional"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Total Nilai PO *</label><input data-rupiah name="external_order_value" value="{{ old('external_order_value') }}" class="form-control" inputmode="numeric" placeholder="Rp 0"><div class="form-text">Masukkan total akhir termasuk pajak jika berlaku.</div></div>
                        @unless(auth()->user()->isSales())
                            <div class="col-md-6"><label class="form-label small fw-semibold">Sales Penanggung Jawab *</label><select name="external_sales_id" class="form-select"><option value="">Pilih Sales</option>@foreach($salesList as $sales)<option value="{{ $sales->id }}" @selected(old('external_sales_id') == $sales->id)>{{ $sales->name }}</option>@endforeach</select></div>
                        @endunless
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label small fw-semibold">Nomor Proyek *</label><input name="project_number" value="{{ old('project_number') }}" class="form-control" placeholder="Isi manual" required></div>
                    <div class="col-md-8"><label class="form-label small fw-semibold">Nama Customer *</label><input id="customerName" name="customer_name" value="{{ old('customer_name',$quotation?->customer_name) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Area / Lokasi Customer</label><input id="customerArea" name="customer_area" value="{{ old('customer_area',$quotation?->customer?->area ?: $quotation?->customer?->city) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Divisi Customer</label><input id="customerDivision" name="customer_division" value="{{ old('customer_division',$quotation?->customer?->division) }}" class="form-control"></div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Tanggal Request *</label>
                        <input type="date" name="request_date" value="{{ old('request_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">No PO Customer</label>
                        <input name="customer_po_number" value="{{ old('customer_po_number') }}" class="form-control" placeholder="Jika customer sudah memberi nomor PO">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Upload PO Customer / Lampiran</label>
                        <input type="file" name="customer_po_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                    </div>
                </div>
            </div>

            <div class="card-r">
                <div class="card-head"><h2>Data untuk Input Accurate</h2></div>
                <div class="row g-3">
                    <div class="col-md-12"><label class="form-label small fw-semibold">Alamat Pengiriman / Lokasi Project</label><textarea id="deliveryAddress" name="delivery_address" rows="2" class="form-control">{{ old('delivery_address',$quotation?->customer?->address) }}</textarea></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">PIC Penerima / Project</label><input id="deliveryPic" name="delivery_pic_name" value="{{ old('delivery_pic_name',$quotation?->customer?->primaryPic?->name ?: $quotation?->pic_name) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">No HP PIC</label><input id="deliveryPhone" name="delivery_pic_phone" value="{{ old('delivery_pic_phone',$quotation?->customer?->phone) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Nama NPWP / Billing</label><input name="npwp_name" value="{{ old('npwp_name') }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Nomor NPWP</label><input name="npwp_number" value="{{ old('npwp_number') }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Termin Pembayaran</label><input name="payment_term" value="{{ old('payment_term') }}" class="form-control" placeholder="Contoh: DP 50%, Pelunasan 50% sebelum kirim"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Estimasi Tanggal Kirim</label><input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}" class="form-control"></div>
                    <div class="col-md-12"><label class="form-label small fw-semibold">Catatan Internal</label><textarea name="admin_note" rows="4" class="form-control" placeholder="Catatan untuk proses input PO di Accurate">{{ old('admin_note') }}</textarea></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-r">
                <div class="card-head"><h2>Checklist Kelengkapan</h2></div>
                @foreach(\App\Models\PurchaseOrderRequest::checklistItems() as $key => $label)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="checklist[{{ $key }}]" value="1" id="chk_{{ $key }}" @checked(old('checklist.'.$key))>
                        <label class="form-check-label small" for="chk_{{ $key }}">{{ $label }}</label>
                    </div>
                @endforeach
                <div class="form-text">Checklist membantu memastikan data siap sebelum diinput ke Accurate.</div>
            </div>
            <div class="card-r">
                <div class="card-head"><h2>Alur</h2></div>
                <ol class="small mb-0 ps-3">
                    <li>Pilih penawaran CRM atau mode PO Existing / Non-CRM.</li>
                    <li>Lengkapi data customer, PO, dan pengiriman.</li>
                    <li>Simpan Request PO.</li>
                    <li>PO resmi dibuat di Accurate.</li>
                </ol>
            </div>
            <button class="btn btn-primary w-100 mt-3"><i class="bi bi-save me-1"></i>Simpan Request PO</button>
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
    externalFields?.querySelectorAll('input,select').forEach(el=>{
        el.disabled=source!=='external';
        if(['external_project_name','external_order_value','external_sales_id'].includes(el.name)) el.required=source==='external';
    });
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
