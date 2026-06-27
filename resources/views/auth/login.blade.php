<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">ROBUST<span>®</span></div>
        <div class="auth-sub">Sales CRM · Laboratory Furniture & Equipment</div>

        @if($errors->any())
            <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="nama@robust.test" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label small" for="remember">Ingat saya</label>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Masuk</button>
        </form>

        <div class="mt-4 pt-3 border-top">
            <div class="small text-muted mb-2">Akun demo:</div>
            <div class="small text-muted-2" style="line-height:1.8">
                <div><strong>Admin:</strong> admin@robust.test</div>
                <div><strong>Sales:</strong> sales@robust.test</div>
                <div><strong>Drafter:</strong> drafter@robust.test</div>
                <div>Password semua: <code>password</code></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
