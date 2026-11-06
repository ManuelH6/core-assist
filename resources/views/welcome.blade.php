@extends('layouts.app')

@section('title', 'Gestion de Cabinet & Pilotage - ' . config('app.name', 'Laravel'))

@section('content')
<div class="container">
    <div class="hero">
        <div class="badge-row">
            <span class="badge badge-green"><x-icon name="check" size="13" /> 100% Laravel Blade</span>
            <span class="badge"><x-icon name="briefcase" size="13" /> Gestion de Cabinet & Staffing</span>
            <span class="badge"><x-icon name="chart" size="13" /> Module Financier</span>
        </div>
        <h1>Pilotage de Cabinet & Outils Métier</h1>
        <p>
            Plateforme modulaire : gestion des missions, allocation hebdomadaire de la charge de travail (staffing), suivi d'équipe et analyses financières.
        </p>
        <div class="actions">
            <a href="{{ route('missions.index') }}" class="btn btn-primary">
                <x-icon name="briefcase" size="16" /> Voir les Missions
            </a>
            <a href="{{ route('collaborators.index') }}" class="btn btn-secondary">
                <x-icon name="users" size="16" /> Équipe & Staffing
            </a>
            <a href="{{ route('collections.index') }}" class="btn btn-secondary">
                <x-icon name="clipboard" size="16" /> Outils Financiers
            </a>
        </div>
    </div>

    <div class="features-grid">
        <div class="card">
            <h3 class="card-title">
                <x-icon name="briefcase" size="20" /> Missions & Dossiers
            </h3>
            <p style="margin-bottom: 1.25rem;">
                Suivi des missions, taux d'avancement (%), interventions programmées et volume hebdomadaire engagé.
            </p>
            <a href="{{ route('missions.index') }}" class="btn btn-secondary btn-sm">Accéder aux missions</a>
        </div>

        <div class="card">
            <h3 class="card-title">
                <x-icon name="users" size="20" /> Staffing Cabinet (h/sem)
            </h3>
            <p style="margin-bottom: 1.25rem;">
                Allocation par nombre d'heures par semaine, calcul du taux d'occupation sur 35h et détection instantanée du sur-staffing.
            </p>
            <a href="{{ route('collaborators.index') }}" class="btn btn-secondary btn-sm">Gérer le staffing</a>
        </div>

        <div class="card">
            <h3 class="card-title">
                <x-icon name="clipboard" size="20" /> Collecte & Production
            </h3>
            <p style="margin-bottom: 1.25rem;">
                Saisie des volumes de vente, prix unitaires et coûts de production journaliers.
            </p>
            <a href="{{ route('collections.index') }}" class="btn btn-secondary btn-sm">Ouvrir les collectes</a>
        </div>

        <div class="card">
            <h3 class="card-title">
                <x-icon name="chart" size="20" /> Analyse & Rentabilité
            </h3>
            <p style="margin-bottom: 1.25rem;">
                Moteur de calcul de marge brute journalière avec traçabilité par snapshots immuables.
            </p>
            <a href="{{ route('analyses.index') }}" class="btn btn-secondary btn-sm">Consulter les analyses</a>
        </div>
    </div>
</div>
@endsection
