@php
    $progressWorkflow = $progressProject->workflow;
@endphp
@if($workRole === 'production')
    <div class="small fw-semibold">{{ \App\Models\ProjectWorkflow::productionStatuses()[$progressWorkflow?->production_status ?? 'stock'] }}</div>
    <div class="prog my-2"><span style="width:{{ $progressWorkflow?->production_progress ?? 0 }}%"></span></div>
    <small>Produksi {{ $progressWorkflow?->production_progress ?? 0 }}%</small>
@elseif(in_array($workRole, ['qc', 'qc_production', 'qc_installation'], true))
    @foreach([false => 'QC Produksi', true => 'QC Pemasangan'] as $installation => $qcLabel)
        @continue(($workRole === 'qc_production' && $installation) || ($workRole === 'qc_installation' && !$installation))
        @php
            $qcProgress = $progressWorkflow?->qcProgress((bool) $installation) ?? 0;
            $qcCompleted = $installation ? $progressWorkflow?->qc_installation_completed : $progressWorkflow?->qc_completed;
        @endphp
        <div class="small @if($installation) mt-2 @endif"><strong>{{ $qcLabel }}</strong>: {{ $qcCompleted ? 'Selesai' : ($qcProgress > 0 ? 'Dalam Pemeriksaan' : 'Belum Dimulai') }} · {{ $qcProgress }}%</div>
        <div class="prog mt-1"><span style="width:{{ $qcProgress }}%"></span></div>
    @endforeach
@elseif($workRole === 'delivery')
    <div class="small fw-semibold">{{ \App\Models\ProjectWorkflow::deliveryStatuses()[$progressWorkflow?->delivery_status ?? 'scheduling'] }}</div>
    <div class="small mt-1">Jadwal: {{ $progressWorkflow?->delivery_scheduled_at?->format('d/m/Y H:i') ?? 'Belum dijadwalkan' }}</div>
    <div class="small mt-1">DO/BA keluar: {{ $progressWorkflow?->delivery_out_completed ? 'Selesai' : 'Belum selesai' }}</div>
    <div class="small mt-1">DO/BA kembali: {{ $progressWorkflow?->delivery_returned_completed ? 'Selesai' : 'Belum selesai' }}</div>
@else
    <div class="prog my-2"><span style="width:{{ $progressProject->progress }}%"></span></div><small>{{ $progressProject->progress }}%</small>
@endif
