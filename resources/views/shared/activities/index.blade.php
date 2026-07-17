@extends('layouts.app')
@section('title', 'Activities')
@section('content')
@php
    $isSalesAdminLayout = auth()->user()->isAdminLevel();
    $selectedActivity = $selectedActivity ?? $activities->first();
    $typeClass = fn($t) => match($t) {'meeting'=>'blue','call'=>'green','survey_lokasi'=>'orange','presentasi'=>'purple','follow_up'=>'orange','whatsapp'=>'green','email'=>'red','penawaran'=>'blue', default=>'blue'};
    $stageClass = fn($s) => match($s) {'identify'=>'st-blue','approaching'=>'st-yellow','follow_up'=>'st-purple','won_closing'=>'st-green','lost'=>'st-red','maintaining'=>'st-green', default=>'st-gray'};
    $stageLabel = fn($s) => \App\Models\Customer::stages()[$s] ?? match($s) {'lead'=>'Identify','design_request'=>'Approaching','penawaran'=>'Follow Up','negosiasi'=>'Follow Up','won'=>'Won / Closing','closing'=>'Won / Closing', default => ($s ? \Illuminate\Support\Str::headline($s) : '-')};
    $cleanQuery = request()->except('page');
    $periodUrl = fn($p) => route('activities.index', array_merge(request()->except('page', 'date'), ['period' => $p]));
    $activityUrl = fn($id) => route('activities.index', array_merge($cleanQuery, ['activity' => $id]));
    $calendarStartBlank = $calendarFirst->dayOfWeekIso - 1;
    $calendarUrl = fn($month, $year) => route('activities.index', array_merge($cleanQuery, ['cal_month' => $month, 'cal_year' => $year]));
    $dateUrl = fn($date) => route('activities.index', array_merge(request()->except('page', 'period'), ['date' => $date]));
    $displayDate = $selectedDate ? \Illuminate\Support\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') : match($period ?? 'today') {
        'week' => 'Minggu Ini',
        'month' => 'Bulan Ini',
        default => now()->translatedFormat('l, d F Y'),
    };
@endphp
@if($isSalesAdminLayout)
<div class="sales-admin-ui">
    <div class="sa-activity-grid">
        <main>
            <div class="sa-page-head">
                <div>
                    <h1 class="page-title mb-1">Activities</h1>
                    <div class="page-subtitle">Daftar semua aktivitas sales dan follow up dengan customer.</div>
                </div>
                <div class="page-actions"><a href="{{ route('activities.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Activity</a></div>
            </div>

            <div class="sa-stats four mb-3">
                <div class="sa-stat"><div class="sa-ico blue"><i class="bi bi-calendar3"></i></div><div><small>Today Activities</small><strong>{{ $stats['today'] }}</strong><span>Aktivitas hari ini</span></div></div>
                <div class="sa-stat"><div class="sa-ico orange"><i class="bi bi-clock"></i></div><div><small>Pending Activities</small><strong>{{ $stats['pending'] }}</strong><span>Belum selesai</span></div></div>
                <div class="sa-stat"><div class="sa-ico green"><i class="bi bi-check-circle"></i></div><div><small>Completed Today</small><strong>{{ $stats['completed_today'] }}</strong><span>Selesai hari ini</span></div></div>
                <div class="sa-stat"><div class="sa-ico red"><i class="bi bi-exclamation-triangle"></i></div><div><small>Overdue</small><strong>{{ $stats['overdue'] }}</strong><span>Terlambat</span></div></div>
            </div>

            <div class="sales-chip-row mb-3"><span class="sales-chip active">Pipeline</span><span class="sales-chip">Activity List</span><a class="sales-chip" href="{{ route('calendar.index') }}">Calendar</a><span class="sales-chip">Tracking Harian</span></div>
            <div class="sales-kanban mb-3">
                @foreach($pipeline as $stage => $data)
                    <div class="kanban-col">
                        <a class="kh {{ $stageClass($stage) }} text-decoration-none" href="{{ route('sales.customers.index',['status'=>$stage]) }}"><span class="kh-title">{{ strtoupper($data['label']) }}</span><span class="kh-count">{{ $data['customers']->count() }} Customer <i class="bi bi-chevron-right"></i></span></a>
                        @forelse($data['customers']->take(4) as $cust)
                            <a class="kanban-card text-reset text-decoration-none" href="{{ route('activities.index', array_merge(request()->except(['page','hide_detail']), ['customer_id'=>$cust->id])) }}#activity-customer-detail">
                                <div class="fw-bold">{{ $cust->name }}</div>
                                <div class="small text-muted-2">{{ $cust->primaryPic?->name ?? $cust->sales?->name ?? '-' }}</div>
                                <div class="kanban-meta mt-2">
                                    <span>{{ \App\Support\Format::rupiahShort($cust->quotations()->sum('grand_total')) }}</span>
                                    <span class="small text-muted-2"><i class="bi bi-calendar2 me-1"></i>{{ $cust->updated_at->translatedFormat('d M Y') }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="small text-muted-2">Belum ada customer.</div>
                        @endforelse
                        <a href="{{ route('sales.customers.index',['status'=>$stage]) }}" class="btn btn-link w-100 fw-bold small">Lihat Semua</a>
                    </div>
                @endforeach
            </div>

            <div class="sa-tabs"><a href="{{ $periodUrl('today') }}" class="{{ ($period ?? 'today') === 'today' && ! $selectedDate ? 'active' : '' }}">Hari Ini</a><a href="{{ $periodUrl('week') }}" class="{{ ($period ?? '') === 'week' && ! $selectedDate ? 'active' : '' }}">Minggu Ini</a><a href="{{ $periodUrl('month') }}" class="{{ ($period ?? '') === 'month' && ! $selectedDate ? 'active' : '' }}">Bulan Ini</a></div>
            <div class="sa-activity-main-grid">
                <section class="sa-card">
                    <div class="sa-activity-filter">
                        <strong>{{ $displayDate }} <i class="bi bi-calendar3 ms-1"></i></strong>
                        <form method="GET" class="d-flex flex-wrap gap-2 ms-auto">
                            <select name="sales_id" class="form-select form-select-sm"><option value="">Semua Sales</option>@foreach($salesUsers ?? [] as $s)<option value="{{ $s->id }}" @selected(request('sales_id')==$s->id)>{{ $s->name }}</option>@endforeach</select>
                            <select name="type" class="form-select form-select-sm"><option value="">Semua Jenis Aktivitas</option>@foreach(\App\Models\Activity::types() as $k=>$v)<option value="{{ $k }}" @selected(request('type')===$k)>{{ $v }}</option>@endforeach</select>
                            <select name="pipeline_stage" class="form-select form-select-sm"><option value="">Semua Pipeline Stage</option>@foreach(\App\Models\Customer::stages() as $k=>$v)<option value="{{ $k }}" @selected(request('pipeline_stage')===$k)>{{ $v }}</option>@endforeach</select>
                            <select name="customer_id" class="form-select form-select-sm"><option value="">Semua Customer</option>@foreach($customers ?? [] as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach</select>
                            <select name="status" class="form-select form-select-sm"><option value="">Semua Status</option>@foreach(\App\Models\Activity::statuses() as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select>
                            <button class="btn btn-sm btn-soft"><i class="bi bi-funnel"></i></button>
                        </form>
                    </div>
                    <div class="sa-day-timeline">
                        @forelse($activities->take(5) as $act)
                            <div class="sa-day-item {{ $typeClass($act->type) }}">
                                <time>{{ $act->activity_time ? \Illuminate\Support\Carbon::parse($act->activity_time)->format('H:i') : '--:--' }}</time>
                                <div class="line-dot"></div>
                                <a href="{{ $activityUrl($act->id) }}" class="sa-day-card text-reset text-decoration-none">
                                    <div><strong>{{ $act->title }}</strong><span>{{ $act->customer?->name ?? $act->lead?->instansi ?? '-' }}<br>{{ $act->sales?->name ?? '-' }}</span></div>
                                    <x-status-badge :status="$act->status" />
                                    <i class="bi bi-{{ $act->type === 'call' ? 'telephone' : ($act->type === 'meeting' ? 'camera-video' : ($act->type === 'survey_lokasi' ? 'geo-alt' : 'chat-dots')) }}"></i>
                                </a>
                            </div>
                        @empty
                            <x-empty text="Belum ada aktivitas." />
                        @endforelse
                    </div>
                </section>
                <section class="sa-card sa-mini-calendar-card">
                    <div class="sa-card-head"><a href="{{ $calendarUrl($calendarPrev->month, $calendarPrev->year) }}" class="btn btn-sm btn-soft"><i class="bi bi-chevron-left"></i></a><h2>{{ $calendarFirst->translatedFormat('F Y') }}</h2><a href="{{ $calendarUrl($calendarNext->month, $calendarNext->year) }}" class="btn btn-sm btn-soft"><i class="bi bi-chevron-right"></i></a></div>
                    <div class="sa-mini-calendar">
                        @foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $d)<b>{{ $d }}</b>@endforeach
                        @for($i=0;$i<$calendarStartBlank;$i++)<span></span>@endfor
                        @for($i=1;$i<=$calendarFirst->daysInMonth;$i++)
                            @php
                                $date = $calendarFirst->copy()->day($i)->format('Y-m-d');
                            @endphp
                            <a href="{{ $dateUrl($date) }}" class="{{ $date === ($selectedDate ?: now()->format('Y-m-d')) ? 'active' : '' }}">{{ $i }}</a>
                        @endfor
                    </div>
                    <div class="sa-card-head mt-3"><h2>Ringkasan Jenis Aktivitas</h2></div>
                    <div class="sa-chart-donut small"><div class="chart-box"><canvas id="saActType"></canvas><div class="donut-center"><small>Total</small><b>{{ $activities->total() }}</b></div></div></div>
                </section>
            </div>

            <section class="sa-card p-0 overflow-hidden mt-3">
                <div class="table-wrap">
                    <table class="sa-table">
                        <thead><tr><th>Waktu</th><th>Customer</th><th>Pipeline Stage</th><th>Jenis Aktivitas</th><th>Judul / Topik</th><th>Sales PIC</th><th>Status</th><th>Durasi</th><th>Aksi</th></tr></thead>
                        <tbody>
                        @forelse($activities as $act)
                            <tr>
                                <td>{{ $act->activity_time ? \Illuminate\Support\Carbon::parse($act->activity_time)->format('H:i') : '-' }}</td>
                                <td>{{ $act->customer?->name ?? $act->lead?->instansi ?? '-' }}</td>
                                <td><span class="status-soft {{ $stageClass($act->pipeline_stage) }}">{{ $stageLabel($act->pipeline_stage) }}</span></td>
                                <td><span class="status-soft st-blue">{{ \App\Models\Activity::types()[$act->type] ?? $act->type }}</span></td>
                                <td><a href="{{ $activityUrl($act->id) }}" class="text-reset text-decoration-none"><strong>{{ $act->title }}</strong><br><small>{{ $act->description }}</small></a></td>
                                <td>{{ $act->sales?->name ?? '-' }}</td>
                                <td><x-status-badge :status="$act->status" /></td>
                                <td>{{ $act->duration_minutes ? $act->duration_minutes.' Menit' : '-' }}</td>
                                <td>@if($act->status !== 'completed')<form method="POST" action="{{ route('activities.status',$act) }}">@csrf @method('PUT')<input type="hidden" name="status" value="completed"><button class="btn btn-sm btn-soft text-success"><i class="bi bi-check-lg"></i></button></form>@else<span class="text-muted-2">—</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="9"><x-empty text="Belum ada aktivitas." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 d-flex flex-wrap justify-content-between gap-3"><span class="small text-muted-2">Menampilkan {{ $activities->firstItem() ?? 0 }} - {{ $activities->lastItem() ?? 0 }} dari {{ $activities->total() }} data</span>{{ $activities->links() }}</div>
            </section>
        </main>
        <aside class="sa-activity-side" id="activity-customer-detail">
            <div class="sa-card">
                <div class="d-flex justify-content-end"><a href="{{ route('activities.index', request()->except('activity', 'page')) }}" class="btn btn-sm btn-link text-dark"><i class="bi bi-x-lg"></i></a></div>
                @if($selectedActivity)
                    <span class="status-soft st-green">{{ ucfirst(str_replace('_',' ', $selectedActivity->status)) }}</span>
                    <div class="text-center my-4"><div class="sa-big-icon"><i class="bi bi-camera-video"></i></div><h3>{{ $selectedActivity->title }}</h3><span>ID Aktivitas: {{ $selectedActivity->code }}</span></div>
                    <div class="sa-info-card"><h6>Informasi Aktivitas</h6><div class="sa-info-grid single"><div><span>Customer</span><strong>{{ $selectedActivity->customer?->name ?? $selectedActivity->lead?->instansi ?? '-' }}</strong></div><div><span>Pipeline Stage</span><strong>{{ $stageLabel($selectedActivity->pipeline_stage) }}</strong></div><div><span>Sales PIC</span><strong>{{ $selectedActivity->sales?->name ?? '-' }}</strong></div><div><span>Tanggal</span><strong>{{ $selectedActivity->activity_date?->translatedFormat('d F Y (l)') }}</strong></div><div><span>Jam</span><strong>{{ $selectedActivity->activity_time ? \Illuminate\Support\Carbon::parse($selectedActivity->activity_time)->format('H:i') : '-' }}</strong></div><div><span>Durasi</span><strong>{{ $selectedActivity->duration_minutes ? $selectedActivity->duration_minutes.' Menit' : '-' }}</strong></div><div><span>Lokasi / Link</span><strong>{{ $selectedActivity->location_link ?: '-' }}</strong></div></div></div>
                    <div class="sa-note-block mt-3"><h6>Catatan</h6><p>{{ $selectedActivity->description ?: 'Belum ada catatan.' }}</p></div>
                    <div class="sa-result-block mt-3"><h6>Hasil Aktivitas</h6><p>{{ $selectedActivity->result ?: 'Belum tersedia' }}</p></div>
                    <div class="sa-next-block mt-3"><i class="bi bi-arrow-right"></i><span>{{ $selectedActivity->next_action ?: 'Next action belum diisi' }}<br><small>Target: {{ $selectedActivity->next_followup_date?->translatedFormat('d F Y') ?? '-' }}</small></span></div>
                    <div class="sa-info-card mt-3"><h6>Riwayat Status</h6><div class="sa-status-timeline"><div><i></i><strong>{{ ucfirst($selectedActivity->status) }}</strong><span>{{ $selectedActivity->updated_at?->translatedFormat('d F Y H:i') }}</span></div><div><i></i><strong>Dibuat</strong><span>{{ $selectedActivity->created_at?->translatedFormat('d F Y H:i') }}</span></div></div></div>
                @else
                    <x-empty text="Belum ada aktivitas." />
                @endif
            </div>
        </aside>
    </div>
</div>
@push('scripts')
<script>
    const actTypes = @json($activities->getCollection()->groupBy('type')->map->count());
    robustChart('saActType','doughnut',Object.keys(actTypes),Object.values(actTypes),['#0b5cff','#10b981','#f59e0b','#8b5cf6','#ef4444','#14b8a6']);
</script>
@endpush
@else
@php
    $stageClass = fn($s) => match($s) {'identify'=>'st-blue','approaching'=>'st-yellow','follow_up'=>'st-purple','won_closing'=>'st-green','lost'=>'st-red','maintaining'=>'st-green', default=>'st-gray'};
    $typeClass = fn($t) => match($t) {'meeting'=>'sorange','call'=>'sblue','survey_lokasi'=>'sgreen','presentasi'=>'spurple','follow_up'=>'sorange','whatsapp'=>'sgreen','email'=>'sred','penawaran'=>'steal', default=>'sblue'};
@endphp
<div class="sales-ui">
    <div class="sales-main-grid">
        <div>
            <div class="sales-page-head"><div><div class="small fw-bold text-primary mb-1">Activities</div><h1 class="page-title mb-1">Activities</h1><div class="page-subtitle">Kelola pipeline dan aktivitas harian untuk mendorong penjualan dan menjaga hubungan dengan customer.</div></div><div class="page-actions"><a href="{{ route('activities.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Activity</a></div></div>
            <div class="sales-chip-row mb-3"><span class="sales-chip active">Pipeline</span><span class="sales-chip">Activity List</span><a class="sales-chip" href="{{ route('calendar.index') }}">Calendar</a><span class="sales-chip">Tracking Harian</span></div>
            <div class="sales-kanban mb-3">
                @foreach($pipeline as $stage => $data)
                    <div class="kanban-col"><div class="kh {{ $stageClass($stage) }}"><span class="kh-title">{{ strtoupper($data['label']) }}</span><span class="kh-count">{{ $data['customers']->count() }} Customer</span></div>@forelse($data['customers']->take(4) as $cust)<div class="kanban-card"><div class="fw-bold">{{ $cust->name }}</div><div class="small text-muted-2">{{ $cust->primaryPic?->name ?? '-' }}</div><div class="kanban-meta mt-2"><span>{{ \App\Support\Format::rupiahShort($cust->quotations()->sum('grand_total')) }}</span><span class="small text-muted-2"><i class="bi bi-calendar2 me-1"></i>{{ $cust->updated_at->translatedFormat('d M Y') }}</span></div></div>@empty<div class="small text-muted-2">Belum ada customer</div>@endforelse<a href="{{ route('sales.customers.index',['status'=>$stage]) }}" class="btn btn-link w-100 fw-bold small">Lihat Semua</a></div>
                @endforeach
            </div>
            <div class="card-r p-0 overflow-hidden">
                <div class="sales-chip-row p-3 pb-0"><span class="sales-chip active">Activity List</span><a href="{{ route('calendar.index') }}" class="sales-chip">Calendar</a><span class="sales-chip">Tracking Harian</span></div>
                <form class="sales-filter-row p-3 pb-0" method="GET"><input name="date" type="date" class="form-control" value="{{ request('date', date('Y-m-d')) }}"><select name="type" class="form-select"><option value="">Semua Tipe Aktivitas</option>@foreach(\App\Models\Activity::types() as $k=>$v)<option value="{{ $k }}" @selected(request('type')==$k)>{{ $v }}</option>@endforeach</select><select name="pipeline_stage" class="form-select"><option value="">Semua Pipeline Stage</option>@foreach(\App\Models\Customer::stages() as $k=>$v)<option value="{{ $k }}" @selected(request('pipeline_stage')==$k)>{{ $v }}</option>@endforeach</select><select name="customer_id" class="form-select"><option value="">Semua Customer</option>@foreach($customers ?? [] as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach</select><select name="status" class="form-select"><option value="">Semua Status</option>@foreach(\App\Models\Activity::statuses() as $key=>$label)<option value="{{ $key }}" @selected(request('status')==$key)>{{ $label }}</option>@endforeach</select><button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button></form>
                <div class="table-wrap"><table class="sales-table"><thead><tr><th>Waktu</th><th>Aktivitas</th><th>Customer</th><th>Pipeline Stage</th><th>Tipe</th><th>Sales</th><th>Status</th><th>Next Follow Up</th><th>Aksi</th></tr></thead><tbody>@forelse($activities as $act)<tr><td>{{ $act->activity_time ? \Illuminate\Support\Carbon::parse($act->activity_time)->format('H:i') : '-' }}</td><td><div class="d-flex gap-2 align-items-center"><div class="ico {{ $typeClass($act->type) }}" style="width:32px;height:32px;border-radius:8px;display:grid;place-items:center"><i class="bi bi-{{ $act->type==='call'?'telephone':($act->type==='email'?'envelope':($act->type==='meeting'?'people':'chat-dots')) }}"></i></div><div><div class="fw-bold">{{ $act->title }}</div><div class="small text-muted-2">{{ $act->description }}</div></div></div></td><td><strong>{{ $act->customer?->name ?? $act->lead?->instansi ?? '-' }}</strong><div class="small text-muted-2">{{ $act->customer?->primaryPic?->name ?? '' }}</div></td><td><span class="status-soft {{ $stageClass($act->pipeline_stage) }}">{{ \App\Models\Customer::stages()[$act->pipeline_stage] ?? ($act->pipeline_stage ?: '-') }}</span></td><td><span class="status-soft st-blue">{{ \App\Models\Activity::types()[$act->type] ?? $act->type }}</span></td><td>{{ $act->sales?->name ?? '-' }}</td><td><x-status-badge :status="$act->status" /></td><td>{{ $act->next_followup_date?->translatedFormat('d M H:i') ?: '-' }}</td><td>@if($act->status !== 'completed')<form method="POST" action="{{ route('activities.status',$act) }}">@csrf @method('PUT')<input type="hidden" name="status" value="completed"><button class="btn btn-sm btn-soft text-success"><i class="bi bi-check-lg"></i></button></form>@else<span class="text-muted-2">—</span>@endif</td></tr>@empty<tr><td colspan="9"><x-empty text="Belum ada aktivitas." /></td></tr>@endforelse</tbody></table></div><div class="p-3 d-flex justify-content-between"><span class="small text-muted-2">Menampilkan {{ $activities->firstItem() ?? 0 }} - {{ $activities->lastItem() ?? 0 }} dari {{ $activities->total() }} data</span>{{ $activities->links() }}</div>
            </div>
        </div>
        <aside class="sales-detail">
            @if($selectedCustomer)
                <div class="sales-detail-head"><a href="{{ route('activities.index', array_merge(request()->except('customer_id', 'page'), ['hide_detail' => 1])) }}" class="btn btn-sm btn-link text-dark ms-auto" aria-label="Tutup detail"><i class="bi bi-x-lg"></i></a></div><div class="sales-detail-body"><div class="text-center mb-3"><div class="logo-avatar mx-auto mb-2" style="width:64px;height:64px"><i class="bi bi-building fs-3"></i></div><h4 class="fw-black">{{ $selectedCustomer->name }}</h4><span class="status-soft {{ $stageClass($selectedCustomer->pipeline_stage) }}">{{ \App\Models\Customer::stages()[$selectedCustomer->pipeline_stage] ?? $selectedCustomer->pipeline_stage }}</span></div><div class="info-card mb-3"><h6>Pipeline Progress</h6>@foreach(\App\Models\Customer::stages() as $k=>$v)<div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-{{ $selectedCustomer->pipeline_stage===$k?'check-circle-fill text-success':'circle text-muted' }}"></i><span>{{ $v }}</span><span class="ms-auto small text-muted-2">{{ $selectedCustomer->pipeline_stage===$k ? $selectedCustomer->updated_at->translatedFormat('d M Y') : '-' }}</span></div>@endforeach</div><div class="info-card mb-3"><h6>Timeline Aktivitas</h6>@forelse($selectedCustomer->activities()->latest('activity_date')->take(4)->get() as $a)<div class="timeline-item" style="grid-template-columns:70px 1fr"><span class="small text-muted-2">{{ $a->activity_date->translatedFormat('d M Y') }}</span><div><strong>{{ $a->title }}</strong><div class="small text-muted-2">{{ $a->description }}</div></div></div>@empty<div class="small text-muted-2">Belum ada aktivitas.</div>@endforelse</div><div class="info-card"><h6>Informasi Lainnya</h6><div class="kv"><div class="k">PIC Customer</div><div class="v">{{ $selectedCustomer->primaryPic?->name ?? '-' }}</div></div><div class="kv"><div class="k">Email</div><div class="v">{{ $selectedCustomer->email ?: '-' }}</div></div><div class="kv"><div class="k">No. Telepon</div><div class="v">{{ $selectedCustomer->phone ?: '-' }}</div></div><a href="{{ route('sales.customers.show',$selectedCustomer) }}" class="btn btn-soft w-100 mt-3">Lihat Detail Customer <i class="bi bi-arrow-right"></i></a></div></div>
            @else
                <div class="sales-detail-body"><x-empty text="Belum ada customer untuk ditampilkan." /></div>
            @endif
        </aside>
    </div>
</div>

@endif
@endsection
