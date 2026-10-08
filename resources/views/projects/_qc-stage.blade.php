@php
    $qcPrefix = $installation ? 'qc_installation' : 'qc';
    $qcTitle = $installation ? 'QC Pemasangan' : 'QC Produksi';
    $qcRoute = $installation ? 'project-workflow.qc-installation' : 'project-workflow.qc';
    $qcAttachment = $installation ? 'qc-installation' : 'qc';
    $qcComplete = (bool) $workflow->{"{$qcPrefix}_completed"};
    $qcProgress = $workflow->qcProgress($installation);
    $qcValues = $workflow->{"{$qcPrefix}_checklist"} ?? [];
    $qcDefinition = $installation ? \App\Models\ProjectWorkflow::qcChecklistDefinition($project, $showPrices, true) : $qcChecklistDefinition;
    $qcReady = $installation ? $workflow->qc_completed : $workflow->production_status === 'production_finished';
@endphp
<div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div><h3>{{ $qcTitle }}</h3><small class="text-muted-2">{{ $installation ? 'Pemeriksaan hasil pemasangan di lokasi customer' : 'Pemeriksaan hasil produksi sesuai spesifikasi penawaran' }}</small></div>
    <x-status-badge :status="$qcComplete ? 'approved' : 'pending'" :label="$qcComplete ? 'Selesai' : ($qcProgress > 0 ? 'Dalam Pemeriksaan' : 'Belum Dimulai')" />
</div>
@if($installation ? auth()->user()->canUpdateQcInstallation() : auth()->user()->canUpdateQcProduction())
    @if(!$qcReady)
        <div class="alert alert-info py-2 small">{{ $installation ? 'QC Pemasangan dapat diisi setelah QC Produksi selesai.' : 'QC Produksi dapat diisi setelah produksi selesai.' }}</div>
    @endif
    <form method="POST" action="{{ route($qcRoute, $project) }}" enctype="multipart/form-data" data-qc-stage>
        @csrf @method('PUT')
        <fieldset @disabled(!$qcReady)>
            <label class="form-label d-flex justify-content-between align-items-center" for="{{ $qcPrefix }}_progress"><span>Progress {{ $qcTitle }}</span><output class="progress-range-value" for="{{ $qcPrefix }}_progress" data-qc-value>{{ old($qcPrefix.'_progress', $qcProgress) }}%</output></label>
            <input class="form-range" id="{{ $qcPrefix }}_progress" type="range" name="{{ $qcPrefix }}_progress" min="0" max="100" step="5" value="{{ old($qcPrefix.'_progress', $qcProgress) }}" data-qc-range>
            <div class="progress mb-3" style="height:8px"><div class="progress-bar" role="progressbar" aria-label="Progress {{ $qcTitle }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ old($qcPrefix.'_progress', $qcProgress) }}" style="width:{{ old($qcPrefix.'_progress', $qcProgress) }}%" data-qc-bar></div></div>
            <div class="qc-checklist mb-3">
                @forelse($qcDefinition as $qcItem)
                    <div class="qc-item">
                        <div class="fw-bold mb-1">{{ $qcItem['item_name'] }} @if($qcItem['variant'])<small class="text-muted-2">- {{ $qcItem['variant'] }}</small>@endif</div>
                        @foreach($qcItem['checks'] as $check)
                            <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="{{ $qcPrefix }}_checklist[{{ $check['key'] }}]" value="1" id="{{ $qcPrefix }}_{{ $check['key'] }}" @checked(old($qcPrefix.'_checklist.'.$check['key'], $qcValues[$check['key']] ?? false))><label class="form-check-label small" for="{{ $qcPrefix }}_{{ $check['key'] }}">{{ $check['label'] }}</label></div>
                        @endforeach
                    </div>
                @empty
                    <div class="small text-muted-2">Belum ada item penawaran untuk diperiksa.</div>
                @endforelse
            </div>
            <label class="form-label" for="{{ $qcPrefix }}_note">Catatan {{ $qcTitle }}</label><textarea id="{{ $qcPrefix }}_note" name="{{ $qcPrefix }}_note" class="form-control mb-3" rows="2">{{ old($qcPrefix.'_note', $workflow->{$qcPrefix.'_note'}) }}</textarea>
            <label class="form-label" for="{{ $qcPrefix }}_document">Lampiran {{ $qcTitle }} (opsional, PDF)</label><input id="{{ $qcPrefix }}_document" class="form-control mb-3" type="file" name="{{ $qcPrefix }}_document" accept="application/pdf,.pdf">
            <input type="hidden" name="{{ $qcPrefix }}_completed" value="0">
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="{{ $qcPrefix }}_completed" value="1" id="{{ $qcPrefix }}_complete" @checked(old($qcPrefix.'_completed', $qcComplete)) data-qc-completed><label class="form-check-label fw-semibold" for="{{ $qcPrefix }}_complete">Semua pemeriksaan selesai dan lolos {{ $qcTitle }}</label></div>
            <button class="btn btn-primary w-100"><i class="bi bi-save me-1" aria-hidden="true"></i>Simpan {{ $qcTitle }}</button>
        </fieldset>
    </form>
@else
    <div class="d-flex justify-content-between mb-2"><span>Progress {{ $qcTitle }}</span><strong>{{ $qcProgress }}%</strong></div>
    <div class="progress mb-3" style="height:8px"><div class="progress-bar" role="progressbar" aria-label="Progress {{ $qcTitle }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $qcProgress }}" style="width:{{ $qcProgress }}%"></div></div>
    @if($workflow->{$qcPrefix.'_note'})<p class="small mb-3">{{ $workflow->{$qcPrefix.'_note'} }}</p>@endif
@endif
@if($workflow->{$qcPrefix.'_document_path'})
    <div class="attachment-box mt-3"><div class="small fw-semibold text-truncate">{{ $workflow->{$qcPrefix.'_document_name'} }}</div><div class="mt-2"><a target="_blank" rel="noopener" href="{{ route('project-workflow.attachment', [$project, $qcAttachment]) }}" class="btn btn-sm btn-soft">Lihat</a> <a href="{{ route('project-workflow.attachment', [$project, $qcAttachment, 'download' => 1]) }}" class="btn btn-sm btn-soft">Unduh</a></div></div>
@endif
