@php
    $u = auth()->user();
    $role = $u->role;
    $companyName = \App\Models\SystemSetting::value('company_name', 'ROBUST');
    $companyTagline = \App\Models\SystemSetting::value('company_tagline', 'Laboratory Furniture & Equipment');
    $companyLogo = \App\Models\SystemSetting::assetUrl('company_logo');

    $drafterNewDesignRequestCount = 0;
    if ($role === 'drafter' && class_exists(\App\Models\DesignRequest::class)) {
        try {
            $drafterNewDesignRequestCount = \App\Models\DesignRequest::query()
                ->where('status', 'assigned')
                ->where('production_pic_id', $u->id)
                ->count()
                + \App\Models\DesignRequest::query()
                    ->where('status', 'assigned')
                    ->whereNull('production_pic_id')
                    ->count();
        } catch (\Throwable $e) {
            $drafterNewDesignRequestCount = 0;
        }
    }

    $menuGroups = [];
    $pushItem = function (array &$groups, string $label, string $route, string $active, string $icon, $badge = null) {
        $groups[] = [
            'type' => 'item',
            'label' => $label,
            'route' => $route,
            'active' => $active,
            'icon' => $icon,
            'badge' => $badge,
        ];
    };
    $pushLabel = function (array &$groups, string $label) {
        $groups[] = ['type' => 'label', 'label' => $label];
    };

    $pushItem($menuGroups, 'Dashboard', 'dashboard', 'dashboard', 'bi-house-door');

    if (in_array($role, ['administrator', 'sales_admin', 'sales_spv'], true)) {
        $pushItem($menuGroups, 'Monitoring Pipeline', 'pipeline.index', 'pipeline.*', 'bi-kanban');
    }

    if ($role === 'administrator') {
        $pushLabel($menuGroups, 'MAIN');
        $pushItem($menuGroups, 'Pra Leads', 'admin.pra-leads.index', 'admin.pra-leads.*', 'bi-percent');
        $pushItem($menuGroups, 'Assignment', 'admin.assignment.index', 'admin.assignment.*', 'bi-people');
        $pushItem($menuGroups, 'Request Masuk', 'sales.request-masuk.index', 'sales.request-masuk.*', 'bi-inbox');
        $pushItem($menuGroups, 'Leads', 'sales.leads.index', 'sales.leads.*', 'bi-person-lines-fill');
        $pushItem($menuGroups, 'Activities', 'activities.index', 'activities.*', 'bi-check2-square');
        $pushItem($menuGroups, 'Design Request', 'sales.design-requests.index', 'sales.design-requests.*', 'bi-pencil-square');
        $pushItem($menuGroups, 'Penawaran', 'sales.quotations.index', 'sales.quotations.*', 'bi-file-earmark-text');
        $pushItem($menuGroups, 'Approval Penawaran', 'spv.quotation-approvals.index', 'spv.quotation-approvals.*', 'bi-check2-square');
        $pushItem($menuGroups, 'Request PO', 'admin.purchase-order-requests.index', 'admin.purchase-order-requests.*', 'bi-receipt');
        $pushItem($menuGroups, 'Customers', 'sales.customers.index', 'sales.customers.*', 'bi-person-vcard');
        $pushItem($menuGroups, 'Projects', 'sales.projects.index', 'sales.projects.*', 'bi-folder');
        $pushItem($menuGroups, 'Calendar', 'calendar.index', 'calendar.*', 'bi-calendar3');
        $pushItem($menuGroups, 'Documents', 'documents.index', 'documents.*', 'bi-folder2-open');
        $pushItem($menuGroups, 'Reports', 'reports.index', 'reports.*', 'bi-bar-chart');
        $pushLabel($menuGroups, 'SISTEM');
        $pushItem($menuGroups, 'Manage User', 'admin.users.index', 'admin.users.*', 'bi-person-gear');
        $pushItem($menuGroups, 'System Settings', 'admin.system-settings.index', 'admin.system-settings.*', 'bi-gear-wide-connected');
    } elseif ($role === 'sales_admin') {
        $pushItem($menuGroups, 'Pra Leads', 'admin.pra-leads.index', 'admin.pra-leads.*', 'bi-percent');
        $pushItem($menuGroups, 'Assignment', 'admin.assignment.index', 'admin.assignment.*', 'bi-people');
        $pushItem($menuGroups, 'Request PO', 'admin.purchase-order-requests.index', 'admin.purchase-order-requests.*', 'bi-receipt');
        $pushItem($menuGroups, 'Customers', 'sales.customers.index', 'sales.customers.*', 'bi-person-vcard');
        $pushItem($menuGroups, 'Activities', 'activities.index', 'activities.*', 'bi-check2-square');
        $pushItem($menuGroups, 'Calendar', 'calendar.index', 'calendar.*', 'bi-calendar3');
        $pushItem($menuGroups, 'Reports', 'reports.index', 'reports.*', 'bi-bar-chart');
        $pushItem($menuGroups, 'Manage User', 'admin.users.index', 'admin.users.*', 'bi-person-gear');
    } elseif ($role === 'sales_spv') {
        $pushItem($menuGroups, 'Approval Penawaran', 'spv.quotation-approvals.index', 'spv.quotation-approvals.*', 'bi-check2-square');
        $pushItem($menuGroups, 'Calendar', 'calendar.index', 'calendar.*', 'bi-calendar3');
        $pushItem($menuGroups, 'Reports', 'reports.index', 'reports.*', 'bi-bar-chart');
    } elseif ($role === 'drafter') {
        $pushItem($menuGroups, 'Design Request', 'drafter.design-requests.index', 'drafter.design-requests.*', 'bi-pencil-square', $drafterNewDesignRequestCount);
        $pushItem($menuGroups, 'Projects', 'drafter.projects.index', 'drafter.projects.*', 'bi-box-seam');
        $pushItem($menuGroups, 'Tasks', 'drafter.tasks.index', 'drafter.tasks.*', 'bi-ui-checks');
        $pushItem($menuGroups, 'Documents', 'documents.index', 'documents.*', 'bi-file-earmark-text');
        $pushItem($menuGroups, 'Calendar', 'drafter.calendar.index', 'drafter.calendar.*', 'bi-calendar3');
        $pushItem($menuGroups, 'Reports', 'drafter.reports.index', 'drafter.reports.*', 'bi-bar-chart');
        $pushItem($menuGroups, 'Settings', 'profile.edit', 'profile.*', 'bi-gear');
    } else {
        $pushItem($menuGroups, 'Request Masuk', 'sales.request-masuk.index', 'sales.request-masuk.*', 'bi-inbox');
        $pushItem($menuGroups, 'Leads', 'sales.leads.index', 'sales.leads.*', 'bi-people');
        $pushItem($menuGroups, 'Activities', 'activities.index', 'activities.*', 'bi-check2-square');
        $pushItem($menuGroups, 'Design Request', 'sales.design-requests.index', 'sales.design-requests.*', 'bi-pencil-square');
        $pushItem($menuGroups, 'Penawaran', 'sales.quotations.index', 'sales.quotations.*', 'bi-file-earmark-text');
        $pushItem($menuGroups, 'Customers', 'sales.customers.index', 'sales.customers.*', 'bi-person-vcard');
        $pushItem($menuGroups, 'Projects', 'sales.projects.index', 'sales.projects.*', 'bi-folder');
        $pushItem($menuGroups, 'Calendar', 'calendar.index', 'calendar.*', 'bi-calendar3');
        $pushItem($menuGroups, 'Reports', 'reports.index', 'reports.*', 'bi-bar-chart');
        $pushItem($menuGroups, 'Settings', 'profile.edit', 'profile.*', 'bi-gear');
    }

    $showLogoutButton = in_array($role, ['drafter', 'sales'], true);
@endphp

<aside class="sidebar" id="sidebar">
    <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Tutup menu">
        <i class="bi bi-x-lg"></i>
    </button>
    <div class="brand">
        <?php if ($companyLogo): ?>
            <img src="{{ $companyLogo }}" alt="{{ $companyName }}" class="brand-logo-img">
        <?php else: ?>
            <div class="brand-logo">{{ $companyName }}<span>®</span></div>
        <?php endif; ?>
        <div class="brand-sub">{{ $companyTagline }}</div>
    </div>

    <div class="side-label">{{ strtoupper($u->roleLabel()) }}</div>

    <nav class="side-nav">
        <?php foreach ($menuGroups as $menu): ?>
            <?php if (($menu['type'] ?? 'item') === 'label'): ?>
                <div class="side-label">{{ $menu['label'] }}</div>
            <?php else: ?>
                <?php
                    $isActive = request()->routeIs($menu['active']);
                    $badge = (int) ($menu['badge'] ?? 0);
                ?>
                <a href="{{ route($menu['route']) }}" class="{{ $isActive ? 'active' : '' }}">
                    <i class="bi {{ $menu['icon'] }}"></i>
                    <span class="side-menu-text">{{ $menu['label'] }}</span>
                    <?php if ($badge > 0): ?>
                        <span class="side-badge">{{ $badge > 99 ? '99+' : $badge }}</span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($showLogoutButton): ?>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                {{ csrf_field() }}
                <button type="submit" class="side-logout"><i class="bi bi-box-arrow-right"></i> Logout</button>
            </form>
        <?php endif; ?>
    </nav>

    <div class="side-foot">
        <div class="avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</div>
        <div>
            <div class="name">{{ $u->name }}</div>
            <div class="role">{{ $u->roleLabel() }}</div>
        </div>
    </div>
</aside>
