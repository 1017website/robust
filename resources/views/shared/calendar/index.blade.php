@extends('layouts.app')
@section('title', 'Calendar')
@section('content')
@php
    $first = \Illuminate\Support\Carbon::create($year,$month,1);
    $daysInMonth = $first->daysInMonth;
    $startBlank = $first->dayOfWeekIso - 1;
    $prevMonth = $first->copy()->subMonth();
    $nextMonth = $first->copy()->addMonth();
@endphp
<x-page-header :title="'Calendar · '.$first->translatedFormat('F Y')" subtitle="Agenda aktivitas bulanan">
    <a href="{{ route('calendar.index',['month'=>$prevMonth->month,'year'=>$prevMonth->year]) }}" class="btn btn-soft btn-sm"><i class="bi bi-chevron-left"></i></a>
    <a href="{{ route('calendar.index') }}" class="btn btn-soft btn-sm">Hari Ini</a>
    <a href="{{ route('calendar.index',['month'=>$nextMonth->month,'year'=>$nextMonth->year]) }}" class="btn btn-soft btn-sm"><i class="bi bi-chevron-right"></i></a>
</x-page-header>

<div class="stat-grid">
    <x-stat-card icon="bi-calendar-day" color="primary" label="Hari Ini" :value="$stats['today']" />
    <x-stat-card icon="bi-calendar-week" color="info" label="Minggu Ini" :value="$stats['this_week']" />
    <x-stat-card icon="bi-calendar-check" color="success" label="Mendatang" :value="$stats['upcoming']" />
    <x-stat-card icon="bi-exclamation-triangle" color="danger" label="Terlambat" :value="$stats['overdue']" />
</div>

<div class="card-r">
    <div class="cal-grid">
        @foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $d)<div class="cal-head">{{ $d }}</div>@endforeach
        @for($i=0;$i<$startBlank;$i++)<div class="cal-cell empty"></div>@endfor
        @for($day=1;$day<=$daysInMonth;$day++)
            @php($date = \Illuminate\Support\Carbon::create($year,$month,$day)->format('Y-m-d'))
            @php($items = $byDate[$date] ?? collect())
            <div class="cal-cell {{ $date === now()->format('Y-m-d') ? 'today' : '' }}">
                <div class="cal-day">{{ $day }}</div>
                @foreach($items->take(3) as $act)
                    <div class="cal-event text-bg-{{ \App\Support\Format::badgeClass($act->status) }}">{{ \Illuminate\Support\Str::limit($act->title,16) }}</div>
                @endforeach
                @if($items->count()>3)<div class="small text-muted-2">+{{ $items->count()-3 }} lainnya</div>@endif
            </div>
        @endfor
    </div>
</div>
@push('styles')
<style>
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px}
.cal-head{text-align:center;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;padding:6px}
.cal-cell{min-height:96px;border:1px solid var(--line);border-radius:9px;padding:6px;background:#fff}
.cal-cell.empty{background:transparent;border:none}
.cal-cell.today{border-color:var(--brand);box-shadow:0 0 0 2px rgba(29,111,224,.12)}
.cal-day{font-size:12px;font-weight:700;margin-bottom:4px}
.cal-event{font-size:10px;padding:2px 6px;border-radius:5px;margin-bottom:3px;color:#fff;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
</style>
@endpush
@endsection
