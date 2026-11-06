<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Styles statiques via le helper asset() (Pas de Vite) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="{{ url('/') }}" class="logo">
                <span>{{ config('app.name', 'Laravel') }}</span>
                <span class="logo-badge">Boulangerie</span>
            </a>
            <div class="nav-links">
                <a href="{{ route('missions.index') }}" class="nav-link {{ request()->routeIs('missions.*') ? 'active' : '' }}">
                    <x-icon name="briefcase" size="15" /> Missions
                </a>
                <a href="{{ route('collaborators.index') }}" class="nav-link {{ request()->routeIs('collaborators.*') ? 'active' : '' }}">
                    <x-icon name="users" size="15" /> Équipe & Staffing
                </a>
                <a href="{{ route('collections.index') }}" class="nav-link {{ request()->routeIs('collections.*') ? 'active' : '' }}">
                    <x-icon name="clipboard" size="15" /> Collectes
                </a>
                <a href="{{ route('analyses.index') }}" class="nav-link {{ request()->routeIs('analyses.*') ? 'active' : '' }}">
                    <x-icon name="chart" size="15" /> Analyses
                </a>
            </div>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }} &mdash; 100% Laravel Blade &bull; Aucun bundler Node.js / Vite / VueJS requis.</p>
    </footer>

    <!-- Scripts statiques (Pas de Vite) -->
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
