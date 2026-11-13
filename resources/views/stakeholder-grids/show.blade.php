@extends('layouts.app')

@section('title', 'Stakeholder Input Grid™ - ' . $mission->title)

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">DIAGNOSTIC 360° &bull; OUTIL 04</div>
            <h1 class="page-title mb-1">Stakeholder Input Grid™<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                Cartographie des parties prenantes, plan de mobilisation et recueil des contributions pour la mission <strong>{{ $mission->reference ?? $mission->title }}</strong>
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddStakeholder">
                <x-icon name="plus" size="14" /> Ajouter une partie prenante
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddExpectedContribution">
                <x-icon name="clipboard" size="14" /> Définir une contribution attendue
            </button>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalRecordContribution">
                <x-icon name="check" size="14" /> Consigner un recueil
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Bandeau d'avancement métrique de mobilisation --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                <div>
                    <h2 class="h6 fw-bold mb-0">Taux de mobilisation des parties prenantes</h2>
                    <div class="small text-muted">
                        {{ $progress['completed_count'] }} sur {{ $progress['total_expected'] }} contribution(s) attendue(s) recueillie(s)
                        &bull; {{ $progress['stakeholders_count'] }} interlocuteur(s) cartographié(s)
                    </div>
                </div>
                <div class="fw-bold fs-5 text-primary">
                    {{ $progress['mobilization_percentage'] }}%
                </div>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progress['mobilization_percentage'] }}%;" aria-valuenow="{{ $progress['mobilization_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <div class="row g-3 mt-3 pt-3 border-top">
                <div class="col-6 col-md-3">
                    <div class="text-muted small text-uppercase fw-semibold">Parties prenantes</div>
                    <div class="fw-bold fs-5 text-dark">{{ $progress['stakeholders_count'] }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small text-uppercase fw-semibold">Contributions attendues</div>
                    <div class="fw-bold fs-5 text-primary">{{ $progress['total_expected'] }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small text-uppercase fw-semibold">Recueils effectués</div>
                    <div class="fw-bold fs-5 text-success">{{ $progress['completed_count'] }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small text-uppercase fw-semibold">À mobiliser / Planifiées</div>
                    <div class="fw-bold fs-5 text-warning">{{ $progress['pending_count'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 1 : Répertoire des parties prenantes --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                <x-icon name="users" size="18" />
                Répertoire des parties prenantes ({{ $stakeholders->count() }})
            </h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">Partie prenante / Entité</th>
                            <th scope="col">Type</th>
                            <th scope="col">Fonction & Organisation</th>
                            <th scope="col">Rôle dans le diagnostic</th>
                            <th scope="col" class="pe-3">Contributions attendues</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stakeholders as $sh)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ $sh->name }}</div>
                                    @if($sh->contact_details)
                                        <div class="small text-muted">{{ $sh->contact_details }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ ucfirst($sh->stakeholder_type) }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-secondary small">{{ $sh->position ?? 'Position non précisée' }}</div>
                                    <div class="small text-muted">{{ $sh->organization ?? 'Organisation interne' }}</div>
                                </td>
                                <td>
                                    <div class="small text-dark">{{ $sh->role_in_diagnostic ?? 'Contributeur général' }}</div>
                                </td>
                                <td class="pe-3">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                        {{ $sh->expectedContributions->count() }} contribution(s)
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Aucune partie prenante identifiée pour le moment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Section 2 : Contributions attendues & Plan de mobilisation --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                <x-icon name="clipboard" size="18" />
                Plan de mobilisation des contributions ({{ $expectedContributions->count() }})
            </h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3" style="width: 25%;">Partie prenante & Domaine</th>
                            <th scope="col" style="width: 35%;">Contribution attendue & Modalité</th>
                            <th scope="col" style="width: 15%;">Responsable</th>
                            <th scope="col" style="width: 15%;">Statut</th>
                            <th scope="col" class="pe-3" style="width: 10%;">Priorité</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expectedContributions as $ec)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ $ec->stakeholder->name }}</div>
                                    <div class="small text-muted">{{ $ec->domain }} &bull; {{ $ec->axis }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $ec->expected_contribution }}</div>
                                    <div class="small text-muted mt-1">
                                        Modalité : <strong>{{ ucfirst($ec->modality) }}</strong>
                                        @if($ec->justification)
                                            &bull; <span class="text-secondary fst-italic">{{ $ec->justification }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <form action="{{ route('missions.stakeholder-grid.expected-contributions.assign', [$mission, $ec]) }}" method="POST">
                                        @csrf
                                        <select name="assigned_to" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">-- Non assigné --</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ $ec->assigned_to == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form action="{{ route('missions.stakeholder-grid.expected-contributions.status', [$mission, $ec]) }}" method="POST">
                                        @csrf
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="to_solicit" {{ $ec->status === 'to_solicit' ? 'selected' : '' }}>À solliciter</option>
                                            <option value="solicited" {{ $ec->status === 'solicited' ? 'selected' : '' }}>Sollicité</option>
                                            <option value="scheduled" {{ $ec->status === 'scheduled' ? 'selected' : '' }}>Planifié</option>
                                            <option value="completed" {{ $ec->status === 'completed' ? 'selected' : '' }}>Réalisé</option>
                                            <option value="unavailable" {{ $ec->status === 'unavailable' ? 'selected' : '' }}>Indisponible</option>
                                            <option value="cancelled" {{ $ec->status === 'cancelled' ? 'selected' : '' }}>Non retenu</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="pe-3">
                                    @if($ec->priority === 'critical')
                                        <span class="badge bg-danger">Critique</span>
                                    @elseif($ec->priority === 'high')
                                        <span class="badge bg-warning text-dark">Haute</span>
                                    @else
                                        <span class="badge bg-light text-dark border">Normale</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Aucune contribution attendue consigée.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Section 3 : Contributions effectivement recueillies --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                <x-icon name="check" size="18" />
                Dossier des contributions recueillies ({{ $receivedContributions->count() }})
            </h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3" style="width: 25%;">Partie prenante & Date</th>
                            <th scope="col" style="width: 45%;">Synthèse des déclarations & Constats</th>
                            <th scope="col" style="width: 15%;">Perception / Fiabilité</th>
                            <th scope="col" class="pe-3" style="width: 15%;">Liaisons DIR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receivedContributions as $rc)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold">{{ $rc->stakeholder->name }}</div>
                                    <div class="small text-muted">
                                        {{ $rc->received_at ? $rc->received_at->format('d/m/Y') : 'Date non renseignée' }}
                                        &bull; {{ ucfirst($rc->modality) }}
                                    </div>
                                    <div class="small text-muted mt-1">Par : {{ $rc->recorder?->name ?? 'Cabinet' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $rc->summary }}</div>
                                    @if($rc->key_findings)
                                        <div class="small text-secondary mt-1">
                                            <strong>Points saillants :</strong> {{ $rc->key_findings }}
                                        </div>
                                    @endif
                                    @if($rc->associated_document)
                                        <div class="small text-muted mt-1">
                                            <x-icon name="file" size="12" /> {{ $rc->associated_document }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ ucfirst(str_replace('_', ' ', $rc->reliability_perception)) }}
                                    </span>
                                </td>
                                <td class="pe-3">
                                    @forelse($rc->requiredInformation as $req)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mb-1 d-inline-block">
                                            {{ Str::limit($req->information_name, 25) }}
                                        </span>
                                    @empty
                                        <span class="text-muted small">Aucun lien DIR</span>
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    Aucune contribution recueillie enregistrée pour le moment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Articulation méthodologique --}}
    <div class="card border border-primary border-opacity-25 bg-primary bg-opacity-10 shadow-sm">
        <div class="card-body">
            <h2 class="h6 fw-bold text-primary mb-2 d-flex align-items-center gap-2">
                <x-icon name="layers" size="18" />
                Articulation avec les outils suivants du Diagnostic 360°
            </h2>
            <p class="small text-secondary mb-0">
                Les contributions issues de la <strong>Stakeholder Input Grid™</strong> traduisent la perception humaine et organisationnelle des acteurs. Elles seront directement croisées avec les pièces probantes du DIR au sein de la <strong>360° Diagnostic Matrix™</strong> (Outil 05) pour évaluer chaque critère, puis confrontées aux causes racines dans la <strong>Problem Structuring Grid™</strong> (Outil 06).
            </p>
        </div>
    </div>
</div>

{{-- Modale : Ajouter une partie prenante --}}
<div class="modal fade" id="modalAddStakeholder" tabindex="-1" aria-labelledby="modalAddStakeholderLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('missions.stakeholder-grid.stakeholders.store', $mission) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h6 fw-bold" id="modalAddStakeholderLabel">Nouvelle partie prenante</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="sh_name" class="form-label fw-semibold">Nom ou Désignation <span class="text-danger">*</span></label>
                        <input type="text" id="sh_name" name="name" class="form-control" placeholder="Ex: Jean Dupont ou Équipe Commerciale" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="stakeholder_type" class="form-label fw-semibold">Type</label>
                            <select id="stakeholder_type" name="stakeholder_type" class="form-select">
                                <option value="internal" selected>Interne</option>
                                <option value="external">Externe</option>
                                <option value="partner">Partenaire</option>
                                <option value="supplier">Fournisseur</option>
                                <option value="customer">Client</option>
                                <option value="institutional">Institutionnel</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="role_in_diagnostic" class="form-label fw-semibold">Rôle dans le diagnostic</label>
                            <input type="text" id="role_in_diagnostic" name="role_in_diagnostic" class="form-control" placeholder="Ex: Décideur, Expert, Terrain">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="position" class="form-label fw-semibold">Fonction / Titre</label>
                            <input type="text" id="position" name="position" class="form-control" placeholder="Ex: Directeur Financier">
                        </div>
                        <div class="col-6">
                            <label for="organization" class="form-label fw-semibold">Organisation</label>
                            <input type="text" id="organization" name="organization" class="form-control" placeholder="Ex: Filiale Nord">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label for="contact_details" class="form-label fw-semibold">Coordonnées de contact</label>
                        <input type="text" id="contact_details" name="contact_details" class="form-control" placeholder="Ex: email, téléphone, bureau">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer la partie prenante</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modale : Définir une contribution attendue --}}
<div class="modal fade" id="modalAddExpectedContribution" tabindex="-1" aria-labelledby="modalAddExpectedContributionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('missions.stakeholder-grid.expected-contributions.store', $mission) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h6 fw-bold" id="modalAddExpectedContributionLabel">Définir une contribution attendue</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    @if(!empty($preparation['scope']['excluded_items']))
                        <div class="alert alert-light border border-warning border-opacity-50 small mb-3 p-2">
                            <strong class="text-warning-emphasis">Périmètre exclu (DSC) :</strong>
                            @foreach($preparation['scope']['excluded_items'] as $ex)
                                <span class="badge bg-light text-danger border border-danger me-1">{{ $ex['axis'] }}</span>
                            @endforeach
                            <div class="text-muted mt-1">Les mobilisations sur ces axes sont strictement interdites sans arbitrage préalable.</div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="ec_stakeholder_id" class="form-label fw-semibold">Partie prenante <span class="text-danger">*</span></label>
                        <select id="ec_stakeholder_id" name="stakeholder_id" class="form-select" required>
                            <option value="">-- Sélectionner une partie prenante --</option>
                            @foreach($stakeholders as $sh)
                                <option value="{{ $sh->id }}">{{ $sh->name }} ({{ $sh->position ?? $sh->stakeholder_type }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Sélection directe depuis le périmètre DSC --}}
                    @if(!empty($preparation['scope']['included_items']))
                        <div class="mb-3">
                            <label for="ec_scope_item_id" class="form-label fw-semibold">Rattacher à un axe validé du périmètre (DSC)</label>
                            <select id="ec_scope_item_id" name="scope_item_id" class="form-select">
                                <option value="">-- Sélectionner un axe du DSC (recommandé) --</option>
                                @foreach($preparation['scope']['included_items'] as $sc)
                                    <option value="{{ $sc['id'] }}">
                                        [{{ $sc['domain'] }}] {{ $sc['axis'] }} {{ !empty($sc['subaxis']) ? '- ' . $sc['subaxis'] : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">Renseigne automatiquement le domaine et l'axe sans ressaisie.</div>
                        </div>
                    @endif

                    {{-- Sélection directe depuis le registre DIR --}}
                    @if($requiredInformation->isNotEmpty())
                        <div class="mb-3">
                            <label for="ec_required_information_id" class="form-label fw-semibold">Rattacher à un besoin d'information (DIR)</label>
                            <select id="ec_required_information_id" name="required_information_id" class="form-select">
                                <option value="">-- Associer à une information recherchée (optionnel) --</option>
                                @foreach($requiredInformation as $req)
                                    <option value="{{ $req->id }}">
                                        [{{ $req->domain }}] {{ Str::limit($req->information_name, 50) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="ec_domain" class="form-label fw-semibold">Domaine (si hors liste DSC)</label>
                            <input type="text" id="ec_domain" name="domain" class="form-control" placeholder="Ex: Finance & Rentabilité">
                        </div>
                        <div class="col-6">
                            <label for="ec_axis" class="form-label fw-semibold">Axe (si hors liste DSC)</label>
                            <input type="text" id="ec_axis" name="axis" class="form-control" placeholder="Ex: Rentabilité & Marges">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="expected_contribution" class="form-label fw-semibold">Contribution attendue <span class="text-danger">*</span></label>
                        <textarea id="expected_contribution" name="expected_contribution" class="form-control" rows="2" placeholder="Information, explication, appréciation recherchée..."></textarea>
                    </div>

                    <div class="row g-2 mb-0">
                        <div class="col-6">
                            <label for="ec_modality" class="form-label fw-semibold">Modalité</label>
                            <select id="ec_modality" name="modality" class="form-select">
                                <option value="interview" selected>Entretien</option>
                                <option value="meeting">Réunion</option>
                                <option value="questionnaire">Questionnaire</option>
                                <option value="observation">Observation</option>
                                <option value="document">Document</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="ec_priority" class="form-label fw-semibold">Priorité</label>
                            <select id="ec_priority" name="priority" class="form-select">
                                <option value="critical">Critique</option>
                                <option value="high">Haute</option>
                                <option value="normal" selected>Normale</option>
                                <option value="low">Faible</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer la contribution</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modale : Consigner un recueil effectif --}}
<div class="modal fade" id="modalRecordContribution" tabindex="-1" aria-labelledby="modalRecordContributionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('missions.stakeholder-grid.received-contributions.store', $mission) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h6 fw-bold" id="modalRecordContributionLabel">Consigner une contribution recueillie</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rc_stakeholder_id" class="form-label fw-semibold">Partie prenante interrogée <span class="text-danger">*</span></label>
                        <select id="rc_stakeholder_id" name="stakeholder_id" class="form-select" required>
                            <option value="">-- Sélectionner une partie prenante --</option>
                            @foreach($stakeholders as $sh)
                                <option value="{{ $sh->id }}">{{ $sh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="expected_contribution_id" class="form-label fw-semibold">Rattacher à une contribution attendue (optionnel)</label>
                        <select id="expected_contribution_id" name="expected_contribution_id" class="form-select">
                            <option value="">-- Aucun rattachement --</option>
                            @foreach($expectedContributions as $ec)
                                <option value="{{ $ec->id }}">
                                    [{{ $ec->stakeholder->name }}] {{ Str::limit($ec->expected_contribution, 45) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="summary" class="form-label fw-semibold">Résumé des déclarations / Appréciations <span class="text-danger">*</span></label>
                        <textarea id="summary" name="summary" class="form-control" rows="3" placeholder="Contenu des échanges, déclarations du répondant..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="key_findings" class="form-label fw-semibold">Constats clés ou signaux d'alerte exprimés</label>
                        <textarea id="key_findings" name="key_findings" class="form-control" rows="2" placeholder="Points saillants à examiner dans le diagnostic..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="linked_required_information_ids" class="form-label fw-semibold">Relier aux besoins d'information DIR concernés</label>
                        <select id="linked_required_information_ids" name="linked_required_information_ids[]" class="form-select" multiple size="3">
                            @foreach($requiredInformation as $req)
                                <option value="{{ $req->id }}">
                                    [{{ $req->domain }}] {{ Str::limit($req->information_name, 45) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-0">
                        <div class="col-6">
                            <label for="reliability_perception" class="form-label fw-semibold">Qualité de perception</label>
                            <select id="reliability_perception" name="reliability_perception" class="form-select">
                                <option value="solid">Témoignage direct solide</option>
                                <option value="perception_to_verify" selected>Perception à corroborer</option>
                                <option value="subjective_opinion">Opinion subjective</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="associated_document" class="form-label fw-semibold">Fichier / Compte-rendu</label>
                            <input type="text" id="associated_document" name="associated_document" class="form-control" placeholder="Ex: CR_Entretien_DAF.docx">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Enregistrer la contribution</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
