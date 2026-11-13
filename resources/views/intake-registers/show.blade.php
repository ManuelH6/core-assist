@extends('layouts.app')

@section('title', 'Diagnostic Intake Register™ - ' . $mission->title)

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">DIAGNOSTIC 360° &bull; OUTIL 03</div>
            <h1 class="page-title mb-1">Diagnostic Intake Register™<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                Recensement et suivi des informations nécessaires pour la mission <strong>{{ $mission->reference ?? $mission->title }}</strong>
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <form action="{{ route('missions.intake-register.initialize', $mission) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm" onclick="return confirm('Actualiser le registre avec les axes du périmètre ?');">
                    <x-icon name="refresh" size="14" /> Synchroniser avec le DSC
                </button>
            </form>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddInfo">
                <x-icon name="plus" size="14" /> Ajouter une information
            </button>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddReceivedItem">
                <x-icon name="upload" size="14" /> Enregistrer une pièce reçue
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Bandeau d'avancement métrique --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                <div>
                    <h2 class="h6 fw-bold mb-0">Progression de la collecte d'informations</h2>
                    <div class="small text-muted">
                        {{ $progress['received_count'] }} sur {{ $progress['total_items'] }} information(s) obtenue(s)
                        &bull; {{ $progress['verified_count'] }} vérifiée(s) et fiabilisée(s)
                    </div>
                </div>
                <div class="fw-bold fs-5 text-primary">
                    {{ $progress['progress_percentage'] }}%
                </div>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progress['progress_percentage'] }}%;" aria-valuenow="{{ $progress['progress_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <div class="row g-3 mt-3 pt-3 border-top">
                <div class="col-6 col-md-3">
                    <div class="text-muted small text-uppercase fw-semibold">Total demandé</div>
                    <div class="fw-bold fs-5 text-dark">{{ $progress['total_items'] }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small text-uppercase fw-semibold">Obtenu</div>
                    <div class="fw-bold fs-5 text-primary">{{ $progress['received_count'] }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small text-uppercase fw-semibold">Fiabilisé / Vérifié</div>
                    <div class="fw-bold fs-5 text-success">{{ $progress['verified_count'] }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small text-uppercase fw-semibold">En attente critique</div>
                    <div class="fw-bold fs-5 text-danger">{{ $progress['pending_by_priority']['high'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 1 : Registre des informations recherchées --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                <x-icon name="clipboard" size="18" />
                Informations recherchées pour le diagnostic ({{ $requiredItems->count() }})
            </h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3" style="width: 20%;">Domaine & Axe</th>
                            <th scope="col" style="width: 30%;">Information recherchée & Source</th>
                            <th scope="col" style="width: 15%;">Responsable</th>
                            <th scope="col" style="width: 15%;">Statut</th>
                            <th scope="col" class="pe-3" style="width: 20%;">Pièces associées & Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requiredItems as $item)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark small">{{ $item->domain }}</div>
                                    <div class="small text-muted">{{ $item->axis }}</div>
                                    @if($item->priority === 'high')
                                        <span class="badge bg-danger mt-1">Priorité Haute</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->information_name }}</div>
                                    <div class="small text-muted mt-1">
                                        Source : <strong>{{ $item->expected_source ?? 'Non précisée' }}</strong> &bull;
                                        Mode : {{ ucfirst($item->collection_method) }}
                                    </div>
                                    @if($item->justification)
                                        <div class="small text-secondary fst-italic mt-1">{{ $item->justification }}</div>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('missions.intake-register.information.assign', [$mission, $item]) }}" method="POST">
                                        @csrf
                                        <select name="assigned_to" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">-- Non assigné --</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ $item->assigned_to == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form action="{{ route('missions.intake-register.information.status', [$mission, $item]) }}" method="POST">
                                        @csrf
                                        <select name="status" class="form-select form-select-sm mb-1" onchange="this.form.submit()">
                                            <option value="pending" {{ $item->status === 'pending' ? 'selected' : '' }}>En attente</option>
                                            <option value="in_progress" {{ $item->status === 'in_progress' ? 'selected' : '' }}>En cours</option>
                                            <option value="received" {{ $item->status === 'received' ? 'selected' : '' }}>Reçu</option>
                                            <option value="verified" {{ $item->status === 'verified' ? 'selected' : '' }}>Vérifié</option>
                                            <option value="unavailable" {{ $item->status === 'unavailable' ? 'selected' : '' }}>Indisponible</option>
                                        </select>
                                    </form>
                                    @if($item->status === 'verified')
                                        <span class="badge bg-success">Fiabilisé</span>
                                    @endif
                                </td>
                                <td class="pe-3">
                                    @if($item->receivedItems->isNotEmpty())
                                        <div class="small">
                                            @foreach($item->receivedItems as $doc)
                                                <span class="badge bg-light text-dark border mb-1">
                                                    <x-icon name="file" size="12" /> {{ Str::limit($doc->title, 20) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted small">Aucune pièce liée</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Aucune information requise enregistrée. Cliquez sur "Synchroniser avec le DSC" pour générer les besoins.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Cartes mobiles --}}
            <div class="d-md-none p-3">
                @forelse($requiredItems as $item)
                    <div class="card border mb-3 shadow-none">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="small text-uppercase text-muted">{{ $item->domain }} &bull; {{ $item->axis }}</div>
                                    <h3 class="h6 fw-bold mb-0 text-dark">{{ $item->information_name }}</h3>
                                </div>
                                <div>
                                    @if($item->priority === 'high')
                                        <span class="badge bg-danger">Priorité Haute</span>
                                    @endif
                                </div>
                            </div>
                            <div class="small text-secondary mb-2">
                                Source : <strong>{{ $item->expected_source ?? 'Non précisée' }}</strong> &bull;
                                Mode : {{ ucfirst($item->collection_method) }}
                            </div>
                            <div class="row g-2 pt-2 border-top">
                                <div class="col-6">
                                    <form action="{{ route('missions.intake-register.information.status', [$mission, $item]) }}" method="POST">
                                        @csrf
                                        <label class="form-label small mb-1 fw-semibold">Statut</label>
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="pending" {{ $item->status === 'pending' ? 'selected' : '' }}>En attente</option>
                                            <option value="in_progress" {{ $item->status === 'in_progress' ? 'selected' : '' }}>En cours</option>
                                            <option value="received" {{ $item->status === 'received' ? 'selected' : '' }}>Reçu</option>
                                            <option value="verified" {{ $item->status === 'verified' ? 'selected' : '' }}>Vérifié</option>
                                            <option value="unavailable" {{ $item->status === 'unavailable' ? 'selected' : '' }}>Indisponible</option>
                                        </select>
                                    </form>
                                </div>
                                <div class="col-6">
                                    <form action="{{ route('missions.intake-register.information.assign', [$mission, $item]) }}" method="POST">
                                        @csrf
                                        <label class="form-label small mb-1 fw-semibold">Responsable</label>
                                        <select name="assigned_to" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">-- Non assigné --</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ $item->assigned_to == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        Aucun besoin d'information consigné.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Section 2 : Pièces et Éléments reçus --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                <x-icon name="file" size="18" />
                Dossier des pièces & éléments collectés ({{ $receivedItems->count() }})
            </h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">Intitulé de la pièce</th>
                            <th scope="col">Type & Provenance</th>
                            <th scope="col">Date de réception</th>
                            <th scope="col" class="pe-3">Informations couvertes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receivedItems as $doc)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold">{{ $doc->title }}</div>
                                    @if($doc->file_name)
                                        <div class="small text-muted">{{ $doc->file_name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ ucfirst($doc->item_type) }}</span>
                                    <div class="small text-muted mt-1">{{ $doc->source_origin ?? 'Origine client' }}</div>
                                </td>
                                <td>
                                    <div class="small text-dark">{{ $doc->received_at ? $doc->received_at->format('d/m/Y H:i') : 'Date non renseignée' }}</div>
                                    <div class="small text-muted">Par : {{ $doc->receiver?->name ?? 'Cabinet' }}</div>
                                </td>
                                <td class="pe-3">
                                    @forelse($doc->requiredInformation as $req)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mb-1 d-inline-block">
                                            {{ Str::limit($req->information_name, 30) }}
                                        </span>
                                    @empty
                                        <span class="text-muted small">Non rattachée</span>
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    Aucune pièce collectée pour le moment. Cliquez sur "Enregistrer une pièce reçue".
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
                Les pièces et données recensées dans le <strong>Diagnostic Intake Register™</strong> forment le corpus de preuves factuelles. Elles alimentent directement la <strong>Stakeholder Input Grid™</strong> (recueil des perceptions contradictoires) et fournissent les données probantes indispensables à la <strong>360° Diagnostic Matrix™</strong>.
            </p>
        </div>
    </div>
</div>

{{-- Modale : Ajouter une information recherchée --}}
<div class="modal fade" id="modalAddInfo" tabindex="-1" aria-labelledby="modalAddInfoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('missions.intake-register.information.store', $mission) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h6 fw-bold" id="modalAddInfoLabel">Nouveau besoin d'information</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="domain" class="form-label fw-semibold">Domaine <span class="text-danger">*</span></label>
                        <input type="text" id="domain" name="domain" class="form-control" placeholder="Ex: Finance & Rentabilité" required>
                    </div>
                    <div class="mb-3">
                        <label for="axis" class="form-label fw-semibold">Axe <span class="text-danger">*</span></label>
                        <input type="text" id="axis" name="axis" class="form-control" placeholder="Ex: Rentabilité & Marges" required>
                    </div>
                    <div class="mb-3">
                        <label for="information_name" class="form-label fw-semibold">Information recherchée <span class="text-danger">*</span></label>
                        <input type="text" id="information_name" name="information_name" class="form-control" placeholder="Ex: États financiers certifiés N-1" required>
                    </div>
                    <div class="mb-3">
                        <label for="expected_source" class="form-label fw-semibold">Source attendue</label>
                        <input type="text" id="expected_source" name="expected_source" class="form-control" placeholder="Ex: Expert-comptable / DAF">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="priority" class="form-label fw-semibold">Priorité</label>
                            <select id="priority" name="priority" class="form-select">
                                <option value="high">Haute</option>
                                <option value="medium" selected>Moyenne</option>
                                <option value="low">Basse</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="collection_method" class="form-label fw-semibold">Mode de collecte</label>
                            <select id="collection_method" name="collection_method" class="form-select">
                                <option value="document" selected>Document</option>
                                <option value="interview">Entretien</option>
                                <option value="data_extraction">Extraction</option>
                                <option value="observation">Observation</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label for="justification" class="form-label fw-semibold">Justification du besoin</label>
                        <textarea id="justification" name="justification" class="form-control" rows="2" placeholder="Pourquoi cette information est-elle indispensable ?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter au registre</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modale : Enregistrer une pièce reçue --}}
<div class="modal fade" id="modalAddReceivedItem" tabindex="-1" aria-labelledby="modalAddReceivedItemLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('missions.intake-register.received-items.store', $mission) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h6 fw-bold" id="modalAddReceivedItemLabel">Consigner une pièce ou document reçu</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="item_title" class="form-label fw-semibold">Intitulé de la pièce reçue <span class="text-danger">*</span></label>
                        <input type="text" id="item_title" name="title" class="form-control" placeholder="Ex: Grand Livre Général 2025.xlsx" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="item_type" class="form-label fw-semibold">Type</label>
                            <select id="item_type" name="item_type" class="form-select">
                                <option value="document" selected>Document</option>
                                <option value="export_file">Fichier de données</option>
                                <option value="interview_notes">Notes d'entretien</option>
                                <option value="observation_report">Rapport de visite</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="source_origin" class="form-label fw-semibold">Origine / Transmis par</label>
                            <input type="text" id="source_origin" name="source_origin" class="form-control" placeholder="Ex: Direction Financière">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="linked_information_ids" class="form-label fw-semibold">Associer aux besoins d'information (couverts par cette pièce)</label>
                        <select id="linked_information_ids" name="linked_information_ids[]" class="form-select" multiple size="4">
                            @foreach($requiredItems as $item)
                                <option value="{{ $item->id }}">
                                    [{{ $item->domain }}] {{ Str::limit($item->information_name, 45) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small">Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs informations.</div>
                    </div>
                    <div class="mb-0">
                        <label for="notes" class="form-label fw-semibold">Observations sur la pièce</label>
                        <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Qualité des données, exhaustivité, limites constatées..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Enregistrer la pièce</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
