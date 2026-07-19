@extends('layouts.app')
@section('title', $designRequest->code)
@section('content')
@php
    $scope = $designRequest->scope_checklist ?? [];
    $outputs = $designRequest->outputs ?? [];
    $dimensions = $designRequest->dimensions ?: [['item'=>'','size'=>'']];
    $materials = $designRequest->materials ?: [['item'=>'','material'=>'','finish'=>'']];
    $accessories = $designRequest->accessories ?: [''];
    $estimations = $designRequest->material_estimation ?: [['material'=>'','qty'=>'']];
    $costTotal = (float) $designRequest->cost_material + (float) $designRequest->cost_production + (float) $designRequest->cost_installation;
    $notes = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $designRequest->technical_note ?? '')));
    $currentDocs = $designRequest->documents->where('is_current', true);
    $hasPrePo = $designRequest->quotations->contains(fn($quotation) => $quotation->purchaseOrderRequest !== null);
@endphp
<div class="drafter-ui">
    <div class="drafter-request-head">
        <div>
            <div class="crumb"><a href="{{ route('drafter.design-requests.index') }}">Design Request</a> <i class="bi bi-chevron-right"></i> {{ $designRequest->code }}</div>
            <h1>{{ $designRequest->code }} <x-status-badge :status="$designRequest->status" /></h1>
            <p>{{ $designRequest->project_name }} - {{ $designRequest->customer_name }}</p>
        </div>
        <div class="page-actions"><a href="{{ route('drafter.design-requests.index') }}" class="btn btn-soft"><i class="bi bi-arrow-left me-1"></i>Kembali</a></div>
    </div>

    <nav class="d-tabs big" aria-label="Bagian design request"><a href="#brief" class="active"><i class="bi bi-shield-check me-1"></i>Detail Request</a><a href="#feedback"><i class="bi bi-sliders me-1"></i>Spesifikasi & Feedback</a><a href="#documents"><i class="bi bi-folder2-open me-1"></i>Dokumen</a><a href="#history"><i class="bi bi-clock-history me-1"></i>Riwayat</a></nav>

    <form method="POST" action="{{ route('drafter.design-requests.feedback',$designRequest) }}" class="drafter-workspace">
        @csrf
        <fieldset @disabled(auth()->user()->isDrafter()) style="display:contents">
        <aside class="left-brief" id="brief">
            <div class="info-card"><h6>Request dari Sales</h6><div class="mini-panel"><strong>Kebutuhan Customer</strong><ul class="check-list mt-2">@forelse($scope as $s)<li>{{ $s }}</li>@empty<li>{{ $designRequest->detail_need ?: 'Belum ada kebutuhan detail.' }}</li>@endforelse</ul></div><div class="note-box mt-3"><strong>Catatan Sales</strong><br>{{ $designRequest->extra_note ?: $designRequest->short_description ?: 'Tidak ada catatan.' }}</div></div>
            <div class="info-card"><h6>Lampiran dari Sales</h6>@forelse($designRequest->documents->take(4) as $doc)<div class="doc-mini"><i class="bi bi-file-earmark-pdf text-danger"></i><span>{{ $doc->name }}<small>{{ $doc->humanSize() }}</small></span></div>@empty<div class="small text-muted-2">Belum ada lampiran.</div>@endforelse</div>
            <div class="info-card"><h6>Informasi Umum</h6><div class="d-info-grid one"><span>Sales</span><strong>{{ $designRequest->sales?->name ?? '—' }}</strong><span>Tanggal Request</span><strong>{{ $designRequest->request_date?->format('d M Y') ?? $designRequest->created_at->format('d M Y') }}</strong><span>Deadline Request</span><strong>{{ $designRequest->deadline?->format('d M Y') ?? '—' }}</strong><span>Prioritas</span><strong>{{ ucfirst($designRequest->priority) }}</strong><span>PIC Customer</span><strong>{{ $designRequest->pic_name ?: '—' }}</strong></div></div>
        </aside>

        <main class="center-feedback" id="feedback">
            <div class="info-card"><div class="card-head"><h2>Spesifikasi & Costing dari Produksi</h2></div>
                <div class="feedback-grid two">
                    <div class="spec-card"><div class="spec-head"><strong>1. Dimensi Utama</strong></div><div class="table-wrap"><table class="table-r compact"><thead><tr><th>Item</th><th>Ukuran (P x L x T)</th></tr></thead><tbody>@foreach($dimensions as $i=>$row)<tr><td><input name="dimensions[{{ $i }}][item]" value="{{ $row['item'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent fw-semibold"></td><td><input name="dimensions[{{ $i }}][size]" value="{{ $row['size'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent"></td></tr>@endforeach</tbody></table></div></div>
                    <div class="spec-card"><div class="spec-head"><strong>2. Material & Finishing</strong></div><div class="table-wrap"><table class="table-r compact"><thead><tr><th>Item</th><th>Material</th><th>Finishing / Warna</th></tr></thead><tbody>@foreach($materials as $i=>$row)<tr><td><input name="materials[{{ $i }}][item]" value="{{ $row['item'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent fw-semibold"></td><td><input name="materials[{{ $i }}][material]" value="{{ $row['material'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent"></td><td><input name="materials[{{ $i }}][finish]" value="{{ $row['finish'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent"></td></tr>@endforeach</tbody></table></div></div>
                    <div class="spec-card"><div class="spec-head"><strong>3. Accessories / Perlengkapan</strong></div><div class="accessory-list">@foreach($accessories as $i=>$item)<label><i class="bi bi-check-circle text-success"></i><input name="accessories[{{ $i }}]" value="{{ is_array($item) ? ($item['name'] ?? '') : $item }}" class="form-control form-control-sm border-0 bg-transparent"></label>@endforeach</div></div>
                    <div class="spec-card"><div class="spec-head"><strong>4. Estimasi Material</strong></div><div class="table-wrap"><table class="table-r compact"><thead><tr><th>Material</th><th>Qty / Estimasi</th></tr></thead><tbody>@foreach($estimations as $i=>$row)<tr><td><input name="material_estimation[{{ $i }}][material]" value="{{ $row['material'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent"></td><td><input name="material_estimation[{{ $i }}][qty]" value="{{ $row['qty'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent"></td></tr>@endforeach</tbody></table></div></div>
                    <div class="spec-card"><div class="spec-head"><strong>5. Estimasi Costing Awal</strong></div><div class="cost-list"><label>Material <input name="cost_material" type="text" inputmode="numeric" data-rupiah value="{{ old('cost_material', (float) $designRequest->cost_material) }}" class="form-control form-control-sm"></label><label>Produksi <input name="cost_production" type="text" inputmode="numeric" data-rupiah value="{{ old('cost_production', (float) $designRequest->cost_production) }}" class="form-control form-control-sm"></label><label>Instalasi <input name="cost_installation" type="text" inputmode="numeric" data-rupiah value="{{ old('cost_installation', (float) $designRequest->cost_installation) }}" class="form-control form-control-sm"></label><div class="total">Total Estimasi <strong>{{ \App\Support\Format::rupiah($costTotal) }}</strong></div></div></div>
                </div>
            </div>

            <div class="info-card mt-3" id="documents"><div class="card-head"><h2>6. Drawing & Dokumen</h2></div><div class="doc-chip-row">@forelse($currentDocs as $doc)<div class="doc-chip"><i class="bi bi-file-earmark-pdf"></i><span>{{ $doc->name }}<small>{{ $doc->revisionLabel() }} · {{ str($doc->category)->headline() }} · {{ $doc->humanSize() }}</small></span><a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" class="btn btn-sm btn-link"><i class="bi bi-download"></i></a></div>@empty<div class="small text-muted-2">Belum ada drawing atau dokumen.</div>@endforelse</div></div>

            <div class="info-card mt-3"><div class="card-head"><h2>Item Hasil untuk Penawaran</h2><button type="button" class="btn btn-soft btn-sm" id="addRow"><i class="bi bi-plus-lg me-1"></i>Tambah Item</button></div><div class="table-wrap"><table class="table-r compact" id="itemTable"><thead><tr><th>Kategori</th><th>Nama Item</th><th>Spesifikasi</th><th>Qty</th><th>Unit</th><th>Harga Satuan</th><th></th></tr></thead><tbody>@forelse($designRequest->items as $i => $it)<tr><td><input name="items[{{ $i }}][category]" value="{{ $it->category }}" class="form-control form-control-sm"></td><td><input name="items[{{ $i }}][name]" value="{{ $it->name }}" class="form-control form-control-sm"></td><td><input name="items[{{ $i }}][specification]" value="{{ $it->specification }}" class="form-control form-control-sm"></td><td><input name="items[{{ $i }}][qty]" type="text" inputmode="decimal" data-qty value="{{ $it->qty }}" class="form-control form-control-sm"></td><td><input name="items[{{ $i }}][unit]" value="{{ $it->unit }}" class="form-control form-control-sm"></td><td><input name="items[{{ $i }}][unit_price]" type="text" inputmode="numeric" data-rupiah value="{{ $it->unit_price }}" class="form-control form-control-sm"></td><td><button type="button" class="btn btn-sm btn-soft text-danger row-del"><i class="bi bi-x"></i></button></td></tr>@empty<tr><td><input name="items[0][category]" value="" class="form-control form-control-sm"></td><td><input name="items[0][name]" value="" class="form-control form-control-sm"></td><td><input name="items[0][specification]" value="" class="form-control form-control-sm"></td><td><input name="items[0][qty]" type="text" inputmode="decimal" data-qty value="1" class="form-control form-control-sm"></td><td><input name="items[0][unit]" value="Unit" class="form-control form-control-sm"></td><td><input name="items[0][unit_price]" type="text" inputmode="numeric" data-rupiah value="0" class="form-control form-control-sm"></td><td></td></tr>@endforelse</tbody></table></div></div>
        </main>

        <aside class="right-status">
            <div class="info-card status-ready"><h6>Kelengkapan Feedback</h6><ul class="check-list"><li>Dimensi: {{ collect($dimensions)->filter(fn($row) => filled($row['item'] ?? null))->count() }} item</li><li>Material: {{ collect($materials)->filter(fn($row) => filled($row['material'] ?? null))->count() }} item</li><li>Dokumen: {{ $designRequest->documents->count() }} file</li><li>Costing: {{ \App\Support\Format::rupiah($costTotal) }}</li></ul><div class="ready-box"><i class="bi bi-info-circle"></i><strong>Status {{ \App\Models\DesignRequest::statuses()[$designRequest->status] ?? \Illuminate\Support\Str::headline($designRequest->status) }}</strong><small>Lengkapi data yang diperlukan sebelum submit final ke sales.</small></div></div>
            <div class="info-card"><h6>Catatan Teknis</h6><textarea name="technical_note" class="form-control" rows="7" placeholder="Catatan teknis untuk sales...">{{ $designRequest->technical_note }}</textarea></div>
            <div class="info-card" id="history"><h6>Log Aktivitas</h6><div class="d-timeline small">@foreach([$designRequest->updated_at,$designRequest->created_at] as $date)<div><time>{{ $date->format('d M H:i') }}</time><span></span><p><strong>{{ $designRequest->productionPic?->name ?? auth()->user()->name }}</strong><small>Update {{ $designRequest->status }}</small></p></div>@endforeach</div></div>
        </aside>

        @if(auth()->user()->isProduction())<div class="submit-bar"><a href="{{ route('drafter.design-requests.index') }}" class="btn btn-soft"><i class="bi bi-arrow-left me-1"></i>Kembali</a><div class="ms-auto d-flex flex-wrap gap-2 submit-actions"><button type="submit" name="action" value="save" class="btn btn-soft">Simpan Progress</button><button type="submit" name="action" value="review" class="btn btn-warning">Kirim untuk Review</button><button type="submit" name="action" value="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit Final ke Sales</button></div></div>@endif
        </fieldset>
    </form>

    @if(auth()->user()->isDrafter())
    <div class="info-card mt-3" id="upload">
        <div class="card-head"><h2>Upload / Revisi Drawing & Dokumen</h2><span class="status-soft {{ $hasPrePo ? 'st-green' : 'st-yellow' }}">{{ $hasPrePo ? 'Pra PO tersedia' : 'Menunggu Pra PO' }}</span></div>
        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <input type="hidden" name="documentable_type" value="{{ \App\Models\DesignRequest::class }}"><input type="hidden" name="documentable_id" value="{{ $designRequest->id }}">
            <div class="col-md-3"><label class="form-label small fw-bold">Jenis *</label><select name="category" class="form-select" required><option value="request_drawing">Gambar sesuai Request Sales</option><option value="fabrication_drawing" @disabled(!$hasPrePo)>Gambar Fabrikasi{{ !$hasPrePo ? ' (setelah Pra PO)' : '' }}</option><option value="supporting_document">Dokumen Pendukung</option></select></div>
            <div class="col-md-3"><label class="form-label small fw-bold">Nama Dokumen *</label><input name="name" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label small fw-bold">Revisi dari</label><select name="replaces_document_id" class="form-select"><option value="">Dokumen baru</option>@foreach($currentDocs as $doc)<option value="{{ $doc->id }}">{{ $doc->name }} ({{ $doc->revisionLabel() }})</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label small fw-bold">File *</label><input type="file" name="file" class="form-control" required></div>
            <div class="col-md-9"><label class="form-label small fw-bold">Catatan Revisi</label><input name="revision_note" class="form-control" placeholder="Jelaskan perubahan pada revisi ini"></div>
            <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100"><i class="bi bi-cloud-upload me-1"></i>Upload</button></div>
        </form>
        <div class="table-wrap mt-4"><table class="table-r compact"><thead><tr><th>Dokumen</th><th>Jenis</th><th>Revisi</th><th>Status</th><th>Uploader</th><th>Tanggal</th><th></th></tr></thead><tbody>@forelse($designRequest->documents->sortByDesc('created_at') as $doc)<tr><td>{{ $doc->name }}</td><td>{{ str($doc->category)->headline() }}</td><td>{{ $doc->revisionLabel() }}@if($doc->revision_note)<small class="d-block text-muted-2">{{ $doc->revision_note }}</small>@endif</td><td><span class="status-soft {{ $doc->is_current ? 'st-green' : 'st-gray' }}">{{ $doc->is_current ? 'Aktif' : 'Riwayat' }}</span></td><td>{{ $doc->uploader?->name ?: '—' }}</td><td>{{ $doc->created_at?->format('d M Y H:i') }}</td><td><a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" class="btn btn-sm btn-soft"><i class="bi bi-download"></i></a></td></tr>@empty<tr><td colspan="7">Belum ada dokumen.</td></tr>@endforelse</tbody></table></div>
    </div>
    @endif
</div>

@push('scripts')
<script>
let rowIdx = {{ max(1, $designRequest->items->count()) }};
document.getElementById('addRow')?.addEventListener('click', function(){
    const i=rowIdx++;
    const tr=document.createElement('tr');
tr.innerHTML=`<td><input name="items[${i}][category]" class="form-control form-control-sm"></td><td><input name="items[${i}][name]" class="form-control form-control-sm"></td><td><input name="items[${i}][specification]" class="form-control form-control-sm"></td><td><input name="items[${i}][qty]" type="text" inputmode="decimal" data-qty value="1" class="form-control form-control-sm"></td><td><input name="items[${i}][unit]" value="Unit" class="form-control form-control-sm"></td><td><input name="items[${i}][unit_price]" type="text" inputmode="numeric" data-rupiah value="0" class="form-control form-control-sm"></td><td><button type="button" class="btn btn-sm btn-soft text-danger row-del"><i class="bi bi-x"></i></button></td>`;
    document.querySelector('#itemTable tbody').appendChild(tr);
    bindNumberInputs(tr);
    tr.querySelector('.row-del').onclick=()=>tr.remove();
});
document.querySelectorAll('.row-del').forEach(b=>b.onclick=()=>b.closest('tr').remove());
</script>
@endpush
@endsection
