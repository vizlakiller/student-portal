<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => trim($__env->yieldContent('title'))])
</head>
<body>
@php
    $user = auth()->user();

    // Number shown next to "Mark changes": requests still waiting for a decision.
    $pendingChanges = $user->can('view-mark-changes')
        ? \App\Models\ResultChangeRequest::visibleTo($user)->where('status', 'pending')->count()
        : 0;
@endphp
<div class="app">
    <aside class="sidebar" id="sidebar">
        <a @class(['brand', 'has-logo' => \App\Support\Branding::logoUrl()]) href="{{ route('dashboard') }}">
            @include('partials.brand')
            <span>{{ \App\Support\Branding::portalName() }}</span>
        </a>

        <nav class="nav" aria-label="Main">
            @cannot('view-own-results')
                <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4zM13 4h7v4h-7zM13 10h7v10h-7zM4 13h7v7H4z"/></svg>
                    Dashboard
                </a>
            @endcannot
            @can('view-statistics')
                <a href="{{ route('statistics') }}" @class(['active' => request()->routeIs('statistics')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>
                    Statistics
                </a>
            @endcan
            @can('view-own-results')
                <a href="{{ route('my.results') }}" @class(['active' => request()->routeIs('my.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15a6 6 0 1 0 0-12 6 6 0 0 0 0 12zM8.5 13.5 7 21l5-3 5 3-1.5-7.5"/></svg>
                    My results
                </a>
            @endcan
            @canany(['teach', 'view-own-results'])
                <a href="{{ route('timetable.mine') }}" @class(['active' => request()->routeIs('timetable.mine')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v15H4zM4 10h16M9 3v4M15 3v4"/></svg>
                    My timetable
                </a>
            @endcanany
            @can('teach')
                <a href="{{ route('marks.index') }}" @class(['active' => request()->routeIs('marks.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4h6v3H9zM7 5.5H5v15h14v-15h-2M8.5 12l2.5 2.5 4.5-5"/></svg>
                    My sessions
                </a>
            @endcan
            @can('view-mark-changes')
                <a href="{{ route('mark-changes.index') }}" @class(['active' => request()->routeIs('mark-changes.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h11M11 3l4 4-4 4M20 17H9M13 13l-4 4 4 4"/></svg>
                    Mark changes
                    @if ($pendingChanges)
                        <span class="nav-badge" aria-label="{{ $pendingChanges }} waiting">{{ $pendingChanges }}</span>
                    @endif
                </a>
            @endcan

            @canany(['view-students', 'view-lecturers', 'view-subjects', 'view-programmes', 'view-departments'])
                <p class="nav-section">Academic</p>
            @endcanany
            @can('view-students')
                <a href="{{ route('students.index') }}" @class(['active' => request()->routeIs('students.*', 'registrations.*', 'results.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21v-1a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v1M16 3.5a4 4 0 0 1 0 7M22 21v-1a6 6 0 0 0-4-5.6"/></svg>
                    Students
                </a>
            @endcan
            @can('view-lecturers')
                <a href="{{ route('lecturers.index') }}" @class(['active' => request()->routeIs('lecturers.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h18v11H3zM8 21l4-6 4 6M7 9h6"/></svg>
                    Lecturers
                </a>
            @endcan
            @can('view-subjects')
                <a href="{{ route('subjects.index') }}" @class(['active' => request()->routeIs('subjects.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h12a4 4 0 0 1 4 4v12H8a4 4 0 0 1-4-4zM8 9h8M8 13h6"/></svg>
                    Subjects
                </a>
            @endcan
            @can('view-programmes')
                <a href="{{ route('programmes.index') }}" @class(['active' => request()->routeIs('programmes.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 8l10-5 10 5-10 5zM6 10v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5M22 8v6"/></svg>
                    Programmes
                </a>
            @endcan
            @can('view-departments')
                <a href="{{ route('departments.index') }}" @class(['active' => request()->routeIs('departments.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6"/></svg>
                    Departments
                </a>
            @endcan

            @can('view-timetable')
                <p class="nav-section">Timetable</p>
                <a href="{{ route('timetable.index') }}" @class(['active' => request()->routeIs('timetable.index')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v15H4zM4 10h16M9 10v10M14 10v10"/></svg>
                    Weekly timetable
                </a>
                <a href="{{ route('sessions.index') }}" @class(['active' => request()->routeIs('sessions.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                    Sessions
                </a>
                <a href="{{ route('classrooms.index') }}" @class(['active' => request()->routeIs('classrooms.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V6l8-3 8 3v14M9 20v-5h6v5M8 9h.01M12 9h.01M16 9h.01"/></svg>
                    Classrooms
                </a>
            @endcan

            @can('view-finance')
                <p class="nav-section">Finance</p>
                <a href="{{ route('finance.index') }}" @class(['active' => request()->routeIs('finance.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18v12H3zM3 7l3-3h12l3 3M16 13h2"/></svg>
                    Fees and payments
                </a>
            @endcan

            @canany(['manage-users', 'manage-branding', 'manage-terms'])
                <p class="nav-section">System</p>
            @endcanany
            @can('manage-terms')
                <a href="{{ route('terms.index') }}" @class(['active' => request()->routeIs('terms.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7v5l3 2M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z"/></svg>
                    Terms
                </a>
            @endcan
            @can('manage-users')
                <a href="{{ route('users.index') }}" @class(['active' => request()->routeIs('users.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.4 8.2-8 9-4.6-.8-8-4.5-8-9V6zM9 12l2 2 4-4"/></svg>
                    User accounts
                </a>
            @endcan
            @can('manage-branding')
                <a href="{{ route('branding.edit') }}" @class(['active' => request()->routeIs('branding.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 0 0 0 18c1.2 0 2-.8 2-1.8 0-.5-.2-.9-.5-1.2-.3-.3-.5-.7-.5-1.2 0-1 .8-1.8 1.8-1.8H17a4 4 0 0 0 4-4c0-4.4-4-8-9-8zM7.5 11.5h.01M10 7.5h.01M15 7.5h.01"/></svg>
                    Branding
                </a>
            @endcan
            <a href="{{ route('profile.edit') }}" @class(['active' => request()->routeIs('profile.*')])>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9zM4 21a8 8 0 0 1 16 0"/></svg>
                My profile
            </a>
        </nav>

        <div class="sidebar-user">
            <strong>{{ $user->name }}</strong>
            <span>{{ $user->roleLabel() }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-button">Log out</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="page-header">
            <button type="button" class="menu-toggle" aria-controls="sidebar" aria-expanded="false">Menu</button>
            <div class="page-heading">
                @hasSection('back')
                    <div class="back-link">@yield('back')</div>
                @endif
                <h1>@yield('title')</h1>
                @hasSection('subtitle')
                    <p class="subtitle">@yield('subtitle')</p>
                @endif
            </div>
            @hasSection('actions')
                <div class="page-actions">@yield('actions')</div>
            @endif
        </header>

        <main class="content">
            @if (session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error" role="alert">{{ session('error') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    // Show or hide the sidebar on small screens
    const toggle = document.querySelector('.menu-toggle');
    const sidebar = document.getElementById('sidebar');

    function setMenu(open) {
        document.body.classList.toggle('sidebar-open', open);
        toggle.setAttribute('aria-expanded', open);
    }

    toggle.addEventListener('click', () => setMenu(!document.body.classList.contains('sidebar-open')));

    // Close it again when tapping outside the menu or pressing Escape
    document.addEventListener('click', (event) => {
        if (document.body.classList.contains('sidebar-open') && !sidebar.contains(event.target) && !toggle.contains(event.target)) {
            setMenu(false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMenu(false);
    });

    // Long menus scroll: make sure the current page's link is in view
    document.querySelector('.nav a.active')?.scrollIntoView({ block: 'nearest' });
</script>
</body>
</html>
