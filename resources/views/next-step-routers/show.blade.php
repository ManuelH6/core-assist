@extends('layouts.app')

@section('title', 'Next-Step Router™ - ' . $mission->title)

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">DIAGNOSTIC 360° &bull; OUTIL 10</div>
            <h1 class="page-title mb-1">Next-Step Router™<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                Passage à l'action : recommandations stratégiques, plan d'action opérationnel et feuille de route cadencée &bull; Mission <strong>{{ $mission->reference ?? $mission->title }}</strong>
                @if($clientName = ($mission->framing?->request?->client?->name ?? $initialData['mission']['client'] ?? null))
                    &bull; Client : <strong>{{ $clientName }}</strong>
                @endif
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <form action="{{ route('missions.next-step-router.prepare', $mission) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm" title="Formuler automatiquement les recommandations à partir des difficultés PSG et constats D360">
                    <x-icon name="zap" size="14" /> Proposer orientations
                </button>
            </form>

            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddRecommendation">
                <x-icon name="plus" size="14" /> Recommandation
            </button>

            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddAction">
                <x-icon name="plus-circle" size="14" /> Ajouter une action
            </button>

            <form action="{{ route('missions.next-step-router.snapshot', $mission) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <x-icon name="check-circle" size="14" /> Consolider l'audit trail
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Bandeau de pilotage & Métriques d'avancement --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold text-dark">Avancement de la mise en œuvre des actions</span>
                        <span class="badge {{ $router->completionPercentage() === 100 ? 'bg-success' : 'bg-primary' }} fs-6">
                            {{ $router->completionPercentage() }} %
                        </span>
                    </div>
                    <div class="progress mb-2" style="height: 10px;">
                        <div class="progress-bar {{ $router->completionPercentage() === 100 ? 'bg-success' : 'bg-primary' }}"
                             role="progressbar"
                             style="width: {{ $router->completionPercentage() }}%;"
                             aria-valuenow="{{ $router->completionPercentage() }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>
                    </div>
                    <div class="small text-muted">
                        {{ $actions->where('status', 'completed')->count() }} sur {{ $actions->count() }} action(s) menée(s) à terme.
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="row g-2 text-center">
                        <div class="col-3">
                            <div class="p-2 border rounded bg-light">
                                <div class="fs-5 fw-bold text-primary">{{ $recommendations->count() }}</div>
                                <div class="small text-muted">Recommandations</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 border rounded bg-light">
                                <div class="fs-5 fw-bold text-info">{{ $actions->where('status', 'in_progress')->count() }}</div>
                                <div class="small text-muted">En cours</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 border rounded bg-light">
                                <div class="fs-5 fw-bold text-success">{{ $actions->where('status', 'completed')->count() }}</div>
                                <div class="small text-muted">Réalisées</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 border rounded bg-light">
                                <div class="fs-5 fw-bold text-danger">{{ $actions->where('status', 'blocked')->count() }}</div>
                                <div class="small text-muted">Bloquées</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 1 : Recommandations Stratégiques --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold mb-0">1. Recommandations Stratégiques ({{ $recommendations->count() }})</h2>
        <span class="small text-muted">Orientations globales structurées à partir des constats et difficultés diagnostiquées</span>
    </div>

    @if($recommendations->isEmpty())
        <div class="card border border-dashed text-center p-4 mb-4">
            <p class="text-muted mb-2">Aucune recommandation formulée pour le moment.</p>
            <div>
                <form action="{{ route('missions.next-step-router.prepare', $mission) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <x-icon name="zap" size="14" /> Générer automatiquement à partir du diagnostic
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="row g-3 mb-4">
            @foreach($recommendations as $rec)
                <div class="col-md-6 col-lg-4">
                    <div class="card border h-100 shadow-sm">
                        <div class="card-body p-3 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-light text-dark border font-monospace">{{ $rec->reference }}</span>
                                <span class="badge {{ $rec->priority === 'critical' ? 'bg-danger' : ($rec->priority === 'high' ? 'bg-warning text-dark' : 'bg-info') }}">
                                    {{ ucfirst($rec->priority) }}
                                </span>
                            </div>

                            <h3 class="h6 fw-bold mb-1">{{ $rec->title }}</h3>
                            @if($rec->domain)
                                <div class="small text-muted mb-2">
                                    <x-icon name="tag" size="11" /> {{ $rec->domain }} @if($rec->axis) &bull; {{ $rec->axis }} @endif
                                </div>
                            @endif

                            @if($rec->description)
                                <p class="small text-muted mb-3 flex-grow-1">{{ $rec->description }}</p>
                            @endif

                            @if($rec->justification)
                                <div class="p-2 bg-light rounded small text-muted mb-3">
                                    <strong>Justification :</strong> {{ $rec->justification }}
                                </div>
                            @endif

                            <div class="pt-2 border-top d-flex justify-content-between align-items-center small text-muted">
                                <span>{{ $rec->actions->count() }} action(s) associée(s)</span>
                                <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($rec->status) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Section 2 : Registre des Actions Concrètes --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold mb-0">2. Registre des Actions Opérationnelles ({{ $actions->count() }})</h2>
        <span class="small text-muted">Déclinaison en unités de travail concrètes avec porteur, horizon et échéance</span>
    </div>

    <div class="card border shadow-sm mb-4">
        {{-- Desktop Table (>= 768px) --}}
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 100px;">Réf.</th>
                        <th>Action & Résultat cible</th>
                        <th style="width: 110px;">Priorité</th>
                        <th>Horizon</th>
                        <th>Porteur / Responsable</th>
                        <th>Échéance</th>
                        <th>Statut</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($actions as $action)
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">{{ $action->reference }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $action->title }}</div>
                                @if($action->expected_result)
                                    <div class="small text-muted text-truncate" style="max-width: 320px;">
                                        <strong>Cible :</strong> {{ $action->expected_result }}
                                    </div>
                                @endif
                                @if($action->problem_reference)
                                    <div class="small text-muted">
                                        <x-icon name="link" size="11" /> Traite : <span class="badge bg-light text-dark border font-monospace">{{ $action->problem_reference }}</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($action->priority === 'critical')
                                    <span class="badge bg-danger">Critique</span>
                                @elseif($action->priority === 'high')
                                    <span class="badge bg-warning text-dark">Élevée</span>
                                @elseif($action->priority === 'medium')
                                    <span class="badge bg-info">Moyenne</span>
                                @else
                                    <span class="badge bg-light text-muted border">Faible</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    @if($action->horizon === 'immediate') Immédiat @elseif($action->horizon === 'short_term') Court terme @elseif($action->horizon === 'medium_term') Moyen terme @else Long terme @endif
                                </span>
                            </td>
                            <td>
                                @if($action->responsible)
                                    <div class="small fw-semibold text-dark">{{ $action->responsible->name }}</div>
                                @elseif($action->responsible_name)
                                    <div class="small text-dark">{{ $action->responsible_name }}</div>
                                @else
                                    <span class="small text-muted font-italic">Non assigné</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $action->due_date ? $action->due_date->format('d/m/Y') : '-' }}
                            </td>
                            <td>
                                @if($action->status === 'completed')
                                    <span class="badge bg-success">Réalisée</span>
                                @elseif($action->status === 'in_progress')
                                    <span class="badge bg-info">En cours</span>
                                @elseif($action->status === 'blocked')
                                    <span class="badge bg-danger">Bloquée</span>
                                @elseif($action->status === 'validated')
                                    <span class="badge bg-primary">Validée</span>
                                @else
                                    <span class="badge bg-secondary">Proposée</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditAction-{{ $action->id }}" title="Modifier l'action">
                                    <x-icon name="pen" size="13" /> Modifier
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                Aucune action opérationnelle enregistrée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards (< 768px) --}}
        <div class="d-md-none p-3 mobile-card-list">
            @forelse($actions as $action)
                <div class="card border mb-3 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-light text-dark border font-monospace me-1">{{ $action->reference }}</span>
                            <span class="badge {{ $action->status === 'completed' ? 'bg-success' : ($action->status === 'in_progress' ? 'bg-info' : 'bg-secondary') }}">
                                {{ ucfirst($action->status) }}
                            </span>
                        </div>

                        <h3 class="h6 fw-bold mb-1">{{ $action->title }}</h3>
                        @if($action->expected_result)
                            <p class="small text-muted mb-2"><strong>Cible :</strong> {{ $action->expected_result }}</p>
                        @endif

                        <div class="row g-2 small text-muted mb-3">
                            <div class="col-6">
                                <strong>Priorité :</strong> {{ ucfirst($action->priority) }}
                            </div>
                            <div class="col-6">
                                <strong>Horizon :</strong> {{ ucfirst($action->horizon) }}
                            </div>
                            <div class="col-6">
                                <strong>Porteur :</strong> {{ $action->responsible_name ?: ($action->responsible?->name ?: 'Non assigné') }}
                            </div>
                            <div class="col-6">
                                <strong>Échéance :</strong> {{ $action->due_date ? $action->due_date->format('d/m/Y') : '-' }}
                            </div>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modalEditAction-{{ $action->id }}">
                            <x-icon name="pen" size="13" /> Modifier l'action
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted">
                    Aucune action opérationnelle enregistrée.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Section 3 : Feuille de Route Cadencée par Horizon (Vue dynamique sans table superflue) --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold mb-0">3. Feuille de Route Cadencée par Horizon Temporel</h2>
        <span class="small text-muted">Séquencement opérationnel des actions</span>
    </div>

    <div class="row g-3 mb-4">
        @foreach($roadmapByHorizon as $horizonKey => $phase)
            <div class="col-lg-6">
                <div class="card border h-100 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h3 class="h6 fw-bold mb-0">{{ $phase['label'] }}</h3>
                        <span class="badge bg-light text-dark border">{{ $phase['actions']->count() }} action(s)</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="list-group list-group-flush">
                            @forelse($phase['actions'] as $act)
                                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <div class="small">
                                        <span class="font-monospace text-primary fw-semibold">{{ $act->reference }}</span> &bull; {{ $act->title }}
                                        @if($act->responsible_name)
                                            <span class="text-muted">({{ $act->responsible_name }})</span>
                                        @endif
                                    </div>
                                    <span class="badge {{ $act->status === 'completed' ? 'bg-success' : 'bg-secondary' }} ms-2">
                                        {{ $act->status }}
                                    </span>
                                </div>
                            @empty
                                <div class="small text-muted py-2 text-center">Aucune action programmée sur cet horizon.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- MODALS --}}

{{-- Modal Ajout Recommandation --}}
<div class="modal fade" id="modalAddRecommendation" tabindex="-1" aria-labelledby="modalAddRecLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('missions.next-step-router.recommendations.store', $mission) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddRecLabel">Formuler une recommandation stratégique</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Intitulé de la recommandation</label>
                        <input type="text" name="title" class="form-control" placeholder="Ex: Réorganiser le pilotage de la supply chain et du cadencement..." required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Domaine d'intervention</label>
                            <input type="text" name="domain" class="form-control" placeholder="Ex: Opérations & Logistique...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Niveau de priorité</label>
                            <select name="priority" class="form-select" required>
                                <option value="critical">Critique (P1)</option>
                                <option value="high" selected>Élevée (P2)</option>
                                <option value="medium">Moyenne (P3)</option>
                                <option value="low">Faible (P4)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Justification & Motivation de l'orientation</label>
                        <textarea name="justification" rows="2" class="form-control" placeholder="Pourquoi cette orientation est-elle recommandée au regard du diagnostic..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description détaillée & Argumentaire</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Détail des orientations recommandées au client..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer la recommandation</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Ajout Action --}}
<div class="modal fade" id="modalAddAction" tabindex="-1" aria-labelledby="modalAddActionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('missions.next-step-router.actions.store', $mission) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddActionLabel">Ajouter une action concrète</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Intitulé de l'action</label>
                        <input type="text" name="title" class="form-control" placeholder="Ex: Définir les seuils d'alerte des stocks dormants..." required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Recommandation rattachée</label>
                            <select name="recommendation_id" class="form-select">
                                <option value="">-- Action autonome (sans recommandation) --</option>
                                @foreach($recommendations as $rec)
                                    <option value="{{ $rec->id }}">{{ $rec->reference }} &bull; {{ $rec->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Horizon temporel</label>
                            <select name="horizon" class="form-select" required>
                                <option value="immediate">Immédiat (0-30j)</option>
                                <option value="short_term" selected>Court terme (1-3 mois)</option>
                                <option value="medium_term">Moyen terme (3-6 mois)</option>
                                <option value="long_term">Long terme (> 6 mois)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Priorité</label>
                            <select name="priority" class="form-select" required>
                                <option value="critical">Critique</option>
                                <option value="high" selected>Élevée</option>
                                <option value="medium">Moyenne</option>
                                <option value="low">Faible</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Porteur / Responsable désigné</label>
                            <input type="text" name="responsible_name" class="form-control" placeholder="Ex: Directeur d'usine, DAF...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Échéance indicative</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Résultat concret attendu</label>
                        <textarea name="expected_result" rows="2" class="form-control" placeholder="Livrable ou jalon vérifiable marquant l'achèvement..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer l'action</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modales de modification d'action --}}
@foreach($actions as $action)
    <div class="modal fade" id="modalEditAction-{{ $action->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('missions.next-step-router.actions.update', [$mission, $action]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier l'action &bull; {{ $action->reference }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Intitulé de l'action</label>
                            <input type="text" name="title" class="form-control" value="{{ $action->title }}" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Statut d'avancement</label>
                                <select name="status" class="form-select" required>
                                    <option value="proposed" {{ $action->status === 'proposed' ? 'selected' : '' }}>Proposée</option>
                                    <option value="validated" {{ $action->status === 'validated' ? 'selected' : '' }}>Validée (acceptée)</option>
                                    <option value="planned" {{ $action->status === 'planned' ? 'selected' : '' }}>Planifiée</option>
                                    <option value="in_progress" {{ $action->status === 'in_progress' ? 'selected' : '' }}>En cours</option>
                                    <option value="completed" {{ $action->status === 'completed' ? 'selected' : '' }}>Réalisée</option>
                                    <option value="blocked" {{ $action->status === 'blocked' ? 'selected' : '' }}>Bloquée</option>
                                    <option value="abandoned" {{ $action->status === 'abandoned' ? 'selected' : '' }}>Abandonnée</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Porteur / Responsable</label>
                                <input type="text" name="responsible_name" class="form-control" value="{{ $action->responsible_name }}">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Priorité</label>
                                <select name="priority" class="form-select" required>
                                    <option value="critical" {{ $action->priority === 'critical' ? 'selected' : '' }}>Critique</option>
                                    <option value="high" {{ $action->priority === 'high' ? 'selected' : '' }}>Élevée</option>
                                    <option value="medium" {{ $action->priority === 'medium' ? 'selected' : '' }}>Moyenne</option>
                                    <option value="low" {{ $action->priority === 'low' ? 'selected' : '' }}>Faible</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Horizon</label>
                                <select name="horizon" class="form-select" required>
                                    <option value="immediate" {{ $action->horizon === 'immediate' ? 'selected' : '' }}>Immédiat (0-30j)</option>
                                    <option value="short_term" {{ $action->horizon === 'short_term' ? 'selected' : '' }}>Court terme (1-3 mois)</option>
                                    <option value="medium_term" {{ $action->horizon === 'medium_term' ? 'selected' : '' }}>Moyen terme (3-6 mois)</option>
                                    <option value="long_term" {{ $action->horizon === 'long_term' ? 'selected' : '' }}>Long terme (> 6 mois)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Échéance</label>
                                <input type="date" name="due_date" class="form-control" value="{{ $action->due_date ? $action->due_date->format('Y-m-d') : '' }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Résultat obtenu (si réalisée)</label>
                            <textarea name="actual_result" rows="2" class="form-control" placeholder="Résultat effectif constaté sur le terrain...">{{ $action->actual_result }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@endsection
