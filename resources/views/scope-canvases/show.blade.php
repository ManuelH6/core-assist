@extends('layouts.app')

@section('title', 'Diagnostic Scope Canvas™ - ' . $canvas->title)

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">DIAGNOSTIC 360° &bull; OUTIL 02</div>
            <h1 class="page-title mb-1">{{ $canvas->title }}<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                Périmètre validé d'investigation pour la mission <strong>{{ $mission->reference ?? $mission->title }}</strong>
            </p>
        </div>

        @php
            $canEditDsc = $dscPermissions['edit'] ?? true;
            $canSubmitDsc = $dscPermissions['submit'] ?? true;
            $canValidateDsc = $dscPermissions['validate'] ?? true;
        @endphp

        <div class="d-flex flex-wrap align-items-center gap-2">
            @if($canvas->status === 'draft')
                <span class="badge bg-secondary text-white px-3 py-2 fs-6">Brouillon</span>
                @if($canEditDsc)
                    <a href="{{ route('missions.scope-canvas.initial-data', $mission) }}" class="btn btn-outline-primary">
                        <x-icon name="pen" size="16" /> Modifier le périmètre
                    </a>
                @endif
                @if($canSubmitDsc)
                    <form action="{{ route('missions.scope-canvas.submit', $mission) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Confirmer la soumission du périmètre pour revue interne ?');">
                            <x-icon name="check" size="16" /> Soumettre pour revue
                        </button>
                    </form>
                @endif
            @elseif($canvas->status === 'submitted')
                <span class="badge bg-warning text-dark px-3 py-2 fs-6">En cours de revue interne</span>
                @if($canEditDsc)
                    <a href="{{ route('missions.scope-canvas.initial-data', $mission) }}" class="btn btn-outline-secondary btn-sm">
                        <x-icon name="pen" size="14" /> Ajuster
                    </a>
                @endif
                @if($canValidateDsc)
                    <form action="{{ route('missions.scope-canvas.validate', $mission) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Valider officiellement ce périmètre d’investigation ?');">
                            <x-icon name="check" size="16" /> Valider le périmètre
                        </button>
                    </form>
                @endif
            @elseif($canvas->status === 'validated')
                <span class="badge bg-success px-3 py-2 fs-6">Périmètre Validé</span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Synthèse Métrique du Périmètre --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="stat-label">Domaines Couverts</div>
                    <div class="stat-value stat-value-primary">{{ count($canvas->included_domains) }}</div>
                    <div class="small text-muted mt-1">{{ implode(', ', $canvas->included_domains) ?: 'Aucun' }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="stat-label">Éléments Inclus</div>
                    <div class="stat-value stat-value-success">{{ count($canvas->included_items) }}</div>
                    <div class="small text-muted mt-1">Axes examinés en détail</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="stat-label">Éléments Exclus</div>
                    <div class="stat-value text-secondary">{{ count($canvas->excluded_items) }}</div>
                    <div class="small text-muted mt-1">Hors périmètre avec motif</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="stat-label">À Confirmer</div>
                    <div class="stat-value text-warning">{{ count($canvas->to_confirm_items) }}</div>
                    <div class="small text-muted mt-1">Arbitrage en attente</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau structuré du périmètre --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold">Détail des Axes et Niveaux de Profondeur</h2>
            <span class="badge bg-light text-dark border">{{ count($canvas->scope_items ?? []) }} éléments</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">Domaine & Axe</th>
                            <th scope="col">Sous-axe / Élément</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Profondeur</th>
                            <th scope="col" class="pe-3">Justification & Observations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($canvas->scope_items ?? [] as $item)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold">{{ $item['domain'] }}</div>
                                    <div class="small text-muted">{{ $item['axis'] }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-secondary small">{{ $item['subaxis'] ?? $item['element'] }}</div>
                                </td>
                                <td>
                                    @if(($item['status'] ?? '') === 'included')
                                        <span class="badge bg-success">Inclus</span>
                                    @elseif(($item['status'] ?? '') === 'excluded')
                                        <span class="badge bg-light text-danger border border-danger">Exclu</span>
                                    @else
                                        <span class="badge bg-warning text-dark">À confirmer</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ ucfirst($item['depth'] ?? 'standard') }}
                                    </span>
                                </td>
                                <td class="pe-3">
                                    @if(!empty($item['justification']))
                                        <div class="small text-danger mb-1">
                                            <strong>Motif :</strong> {{ $item['justification'] }}
                                        </div>
                                    @endif
                                    @if(!empty($item['observation']))
                                        <div class="small text-muted">{{ $item['observation'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Aucun élément défini dans le périmètre.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Cartes empilées sur mobile --}}
            <div class="d-md-none p-3">
                @forelse($canvas->scope_items ?? [] as $item)
                    <div class="card border mb-3 shadow-none">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="text-uppercase small text-muted">{{ $item['domain'] }}</div>
                                    <h3 class="h6 fw-bold mb-0">{{ $item['axis'] }}</h3>
                                </div>
                                <div>
                                    @if(($item['status'] ?? '') === 'included')
                                        <span class="badge bg-success">Inclus</span>
                                    @elseif(($item['status'] ?? '') === 'excluded')
                                        <span class="badge bg-light text-danger border border-danger">Exclu</span>
                                    @else
                                        <span class="badge bg-warning text-dark">À confirmer</span>
                                    @endif
                                </div>
                            </div>
                            <div class="small text-secondary mb-2">{{ $item['subaxis'] ?? $item['element'] }}</div>
                            <div class="small text-muted mb-2">
                                Profondeur : <strong>{{ ucfirst($item['depth'] ?? 'standard') }}</strong>
                            </div>
                            @if(!empty($item['justification']))
                                <div class="small text-danger mb-1">
                                    <strong>Justification :</strong> {{ $item['justification'] }}
                                </div>
                            @endif
                            @if(!empty($item['observation']))
                                <div class="small text-muted">
                                    <strong>Notes :</strong> {{ $item['observation'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        Aucun élément défini dans le périmètre.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Articulation avec la suite de la méthodologie 360° --}}
    <div class="card border border-primary border-opacity-25 bg-primary bg-opacity-10 shadow-sm">
        <div class="card-body">
            <h2 class="h6 fw-bold text-primary mb-2 d-flex align-items-center gap-2">
                <x-icon name="layers" size="18" />
                Articulation avec les outils suivants du Diagnostic 360°
            </h2>
            <p class="small text-secondary mb-0">
                Les axe(s) et sous-axe(s) inclus déterminent immédiatement la liste des informations et pièces à réclamer dans le <strong>Diagnostic Intake Register™</strong> et orientent les profils d'interlocuteurs à solliciter dans la <strong>Stakeholder Input Grid™</strong>.
            </p>
        </div>
    </div>
</div>
@endsection
