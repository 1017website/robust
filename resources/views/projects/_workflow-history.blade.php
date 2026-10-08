<section class="card-r mt-3" id="workflow-history" aria-labelledby="workflow-history-title">
    <div class="card-head"><h2 id="workflow-history-title">Riwayat Pekerjaan</h2></div>
    <p class="small text-muted-2">Setiap pembaruan baru dicatat bersama pengguna, waktu, progres, catatan, dan lampirannya.</p>
    @forelse($workflowHistory as $entry)
        @php
            $before = $entry->meta['before'] ?? [];
            $after = $entry->meta['after'] ?? [];
            $production = $entry->action === 'production_updated';
            $delivery = $entry->action === 'delivery_updated';
            $deliveryOrderEntry = $entry->action === 'delivery_order_updated';
            $statusText = fn ($state) => match (true) {
                $production => \App\Models\ProjectWorkflow::productionStatuses()[$state['status'] ?? ''] ?? '-',
                $delivery => \App\Models\ProjectWorkflow::deliveryStatuses()[$state['status'] ?? ''] ?? '-',
                $deliveryOrderEntry => $state['code'] ?? 'Belum dibuat',
                default => !empty($state['completed']) ? 'Selesai' : 'Belum selesai',
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
            <div class="small">Status: {{ $statusText($before) }} → {{ $statusText($after) }}</div>
            @if(!$delivery && !$deliveryOrderEntry)<div class="small">Progres: {{ $before['progress'] ?? 0 }}% → {{ $after['progress'] ?? 0 }}%</div>@endif
            @if($delivery)
                <div class="small">Jadwal: {{ $formatDate($before['scheduled_at'] ?? null) }} → {{ $formatDate($after['scheduled_at'] ?? null) }}</div>
                <div class="small">Penerima: {{ $before['receiver_name'] ?? '-' }} → {{ $after['receiver_name'] ?? '-' }}</div>
                <div class="small">Diterima: {{ $formatDate($before['received_at'] ?? null) }} → {{ $formatDate($after['received_at'] ?? null) }}</div>
                <div class="small">DO/BA keluar: {{ !empty($after['out_completed']) ? 'Selesai' : 'Belum selesai' }} · DO/BA kembali: {{ !empty($after['returned_completed']) ? 'Selesai' : 'Belum selesai' }}</div>
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
        </article>
    @empty
        <x-empty text="Belum ada riwayat pembaruan pekerjaan. Pembaruan Produksi, QC, dan Delivery berikutnya akan tercatat di sini." />
    @endforelse
    {{ $workflowHistory->links() }}
</section>
