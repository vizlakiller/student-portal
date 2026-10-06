<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => trim($__env->yieldContent('title'))])
</head>
<body class="guest">
    <main class="guest-panel">
        <div @class(['guest-brand', 'has-logo' => \App\Support\Branding::logoUrl()])>
            @include('partials.brand')
            <span>{{ \App\Support\Branding::portalName() }}</span>
        </div>
        @yield('content')
    </main>
</body>
</html>
