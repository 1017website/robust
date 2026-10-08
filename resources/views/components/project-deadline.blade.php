@props(['project'])
@if($indicator = \App\Support\ProjectDeadline::indicator($project))
    <div class="small fw-semibold text-{{ $indicator['tone'] === 'danger' ? 'danger' : 'dark' }} mt-1">
        <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>{{ $indicator['label'] }}
    </div>
@endif
