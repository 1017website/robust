@extends('layouts.app')
@section('title', 'Pra Leads')
@section('content')
<x-page-header title="Pra Leads" subtitle="Kelola dan distribusikan pra lead ke tim sales">
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#praLeadModal"><i class="bi bi-plus-lg me-1"></i>Pra Lead Baru</button>
</x-page-header>

<div class="stat-grid">
    <x-stat-card icon="bi-collection" color="primary" label="Total" :value="$counts['all']" />
    <x-stat-card icon="bi-file-earmark" color="info" label="Draft" :value="$counts['draft']" />
    <x-stat-card icon="bi-send" color="warning" label="Menunggu Acceptance" :value="$counts['waiting']" />
    <x-stat-card icon="bi-x-circle" color="danger" label="Ditolak" :value="$counts['rejected']" />
</div>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari instansi / PIC / kebutuhan...">
        <select name="status" class="form-select">
            <option value="">Semua Status</option>
            @foreach(['draft'=>'Draft','assigned'=>'Assigned','waiting_acceptance'=>'Menunggu','accepted'=>'Diterima','rejected'=>'Ditolak'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="source" class="form-select">
            <option value="">Semua Sumber</option>
            @foreach(['whatsapp','website','referensi','telepon','email','lainnya'] as $src)
                <option value="{{ $src }}" @selected(request('source')==$src)>{{ ucfirst($src) }}</option>
            @endforeach
        </select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>

    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Kode</th><th>Instansi</th><th>PIC</th><th>Sumber</th><th>Estimasi</th><th>Sales</th><th>Prioritas</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($praLeads as $pl)
                <tr>
                    <td class="fw-semibold">{{ $pl->code }}</td>
                    <td>{{ $pl->instansi }}<div class="small text-muted-2">{{ $pl->location }}</div></td>
                    <td>{{ $pl->pic_name }}<div class="small text-muted-2">{{ $pl->phone }}</div></td>
                    <td><span class="pill">{{ ucfirst($pl->source) }}</span></td>
                    <td class="fw-num">{{ $pl->est_value_min ? \App\Support\Format::rupiahShort($pl->est_value_min) : '—' }}</td>
                    <td>{{ $pl->assignedSales?->name ?? '—' }}</td>
                    <td><x-status-badge :status="$pl->priority" /></td>
                    <td><x-status-badge :status="$pl->status" /></td>
                    <td>
                        <form method="POST" action="{{ route('admin.pra-leads.destroy',$pl) }}" onsubmit="return confirm('Hapus pra lead ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-soft text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><x-empty text="Belum ada pra lead. Buat yang pertama." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $praLeads->links() }}</div>
</div>

{{-- Modal Create --}}
<div class="modal fade" id="praLeadModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.pra-leads.store') }}">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Pra Lead Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small fw-semibold">Instansi *</label><input name="instansi" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Lokasi / Kota</label><input name="location" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Nama PIC *</label><input name="pic_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Jabatan PIC</label><input name="pic_position" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">No. Telepon</label><input name="phone" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input name="email" type="email" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Sumber *</label>
                            <select name="source" class="form-select" required>
                                @foreach(['whatsapp','website','referensi','telepon','email','lainnya'] as $src)<option value="{{ $src }}">{{ ucfirst($src) }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Jenis Lab</label><input name="lab_type" class="form-control"></div>
                        <div class="col-12"><label class="form-label small fw-semibold">Kebutuhan Awal</label><textarea name="initial_need" rows="2" class="form-control"></textarea></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Estimasi Min</label><input data-rupiah name="est_value_min" type="text" inputmode="numeric" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Estimasi Max</label><input data-rupiah name="est_value_max" type="text" inputmode="numeric" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Prioritas</label>
                            <select name="priority" class="form-select"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select>
                        </div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Assign ke Sales</label>
                            <select name="assigned_sales_id" class="form-select select2">
                                <option value="">— Belum di-assign —</option>
                                @foreach($salesList as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label small fw-semibold">Catatan Admin</label><textarea name="admin_note" rows="2" class="form-control"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="action" value="draft" class="btn btn-soft">Simpan Draft</button>
                    <button type="submit" name="action" value="send" class="btn btn-primary">Simpan & Kirim ke Sales</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
