@php
    $routeName = request()->route()?->getName() ?? '';
    $isIndex = str_ends_with($routeName, '.index') || in_array($routeName, ['dashboard', 'profile.edit'], true);
    $sections = [
        'admin.purchase-order-requests.' => ['admin.purchase-order-requests.index', 'Request PO'],
        'admin.system-settings.' => ['admin.system-settings.index', 'System Settings'],
        'admin.pra-leads.' => ['admin.pra-leads.index', 'Pra Leads'],
        'admin.assignment.' => ['admin.assignment.index', 'Assignment'],
        'admin.users.' => ['admin.users.index', 'Manage User'],
        'sales.design-requests.' => ['sales.design-requests.index', 'Design Request'],
        'sales.request-masuk.' => ['sales.request-masuk.index', 'Request Masuk'],
        'sales.quotations.' => ['sales.quotations.index', 'Penawaran'],
        'sales.customers.' => ['sales.customers.index', 'Customers'],
        'sales.projects.' => ['sales.projects.index', 'Projects'],
        'sales.leads.' => ['sales.leads.index', 'Leads'],
        'spv.quotation-approvals.' => ['spv.quotation-approvals.index', 'Approval Penawaran'],
        'drafter.design-requests.' => ['drafter.design-requests.index', 'Design Request'],
        'drafter.projects.' => ['drafter.projects.index', 'Projects'],
        'drafter.tasks.' => ['drafter.tasks.index', 'Tasks'],
        'drafter.calendar.' => ['drafter.calendar.index', 'Calendar'],
        'drafter.reports.' => ['drafter.reports.index', 'Reports'],
        'activities.' => ['activities.index', 'Activities'],
        'documents.' => ['documents.index', 'Documents'],
        'pipeline.' => ['pipeline.index', 'Monitoring Pipeline'],
        'calendar.' => ['calendar.index', 'Calendar'],
        'reports.' => ['reports.index', 'Reports'],
        'profile.' => ['profile.edit', 'Profil'],
        'global-search.' => ['global-search.index', 'Pencarian'],
    ];

    $sectionRoute = null;
    $sectionLabel = $contextTitle ?? 'Halaman';
    foreach ($sections as $prefix => [$candidateRoute, $candidateLabel]) {
        if (str_starts_with($routeName, $prefix)) {
            $sectionRoute = $candidateRoute;
            $sectionLabel = $candidateLabel;
            break;
        }
    }

    $actionLabel = match (true) {
        str_ends_with($routeName, '.create') => 'Tambah '.$sectionLabel,
        str_ends_with($routeName, '.edit') => 'Edit '.$sectionLabel,
        str_ends_with($routeName, '.show') => 'Detail '.$sectionLabel,
        default => $contextTitle ?? $sectionLabel,
    };
    $dashboardUrl = route('dashboard');
    $sectionUrl = $sectionRoute && Route::has($sectionRoute) ? route($sectionRoute) : $dashboardUrl;
    $backUrl = ! $isIndex && $sectionRoute !== $routeName ? $sectionUrl : $dashboardUrl;
@endphp

@if($routeName !== 'dashboard')
    <nav class="context-nav" aria-label="Navigasi halaman">
        <a class="context-back" href="{{ $backUrl }}" aria-label="Kembali">
            <i class="bi bi-arrow-left"></i>
            <span>Kembali</span>
        </a>
        <ol class="context-breadcrumb">
            <li><a href="{{ $dashboardUrl }}">Dashboard</a></li>
            @if(! $isIndex && $sectionRoute && $sectionRoute !== $routeName)
                <li><a href="{{ $sectionUrl }}">{{ $sectionLabel }}</a></li>
            @endif
            <li aria-current="page">{{ $isIndex ? $sectionLabel : $actionLabel }}</li>
        </ol>
    </nav>
@endif
