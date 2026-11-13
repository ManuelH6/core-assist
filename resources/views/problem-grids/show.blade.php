@extends('layouts.app')

@section('title', 'Problem Structuring Grid™ - ' . $mission->title)

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">DIAGNOSTIC 360° &bull; OUTIL 06</div>
            <h1 class="page-title mb-1">Problem Structuring Grid™<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                Qualification des dysfonctionnements, analyse causale, impacts et réseau relationnel des problèmes &bull; Mission <strong>{{ $mission->reference ?? $mission->title }}</strong>
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <form action="{{ route('missions.problem-grid.prepare', $mission) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Synchroniser les difficultés depuis le D360">
                    <x-icon name="refresh-cw" size="14" /> Synchroniser D360
                </button>
            </form>

            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddRelation">
                <x-icon name="git-pull-request" size="14" /> Lier deux problèmes
            </button>

            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddProblem">
                <x-icon name="plus" size="14" /> Nouveau problème
            </button>

            <form action="{{ route('missions.problem-grid.snapshot', $mission) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <x-icon name="check-circle" size="14" /> Consolider l'instantané
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Bandeau métrique de progression de la structuration --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                <div>
                    <h2 class="h6 fw-bold mb-0">Taux de structuration des problèmes</h2>
                    <div class="small text-muted">
                        {{ $prioritizedProblems->whereIn('status', ['structured', 'validated'])->count() }} sur {{ $prioritizedProblems->count() }} problème(s) complètement structuré(s) ou validé(s)
                    </div>
                </div>
                <div class="fw-bold fs-5 text-primary">
                    {{ $grid->structuringProgressPercentage() }}%
                </div>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $grid->structuringProgressPercentage() }}%;" aria-valuenow="{{ $grid->structuringProgressPercentage() }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <div class="row g-3 mt-3 pt-3 border-top">
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Problèmes</div>
                    <div class="fw-bold fs-5 text-dark">{{ $prioritizedProblems->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Priorité P1 (Critiques)</div>
                    <div class="fw-bold fs-5 text-danger">{{ $prioritizedProblems->filter(fn($p) => ($p->priorityAssessment?->priority_level ?? '') === 'P1')->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Priorité P2 (Majeurs)</div>
                    <div class="fw-bold fs-5 text-warning">{{ $prioritizedProblems->filter(fn($p) => ($p->priorityAssessment?->priority_level ?? '') === 'P2')->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Causes identifiées</div>
                    <div class="fw-bold fs-5 text-primary">{{ $prioritizedProblems->sum(fn($p) => $p->causes->count()) }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Conséquences tracées</div>
                    <div class="fw-bold fs-5 text-secondary">{{ $prioritizedProblems->sum(fn($p) => $p->consequences->count()) }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Relations causales</div>
                    <div class="fw-bold fs-5 text-info">{{ $relations->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Synthèse des causes racines et réseau relationnel si des liens existent --}}
    @if($relations->isNotEmpty())
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-light py-3 border-bottom">
                <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                    <x-icon name="git-pull-request" size="16" /> Réseau de causalité et d'influence entre problèmes ({{ $relations->count() }})
                </h2>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">
                    @foreach($relations as $rel)
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-white small d-flex flex-column gap-1">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="badge bg-secondary me-1">{{ $rel->sourceProblem?->reference }}</span>
                                        <strong class="text-dark">{{ Str::limit($rel->sourceProblem?->title, 35) }}</strong>
                                    </div>
                                    <span class="badge {{ $rel->is_hypothetical ? 'bg-warning text-dark' : 'bg-primary' }}" style="font-size: 0.7rem;">
                                        {{ str_replace('_', ' ', $rel->relation_type) }}
                                    </span>
                                </div>
                                <div class="text-muted ps-2 border-start border-2">
                                    &rarr; Vers : <span class="badge bg-secondary me-1">{{ $rel->targetProblem?->reference }}</span> {{ Str::limit($rel->targetProblem?->title, 40) }}
                                </div>
                                @if($rel->explanation)
                                    <div class="text-secondary small fst-italic ps-2">{{ $rel->explanation }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Registre structuré des problèmes --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                <x-icon name="list" size="16" /> Registre des problèmes hiérarchisés
            </h2>
            <span class="badge bg-secondary">{{ $prioritizedProblems->count() }} problème(s)</span>
        </div>

        <div class="card-body p-0">
            {{-- Tableau Desktop (>= 768px) --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">Réf. & Priorité</th>
                            <th style="width: 25%;">Problème & Périmètre</th>
                            <th style="width: 25%;">Causes & Confirmation</th>
                            <th style="width: 20%;">Conséquences & Impacts</th>
                            <th style="width: 10%;">Statut</th>
                            <th style="width: 10%;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($prioritizedProblems as $problem)
                            @php
                                $priority = $problem->priorityAssessment?->priority_level ?? 'P3';
                                $pBadgeClass = match($priority) {
                                    'P1' => 'bg-danger text-white',
                                    'P2' => 'bg-warning text-dark',
                                    'P3' => 'bg-primary text-white',
                                    default => 'bg-secondary text-white',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark font-monospace">{{ $problem->reference }}</div>
                                    <span class="badge {{ $pBadgeClass }} mt-1">{{ $priority }}</span>
                                    <div class="small text-muted mt-1" style="font-size: 0.7rem;">
                                        {{ $problem->priorityAssessment?->severity ?? 'Non qualifié' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark mb-1">{{ $problem->title }}</div>
                                    <div class="small text-muted mb-1">
                                        <span class="badge bg-light text-dark border">{{ $problem->domain }}</span>
                                        &bull; {{ $problem->axis }}
                                    </div>
                                    @if($problem->manifestations)
                                        <div class="small text-secondary" style="font-size: 0.75rem;">
                                            <strong>Manifestations :</strong> {{ Str::limit($problem->manifestations, 100) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @forelse($problem->causes as $cause)
                                        @php
                                            $cLevelClass = match($cause->confirmation_level) {
                                                'confirmed' => 'bg-success text-white',
                                                'partially_supported' => 'bg-info text-white',
                                                default => 'bg-light text-dark border',
                                            };
                                        @endphp
                                        <div class="p-1 mb-1 border rounded bg-light small">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <strong class="text-truncate" style="max-width: 180px;">{{ $cause->cause }}</strong>
                                                <span class="badge {{ $cLevelClass }}" style="font-size: 0.65rem;">
                                                    {{ $cause->confirmation_level === 'confirmed' ? 'Confirmée' : ($cause->confirmation_level === 'partially_supported' ? 'Partielle' : 'Hypothèse') }}
                                                </span>
                                            </div>
                                            <div class="text-muted d-flex align-items-center gap-1 mt-1" style="font-size: 0.7rem;">
                                                <span>{{ ucfirst($cause->nature) }}</span>
                                                &bull;
                                                <form action="{{ route('missions.problem-grid.causes.level.update', [$mission, $cause]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="confirmation_level" value="{{ $cause->confirmation_level === 'confirmed' ? 'hypothesis' : ($cause->confirmation_level === 'partially_supported' ? 'confirmed' : 'partially_supported') }}">
                                                    <button type="submit" class="btn btn-link p-0 text-primary" style="font-size: 0.7rem;" title="Changer le niveau de confirmation">
                                                        Évoluer
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @empty
                                        <span class="small text-muted fst-italic">Aucune cause tracée</span>
                                    @endforelse
                                    <button type="button" class="btn btn-link btn-sm p-0 text-primary small mt-1" data-bs-toggle="modal" data-bs-target="#modalAddCause_{{ $problem->id }}">
                                        + Ajouter une cause
                                    </button>
                                </td>
                                <td>
                                    @forelse($problem->consequences as $cons)
                                        @php
                                            $impClass = match($cons->importance) {
                                                'critical' => 'bg-danger text-white',
                                                'high' => 'bg-warning text-dark',
                                                'minor' => 'bg-light text-muted border',
                                                default => 'bg-secondary text-white',
                                            };
                                        @endphp
                                        <div class="p-1 mb-1 border rounded bg-white small">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="text-truncate" style="max-width: 150px;">{{ $cons->consequence }}</span>
                                                <span class="badge {{ $impClass }}" style="font-size: 0.65rem;">
                                                    {{ $cons->importance }}
                                                </span>
                                            </div>
                                        </div>
                                    @empty
                                        <span class="small text-muted fst-italic">Aucune conséquence tracée</span>
                                    @endforelse
                                    <button type="button" class="btn btn-link btn-sm p-0 text-secondary small mt-1" data-bs-toggle="modal" data-bs-target="#modalAddConsequence_{{ $problem->id }}">
                                        + Ajouter conséquence
                                    </button>
                                </td>
                                <td>
                                    @php
                                        $sBadgeClass = match($problem->status) {
                                            'validated' => 'bg-success',
                                            'structured' => 'bg-primary',
                                            'in_analysis' => 'bg-warning text-dark',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $sBadgeClass }}">
                                        {{ str_replace('_', ' ', ucfirst($problem->status)) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAssessPriority_{{ $problem->id }}" title="Évaluer la priorité">
                                            <x-icon name="sliders" size="14" />
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditProblem_{{ $problem->id }}" title="Modifier la fiche">
                                            <x-icon name="pen" size="14" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Aucun problème structuré. Cliquez sur <strong>Synchroniser D360</strong> ou <strong>Nouveau problème</strong>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards Pattern (< 768px) selon les normes d'ergonomie mobile --}}
            <div class="d-md-none p-3 mobile-card-list">
                @forelse($prioritizedProblems as $problem)
                    @php
                        $priority = $problem->priorityAssessment?->priority_level ?? 'P3';
                        $pBadgeClass = match($priority) {
                            'P1' => 'bg-danger text-white',
                            'P2' => 'bg-warning text-dark',
                            'P3' => 'bg-primary text-white',
                            default => 'bg-secondary text-white',
                        };
                    @endphp
                    <div class="card border shadow-sm mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <span class="badge {{ $pBadgeClass }} font-monospace">{{ $problem->reference }} &bull; {{ $priority }}</span>
                                    <h3 class="h6 fw-bold mb-0 mt-1 text-dark">{{ $problem->title }}</h3>
                                </div>
                                <span class="badge bg-secondary">
                                    {{ str_replace('_', ' ', $problem->status) }}
                                </span>
                            </div>

                            <div class="text-muted small mb-2">
                                <span class="badge bg-light text-dark border">{{ $problem->domain }}</span> &bull; {{ $problem->axis }}
                            </div>

                            <div class="row g-2 mb-3 text-center">
                                <div class="col-6">
                                    <div class="border rounded p-1 small">
                                        <span class="text-muted">Causes :</span> <strong>{{ $problem->causes->count() }}</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-1 small">
                                        <span class="text-muted">Impacts :</span> <strong>{{ $problem->consequences->count() }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary btn-sm py-2" data-bs-toggle="modal" data-bs-target="#modalAssessPriority_{{ $problem->id }}">
                                    Hiérarchiser (Priorité {{ $priority }})
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#modalAddCause_{{ $problem->id }}">
                                        + Cause
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#modalAddConsequence_{{ $problem->id }}">
                                        + Impact
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditProblem_{{ $problem->id }}">
                                        <x-icon name="pen" size="14" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        Aucun problème structuré.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Modale : Nouveau Problème --}}
    <div class="modal fade" id="modalAddProblem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('missions.problem-grid.problems.store', $mission) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Qualifier un nouveau problème</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        @if($initialData['d360']['difficulties']->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Rattacher à une difficulté issue du D360 (Optionnel)</label>
                                <select name="diagnostic_finding_id" class="form-select">
                                    <option value="">Création manuelle indépendante</option>
                                    @foreach($initialData['d360']['difficulties'] as $diff)
                                        <option value="{{ $diff->id }}">
                                            [{{ $diff->assessment?->domain }}] {{ $diff->title }} ({{ $diff->severity_or_importance }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Intitulé synthétique du problème</label>
                            <input type="text" name="title" class="form-control" placeholder="Formulez précisément le dysfonctionnement constaté..." required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Grand Domaine</label>
                                <input type="text" name="domain" class="form-control" placeholder="Ex: Finance & Rentabilité, Opérations..." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Axe d'analyse</label>
                                <input type="text" name="axis" class="form-control" placeholder="Ex: Calcul du prix de revient, Gestion des stocks..." required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description détaillée</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Explication contextuelle et mécanisme du problème..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Manifestations observables sur le terrain</label>
                            <textarea name="manifestations" rows="2" class="form-control" placeholder="Signaux concrets : retards, réclamations, rebuts, contestations..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le problème</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modale : Lier deux Problèmes --}}
    <div class="modal fade" id="modalAddRelation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('missions.problem-grid.relations.store', $mission) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Établir une relation causale entre deux problèmes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Problème source</label>
                                <select name="source_problem_id" class="form-select" required>
                                    <option value="">Sélectionner</option>
                                    @foreach($prioritizedProblems as $p)
                                        <option value="{{ $p->id }}">{{ $p->reference }} &bull; {{ Str::limit($p->title, 40) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Problème cible (associé)</label>
                                <select name="target_problem_id" class="form-select" required>
                                    <option value="">Sélectionner</option>
                                    @foreach($prioritizedProblems as $p)
                                        <option value="{{ $p->id }}">{{ $p->reference }} &bull; {{ Str::limit($p->title, 40) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nature du lien relationnel</label>
                            <select name="relation_type" class="form-select" required>
                                <option value="contributes_to">Contribue à &bull; Facteur aggravant direct</option>
                                <option value="causes">Provoque / Engendre &bull; Relation causale directe</option>
                                <option value="aggravates">Aggrave &bull; Amplifie la criticité</option>
                                <option value="depends_on">Dépend de &bull; Dépendance structurelle</option>
                                <option value="shares_common_cause_with">Partage une cause commune avec</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Justification & Explication du mécanisme de liaison</label>
                            <textarea name="explanation" rows="3" class="form-control" placeholder="Expliquez comment le premier problème influence ou déclenche le second..."></textarea>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_hypothetical" value="1" id="is_hypothetical_relation">
                            <label class="form-check-label small" for="is_hypothetical_relation">
                                Relation hypothétique nécessitant une confirmation ultérieure
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer la relation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modales dynamiques par Problème --}}
    @foreach($prioritizedProblems as $problem)
        {{-- Modale Édition Problème --}}
        <div class="modal fade" id="modalEditProblem_{{ $problem->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.problem-grid.problems.update', [$mission, $problem]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Modifier le problème : {{ $problem->reference }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Statut de qualification</label>
                                    <select name="status" class="form-select">
                                        <option value="to_examine" {{ $problem->status === 'to_examine' ? 'selected' : '' }}>À examiner</option>
                                        <option value="in_analysis" {{ $problem->status === 'in_analysis' ? 'selected' : '' }}>En cours d'analyse</option>
                                        <option value="structured" {{ $problem->status === 'structured' ? 'selected' : '' }}>Structuré</option>
                                        <option value="validated" {{ $problem->status === 'validated' ? 'selected' : '' }}>Validé par le chef de mission</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Grand Domaine</label>
                                    <input type="text" name="domain" class="form-control" value="{{ $problem->domain }}" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Intitulé</label>
                                <input type="text" name="title" class="form-control" value="{{ $problem->title }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" rows="3" class="form-control">{{ $problem->description }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Manifestations</label>
                                <textarea name="manifestations" rows="2" class="form-control">{{ $problem->manifestations }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Mettre à jour</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modale Ajouter Cause --}}
        <div class="modal fade" id="modalAddCause_{{ $problem->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.problem-grid.problems.causes.store', [$mission, $problem]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <span class="badge bg-secondary mb-1">{{ $problem->reference }}</span>
                                <h5 class="modal-title fw-bold">Ajouter une cause identifiée</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nature de la cause</label>
                                    <select name="nature" class="form-select" required>
                                        <option value="organizational">Organisationnelle &bull; Processus, circuits d'information</option>
                                        <option value="human">Humaine &bull; Compétences, charge, communication</option>
                                        <option value="technical">Technique &bull; Outils, machines, systèmes d'info</option>
                                        <option value="financial">Financière &bull; Trésorerie, structure de coûts</option>
                                        <option value="strategic">Stratégique &bull; Vision, arbitrage de direction</option>
                                        <option value="other">Autre nature</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Niveau de confirmation</label>
                                    <select name="confirmation_level" class="form-select" required>
                                        <option value="hypothesis">Hypothèse &bull; Déclaratif non vérifié</option>
                                        <option value="partially_supported">Partiellement étayée &bull; Faisceau d'indices</option>
                                        <option value="confirmed">Confirmée &bull; Démontrée par des preuves solides</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Intitulé de la cause</label>
                                <input type="text" name="cause" class="form-control" placeholder="Ex: Absence de procédure d'arbitrage des priorités client..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Explication du mécanisme causal</label>
                                <textarea name="explanation" rows="3" class="form-control" placeholder="Comment cette cause contribue-t-elle concrètement au problème..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Observations complémentaires</label>
                                <input type="text" name="comments" class="form-control" placeholder="Piste d'investigation ou réserves...">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer la cause</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modale Ajouter Conséquence --}}
        <div class="modal fade" id="modalAddConsequence_{{ $problem->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.problem-grid.problems.consequences.store', [$mission, $problem]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <span class="badge bg-secondary mb-1">{{ $problem->reference }}</span>
                                <h5 class="modal-title fw-bold">Ajouter une conséquence / un impact</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Niveau d'importance</label>
                                    <select name="importance" class="form-select" required>
                                        <option value="minor">Mineure</option>
                                        <option value="medium" selected>Moyenne</option>
                                        <option value="high">Élevée</option>
                                        <option value="critical">Critique &bull; Menace immédiate</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Domaine d'impact</label>
                                    <input type="text" name="domain" class="form-control" value="{{ $problem->domain }}" placeholder="Domaine où l'effet se produit">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Conséquence constatée ou redoutée</label>
                                <input type="text" name="consequence" class="form-control" placeholder="Ex: Pénalités de retard et détérioration du taux de service..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Description détaillée de l'impact</label>
                                <textarea name="description" rows="3" class="form-control" placeholder="Chiffrage estimé ou impact opérationnel/stratégique..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer la conséquence</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modale Évaluation de la Priorité --}}
        <div class="modal fade" id="modalAssessPriority_{{ $problem->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.problem-grid.problems.priority', [$mission, $problem]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <span class="badge bg-secondary mb-1">{{ $problem->reference }}</span>
                                <h5 class="modal-title fw-bold">Hiérarchisation et évaluation du problème</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Niveau de Priorité global</label>
                                    <select name="priority_level" class="form-select" required>
                                        <option value="P1" {{ ($problem->priorityAssessment?->priority_level === 'P1') ? 'selected' : '' }}>P1 &bull; Traitement immédiat prioritaire</option>
                                        <option value="P2" {{ ($problem->priorityAssessment?->priority_level === 'P2') ? 'selected' : '' }}>P2 &bull; Important / Court terme</option>
                                        <option value="P3" {{ ($problem->priorityAssessment?->priority_level === 'P3' || !$problem->priorityAssessment) ? 'selected' : '' }}>P3 &bull; Moyen terme / Amélioration continue</option>
                                        <option value="P4" {{ ($problem->priorityAssessment?->priority_level === 'P4') ? 'selected' : '' }}>P4 &bull; Secondaire</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Gravité intrinsèque</label>
                                    <select name="severity" class="form-select" required>
                                        <option value="minor" {{ ($problem->priorityAssessment?->severity === 'minor') ? 'selected' : '' }}>Mineure</option>
                                        <option value="moderate" {{ ($problem->priorityAssessment?->severity === 'moderate' || !$problem->priorityAssessment) ? 'selected' : '' }}>Modérée</option>
                                        <option value="major" {{ ($problem->priorityAssessment?->severity === 'major') ? 'selected' : '' }}>Majeure</option>
                                        <option value="critical" {{ ($problem->priorityAssessment?->severity === 'critical') ? 'selected' : '' }}>Critique</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Fréquence d'apparition</label>
                                    <select name="frequency" class="form-select">
                                        <option value="isolated" {{ ($problem->priorityAssessment?->frequency === 'isolated') ? 'selected' : '' }}>Isolée</option>
                                        <option value="occasional" {{ ($problem->priorityAssessment?->frequency === 'occasional' || !$problem->priorityAssessment) ? 'selected' : '' }}>Occasionnelle</option>
                                        <option value="frequent" {{ ($problem->priorityAssessment?->frequency === 'frequent') ? 'selected' : '' }}>Fréquente</option>
                                        <option value="systemic" {{ ($problem->priorityAssessment?->frequency === 'systemic') ? 'selected' : '' }}>Systémique</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Étendue dans l'entreprise</label>
                                    <select name="scope_extent" class="form-select">
                                        <option value="localized" {{ ($problem->priorityAssessment?->scope_extent === 'localized') ? 'selected' : '' }}>Localisée</option>
                                        <option value="departmental" {{ ($problem->priorityAssessment?->scope_extent === 'departmental' || !$problem->priorityAssessment) ? 'selected' : '' }}>Départementale</option>
                                        <option value="transversal" {{ ($problem->priorityAssessment?->scope_extent === 'transversal') ? 'selected' : '' }}>Transversale</option>
                                        <option value="company_wide" {{ ($problem->priorityAssessment?->scope_extent === 'company_wide') ? 'selected' : '' }}>Toute l'entreprise</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Urgence d'intervention</label>
                                    <select name="urgency" class="form-select">
                                        <option value="low" {{ ($problem->priorityAssessment?->urgency === 'low') ? 'selected' : '' }}>Faible</option>
                                        <option value="medium" {{ ($problem->priorityAssessment?->urgency === 'medium' || !$problem->priorityAssessment) ? 'selected' : '' }}>Moyenne</option>
                                        <option value="high" {{ ($problem->priorityAssessment?->urgency === 'high') ? 'selected' : '' }}>Haute</option>
                                        <option value="immediate" {{ ($problem->priorityAssessment?->urgency === 'immediate') ? 'selected' : '' }}>Immédiate</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Impact sur les objectifs stratégiques de la mission</label>
                                <select name="strategic_impact" class="form-select">
                                    <option value="low" {{ ($problem->priorityAssessment?->strategic_impact === 'low') ? 'selected' : '' }}>Faible</option>
                                    <option value="medium" {{ ($problem->priorityAssessment?->strategic_impact === 'medium' || !$problem->priorityAssessment) ? 'selected' : '' }}>Moyen</option>
                                    <option value="high" {{ ($problem->priorityAssessment?->strategic_impact === 'high') ? 'selected' : '' }}>Élevé</option>
                                    <option value="vital" {{ ($problem->priorityAssessment?->strategic_impact === 'vital') ? 'selected' : '' }}>Vital &bull; Remet en cause le modèle économique</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Justification & Motivation de l'arbitrage</label>
                                <textarea name="rationale" rows="3" class="form-control" placeholder="Arguments justifiant la position de ce problème dans la hiérarchie finale...">{{ $problem->priorityAssessment?->rationale }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Valider la hiérarchisation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
