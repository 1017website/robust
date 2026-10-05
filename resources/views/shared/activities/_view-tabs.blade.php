<nav class="sales-chip-row mb-3" aria-label="Tampilan aktivitas">
    @foreach(['pipeline' => 'Pipeline', 'list' => 'Activity List', 'calendar' => 'Calendar', 'tracking' => 'Tracking Harian'] as $key => $label)
        <a class="sales-chip {{ $view === $key ? 'active' : '' }}" href="{{ $viewUrl($key) }}" @if($view === $key) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
