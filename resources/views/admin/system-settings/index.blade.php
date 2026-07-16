@extends('layouts.app')
@section('title', 'System Settings')

@section('content')
<x-page-header title="System Settings" subtitle="Pengaturan branding perusahaan dan tools maintenance untuk superadmin">
    <a href="{{ route('dashboard') }}" class="btn btn-soft"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
</x-page-header>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card-r">
            <div class="card-head">
                <h2>Branding Perusahaan</h2>
                <span class="pill">Logo & Favicon</span>
            </div>
            <form method="POST" action="{{ route('admin.system-settings.branding') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nama Perusahaan</label>
                        <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $settings['company_name']) }}" required maxlength="80">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Tagline / Deskripsi Singkat</label>
                        <input type="text" name="company_tagline" class="form-control" value="{{ old('company_tagline', $settings['company_tagline']) }}" maxlength="140">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Target Penjualan Bulanan (Rp)</label>
                        <input type="text" inputmode="numeric" data-rupiah name="sales_monthly_target" class="form-control" value="{{ old('sales_monthly_target', $settings['sales_monthly_target']) }}">
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Logo Perusahaan</label>
                        <input type="file" name="company_logo" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml">
                        <div class="form-text">Rekomendasi PNG/SVG transparan. Maksimal 2MB.</div>
                        <div class="brand-preview mt-3">
                            @if($settings['company_logo'])
                                <img src="{{ $settings['company_logo'] }}" alt="Logo perusahaan">
                            @else
                                <div class="brand-preview-empty">Belum ada logo upload</div>
                            @endif
                        </div>
                        @if($settings['company_logo'])
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                                <label class="form-check-label small" for="remove_logo">Hapus logo upload dan kembali ke teks</label>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Favicon</label>
                        <input type="file" name="company_favicon" class="form-control" accept=".ico,.png,.jpg,.jpeg,.webp,.svg,image/x-icon,image/png,image/jpeg,image/webp,image/svg+xml">
                        <div class="form-text">Bisa ICO/PNG/SVG. Maksimal 1MB.</div>
                        <div class="favicon-preview mt-3">
                            @if($settings['company_favicon'])
                                <img src="{{ $settings['company_favicon'] }}" alt="Favicon">
                            @else
                                <div class="brand-preview-empty">Default favicon</div>
                            @endif
                        </div>
                        @if($settings['company_favicon'])
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_favicon" value="1" id="remove_favicon">
                                <label class="form-check-label small" for="remove_favicon">Hapus favicon upload</label>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Branding</button>
                    <a href="{{ route('admin.system-settings.index') }}" class="btn btn-soft">Reset Form</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card-r">
            <div class="card-head">
                <h2>Maintenance Command</h2>
                <span class="pill">Superadmin</span>
            </div>
            <div class="alert alert-warning small mb-3">
                <i class="bi bi-shield-exclamation me-1"></i>
                Menu ini hanya untuk Administrator. Jalankan command setelah upload patch/revisi atau saat upload file tidak bisa diakses.
            </div>

            <div class="d-grid gap-3">
                @foreach($commands as $key => $cmd)
                    <div class="command-card">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="command-icon bg-{{ $cmd['variant'] }}-soft text-{{ $cmd['variant'] }}"><i class="bi {{ $cmd['icon'] }}"></i></div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">{{ $cmd['label'] }}</div>
                                <code class="small">{{ $cmd['command'] }}</code>
                                <div class="small text-muted-2 mt-1">{{ $cmd['description'] }}</div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.system-settings.run-command') }}" class="mt-3" onsubmit="return confirm('Jalankan {{ $cmd['command'] }} sekarang?')">
                            @csrf
                            <input type="hidden" name="command" value="{{ $key }}">
                            <button class="btn btn-{{ $cmd['variant'] }} btn-sm"><i class="bi bi-terminal me-1"></i> {{ $cmd['button'] }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        @if(session('artisan_output'))
            <div class="card-r mt-3">
                <div class="card-head">
                    <h2>Output Command</h2>
                    <span class="pill">{{ session('artisan_command') }}</span>
                </div>
                <pre class="command-output">{{ session('artisan_output') }}</pre>
            </div>
        @endif
    </div>
</div>
@endsection
