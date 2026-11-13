@extends('layouts.app')

@section('title', 'Pilotage de Cabinet & Outils Métier - CoreAssist')

@section('content')
@php
    $authUser = auth()->user();
@endphp
<div class="container py-4">
    <!-- En-tête héro sobre et institutionnel -->
    <section class="text-center py-5" aria-labelledby="hero-title">
        <div class="ba-eyebrow">ESPACE OPÉRATIONNEL</div>
        <h1 id="hero-title" class="display-5 fw-bold mb-3">
            Pilotage de Cabinet &amp; Interventions<span class="ba-dot">.</span>
        </h1>
        <p class="lead mx-auto mb-4" style="max-width: 680px; color: var(--ba-graphite-500);">
            Suivi des demandes clients, cadrage méthodologique, configuration des missions et diagnostic 360° en temps réel.
        </p>

        @if(! $authUser)
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <x-icon name="lock" size="16" /> Se connecter à mon espace
                </a>
                <a href="{{ route('missions.index') }}" class="btn btn-secondary">
                    <x-icon name="briefcase" size="16" /> Portefeuille des Missions
                </a>
            </div>
        @elseif($authUser->isClient())
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="{{ route('client-portal.index') }}" class="btn btn-primary">
                    <x-icon name="clipboard" size="16" /> Accéder à mes demandes
                </a>
                <a href="{{ route('client-portal.requests.create') }}" class="btn btn-secondary">
                    <x-icon name="plus" size="16" /> Déposer une nouvelle demande
                </a>
            </div>
        @else
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                @if($authUser->canManageClientsAndRequests())
                    <a href="{{ route('requests.index') }}" class="btn btn-primary">
                        <x-icon name="clipboard" size="16" /> Demandes Clients
                    </a>
                @endif
                <a href="{{ route('missions.index') }}" class="btn btn-secondary">
                    <x-icon name="briefcase" size="16" /> Accéder aux Missions
                </a>
                <a href="{{ route('collaborators.index') }}" class="btn btn-outline-secondary">
                    <x-icon name="users" size="16" /> Plan de Staffing
                </a>
            </div>
        @endif
    </section>

    @if(! $authUser || $authUser->isCabinet())
        <section aria-labelledby="modules-title" class="mt-4">
            <h2 id="modules-title" class="visually-hidden">Espaces de travail</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="ba-eyebrow">RELATION CLIENT</div>
                            <h3 class="card-title h5 d-flex align-items-center gap-2 mb-2">
                                <x-icon name="clipboard" size="18" /> Demandes &amp; Cadrage
                            </h3>
                            <p class="card-text small mb-4 flex-grow-1" style="color: var(--ba-graphite-500);">
                                Réception des demandes portail, compréhension initiale du besoin et validation du cadrage.
                            </p>
                            <a href="{{ $authUser?->canManageClientsAndRequests() ? route('requests.index') : route('login') }}" class="btn btn-outline-secondary btn-sm mt-auto">
                                Consulter les demandes &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="ba-eyebrow">PORTEFEUILLE</div>
                            <h3 class="card-title h5 d-flex align-items-center gap-2 mb-2">
                                <x-icon name="briefcase" size="18" /> Missions
                            </h3>
                            <p class="card-text small mb-4 flex-grow-1" style="color: var(--ba-graphite-500);">
                                Configuration des outils autorisés, pilotage de l'avancement et diagnostic 360°.
                            </p>
                            <a href="{{ route('missions.index') }}" class="btn btn-outline-secondary btn-sm mt-auto">
                                Consulter le portefeuille &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="ba-eyebrow">RESSOURCES</div>
                            <h3 class="card-title h5 d-flex align-items-center gap-2 mb-2">
                                <x-icon name="users" size="18" /> Équipe &amp; Staffing
                            </h3>
                            <p class="card-text small mb-4 flex-grow-1" style="color: var(--ba-graphite-500);">
                                Équilibrage de la charge hebdomadaire, suivi du taux d'occupation et alertes surcharge.
                            </p>
                            <a href="{{ route('collaborators.index') }}" class="btn btn-outline-secondary btn-sm mt-auto">
                                Gérer les allocations &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="ba-eyebrow">RENTABILITÉ</div>
                            <h3 class="card-title h5 d-flex align-items-center gap-2 mb-2">
                                <x-icon name="chart" size="18" /> Analyses
                            </h3>
                            <p class="card-text small mb-4 flex-grow-1" style="color: var(--ba-graphite-500);">
                                Calcul instantané des marges brutes, rentabilité journalière et snapshots immuables.
                            </p>
                            <a href="{{ route('analyses.index') }}" class="btn btn-outline-secondary btn-sm mt-auto">
                                Voir les rapports &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
