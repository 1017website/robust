@php
    $isEdit = filled($quotation?->id);
    $itemsSource = old('items');
    if (! is_array($itemsSource)) {
        if ($isEdit) {
            $itemsSource = $quotation->items->map(fn ($i) => [
                'category' => $i->category,
                'name' => $i->name,
                'specification' => $i->specification,
                'qty' => $i->qty,
                'unit' => $i->unit,
                'unit_price' => $i->unit_price,
                'margin' => $i->margin,
            ])->values()->all();
        } elseif ($designRequest) {
            $itemsSource = $designRequest->items->map(fn ($i) => [
                'category' => $i->category,
                'name' => $i->name,
                'specification' => $i->specification,
                'qty' => $i->qty,
                'unit' => $i->unit,
                'unit_price' => $i->unit_price,
                'margin' => $i->margin,
            ])->values()->all();
        } else {
            $itemsSource = [];
        }
    }

    $costsSource = old('additional_costs', $quotation?->additional_costs ?? []);
    $customerValue = old('customer_name', $quotation?->customer_name ?? $designRequest?->customer_name);
    $picValue = old('pic_name', $quotation?->pic_name ?? $designRequest?->pic_name);
    $projectValue = old('project_name', $quotation?->project_name ?? $designRequest?->project_name);
    $customerIdValue = old('customer_id', $quotation?->customer_id ?? $designRequest?->customer_id);
@endphp

<div class="steps" id="steps">
    <div class="step active" data-step="1"><div class="n">1</div><div class="lbl">Info Dasar</div></div>
    <div class="step" data-step="2"><div class="n">2</div><div class="lbl">Item & Margin</div></div>
    <div class="step" data-step="3"><div class="n">3</div><div class="lbl">Harga</div></div>
    <div class="step" data-step="4"><div class="n">4</div><div class="lbl">Review</div></div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Form belum valid.</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $formAction }}" id="quoteForm">
    @csrf
    @if(($formMethod ?? 'POST') !== 'POST')
        @method($formMethod)
    @endif
    <input type="hidden" name="design_request_id" value="{{ old('design_request_id', $quotation?->design_request_id ?? $designRequest?->id) }}">

    <div class="wizard-pane" data-pane="1">
        <div class="card-r">
            <div class="card-head"><h2>Informasi Dasar</h2></div>
            @if($designRequest)
                <div class="alert alert-info small">
                    Penawaran ini terhubung dengan Design Request <strong>{{ $designRequest->code }}</strong>. Relasi Lead dan Customer akan ikut disimpan untuk tracking pipeline.
                </div>
            @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Link Customer Master</label>
                    <select name="customer_id" class="form-select" id="customerSelect">
                        <option value="">Tidak dihubungkan / customer baru</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" data-name="{{ $customer->name }}" @selected((string)$customerIdValue === (string)$customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Customer *</label><input name="customer_name" value="{{ $customerValue }}" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">PIC</label><input name="pic_name" value="{{ $picValue }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Nama Proyek *</label><input name="project_name" value="{{ $projectValue }}" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Metode Pengiriman *</label>
                    <select name="delivery_method" class="form-select">
                        @foreach(['email'=>'Email','whatsapp'=>'WhatsApp','hardcopy'=>'Hardcopy'] as $k=>$v)
                            <option value="{{ $k }}" @selected(old('delivery_method', $quotation?->delivery_method ?? 'email') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Tanggal Penawaran *</label><input name="quote_date" type="date" value="{{ old('quote_date', optional($quotation?->quote_date)->format('Y-m-d') ?? date('Y-m-d')) }}" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Berlaku Sampai *</label><input name="valid_until" type="date" value="{{ old('valid_until', optional($quotation?->valid_until)->format('Y-m-d') ?? date('Y-m-d', strtotime('+30 days'))) }}" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Prioritas</label>
                    <select name="priority" class="form-select">
                        @foreach(['low'=>'Low','medium'=>'Medium','high'=>'High'] as $k=>$v)
                            <option value="{{ $k }}" @selected(old('priority', $quotation?->priority ?? 'medium') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Mata Uang</label><input name="currency" value="{{ old('currency', $quotation?->currency ?? 'IDR') }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Target Margin (%)</label><input name="target_margin" type="number" step="0.01" value="{{ old('target_margin', $quotation?->target_margin) }}" class="form-control"></div>
                <div class="col-12"><label class="form-label small fw-semibold">Catatan untuk Customer</label><textarea name="customer_note" rows="2" class="form-control">{{ old('customer_note', $quotation?->customer_note) }}</textarea></div>
            </div>
        </div>
        <div class="d-flex justify-content-end"><button type="button" class="btn btn-primary next-step">Lanjut <i class="bi bi-arrow-right ms-1"></i></button></div>
    </div>

    <div class="wizard-pane d-none" data-pane="2">
        <div class="card-r">
            <div class="card-head"><h2>Item Penawaran</h2><button type="button" class="btn btn-soft btn-sm" id="addItem"><i class="bi bi-plus-lg me-1"></i>Tambah Item</button></div>
            <div class="table-wrap">
                <table class="table-r" id="itemTable">
                    <thead><tr><th style="width:18%">Item</th><th>Spesifikasi</th><th style="width:90px">Qty</th><th style="width:85px">Unit</th><th style="width:150px">Harga Satuan</th><th style="width:100px">Margin %</th><th style="width:140px">Total</th><th></th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="form-text mt-2">Margin per item bersifat informasi kontrol untuk SPV. Total tetap dihitung dari Qty x Harga Satuan.</div>
        </div>
        <div class="d-flex justify-content-between"><button type="button" class="btn btn-soft prev-step"><i class="bi bi-arrow-left me-1"></i>Kembali</button><button type="button" class="btn btn-primary next-step">Lanjut <i class="bi bi-arrow-right ms-1"></i></button></div>
    </div>

    <div class="wizard-pane d-none" data-pane="3">
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card-r">
                    <div class="card-head"><h2>Diskon & Pajak</h2></div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label small fw-semibold">Tipe Diskon</label><select name="discount_type" id="discType" class="form-select"><option value="percent" @selected(old('discount_type', $quotation?->discount_type ?? 'percent') === 'percent')>Persen (%)</option><option value="nominal" @selected(old('discount_type', $quotation?->discount_type) === 'nominal')>Nominal (Rp)</option></select></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Nilai Diskon</label><input name="discount_value" id="discValue" type="number" value="{{ old('discount_value', $quotation?->discount_value ?? 0) }}" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">PPN (%)</label><input name="tax_percent" id="taxPercent" type="number" value="{{ old('tax_percent', $quotation?->tax_percent ?? 11) }}" class="form-control"></div>
                        <div class="col-12"><label class="form-label small fw-semibold">Alasan Diskon</label><input name="discount_reason" value="{{ old('discount_reason', $quotation?->discount_reason) }}" class="form-control"></div>
                    </div>
                </div>
                <div class="card-r">
                    <div class="card-head"><h2>Biaya Tambahan</h2><button type="button" class="btn btn-soft btn-sm" id="addCost"><i class="bi bi-plus-lg me-1"></i>Tambah</button></div>
                    <div id="costList"></div>
                </div>
                <div class="card-r"><label class="form-label small fw-semibold">Catatan Internal</label><textarea name="internal_note" rows="2" class="form-control">{{ old('internal_note', $quotation?->internal_note) }}</textarea></div>
            </div>
            <div class="col-lg-5">
                <div class="card-r">
                    <div class="card-head"><h2>Ringkasan Harga</h2></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Subtotal</span><span class="fw-num" id="sumSubtotal">Rp 0</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Diskon</span><span class="fw-num text-danger" id="sumDiscount">- Rp 0</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">PPN</span><span class="fw-num" id="sumTax">Rp 0</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted-2">Biaya Tambahan</span><span class="fw-num" id="sumAdd">Rp 0</span></div>
                    <hr>
                    <div class="d-flex justify-content-between"><strong>Grand Total</strong><strong class="fw-num" id="sumGrand">Rp 0</strong></div>
                    @if($designRequest?->cost_total)
                        <hr>
                        <div class="d-flex justify-content-between small"><span class="text-muted-2">Estimasi Cost Drafter</span><span class="fw-num">{{ \App\Support\Format::rupiah($designRequest->cost_total) }}</span></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between"><button type="button" class="btn btn-soft prev-step"><i class="bi bi-arrow-left me-1"></i>Kembali</button><button type="button" class="btn btn-primary next-step">Lanjut <i class="bi bi-arrow-right ms-1"></i></button></div>
    </div>

    <div class="wizard-pane d-none" data-pane="4">
        <div class="card-r">
            <div class="card-head"><h2>Review Penawaran</h2></div>
            <div id="reviewBox" class="small"></div>
        </div>
        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-soft prev-step"><i class="bi bi-arrow-left me-1"></i>Kembali</button>
            <div class="d-flex gap-2">
                <button type="submit" name="action" value="draft" class="btn btn-soft">{{ $submitDraftLabel }}</button>
                <button type="submit" name="action" value="submit_approval" class="btn btn-primary"><i class="bi bi-send-check me-1"></i>{{ $submitApprovalLabel }}</button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
const itemsData = @json($itemsSource);
const costsData = @json(array_values($costsSource ?? []));
let step = 1, itemIdx = 0, costIdx = 0;
const rupiah = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n||0));
const esc = v => String(v ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));

function showStep(s){
    step = s;
    document.querySelectorAll('.wizard-pane').forEach(p=>p.classList.toggle('d-none', +p.dataset.pane!==s));
    document.querySelectorAll('.step').forEach(el=>{
        const n=+el.dataset.step;
        el.classList.toggle('active', n===s);
        el.classList.toggle('done', n<s);
    });
    if(s===3) recalc();
    if(s===4) buildReview();
}
document.querySelectorAll('.next-step').forEach(b=>b.onclick=()=>{ if(validateStep()) showStep(Math.min(step+1,4)); });
document.querySelectorAll('.prev-step').forEach(b=>b.onclick=()=>showStep(Math.max(step-1,1)));

function validateStep(){
    if(step===1){
        const req = document.querySelectorAll('[data-pane="1"] [required]');
        for(const el of req){ if(!el.value){ el.focus(); el.classList.add('is-invalid'); return false; } el.classList.remove('is-invalid'); }
    }
    if(step===2 && document.querySelectorAll('#itemTable tbody tr').length===0){ alert('Tambahkan minimal 1 item.'); return false; }
    return true;
}

function addItem(data={}){
    const i = itemIdx++;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input name="items[${i}][name]" class="form-control form-control-sm" value="${esc(data.name)}" required>
            <input type="hidden" name="items[${i}][category]" value="${esc(data.category)}"></td>
        <td><input name="items[${i}][specification]" class="form-control form-control-sm" value="${esc(data.specification)}"></td>
        <td><input name="items[${i}][qty]" type="number" step="0.01" min="0.01" class="form-control form-control-sm it-qty" value="${data.qty||1}"></td>
        <td><input name="items[${i}][unit]" class="form-control form-control-sm" value="${esc(data.unit || 'Unit')}"></td>
        <td><input name="items[${i}][unit_price]" type="number" min="0" class="form-control form-control-sm it-price" value="${data.unit_price||0}"></td>
        <td><input name="items[${i}][margin]" type="number" step="0.01" min="0" class="form-control form-control-sm" value="${data.margin||0}"></td>
        <td class="fw-num it-total">Rp 0</td>
        <td><button type="button" class="btn btn-sm btn-soft text-danger it-del"><i class="bi bi-x"></i></button></td>`;
    document.querySelector('#itemTable tbody').appendChild(tr);
    tr.querySelectorAll('.it-qty,.it-price').forEach(el=>el.addEventListener('input',()=>{ rowTotal(tr); recalc(); }));
    tr.querySelector('.it-del').onclick=()=>{ tr.remove(); recalc(); };
    rowTotal(tr);
}
function rowTotal(tr){
    const q=+tr.querySelector('.it-qty').value||0, p=+tr.querySelector('.it-price').value||0;
    tr.querySelector('.it-total').textContent = rupiah(q*p);
}
document.getElementById('addItem').onclick=()=>addItem();
if(itemsData.length){ itemsData.forEach(addItem); } else { addItem(); }

function addCost(data={}){
    const i = costIdx++;
    const div = document.createElement('div');
    div.className='row g-2 mb-2';
    div.innerHTML=`
        <div class="col-7"><input name="additional_costs[${i}][label]" class="form-control form-control-sm" placeholder="mis. Pengiriman" value="${esc(data.label)}"></div>
        <div class="col-4"><input name="additional_costs[${i}][amount]" type="number" class="form-control form-control-sm cost-amt" placeholder="0" value="${data.amount||0}"></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-soft text-danger cost-del"><i class="bi bi-x"></i></button></div>`;
    document.getElementById('costList').appendChild(div);
    div.querySelector('.cost-amt').addEventListener('input',recalc);
    div.querySelector('.cost-del').onclick=()=>{ div.remove(); recalc(); };
}
document.getElementById('addCost').onclick=()=>{ addCost(); };
if(costsData.length){ costsData.forEach(addCost); }

['discType','discValue','taxPercent'].forEach(id=>document.getElementById(id).addEventListener('input',recalc));
const customerSelect = document.getElementById('customerSelect');
customerSelect?.addEventListener('change', function(){
    const opt = this.options[this.selectedIndex];
    if(opt?.dataset?.name && !document.querySelector('[name="customer_name"]').value){
        document.querySelector('[name="customer_name"]').value = opt.dataset.name;
    }
});

function recalc(){
    let sub=0;
    document.querySelectorAll('#itemTable tbody tr').forEach(tr=>{
        sub += (+tr.querySelector('.it-qty').value||0)*(+tr.querySelector('.it-price').value||0);
    });
    const dType=document.getElementById('discType').value, dVal=+document.getElementById('discValue').value||0;
    let disc = dType==='percent' ? sub*dVal/100 : dVal; disc=Math.min(disc,sub);
    const afterDisc = sub-disc;
    const tax = afterDisc*(+document.getElementById('taxPercent').value||0)/100;
    let add=0; document.querySelectorAll('.cost-amt').forEach(el=>add+=+el.value||0);
    const grand = afterDisc+tax+add;
    document.getElementById('sumSubtotal').textContent=rupiah(sub);
    document.getElementById('sumDiscount').textContent='- '+rupiah(disc);
    document.getElementById('sumTax').textContent=rupiah(tax);
    document.getElementById('sumAdd').textContent=rupiah(add);
    document.getElementById('sumGrand').textContent=rupiah(grand);
}

function buildReview(){
    recalc();
    const g=v=>document.querySelector(`[name="${v}"]`)?.value||'-';
    let rows='';
    document.querySelectorAll('#itemTable tbody tr').forEach(tr=>{
        const n=tr.querySelector('[name$="[name]"]').value, q=tr.querySelector('.it-qty').value, p=+tr.querySelector('.it-price').value||0;
        rows+=`<tr><td>${esc(n)}</td><td>${q}</td><td class="fw-num">${rupiah(p)}</td><td class="fw-num">${rupiah(q*p)}</td></tr>`;
    });
    document.getElementById('reviewBox').innerHTML=`
        <div class="row g-2 mb-3">
            <div class="col-md-4"><div class="text-muted-2">Customer</div><div class="fw-semibold">${esc(g('customer_name'))}</div></div>
            <div class="col-md-4"><div class="text-muted-2">Proyek</div><div class="fw-semibold">${esc(g('project_name'))}</div></div>
            <div class="col-md-4"><div class="text-muted-2">Berlaku s/d</div><div class="fw-semibold">${esc(g('valid_until'))}</div></div>
        </div>
        <table class="table-r"><thead><tr><th>Item</th><th>Qty</th><th>Harga</th><th>Total</th></tr></thead><tbody>${rows}</tbody></table>
        <div class="d-flex justify-content-end mt-3"><div style="min-width:240px">
            <div class="d-flex justify-content-between"><span class="text-muted-2">Subtotal</span><span class="fw-num">${document.getElementById('sumSubtotal').textContent}</span></div>
            <div class="d-flex justify-content-between"><span class="text-muted-2">Diskon</span><span class="fw-num">${document.getElementById('sumDiscount').textContent}</span></div>
            <div class="d-flex justify-content-between"><span class="text-muted-2">PPN</span><span class="fw-num">${document.getElementById('sumTax').textContent}</span></div>
            <div class="d-flex justify-content-between"><span class="text-muted-2">Biaya Tambahan</span><span class="fw-num">${document.getElementById('sumAdd').textContent}</span></div>
            <hr class="my-1"><div class="d-flex justify-content-between"><strong>Grand Total</strong><strong class="fw-num">${document.getElementById('sumGrand').textContent}</strong></div>
        </div></div>`;
}
recalc();
</script>
@endpush
