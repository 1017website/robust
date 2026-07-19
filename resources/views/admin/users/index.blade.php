@extends('layouts.app')
@section('title', 'Manage User')
@section('content')
<x-page-header title="Manage User" subtitle="Kelola akun pengguna dan hak akses">
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userCreate"><i class="bi bi-person-plus me-1"></i>Tambah User</button>
</x-page-header>

<div class="stat-grid">
    <x-stat-card icon="bi-people" color="primary" label="Total User" :value="$stats['total']" />
    <x-stat-card icon="bi-person-check" color="success" label="Aktif" :value="$stats['active']" />
    <x-stat-card icon="bi-person-dash" color="danger" label="Nonaktif" :value="$stats['inactive']" />
    <x-stat-card icon="bi-shield-lock" color="warning" label="Admin" :value="$stats['admin']" />
</div>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama / email / jabatan...">
        <select name="role" class="form-select">
            <option value="">Semua Role</option>
            @foreach($allowedRoles as $k=>$v)<option value="{{ $k }}" @selected(request('role')==$k)>{{ $v }}</option>@endforeach
        </select>
        <select name="status" class="form-select">
            <option value="">Semua Status</option>
            <option value="active" @selected(request('status')=='active')>Aktif</option>
            <option value="inactive" @selected(request('status')=='inactive')>Nonaktif</option>
        </select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>

    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Nama</th><th>Email</th><th>Jabatan</th><th>Role</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td class="fw-semibold d-flex align-items-center gap-2">
                        <span class="avatar-sm">{{ strtoupper(substr($user->name,0,1)) }}</span>
                        {{ $user->name }}
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->job_title ?? '—' }}</td>
                    <td>
                        @php($rc = ['administrator'=>'danger','sales_admin'=>'primary','sales_spv'=>'success','sales'=>'info','drafter'=>'warning','production'=>'secondary'][$user->role] ?? 'secondary')
                        <span class="badge text-bg-{{ $rc }}">{{ $user->roleLabel() }}</span>
                    </td>
                    <td>
                        @if($user->is_active)
                            <span class="badge text-bg-success">Aktif</span>
                        @else
                            <span class="badge text-bg-secondary">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1 justify-content-end">
                            <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#userEdit{{ $user->id }}"><i class="bi bi-pencil"></i></button>
                            @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.toggle',$user) }}">@csrf @method('PUT')
                                    <button class="btn btn-sm btn-soft" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <i class="bi {{ $user->is_active ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted-2' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy',$user) }}" onsubmit="return confirm('Hapus user {{ $user->name }}?')">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-soft text-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>

                {{-- Modal Edit --}}
                <div class="modal fade" id="userEdit{{ $user->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('admin.users.update',$user) }}">
                                @csrf @method('PUT')
                                <div class="modal-header"><h5 class="modal-title">Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label small fw-semibold">Nama *</label><input name="name" value="{{ $user->name }}" class="form-control" required></div>
                                        <div class="col-md-6"><label class="form-label small fw-semibold">Email *</label><input name="email" type="email" value="{{ $user->email }}" class="form-control" required></div>
                                        <div class="col-md-6"><label class="form-label small fw-semibold">Jabatan</label><input name="job_title" value="{{ $user->job_title }}" class="form-control"></div>
                                        <div class="col-md-6"><label class="form-label small fw-semibold">Telepon</label><input name="phone" value="{{ $user->phone }}" class="form-control"></div>
                                        <div class="col-md-6"><label class="form-label small fw-semibold">Role / Hak Akses *</label>
                                            <select name="role" class="form-select" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                                @foreach($allowedRoles as $k=>$v)<option value="{{ $k }}" @selected($user->role==$k)>{{ $v }}</option>@endforeach
                                            </select>
                                            @if($user->id === auth()->id())<input type="hidden" name="role" value="{{ $user->role }}"><div class="form-text">Role akun sendiri tidak dapat diubah.</div>@endif
                                        </div>
                                        <div class="col-md-6"><label class="form-label small fw-semibold">Status</label>
                                            <select name="is_active" class="form-select" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                                <option value="1" @selected($user->is_active)>Aktif</option>
                                                <option value="0" @selected(!$user->is_active)>Nonaktif</option>
                                            </select>
                                            @if($user->id === auth()->id())<input type="hidden" name="is_active" value="1">@endif
                                        </div>
                                        <div class="col-md-6"><label class="form-label small fw-semibold">Password Baru</label><input name="password" type="password" class="form-control" placeholder="Kosongkan jika tidak diubah"></div>
                                        <div class="col-md-6"><label class="form-label small fw-semibold">Konfirmasi Password</label><input name="password_confirmation" type="password" class="form-control"></div>
                                    </div>
                                </div>
                                <div class="modal-footer"><button class="btn btn-primary">Simpan Perubahan</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <tr><td colspan="6"><x-empty text="Belum ada user." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $users->links() }}</div>
</div>

{{-- Modal Create --}}
<div class="modal fade" id="userCreate" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small fw-semibold">Nama *</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Email *</label><input name="email" type="email" value="{{ old('email') }}" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Jabatan</label><input name="job_title" value="{{ old('job_title') }}" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Telepon</label><input name="phone" value="{{ old('phone') }}" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Role / Hak Akses *</label>
                            <select name="role" class="form-select" required>
                                @foreach($allowedRoles as $k=>$v)<option value="{{ $k }}" @selected(old('role')==$k)>{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Status</label>
                            <select name="is_active" class="form-select"><option value="1" selected>Aktif</option><option value="0">Nonaktif</option></select>
                        </div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Password *</label><input name="password" type="password" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Konfirmasi Password *</label><input name="password_confirmation" type="password" class="form-control" required></div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Tambah User</button></div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.avatar-sm{width:30px;height:30px;border-radius:50%;background:var(--brand);color:#fff;display:inline-grid;place-items:center;font-weight:700;font-size:12px;flex-shrink:0}
</style>
@endpush
@endsection
