@extends('layouts.app')
@section('title', 'Calendar')
@section('content')
@php
    $start = \Illuminate\Support\Carbon::create($year, $month, 1);
    $daysInMonth = $start->daysInMonth;
    $firstDow = $start->dayOfWeekIso;
    $prev = $start->copy()->subMonth();
    $next = $start->copy()->addMonth();
    $types = ['Meeting'=>'orange','Produksi'=>'blue','QC'=>'green','Review'=>'purple','Dokumen'=>'amber'];
@endphp
<div class="drafter-ui">
    <div class="drafter-page-head"><div><h1 class="page-title mb-1">Calendar</h1><div class="page-subtitle">Jadwal dan deadline semua project & task.</div></div></div>
    <div class="drafter-calendar-layout">
        <main>
            <div class="calendar-toolbar"><a class="btn btn-soft" href="{{ route('drafter.calendar.index') }}">Today</a><a class="btn btn-soft" href="{{ route('drafter.calendar.index',['month'=>$prev->month,'year'=>$prev->year]) }}"><i class="bi bi-chevron-left"></i></a><a class="btn btn-soft" href="{{ route('drafter.calendar.index',['month'=>$next->month,'year'=>$next->year]) }}"><i class="bi bi-chevron-right"></i></a><strong>{{ $start->translatedFormat('F Y') }}</strong><span class="ms-auto"></span><button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Event</button></div>
            <div class="calendar-toolbar"><select class="form-select"><option>Semua Project</option></select><select class="form-select"><option>Semua Tipe</option></select><select class="form-select"><option>Semua PIC</option></select><button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            <div class="drafter-calendar-grid">
                @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $d)<div class="cal-head">{{ $d }}</div>@endforeach
                @for($i=1;$i<$firstDow;$i++)<div class="cal-cell muted"></div>@endfor
                @for($day=1;$day<=$daysInMonth;$day++)
                    @php($date = \Illuminate\Support\Carbon::create($year,$month,$day))
                    <div class="cal-cell {{ $date->isToday() ? 'today' : '' }}"><div class="day-num">{{ $day }}</div>@foreach(($byDate[$date->format('Y-m-d')] ?? collect())->take(3) as $event)<div class="cal-event {{ strtolower($types[$event->type] ?? 'blue') }}"><strong>{{ $event->time ?: '' }} {{ $event->title }}</strong><small>{{ $event->subtitle }}</small></div>@endforeach @if(($byDate[$date->format('Y-m-d')] ?? collect())->count() > 3)<small>+{{ ($byDate[$date->format('Y-m-d')] ?? collect())->count()-3 }} more</small>@endif</div>
                @endfor
            </div>
        </main>
        <aside>
            <div class="info-card"><h6><i class="bi bi-calendar2-event me-1"></i>{{ today()->translatedFormat('l, d M Y') }}</h6></div>
            <div class="info-card"><h6>Event Hari Ini</h6>@forelse($todayEvents as $ev)<div class="timeline-line"><span>{{ $ev->time ?: '-' }}</span><i></i><div><strong>{{ $ev->title }}</strong><small>{{ $ev->subtitle }}</small><x-status-badge :status="$ev->type" :label="$ev->type" /></div></div>@empty<div class="small text-muted-2">Tidak ada event hari ini.</div>@endforelse</div>
            <div class="info-card"><h6>Event Mendatang</h6>@forelse($upcomingEvents as $ev)<div class="mini-row"><span>{{ $ev->date->translatedFormat('d M Y') }}</span><strong>{{ $ev->title }}</strong><x-status-badge :status="$ev->type" :label="$ev->type" /></div>@empty<div class="small text-muted-2">Tidak ada event mendatang.</div>@endforelse</div>
            <div class="info-card"><h6>Legenda</h6><div class="legend-grid">@foreach($types as $type=>$cls)<span><i class="legend-dot {{ $cls }}"></i>{{ $type }}</span>@endforeach</div></div>
        </aside>
    </div>
</div>
@endsection
