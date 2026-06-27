@props(['icon' => 'bi-graph-up', 'label' => '', 'value' => '', 'sub' => null, 'color' => 'primary'])
<div class="stat-card">
    <div class="stat-icon bg-{{ $color }}-soft text-{{ $color }}"><i class="bi {{ $icon }}"></i></div>
    <div class="stat-body">
        <div class="stat-label">{{ $label }}</div>
        <div class="stat-value">{{ $value }}</div>
        @if($sub)<div class="stat-sub">{{ $sub }}</div>@endif
    </div>
</div>
