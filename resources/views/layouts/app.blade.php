<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'CoreAssist - Pilotage de Cabinet')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ file_exists(public_path('css/app.css')) ? filemtime(public_path('css/app.css')) : time() }}">
    @stack('styles')
</head>
<body>
    <a href="#main-content" class="skip-link">Passer au contenu principal</a>

    @php
        $authUser = auth()->user();
    @endphp

    <header role="banner">
        <nav class="navbar navbar-expand-md navbar-light bg-white" aria-label="Navigation principale">
            <div class="container d-flex justify-content-between align-items-center py-0">
                <a href="{{ $authUser?->isClient() ? route('client-portal.index') : url('/') }}" class="logo" aria-label="CoreAssist - Accueil">
                    <span class="logo-mark" aria-hidden="true">
                        <span class="logo-crease"></span>
                    </span>
                    <span class="logo-text">CoreAssist<span class="ba-dot">.</span></span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Menu principal">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarContent">
                    <div class="navbar-nav ms-auto align-items-md-center gap-1 pt-3 pt-md-0">
                        @if($authUser && $authUser->isClient())
                            <a href="{{ route('client-portal.index') }}" class="nav-link {{ request()->routeIs('client-portal.index') || request()->routeIs('client-portal.requests.show') ? 'active' : '' }}">
                                <x-icon name="clipboard" size="16" /> Mes Demandes
                            </a>
                            <a href="{{ route('client-portal.requests.create') }}" class="nav-link {{ request()->routeIs('client-portal.requests.create') ? 'active' : '' }}">
                                <x-icon name="plus" size="16" /> Nouvelle Demande
                            </a>
                        @else
                            @if($authUser && $authUser->canManageClientsAndRequests())
                                <a href="{{ route('requests.index') }}" class="nav-link {{ request()->routeIs('requests.*') || request()->routeIs('framings.*') ? 'active' : '' }}">
                                    <x-icon name="clipboard" size="16" /> Demandes
                                </a>
                            @endif
                            @if($authUser && $authUser->canConfigureMissions())
                                <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                                    <x-icon name="briefcase" size="16" /> Clients
                                </a>
                            @endif
                            <a href="{{ route('missions.index') }}" class="nav-link {{ request()->routeIs('missions.*') ? 'active' : '' }}" {{ request()->routeIs('missions.*') ? 'aria-current=page' : '' }}>
                                <x-icon name="briefcase" size="16" /> Missions
                            </a>
                            <a href="{{ route('collaborators.index') }}" class="nav-link {{ request()->routeIs('collaborators.*') ? 'active' : '' }}" {{ request()->routeIs('collaborators.*') ? 'aria-current=page' : '' }}>
                                <x-icon name="users" size="16" /> Équipe &amp; Staffing
                            </a>
                            <a href="{{ route('collections.index') }}" class="nav-link {{ request()->routeIs('collections.*') ? 'active' : '' }}" {{ request()->routeIs('collections.*') ? 'aria-current=page' : '' }}>
                                <x-icon name="clipboard" size="16" /> Collectes
                            </a>
                            <a href="{{ route('analyses.index') }}" class="nav-link {{ request()->routeIs('analyses.*') ? 'active' : '' }}" {{ request()->routeIs('analyses.*') ? 'aria-current=page' : '' }}>
                                <x-icon name="chart" size="16" /> Analyses
                            </a>
                        @endif

                        @if($authUser)
                            <div class="d-flex align-items-center gap-2 ms-md-3 pt-2 pt-md-0 border-top border-md-0">
                                <span class="badge bg-secondary text-white">
                                    {{ $authUser->name }} &bull; {{ $authUser->role?->label() ?? 'Utilisateur' }}
                                </span>
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                                        Déconnexion
                                    </button>
                                </form>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-sm ms-md-2">
                                <x-icon name="lock" size="14" /> Connexion
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main id="main-content" tabindex="-1">
        @yield('content')
    </main>

    <footer role="contentinfo">
        <div class="footer-container">
            <div class="footer-colophon">
                <p class="footer-brand">
                    <strong>CoreAssist</strong> &mdash; Pilotage de cabinet &amp; gestion des interventions
                </p>
            </div>
            <div class="footer-copyright">
                &copy; {{ date('Y') }} CoreAssist. Tous droits réservés.
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
