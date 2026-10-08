@php
    $itemProgressValues = old($itemPrefix.'_item_progress', $workflow->{$itemPrefix.'_item_progress'} ?? []);
    $itemStageCompleted = $itemPrefix === 'production' ? $workflow->production_status === 'production_finished' : (bool) $workflow->{$itemPrefix.'_completed'};
@endphp
<div class="mb-3">
    <div class="fw-semibold mb-2">Progress per Item</div>
    @forelse($workItems as $workItem)
        @php($itemPercent = (int) ($itemProgressValues[$workItem->id] ?? ($itemStageCompleted ? 100 : 0)))
        <div class="border rounded p-3 mb-2" data-item-progress-row>
            <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                <div><div class="fw-semibold">{{ $workItem->name }}</div><small class="text-muted-2">{{ rtrim(rtrim(number_format((float) $workItem->qty, 2, ',', '.'), '0'), ',') }} {{ $workItem->unit ?: 'Unit' }}</small></div>
                <output class="fw-bold text-primary" data-item-progress-value>{{ $itemPercent }}%</output>
            </div>
            @if($itemEditable)
                <input type="range" class="form-range" name="{{ $itemPrefix }}_item_progress[{{ $workItem->id }}]" min="0" max="100" step="5" value="{{ $itemPercent }}" aria-label="Progress {{ $workItem->name }}" data-item-progress>
            @else
                <div class="progress" style="height:8px"><div class="progress-bar" role="progressbar" aria-label="Progress {{ $workItem->name }}" aria-valuenow="{{ $itemPercent }}" aria-valuemin="0" aria-valuemax="100" style="width:{{ $itemPercent }}%"></div></div>
            @endif
        </div>
    @empty
        <p class="small text-muted-2">Belum ada item project.</p>
    @endforelse
</div>
