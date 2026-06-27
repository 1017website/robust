@props(['status' => '', 'label' => null])
<span class="badge text-bg-{{ \App\Support\Format::badgeClass($status) }}">{{ $label ?? \Illuminate\Support\Str::headline($status) }}</span>
