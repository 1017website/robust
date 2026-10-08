<section class="card-r mt-3" id="workflow-history" aria-labelledby="workflow-history-title">
    <div class="card-head"><h2 id="workflow-history-title">Riwayat Produksi &amp; QC</h2></div>
    <p class="small text-muted-2">Setiap pembaruan baru dicatat bersama pengguna, waktu, progres, catatan, dan lampirannya.</p>
    @forelse($workflowHistory as $entry)
        @php
            $before = $entry->meta['before'] ?? [];
            $after = $entry->meta['after'] ?? [];
            $production = $entry->action === 'production_updated';
            $statusText = fn ($state) => $production
                ? (\App\Models\ProjectWorkflow::productionStatuses()[$state['status'] ?? ''] ?? '-')
                : (!empty($state['completed']) ? 'Selesai' : 'Belum selesai');
        @endphp
        <article class="border-top py-3">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                <strong>{{ $entry->meta['stage'] ?? $entry->description }}</strong>
                <small>{{ $entry->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB · {{ $entry->user?->name ?? 'Pengguna dihapus' }}</small>
            </div>
            <div class="small">Status: {{ $statusText($before) }} → {{ $statusText($after) }}</div>
            <div class="small">Progres: {{ $before['progress'] ?? 0 }}% → {{ $after['progress'] ?? 0 }}%</div>
            @if($production)
                <div class="small">Checklist Produksi: {{ !empty($before['report_completed']) ? 'Lengkap' : 'Belum lengkap' }} → {{ !empty($after['report_completed']) ? 'Lengkap' : 'Belum lengkap' }}</div>
            @endif
            <div class="small mt-2 text-break" style="white-space: pre-wrap">{{ $after['note'] ?: 'Tanpa catatan.' }}</div>
            @if(!empty($before['note']) && $before['note'] !== ($after['note'] ?? null))
                <details class="small mt-2"><summary>Catatan sebelumnya</summary><div class="text-break" style="white-space: pre-wrap">{{ $before['note'] }}</div></details>
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
        <x-empty text="Belum ada riwayat pembaruan Produksi atau QC. Pembaruan berikutnya akan tercatat di sini." />
    @endforelse
    {{ $workflowHistory->links() }}
</section>
