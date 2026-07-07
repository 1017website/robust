@extends('layouts.app')
@section('title', $designRequest->code)
@section('content')
@php
    $statusText = \App\Models\DesignRequest::statuses()[$designRequest->status] ?? \Illuminate\Support\Str::headline($designRequest->status);
    $scope = $designRequest->scope_checklist ?? [];
    $outputs = $designRequest->outputs ?? [];
    $dimensions = $designRequest->dimensions ?: [
        ['item' => 'Wall Bench', 'size' => '6000 x 750 x 850 mm'],
        ['item' => 'Island Bench', 'size' => '3000 x 1500 x 850 mm'],
        ['item' => 'Fume Hood', 'size' => '1800 x 850 x 2400 mm'],
    ];
    $materials = $designRequest->materials ?: [
        ['item' => 'Top Table', 'material' => 'Phenolic Resin 19mm', 'finishing' => 'Black'],
        ['item' => 'Cabinet', 'material' => 'Multiplex 18mm', 'finishing' => 'HPL White'],
        ['item' => 'Frame', 'material' => 'Hollow Galvanis', 'finishing' => 'Powder Coating'],
    ];
    $accessories = $designRequest->accessories ?: ['Sink Stainless SUS304', 'Gas Outlet', 'Eye Wash', 'Emergency Shower', 'Reagent Rack'];
    $matEst = $designRequest->material_estimation ?: [
        ['material' => 'Phenolic Resin 19mm', 'qty' => '12 Lembar'],
        ['material' => 'HPL Compact 12mm', 'qty' => '20 Lembar'],
        ['material' => 'Multiplex 18mm', 'qty' => '35 Lembar'],
    ];
@endphp
<div class="drafter-ui">
    <div class="drafter-page-head align-items-start">
        <div>
            <div class="small mb-2"><a href="{{ route('drafter.design-requests.index') }}">Design Request</a> <span class="mx-1">›</span> {{ $designRequest->code }}</div>
            <div class="d-flex align-items-center gap-2 flex-wrap"><h1 class="page-title mb-0">{{ $designRequest->code }}</h1><x-status-badge :status="$designRequest->status" :label="$statusText" /></div>
            <div class="page-subtitle mt-1">{{ $designRequest->project_name }} - {{ $designRequest->customer_name }}</div>
        </div>
        <div class="page-actions"><button type="button" class="btn btn-soft"><i class="bi bi-clock-history me-1"></i>Riwayat Aktivitas</button><button type="button" class="btn btn-primary"><i class="bi bi-lightning-charge me-1"></i>Aksi Cepat</button></div>
    </div>

    <div class="detail-tabs mb-3"><span class="active">Detail Request</span><span>Spesifikasi & Feedback</span><span>Revisi</span><span>Dokumen</span><span>Riwayat</span></div>

    <div class="drafter-workspace">
        <aside class="left-brief">
            <div class="info-card"><h6>Request dari Sales</h6><div class="brief-box"><strong>Kebutuhan Customer</strong><ul class="check-list mt-2">@forelse($scope as $s)<li>{{ $s }}</li>@empty<li>{{ $designRequest->detail_need ?: 'Belum ada kebutuhan.' }}</li>@endforelse</ul></div><div class="brief-box warm"><strong>Catatan Sales</strong><p>{{ $designRequest->extra_note ?: $designRequest->short_description ?: 'Belum ada catatan.' }}</p></div></div>
            <div class="info-card"><h6>Lampiran dari Sales</h6>@forelse($designRequest->documents->take(4) as $doc)<div class="doc-row"><i class="bi bi-file-earmark-pdf text-danger"></i><span>{{ $doc->name }}<small>{{ $doc->humanSize() }}</small></span></div>@empty<div class="small text-muted-2">Belum ada lampiran.</div>@endforelse</div>
            <div class="info-card"><h6>Informasi Umum</h6><div class="detail-grid"><div><small>Sales</small><strong>{{ $designRequest->sales?->name ?? '—' }}</strong></div><div><small>Tanggal Request</small><strong>{{ $designRequest->request_date?->translatedFormat('d M Y') ?? '—' }}</strong></div><div><small>Deadline Request</small><strong>{{ $designRequest->deadline?->translatedFormat('d M Y') ?? '—' }}</strong></div><div><small>Prioritas</small><x-status-badge :status="$designRequest->priority" :label="ucfirst($designRequest->priority)" /></div><div><small>PIC Customer</small><strong>{{ $designRequest->pic_name ?? '—' }}</strong></div></div></div>
        </aside>

        <main class="center-feedback">
            <form method="POST" action="{{ route('drafter.design-requests.feedback',$designRequest) }}" id="feedback">
                @csrf
                <div class="card-r">
                    <div class="card-head"><h2>Feedback Teknis dari Produksi / Drafter</h2></div>
                    <div class="tech-grid">
                        <div class="info-card"><div class="card-head mini"><h6>1. Dimensi Utama</h6><button type="button" class="btn btn-soft btn-sm"><i class="bi bi-pencil me-1"></i>Edit</button></div><div class="table-wrap"><table class="mini-table"><thead><tr><th>Item</th><th>Ukuran (P x L x T)</th></tr></thead><tbody>@foreach($dimensions as $i=>$row)<tr><td><input name="dimensions[{{ $i }}][item]" class="form-control form-control-sm" value="{{ $row['item'] ?? '' }}"></td><td><input name="dimensions[{{ $i }}][size]" class="form-control form-control-sm" value="{{ $row['size'] ?? '' }}"></td></tr>@endforeach</tbody></table></div></div>
                        <div class="info-card"><div class="card-head mini"><h6>2. Material & Finishing</h6><button type="button" class="btn btn-soft btn-sm"><i class="bi bi-pencil me-1"></i>Edit</button></div><div class="table-wrap"><table class="mini-table"><thead><tr><th>Item</th><th>Material</th><th>Finishing</th></tr></thead><tbody>@foreach($materials as $i=>$row)<tr><td><input name="materials[{{ $i }}][item]" class="form-control form-control-sm" value="{{ $row['item'] ?? '' }}"></td><td><input name="materials[{{ $i }}][material]" class="form-control form-control-sm" value="{{ $row['material'] ?? '' }}"></td><td><input name="materials[{{ $i }}][finishing]" class="form-control form-control-sm" value="{{ $row['finishing'] ?? '' }}"></td></tr>@endforeach</tbody></table></div></div>
                        <div class="info-card"><div class="card-head mini"><h6>3. Accessories / Perlengkapan</h6><button type="button" class="btn btn-soft btn-sm"><i class="bi bi-pencil me-1"></i>Edit</button></div>@foreach($accessories as $i=>$acc)<div class="input-group input-group-sm mb-2"><span class="input-group-text"><i class="bi bi-check-circle text-success"></i></span><input name="accessories[{{ $i }}]" class="form-control" value="{{ $acc }}"></div>@endforeach</div>
                        <div class="info-card"><div class="card-head mini"><h6>4. Estimasi Material</h6><button type="button" class="btn btn-soft btn-sm"><i class="bi bi-pencil me-1"></i>Edit</button></div><div class="table-wrap"><table class="mini-table"><thead><tr><th>Material</th><th>Qty / Estimasi</th></tr></thead><tbody>@foreach($matEst as $i=>$row)<tr><td><input name="material_estimation[{{ $i }}][material]" class="form-control form-control-sm" value="{{ $row['material'] ?? '' }}"></td><td><input name="material_estimation[{{ $i }}][qty]" class="form-control form-control-sm" value="{{ $row['qty'] ?? '' }}"></td></tr>@endforeach</tbody></table></div></div>
                        <div class="info-card"><div class="card-head mini"><h6>5. Estimasi Costing Awal</h6><button type="button" class="btn btn-soft btn-sm"><i class="bi bi-pencil me-1"></i>Edit</button></div><div class="mb-2"><label class="form-label small">Material</label><input name="cost_material" type="number" class="form-control" value="{{ old('cost_material', $designRequest->cost_material ?? 0) }}"></div><div class="mb-2"><label class="form-label small">Produksi</label><input name="cost_production" type="number" class="form-control" value="{{ old('cost_production', $designRequest->cost_production ?? 0) }}"></div><div class="mb-2"><label class="form-label small">Instalasi</label><input name="cost_installation" type="number" class="form-control" value="{{ old('cost_installation', $designRequest->cost_installation ?? 0) }}"></div><div class="cost-total">Total Estimasi <strong>{{ \App\Support\Format::rupiah(($designRequest->cost_material ?? 0)+($designRequest->cost_production ?? 0)+($designRequest->cost_installation ?? 0)) }}</strong></div></div>
                    </div>
                </div>

                <div class="card-r" id="boq">
                    <div class="card-head"><h2>Item Hasil untuk Penawaran</h2><button type="button" class="btn btn-soft btn-sm" id="addRow"><i class="bi bi-plus-lg me-1"></i>Tambah Item</button></div>
                    <div class="table-wrap"><table class="drafter-table" id="itemTable"><thead><tr><th>Kategori</th><th>Nama Item</th><th>Spesifikasi</th><th>Qty</th><th>Unit</th><th>Harga Satuan</th><th>Margin %</th><th></th></tr></thead><tbody>@forelse($designRequest->items as $i=>$item)<tr><td><input class="form-control form-control-sm" name="items[{{ $i }}][category]" value="{{ $item->category }}"></td><td><input class="form-control form-control-sm" name="items[{{ $i }}][name]" value="{{ $item->name }}"></td><td><input class="form-control form-control-sm" name="items[{{ $i }}][specification]" value="{{ $item->specification }}"></td><td><input class="form-control form-control-sm" name="items[{{ $i }}][qty]" value="{{ $item->qty }}"></td><td><input class="form-control form-control-sm" name="items[{{ $i }}][unit]" value="{{ $item->unit }}"></td><td><input class="form-control form-control-sm" name="items[{{ $i }}][unit_price]" value="{{ $item->unit_price }}"></td><td><input class="form-control form-control-sm" name="items[{{ $i }}][margin]" value="{{ $item->margin }}"></td><td><button type="button" class="btn btn-sm btn-light remove-row"><i class="bi bi-trash text-danger"></i></button></td></tr>@empty<tr><td><input class="form-control form-control-sm" name="items[0][category]" value="Wall Bench"></td><td><input class="form-control form-control-sm" name="items[0][name]" value="Wall Bench"></td><td><input class="form-control form-control-sm" name="items[0][specification]" value="Top table phenolic resin 16mm"></td><td><input class="form-control form-control-sm" name="items[0][qty]" value="1"></td><td><input class="form-control form-control-sm" name="items[0][unit]" value="Unit"></td><td><input class="form-control form-control-sm" name="items[0][unit_price]" value="0"></td><td><input class="form-control form-control-sm" name="items[0][margin]" value="25"></td><td><button type="button" class="btn btn-sm btn-light remove-row"><i class="bi bi-trash text-danger"></i></button></td></tr>@endforelse</tbody></table></div>
                </div>

                <div class="card-r" id="upload">
                    <div class="card-head"><h2>6. Upload Drawing & Dokumen</h2></div>
                    <div class="doc-thumb-grid">@forelse($designRequest->documents as $doc)<div class="doc-thumb"><i class="bi bi-file-earmark-text"></i><span>{{ $doc->name }}</span><small>{{ $doc->humanSize() }}</small></div>@empty<div class="small text-muted-2">Belum ada dokumen upload.</div>@endforelse</div>
                    <textarea name="technical_note" class="form-control mt-3" rows="3" placeholder="Catatan teknis">{{ old('technical_note', $designRequest->technical_note) }}</textarea>
                </div>

                <div class="bottom-action-bar"><a href="{{ route('drafter.design-requests.index') }}" class="btn btn-soft">Kembali</a><button name="action" value="draft" class="btn btn-soft">Simpan Draft</button><button name="action" value="review" class="btn btn-warning">Kirim Revisi ke Sales</button><button name="action" value="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit Final ke Sales</button></div>
            </form>
        </main>

        <aside class="right-status">
            <div class="info-card"><h6>Status Feedback</h6><ul class="check-list big"><li>Layout {{ in_array('layout_2d',$outputs) ? 'Selesai' : 'Diminta' }}</li><li>Drawing {{ in_array('shop_drawing',$outputs) ? 'Selesai' : 'Diminta' }}</li><li>BOQ {{ in_array('boq',$outputs) ? 'Selesai' : 'Diminta' }}</li><li>Costing {{ in_array('cost_estimation',$outputs) ? 'Selesai' : 'Diminta' }}</li></ul><div class="ready-box"><i class="bi bi-check-circle"></i><div><strong>Siap Dikirim ke Sales</strong><small>Pastikan semua data lengkap sebelum submit final.</small></div></div></div>
            <div class="info-card"><h6>Catatan Teknis</h6><ul class="clean-list"><li>{{ $designRequest->technical_note ?: 'Top table menggunakan Phenolic Resin 19mm.' }}</li><li>Cabinet menggunakan multiplex finishing HPL.</li><li>Frame menggunakan hollow galvanis powder coating.</li></ul></div>
            <div class="info-card"><h6>Log Aktivitas</h6><div class="timeline-line"><span>{{ $designRequest->updated_at?->format('H:i') }}</span><i></i><div><strong>{{ auth()->user()->name }} update progress</strong><small>{{ $designRequest->updated_at?->translatedFormat('d M Y') }}</small></div></div><div class="timeline-line"><span>{{ $designRequest->created_at?->format('H:i') }}</span><i></i><div><strong>Request diterima</strong><small>{{ $designRequest->created_at?->translatedFormat('d M Y') }}</small></div></div></div>
        </aside>
    </div>
</div>
@endsection
@push('scripts')
<script>
(function(){
    let idx = document.querySelectorAll('#itemTable tbody tr').length;
    const body = document.querySelector('#itemTable tbody');
    document.getElementById('addRow')?.addEventListener('click', () => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td><input class="form-control form-control-sm" name="items[${idx}][category]"></td><td><input class="form-control form-control-sm" name="items[${idx}][name]"></td><td><input class="form-control form-control-sm" name="items[${idx}][specification]"></td><td><input class="form-control form-control-sm" name="items[${idx}][qty]" value="1"></td><td><input class="form-control form-control-sm" name="items[${idx}][unit]" value="Unit"></td><td><input class="form-control form-control-sm" name="items[${idx}][unit_price]" value="0"></td><td><input class="form-control form-control-sm" name="items[${idx}][margin]" value="25"></td><td><button type="button" class="btn btn-sm btn-light remove-row"><i class="bi bi-trash text-danger"></i></button></td>`;
        body.appendChild(tr); idx++;
    });
    document.addEventListener('click', e => { if(e.target.closest('.remove-row')) e.target.closest('tr')?.remove(); });
})();
</script>
@endpush
