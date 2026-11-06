@extends('layouts.app')

@section('title', 'Collectes Financières - Boulangerie')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <x-icon name="clipboard" size="24" /> Collectes Financières
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Gérez les données de vente et de coûts de production de votre boulangerie.</p>
        </div>
        <div>
            <a href="{{ route('collections.create') }}" class="btn btn-primary">
                <x-icon name="plus" size="14" /> Nouvelle Collecte
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-container">
        @if($collections->isEmpty())
            <div class="empty-state">
                <p style="font-size: 1.1rem; margin-bottom: 1rem;">Aucune collecte financière enregistrée pour l'instant.</p>
                <a href="{{ route('collections.create') }}" class="btn btn-secondary">Créer votre première collecte</a>
            </div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Titre / Produit</th>
                        <th>Date d'activité</th>
                        <th>Prix de vente</th>
                        <th>Quantité / jour</th>
                        <th>Coût unitaire</th>
                        <th>Devise</th>
                        <th>Analyses</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($collections as $collection)
                        <tr>
                            <td>
                                <strong>{{ $collection->title }}</strong>
                            </td>
                            <td>{{ $collection->entry_date ? $collection->entry_date->format('d/m/Y') : '-' }}</td>
                            <td>{{ number_format($collection->selling_price, 0, ',', ' ') }} {{ $collection->currency }}</td>
                            <td>{{ number_format($collection->daily_quantity, 0, ',', ' ') }}</td>
                            <td>{{ number_format($collection->unit_production_cost, 0, ',', ' ') }} {{ $collection->currency }}</td>
                            <td><span class="badge">{{ $collection->currency }}</span></td>
                            <td>
                                @if($collection->analyses->count() > 0)
                                    <span class="badge badge-green">{{ $collection->analyses->count() }} effectuée(s)</span>
                                @else
                                    <span class="badge">Non analysée</span>
                                @endif
                            </td>
                            <td style="text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <a href="{{ route('collections.show', $collection) }}" class="btn btn-secondary btn-sm">Détails</a>
                                <form action="{{ route('analyses.store') }}" method="POST" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="financial_collection_id" value="{{ $collection->id }}">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <x-icon name="chart" size="13" /> Analyser
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
