@php
    $stages = collect([
        ['label' => 'Produksi', 'status' => $statusLabel, 'progress' => (int) $workflow->production_progress,
            'note' => $workflow->production_note, 'time' => $workflow->production_updated_at, 'user' => $workflow->productionUpdater, 'tab' => 'production', 'target' => $workflow->production_target_date],
        ['label' => 'QC Produksi', 'status' => $workflow->qcStatusLabel(),
            'progress' => $workflow->qcProgress(), 'note' => $workflow->qc_note, 'time' => $workflow->qc_updated_at, 'user' => $workflow->qcUpdater, 'tab' => 'qc-production', 'target' => $workflow->qc_target_date],
        ['label' => 'Delivery', 'status' => $deliveryStatusLabel, 'progress' => null,
            'note' => $workflow->delivery_note, 'time' => $workflow->delivery_updated_at, 'user' => $workflow->deliveryUpdater, 'tab' => 'delivery'],
        ['label' => 'QC Pemasangan', 'status' => $workflow->qcStatusLabel(true),
            'progress' => $workflow->qcProgress(true), 'note' => $workflow->qc_installation_note, 'time' => $workflow->qc_installation_updated_at, 'user' => $workflow->qcInstallationUpdater, 'tab' => 'qc-installation', 'target' => $workflow->qc_installation_target_date],
    ]);
    $latestWork = $stages->filter(fn ($stage) => $stage['time'])->sortByDesc(fn ($stage) => $stage['time']->getTimestamp())->first();
    if ($latestHistoryEntry && (!$latestWork || $latestHistoryEntry->created_at->gte($latestWork['time']))) {
        $state = $latestHistoryEntry->meta['after'] ?? [];
        $latestWork = [
            'label' => $latestHistoryEntry->meta['stage'] ?? $latestHistoryEntry->description,
            'status' => str_ends_with($latestHistoryEntry->action, '_target_date_updated') ? 'Target selesai: '.\Illuminate\Support\Carbon::parse($state['target_date'])->format('d/m/Y') : match ($latestHistoryEntry->action) {
                'production_updated' => \App\Models\ProjectWorkflow::productionStatuses()[$state['status'] ?? ''] ?? '-',
                'delivery_updated' => \App\Models\ProjectWorkflow::deliveryStatuses()[$state['status'] ?? ''] ?? '-',
                'delivery_order_updated' => $state['code'] ?? 'DO disimpan',
                default => !empty($state['completed']) ? 'Selesai dan lolos QC' : (($state['result'] ?? null) === 'failed' ? 'Belum lolos / perlu perbaikan' : (!empty($state['progress']) || ($state['result'] ?? null) === 'in_progress' ? 'Masih diperiksa' : 'Belum dimulai')),
            },
            'progress' => str_ends_with($latestHistoryEntry->action, '_target_date_updated') ? null : ($state['progress'] ?? null),
            'note' => str_ends_with($latestHistoryEntry->action, '_target_date_updated') ? ($latestHistoryEntry->meta['reason'] ?? null) : ($state['note'] ?? $state['notes'] ?? null),
            'time' => $latestHistoryEntry->created_at, 'user' => $latestHistoryEntry->user,
        ];
    }
@endphp
<section class="workflow-card mb-3" aria-labelledby="work-summary-title">
    <div class="card-head"><h2 id="work-summary-title">Status Pekerjaan</h2></div>
    <div class="mb-3 pb-3 border-bottom">
        <div class="small text-muted-2">Pekerjaan terakhir</div>
        @if($latestWork)
            <div class="fw-semibold">{{ $latestWork['label'] }} · {{ $latestWork['status'] }}@if($latestWork['progress'] !== null) · {{ $latestWork['progress'] }}%@endif</div>
            <div class="small mt-1">{{ $latestWork['time']->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB · {{ $latestWork['user']?->name ?? 'Pengguna tidak tersedia' }}</div>
            @if($latestWork['note'])<div class="small mt-2 text-break" style="white-space: pre-wrap">{{ $latestWork['note'] }}</div>@endif
        @else
            <div>Belum ada pembaruan pekerjaan.</div>
        @endif
    </div>
    <div class="work-stage-list">
        @foreach($stages as $stage)
            <div>
                @if(in_array($stage['tab'], $visibleWorkTabs, true))
                    <a class="fw-semibold" href="#{{ $stage['tab'] }}">{{ $stage['label'] }}</a>
                @else
                    <span class="fw-semibold">{{ $stage['label'] }}</span>
                @endif
                <div class="mt-1">{{ $stage['status'] }}@if($stage['progress'] !== null) · {{ $stage['progress'] }}%@endif</div>
                @if(isset($stage['target']))<div class="mt-1">Target selesai: {{ $stage['target']->format('d/m/Y') }}</div>@endif
                @if($stage['time'])
                    <div class="small mt-1">{{ $stage['time']->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB · {{ $stage['user']?->name ?? 'Pengguna tidak tersedia' }}</div>
                @else
                    <div class="small text-muted-2 mt-1">Belum ada pembaruan</div>
                @endif
            </div>
        @endforeach
    </div>
</section>
