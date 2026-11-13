@extends('layouts.app')

@section('title', 'Rapport d\'Analyse Financière #' . $analysis->id . ' - CoreAssist')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('analyses.index')" label="Retour aux analyses" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <div class="ba-eyebrow">RAPPORT FINANCIER</div>
            <h1 class="page-title mb-1">
                <x-icon name="chart" size="24" /> Rapport d'Analyse Financière #{{ $analysis->id }}<span class="ba-dot">.</span>
            </h1>
            <p class="page-subtitle">
                Calcul exécuté le {{ $analysis->created_at->format('d/m/Y à H:i:s') }}
                @if($analysis->source instanceof \App\Modules\FinancialCollection\Http\Models\FinancialCollection)
                    &bull; Source : <a href="{{ route('collections.show', $analysis->source) }}" class="text-dark fw-semibold">{{ $analysis->source->title }}</a>
                @elseif($analysis->source)
                    &bull; Source : <strong class="text-dark">{{ $analysis->source->title ?? $analysis->source->name ?? 'Entité source' }}</strong>
                @endif
            </p>
        </div>
        <div>
            @if($analysis->source)
                <form action="{{ route('analyses.store') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="source_type" value="{{ get_class($analysis->source) }}">
                    <input type="hidden" name="source_id" value="{{ $analysis->source->getKey() }}">
                    <input type="hidden" name="analysis_type" value="{{ $analysis->analysis_type }}">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <x-icon name="refresh" size="14" /> Recalculer avec les données actuelles
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @php
        $currency = $analysis->input_snapshot['currency'] ?? 'XOF';
        $revenue = (float) $analysis->getResult('daily_revenue', 0);
        $cost = (float) $analysis->getResult('daily_production_cost', 0);
        $margin = (float) $analysis->getResult('daily_gross_margin', 0);
        $marginRate = (float) $analysis->getResult('margin_rate_percent', $revenue > 0 ? ($margin / $revenue) * 100 : 0);
        $unitMargin = (float) $analysis->getResult('unit_margin', 0);
    @endphp

    <!-- Indicateurs clés calculés -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Chiffre d'Affaires / Jour</div>
                    <div class="stat-value stat-value-primary">{{ number_format($revenue, 0, ',', ' ') }} {{ $currency }}</div>
                    <div class="text-muted small mt-1">Prix de vente &times; Quantité vendue</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Coût de Production / Jour</div>
                    <div class="stat-value stat-value-warning">{{ number_format($cost, 0, ',', ' ') }} {{ $currency }}</div>
                    <div class="text-muted small mt-1">Coût unitaire &times; Quantité produite</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Marge Brute / Jour</div>
                    <div class="stat-value stat-value-success">{{ number_format($margin, 0, ',', ' ') }} {{ $currency }}</div>
                    <div class="text-muted small mt-1">Bénéfice brut direct quotidien</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Taux de Rentabilité Brute</div>
                    <div class="stat-value">{{ number_format($marginRate, 1) }} %</div>
                    <div class="text-muted small mt-1">Marge unitaire : {{ number_format($unitMargin, 0, ',', ' ') }} {{ $currency }}/u</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Snapshots immuables (Entrées & Résultats) -->
    <div class="row g-3 mb-4">
        <!-- Snapshot des paramètres d'entrée -->
        <div class="col-12 col-lg-6">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-4">
                    <h3 class="h6 fw-bold text-dark mb-2">
                        <x-icon name="lock" size="16" /> Snapshot des données sources (Input)
                    </h3>
                    <p class="text-muted small mb-3">
                        Données d'entrées utilisées lors de l'exécution du calcul.
                    </p>

                    @if(!empty($analysis->input_snapshot))
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <tbody>
                                    @foreach($analysis->input_snapshot as $key => $val)
                                        <tr>
                                            <td class="text-muted small py-2">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                            <td class="fw-semibold text-end py-2">{{ is_array($val) ? json_encode($val) : $val }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted small">Aucun snapshot d'entrée enregistré.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Snapshot des résultats calculés -->
        <div class="col-12 col-lg-6">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-4">
                    <h3 class="h6 fw-bold text-dark mb-2">
                        <x-icon name="check" size="16" /> Snapshot des résultats (Output)
                    </h3>
                    <p class="text-muted small mb-3">
                        Métriques figées produites par la méthode : <strong>{{ $analysis->analysis_type }}</strong>.
                    </p>

                    @if(!empty($analysis->result_snapshot))
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <tbody>
                                    @foreach($analysis->result_snapshot as $key => $val)
                                        <tr>
                                            <td class="text-muted small py-2">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                            <td class="fw-semibold text-end py-2">{{ is_numeric($val) ? number_format((float) $val, 2, ',', ' ') : $val }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted small">Aucun snapshot de résultat enregistré.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
