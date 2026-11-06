@extends('layouts.app')

@section('title', $collection->title . ' - Collecte Financière')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <div style="margin-bottom: 0.5rem;">
                <a href="{{ route('collections.index') }}" class="btn btn-secondary btn-sm">&larr; Toutes les collectes</a>
            </div>
            <h1 class="page-title">{{ $collection->title }}</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Date d'activité : {{ $collection->entry_date ? $collection->entry_date->format('d/m/Y') : 'Non renseignée' }} &bull; Créée le {{ $collection->created_at->format('d/m/Y H:i') }}
            </p>
        </div>
        <div>
            <form action="{{ route('analyses.store') }}" method="POST" style="display: inline;">
                @csrf
                <input type="hidden" name="financial_collection_id" value="{{ $collection->id }}">
                <button type="submit" class="btn btn-primary">
                    <x-icon name="chart" size="15" /> Lancer une analyse
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Prix de vente unitaire</div>
            <div class="stat-value stat-value-primary">{{ number_format($collection->selling_price, 0, ',', ' ') }} {{ $collection->currency }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Quantité vendue / jour</div>
            <div class="stat-value">{{ number_format($collection->daily_quantity, 0, ',', ' ') }} unités</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Coût unitaire de production</div>
            <div class="stat-value stat-value-warning">{{ number_format($collection->unit_production_cost, 0, ',', ' ') }} {{ $collection->currency }}</div>
        </div>
    </div>

    <div style="margin-top: 2rem;">
        <h2 style="font-size: 1.35rem; margin-bottom: 1rem;">
            <x-icon name="chart" size="20" /> Analyses générées pour cette collecte ({{ $collection->analyses->count() }})
        </h2>

        <div class="table-container">
            @if($collection->analyses->isEmpty())
                <div class="empty-state">
                    <p style="margin-bottom: 1rem;">Cette collecte n'a pas encore été analysée.</p>
                    <form action="{{ route('analyses.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="financial_collection_id" value="{{ $collection->id }}">
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="chart" size="15" /> Exécuter l'analyse financière
                        </button>
                    </form>
                </div>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID Analyse</th>
                            <th>Date d'exécution</th>
                            <th>Chiffre d'Affaires / jour</th>
                            <th>Coût de production / jour</th>
                            <th>Marge Brute / jour</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($collection->analyses as $analysis)
                            <tr>
                                <td>#{{ $analysis->id }}</td>
                                <td>{{ $analysis->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>{{ number_format($analysis->daily_revenue, 0, ',', ' ') }} {{ $collection->currency }}</td>
                                <td>{{ number_format($analysis->daily_production_cost, 0, ',', ' ') }} {{ $collection->currency }}</td>
                                <td>
                                    <strong class="stat-value-success">
                                        {{ number_format($analysis->daily_gross_margin, 0, ',', ' ') }} {{ $collection->currency }}
                                    </strong>
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('analyses.show', $analysis) }}" class="btn btn-secondary btn-sm">Consulter le rapport</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
