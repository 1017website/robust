@php
    $targetDateOnly = str_ends_with($entry->action, '_target_date_updated');
    $before = $entry->meta['before'] ?? [];
    $after = $entry->meta['after'] ?? [];
    $production = $entry->action === 'production_updated';
    $delivery = $entry->action === 'delivery_updated';
    $deliveryOrderEntry = $entry->action === 'delivery_order_updated';
    $statusText = fn ($state) => match (true) {
        $production => \App\Models\ProjectWorkflow::productionStatuses()[$state['status'] ?? ''] ?? '-',
        $delivery => \App\Models\ProjectWorkflow::deliveryStatuses()[$state['status'] ?? ''] ?? '-',
        $deliveryOrderEntry => $state['code'] ?? 'Belum dibuat',
        default => !empty($state['completed']) ? 'Selesai dan lolos QC' : (($state['result'] ?? null) === 'failed' ? 'Belum lolos / perlu perbaikan' : (($state['result'] ?? null) === 'in_progress' ? 'Masih diperiksa' : 'Belum selesai')),
    };
    $note = $after['note'] ?? $after['notes'] ?? null;
    $previousNote = $before['note'] ?? $before['notes'] ?? null;
    $formatDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-';
@endphp
<article class="border-top py-3">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
        <strong>{{ $entry->meta['stage'] ?? $entry->description }}</strong>
        <small>{{ $entry->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB · {{ $entry->user?->name ?? 'Pengguna dihapus' }}</small>
    </div>
    @if($targetDateOnly)
        <div>Target selesai: {{ !empty($before['target_date']) ? \Illuminate\Support\Carbon::parse($before['target_date'])->format('d/m/Y') : 'Belum diisi' }} → {{ \Illuminate\Support\Carbon::parse($after['target_date'])->format('d/m/Y') }}</div>
        @if(!empty($entry->meta['reason']))<p class="mt-2 mb-0 text-break">Alasan: {{ $entry->meta['reason'] }}</p>@endif
    @else
    <div class="small">Status: {{ $statusText($before) }} → {{ $statusText($after) }}</div>
    @if(!$delivery && !$deliveryOrderEntry)<div class="small">Progres: {{ $before['progress'] ?? 0 }}% → {{ $after['progress'] ?? 0 }}%</div>@endif
    @if(!empty($after['item_progress']))
        <details class="small mt-2">
            <summary>Progress per Item</summary>
            <ul class="mt-2 mb-0">
                @foreach($after['item_progress'] as $itemId => $itemProgress)
                    <li>{{ $after['item_names'][$itemId] ?? 'Item' }}: {{ $before['item_progress'][$itemId] ?? 0 }}% → {{ $itemProgress }}%</li>
                @endforeach
            </ul>
        </details>
    @endif
    @if(!empty($after['target_date']))<div class="small">Target selesai: {{ $formatDate($before['target_date'] ?? null) }} → {{ $formatDate($after['target_date']) }}</div>@endif
    @if($delivery)
        <div class="small">Jadwal: {{ $formatDate($before['scheduled_at'] ?? null) }} → {{ $formatDate($after['scheduled_at'] ?? null) }}</div>
        <div class="small">Penerima: {{ $before['receiver_name'] ?? '-' }} → {{ $after['receiver_name'] ?? '-' }}</div>
        <div class="small">Diterima: {{ $formatDate($before['received_at'] ?? null) }} → {{ $formatDate($after['received_at'] ?? null) }}</div>
        <div class="small">DO/BA keluar: {{ !empty($after['out_completed']) ? 'Selesai' : 'Belum selesai' }} · DO/BA kembali: {{ !empty($after['returned_completed']) ? 'Selesai' : 'Belum selesai' }}</div>
    @endif
    @if(!empty($after['items']))
        <details class="small mt-2">
            <summary>Daftar barang: {{ count($after['items']) }} item{{ ($before['items'] ?? []) != $after['items'] ? ' (diubah)' : '' }}</summary>
            <ul class="mt-2 mb-0">
                @foreach($after['items'] as $item)
                    <li class="text-break">{{ $item['name'] ?? '-' }} — {{ rtrim(rtrim(number_format((float) ($item['qty'] ?? 0), 2, ',', '.'), '0'), ',') }} {{ $item['unit'] ?? '' }}</li>
                @endforeach
            </ul>
        </details>
    @endif
    @if($deliveryOrderEntry)
        <div class="small">Tanggal DO: {{ $formatDate($before['delivery_date'] ?? null) }} → {{ $formatDate($after['delivery_date'] ?? null) }}</div>
        <div class="small text-break">Alamat: {{ $after['delivery_address'] ?? '-' }}</div>
    @endif
    @if($production)
        <div class="small">Checklist Produksi: {{ !empty($before['report_completed']) ? 'Lengkap' : 'Belum lengkap' }} → {{ !empty($after['report_completed']) ? 'Lengkap' : 'Belum lengkap' }}</div>
    @endif
    <div class="small mt-2 text-break" style="white-space: pre-wrap">{{ $note ?: 'Tanpa catatan.' }}</div>
    @if($previousNote && $previousNote !== $note)
        <details class="small mt-2"><summary>Catatan sebelumnya</summary><div class="text-break" style="white-space: pre-wrap">{{ $previousNote }}</div></details>
    @endif
    @if(!$production && !empty($after['checklist']))
        <details class="small mt-2">
            <summary>Checklist QC: {{ count(array_filter($after['checklist'])) }} / {{ count($after['checklist']) }} diperiksa</summary>
            <ul class="mt-2 mb-0">
                @foreach($after['checklist'] as $key => $checked)
                    <li class="text-break">{{ $after['checklist_labels'][$key] ?? 'Pemeriksaan item' }}: {{ !empty($before['checklist'][$key]) ? 'Dicek' : 'Belum dicek' }} → {{ $checked ? 'Dicek' : 'Belum dicek' }}</li>
                @endforeach
            </ul>
        </details>
    @endif
    @foreach(($entry->meta['attachments'] ?? []) as $index => $attachment)
        <a class="btn btn-sm btn-soft mt-2 text-break" href="{{ route('project-workflow.history-attachment', [$project, $entry, $index]) }}">Unduh {{ $attachment['name'] }}</a>
    @endforeach
    @endif
</article>
