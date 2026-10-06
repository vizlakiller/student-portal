<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ config('portal.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Next:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="brand-seal" aria-hidden="true">SP</span>
            <span>{{ config('portal.name') }}</span>
        </a>

        <nav class="nav" aria-label="Main">
            <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4zM13 4h7v4h-7zM13 10h7v10h-7zM4 13h7v7H4z"/></svg>
                Dashboard
            </a>
            <a href="{{ route('students.index') }}" @class(['active' => request()->routeIs('students.*')])>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21v-1a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v1M16 3.5a4 4 0 0 1 0 7M22 21v-1a6 6 0 0 0-4-5.6"/></svg>
                Students
            </a>
            <a href="{{ route('programmes.index') }}" @class(['active' => request()->routeIs('programmes.*')])>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 8l10-5 10 5-10 5zM6 10v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5M22 8v6"/></svg>
                Programmes
            </a>
            <a href="{{ route('subjects.index') }}" @class(['active' => request()->routeIs('subjects.*')])>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h12a4 4 0 0 1 4 4v12H8a4 4 0 0 1-4-4zM8 9h8M8 13h6"/></svg>
                Subjects
            </a>
            @can('admin')
                <a href="{{ route('users.index') }}" @class(['active' => request()->routeIs('users.*')])>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.4 8.2-8 9-4.6-.8-8-4.5-8-9V6zM9 12l2 2 4-4"/></svg>
                    Staff accounts
                </a>
            @endcan
            <a href="{{ route('profile.edit') }}" @class(['active' => request()->routeIs('profile.*')])>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9zM4 21a8 8 0 0 1 16 0"/></svg>
                My profile
            </a>
        </nav>

        <div class="sidebar-user">
            <strong>{{ auth()->user()->name }}</strong>
            <span>{{ ucfirst(auth()->user()->role) }}</span>
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
</script>
</body>
</html>
