@php
    $appName = config('app.name', 'ROBUST CRM');
    $destination = auth()->check() ? route('dashboard') : route('login');
    $destinationLabel = auth()->check() ? 'Kembali ke Dashboard' : 'Kembali ke Login';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} - {{ $title }} | {{ $appName }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--navy:#0f1b2d;--blue:#1d6fe0;--soft:#f4f7fc;--muted:#6f7f96;--line:#e1e8f2;--tone:{{ $tone ?? '#1d6fe0' }}}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 15% 10%,#eaf2ff 0,transparent 34%),linear-gradient(145deg,#f8faff,#eef3f9);font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:var(--navy)}
        .page{min-height:100vh;display:grid;place-items:center;padding:28px}.card{position:relative;width:min(680px,100%);overflow:hidden;border:1px solid rgba(255,255,255,.9);border-radius:28px;background:rgba(255,255,255,.94);box-shadow:0 30px 80px rgba(15,27,45,.13)}
        .bar{height:7px;background:linear-gradient(90deg,var(--tone),#61a2ff)}.body{padding:46px}.brand{display:flex;align-items:center;gap:12px;margin-bottom:38px}.brand-mark{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:var(--navy);color:#fff;font-size:18px;font-weight:800}.brand strong{display:block;font-size:16px}.brand span{color:var(--muted);font-size:11px}
        .error{display:grid;grid-template-columns:auto 1fr;gap:24px;align-items:start}.code{font-size:72px;font-weight:800;letter-spacing:-5px;line-height:.9;color:var(--tone)}h1{margin:0 0 10px;font-size:28px;letter-spacing:-.8px}p{margin:0;color:var(--muted);font-size:14px;line-height:1.8}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:30px}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 18px;border:1px solid var(--line);border-radius:12px;background:#fff;color:var(--navy);font:inherit;font-size:13px;font-weight:700;text-decoration:none;cursor:pointer}.btn.primary{border-color:var(--blue);background:var(--blue);color:#fff;box-shadow:0 10px 24px rgba(29,111,224,.22)}.meta{margin-top:34px;padding-top:18px;border-top:1px solid var(--line);color:#98a4b5;font-size:11px}
        @media(max-width:600px){.body{padding:30px 24px}.error{grid-template-columns:1fr;gap:14px}.code{font-size:58px}.brand{margin-bottom:28px}h1{font-size:23px}.actions{display:grid}.btn{width:100%}}
    </style>
</head>
<body>
<main class="page">
    <section class="card" aria-labelledby="error-title">
        <div class="bar"></div>
        <div class="body">
            <div class="brand"><div class="brand-mark">R</div><div><strong>ROBUST CRM</strong><span>Laboratory Furniture & Equipment</span></div></div>
            <div class="error"><div class="code">{{ $code }}</div><div><h1 id="error-title">{{ $title }}</h1><p>{{ $message }}</p><div class="actions"><a class="btn primary" href="{{ $destination }}">{{ $destinationLabel }}</a><button class="btn" type="button" onclick="history.back()">Kembali ke Halaman Sebelumnya</button></div></div></div>
            <div class="meta">Jika masalah berulang, catat waktu kejadian dan hubungi administrator sistem.</div>
        </div>
    </section>
</main>
</body>
</html>
