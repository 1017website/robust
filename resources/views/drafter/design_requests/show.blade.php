@extends('layouts.app')
@section('title', $designRequest->code)
@section('content')
@php
    $scope = $designRequest->scope_checklist ?? [];
    $outputs = $designRequest->outputs ?? [];
    $dimensions = $designRequest->dimensions ?: [
        ['item'=>'Wall Bench','size'=>'6000 x 750 x 850 mm'],
        ['item'=>'Island Bench','size'=>'3000 x 1500 x 850 mm'],
        ['item'=>'Fume Hood','size'=>'1800 x 850 x 2400 mm'],
        ['item'=>'Storage Cabinet','size'=>'1200 x 450 x 2000 mm'],
    ];
    $materials = $designRequest->materials ?: [
        ['item'=>'Top Table','material'=>'Phenolic Resin 19mm','finish'=>'Black'],
        ['item'=>'Cabinet','material'=>'Multiplek 18mm','finish'=>'HPL White'],
        ['item'=>'Frame','material'=>'Hollow Galvanis','finish'=>'Powder Coating'],
        ['item'=>'Back Panel','material'=>'Multiplek 9mm','finish'=>'HPL White'],
    ];
    $accessories = $designRequest->accessories ?: ['Sink Stainless SUS304','Gas Outlet','Eye Wash','Emergency Shower','Reagent Rack'];
    $estimations = $designRequest->material_estimation ?: [
        ['material'=>'Phenolic Resin 19mm','qty'=>'12 Lembar'],
        ['material'=>'HPL Compact 12mm','qty'=>'20 Lembar'],
        ['material'=>'Multiplek 18mm','qty'=>'35 Lembar'],
        ['material'=>'Hollow Galvanis 40x40','qty'=>'150 Meter'],
    ];
    $notes = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $designRequest->technical_note ?? '')));
@endphp
<div class="drafter-ui">
    <div class="drafter-request-head">
        <div>
            <div class="crumb"><a href="{{ route('drafter.design-requests.index') }}">Design Request</a> <i class="bi bi-chevron-right"></i> {{ $designRequest->code }}</div>
            <h1>{{ $designRequest->code }} <x-status-badge :status="$designRequest->status" /></h1>
            <p>{{ $designRequest->project_name }} - {{ $designRequest->customer_name }}</p>
        </div>
        <div class="page-actions"><button class="btn btn-soft"><i class="bi bi-clock-history me-1"></i>Riwayat Aktivitas</button><button class="btn btn-primary"><i class="bi bi-lightning-charge me-1"></i>Aksi Cepat</button></div>
    </div>

    <div class="d-tabs big"><a class="active"><i class="bi bi-shield-check me-1"></i>Detail Request</a><a><i class="bi bi-sliders me-1"></i>Spesifikasi & Feedback</a><a><i class="bi bi-pencil-square me-1"></i>Revisi</a><a><i class="bi bi-folder2-open me-1"></i>Dokumen</a><a><i class="bi bi-clock-history me-1"></i>Riwayat</a></div>

    <form method="POST" action="{{ route('drafter.design-requests.feedback',$designRequest) }}" class="drafter-workspace">
        @csrf
        <aside class="left-brief">
            <div class="info-card"><h6>Request dari Sales</h6><div class="mini-panel"><strong>Kebutuhan Customer</strong><ul class="check-list mt-2">@forelse($scope as $s)<li>{{ $s }}</li>@empty<li>{{ $designRequest->detail_need ?: 'Belum ada kebutuhan detail.' }}</li>@endforelse</ul></div><div class="note-box mt-3"><strong>Catatan Sales</strong><br>{{ $designRequest->extra_note ?: $designRequest->short_description ?: 'Tidak ada catatan.' }}</div></div>
            <div class="info-card"><h6>Lampiran dari Sales</h6>@forelse($designRequest->documents->take(4) as $doc)<div class="doc-mini"><i class="bi bi-file-earmark-pdf text-danger"></i><span>{{ $doc->name }}<small>{{ $doc->humanSize() }}</small></span></div>@empty<div class="small text-muted-2">Belum ada lampiran.</div>@endforelse</div>
            <div class="info-card"><h6>Informasi Umum</h6><div class="d-info-grid one"><span>Sales</span><strong>{{ $designRequest->sales?->name ?? '—' }}</strong><span>Tanggal Request</span><strong>{{ $designRequest->request_date?->format('d M Y') ?? $designRequest->created_at->format('d M Y') }}</strong><span>Deadline Request</span><strong>{{ $designRequest->deadline?->format('d M Y') ?? '—' }}</strong><span>Prioritas</span><strong>{{ ucfirst($designRequest->priority) }}</strong><span>PIC Customer</span><strong>{{ $designRequest->pic_name ?: '—' }}</strong></div></div>
        </aside>

        <main class="center-feedback">
            <div class="info-card"><div class="card-head"><h2>Feedback Teknis dari Produksi / Drafter</h2></div>
                <div class="feedback-grid two">
                    <div class="spec-card"><div class="spec-head"><strong>1. Dimensi Utama</strong><button type="button" class="btn btn-sm btn-soft"><i class="bi bi-pencil me-1"></i>Edit</button></div><div class="table-wrap"><table class="table-r compact"><thead><tr><th>Item</th><th>Ukuran (P x L x T)</th></tr></thead><tbody>@foreach($dimensions as $i=>$row)<tr><td><input name="dimensions[{{ $i }}][item]" value="{{ $row['item'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent fw-semibold"></td><td><input name="dimensions[{{ $i }}][size]" value="{{ $row['size'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent"></td></tr>@endforeach</tbody></table></div></div>
                    <div class="spec-card"><div class="spec-head"><strong>2. Material & Finishing</strong><button type="button" class="btn btn-sm btn-soft"><i class="bi bi-pencil me-1"></i>Edit</button></div><div class="table-wrap"><table class="table-r compact"><thead><tr><th>Item</th><th>Material</th><th>Finishing / Warna</th></tr></thead><tbody>@foreach($materials as $i=>$row)<tr><td><input name="materials[{{ $i }}][item]" value="{{ $row['item'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent fw-semibold"></td><td><input name="materials[{{ $i }}][material]" value="{{ $row['material'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent"></td><td><input name="materials[{{ $i }}][finish]" value="{{ $row['finish'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent"></td></tr>@endforeach</tbody></table></div></div>
                    <div class="spec-card"><div class="spec-head"><strong>3. Accessories / Perlengkapan</strong><button type="button" class="btn btn-sm btn-soft"><i class="bi bi-pencil me-1"></i>Edit</button></div><div class="accessory-list">@foreach($accessories as $i=>$item)<label><i class="bi bi-check-circle text-success"></i><input name="accessories[{{ $i }}]" value="{{ is_array($item) ? ($item['name'] ?? '') : $item }}" class="form-control form-control-sm border-0 bg-transparent"></label>@endforeach</div></div>
                    <div class="spec-card"><div class="spec-head"><strong>4. Estimasi Material</strong><button type="button" class="btn btn-sm btn-soft"><i class="bi bi-pencil me-1"></i>Edit</button></div><div class="table-wrap"><table class="table-r compact"><thead><tr><th>Material</th><th>Qty / Estimasi</th></tr></thead><tbody>@foreach($estimations as $i=>$row)<tr><td><input name="material_estimation[{{ $i }}][material]" value="{{ $row['material'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent"></td><td><input name="material_estimation[{{ $i }}][qty]" value="{{ $row['qty'] ?? '' }}" class="form-control form-control-sm border-0 bg-transparent"></td></tr>@endforeach</tbody></table></div></div>
                    <div class="spec-card"><div class="spec-head"><strong>5. Estimasi Costing Awal</strong><button type="button" class="btn btn-sm btn-soft"><i class="bi bi-pencil me-1"></i>Edit</button></div><div class="cost-list"><label>Material <input name="cost_material" type="number" value="{{ (float) $designRequest->cost_material ?: 180000000 }}" class="form-control form-control-sm"></label><label>Produksi <input name="cost_production" type="number" value="{{ (float) $designRequest->cost_production ?: 90000000 }}" class="form-control form-control-sm"></label><label>Instalasi <input name="cost_installation" type="number" value="{{ (float) $designRequest->cost_installation ?: 25000000 }}" class="form-control form-control-sm"></label><div class="total">Total Estimasi <strong>{{ \App\Support\Format::rupiah($designRequest->cost_total ?: 295000000) }}</strong></div></div></div>
                </div>
            </div>

            <div class="info-card mt-3"><div class="card-head"><h2>6. Upload Drawing & Dokumen</h2></div><div class="doc-chip-row">@forelse($designRequest->documents->take(4) as $doc)<div class="doc-chip"><i class="bi bi-file-earmark-pdf"></i><span>{{ $doc->name }}<small>{{ $doc->humanSize() }}</small></span><button type="button" class="btn btn-sm btn-link"><i class="bi bi-trash"></i></button></div>@empty<div class="upload-box small"><i class="bi bi-cloud-upload fs-3"></i><br>Upload drawing, render, shop drawing, atau BOQ dari menu Documents.</div>@endforelse</div><div class="preview-strip mt-3"><div>Layout</div><div>3D Render</div><div>Shop Drawing</div></div></div>

            <div class="info-card mt-3"><div class="card-head"><h2>Item Hasil untuk Penawaran</h2><button type="button" class="btn btn-soft btn-sm" id="addRow"><i class="bi bi-plus-lg me-1"></i>Tambah Item</button></div><div class="table-wrap"><table class="table-r compact" id="itemTable"><thead><tr><th>Kategori</th><th>Nama Item</th><th>Spesifikasi</th><th>Qty</th><th>Unit</th><th>Harga Satuan</th><th></th></tr></thead><tbody>@forelse($designRequest->items as $i => $it)<tr><td><input name="items[{{ $i }}][category]" value="{{ $it->category }}" class="form-control form-control-sm"></td><td><input name="items[{{ $i }}][name]" value="{{ $it->name }}" class="form-control form-control-sm"></td><td><input name="items[{{ $i }}][specification]" value="{{ $it->specification }}" class="form-control form-control-sm"></td><td><input name="items[{{ $i }}][qty]" type="number" step="0.01" value="{{ $it->qty }}" class="form-control form-control-sm"></td><td><input name="items[{{ $i }}][unit]" value="{{ $it->unit }}" class="form-control form-control-sm"></td><td><input name="items[{{ $i }}][unit_price]" type="number" value="{{ $it->unit_price }}" class="form-control form-control-sm"></td><td><button type="button" class="btn btn-sm btn-soft text-danger row-del"><i class="bi bi-x"></i></button></td></tr>@empty<tr><td><input name="items[0][category]" value="Wall Bench" class="form-control form-control-sm"></td><td><input name="items[0][name]" value="Wall Bench" class="form-control form-control-sm"></td><td><input name="items[0][specification]" value="Top table phenolic resin 16mm" class="form-control form-control-sm"></td><td><input name="items[0][qty]" type="number" value="1" class="form-control form-control-sm"></td><td><input name="items[0][unit]" value="Unit" class="form-control form-control-sm"></td><td><input name="items[0][unit_price]" type="number" value="0" class="form-control form-control-sm"></td><td></td></tr>@endforelse</tbody></table></div></div>
        </main>

        <aside class="right-status">
            <div class="info-card status-ready"><h6>Status Feedback</h6><ul class="check-list"><li>Layout Selesai</li><li>Drawing Selesai</li><li>BOQ Selesai</li><li>Costing Selesai</li></ul><div class="ready-box"><i class="bi bi-send"></i><strong>Siap Dikirim ke Sales</strong><small>Semua data sudah lengkap. Silakan submit ke sales untuk dibuatkan penawaran.</small></div></div>
            <div class="info-card"><h6>Catatan Teknis</h6><textarea name="technical_note" class="form-control" rows="7" placeholder="Catatan teknis untuk sales...">{{ $designRequest->technical_note }}</textarea></div>
            <div class="info-card"><h6>Log Aktivitas</h6><div class="d-timeline small">@foreach([$designRequest->updated_at,$designRequest->created_at] as $date)<div><time>{{ $date->format('d M H:i') }}</time><span></span><p><strong>{{ $designRequest->productionPic?->name ?? auth()->user()->name }}</strong><small>Update {{ $designRequest->status }}</small></p></div>@endforeach</div></div>
        </aside>

        <div class="submit-bar"><a href="{{ route('drafter.design-requests.index') }}" class="btn btn-soft"><i class="bi bi-arrow-left me-1"></i>Kembali</a><div class="ms-auto d-flex gap-2"><button type="submit" name="action" value="review" class="btn btn-soft">Simpan Draft</button><button type="submit" name="action" value="review" class="btn btn-warning">Kirim Revisi ke Sales</button><button type="submit" name="action" value="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit Final ke Sales</button></div></div>
    </form>
</div>

@push('scripts')
<script>
let rowIdx = {{ max(1, $designRequest->items->count()) }};
document.getElementById('addRow')?.addEventListener('click', function(){
    const i=rowIdx++;
    const tr=document.createElement('tr');
    tr.innerHTML=`<td><input name="items[${i}][category]" class="form-control form-control-sm"></td><td><input name="items[${i}][name]" class="form-control form-control-sm"></td><td><input name="items[${i}][specification]" class="form-control form-control-sm"></td><td><input name="items[${i}][qty]" type="number" step="0.01" value="1" class="form-control form-control-sm"></td><td><input name="items[${i}][unit]" value="Unit" class="form-control form-control-sm"></td><td><input name="items[${i}][unit_price]" type="number" value="0" class="form-control form-control-sm"></td><td><button type="button" class="btn btn-sm btn-soft text-danger row-del"><i class="bi bi-x"></i></button></td>`;
    document.querySelector('#itemTable tbody').appendChild(tr);
    tr.querySelector('.row-del').onclick=()=>tr.remove();
});
document.querySelectorAll('.row-del').forEach(b=>b.onclick=()=>b.closest('tr').remove());
</script>
@endpush
@endsection
