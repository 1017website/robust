@extends('layouts.app')
@section('title', 'Kerjakan Design Request')
@section('content')
<x-page-header :title="$designRequest->project_name" :subtitle="$designRequest->code.' · '.$designRequest->customer_name">
    <x-status-badge :status="$designRequest->status" />
</x-page-header>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card-r">
            <div class="card-head"><h2>Brief dari Sales</h2></div>
            <div class="mb-2"><div class="small text-muted-2">Sales</div><div class="fw-semibold">{{ $designRequest->sales?->name ?? '—' }}</div></div>
            <div class="mb-2"><div class="small text-muted-2">Deadline</div><div class="fw-semibold">{{ $designRequest->deadline?->format('d M Y') ?? '—' }}</div></div>
            <div class="mb-2"><div class="small text-muted-2">Deskripsi</div><div>{{ $designRequest->short_description }}</div></div>
            <div class="mb-2"><div class="small text-muted-2">Detail Kebutuhan</div><div>{{ $designRequest->detail_need }}</div></div>
            @if($designRequest->scope_checklist)
            <div class="mb-2"><div class="small text-muted-2 mb-1">Scope</div>@foreach($designRequest->scope_checklist as $s)<span class="pill me-1 mb-1">{{ $s }}</span>@endforeach</div>
            @endif
            @if($designRequest->outputs)
            <div class="mb-2"><div class="small text-muted-2 mb-1">Output Diminta</div>@foreach($designRequest->outputs as $o)<span class="pill me-1 mb-1">{{ \Illuminate\Support\Str::headline($o) }}</span>@endforeach</div>
            @endif
        </div>

        <div class="card-r">
            <div class="card-head"><h2>Update Progress</h2></div>
            <form method="POST" action="{{ route('drafter.design-requests.progress',$designRequest) }}">
                @csrf @method('PUT')
                <div class="mb-3"><label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">@foreach(\App\Models\DesignRequest::statuses() as $k=>$v)<option value="{{ $k }}" @selected($designRequest->status==$k)>{{ $v }}</option>@endforeach</select>
                </div>
                <div class="mb-3"><label class="form-label small fw-semibold">Progress (%)</label><input name="progress" type="number" min="0" max="100" value="{{ $designRequest->progress }}" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Catatan Produksi</label><textarea name="production_note" rows="2" class="form-control">{{ $designRequest->production_note }}</textarea></div>
                <button class="btn btn-soft w-100">Update Progress</button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <form method="POST" action="{{ route('drafter.design-requests.feedback',$designRequest) }}">
            @csrf
            <div class="card-r">
                <div class="card-head"><h2>Estimasi Biaya</h2></div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label small fw-semibold">Biaya Material</label><input data-rupiah name="cost_material" type="text" inputmode="numeric" value="{{ $designRequest->cost_material }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Biaya Produksi</label><input data-rupiah name="cost_production" type="text" inputmode="numeric" value="{{ $designRequest->cost_production }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Biaya Instalasi</label><input data-rupiah name="cost_installation" type="text" inputmode="numeric" value="{{ $designRequest->cost_installation }}" class="form-control"></div>
                </div>
                <div class="mt-3"><label class="form-label small fw-semibold">Catatan Teknis</label><textarea name="technical_note" rows="2" class="form-control">{{ $designRequest->technical_note }}</textarea></div>
            </div>

            <div class="card-r">
                <div class="card-head"><h2>Item Hasil (untuk Penawaran)</h2><button type="button" class="btn btn-soft btn-sm" id="addRow"><i class="bi bi-plus-lg me-1"></i>Tambah Item</button></div>
                <div class="table-wrap">
                    <table class="table-r" id="itemTable">
                        <thead><tr><th>Kategori</th><th>Nama Item</th><th>Spesifikasi</th><th style="width:80px">Qty</th><th style="width:80px">Unit</th><th style="width:150px">Harga Satuan</th><th></th></tr></thead>
                        <tbody>
                        @forelse($designRequest->items as $i => $it)
                            <tr>
                                <td><input name="items[{{ $i }}][category]" value="{{ $it->category }}" class="form-control form-control-sm"></td>
                                <td><input name="items[{{ $i }}][name]" value="{{ $it->name }}" class="form-control form-control-sm"></td>
                                <td><input name="items[{{ $i }}][specification]" value="{{ $it->specification }}" class="form-control form-control-sm"></td>
                                <td><input name="items[{{ $i }}][qty]" type="number" step="0.01" value="{{ $it->qty }}" class="form-control form-control-sm"></td>
                                <td><input name="items[{{ $i }}][unit]" value="{{ $it->unit }}" class="form-control form-control-sm"></td>
                                <td><input name="items[{{ $i }}][unit_price]" type="number" value="{{ $it->unit_price }}" class="form-control form-control-sm"></td>
                                <td><button type="button" class="btn btn-sm btn-soft text-danger row-del"><i class="bi bi-x"></i></button></td>
                            </tr>
                        @empty
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-r d-flex gap-2 justify-content-end">
                <button type="submit" name="action" value="review" class="btn btn-soft">Simpan Draft</button>
                <button type="submit" name="action" value="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit Final ke Sales</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let rowIdx = {{ $designRequest->items->count() }};
document.getElementById('addRow').onclick=function(){
    const i=rowIdx++;
    const tr=document.createElement('tr');
    tr.innerHTML=`
        <td><input name="items[${i}][category]" class="form-control form-control-sm"></td>
        <td><input name="items[${i}][name]" class="form-control form-control-sm"></td>
        <td><input name="items[${i}][specification]" class="form-control form-control-sm"></td>
        <td><input name="items[${i}][qty]" type="number" step="0.01" value="1" class="form-control form-control-sm"></td>
        <td><input name="items[${i}][unit]" value="Unit" class="form-control form-control-sm"></td>
        <td><input name="items[${i}][unit_price]" type="number" value="0" class="form-control form-control-sm"></td>
        <td><button type="button" class="btn btn-sm btn-soft text-danger row-del"><i class="bi bi-x"></i></button></td>`;
    document.querySelector('#itemTable tbody').appendChild(tr);
    tr.querySelector('.row-del').onclick=()=>tr.remove();
};
document.querySelectorAll('.row-del').forEach(b=>b.onclick=()=>b.closest('tr').remove());
</script>
@endpush
@endsection
