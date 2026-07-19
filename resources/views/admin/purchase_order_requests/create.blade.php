@extends('layouts.app')
@section('title', 'Request PO Baru')
@section('content')
<x-page-header title="Request PO Baru" subtitle="Buat request monitoring untuk dilanjutkan menjadi PO di Accurate">
    <a href="{{ route('admin.purchase-order-requests.index') }}" class="btn btn-soft btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</x-page-header>

<form method="POST" action="{{ route('admin.purchase-order-requests.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-r">
                <div class="card-head"><h2>Data Penawaran</h2></div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Pilih Penawaran Approved / Customer Setuju *</label>
                    <select name="quotation_id" id="quotationSelect" class="form-select" required>
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
                    <div class="form-text">Data yang tampil adalah penawaran yang sudah approved/dikirim/customer setuju dan belum pernah dibuatkan Request PO.</div>
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
                    <div class="col-md-12"><label class="form-label small fw-semibold">Catatan Sales Admin</label><textarea name="admin_note" rows="4" class="form-control" placeholder="Catatan untuk proses input PO di Accurate">{{ old('admin_note') }}</textarea></div>
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
                    <li>Sales membuat penawaran dari Design Request.</li>
                    <li>SPV approve penawaran.</li>
                    <li>Sales download/kirim PDF ke customer.</li>
                    <li>Sales Admin buat Request PO.</li>
                    <li>PO resmi dibuat di Accurate.</li>
                </ol>
            </div>
            <button class="btn btn-primary w-100 mt-3"><i class="bi bi-save me-1"></i>Simpan Request PO</button>
        </div>
    </div>
</form>
@push('scripts')<script>
document.getElementById('quotationSelect')?.addEventListener('change', function(){
    const option=this.options[this.selectedIndex]; if(!option?.value) return;
    const values={customerName:'customer',customerArea:'area',customerDivision:'division',deliveryAddress:'address',deliveryPic:'pic',deliveryPhone:'phone'};
    Object.entries(values).forEach(([id,key])=>{ const el=document.getElementById(id); if(el && !el.value) el.value=option.dataset[key]||''; });
});
</script>@endpush
@endsection
