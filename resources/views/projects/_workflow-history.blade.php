@php
    // Riwayat dikelompokkan per bagian kerja; tiap grup dapat dibuka/ditutup.
    $historySections = ['production' => 'Produksi', 'qc' => 'QC Produksi', 'delivery' => 'Delivery', 'qc_installation' => 'QC Pemasangan'];
    $historySectionOf = fn ($action) => match (true) {
        str_starts_with($action, 'production_') => 'production',
        str_starts_with($action, 'qc_installation_') => 'qc_installation',
        str_starts_with($action, 'qc_') => 'qc',
        default => 'delivery',
    };
    $historyGroups = $workflowHistory->groupBy(fn ($entry) => $historySectionOf($entry->action));
    $latestHistorySection = $workflowHistory->first() ? $historySectionOf($workflowHistory->first()->action) : null;
    $historyPerGroup = 10;
@endphp
@push('styles')
<style>
    .history-group { border: 1px solid #e7ebf1; border-radius: 12px; background: #fff; }
    .history-group + .history-group { margin-top: .75rem; }
    .history-group > summary { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; padding: .85rem 1rem; cursor: pointer; list-style: none; }
    .history-group > summary::-webkit-details-marker { display: none; }
    .history-group > summary::before { content: "\25B8"; color: #667085; transition: transform .15s; }
    .history-group[open] > summary::before { transform: rotate(90deg); }
    .history-group > summary:focus-visible { outline: 3px solid #0b63ce; outline-offset: 2px; border-radius: 12px; }
    .history-group-body { padding: 0 1rem .5rem; }
    .history-older > summary { cursor: pointer; padding: .75rem 0; color: #0b63ce; font-weight: 600; }
</style>
@endpush
<section class="card-r mt-3" id="workflow-history" aria-labelledby="workflow-history-title">
    <div class="card-head d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 id="workflow-history-title">Riwayat Pekerjaan</h2>
        @if($historyGroups->count() > 1)
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-soft" data-history-toggle="open">Buka semua</button>
                <button type="button" class="btn btn-sm btn-soft" data-history-toggle="close">Tutup semua</button>
            </div>
        @endif
    </div>
    <p class="small text-muted-2">Riwayat dikelompokkan per bagian. Klik nama bagian untuk membuka atau menutup; setiap pembaruan dicatat bersama pengguna, waktu, progres, catatan, dan lampirannya.</p>
    @foreach($historySections as $sectionKey => $sectionLabel)
        @continue(! $historyGroups->has($sectionKey))
        @php($entries = $historyGroups[$sectionKey])
        <details class="history-group" id="history-{{ str_replace('_', '-', $sectionKey) }}" data-history-group @if($sectionKey === $latestHistorySection) open @endif>
            <summary>
                <strong>{{ $sectionLabel }}</strong>
                <span class="badge text-bg-light">{{ $entries->count() }} pembaruan</span>
                <small class="text-muted-2 ms-auto">Terakhir {{ $entries->first()->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB · {{ $entries->first()->user?->name ?? 'Pengguna dihapus' }}</small>
            </summary>
            <div class="history-group-body">
                @foreach($entries->take($historyPerGroup) as $entry)
                    @include('projects._workflow-history-entry')
                @endforeach
                @if($entries->count() > $historyPerGroup)
                    <details class="history-older border-top">
                        <summary>Tampilkan {{ $entries->count() - $historyPerGroup }} riwayat {{ $sectionLabel }} lebih lama</summary>
                        @foreach($entries->skip($historyPerGroup) as $entry)
                            @include('projects._workflow-history-entry')
                        @endforeach
                    </details>
                @endif
            </div>
        </details>
    @endforeach
    @if($workflowHistory->isEmpty())
        <x-empty text="Belum ada riwayat pembaruan pekerjaan. Pembaruan Produksi, QC, dan Delivery berikutnya akan tercatat di sini." />
    @endif
</section>
@push('scripts')
<script>
document.querySelectorAll('[data-history-toggle]').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('[data-history-group]').forEach(group => group.open = button.dataset.historyToggle === 'open');
}));
</script>
@endpush
