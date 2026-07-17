@extends('layouts.app')
@section('title', 'Pra Leads')
@section('content')
@php
    $sourceLabels = \App\Models\PraLead::sources();
    $statusLabels = \App\Models\PraLead::statuses();
@endphp

<x-page-header title="Pra Leads" subtitle="Kelola prospek baru sebelum menjadi lead dan ditugaskan ke sales.">
    <a href="#praLeadPanel" class="btn btn-primary btn-sm" id="topCreatePraLead"><i class="bi bi-plus-lg me-1"></i>Tambah Pra Lead</a>
</x-page-header>

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-3">
        <div class="fw-semibold mb-1">Data belum bisa disimpan.</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="pra-lead-shell">
    <section class="pra-lead-main">
        <div class="mini-stat-grid mb-4">
            <x-stat-card icon="bi-file-earmark-plus" color="primary" label="Semua Pra Leads" :value="$counts['all']" sub="100% dari total" />
            <x-stat-card icon="bi-file-earmark" color="warning" label="Draft" :value="$counts['draft']" :sub="$counts['all'] ? round($counts['draft'] / max($counts['all'],1) * 100).' % dari total' : '0% dari total'" />
            <x-stat-card icon="bi-person-plus" color="info" label="Ditugaskan" :value="$counts['assigned']" :sub="$counts['all'] ? round($counts['assigned'] / max($counts['all'],1) * 100).' % dari total' : '0% dari total'" />
            <x-stat-card icon="bi-hourglass-split" color="warning" label="Menunggu Konfirmasi Sales" :value="$counts['waiting']" :sub="$counts['all'] ? round($counts['waiting'] / max($counts['all'],1) * 100).' % dari total' : '0% dari total'" />
            <x-stat-card icon="bi-x-circle" color="danger" label="Ditolak Sales" :value="$counts['rejected']" :sub="$counts['all'] ? round($counts['rejected'] / max($counts['all'],1) * 100).' % dari total' : '0% dari total'" />
        </div>

        <div class="card-r pra-card-flat">
            <form class="filter-bar pra-filter" method="GET">
                <div class="filter-search">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari instansi, PIC, no. WA, kebutuhan...">
                </div>
                <select name="status" class="form-select">
                    <option value="">Status Semua</option>
                    @foreach($statusLabels as $k=>$v)
                        <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
                    @endforeach
                </select>
                <select name="source" class="form-select">
                    <option value="">Sumber Semua</option>
                    @foreach($sourceLabels as $src => $label)
                        <option value="{{ $src }}" @selected(request('source')===$src)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="created_date" value="{{ request('created_date') }}" class="form-control" title="Tanggal dibuat">
                <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button>
            </form>

            <div class="table-wrap">
                <table class="table-r pra-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Instansi / PIC</th>
                            <th>Sumber</th>
                            <th>Kebutuhan Awal</th>
                            <th>Lokasi</th>
                            <th>Sales Ditugaskan</th>
                            <th>Status</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($praLeads as $pl)
                        <tr>
                            <td>{{ $praLeads->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="fw-semibold">{{ $pl->instansi }}</div>
                                <div class="small text-muted-2">{{ $pl->pic_name }}</div>
                                <div class="small text-muted-2">{{ $pl->phone ?: '—' }}</div>
                            </td>
                            <td><span class="pill source-pill source-{{ $pl->source }}">{{ $sourceLabels[$pl->source] ?? ucfirst($pl->source) }}</span></td>
                            <td class="text-truncate-cell">{{ $pl->initial_need ?: '—' }}</td>
                            <td>{{ $pl->location ?: '—' }}</td>
                            <td>
                                @if($pl->assignedSales)
                                    {{ $pl->assignedSales->name }}
                                @else
                                    <span class="text-danger small fw-semibold">Belum diassign</span>
                                @endif
                            </td>
                            <td><x-status-badge :status="$pl->status" :label="$statusLabels[$pl->status] ?? \Illuminate\Support\Str::headline($pl->status)" /></td>
                            <td>
                                {{ $pl->created_at?->translatedFormat('d M Y') }}
                                <div class="small text-muted-2">{{ $pl->created_at?->format('H:i') }}</div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-soft" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <button type="button" class="dropdown-item pra-load-detail"
                                            data-instansi="{{ e($pl->instansi) }}"
                                            data-pic="{{ e($pl->pic_name) }}"
                                            data-phone="{{ e($pl->phone) }}"
                                            data-email="{{ e($pl->email) }}"
                                            data-source="{{ e($sourceLabels[$pl->source] ?? ucfirst($pl->source)) }}"
                                            data-lab="{{ e($pl->lab_type) }}"
                                            data-location="{{ e($pl->location) }}"
                                            data-need="{{ e($pl->initial_need) }}"
                                            data-note="{{ e($pl->admin_note) }}"
                                            data-sales="{{ e($pl->assignedSales?->name ?? '—') }}"
                                            data-status="{{ e($statusLabels[$pl->status] ?? \Illuminate\Support\Str::headline($pl->status)) }}">
                                            <i class="bi bi-eye me-2"></i>Lihat Detail
                                        </button>
                                        <button type="button" class="dropdown-item pra-edit"
                                            data-update-url="{{ route('admin.pra-leads.update', $pl) }}"
                                            data-instansi="{{ e($pl->instansi) }}"
                                            data-pic-name="{{ e($pl->pic_name) }}"
                                            data-pic-position="{{ e($pl->pic_position) }}"
                                            data-phone="{{ e($pl->phone) }}"
                                            data-email="{{ e($pl->email) }}"
                                            data-source="{{ e($pl->source) }}"
                                            data-lab-type="{{ e($pl->lab_type) }}"
                                            data-location="{{ e($pl->location) }}"
                                            data-initial-need="{{ e($pl->initial_need) }}"
                                            data-admin-note="{{ e($pl->admin_note) }}"
                                            data-est-value-min="{{ $pl->est_value_min !== null ? (float) $pl->est_value_min : '' }}"
                                            data-est-value-max="{{ $pl->est_value_max !== null ? (float) $pl->est_value_max : '' }}"
                                            data-priority="{{ e($pl->priority ?: 'medium') }}"
                                            data-assigned-sales-id="{{ $pl->assigned_sales_id }}"
                                            data-created="{{ $pl->created_at?->format('d M Y, H:i') }}">
                                            <i class="bi bi-pencil-square me-2"></i>Edit / Assign Sales
                                        </button>
                                        <form method="POST" action="{{ route('admin.pra-leads.destroy',$pl) }}" onsubmit="return confirm('Hapus pra lead ini?')">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><x-empty text="Belum ada pra lead. Buat yang pertama." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                <div class="small text-muted-2">
                    @if($praLeads->total())
                        Menampilkan {{ $praLeads->firstItem() }} - {{ $praLeads->lastItem() }} dari {{ $praLeads->total() }} data
                    @else
                        Menampilkan 0 data
                    @endif
                </div>
                {{ $praLeads->links() }}
            </div>
        </div>
    </section>

    <aside class="pra-lead-detail-panel" id="praLeadPanel">
        <div class="detail-header">
            <div>
                <h5 id="praPanelTitle">Tambah Pra Lead</h5>
                <div class="small text-muted-2" id="praPanelSubtitle">Input data prospek dan assign sales jika sudah siap dikirim.</div>
            </div>
            <button class="btn btn-sm btn-primary" type="button" id="showCreateForm"><i class="bi bi-plus-lg me-1"></i>Tambah Pra Lead</button>
        </div>

        <div id="praLeadPreview" class="pra-preview d-none">
            <div class="badge text-bg-success mb-3" id="previewStatus">Status</div>
            <h4 id="previewInstansi">Instansi</h4>
            <div class="info-box mt-3">
                <div class="info-row"><span>PIC</span><strong id="previewPic">—</strong></div>
                <div class="info-row"><span>No. WhatsApp</span><strong id="previewPhone">—</strong></div>
                <div class="info-row"><span>Email</span><strong id="previewEmail">—</strong></div>
                <div class="info-row"><span>Sumber</span><strong id="previewSource">—</strong></div>
                <div class="info-row"><span>Jenis Kebutuhan</span><strong id="previewLab">—</strong></div>
                <div class="info-row"><span>Lokasi Project</span><strong id="previewLocation">—</strong></div>
                <div class="info-row"><span>Sales Ditugaskan</span><strong id="previewSales">—</strong></div>
            </div>
            <div class="info-box mt-3">
                <div class="small fw-semibold mb-1">Kebutuhan Awal</div>
                <p class="mb-0 text-muted-2" id="previewNeed">—</p>
            </div>
            <div class="info-box mt-3">
                <div class="small fw-semibold mb-1">Catatan Admin</div>
                <p class="mb-0 text-muted-2" id="previewNote">—</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.pra-leads.store') }}" id="praLeadForm" data-store-url="{{ route('admin.pra-leads.store') }}">
            @csrf
            <span id="praFormMethodWrap"></span>
            <div class="section-title">Informasi Prospek</div>
            <div class="mb-3"><label class="form-label small fw-semibold">Nama Instansi <span class="text-danger">*</span></label><input name="instansi" value="{{ old('instansi') }}" class="form-control" required></div>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label small fw-semibold">PIC <span class="text-danger">*</span></label><input name="pic_name" value="{{ old('pic_name') }}" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Jabatan PIC</label><input name="pic_position" value="{{ old('pic_position') }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">No. WhatsApp</label><input name="phone" value="{{ old('phone') }}" class="form-control" placeholder="0812-xxxx-xxxx"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input name="email" type="email" value="{{ old('email') }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Sumber <span class="text-danger">*</span></label>
                    <select name="source" class="form-select" required>
                        @foreach($sourceLabels as $src => $label)
                            <option value="{{ $src }}" @selected(old('source','whatsapp')===$src)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Tanggal Dibuat</label><input id="formCreatedAt" value="{{ now()->format('d M Y, H:i') }}" class="form-control" disabled></div>
            </div>

            <div class="section-title mt-4">Informasi Kebutuhan</div>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label small fw-semibold">Jenis Kebutuhan</label><input name="lab_type" value="{{ old('lab_type') }}" class="form-control" placeholder="Contoh: Renovasi lab, furniture, fume hood, storage cabinet"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Lokasi Project</label><input name="location" value="{{ old('location') }}" class="form-control" placeholder="Surabaya"></div>
                <div class="col-12"><label class="form-label small fw-semibold">Kebutuhan Awal</label><textarea name="initial_need" rows="3" class="form-control" placeholder="Contoh: wall bench, fume hood, storage cabinet...">{{ old('initial_need') }}</textarea></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Estimasi Nilai Minimum (Rp)</label><input data-rupiah name="est_value_min" type="text" inputmode="numeric" value="{{ old('est_value_min') }}" class="form-control" placeholder="Contoh: 80.000.000"><div class="form-text">Perkiraan nilai proyek terendah dari kebutuhan awal.</div></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Estimasi Nilai Maksimum (Rp)</label><input data-rupiah name="est_value_max" type="text" inputmode="numeric" value="{{ old('est_value_max') }}" class="form-control" placeholder="Contoh: 120.000.000"><div class="form-text">Perkiraan nilai proyek tertinggi; boleh dikosongkan jika belum diketahui.</div></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Prioritas</label>
                    <select name="priority" class="form-select"><option value="low" @selected(old('priority')==='low')>Low</option><option value="medium" @selected(old('priority','medium')==='medium')>Medium</option><option value="high" @selected(old('priority')==='high')>High</option></select>
                </div>
                <div class="col-12"><label class="form-label small fw-semibold">Catatan dari Admin</label><textarea name="admin_note" rows="2" class="form-control">{{ old('admin_note') }}</textarea></div>
            </div>

            <div class="section-title mt-4">Sales Ditugaskan</div>
            @if($salesWorkloads->isNotEmpty())
                <div class="sales-recommend-grid mb-3">
                    @foreach($salesWorkloads->take(3) as $row)
                        <label class="sales-recommend-card">
                            <input type="radio" name="sales_quick_pick" value="{{ $row['sales']->id }}" data-sales-pick>
                            <div class="avatar-sm">{{ strtoupper(substr($row['sales']->name,0,1)) }}</div>
                            <div>
                                @if($loop->first)<span class="recommend-badge">Recommended</span>@endif
                                <div class="fw-semibold small">{{ $row['sales']->name }}</div>
                                <div class="small text-muted-2">{{ $row['active_leads'] }} Leads Aktif</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif
            <div class="mb-3">
                <label class="form-label small fw-semibold">Sales PIC <span class="text-danger" id="salesRequiredMark">*</span></label>
                <select name="assigned_sales_id" id="assigned_sales_id" class="form-select">
                    <option value="">Pilih Sales</option>
                    @foreach($salesList as $s)
                        <option value="{{ $s->id }}" @selected((string) old('assigned_sales_id') === (string) $s->id)>{{ $s->name }}{{ $s->job_title ? ' - '.$s->job_title : '' }}</option>
                    @endforeach
                </select>
                @if($salesList->isEmpty())
                    <div class="form-text text-danger">Belum ada user role Sales aktif. Cek menu Manage User dan pastikan role = Sales serta status Aktif.</div>
                @else
                    <div class="form-text">Gunakan tombol <b>Edit / Assign Sales</b> pada data lama jika sales belum terisi.</div>
                @endif
            </div>

            <div class="detail-actions">
                <button type="submit" name="action" value="draft" class="btn btn-soft">Simpan Draft</button>
                <button type="submit" name="action" value="save" class="btn btn-soft">Simpan</button>
                <button type="submit" name="action" value="send" class="btn btn-primary">Kirim ke Sales</button>
            </div>
        </form>
    </aside>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var createBtn = document.getElementById('showCreateForm');
    var topCreateBtn = document.getElementById('topCreatePraLead');
    var form = document.getElementById('praLeadForm');
    var methodWrap = document.getElementById('praFormMethodWrap');
    var preview = document.getElementById('praLeadPreview');
    var title = document.getElementById('praPanelTitle');
    var subtitle = document.getElementById('praPanelSubtitle');
    var createdAt = document.getElementById('formCreatedAt');

    document.querySelectorAll('[data-sales-pick]').forEach(function (el) {
        el.addEventListener('change', function () {
            var select = document.getElementById('assigned_sales_id');
            if (select) select.value = el.value;
        });
    });

    function showCreateForm(event) {
        if (event) event.preventDefault();
        preview?.classList.add('d-none');
        form?.classList.remove('d-none');
        form?.reset();
        if (form) form.action = form.dataset.storeUrl;
        if (methodWrap) methodWrap.innerHTML = '';
        document.querySelectorAll('[data-sales-pick]').forEach(function (radio) { radio.checked = false; });
        if (title) title.textContent = 'Tambah Pra Lead';
        if (subtitle) subtitle.textContent = 'Input data prospek dan assign sales jika sudah siap dikirim.';
        if (createdAt) createdAt.value = '{{ now()->format('d M Y, H:i') }}';
        document.getElementById('praLeadPanel')?.scrollIntoView({behavior: 'smooth', block: 'start'});
    }

    if (createBtn) createBtn.addEventListener('click', showCreateForm);
    if (topCreateBtn) topCreateBtn.addEventListener('click', showCreateForm);

    document.querySelectorAll('.pra-load-detail').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form?.classList.add('d-none');
            preview?.classList.remove('d-none');
            if (title) title.textContent = 'Detail Pra Lead';
            if (subtitle) subtitle.textContent = 'Informasi prospek yang dipilih dari tabel.';
            setText('previewInstansi', btn.dataset.instansi);
            setText('previewPic', btn.dataset.pic);
            setText('previewPhone', btn.dataset.phone || '—');
            setText('previewEmail', btn.dataset.email || '—');
            setText('previewSource', btn.dataset.source || '—');
            setText('previewLab', btn.dataset.lab || '—');
            setText('previewLocation', btn.dataset.location || '—');
            setText('previewNeed', btn.dataset.need || '—');
            setText('previewNote', btn.dataset.note || '—');
            setText('previewSales', btn.dataset.sales || '—');
            setText('previewStatus', btn.dataset.status || 'Status');
            document.getElementById('praLeadPanel')?.scrollIntoView({behavior: 'smooth', block: 'start'});
        });
    });

    document.querySelectorAll('.pra-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            preview?.classList.add('d-none');
            form?.classList.remove('d-none');
            if (form) form.action = btn.dataset.updateUrl;
            if (methodWrap) methodWrap.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            if (title) title.textContent = 'Edit Pra Lead';
            if (subtitle) subtitle.textContent = 'Ubah data prospek, assign sales, atau kirim ulang ke sales.';
            setField('instansi', btn.dataset.instansi);
            setField('pic_name', btn.dataset.picName);
            setField('pic_position', btn.dataset.picPosition);
            setField('phone', btn.dataset.phone);
            setField('email', btn.dataset.email);
            setField('source', btn.dataset.source || 'whatsapp');
            setField('lab_type', btn.dataset.labType);
            setField('location', btn.dataset.location);
            setField('initial_need', btn.dataset.initialNeed);
            setField('admin_note', btn.dataset.adminNote);
            setField('est_value_min', btn.dataset.estValueMin);
            setField('est_value_max', btn.dataset.estValueMax);
            setField('priority', btn.dataset.priority || 'medium');
            setField('assigned_sales_id', btn.dataset.assignedSalesId);
            syncSalesQuickPick(btn.dataset.assignedSalesId);
            if (createdAt) createdAt.value = btn.dataset.created || '—';
            document.getElementById('praLeadPanel')?.scrollIntoView({behavior: 'smooth', block: 'start'});
        });
    });

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || '—';
    }

    function setField(name, value) {
        var el = form?.querySelector('[name="' + name + '"]');
        if (!el) return;
        if (numberInputKind(el)) setNumberInputValue(el, value);
        else el.value = value || '';
    }

    function syncSalesQuickPick(value) {
        document.querySelectorAll('[data-sales-pick]').forEach(function (radio) {
            radio.checked = value && radio.value === String(value);
        });
    }
});
</script>
@endpush
