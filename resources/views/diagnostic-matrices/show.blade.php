@extends('layouts.app')

@section('title', '360° Diagnostic Matrix™ - ' . $mission->title)

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">DIAGNOSTIC 360° &bull; OUTIL 05</div>
            <h1 class="page-title mb-1">360° Diagnostic Matrix™<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                Évaluation méthodique des axes, confrontation des preuves et consolidation des constats &bull; Mission <strong>{{ $mission->reference ?? $mission->title }}</strong>
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <form action="{{ route('missions.diagnostic-matrix.prepare', $mission) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Synchroniser les axes depuis le Diagnostic Scope Canvas">
                    <x-icon name="refresh-cw" size="14" /> Synchroniser avec le DSC
                </button>
            </form>

            <form action="{{ route('missions.diagnostic-matrix.snapshot', $mission) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <x-icon name="check-circle" size="14" /> Consolider l'instantané de sortie
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Bandeau métrique d'avancement du diagnostic 360° --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                <div>
                    <h2 class="h6 fw-bold mb-0">Progression de l'évaluation du diagnostic</h2>
                    <div class="small text-muted">
                        {{ $assessments->whereIn('status', ['assessed', 'validated'])->count() }} sur {{ $assessments->count() }} axe(s) évalué(s) ou validé(s)
                    </div>
                </div>
                <div class="fw-bold fs-5 text-primary">
                    {{ $matrix->evaluationProgressPercentage() }}%
                </div>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $matrix->evaluationProgressPercentage() }}%;" aria-valuenow="{{ $matrix->evaluationProgressPercentage() }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <div class="row g-3 mt-3 pt-3 border-top">
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Axes retenus</div>
                    <div class="fw-bold fs-5 text-dark">{{ $assessments->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Points forts</div>
                    <div class="fw-bold fs-5 text-success">{{ $matrix->findings()->where('finding_type', 'strength')->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Difficultés / Risques</div>
                    <div class="fw-bold fs-5 text-danger">{{ $matrix->findings()->where('finding_type', 'difficulty')->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Hypothèses</div>
                    <div class="fw-bold fs-5 text-info">{{ $matrix->findings()->where('finding_type', 'hypothesis')->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Preuves associées</div>
                    <div class="fw-bold fs-5 text-primary">{{ $matrix->evidences()->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Lacunes détectées</div>
                    <div class="fw-bold fs-5 text-warning">{{ count($gaps) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerte sur les lacunes d'informations ou fragilités de preuves si existantes --}}
    @if(count($gaps) > 0)
        <div class="card border-warning border-start-warning border-start-4 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2 text-warning-emphasis fw-bold">
                    <x-icon name="alert-triangle" size="18" /> Points de vigilance & Lacunes d'information identifiées ({{ count($gaps) }})
                </div>
                <div class="small text-muted mb-3">
                    Certains axes d'évaluation ne disposent pas d'éléments de preuve documentaires (DIR) ou d'entretiens (SIG) suffisants :
                </div>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                    @foreach($gaps as $gap)
                        <li class="d-flex align-items-center justify-content-between p-2 bg-light rounded small">
                            <div>
                                <span class="badge bg-secondary me-2">{{ $gap['domain'] }}</span>
                                <strong class="text-dark">{{ $gap['axis'] }}</strong> : {{ $gap['message'] }}
                            </div>
                            <span class="badge {{ $gap['severity'] === 'high' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                {{ $gap['severity'] === 'high' ? 'Priorité haute' : 'À corroborer' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Affichage structuré des évaluations par Domaine --}}
    @php
        $groupedAssessments = $assessments->groupBy('domain');
    @endphp

    @forelse($groupedAssessments as $domainName => $domainItems)
        @php
            $domainSummary = $domainSummaries->get($domainName);
        @endphp
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-light py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h2 class="h5 mb-0 fw-bold text-dark">{{ $domainName }}</h2>
                    <span class="badge bg-primary">{{ $domainItems->count() }} axe(s)</span>
                    @if($domainSummary && $domainSummary->overall_rating)
                        <span class="badge bg-dark">{{ ucfirst($domainSummary->overall_rating) }}</span>
                    @endif
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDomainSummary_{{ md5($domainName) }}">
                    <x-icon name="pen" size="14" /> Synthèse Domaine
                </button>
            </div>

            @if($domainSummary && ($domainSummary->summary_text || $domainSummary->key_takeaways))
                <div class="p-3 bg-white border-bottom">
                    @if($domainSummary->summary_text)
                        <p class="text-secondary small mb-1"><strong>Appréciation globale :</strong> {{ $domainSummary->summary_text }}</p>
                    @endif
                    @if($domainSummary->key_takeaways)
                        <p class="text-muted small mb-0"><strong>Enseignements clés :</strong> {{ $domainSummary->key_takeaways }}</p>
                    @endif
                </div>
            @endif

            <div class="card-body p-0">
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%;">Axe d'évaluation</th>
                                <th style="width: 30%;">Situation observée & Analyse</th>
                                <th style="width: 20%;">Preuves & Sources</th>
                                <th style="width: 15%;">Points clés</th>
                                <th style="width: 10%;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($domainItems as $assessment)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $assessment->axis }}</div>
                                        @if($assessment->subaxis)
                                            <div class="small text-muted">{{ $assessment->subaxis }}</div>
                                        @endif
                                        <div class="mt-2 d-flex flex-wrap gap-1">
                                            <span class="badge {{ $assessment->status === 'assessed' || $assessment->status === 'validated' ? 'bg-success' : ($assessment->status === 'in_progress' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                                {{ ucfirst($assessment->status) }}
                                            </span>
                                            @if($assessment->rating_level)
                                                <span class="badge bg-outline-dark border text-dark">{{ ucfirst($assessment->rating_level) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($assessment->observed_situation)
                                            <div class="small text-dark mb-1">
                                                <strong>Constat :</strong> {{ Str::limit($assessment->observed_situation, 150) }}
                                            </div>
                                        @else
                                            <div class="small text-muted fst-italic">Situation non documentée</div>
                                        @endif

                                        @if($assessment->assessment_text)
                                            <div class="small text-secondary">
                                                <strong>Analyse :</strong> {{ Str::limit($assessment->assessment_text, 150) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @forelse($assessment->evidences as $evidence)
                                            <div class="small mb-1 p-1 bg-light rounded border">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <strong class="text-truncate" style="max-width: 130px;">{{ $evidence->title }}</strong>
                                                    <span class="badge {{ $evidence->assessed_reliability === 'solid' ? 'bg-success' : ($evidence->assessed_reliability === 'weak' ? 'bg-danger' : 'bg-warning text-dark') }}" style="font-size: 0.65rem;">
                                                        {{ $evidence->assessed_reliability }}
                                                    </span>
                                                </div>
                                                @if($evidence->content_summary)
                                                    <div class="text-muted text-truncate" style="font-size: 0.75rem;">{{ $evidence->content_summary }}</div>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="small text-muted fst-italic">Aucune preuve rattachée</span>
                                        @endforelse
                                        <button type="button" class="btn btn-link btn-sm p-0 text-primary small mt-1" data-bs-toggle="modal" data-bs-target="#modalEvidence_{{ $assessment->id }}">
                                            + Lier une preuve
                                        </button>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            @foreach($assessment->strengths as $strength)
                                                <div class="small text-success text-truncate" title="{{ $strength->title }}">
                                                    &bull; <strong>{{ $strength->title }}</strong>
                                                </div>
                                            @endforeach
                                            @foreach($assessment->difficulties as $difficulty)
                                                <div class="small text-danger text-truncate" title="{{ $difficulty->title }}">
                                                    &bull; <strong>{{ $difficulty->title }}</strong>
                                                </div>
                                            @endforeach
                                            @foreach($assessment->hypotheses as $hypothesis)
                                                <div class="small text-info text-truncate" title="{{ $hypothesis->title }}">
                                                    &bull; <em>{{ $hypothesis->title }}</em>
                                                </div>
                                            @endforeach
                                        </div>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary small mt-1" data-bs-toggle="modal" data-bs-target="#modalFinding_{{ $assessment->id }}">
                                            + Ajouter constat
                                        </button>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAssessment_{{ $assessment->id }}">
                                            <x-icon name="pen" size="14" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards Pattern (< 768px) selon les normes d'ergonomie mobile --}}
                <div class="d-md-none p-3 mobile-card-list">
                    @foreach($domainItems as $assessment)
                        <div class="card border shadow-sm mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <h3 class="h6 fw-bold mb-0 text-dark">{{ $assessment->axis }}</h3>
                                        @if($assessment->subaxis)
                                            <div class="small text-muted">{{ $assessment->subaxis }}</div>
                                        @endif
                                    </div>
                                    <span class="badge {{ $assessment->status === 'assessed' || $assessment->status === 'validated' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ ucfirst($assessment->status) }}
                                    </span>
                                </div>

                                <div class="bg-light p-2 rounded mb-2 small">
                                    <div class="text-muted">Situation :</div>
                                    <div class="text-dark">{{ $assessment->observed_situation ?: 'Non renseignée' }}</div>
                                </div>

                                <div class="row g-2 mb-3 text-center">
                                    <div class="col-6">
                                        <div class="border rounded p-1 small">
                                            <span class="text-muted">Preuves :</span> <strong>{{ $assessment->evidences->count() }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-1 small">
                                            <span class="text-muted">Constats :</span> <strong>{{ $assessment->findings->count() }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary btn-sm py-2" data-bs-toggle="modal" data-bs-target="#modalAssessment_{{ $assessment->id }}">
                                        Évaluer l'axe
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#modalEvidence_{{ $assessment->id }}">
                                            + Preuve
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#modalFinding_{{ $assessment->id }}">
                                            + Constat
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Modale Synthèse de Domaine --}}
        <div class="modal fade" id="modalDomainSummary_{{ md5($domainName) }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.diagnostic-matrix.consolidate-domain', $mission) }}" method="POST">
                        @csrf
                        <input type="hidden" name="domain" value="{{ $domainName }}">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Synthèse du domaine : {{ $domainName }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="overall_rating_{{ md5($domainName) }}" class="form-label fw-semibold">Appréciation globale du domaine</label>
                                <select name="overall_rating" id="overall_rating_{{ md5($domainName) }}" class="form-select">
                                    <option value="">Sélectionner une appréciation</option>
                                    <option value="exemplary" {{ ($domainSummary?->overall_rating === 'exemplary') ? 'selected' : '' }}>Exemplaire &bull; Maîtrise éprouvée et modèle de performance</option>
                                    <option value="satisfactory" {{ ($domainSummary?->overall_rating === 'satisfactory') ? 'selected' : '' }}>Satisfaisant &bull; Niveau solide avec marge d'optimisation</option>
                                    <option value="acceptable" {{ ($domainSummary?->overall_rating === 'acceptable') ? 'selected' : '' }}>Acceptable &bull; Conforme aux attentes de base mais vulnérable</option>
                                    <option value="vulnerable" {{ ($domainSummary?->overall_rating === 'vulnerable') ? 'selected' : '' }}>Vulnérable &bull; Faiblesses structurelles nécessitant un plan d'action</option>
                                    <option value="critical" {{ ($domainSummary?->overall_rating === 'critical') ? 'selected' : '' }}>Critique &bull; Risque immédiat compromettant la mission</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="summary_text_{{ md5($domainName) }}" class="form-label fw-semibold">Synthèse diagnostique du domaine</label>
                                <textarea name="summary_text" id="summary_text_{{ md5($domainName) }}" rows="4" class="form-control" placeholder="Rédigez la conclusion synthétique sur ce domaine...">{{ $domainSummary?->summary_text }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="key_takeaways_{{ md5($domainName) }}" class="form-label fw-semibold">Enseignements clés & Recommandations préliminaires</label>
                                <textarea name="key_takeaways" id="key_takeaways_{{ md5($domainName) }}" rows="3" class="form-control" placeholder="Points cardinaux à retenir pour le rapport final...">{{ $domainSummary?->key_takeaways }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer la synthèse</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="card border shadow-sm p-5 text-center">
            <div class="text-muted mb-3">
                <x-icon name="layers" size="36" />
            </div>
            <h2 class="h5 fw-bold mb-2">Aucun axe de diagnostic instancié</h2>
            <p class="text-muted small mb-4">
                La matrice 360° s'alimente directement à partir des axes retenus dans le Diagnostic Scope Canvas (DSC).
            </p>
            <form action="{{ route('missions.diagnostic-matrix.prepare', $mission) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">
                    Initialiser la matrice à partir du DSC
                </button>
            </form>
        </div>
    @endforelse

    {{-- Modales pour chaque évaluation individuelle --}}
    @foreach($assessments as $assessment)
        {{-- Modale Évaluation de l'axe --}}
        <div class="modal fade" id="modalAssessment_{{ $assessment->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.diagnostic-matrix.assessments.update', [$mission, $assessment]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <div class="badge bg-secondary mb-1">{{ $assessment->domain }}</div>
                                <h5 class="modal-title fw-bold">Évaluation de l'axe : {{ $assessment->axis }}</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Statut d'évaluation</label>
                                    <select name="status" class="form-select">
                                        <option value="pending" {{ $assessment->status === 'pending' ? 'selected' : '' }}>En attente</option>
                                        <option value="in_progress" {{ $assessment->status === 'in_progress' ? 'selected' : '' }}>En cours d'investigation</option>
                                        <option value="assessed" {{ $assessment->status === 'assessed' ? 'selected' : '' }}>Évalué</option>
                                        <option value="validated" {{ $assessment->status === 'validated' ? 'selected' : '' }}>Validé par le chef de mission</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Niveau d'appréciation</label>
                                    <select name="rating_level" class="form-select">
                                        <option value="">Sélectionner</option>
                                        <option value="exemplary" {{ $assessment->rating_level === 'exemplary' ? 'selected' : '' }}>Exemplaire</option>
                                        <option value="satisfactory" {{ $assessment->rating_level === 'satisfactory' ? 'selected' : '' }}>Satisfaisant</option>
                                        <option value="acceptable" {{ $assessment->rating_level === 'acceptable' ? 'selected' : '' }}>Acceptable</option>
                                        <option value="vulnerable" {{ $assessment->rating_level === 'vulnerable' ? 'selected' : '' }}>Vulnérable</option>
                                        <option value="critical" {{ $assessment->rating_level === 'critical' ? 'selected' : '' }}>Critique</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Situation observée / Constat factuel</label>
                                <textarea name="observed_situation" rows="4" class="form-control" placeholder="Décrivez objectivement la réalité du terrain et le fonctionnement constaté...">{{ $assessment->observed_situation }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Appréciation & Analyse du cabinet</label>
                                <textarea name="assessment_text" rows="4" class="form-control" placeholder="Interprétation méthodologique, écarts par rapport aux bonnes pratiques ou au cadre convenu...">{{ $assessment->assessment_text }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer l'évaluation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modale Rapprochement de Preuve --}}
        <div class="modal fade" id="modalEvidence_{{ $assessment->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.diagnostic-matrix.assessments.evidence', [$mission, $assessment]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <div class="badge bg-secondary mb-1">Axe : {{ $assessment->axis }}</div>
                                <h5 class="modal-title fw-bold">Rattacher une preuve ou une source factuelle</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type de source</label>
                                    <select name="source_type" class="form-select" id="evidence_source_{{ $assessment->id }}">
                                        <option value="dir_item">Document collecté (DIR)</option>
                                        <option value="sig_contribution">Entretien / Contribution partie prenante (SIG)</option>
                                        <option value="direct_observation">Observation directe sur site</option>
                                        <option value="interview">Entretien complémentaire</option>
                                        <option value="other">Autre source factuelle</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Degré de pertinence</label>
                                    <select name="relevance_degree" class="form-select">
                                        <option value="direct">Preuve directe</option>
                                        <option value="indirect">Preuve indirecte / corrobative</option>
                                        <option value="contextual">Élément de contexte</option>
                                    </select>
                                </div>
                            </div>

                            @if($availableInformation->isNotEmpty())
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Sélectionner parmi les documents collectés (DIR)</label>
                                    <select name="received_item_id" class="form-select">
                                        <option value="">Aucun document DIR préexistant</option>
                                        @foreach($availableInformation as $item)
                                            <option value="{{ $item->id }}">
                                                {{ $item->title }} ({{ $item->item_type }}) &bull; Reçu le {{ $item->received_at?->format('d/m/Y') ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if($availableContributions->isNotEmpty())
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Sélectionner parmi les entretiens / recueils (SIG)</label>
                                    <select name="received_contribution_id" class="form-select">
                                        <option value="">Aucune contribution SIG préexistante</option>
                                        @foreach($availableContributions as $contribution)
                                            <option value="{{ $contribution->id }}">
                                                {{ $contribution->stakeholder?->name }} ({{ $contribution->modality }}) &bull; {{ Str::limit($contribution->summary, 60) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Intitulé de la preuve</label>
                                <input type="text" name="title" class="form-control" placeholder="Ex: Grand livre des comptes N-1, Entretien DAF du 12/03..." required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Fiabilité estimée de la preuve</label>
                                    <select name="assessed_reliability" class="form-select">
                                        <option value="solid">Solide &bull; Donnée certifiée ou recoupée</option>
                                        <option value="to_corroborate">À corroborer &bull; Déclaratif à confirmer</option>
                                        <option value="weak">Fragile &bull; Information parcellaire ou approximative</option>
                                        <option value="contradictory">Contradictoire &bull; Divergence avec d'autres sources</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Notes / Rapprochement</label>
                                    <input type="text" name="notes" class="form-control" placeholder="Comment cet élément éclaire l'axe">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Contenu synthétique / Extraits notables</label>
                                <textarea name="content_summary" rows="3" class="form-control" placeholder="Données clés ou citation pertinente tirée de cette preuve..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Limites ou réserves méthodologiques</label>
                                <textarea name="limits_or_reservations" rows="2" class="form-control" placeholder="Périmètre partiel, données non auditées, réserve d'usage..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Rattacher la preuve</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modale Consignation de Constat (Strength, Difficulty, Hypothesis) --}}
        <div class="modal fade" id="modalFinding_{{ $assessment->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.diagnostic-matrix.assessments.findings', [$mission, $assessment]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <div class="badge bg-secondary mb-1">Axe : {{ $assessment->axis }}</div>
                                <h5 class="modal-title fw-bold">Consigner un constat diagnostique</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type de constat</label>
                                    <select name="finding_type" class="form-select" required>
                                        <option value="strength">Point fort &bull; Atout ou compétence démontrée</option>
                                        <option value="difficulty" selected>Difficulté / Faiblesse &bull; Dysfonctionnement ou risque</option>
                                        <option value="hypothesis">Hypothèse d'investigation &bull; Point d'alerte à creuser</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Niveau de criticité / importance</label>
                                    <select name="severity_or_importance" class="form-select">
                                        <option value="minor">Mineure</option>
                                        <option value="medium" selected>Moyenne</option>
                                        <option value="major">Majeure</option>
                                        <option value="critical">Critique</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Titre du constat</label>
                                <input type="text" name="title" class="form-control" placeholder="Ex: Érosion de la marge brute sur la gamme B..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Description détaillée</label>
                                <textarea name="description" rows="3" class="form-control" placeholder="Explication factuelle du problème ou du point fort..."></textarea>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Causes probables identifiées</label>
                                    <textarea name="possible_causes" rows="3" class="form-control" placeholder="Origines racines ou facteurs déclencheurs..."></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Conséquences potentielles</label>
                                    <textarea name="potential_consequences" rows="3" class="form-control" placeholder="Impact sur l'organisation, la trésorerie, la rentabilité..."></textarea>
                                </div>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_validated" value="1" id="is_validated_{{ $assessment->id }}">
                                <label class="form-check-label small" for="is_validated_{{ $assessment->id }}">
                                    Constat confirmé et validé par l'équipe
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer le constat</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
