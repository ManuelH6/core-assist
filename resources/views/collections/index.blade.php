@extends('layouts.app')

@section('title', 'Collectes Financières - CoreAssist')

@section('content')
<div class="container py-4">
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <div class="ba-eyebrow">ACQUISITION DE DONNÉES</div>
            <h1 class="page-title mb-1">
                <x-icon name="clipboard" size="24" /> Collectes Financières<span class="ba-dot">.</span>
            </h1>
            <p class="page-subtitle">Gérez les données de vente et de coûts directs d'exploitation de votre activité.</p>
        </div>
        <div class="w-100 w-md-auto">
            <a href="{{ route('collections.create') }}" class="btn btn-primary w-100">
                <x-icon name="plus" size="14" /> Nouvelle Collecte
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($collections->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                <x-icon name="clipboard" size="24" />
            </div>
            <div class="empty-state-title">Aucune collecte financière enregistrée</div>
            <p class="empty-state-text">Saisissez les données journalières de production et de prix pour démarrer les analyses de rentabilité.</p>
            <div class="empty-state-actions">
                <a href="{{ route('collections.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" size="14" /> Créer une première collecte
                </a>
            </div>
        </div>
    @else
        <!-- Design Pattern Mobile : Cartes empilées pour écrans tactiles (< 768px) -->
        <div class="mobile-card-list d-md-none">
            @foreach($collections as $collection)
                <div class="mobile-data-card">
                    <div class="mobile-data-card-header">
                        <div>
                            <a href="{{ route('collections.show', $collection) }}" class="mobile-data-card-title">
                                {{ $collection->title }}
                            </a>
                            <div class="text-muted small">
                                Date : {{ $collection->entry_date ? $collection->entry_date->format('d/m/Y') : 'Non renseignée' }}
                            </div>
                        </div>
                        <span class="badge bg-light text-secondary border">{{ $collection->currency }}</span>
                    </div>

                    <div class="mobile-data-grid">
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Prix unitaire</span>
                            <span class="mobile-data-value">{{ number_format($collection->selling_price, 0, ',', ' ') }} {{ $collection->currency }}</span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Quantité / jour</span>
                            <span class="mobile-data-value">{{ number_format($collection->daily_quantity, 0, ',', ' ') }} u</span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Coût unitaire</span>
                            <span class="mobile-data-value">{{ number_format($collection->unit_production_cost, 0, ',', ' ') }} {{ $collection->currency }}</span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Analyses</span>
                            <span class="mobile-data-value">
                                @if($collection->analyses->count() > 0)
                                    <span class="badge badge-green">{{ $collection->analyses->count() }} faite(s)</span>
                                @else
                                    <span class="badge bg-light text-muted border">0</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('collections.show', $collection) }}" class="btn btn-outline-secondary btn-sm flex-fill">Détails</a>
                        <form action="{{ route('analyses.store') }}" method="POST" class="flex-fill">
                            @csrf
                            <input type="hidden" name="financial_collection_id" value="{{ $collection->id }}">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <x-icon name="chart" size="13" /> Analyser
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Design Pattern Desktop : Tableau avec première colonne collante (> 768px) -->
        <div class="card shadow-sm border mb-4 d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover table-sticky-first align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Titre / Produit</th>
                            <th>Date d'activité</th>
                            <th>Prix de vente</th>
                            <th>Quantité / jour</th>
                            <th>Coût unitaire</th>
                            <th>Devise</th>
                            <th>Analyses</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($collections as $collection)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('collections.show', $collection) }}" class="fw-semibold text-decoration-none text-dark">
                                        {{ $collection->title }}
                                    </a>
                                </td>
                                <td>{{ $collection->entry_date ? $collection->entry_date->format('d/m/Y') : '-' }}</td>
                                <td><span class="fw-semibold">{{ number_format($collection->selling_price, 0, ',', ' ') }}</span> {{ $collection->currency }}</td>
                                <td>{{ number_format($collection->daily_quantity, 0, ',', ' ') }}</td>
                                <td>{{ number_format($collection->unit_production_cost, 0, ',', ' ') }} {{ $collection->currency }}</td>
                                <td><span class="badge bg-light text-secondary border">{{ $collection->currency }}</span></td>
                                <td>
                                    @if($collection->analyses->count() > 0)
                                        <span class="badge badge-green">{{ $collection->analyses->count() }} effectuée(s)</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Non analysée</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('collections.show', $collection) }}" class="btn btn-outline-secondary btn-sm">Détails</a>
                                        <form action="{{ route('analyses.store') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="financial_collection_id" value="{{ $collection->id }}">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <x-icon name="chart" size="13" /> Analyser
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
