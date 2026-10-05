<nav class="sales-chip-row mb-3" aria-label="Tampilan aktivitas">
    @foreach(['pipeline' => 'Pipeline', 'list' => 'Activity List'] as $key => $label)
        <a class="sales-chip {{ $view === $key ? 'active' : '' }}" href="{{ $viewUrl($key) }}" @if($view === $key) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
    <a class="sales-chip" href="{{ route('calendar.index') }}">Calendar</a>
    <a class="sales-chip {{ $view === 'tracking' ? 'active' : '' }}" href="{{ $viewUrl('tracking') }}" @if($view === 'tracking') aria-current="page" @endif>Tracking Harian</a>
</nav>
