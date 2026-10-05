@php
    $calendarBlank = $calendarFirst->dayOfWeekIso - 1;
    $calendarTypeClass = fn ($type) => match ($type) {'meeting' => 'sorange', 'call' => 'sblue', 'survey_lokasi' => 'sgreen', 'presentasi' => 'spurple', 'follow_up' => 'sorange', 'whatsapp' => 'sgreen', 'email' => 'sred', 'penawaran' => 'steal', default => 'sblue'};
    $calendarMonthTotal = $calendarActivities->flatten(1)->count();
@endphp
<section class="{{ $calendarCardClass }} activity-calendar">
    <div class="activity-calendar-head">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ $calendarUrl($calendarPrev->month, $calendarPrev->year) }}" class="btn btn-sm btn-soft" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
            <h2 class="mb-0">{{ $calendarFirst->translatedFormat('F Y') }}</h2>
            <a href="{{ $calendarUrl($calendarNext->month, $calendarNext->year) }}" class="btn btn-sm btn-soft" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
            @unless($calendarFirst->isSameMonth(now()))
                <a href="{{ $calendarUrl(now()->month, now()->year) }}" class="btn btn-sm btn-link">Bulan ini</a>
            @endunless
        </div>
        <form method="GET" class="d-flex flex-wrap gap-2">
            <input type="hidden" name="view" value="calendar">
            <input type="hidden" name="cal_month" value="{{ $calendarFirst->month }}">
            <input type="hidden" name="cal_year" value="{{ $calendarFirst->year }}">
            <select name="type" class="form-select form-select-sm"><option value="">Semua Jenis Aktivitas</option>@foreach(\App\Models\Activity::types() as $key => $label)<option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>@endforeach</select>
            @unless(auth()->user()->isSales())
                <select name="sales_id" class="form-select form-select-sm"><option value="">Semua Sales</option>@foreach($salesUsers ?? [] as $sales)<option value="{{ $sales->id }}" @selected((string) request('sales_id') === (string) $sales->id)>{{ $sales->name }}</option>@endforeach</select>
            @endunless
            <button class="btn btn-sm btn-soft" aria-label="Terapkan filter"><i class="bi bi-funnel"></i></button>
        </form>
    </div>
    <div class="small text-muted-2 mb-2">{{ $calendarMonthTotal }} aktivitas bulan ini. Klik aktivitas untuk melihat detailnya.</div>
    <div class="sales-calendar-grid">
        @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $dayName)<div class="sales-cal-head">{{ $dayName }}</div>@endforeach
        @for($i = 0; $i < $calendarBlank; $i++)<div class="sales-cal-cell activity-calendar-blank"></div>@endfor
        @for($day = 1; $day <= $calendarFirst->daysInMonth; $day++)
            @php
                $calendarDate = $calendarFirst->copy()->day($day);
                $dayItems = $calendarActivities[$calendarDate->format('Y-m-d')] ?? collect();
            @endphp
            <div class="sales-cal-cell {{ $dayItems->isEmpty() ? 'is-empty' : '' }}">
                <div class="sales-cal-day calendar-day-dot {{ $calendarDate->isToday() ? 'active' : '' }}"><span class="activity-calendar-dayname">{{ $calendarDate->translatedFormat('D') }}, </span>{{ $day }}</div>
                @foreach($dayItems->take(3) as $act)
                    <a href="{{ $activityUrl($act->id) }}#activity-customer-detail" class="sales-cal-event {{ $calendarTypeClass($act->type) }} {{ $selectedActivity && $selectedActivity->id === $act->id ? 'selected' : '' }}" title="{{ $act->title }}">
                        {{ $act->activity_time ? \Illuminate\Support\Carbon::parse($act->activity_time)->format('H:i').' · ' : '' }}{{ \Illuminate\Support\Str::limit($act->title, 22) }}
                        <small class="d-block">{{ $act->customer?->name ?? $act->lead?->instansi ?? '' }}</small>
                    </a>
                @endforeach
                @if($dayItems->count() > 3)
                    <a href="{{ route('activities.index', array_merge(request()->except('page', 'period', 'activity'), ['view' => 'tracking', 'date' => $calendarDate->format('Y-m-d')])) }}" class="small fw-bold text-primary text-decoration-none">+{{ $dayItems->count() - 3 }} lainnya</a>
                @endif
            </div>
        @endfor
    </div>
</section>
