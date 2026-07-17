@extends('layouts.app')
@section('title', 'Customers')
@section('content')
@php
    $selected = $selectedCustomer;
    $isSalesAdminLayout = ! auth()->user()->isSales();
    $stageClass = fn($s) => match($s) {'identify'=>'st-blue','approaching'=>'st-yellow','follow_up'=>'st-purple','won_closing'=>'st-green','lost'=>'st-red','maintaining'=>'st-green', default=>'st-gray'};
    $activeCustomer = max(0, ($stats['total'] ?? 0) - ($stats['lost'] ?? 0));
    $repeatCustomer = \App\Models\Customer::has('projects', '>=', 2)->count();
    $totalProject = \App\Models\Project::count();
@endphp
@if($isSalesAdminLayout)
<div class="sales-admin-ui">
    <div class="sa-customer-grid">
        <main>
            <div class="sa-page-head">
                <div>
                    <h1 class="page-title mb-1">Customers</h1>
                    <div class="page-subtitle">Database seluruh customer yang sudah menjadi klien.</div>
                </div>
                @unless(auth()->user()->isSalesSpv())<div class="page-actions"><a href="{{ route('sales.customers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Customer</a></div>@endunless
            </div>

            <div class="sa-stats four mb-3">
                <div class="sa-stat"><div class="sa-ico blue"><i class="bi bi-people"></i></div><div><small>Total Customer</small><strong>{{ $stats['total'] }}</strong><span>100% dari total</span></div></div>
                <div class="sa-stat"><div class="sa-ico green"><i class="bi bi-person-check"></i></div><div><small>Active Customer</small><strong>{{ $activeCustomer }}</strong><span>{{ $stats['total'] ? round($activeCustomer / max(1,$stats['total']) * 100) : 0 }}% dari total</span></div></div>
                <div class="sa-stat"><div class="sa-ico purple"><i class="bi bi-arrow-repeat"></i></div><div><small>Repeat Customer</small><strong>{{ $repeatCustomer }}</strong><span>{{ $stats['total'] ? round($repeatCustomer / max(1,$stats['total']) * 100) : 0 }}% dari total</span></div></div>
                <div class="sa-stat"><div class="sa-ico orange"><i class="bi bi-briefcase"></i></div><div><small>Total Project</small><strong>{{ $totalProject }}</strong><span>100% dari total</span></div></div>
            </div>

            <div class="sa-three-col mb-3">
                <section class="sa-card">
                    <div class="sa-card-head"><h2>Customer Segmentation</h2></div>
                    <div class="sa-chart-donut small"><div class="chart-box"><canvas id="saCustomerSeg"></canvas><div class="donut-center"><small>Total</small><b>{{ $stats['total'] }}</b></div></div><div class="sa-legend-list"><div><i></i><span>Identify</span><strong>{{ $stats['identify'] }}</strong></div><div><i></i><span>Approaching</span><strong>{{ $stats['approaching'] }}</strong></div><div><i></i><span>Follow Up</span><strong>{{ $stats['follow_up'] }}</strong></div><div><i></i><span>Won / Closing</span><strong>{{ $stats['won'] }}</strong></div></div></div>
                    <a class="sa-link" href="{{ route('sales.customers.index') }}">Lihat Semua <i class="bi bi-arrow-right"></i></a>
                </section>
                <section class="sa-card">
                    <div class="sa-card-head"><h2>Top Customer (Berdasarkan Total Project)</h2></div>
                    <div class="sa-ranked-list">
                        @foreach($customers->take(5) as $cust)
                            <div><span>{{ $loop->iteration }}</span><strong>{{ $cust->name }}</strong><b>{{ $cust->projects()->count() }}</b></div>
                        @endforeach
                    </div>
                    <a class="sa-link" href="{{ route('sales.customers.index') }}">Lihat Semua <i class="bi bi-arrow-right"></i></a>
                </section>
                <section class="sa-card">
                    <div class="sa-card-head"><h2>Potensi Repeat Order</h2></div>
                    <div class="sa-repeat-list">
                        @foreach($customers->take(3) as $cust)
                            <div><i class="bi bi-building-check"></i><span><strong>{{ $cust->name }}</strong><small>Terakhir project: {{ $cust->updated_at?->translatedFormat('M Y') }}</small></span><em>{{ $cust->probability >= 70 ? 'Tinggi' : 'Sedang' }}</em></div>
                        @endforeach
                    </div>
                    <a class="sa-link" href="{{ route('sales.customers.index') }}">Lihat Semua <i class="bi bi-arrow-right"></i></a>
                </section>
            </div>

            <section class="sa-card p-0 overflow-hidden">
                <form class="sa-filter-row p-3" method="GET">
                    <div class="sa-search"><i class="bi bi-search"></i><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari customer..."></div>
                    <select name="category" class="form-select"><option value="">Jenis Customer</option><option>Pendidikan</option><option>Industri</option><option>Kesehatan</option></select>
                    <select name="sales_id" class="form-select"><option value="">Semua Sales</option>@foreach($salesList as $sales)<option value="{{ $sales->id }}" @selected((string)request('sales_id')===(string)$sales->id)>{{ $sales->name }}</option>@endforeach</select>
                    <select name="status" class="form-select"><option value="">Status</option>@foreach(\App\Models\Customer::stages() as $k=>$v)<option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>@endforeach</select>
                    <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button>
                </form>
                <div class="table-wrap">
                    <table class="sa-table">
                        <thead><tr><th>No</th><th>Customer</th><th>Jenis Customer</th><th>PIC Utama</th><th>Sales PIC</th><th>Total Project</th><th>Total Nilai (Rp)</th><th>Last Project</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                        @forelse($customers as $cust)
                            <tr class="{{ $selected && $selected->id === $cust->id ? 'selected' : '' }}">
                                <td>{{ $customers->firstItem()+$loop->index }}</td>
                                <td><a href="{{ route('sales.customers.index', array_merge(request()->except(['page','hide_detail']), ['customer'=>$cust->id])) }}#customer-detail" class="fw-bold">{{ $cust->name }}</a></td>
                                <td>{{ $cust->category ?: '-' }}</td>
                                <td>{{ $cust->primaryPic?->name ?? $cust->pic_name ?? '-' }}</td>
                                <td>{{ $cust->sales?->name ?? '-' }}</td>
                                <td>{{ $cust->projects()->count() }}</td>
                                <td>{{ \App\Support\Format::rupiahShort($cust->quotations()->sum('grand_total')) }}</td>
                                <td>{{ $cust->updated_at?->translatedFormat('M Y') }}</td>
                                <td><span class="status-soft {{ $stageClass($cust->pipeline_stage) }}">{{ \App\Models\Customer::stages()[$cust->pipeline_stage] ?? $cust->pipeline_stage }}</span></td>
                                <td><a href="{{ route('sales.customers.show',$cust) }}" class="btn btn-sm btn-link">...</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="10"><x-empty text="Belum ada customer." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 d-flex flex-wrap justify-content-between gap-3"><span class="small text-muted-2">Menampilkan {{ $customers->firstItem() ?? 0 }} - {{ $customers->lastItem() ?? 0 }} dari {{ $customers->total() }} data</span>{{ $customers->links() }}</div>
            </section>
        </main>

        <aside class="sa-customer-side" id="customer-detail">
            @if($selected)
                <div class="sa-card">
                <div class="d-flex justify-content-between align-items-start"><div><h2 class="mb-2">{{ $selected->name }}</h2><span class="status-soft {{ $stageClass($selected->pipeline_stage) }}">{{ \App\Models\Customer::stages()[$selected->pipeline_stage] ?? $selected->pipeline_stage }}</span></div><a class="btn btn-sm btn-link text-dark" href="{{ route('sales.customers.index', array_merge(request()->except('page'), ['hide_detail'=>1])) }}" aria-label="Tutup detail"><i class="bi bi-x-lg"></i></a></div>
                    <div class="sa-detail-tabs" role="tablist" aria-label="Detail customer">
                        <button type="button" class="active" data-customer-tab="overview">Overview</button>
                        <button type="button" data-customer-tab="projects">Projects <span>{{ $selected->projects->count() }}</span></button>
                        <button type="button" data-customer-tab="quotations">Penawaran <span>{{ $selected->quotations->count() }}</span></button>
                        <button type="button" data-customer-tab="activities">Activities <span>{{ $selected->activities->count() }}</span></button>
                        <button type="button" data-customer-tab="documents">Documents <span>{{ $selected->documents->count() }}</span></button>
                        <button type="button" data-customer-tab="contacts">Contacts <span>{{ $selected->pics->count() }}</span></button>
                    </div>
                    <div class="customer-tab-panel active" data-customer-panel="overview">
                        <div class="sa-info-card"><h6>Informasi Customer</h6><div class="sa-info-grid"><div><i class="bi bi-building"></i><span>Jenis Customer</span><strong>{{ $selected->category ?: '-' }}</strong></div><div><i class="bi bi-globe"></i><span>Website</span><strong>{{ $selected->website ?: '-' }}</strong></div><div><i class="bi bi-geo-alt"></i><span>Alamat</span><strong>{{ $selected->address ?: '-' }}</strong></div><div><i class="bi bi-envelope"></i><span>Email</span><strong>{{ $selected->email ?: '-' }}</strong></div><div><i class="bi bi-telephone"></i><span>Telepon</span><strong>{{ $selected->phone ?: '-' }}</strong></div><div><i class="bi bi-calendar"></i><span>Tahun Kerja Sama</span><strong>{{ $selected->partner_since?->format('Y') ?? '-' }}</strong></div></div></div>
                        <div class="sa-two-col mt-3"><div class="sa-info-card"><h6>PIC Customer</h6>@forelse($selected->pics->take(2) as $pic)<div class="sa-pic-row"><span>{{ $loop->iteration }}</span><strong>{{ $pic->name }}<small>{{ $pic->position }}</small></strong><em>{{ $pic->phone }}</em></div>@empty<div class="small text-muted-2">Belum ada PIC.</div>@endforelse</div><div class="sa-info-card"><h6>Sales Owner</h6><div class="sa-person"><span class="sa-avatar">{{ strtoupper(substr($selected->sales?->name ?? 'S',0,1)) }}</span><strong>{{ $selected->sales?->name ?? '-' }}<small>{{ $selected->sales?->job_title ?? 'Sales' }}</small></strong></div></div></div>
                        <div class="sa-info-card mt-3"><h6>Statistik</h6><div class="sa-mini-stat-grid"><div><strong>{{ $selected->projects->count() }}</strong><span>Total Project</span></div><div><strong>{{ \App\Support\Format::rupiahShort($selected->quotations->sum('grand_total')) }}</strong><span>Total Nilai</span></div><div><strong>{{ $selected->quotations->count() }}</strong><span>Total Penawaran</span></div><div><strong>{{ $selected->probability }}%</strong><span>Win Rate</span></div></div></div>
                        <div class="sa-info-card mt-3"><h6>Timeline Project</h6>@forelse($selected->projects->take(4) as $project)<div class="sa-timeline-row"><i></i><span>{{ $project->created_at?->translatedFormat('M Y') }}<strong>{{ $project->name }}</strong></span><em>{{ $project->status }}</em></div>@empty<div class="small text-muted-2">Belum ada project.</div>@endforelse</div>
                    </div>
                    <div class="customer-tab-panel" data-customer-panel="projects"><div class="sa-info-card"><h6>Daftar Project</h6>@forelse($selected->projects as $project)<div class="customer-panel-row"><span><strong>{{ $project->name }}</strong><small>{{ $project->created_at?->translatedFormat('d M Y') }}</small></span><x-status-badge :status="$project->status" /></div>@empty<x-empty text="Belum ada project." />@endforelse</div></div>
                    <div class="customer-tab-panel" data-customer-panel="quotations"><div class="sa-info-card"><h6>Daftar Penawaran</h6>@forelse($selected->quotations as $quotation)<div class="customer-panel-row"><span><strong>{{ $quotation->code }}</strong><small>{{ \App\Support\Format::rupiahShort($quotation->grand_total) }}</small></span><x-status-badge :status="$quotation->status" /></div>@empty<x-empty text="Belum ada penawaran." />@endforelse</div></div>
                    <div class="customer-tab-panel" data-customer-panel="activities"><div class="sa-info-card"><h6>Aktivitas Terbaru</h6>@forelse($selected->activities as $activity)<div class="customer-panel-row"><span><strong>{{ $activity->title }}</strong><small>{{ $activity->activity_date?->translatedFormat('d M Y') }} · {{ \App\Models\Activity::types()[$activity->type] ?? $activity->type }}</small></span><x-status-badge :status="$activity->status" /></div>@empty<x-empty text="Belum ada aktivitas." />@endforelse</div></div>
                    <div class="customer-tab-panel" data-customer-panel="documents"><div class="sa-info-card"><h6>Dokumen Customer</h6>@forelse($selected->documents as $document)<div class="customer-panel-row"><span><strong>{{ $document->name }}</strong><small>{{ $document->humanSize() }} · {{ $document->created_at?->translatedFormat('d M Y') }}</small></span><i class="bi bi-file-earmark-text text-primary"></i></div>@empty<x-empty text="Belum ada dokumen." />@endforelse</div></div>
                    <div class="customer-tab-panel" data-customer-panel="contacts"><div class="sa-info-card"><h6>Kontak Customer</h6>@forelse($selected->pics as $pic)<div class="customer-contact-row"><span class="sa-avatar">{{ strtoupper(substr($pic->name,0,1)) }}</span><span><strong>{{ $pic->name }}</strong><small>{{ $pic->position ?: 'PIC Customer' }}</small><small>{{ $pic->phone ?: '-' }} · {{ $pic->email ?: '-' }}</small></span></div>@empty<x-empty text="Belum ada kontak." />@endforelse</div></div>
                    @unless(auth()->user()->isSalesSpv())<div class="sa-detail-actions"><a href="{{ route('sales.customers.edit',$selected) }}" class="btn btn-soft"><i class="bi bi-pencil"></i>Edit</a><a href="{{ route('sales.projects.create') }}" class="btn btn-soft"><i class="bi bi-plus-lg"></i>Project Baru</a><a href="{{ route('sales.quotations.create',['customer'=>$selected->id]) }}" class="btn btn-primary"><i class="bi bi-file-earmark-plus"></i>Buat Penawaran</a></div>@endunless
                </div>
            @else
                <div class="sa-card"><x-empty text="Belum ada customer." /></div>
            @endif
        </aside>
    </div>
</div>
@push('scripts')
<script>
    robustChart('saCustomerSeg','doughnut',['Identify','Approaching','Follow Up','Won'],[{{ $stats['identify'] }},{{ $stats['approaching'] }},{{ $stats['follow_up'] }},{{ $stats['won'] }}],['#60a5fa','#f59e0b','#8b5cf6','#10b981']);
    document.querySelectorAll('[data-customer-tab]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('[data-customer-tab]').forEach(function (tab) { tab.classList.remove('active'); });
            document.querySelectorAll('[data-customer-panel]').forEach(function (panel) { panel.classList.remove('active'); });
            button.classList.add('active');
            document.querySelector('[data-customer-panel="' + button.dataset.customerTab + '"]')?.classList.add('active');
        });
    });
</script>
@endpush
@else
@php
    $selected = $selectedCustomer;
    $stageClass = fn($s) => match($s) {'identify'=>'st-blue','approaching'=>'st-yellow','follow_up'=>'st-purple','won_closing'=>'st-green','lost'=>'st-red','maintaining'=>'st-green', default=>'st-gray'};
@endphp
<div class="sales-ui">
    <div class="sales-main-grid">
        <div>
            <div class="sales-page-head"><div><div class="small fw-bold text-primary mb-1">Customers &gt; Daftar Customer</div><h1 class="page-title mb-1">Customers</h1><div class="page-subtitle">Kelola informasi customer dan kontak utama.</div></div><a href="{{ route('sales.customers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Customer</a></div>
            <div class="sales-grid-5" style="grid-template-columns:repeat(7,minmax(135px,1fr))"><div class="sales-stat"><div class="ico sblue"><i class="bi bi-people"></i></div><div><div class="label">Total Customers</div><div class="value">{{ $stats['total'] }}</div></div></div><div class="sales-stat"><div class="ico sblue"><i class="bi bi-compass"></i></div><div><div class="label">Identify</div><div class="value">{{ $stats['identify'] }}</div></div></div><div class="sales-stat"><div class="ico sorange"><i class="bi bi-person-lines-fill"></i></div><div><div class="label">Approaching</div><div class="value">{{ $stats['approaching'] }}</div></div></div><div class="sales-stat"><div class="ico spurple"><i class="bi bi-chat-left-dots"></i></div><div><div class="label">Follow Up</div><div class="value">{{ $stats['follow_up'] }}</div></div></div><div class="sales-stat"><div class="ico sgreen"><i class="bi bi-person-check"></i></div><div><div class="label">Won / Closing</div><div class="value">{{ $stats['won'] }}</div></div></div><div class="sales-stat"><div class="ico sred"><i class="bi bi-x-circle"></i></div><div><div class="label">Lost</div><div class="value">{{ $stats['lost'] }}</div></div></div><div class="sales-stat"><div class="ico steal"><i class="bi bi-arrow-repeat"></i></div><div><div class="label">Maintaining</div><div class="value">{{ $stats['maintaining'] }}</div></div></div></div>
            <div class="card-r p-0 overflow-hidden"><form class="sales-filter-row p-3 pb-0" method="GET" style="grid-template-columns:minmax(260px,1fr) 180px 180px auto"><div class="sales-search"><i class="bi bi-search"></i><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari customer..."></div><select name="status" class="form-select"><option value="">Semua Status</option>@foreach(\App\Models\Customer::stages() as $k=>$v)<option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>@endforeach</select><select name="category" class="form-select"><option value="">Semua Kategori</option>@foreach(\App\Models\Customer::categories() as $category)<option value="{{ $category }}" @selected(request('category')===$category)>{{ $category }}</option>@endforeach</select><button type="submit" class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button></form><div class="table-wrap"><table class="sales-table"><thead><tr><th>No.</th><th>Nama Customer</th><th>PIC Utama</th><th>Email</th><th>No. Telepon</th><th>Kategori</th><th>Status</th><th>Sales</th><th>Aksi</th></tr></thead><tbody>@forelse($customers as $cust)<tr class="{{ $selected && $selected->id===$cust->id?'selected':'' }}"><td>{{ $customers->firstItem()+$loop->index }}</td><td><a href="{{ route('sales.customers.show',$cust) }}" class="fw-bold">{{ $cust->name }}</a></td><td>{{ $cust->primaryPic?->name ?? $cust->pic_name ?? '-' }}</td><td>{{ $cust->email ?: '-' }}</td><td>{{ $cust->phone ?: '-' }}</td><td>{{ $cust->category ?: '-' }}</td><td><span class="status-soft {{ $stageClass($cust->pipeline_stage) }}">{{ \App\Models\Customer::stages()[$cust->pipeline_stage] ?? $cust->pipeline_stage }}</span></td><td>{{ $cust->sales?->name ?? '-' }}</td><td><div class="dropdown"><button class="btn btn-sm btn-soft" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button><div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="{{ route('sales.customers.show',$cust) }}">Lihat Detail</a><a class="dropdown-item" href="{{ route('sales.customers.edit',$cust) }}">Edit Customer</a></div></div></td></tr>@empty<tr><td colspan="9"><x-empty text="Belum ada customer." /></td></tr>@endforelse</tbody></table></div><div class="p-3 d-flex justify-content-between"><span class="small text-muted-2">Menampilkan {{ $customers->firstItem() ?? 0 }} - {{ $customers->lastItem() ?? 0 }} dari {{ $customers->total() }} data</span>{{ $customers->links() }}</div></div>
        </div>
        <aside class="sales-detail">
            @if($selected)
                <div class="sales-detail-head"><div class="text-center w-100"><div class="logo-avatar mx-auto mb-2" style="width:64px;height:64px"><i class="bi bi-building fs-3"></i></div><h4 class="fw-black mb-1">{{ $selected->name }}</h4><span class="status-soft {{ $stageClass($selected->pipeline_stage) }}">{{ \App\Models\Customer::stages()[$selected->pipeline_stage] ?? $selected->pipeline_stage }}</span><a href="{{ route('sales.customers.edit',$selected) }}" class="btn btn-soft w-100 mt-3"><i class="bi bi-pencil me-1"></i>Edit Customer</a></div></div>
                <div class="sales-detail-body"><div class="info-card mb-3"><h6>Informasi Utama</h6><div class="kv"><div class="k">PIC Utama</div><div class="v">{{ $selected->primaryPic?->name ?? '-' }}</div></div><div class="kv"><div class="k">Email</div><div class="v">{{ $selected->email ?: '-' }}</div></div><div class="kv"><div class="k">No. Telepon</div><div class="v">{{ $selected->phone ?: '-' }}</div></div><div class="kv"><div class="k">Alamat</div><div class="v">{{ $selected->address ?: '-' }}</div></div></div><div class="info-card mb-3"><h6>Status Customer</h6><div class="sales-chip-row">@foreach(\App\Models\Customer::stages() as $k=>$v)<span class="status-soft {{ $stageClass($k) }} {{ $selected->pipeline_stage===$k?'':'opacity-50' }}">{{ $v }}</span>@endforeach</div><div class="kv mt-3"><div class="k">Probabilitas</div><div class="v">{{ $selected->probability }}%<div class="sales-progress mt-1"><span style="width:{{ $selected->probability }}%"></span></div></div></div></div><div class="info-card"><h6>Ringkasan Aktivitas</h6><div class="kv"><div class="k">Total Penawaran</div><div class="v">{{ $selected->quotations_count ?? $selected->quotations()->count() }}</div></div><div class="kv"><div class="k">Total Project</div><div class="v">{{ $selected->projects()->count() }}</div></div><div class="kv"><div class="k">Total Nilai Penawaran</div><div class="v">{{ \App\Support\Format::rupiahShort($selected->quotations()->sum('grand_total')) }}</div></div><a href="{{ route('sales.customers.show',$selected) }}" class="btn btn-soft w-100 mt-3">Lihat Semua Aktivitas <i class="bi bi-arrow-right"></i></a></div></div>
            @else
                <div class="sales-detail-body"><x-empty text="Belum ada customer." /></div>
            @endif
        </aside>
    </div>
</div>
@endif
@endsection
