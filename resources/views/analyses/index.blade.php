@extends('layouts.app')

@section('title', 'Analyses Financières - CoreAssist')

@section('content')
<div class="container py-4">
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <div class="ba-eyebrow">MODÉLISATION & RENTABILITÉ</div>
            <h1 class="page-title mb-1">
                <x-icon name="chart" size="24" /> Analyses & Marges<span class="ba-dot">.</span>
            </h1>
            <p class="page-subtitle">Résultats calculés : Chiffre d'affaires, coûts globaux et rentabilité journalière.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Bloc d'exécution rapide d'analyse -->
    @if($collections->isNotEmpty())
        <div class="card shadow-sm border mb-4">
            <div class="card-body p-4">
                <h3 class="h6 fw-bold text-dark mb-2">
                    <x-icon name="chart" size="18" /> Exécuter une nouvelle analyse
                </h3>
                <p class="text-muted small mb-3">Sélectionnez une collecte existante pour calculer automatiquement les marges et ratios :</p>
                <form action="{{ route('analyses.store') }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <input type="hidden" name="analysis_type" value="gross_margin">
                    <div class="col-12 col-md-8 col-lg-9">
                        <label for="financial_collection_id" class="form-label fw-semibold small">Sélectionner la collecte source</label>
                        <select name="financial_collection_id" id="financial_collection_id" class="form-select" required>
                            @foreach($collections as $col)
                                <option value="{{ $col->id }}">
                                    {{ $col->title }} &mdash; {{ number_format($col->daily_quantity, 0, ',', ' ') }} unités à {{ number_format($col->selling_price, 0, ',', ' ') }} {{ $col->currency }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <x-icon name="play" size="14" /> Lancer le calcul
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($analyses->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                <x-icon name="chart" size="24" />
            </div>
            <div class="empty-state-title">Aucune analyse financière enregistrée</div>
            <p class="empty-state-text">Lancez votre première simulation à partir d'une collecte de données.</p>
            @if($collections->isEmpty())
                <div class="empty-state-actions">
                    <a href="{{ route('collections.create') }}" class="btn btn-primary btn-sm">
                        <x-icon name="plus" size="14" /> Créer une collecte d'abord
                    </a>
                </div>
            @endif
        </div>
    @else
        <!-- Design Pattern Mobile : Cartes empilées pour écrans tactiles (< 768px) -->
        <div class="mobile-card-list d-md-none">
            @foreach($analyses as $analysis)
                @php
                    $revenue = (float) $analysis->getResult('daily_revenue', 0);
                    $cost = (float) $analysis->getResult('daily_production_cost', 0);
                    $margin = (float) $analysis->getResult('daily_gross_margin', 0);
                    $marginRate = (float) $analysis->getResult('margin_rate_percent', $revenue > 0 ? ($margin / $revenue) * 100 : 0);
                    $currency = $analysis->input_snapshot['currency'] ?? 'XOF';
                @endphp
                <div class="mobile-data-card">
                    <div class="mobile-data-card-header">
                        <div>
                            <a href="{{ route('analyses.show', $analysis) }}" class="mobile-data-card-title">
                                Analyse #{{ $analysis->id }}
                            </a>
                            <div class="text-muted small">
                                @if($analysis->source instanceof \App\Modules\FinancialCollection\Http\Models\FinancialCollection)
                                    Source : {{ $analysis->source->title }}
                                @else
                                    Source : {{ $analysis->input_snapshot['title'] ?? 'Dossier source' }}
                                @endif
                            </div>
                        </div>
                        <span class="badge bg-light text-dark border">
                            {{ $analysis->analysis_type === 'gross_margin' ? 'Marge Brute' : ucfirst($analysis->analysis_type) }}
                        </span>
                    </div>

                    <div class="mobile-data-grid">
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Chiffre d'Affaires</span>
                            <span class="mobile-data-value">{{ number_format($revenue, 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Coût direct / j</span>
                            <span class="mobile-data-value">{{ number_format($cost, 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Marge Brute</span>
                            <span class="mobile-data-value text-success">{{ number_format($margin, 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Taux de rentabilité</span>
                            <span class="mobile-data-value">
                                <span class="badge {{ $marginRate >= 30 ? 'badge-green' : 'bg-light text-secondary border' }}">
                                    {{ number_format($marginRate, 1) }} %
                                </span>
                            </span>
                        </div>
                    </div>

                    <div class="text-muted small">
                        Calculée le {{ $analysis->created_at->format('d/m/Y à H:i') }}
                    </div>

                    <a href="{{ route('analyses.show', $analysis) }}" class="btn btn-outline-secondary btn-sm w-100">
                        Consulter le rapport complet &rarr;
                    </a>
                </div>
            @endforeach
        </div>

        <!-- Design Pattern Desktop : Tableau avec première colonne collante (> 768px) -->
        <div class="card shadow-sm border mb-4 d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover table-sticky-first align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Réf</th>
                            <th>Type</th>
                            <th>Source</th>
                            <th>Date de calcul</th>
                            <th>Chiffre d'Affaires / j</th>
                            <th>Coût direct / j</th>
                            <th>Marge Brute / j</th>
                            <th>Taux de marge</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($analyses as $analysis)
                            @php
                                $revenue = (float) $analysis->getResult('daily_revenue', 0);
                                $cost = (float) $analysis->getResult('daily_production_cost', 0);
                                $margin = (float) $analysis->getResult('daily_gross_margin', 0);
                                $marginRate = (float) $analysis->getResult('margin_rate_percent', $revenue > 0 ? ($margin / $revenue) * 100 : 0);
                                $currency = $analysis->input_snapshot['currency'] ?? 'XOF';
                            @endphp
                            <tr>
                                <td class="ps-4 fw-semibold">#{{ $analysis->id }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $analysis->analysis_type === 'gross_margin' ? 'Marge Brute' : ucfirst($analysis->analysis_type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($analysis->source instanceof \App\Modules\FinancialCollection\Http\Models\FinancialCollection)
                                        <a href="{{ route('collections.show', $analysis->source) }}" class="fw-semibold text-decoration-none text-dark">
                                            {{ $analysis->source->title }}
                                        </a>
                                    @elseif($analysis->source)
                                        <span class="fw-semibold">{{ $analysis->source->title ?? $analysis->source->name ?? 'Source externe' }}</span>
                                    @else
                                        <span class="text-muted small">{{ $analysis->input_snapshot['title'] ?? 'Source archivée' }}</span>
                                    @endif
                                </td>
                                <td>{{ $analysis->created_at->format('d/m/Y H:i') }}</td>
                                <td><strong>{{ number_format($revenue, 0, ',', ' ') }}</strong> {{ $currency }}</td>
                                <td>{{ number_format($cost, 0, ',', ' ') }} {{ $currency }}</td>
                                <td>
                                    <strong class="text-success">
                                        {{ number_format($margin, 0, ',', ' ') }} {{ $currency }}
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge {{ $marginRate >= 30 ? 'badge-green' : 'bg-light text-secondary border' }}">
                                        {{ number_format($marginRate, 1) }} %
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('analyses.show', $analysis) }}" class="btn btn-outline-secondary btn-sm">Détails &rarr;</a>
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
