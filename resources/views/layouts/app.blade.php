@php($u = auth()->user())
@php($calendarRoute = $u?->isDrafter() ? route('drafter.calendar.index') : route('calendar.index'))
@php($siteName = \App\Models\SystemSetting::value('company_name', config('app.name')))
@php($companyFavicon = \App\Models\SystemSetting::assetUrl('company_favicon', asset('favicon.ico')))
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ $siteName }}</title>
    <link rel="icon" href="{{ $companyFavicon }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
<div class="layout">
    @include('layouts.sidebar')
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    <div class="main">
        <header class="topbar">
            <button type="button" class="btn btn-sm btn-light d-xl-none" id="sidebarToggle" aria-label="Buka menu"><i class="bi bi-list"></i></button>
            <form class="search-box" method="GET" action="{{ route('global-search.index') }}">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="{{ request()->routeIs('global-search.*') ? request('q') : '' }}" placeholder="Cari customer, PIC, proyek, atau aktivitas..." autocomplete="off">
            </form>
            <div class="topbar-right">
                <div class="dropdown">
                    <button type="button" class="topbar-icon topbar-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                        <i class="bi bi-bell"></i>
                        @if(($topbarNotificationCount ?? 0) > 0)
                            <span class="dot">{{ $topbarNotificationCount > 9 ? '9+' : $topbarNotificationCount }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end topbar-notifications">
                        <div class="topbar-notifications-head">
                            <strong>Notifikasi</strong>
                            <span>{{ $topbarNotificationCount ?? 0 }} item</span>
                        </div>
                        @forelse(($topbarNotifications ?? []) as $notification)
                            <a class="notification-item" href="{{ $notification['href'] }}">
                                <i class="bi {{ $notification['icon'] }} {{ $notification['tone'] ?? '' }}"></i>
                                <span>
                                    <strong>{{ $notification['title'] }}</strong>
                                    <small>{{ $notification['detail'] }}</small>
                                </span>
                            </a>
                        @empty
                            <div class="notification-empty">
                                <i class="bi bi-check2-circle"></i>
                                <span>Tidak ada notifikasi baru.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
                <a href="{{ $calendarRoute }}" class="topbar-icon d-none d-md-inline-flex"><i class="bi bi-calendar3"></i></a>
                <div class="dropdown">
                    <a href="#" class="user-chip" data-bs-toggle="dropdown">
                        <div class="avatar">{{ strtoupper(substr($u->name,0,1)) }}</div>
                        <div class="d-none d-md-block">
                            <div class="name">{{ $u->name }}</div>
                            <div class="role">{{ $u->roleLabel() }}</div>
                        </div>
                        <i class="bi bi-chevron-down ms-1"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
