<details class="border rounded p-3 mb-3" @if($errors->has($targetPrefix.'_target_date')) open @endif>
    <summary class="fw-semibold" style="font-size:1.125rem;cursor:pointer;min-height:32px">Ubah tanggal target selesai {{ $targetLabel }}</summary>
    <form method="POST" action="{{ route('project-workflow.target-date', [$project, $targetStage]) }}" class="mt-3" data-target-date-form>
        @csrf @method('PUT')
        <label class="form-label fw-semibold" for="{{ $targetPrefix }}_change_target_date">Tanggal target selesai (wajib diisi)</label>
        <input type="date" id="{{ $targetPrefix }}_change_target_date" name="{{ $targetPrefix }}_target_date" class="form-control mb-3" required value="{{ old($targetPrefix.'_target_date', $workflow->{$targetPrefix.'_target_date'}?->format('Y-m-d')) }}">
        @error($targetPrefix.'_target_date')<p class="text-danger fw-semibold">Isi tanggal target selesai yang valid.</p>@enderror
        <label class="form-label" for="{{ $targetPrefix }}_target_reason">Alasan perubahan (opsional)</label>
        <textarea id="{{ $targetPrefix }}_target_reason" name="target_date_reason" class="form-control mb-3" rows="2" maxlength="2000">{{ old('target_date_reason') }}</textarea>
        @error('target_date_reason')<p class="text-danger fw-semibold">{{ $message }}</p>@enderror
        <p>Perubahan tanggal tercatat dalam riwayat. Hasil pemeriksaan tetap tersimpan.</p>
        <button class="btn btn-primary btn-lg w-100">Simpan tanggal target</button>
    </form>
</details>
