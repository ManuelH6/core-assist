@extends('layouts.app')

@section('title', 'Détail de l\'Analyse #' . $analysis->id)

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <div style="margin-bottom: 0.5rem;">
                <a href="{{ route('analyses.index') }}" class="btn btn-secondary btn-sm">&larr; Toutes les analyses</a>
            </div>
            <h1 class="page-title">
                <x-icon name="chart" size="24" /> Rapport d'Analyse Financière #{{ $analysis->id }}
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Calcul exécuté le {{ $analysis->created_at->format('d/m/Y à H:i:s') }}
                @if($analysis->collection)
                    &bull; Basé sur la collecte <a href="{{ route('collections.show', $analysis->collection) }}" style="color: var(--primary);">{{ $analysis->collection->title }}</a>
                @endif
            </p>
        </div>
        <div>
            @if($analysis->collection)
                <form action="{{ route('analyses.store') }}" method="POST" style="display: inline;">
                    @csrf
                    <input type="hidden" name="financial_collection_id" value="{{ $analysis->collection->id }}">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <x-icon name="refresh" size="14" /> Ré-analyser avec les données actuelles
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @php
        $currency = $analysis->input_snapshot['currency'] ?? 'XOF';
        $marginRate = $analysis->daily_revenue > 0 ? ($analysis->daily_gross_margin / $analysis->daily_revenue) * 100 : 0;
        $snapshotQty = $analysis->input_snapshot['daily_quantity'] ?? 1;
        $unitMargin = $snapshotQty > 0 ? ($analysis->daily_gross_margin / $snapshotQty) : 0;
    @endphp

    <!-- Indicateurs clés calculés -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Chiffre d'Affaires / Jour</div>
            <div class="stat-value stat-value-primary">{{ number_format($analysis->daily_revenue, 0, ',', ' ') }} {{ $currency }}</div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Prix de vente &times; Quantité vendue</p>
        </div>

        <div class="stat-card">
            <div class="stat-label">Coût de Production / Jour</div>
            <div class="stat-value stat-value-warning">{{ number_format($analysis->daily_production_cost, 0, ',', ' ') }} {{ $currency }}</div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Coût unitaire &times; Quantité produite</p>
        </div>

        <div class="stat-card">
            <div class="stat-label">Marge Brute / Jour</div>
            <div class="stat-value stat-value-success">{{ number_format($analysis->daily_gross_margin, 0, ',', ' ') }} {{ $currency }}</div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Bénéfice brut d'exploitation quotidien</p>
        </div>

        <div class="stat-card">
            <div class="stat-label">Taux de Rentabilité Brute</div>
            <div class="stat-value">{{ number_format($marginRate, 1) }} %</div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Marge unitaire : {{ number_format($unitMargin, 0, ',', ' ') }} {{ $currency }}/unité</p>
        </div>
    </div>

    <!-- Snapshot des données sources immuables -->
    <div class="card" style="margin-top: 2rem;">
        <h3 class="card-title">
            <x-icon name="lock" size="18" /> Snapshot immuable des données sources
        </h3>
        <p style="color: var(--text-muted); margin-bottom: 1.25rem;">
            Ces paramètres représentent la copie exacte des données utilisées lors de l'exécution de ce calcul. Ils garantissent que l'analyse reste traçable et inaltérable même si la collecte est ultérieurement modifiée.
        </p>

        @if(!empty($analysis->input_snapshot))
            <table class="table">
                <thead>
                    <tr>
                        <th>Paramètre source</th>
                        <th>Valeur figée au calcul</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Produit / Titre</strong></td>
                        <td>{{ $analysis->input_snapshot['title'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Date d'activité</strong></td>
                        <td>{{ $analysis->input_snapshot['entry_date'] ?? 'Non spécifiée' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Prix de vente unitaire</strong></td>
                        <td>{{ number_format($analysis->input_snapshot['selling_price'] ?? 0, 0, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    <tr>
                        <td><strong>Quantité journalière</strong></td>
                        <td>{{ number_format($analysis->input_snapshot['daily_quantity'] ?? 0, 0, ',', ' ') }} pièces</td>
                    </tr>
                    <tr>
                        <td><strong>Coût unitaire de production</strong></td>
                        <td>{{ number_format($analysis->input_snapshot['unit_production_cost'] ?? 0, 0, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    <tr>
                        <td><strong>Devise monétaire</strong></td>
                        <td>{{ $currency }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <p style="color: var(--text-muted);">Aucun snapshot disponible pour cette analyse.</p>
        @endif
    </div>
</div>
@endsection
