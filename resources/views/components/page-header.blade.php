@props(['title' => '', 'subtitle' => null])
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        @if($subtitle)<p class="page-subtitle">{{ $subtitle }}</p>@endif
    </div>
    <div class="page-actions">{{ $slot }}</div>
</div>
