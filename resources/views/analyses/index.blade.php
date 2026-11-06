@extends('layouts.app')

@section('title', 'Analyses Financières - Boulangerie')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <x-icon name="chart" size="24" /> Analyses Financières
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Résultats calculés : Chiffre d'affaires, coûts globaux et rentabilité journalière.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Bloc d'exécution rapide d'analyse -->
    @if($collections->isNotEmpty())
        <div class="card" style="margin-bottom: 2rem;">
            <h3 class="card-title">
                <x-icon name="chart" size="18" /> Exécuter une nouvelle analyse
            </h3>
            <p style="margin-bottom: 1rem;">Sélectionnez une collecte financière existante pour calculer automatiquement la rentabilité :</p>
            <form action="{{ route('analyses.store') }}" method="POST" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                @csrf
                <div style="flex: 1; min-width: 250px;">
                    <label for="financial_collection_id" class="form-label">Sélectionner la collecte</label>
                    <select name="financial_collection_id" id="financial_collection_id" class="form-select" required>
                        @foreach($collections as $col)
                            <option value="{{ $col->id }}">
                                {{ $col->title }} &mdash; {{ number_format($col->daily_quantity, 0, ',', ' ') }} unités à {{ number_format($col->selling_price, 0, ',', ' ') }} {{ $col->currency }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="height: 44px; display: inline-flex; align-items: center; gap: 0.4rem;">
                    <x-icon name="play" size="14" /> Analyser cette collecte
                </button>
            </form>
        </div>
    @endif

    <div class="table-container">
        @if($analyses->isEmpty())
            <div class="empty-state">
                <p style="font-size: 1.1rem; margin-bottom: 1rem;">Aucune analyse financière enregistrée pour l'instant.</p>
                @if($collections->isEmpty())
                    <a href="{{ route('collections.create') }}" class="btn btn-primary">Créer une collecte d'abord</a>
                @endif
            </div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Collecte source</th>
                        <th>Date de calcul</th>
                        <th>Chiffre d'Affaires / jour</th>
                        <th>Coût de production / jour</th>
                        <th>Marge Brute / jour</th>
                        <th>Taux de marge</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analyses as $analysis)
                        @php
                            $marginRate = $analysis->daily_revenue > 0 ? ($analysis->daily_gross_margin / $analysis->daily_revenue) * 100 : 0;
                            $currency = $analysis->input_snapshot['currency'] ?? 'XOF';
                        @endphp
                        <tr>
                            <td>#{{ $analysis->id }}</td>
                            <td>
                                @if($analysis->collection)
                                    <a href="{{ route('collections.show', $analysis->collection) }}" style="color: var(--primary); text-decoration: none;">
                                        {{ $analysis->collection->title }}
                                    </a>
                                @else
                                    <span style="color: var(--text-muted);">{{ $analysis->input_snapshot['title'] ?? 'Collecte supprimée' }}</span>
                                @endif
                            </td>
                            <td>{{ $analysis->created_at->format('d/m/Y H:i') }}</td>
                            <td><strong>{{ number_format($analysis->daily_revenue, 0, ',', ' ') }} {{ $currency }}</strong></td>
                            <td>{{ number_format($analysis->daily_production_cost, 0, ',', ' ') }} {{ $currency }}</td>
                            <td>
                                <strong class="stat-value-success">
                                    {{ number_format($analysis->daily_gross_margin, 0, ',', ' ') }} {{ $currency }}
                                </strong>
                            </td>
                            <td>
                                <span class="badge {{ $marginRate >= 30 ? 'badge-green' : '' }}">
                                    {{ number_format($marginRate, 1) }} %
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('analyses.show', $analysis) }}" class="btn btn-secondary btn-sm">Détails</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
