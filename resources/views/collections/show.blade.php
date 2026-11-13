@extends('layouts.app')

@section('title', $collection->title . ' - Collecte Financière')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('collections.index')" label="Retour aux collectes" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <div class="ba-eyebrow">FICHE COLLECTE</div>
            <h1 class="page-title mb-1">
                {{ $collection->title }}<span class="ba-dot">.</span>
            </h1>
            <p class="page-subtitle">
                Date d'activité : {{ $collection->entry_date ? $collection->entry_date->format('d/m/Y') : 'Non renseignée' }} &bull; Saisie le {{ $collection->created_at->format('d/m/Y à H:i') }}
            </p>
        </div>
        <div>
            <form action="{{ route('analyses.store') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="financial_collection_id" value="{{ $collection->id }}">
                <button type="submit" class="btn btn-primary">
                    <x-icon name="chart" size="15" /> Lancer une analyse
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Prix de Vente Unitaire</div>
                    <div class="stat-value stat-value-primary">{{ number_format($collection->selling_price, 0, ',', ' ') }} {{ $collection->currency }}</div>
                    <div class="text-muted small mt-1">Tarif appliqué par unité vendue</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Quantité Vendue / Jour</div>
                    <div class="stat-value">{{ number_format($collection->daily_quantity, 0, ',', ' ') }} <span class="fs-6 text-muted fw-normal">unités</span></div>
                    <div class="text-muted small mt-1">Volume quotidien constaté</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Coût Unitaire de Production</div>
                    <div class="stat-value stat-value-warning">{{ number_format($collection->unit_production_cost, 0, ',', ' ') }} {{ $collection->currency }}</div>
                    <div class="text-muted small mt-1">Coût direct par unité produite</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <h2 class="h5 fw-bold text-dark mb-3">
            <x-icon name="chart" size="18" /> Analyses générées pour cette collecte ({{ $collection->analyses->count() }})
        </h2>

        @if($collection->analyses->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon">
                    <x-icon name="chart" size="24" />
                </div>
                <div class="empty-state-title">Cette collecte n'a pas encore été analysée</div>
                <p class="empty-state-text">Générez un rapport financier instantané avec calcul du CA, coût total et marge brute.</p>
                <div class="empty-state-actions">
                    <form action="{{ route('analyses.store') }}" method="POST" class="w-100 d-flex justify-content-center">
                        @csrf
                        <input type="hidden" name="financial_collection_id" value="{{ $collection->id }}">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <x-icon name="chart" size="14" /> Exécuter l'analyse financière
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="card shadow-sm border mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Réf. Analyse</th>
                                <th>Date d'exécution</th>
                                <th>Chiffre d'Affaires / jour</th>
                                <th>Coût de production / jour</th>
                                <th>Marge Brute / jour</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($collection->analyses as $analysis)
                                <tr>
                                    <td class="ps-4 fw-semibold">#{{ $analysis->id }}</td>
                                    <td>{{ $analysis->created_at->format('d/m/Y à H:i:s') }}</td>
                                    <td>{{ number_format($analysis->daily_revenue, 0, ',', ' ') }} {{ $collection->currency }}</td>
                                    <td>{{ number_format($analysis->daily_production_cost, 0, ',', ' ') }} {{ $collection->currency }}</td>
                                    <td>
                                        <strong class="text-success">
                                            {{ number_format($analysis->daily_gross_margin, 0, ',', ' ') }} {{ $collection->currency }}
                                        </strong>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="{{ route('analyses.show', $analysis) }}" class="btn btn-outline-secondary btn-sm">Consulter le rapport &rarr;</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
