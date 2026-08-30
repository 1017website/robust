{{-- Panel progres unggahan. Dipakai bersama public/js/upload-progress.js. --}}
<div class="mt-3 d-none" data-upload-progress-panel aria-live="polite">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-1">
        <span class="small fw-bold" data-upload-status>Menyiapkan upload...</span>
        <strong class="small text-primary" data-upload-percent>0%</strong>
    </div>
    <div class="progress" style="height: 12px;" aria-label="Progres upload lampiran">
        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-upload-bar></div>
    </div>
    <div class="form-text" data-upload-detail>0 B terkirim</div>
</div>
<div class="alert alert-danger d-none mt-3 mb-0" role="alert" data-upload-errors></div>
