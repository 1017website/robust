@extends('layouts.app')
@section('title', 'Profil')
@section('content')
<x-page-header title="Profil Saya" subtitle="Kelola informasi akun dan password" />

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-r">
            <div class="card-head"><h2>Informasi Akun</h2></div>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PUT')
                <div class="mb-3"><label class="form-label small fw-semibold">Nama</label><input name="name" value="{{ old('name',$user->name) }}" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Email</label><input name="email" type="email" value="{{ old('email',$user->email) }}" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">No. Telepon</label><input name="phone" value="{{ old('phone',$user->phone) }}" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Jabatan</label><input name="job_title" value="{{ old('job_title',$user->job_title) }}" class="form-control"></div>
                <button class="btn btn-primary">Simpan Perubahan</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-r">
            <div class="card-head"><h2>Ubah Password</h2></div>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf @method('PUT')
                <div class="mb-3"><label class="form-label small fw-semibold">Password Saat Ini</label><input name="current_password" type="password" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Password Baru</label><input name="password" type="password" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Konfirmasi Password Baru</label><input name="password_confirmation" type="password" class="form-control"></div>
                <button class="btn btn-primary">Ubah Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
