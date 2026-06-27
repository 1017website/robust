@php($u = auth()->user())
@php($role = $u->role)
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-logo">ROBUST<span>®</span></div>
        <div class="brand-sub">Laboratory Furniture & Equipment</div>
    </div>
    <div class="side-label">{{ strtoupper($u->roleLabel()) }}</div>
    <nav class="side-nav">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-house-door"></i> Dashboard</a>

        @if($role === 'administrator')
            <div class="side-label">MAIN</div>
            <a href="{{ route('admin.pra-leads.index') }}" class="{{ request()->routeIs('admin.pra-leads.*') ? 'active' : '' }}"><i class="bi bi-percent"></i> Pra Leads</a>
            <a href="{{ route('admin.assignment.index') }}" class="{{ request()->routeIs('admin.assignment.*') ? 'active' : '' }}"><i class="bi bi-people"></i> Assignment</a>
            <a href="{{ route('sales.request-masuk.index') }}" class="{{ request()->routeIs('sales.request-masuk.*') ? 'active' : '' }}"><i class="bi bi-inbox"></i> Request Masuk</a>
            <a href="{{ route('sales.leads.index') }}" class="{{ request()->routeIs('sales.leads.*') ? 'active' : '' }}"><i class="bi bi-person-lines-fill"></i> Leads</a>
            <a href="{{ route('sales.design-requests.index') }}" class="{{ request()->routeIs('sales.design-requests.*') ? 'active' : '' }}"><i class="bi bi-pencil-square"></i> Design Request</a>
            <a href="{{ route('sales.quotations.index') }}" class="{{ request()->routeIs('sales.quotations.*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text"></i> Penawaran</a>
            <a href="{{ route('sales.customers.index') }}" class="{{ request()->routeIs('sales.customers.*') ? 'active' : '' }}"><i class="bi bi-person-vcard"></i> Customers</a>
            <a href="{{ route('sales.projects.index') }}" class="{{ request()->routeIs('sales.projects.*') ? 'active' : '' }}"><i class="bi bi-folder"></i> Projects</a>
            <a href="{{ route('activities.index') }}" class="{{ request()->routeIs('activities.*') ? 'active' : '' }}"><i class="bi bi-check2-square"></i> Activities</a>
            <a href="{{ route('calendar.index') }}" class="{{ request()->routeIs('calendar.*') ? 'active' : '' }}"><i class="bi bi-calendar3"></i> Calendar</a>
            <a href="{{ route('documents.index') }}" class="{{ request()->routeIs('documents.*') ? 'active' : '' }}"><i class="bi bi-folder2-open"></i> Documents</a>
            <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"><i class="bi bi-bar-chart"></i> Reports</a>
            <div class="side-label">SISTEM</div>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-person-gear"></i> Manage User</a>
        @elseif($role === 'sales_admin')
            <a href="{{ route('admin.pra-leads.index') }}" class="{{ request()->routeIs('admin.pra-leads.*') ? 'active' : '' }}"><i class="bi bi-percent"></i> Pra Leads</a>
            <a href="{{ route('admin.assignment.index') }}" class="{{ request()->routeIs('admin.assignment.*') ? 'active' : '' }}"><i class="bi bi-people"></i> Assignment</a>
            <a href="{{ route('sales.customers.index') }}" class="{{ request()->routeIs('sales.customers.*') ? 'active' : '' }}"><i class="bi bi-person-vcard"></i> Customers</a>
            <a href="{{ route('activities.index') }}" class="{{ request()->routeIs('activities.*') ? 'active' : '' }}"><i class="bi bi-check2-square"></i> Activities</a>
            <a href="{{ route('calendar.index') }}" class="{{ request()->routeIs('calendar.*') ? 'active' : '' }}"><i class="bi bi-calendar3"></i> Calendar</a>
            <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"><i class="bi bi-bar-chart"></i> Reports</a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-person-gear"></i> Manage User</a>
        @elseif($role === 'drafter')
            <a href="{{ route('drafter.design-requests.index') }}" class="{{ request()->routeIs('drafter.design-requests.*') ? 'active' : '' }}"><i class="bi bi-pencil-square"></i> Design Request</a>
            <a href="{{ route('documents.index') }}" class="{{ request()->routeIs('documents.*') ? 'active' : '' }}"><i class="bi bi-folder2-open"></i> Documents</a>
            <a href="{{ route('drafter.calendar.index') }}" class="{{ request()->routeIs('drafter.calendar.*') ? 'active' : '' }}"><i class="bi bi-calendar3"></i> Calendar</a>
            <a href="{{ route('drafter.reports.index') }}" class="{{ request()->routeIs('drafter.reports.*') ? 'active' : '' }}"><i class="bi bi-bar-chart"></i> Reports</a>
        @else
            <a href="{{ route('sales.request-masuk.index') }}" class="{{ request()->routeIs('sales.request-masuk.*') ? 'active' : '' }}"><i class="bi bi-inbox"></i> Request Masuk</a>
            <a href="{{ route('sales.leads.index') }}" class="{{ request()->routeIs('sales.leads.*') ? 'active' : '' }}"><i class="bi bi-people"></i> Leads</a>
            <a href="{{ route('sales.design-requests.index') }}" class="{{ request()->routeIs('sales.design-requests.*') ? 'active' : '' }}"><i class="bi bi-pencil-square"></i> Design Request</a>
            <a href="{{ route('sales.quotations.index') }}" class="{{ request()->routeIs('sales.quotations.*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text"></i> Penawaran</a>
            <a href="{{ route('sales.customers.index') }}" class="{{ request()->routeIs('sales.customers.*') ? 'active' : '' }}"><i class="bi bi-person-vcard"></i> Customers</a>
            <a href="{{ route('sales.projects.index') }}" class="{{ request()->routeIs('sales.projects.*') ? 'active' : '' }}"><i class="bi bi-folder"></i> Projects</a>
            <a href="{{ route('activities.index') }}" class="{{ request()->routeIs('activities.*') ? 'active' : '' }}"><i class="bi bi-check2-square"></i> Activities</a>
            <a href="{{ route('calendar.index') }}" class="{{ request()->routeIs('calendar.*') ? 'active' : '' }}"><i class="bi bi-calendar3"></i> Calendar</a>
            <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"><i class="bi bi-bar-chart"></i> Reports</a>
        @endif
    </nav>
    <div class="side-foot">
        <div class="avatar">{{ strtoupper(substr($u->name,0,1)) }}</div>
        <div>
            <div class="name">{{ $u->name }}</div>
            <div class="role">{{ $u->roleLabel() }}</div>
        </div>
    </div>
</aside>
