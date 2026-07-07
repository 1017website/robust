@extends('layouts.app')
@section('title', 'Calendar')
@section('content')
@php
    $first = \Illuminate\Support\Carbon::create($year,$month,1);
    $daysInMonth = $first->daysInMonth;
    $startBlank = $first->dayOfWeekIso - 1;
    $prevMonth = $first->copy()->subMonth();
    $nextMonth = $first->copy()->addMonth();
    $typeClass = fn($t) => match($t) {'meeting'=>'sorange','call'=>'sblue','survey_lokasi'=>'sgreen','presentasi'=>'spurple','follow_up'=>'sorange','whatsapp'=>'sgreen','email'=>'sred','penawaran'=>'steal', default=>'sblue'};
@endphp
<div class="sales-ui">
    <div class="sales-page-head"><div class="sales-title-wrap"><div class="sales-title-icon"><i class="bi bi-calendar3"></i></div><div><h1 class="page-title mb-1">Calendar</h1><div class="page-subtitle">Kelola jadwal dan aktivitas harian untuk mendukung penjualan dan menjaga hubungan dengan customer.</div></div></div><a href="{{ route('activities.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Activity</a></div>
    <div class="calendar-page-grid">
        <div>
            <div class="sales-toolbar"><div class="sales-chip-row"><a class="sales-chip" href="#">Hari</a><a class="sales-chip" href="#">Minggu</a><a class="sales-chip active" href="#">Bulan</a><a href="{{ route('calendar.index',['month'=>$prevMonth->month,'year'=>$prevMonth->year]) }}" class="btn btn-soft"><i class="bi bi-chevron-left"></i></a><a href="{{ route('calendar.index',['month'=>$nextMonth->month,'year'=>$nextMonth->year]) }}" class="btn btn-soft"><i class="bi bi-chevron-right"></i></a><span class="sales-chip">{{ $first->translatedFormat('F Y') }}</span></div><div class="page-actions"><select class="form-select" style="width:190px"><option>Semua Tipe Aktivitas</option></select><select class="form-select" style="width:150px"><option>Semua Sales</option></select><button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button></div></div>
            <div class="sales-calendar-grid">
                @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $d)<div class="sales-cal-head">{{ $d }}</div>@endforeach
                @for($i=0;$i<$startBlank;$i++)<div class="sales-cal-cell bg-light"></div>@endfor
                @for($day=1;$day<=$daysInMonth;$day++)
                    @php($date = \Illuminate\Support\Carbon::create($year,$month,$day)->format('Y-m-d'))
                    @php($items = $byDate[$date] ?? collect())
                    <div class="sales-cal-cell {{ $date === now()->format('Y-m-d') ? 'bg-primary-subtle' : '' }}">
                        <div class="sales-cal-day">{{ $day }}</div>
                        @foreach($items->take(3) as $act)
                            <div class="sales-cal-event {{ $typeClass($act->type) }}"><i class="bi bi-dot"></i>{{ \Illuminate\Support\Str::limit($act->title,18) }}<br><small>{{ $act->customer?->name ?? $act->lead?->instansi ?? '' }}</small></div>
                        @endforeach
                        @if($items->count()>3)<div class="small fw-bold text-primary">+{{ $items->count()-3 }} lainnya</div>@endif
                    </div>
                @endfor
            </div>
            <div class="small text-muted-2 mt-3">Keterangan Tipe Aktivitas: @foreach(\App\Models\Activity::types() as $k=>$v)<span class="status-soft st-gray me-1">{{ $v }}</span>@endforeach</div>
        </div>
        <aside>
            <div class="info-card mb-3"><h6>Kalender Mini</h6><div class="d-flex justify-content-between align-items-center mb-2"><strong>{{ $first->translatedFormat('F Y') }}</strong><div><a href="{{ route('calendar.index',['month'=>$prevMonth->month,'year'=>$prevMonth->year]) }}" class="btn btn-sm btn-soft"><i class="bi bi-chevron-left"></i></a><a href="{{ route('calendar.index',['month'=>$nextMonth->month,'year'=>$nextMonth->year]) }}" class="btn btn-sm btn-soft"><i class="bi bi-chevron-right"></i></a></div></div><div class="d-grid" style="grid-template-columns:repeat(7,1fr);gap:7px;text-align:center;font-size:12px">@foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $d)<strong>{{ $d }}</strong>@endforeach @for($i=0;$i<$startBlank;$i++)<span></span>@endfor @for($d=1;$d<=$daysInMonth;$d++)<a href="{{ route('calendar.index',['month'=>$month,'year'=>$year]) }}" class="{{ $d==now()->day && $month==now()->month ? 'badge bg-primary rounded-circle' : 'text-dark' }}">{{ $d }}</a>@endfor</div></div>
            <div class="info-card mb-3"><h6>Aktivitas Hari Ini</h6>@forelse($todayActivities as $act)<div class="timeline-item" style="grid-template-columns:54px 1fr auto"><strong>{{ $act->activity_time ? \Illuminate\Support\Carbon::parse($act->activity_time)->format('H:i') : '-' }}</strong><div><div class="fw-bold">{{ $act->title }}</div><div class="small text-muted-2">{{ $act->customer?->name ?? $act->lead?->instansi ?? '-' }}</div></div><x-status-badge :status="$act->status" /></div>@empty<div class="small text-muted-2">Tidak ada aktivitas hari ini.</div>@endforelse<a href="{{ route('activities.index') }}" class="btn btn-link w-100 fw-bold">Lihat Semua Aktivitas <i class="bi bi-arrow-right"></i></a></div>
            <div class="info-card"><h6>Ringkasan Bulan Ini</h6><div class="sales-metric-list"><div class="rowx"><span>Total Aktivitas</span><strong>{{ $activities->count() }}</strong></div><div class="rowx"><span>Selesai</span><strong>{{ $activities->where('status','completed')->count() }}</strong></div><div class="rowx"><span>Terjadwal</span><strong>{{ $activities->where('status','scheduled')->count() }}</strong></div><div class="rowx"><span>Tertunda</span><strong>{{ $activities->where('status','pending')->count() }}</strong></div></div></div>
        </aside>
    </div>
</div>
@endsection
