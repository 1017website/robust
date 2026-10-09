@php
    $qcPrefix = $installation ? 'qc_installation' : 'qc';
    $qcTitle = $installation ? 'QC Pemasangan' : 'QC Produksi';
    $qcRoute = $installation ? 'project-workflow.qc-installation' : 'project-workflow.qc';
    $qcAttachment = $installation ? 'qc-installation' : 'qc';
    $qcComplete = (bool) $workflow->{"{$qcPrefix}_completed"};
    $qcProgress = $workflow->qcProgress($installation);
    $qcValues = $workflow->{"{$qcPrefix}_checklist"} ?? [];
    $qcDefinition = $installation ? \App\Models\ProjectWorkflow::qcChecklistDefinition($project, $showPrices, true) : $qcChecklistDefinition;
    $qcFormValues = old($qcPrefix.'_checklist', session()->hasOldInput($qcPrefix.'_result') ? [] : $qcValues);
    $qcFormProgress = \App\Models\ProjectWorkflow::qcChecklistPercent($qcDefinition, $qcFormValues);
    $qcResult = old($qcPrefix.'_result', $workflow->qcResult($installation));
    $qcReady = $installation ? $workflow->installationQcReady() : $workflow->production_status === 'production_finished';
@endphp
<div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div><h3>{{ $qcTitle }}</h3><small class="text-muted-2">{{ $installation ? 'Pemeriksaan hasil pemasangan di lokasi customer' : 'Pemeriksaan hasil produksi sesuai spesifikasi penawaran' }}</small></div>
    <x-status-badge :status="$qcComplete ? 'approved' : ($workflow->qcResult($installation) === 'failed' ? 'rejected' : 'pending')" :label="$workflow->qcStatusLabel($installation)" />
</div>
@if($installation ? auth()->user()->canUpdateQcInstallation() : auth()->user()->canUpdateQcProduction())
    @include('projects._target-date', ['targetPrefix' => $qcPrefix, 'targetStage' => $installation ? 'qc-installation' : 'qc', 'targetLabel' => $qcTitle])
@endif
@if(!$qcComplete && ($installation ? auth()->user()->canUpdateQcInstallation() : auth()->user()->canUpdateQcProduction()))
    @if(!$qcReady)
        <div class="alert alert-info py-2 small">{{ $installation ? 'QC Pemasangan dilakukan di customer setelah QC Produksi lolos dan Delivery berstatus Terkirim atau Diterima Customer.' : 'QC Produksi dapat diisi setelah produksi selesai.' }}</div>
    @endif
    <form method="POST" action="{{ route($qcRoute, $project) }}" enctype="multipart/form-data" data-qc-stage>
        @csrf @method('PUT')
        <fieldset @disabled(!$qcReady)>
            <label class="form-label fw-semibold" for="{{ $qcPrefix }}_target_date">Tanggal target selesai {{ $qcTitle }} (wajib diisi)</label>
            <input type="date" id="{{ $qcPrefix }}_target_date" name="{{ $qcPrefix }}_target_date" class="form-control qc-note mb-3" value="{{ old($qcPrefix.'_target_date', $workflow->{$qcPrefix.'_target_date'}?->format('Y-m-d')) }}" required>
            @error($qcPrefix.'_target_date')<div class="text-danger fw-semibold mb-3" role="alert">Isi tanggal target selesai yang valid.</div>@enderror
            <div class="d-flex justify-content-between mb-3"><span>Checklist {{ $qcTitle }}</span><output aria-live="polite" data-qc-value>{{ $qcFormProgress }}%</output></div>
            <p class="qc-instruction">Centang hanya pemeriksaan yang sudah sesuai. Yang belum diperiksa atau perlu diperbaiki, biarkan kosong.</p>
            <div class="qc-checklist mb-3">
                @forelse($qcDefinition as $qcItem)
                    <div class="qc-item">
                        <div class="fw-bold mb-1">{{ $qcItem['item_name'] }} @if($qcItem['variant'])<small class="text-muted-2">- {{ $qcItem['variant'] }}</small>@endif</div>
                        @foreach($qcItem['checks'] as $check)
                            <div class="form-check qc-check-row"><input class="form-check-input" type="checkbox" data-qc-check name="{{ $qcPrefix }}_checklist[{{ $check['key'] }}]" value="1" id="{{ $qcPrefix }}_{{ $check['key'] }}" @checked($qcFormValues[$check['key']] ?? false)><label class="form-check-label" for="{{ $qcPrefix }}_{{ $check['key'] }}">{{ $check['label'] }}</label></div>
                        @endforeach
                    </div>
                @empty
                    <div class="small text-muted-2">Belum ada item penawaran untuk diperiksa.</div>
                @endforelse
            </div>
            <fieldset class="qc-result-group mb-3">
                <legend>Hasil {{ $qcTitle }} <span class="qc-required">(wajib dipilih)</span></legend>
                @foreach(['in_progress' => ['Masih diperiksa', 'Simpan dulu, lalu lanjutkan pemeriksaan nanti.'], 'failed' => ['Belum lolos / perlu perbaikan', 'Tulis bagian yang harus diperbaiki, lalu periksa kembali setelah perbaikan.'], 'passed' => ['Semua pemeriksaan selesai dan lolos '.$qcTitle, 'Semua checklist harus dicentang sebelum memilih ini.']] as $result => [$resultLabel, $resultHelp])
                    <label class="qc-result-option" for="{{ $qcPrefix }}_result_{{ $result }}">
                        <input type="radio" name="{{ $qcPrefix }}_result" id="{{ $qcPrefix }}_result_{{ $result }}" value="{{ $result }}" required data-qc-result @checked($qcResult === $result) @if($result === 'passed') data-qc-completed @endif>
                        <span><strong>{{ $resultLabel }}</strong><span class="qc-result-help">{{ $resultHelp }}</span></span>
                    </label>
                @endforeach
                <p class="qc-instruction mb-0" data-qc-result-hint aria-live="polite">Pilih hasil pemeriksaan sebelum menyimpan.</p>
                @error($qcPrefix.'_result')<div class="text-danger fw-semibold mt-2" role="alert">{{ $message }}</div>@enderror
                @error($qcPrefix.'_checklist')<div class="text-danger fw-semibold mt-2" role="alert">{{ $message }}</div>@enderror
            </fieldset>
            <label class="form-label fw-semibold" for="{{ $qcPrefix }}_note">Catatan {{ $qcTitle }} <span data-qc-note-label>(opsional)</span></label>
            <textarea id="{{ $qcPrefix }}_note" name="{{ $qcPrefix }}_note" class="form-control qc-note mb-2" rows="3" data-qc-note @required($qcResult === 'failed') aria-describedby="{{ $qcPrefix }}_note_help">{{ old($qcPrefix.'_note', $workflow->{$qcPrefix.'_note'}) }}</textarea>
            <p id="{{ $qcPrefix }}_note_help" class="qc-instruction">Jika belum lolos, tulis bagian yang bermasalah dan perbaikan yang diperlukan.</p>
            @error($qcPrefix.'_note')<div class="text-danger fw-semibold mb-3" role="alert">{{ $message }}</div>@enderror
            <label class="form-label" for="{{ $qcPrefix }}_document">Lampiran {{ $qcTitle }} (opsional, PDF)</label><input id="{{ $qcPrefix }}_document" class="form-control mb-3" type="file" name="{{ $qcPrefix }}_document" accept="application/pdf,.pdf">
            <button class="btn btn-primary btn-lg qc-save w-100"><i class="bi bi-save me-1" aria-hidden="true"></i>Simpan {{ $qcTitle }}</button>
        </fieldset>
    </form>
@else
    @if($qcComplete)
        <div class="alert alert-success qc-instruction" role="status"><strong>{{ $qcTitle }} sudah selesai dan lolos.</strong> Hasil QC dikunci dan tidak dapat diubah.</div>
    @endif
    <p class="qc-instruction">Tanggal target selesai: <strong>{{ $workflow->{$qcPrefix.'_target_date'}?->format('d/m/Y') ?? 'Belum diisi' }}</strong></p>
    <div class="d-flex justify-content-between mb-2"><span>Checklist {{ $qcTitle }}</span><strong>{{ $qcProgress }}%</strong></div>
    <div class="qc-checklist mb-3">
        @forelse($qcDefinition as $qcItem)
            <div class="qc-item">
                <div class="fw-bold mb-1">{{ $qcItem['item_name'] }} @if($qcItem['variant'])<small>- {{ $qcItem['variant'] }}</small>@endif</div>
                @foreach($qcItem['checks'] as $check)
                    <div class="form-check qc-check-row"><input class="form-check-input" type="checkbox" disabled id="{{ $qcPrefix }}_saved_{{ $check['key'] }}" @checked($qcValues[$check['key']] ?? false)><label class="form-check-label" for="{{ $qcPrefix }}_saved_{{ $check['key'] }}">{{ $check['label'] }}</label></div>
                @endforeach
            </div>
        @empty
            <p class="qc-instruction mb-0">Belum ada item penawaran untuk diperiksa.</p>
        @endforelse
    </div>
    @if($workflow->{$qcPrefix.'_note'})<p class="small mb-3">{{ $workflow->{$qcPrefix.'_note'} }}</p>@endif
@endif
@if($workflow->{$qcPrefix.'_document_path'})
    <div class="attachment-box mt-3"><div class="small fw-semibold text-truncate">{{ $workflow->{$qcPrefix.'_document_name'} }}</div><div class="mt-2"><a target="_blank" rel="noopener" href="{{ route('project-workflow.attachment', [$project, $qcAttachment]) }}" class="btn btn-sm btn-soft">Lihat</a> <a href="{{ route('project-workflow.attachment', [$project, $qcAttachment, 'download' => 1]) }}" class="btn btn-sm btn-soft">Unduh</a></div></div>
@endif
