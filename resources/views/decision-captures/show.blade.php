@extends('layouts.app')

@section('title', 'Live Decision Capture™ - ' . $mission->title)

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">DIAGNOSTIC 360° &bull; OUTIL 08</div>
            <h1 class="page-title mb-1">Live Decision Capture™<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                Traçabilité des arbitrages, formalisation des décisions et pilotage des engagements d'action &bull; Mission <strong>{{ $mission->reference ?? $mission->title }}</strong>
                @if($clientName = ($mission->framing?->request?->client?->name ?? $initialData['mission']['client'] ?? null))
                    &bull; Client : <strong>{{ $clientName }}</strong>
                @endif
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddExchange">
                <x-icon name="calendar" size="14" /> Enregistrer un échange
            </button>

            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddDecision">
                <x-icon name="check-square" size="14" /> Acter une décision
            </button>

            <form action="{{ route('missions.decision-capture.snapshot', $mission) }}" method="POST" class="d-inline">
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

    {{-- Bandeau métrique de suivi des engagements issus des décisions --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                <div>
                    <h2 class="h6 fw-bold mb-0">Taux de réalisation des engagements décidés</h2>
                    <div class="small text-muted">
                        {{ $commitments->where('status', 'completed')->count() }} sur {{ $commitments->count() }} action(s) d'engagement finalisée(s)
                    </div>
                </div>
                <div class="fw-bold fs-5 text-primary">
                    {{ $capture->commitmentsCompletionPercentage() }}%
                </div>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $capture->commitmentsCompletionPercentage() }}%;" aria-valuenow="{{ $capture->commitmentsCompletionPercentage() }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <div class="row g-3 mt-3 pt-3 border-top">
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Échanges / Comités</div>
                    <div class="fw-bold fs-5 text-dark">{{ $exchanges->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Décisions actées</div>
                    <div class="fw-bold fs-5 text-success">{{ $decisions->where('status', 'taken')->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">À confirmer</div>
                    <div class="fw-bold fs-5 text-warning">{{ $decisions->whereIn('status', ['proposed', 'to_confirm'])->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Révisées / Annulées</div>
                    <div class="fw-bold fs-5 text-secondary">{{ $decisions->whereIn('status', ['revised', 'cancelled'])->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Actions en cours</div>
                    <div class="fw-bold fs-5 text-primary">{{ $commitments->where('status', 'in_progress')->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Actions bloquées</div>
                    <div class="fw-bold fs-5 text-danger">{{ $commitments->where('status', 'blocked')->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 1 : Registre des Décisions & Engagements résultants --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                <x-icon name="check-square" size="16" /> Registre officiel des décisions & arbitrages
            </h2>
            <span class="badge bg-secondary">{{ $decisions->count() }} décision(s)</span>
        </div>

        <div class="card-body p-0">
            {{-- Tableau Desktop (>= 768px) --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 12%;">Réf. & Nature</th>
                            <th style="width: 28%;">Décision & Motivation</th>
                            <th style="width: 18%;">Décideur & Contexte</th>
                            <th style="width: 25%;">Engagements associés</th>
                            <th style="width: 8%;">Statut</th>
                            <th style="width: 9%;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($decisions as $decision)
                            @php
                                $natureClass = match($decision->nature) {
                                    'validation' => 'bg-success text-white',
                                    'arbitrage' => 'bg-primary text-white',
                                    'accord' => 'bg-info text-white',
                                    'refusal' => 'bg-danger text-white',
                                    'postponement' => 'bg-warning text-dark',
                                    default => 'bg-secondary text-white',
                                };
                                $statusClass = match($decision->status) {
                                    'taken' => 'bg-success text-white',
                                    'proposed', 'to_confirm' => 'bg-warning text-dark',
                                    'revised' => 'bg-info text-white',
                                    'cancelled' => 'bg-danger text-white',
                                    default => 'bg-secondary text-white',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold font-monospace text-dark">{{ $decision->reference }}</div>
                                    <span class="badge {{ $natureClass }} mt-1">{{ ucfirst($decision->nature) }}</span>
                                    <div class="small text-muted mt-1" style="font-size: 0.7rem;">
                                        {{ $decision->decided_at?->format('d/m/Y') }}
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-dark d-block mb-1">{{ $decision->title }}</strong>
                                    <div class="small text-secondary mb-1" style="white-space: pre-line;">{{ Str::limit($decision->decision_text, 120) }}</div>
                                    @if($decision->justification)
                                        <div class="small text-muted fst-italic" style="font-size: 0.75rem;">
                                            <strong>Motif :</strong> {{ Str::limit($decision->justification, 80) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">
                                        {{ $decision->decision_maker_name ?: ucfirst($decision->decision_maker_type) }}
                                    </div>
                                    <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">
                                        {{ match($decision->decision_maker_type) {
                                            'client' => 'Décision Client',
                                            'cabinet' => 'Arbitrage Cabinet',
                                            'joint' => 'Accord Conjoint',
                                            default => 'Comité de Pilotage',
                                        } }}
                                    </span>
                                    @if($decision->exchange)
                                        <div class="text-muted mt-1 small" style="font-size: 0.7rem;">
                                            Via : {{ Str::limit($decision->exchange->title, 25) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @forelse($decision->commitments as $commitment)
                                        @php
                                            $cStatClass = match($commitment->status) {
                                                'completed' => 'bg-success text-white',
                                                'in_progress' => 'bg-primary text-white',
                                                'blocked' => 'bg-danger text-white',
                                                'cancelled' => 'bg-secondary text-white',
                                                default => 'bg-warning text-dark',
                                            };
                                        @endphp
                                        <div class="p-1 mb-1 border rounded bg-light small">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <strong class="text-truncate" style="max-width: 150px;">{{ $commitment->action }}</strong>
                                                <span class="badge {{ $cStatClass }}" style="font-size: 0.65rem;">
                                                    {{ $commitment->status }}
                                                </span>
                                            </div>
                                            <div class="text-muted d-flex align-items-center justify-content-between mt-1" style="font-size: 0.7rem;">
                                                <span>{{ $commitment->responsible_name ?: 'Non affecté' }}</span>
                                                @if($commitment->due_date)
                                                    <span>{{ $commitment->due_date->format('d/m') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <span class="small text-muted fst-italic">Aucun engagement requis</span>
                                    @endforelse
                                    <button type="button" class="btn btn-link btn-sm p-0 text-primary small mt-1" data-bs-toggle="modal" data-bs-target="#modalAddCommitment_{{ $decision->id }}">
                                        + Ajouter une action
                                    </button>
                                </td>
                                <td>
                                    <span class="badge {{ $statusClass }}">
                                        {{ ucfirst($decision->status) }}
                                    </span>
                                    @if($decision->previous_version_id)
                                        <div class="text-muted small mt-1" style="font-size: 0.65rem;">
                                            (Révision)
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if(in_array($decision->status, ['proposed', 'to_confirm']))
                                            <form action="{{ route('missions.decision-capture.decisions.confirm', [$mission, $decision]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Confirmer la décision">
                                                    <x-icon name="check" size="14" />
                                                </button>
                                            </form>
                                        @endif
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalReviseDecision_{{ $decision->id }}" title="Réviser la décision">
                                            <x-icon name="refresh-cw" size="14" />
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditDecision_{{ $decision->id }}" title="Modifier">
                                            <x-icon name="pen" size="14" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Aucune décision formellement enregistrée. Cliquez sur <strong>Acter une décision</strong>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards Pattern (< 768px) selon les normes d'ergonomie mobile --}}
            <div class="d-md-none p-3 mobile-card-list">
                @forelse($decisions as $decision)
                    @php
                        $natureClass = match($decision->nature) {
                            'validation' => 'bg-success text-white',
                            'arbitrage' => 'bg-primary text-white',
                            'accord' => 'bg-info text-white',
                            'refusal' => 'bg-danger text-white',
                            default => 'bg-secondary text-white',
                        };
                    @endphp
                    <div class="card border shadow-sm mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <span class="badge {{ $natureClass }}">{{ ucfirst($decision->nature) }}</span>
                                    <span class="badge bg-dark font-monospace">{{ $decision->reference }}</span>
                                    <h3 class="h6 fw-bold mb-0 mt-1 text-dark">{{ $decision->title }}</h3>
                                </div>
                                <span class="badge bg-light text-dark border">
                                    {{ ucfirst($decision->status) }}
                                </span>
                            </div>

                            <div class="bg-light p-2 rounded small mb-2 text-dark">
                                {{ Str::limit($decision->decision_text, 140) }}
                            </div>

                            <div class="row g-2 mb-3 text-center">
                                <div class="col-6">
                                    <div class="border rounded p-1 small">
                                        <span class="text-muted">Décideur :</span> <strong>{{ $decision->decision_maker_name ?: $decision->decision_maker_type }}</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-1 small">
                                        <span class="text-muted">Actions :</span> <strong>{{ $decision->commitments->count() }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddCommitment_{{ $decision->id }}">
                                    + Ajouter un engagement
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#modalReviseDecision_{{ $decision->id }}">
                                        Réviser
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#modalEditDecision_{{ $decision->id }}">
                                        <x-icon name="pen" size="14" /> Modifier
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        Aucune décision actée.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Section 2 : Chronologie des Échanges & Points discutés --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                <x-icon name="calendar" size="16" /> Historique des réunions, comités et échanges ({{ $exchanges->count() }})
            </h2>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                @forelse($exchanges as $exchange)
                    <div class="col-md-6">
                        <div class="card border h-100 shadow-xs">
                            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-secondary-subtle text-secondary me-1">{{ ucfirst($exchange->modality) }}</span>
                                    <strong class="text-dark">{{ $exchange->title }}</strong>
                                </div>
                                <small class="text-muted">{{ $exchange->exchanged_at->format('d/m/Y') }}</small>
                            </div>
                            <div class="card-body p-3">
                                @if($exchange->context)
                                    <p class="small text-muted mb-2">{{ $exchange->context }}</p>
                                @endif

                                <div class="fw-semibold small text-dark mb-1">Points discutés :</div>
                                <ul class="list-unstyled mb-3 small d-flex flex-column gap-1">
                                    @forelse($exchange->discussionPoints as $point)
                                        <li class="p-1 bg-light rounded border">
                                            <strong>{{ $point->subject }}</strong>
                                            @if($point->problem)
                                                <span class="badge bg-danger-subtle text-danger" style="font-size: 0.65rem;">[{{ $point->problem->reference }}]</span>
                                            @endif
                                            @if($point->description)
                                                <div class="text-muted" style="font-size: 0.75rem;">{{ $point->description }}</div>
                                            @endif
                                        </li>
                                    @empty
                                        <li class="text-muted fst-italic">Aucun point d'échange consigné</li>
                                    @endforelse
                                </ul>

                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <span class="small text-muted">{{ $exchange->decisions->count() }} décision(s) issue(s)</span>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddPoint_{{ $exchange->id }}">
                                        + Point discuté
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-4 text-muted">
                        Aucun échange consigné pour cette mission.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Modale : Nouvel Échange --}}
    <div class="modal fade" id="modalAddExchange" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('missions.decision-capture.exchanges.store', $mission) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Consigner un nouvel échange ou comité</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Objet de l'échange</label>
                                <input type="text" name="title" class="form-control" placeholder="Ex: Comité de direction hebdomadaire, Point d'arbitrage..." required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Date et heure</label>
                                <input type="datetime-local" name="exchanged_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Modalité</label>
                                <select name="modality" class="form-select" required>
                                    <option value="steering_committee">Comité de pilotage / Décision</option>
                                    <option value="meeting" selected>Réunion de travail</option>
                                    <option value="videoconference">Visioconférence</option>
                                    <option value="workshop">Atelier collaboratif</option>
                                    <option value="interview">Entretien bilatéral</option>
                                    <option value="phone_call">Échange téléphonique</option>
                                    <option value="other">Autre format</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Lieu / Salle / Plateforme</label>
                                <input type="text" name="location" class="form-control" placeholder="Ex: Siège client, Teams...">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contexte & Objectif de la réunion</label>
                            <textarea name="context" rows="3" class="form-control" placeholder="Objet des discussions et décisions attendues..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer l'échange</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modale : Acter une Décision --}}
    <div class="modal fade" id="modalAddDecision" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('missions.decision-capture.decisions.store', $mission) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Acter formellement une décision</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        @if($exchanges->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Rattacher à un échange / réunion</label>
                                <select name="exchange_id" class="form-select">
                                    <option value="">Décision indépendante hors comité</option>
                                    @foreach($exchanges as $ex)
                                        <option value="{{ $ex->id }}">{{ $ex->title }} ({{ $ex->exchanged_at->format('d/m/Y') }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Intitulé synthétique</label>
                                <input type="text" name="title" class="form-control" placeholder="Ex: Gel des embauches sur l'entrepôt Sud..." required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Date de prise d'effet</label>
                                <input type="date" name="decided_at" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Nature</label>
                                <select name="nature" class="form-select" required>
                                    <option value="arbitrage" selected>Arbitrage</option>
                                    <option value="validation">Validation formelle</option>
                                    <option value="accord">Accord de principe</option>
                                    <option value="orientation">Orientation stratégique</option>
                                    <option value="refusal">Refus motivé</option>
                                    <option value="postponement">Report de décision</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Autorité décisionnelle</label>
                                <select name="decision_maker_type" class="form-select" required>
                                    <option value="client" selected>Client (Direction)</option>
                                    <option value="steering_committee">Comité de pilotage</option>
                                    <option value="joint">Accord Conjoint</option>
                                    <option value="cabinet">Cabinet (Chef de mission)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Nom du décideur / Instance</label>
                                <input type="text" name="decision_maker_name" class="form-control" placeholder="Ex: M. Dupont (DG), Copil...">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Formulation précise de la décision</label>
                            <textarea name="decision_text" rows="3" class="form-control" placeholder="Texte exact et engagement acté par les parties..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Justification & Motivation de l'arbitrage</label>
                            <textarea name="justification" rows="2" class="form-control" placeholder="Pourquoi cette décision a été retenue plutôt qu'une autre..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer la décision</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modales dynamiques par Décision & par Échange --}}
    @foreach($decisions as $decision)
        {{-- Modale Ajouter Engagement --}}
        <div class="modal fade" id="modalAddCommitment_{{ $decision->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.decision-capture.decisions.commitments.store', [$mission, $decision]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <span class="badge bg-secondary mb-1">Décision {{ $decision->reference }}</span>
                                <h5 class="modal-title fw-bold">Ajouter un engagement d'action résultant</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Action attendue</label>
                                <input type="text" name="action" class="form-control" placeholder="Ex: Rédiger le nouveau protocole de déchargement..." required>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Responsable désigné</label>
                                    <input type="text" name="responsible_name" class="form-control" placeholder="Nom de l'interlocuteur ou collaborateur...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Échéance de réalisation</label>
                                    <input type="date" name="due_date" class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Livrable ou résultat attendu</label>
                                <textarea name="expected_outcome" rows="2" class="form-control" placeholder="Ce qui permettra de déclarer l'action achevée..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer l'action</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modale Réviser Décision --}}
        <div class="modal fade" id="modalReviseDecision_{{ $decision->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.decision-capture.decisions.revise', [$mission, $decision]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Réviser la décision : {{ $decision->reference }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info small mb-3">
                                La décision actuelle passera au statut <strong>Révisée</strong> et conservera son historique. Une nouvelle décision fille liée sera créée.
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Motif de la révision</label>
                                <input type="text" name="revision_reason" class="form-control" placeholder="Pourquoi cette décision est-elle modifiée..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nouvel intitulé</label>
                                <input type="text" name="title" class="form-control" value="{{ $decision->title }}" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nature</label>
                                    <select name="nature" class="form-select">
                                        <option value="arbitrage" {{ $decision->nature === 'arbitrage' ? 'selected' : '' }}>Arbitrage</option>
                                        <option value="validation" {{ $decision->nature === 'validation' ? 'selected' : '' }}>Validation</option>
                                        <option value="accord" {{ $decision->nature === 'accord' ? 'selected' : '' }}>Accord</option>
                                        <option value="orientation" {{ $decision->nature === 'orientation' ? 'selected' : '' }}>Orientation</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Autorité</label>
                                    <select name="decision_maker_type" class="form-select">
                                        <option value="client" {{ $decision->decision_maker_type === 'client' ? 'selected' : '' }}>Client</option>
                                        <option value="steering_committee" {{ $decision->decision_maker_type === 'steering_committee' ? 'selected' : '' }}>Comité</option>
                                        <option value="cabinet" {{ $decision->decision_maker_type === 'cabinet' ? 'selected' : '' }}>Cabinet</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nouveau texte de la décision</label>
                                <textarea name="decision_text" rows="3" class="form-control" required>{{ $decision->decision_text }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Créer la révision</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modale Édition Décision --}}
        <div class="modal fade" id="modalEditDecision_{{ $decision->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.decision-capture.decisions.update', [$mission, $decision]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Modifier la décision : {{ $decision->reference }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Intitulé</label>
                                    <input type="text" name="title" class="form-control" value="{{ $decision->title }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Statut</label>
                                    <select name="status" class="form-select">
                                        <option value="taken" {{ $decision->status === 'taken' ? 'selected' : '' }}>Prise / Actée</option>
                                        <option value="proposed" {{ $decision->status === 'proposed' ? 'selected' : '' }}>Proposée</option>
                                        <option value="to_confirm" {{ $decision->status === 'to_confirm' ? 'selected' : '' }}>À confirmer</option>
                                        <option value="revised" {{ $decision->status === 'revised' ? 'selected' : '' }}>Révisée</option>
                                        <option value="cancelled" {{ $decision->status === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Texte</label>
                                <textarea name="decision_text" rows="3" class="form-control" required>{{ $decision->decision_text }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Justification</label>
                                <input type="text" name="justification" class="form-control" value="{{ $decision->justification }}">
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
    @endforeach

    @foreach($exchanges as $exchange)
        {{-- Modale Ajouter Point Discuté dans Échange --}}
        <div class="modal fade" id="modalAddPoint_{{ $exchange->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.decision-capture.exchanges.points.store', [$mission, $exchange]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <span class="badge bg-secondary mb-1">Échange : {{ $exchange->title }}</span>
                                <h5 class="modal-title fw-bold">Ajouter un point discuté</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Sujet abordé</label>
                                <input type="text" name="subject" class="form-control" placeholder="Ex: Arbitrage sur les remises de la collection été..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Résumé des échanges et arguments</label>
                                <textarea name="description" rows="3" class="form-control" placeholder="Positions des parties, objections, consensus émergent..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer le point</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
