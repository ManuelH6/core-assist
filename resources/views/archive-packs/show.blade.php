@extends('layouts.app')

@section('title', 'Diagnostic Archive Pack™ - ' . $mission->title)

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">DIAGNOSTIC 360° &bull; OUTIL 09</div>
            <h1 class="page-title mb-1">Diagnostic Archive Pack™<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                Mémoire documentaire, inventaire analytique et conservation structurée des 10 outils &bull; Mission <strong>{{ $mission->reference ?? $mission->title }}</strong>
                @if($clientName = ($mission->framing?->request?->client?->name ?? $initialData['mission']['client'] ?? null))
                    &bull; Client : <strong>{{ $clientName }}</strong>
                @endif
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            @if(! $pack->isClosed())
                <form action="{{ route('missions.archive-pack.collect', $mission) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm" title="Synchroniser automatiquement les données des 8 outils amont">
                        <x-icon name="refresh-cw" size="14" /> Auto-collecte des 8 outils
                    </button>
                </form>

                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddItem">
                    <x-icon name="plus" size="14" /> Archiver un élément
                </button>

                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalClosePack">
                    <x-icon name="lock" size="14" /> Clôturer l'archive
                </button>
            @else
                <span class="badge bg-secondary p-2">
                    <x-icon name="lock" size="14" /> Archive Clôturée le {{ $pack->closed_at?->format('d/m/Y') }}
                </span>
            @endif

            <form action="{{ route('missions.archive-pack.snapshot', $mission) }}" method="POST" class="d-inline">
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

    {{-- Bandeau de statut et de complétude méthodologique --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold text-dark">Complétude documentaire du diagnostic 360°</span>
                        <span class="badge {{ $completeness['completeness_score'] === 100 ? 'bg-success' : 'bg-primary' }} fs-6">
                            {{ $completeness['completeness_score'] }} %
                        </span>
                    </div>
                    <div class="progress mb-2" style="height: 10px;">
                        <div class="progress-bar {{ $completeness['completeness_score'] === 100 ? 'bg-success' : 'bg-primary' }}"
                             role="progressbar"
                             style="width: {{ $completeness['completeness_score'] }}%;"
                             aria-valuenow="{{ $completeness['completeness_score'] }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>
                    </div>
                    <div class="small text-muted">
                        {{ $completeness['covered_count'] }} sur {{ $completeness['total_expected'] }} outils référentiels couverts dans ce dossier.
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-2 border rounded bg-light">
                                <div class="fs-5 fw-bold text-primary">{{ $pack->items->count() }}</div>
                                <div class="small text-muted">Éléments archivés</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded bg-light">
                                <div class="fs-5 fw-bold text-dark">
                                    {{ $pack->items->sum(fn($it) => $it->versions->count()) }}
                                </div>
                                <div class="small text-muted">Versions tracées</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded bg-light">
                                <div class="fs-5 fw-bold {{ $pack->status === 'closed' ? 'text-secondary' : ($pack->status === 'compiled' ? 'text-success' : 'text-warning') }}">
                                    @if($pack->status === 'closed')
                                        Clôturé
                                    @elseif($pack->status === 'compiled')
                                        Complet
                                    @else
                                        En cours
                                    @endif
                                </div>
                                <div class="small text-muted">Statut du dossier</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- État de couverture des 8 outils --}}
            <div class="mt-4 pt-3 border-top">
                <div class="small fw-semibold text-muted text-uppercase mb-2">Couverture des 8 outils du diagnostic</div>
                <div class="d-flex flex-wrap gap-2">
                    @php
                        $toolBadges = [
                            'mfs' => 'MFS (Cadrage)',
                            'dsc' => 'DSC (Périmètre)',
                            'dir' => 'DIR (Collecte)',
                            'sig' => 'SIG (Consultations)',
                            'd360' => 'D360 (Analyses)',
                            'psg' => 'PSG (Problèmes)',
                            'drd' => 'DRD (Restitution)',
                            'ldc' => 'LDC (Décisions)',
                        ];
                    @endphp
                    @foreach($toolBadges as $key => $lbl)
                        @php
                            $isCovered = isset($completeness['covered_tools'][$key]);
                            $count = $isCovered ? $completeness['covered_tools'][$key]['items_count'] : 0;
                        @endphp
                        <span class="badge {{ $isCovered ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' }} px-2 py-1">
                            <x-icon :name="$isCovered ? 'check-circle' : 'circle'" size="12" />
                            {{ $lbl }}
                            @if($isCovered)
                                <span class="badge bg-white text-dark ms-1">{{ $count }}</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres et Barre de recherche --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('missions.archive-pack.search', $mission) }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <x-icon name="search" size="14" />
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0" placeholder="Rechercher par mot-clé, référence, auteur...">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">Toutes les catégories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->code }}" {{ request('category') === $category->code ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="tool" class="form-select">
                        <option value="">Tous les outils</option>
                        <option value="mfs" {{ request('tool') === 'mfs' ? 'selected' : '' }}>MFS</option>
                        <option value="dsc" {{ request('tool') === 'dsc' ? 'selected' : '' }}>DSC</option>
                        <option value="dir" {{ request('tool') === 'dir' ? 'selected' : '' }}>DIR</option>
                        <option value="sig" {{ request('tool') === 'sig' ? 'selected' : '' }}>SIG</option>
                        <option value="d360" {{ request('tool') === 'd360' ? 'selected' : '' }}>D360</option>
                        <option value="psg" {{ request('tool') === 'psg' ? 'selected' : '' }}>PSG</option>
                        <option value="drd" {{ request('tool') === 'drd' ? 'selected' : '' }}>DRD</option>
                        <option value="ldc" {{ request('tool') === 'ldc' ? 'selected' : '' }}>LDC</option>
                        <option value="external" {{ request('tool') === 'external' ? 'selected' : '' }}>Externe</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-outline-primary w-100">Filtrer</button>
                    @if(request()->hasAny(['q', 'category', 'tool']))
                        <a href="{{ route('missions.archive-pack.show', $mission) }}" class="btn btn-outline-secondary" title="Réinitialiser">
                            <x-icon name="x" size="14" />
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Inventaire des Éléments Archivés --}}
    @php
        $displayedItems = isset($items) ? $items : $pack->items;
    @endphp

    <div class="card border shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0">Inventaire documentaire & analytique ({{ $displayedItems->count() }})</h2>
            <div class="small text-muted">
                Dossier de référence : <strong>{{ $pack->reference }}</strong>
            </div>
        </div>

        {{-- Desktop Table (>= 768px) --}}
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 110px;">Réf.</th>
                        <th>Intitulé de l'élément</th>
                        <th>Catégorie</th>
                        <th>Origine</th>
                        <th style="width: 90px;">Version</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th class="text-end" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($displayedItems as $item)
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">
                                    {{ $item->reference }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $item->title }}</div>
                                @if($item->description)
                                    <div class="small text-muted text-truncate" style="max-width: 340px;">
                                        {{ $item->description }}
                                    </div>
                                @endif
                                @if($item->location_path)
                                    <div class="font-monospace small text-muted">
                                        <x-icon name="link" size="11" /> {{ $item->location_path }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border">
                                    {{ $item->primaryCategory?->name ?? 'Non classé' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border">
                                    {{ strtoupper($item->source_tool) }}
                                    @if($item->source_reference)
                                        &bull; {{ $item->source_reference }}
                                    @endif
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-dark">v{{ $item->current_version_number }}.0</span>
                            </td>
                            <td class="small text-muted">
                                {{ $item->archived_at?->format('d/m/Y') }}
                            </td>
                            <td>
                                @if($item->status === 'available')
                                    <span class="badge bg-success">Disponible</span>
                                @elseif($item->status === 'replaced')
                                    <span class="badge bg-warning text-dark">Remplacé</span>
                                @elseif($item->status === 'deprecated')
                                    <span class="badge bg-danger">Obsolète</span>
                                @else
                                    <span class="badge bg-secondary">Archivé</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalVersions-{{ $item->id }}"
                                        title="Historique des versions ({{ $item->versions->count() }})">
                                    <x-icon name="history" size="13" /> {{ $item->versions->count() }}
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalSources-{{ $item->id }}"
                                        title="Sources justificatives ({{ $item->sources->count() }})">
                                    <x-icon name="git-branch" size="13" />
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                Aucun élément archivé ne correspond aux critères.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards (< 768px) conforme à la règle data display matrix --}}
        <div class="d-md-none p-3 mobile-card-list">
            @forelse($displayedItems as $item)
                <div class="card border mb-3 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-light text-dark border font-monospace me-1">{{ $item->reference }}</span>
                                <span class="badge bg-primary-subtle text-primary border">{{ strtoupper($item->source_tool) }}</span>
                            </div>
                            <span class="badge bg-dark">v{{ $item->current_version_number }}.0</span>
                        </div>

                        <h3 class="h6 fw-bold mb-1">{{ $item->title }}</h3>
                        @if($item->description)
                            <p class="small text-muted mb-2">{{ $item->description }}</p>
                        @endif

                        <div class="row g-2 small text-muted mb-3">
                            <div class="col-6">
                                <strong>Catégorie :</strong> {{ $item->primaryCategory?->name ?? 'Général' }}
                            </div>
                            <div class="col-6">
                                <strong>Archivé le :</strong> {{ $item->archived_at?->format('d/m/Y') }}
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary flex-fill" data-bs-toggle="modal" data-bs-target="#modalVersions-{{ $item->id }}">
                                <x-icon name="history" size="13" /> Versions ({{ $item->versions->count() }})
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" data-bs-toggle="modal" data-bs-target="#modalSources-{{ $item->id }}">
                                <x-icon name="git-branch" size="13" /> Sources ({{ $item->sources->count() }})
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted">
                    Aucun élément archivé pour le moment.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Journal d'Audit & Historique Récent des Versions --}}
    @if($history->isNotEmpty())
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h6 fw-bold mb-0">Traçabilité & Historique des versions archivées</h2>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($history->take(10) as $version)
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div class="fw-semibold text-dark">
                                    {{ $version->item?->reference }} &bull; {{ $version->title }}
                                    <span class="badge bg-dark ms-1">{{ $version->version_tag ?: ('v' . $version->version_number . '.0') }}</span>
                                </div>
                                <span class="small text-muted">{{ $version->archived_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="small text-muted mb-1">{{ $version->changelog ?? $version->description }}</div>
                            <div class="small text-muted font-monospace">
                                Auteur : {{ $version->creator?->name ?? 'Système' }} &bull; Catégorie : {{ $version->item?->primaryCategory?->name ?? 'Non classé' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

{{-- MODALS --}}

{{-- Modal d'archivage manuel d'un élément --}}
<div class="modal fade" id="modalAddItem" tabindex="-1" aria-labelledby="modalAddItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('missions.archive-pack.items.store', $mission) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddItemLabel">Archiver un nouvel élément</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Intitulé de l'élément</label>
                        <input type="text" name="title" class="form-control" placeholder="Ex: Rapport d'audit intermédiaire, Pièce comptable 2025..." required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Catégorie principale</label>
                            <select name="category_id" class="form-select" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Type d'élément</label>
                            <select name="item_type" class="form-select" required>
                                <option value="document">Document / Fichier</option>
                                <option value="tool_snapshot">Instantané d'outil (Snapshot)</option>
                                <option value="data_record">Enregistrement de données</option>
                                <option value="contribution">Contribution / Entretien</option>
                                <option value="decision">Décision / Arbitrage</option>
                                <option value="restitution_version">Version de restitution</option>
                                <option value="export">Export de données</option>
                                <option value="other">Autre pièce justificative</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Outil source</label>
                            <select name="source_tool" class="form-select">
                                <option value="external">Document Externe / Versé</option>
                                <option value="mfs">MFS - Mission Framing Sheet</option>
                                <option value="dsc">DSC - Diagnostic Scope Canvas</option>
                                <option value="dir">DIR - Diagnostic Intake Register</option>
                                <option value="sig">SIG - Stakeholder Input Grid</option>
                                <option value="d360">D360 - 360° Diagnostic Matrix</option>
                                <option value="psg">PSG - Problem Structuring Grid</option>
                                <option value="drd">DRD - Diagnostic Restitution Deck</option>
                                <option value="ldc">LDC - Live Decision Capture</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Référence source (optionnel)</label>
                            <input type="text" name="source_reference" class="form-control" placeholder="Ex: DEC-001, PRB-003, DIR-DOC-12">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Emplacement / Chemin du document ou de la ressource</label>
                        <input type="text" name="location_path" class="form-control font-monospace" placeholder="Ex: uploads/missions/2026/bilan.pdf ou lien d'enregistrement...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description & Contexte de la pièce</label>
                        <textarea name="description" rows="2" class="form-control" placeholder="Préciser l'utilité et la nature de cet élément dans la démarche de diagnostic..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer dans l'archive</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal de clôture du dossier --}}
<div class="modal fade" id="modalClosePack" tabindex="-1" aria-labelledby="modalClosePackLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('missions.archive-pack.close', $mission) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalClosePackLabel">Clôturer le dossier d'archive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        La clôture formelle fige le dossier d'archive et atteste de sa complétude finale pour les archives pérennes du cabinet. Les outils sources restent intacts et consultables.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observations de clôture</label>
                        <textarea name="observations" rows="3" class="form-control" placeholder="Ex: Dossier d'archive complet et validé par le chef de mission suite à la restitution finale..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">Confirmer la clôture</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modals par Item : Versions & Sources --}}
@foreach($displayedItems as $item)
    {{-- Modal Versions --}}
    <div class="modal fade" id="modalVersions-{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Historique des versions &bull; {{ $item->reference }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold mb-3">{{ $item->title }}</h6>
                    <div class="list-group mb-4">
                        @foreach($item->versions as $v)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-dark">{{ $v->version_tag ?: ('v' . $v->version_number . '.0') }}</span>
                                    <span class="small text-muted">{{ $v->archived_at?->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="fw-semibold text-dark">{{ $v->title }}</div>
                                <div class="small text-muted">{{ $v->changelog }}</div>
                                @if($v->location_path)
                                    <div class="small text-muted font-monospace mt-1">
                                        <x-icon name="link" size="11" /> {{ $v->location_path }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if(! $pack->isClosed())
                        <div class="border rounded p-3 bg-light">
                            <h6 class="fw-bold mb-2">Ajouter une nouvelle version</h6>
                            <form action="{{ route('missions.archive-pack.items.versions.store', [$mission, $item]) }}" method="POST">
                                @csrf
                                <div class="row g-2 mb-2">
                                    <div class="col-md-6">
                                        <input type="text" name="version_tag" class="form-control form-control-sm" placeholder="Tag (ex: v{{ $item->current_version_number + 1 }}.0-valide)">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="location_path" class="form-control form-control-sm" placeholder="Nouvel emplacement...">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="changelog" class="form-control form-control-sm" placeholder="Motif ou changelog de la nouvelle version..." required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Enregistrer la version</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Sources --}}
    <div class="modal fade" id="modalSources-{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Origine & Sources probantes &bull; {{ $item->reference }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group mb-3">
                        @forelse($item->sources as $src)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-primary-subtle text-primary border">
                                        {{ strtoupper($src->source_tool) }} &bull; {{ $src->source_type }}
                                    </span>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $src->relationship_type }}</span>
                                </div>
                                @if($src->source_reference)
                                    <div class="small fw-semibold">Réf : {{ $src->source_reference }}</div>
                                @endif
                                @if($src->notes)
                                    <div class="small text-muted">{{ $src->notes }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="text-muted p-2 text-center">Aucun lien de source rattaché.</div>
                        @endforelse
                    </div>

                    @if(! $pack->isClosed())
                        <div class="border rounded p-3 bg-light">
                            <h6 class="fw-bold mb-2">Relier une source justificative</h6>
                            <form action="{{ route('missions.archive-pack.items.sources.store', [$mission, $item]) }}" method="POST">
                                @csrf
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <select name="source_tool" class="form-select form-select-sm" required>
                                            <option value="mfs">MFS</option>
                                            <option value="dsc">DSC</option>
                                            <option value="dir">DIR</option>
                                            <option value="sig">SIG</option>
                                            <option value="d360">D360</option>
                                            <option value="psg">PSG</option>
                                            <option value="drd">DRD</option>
                                            <option value="ldc">LDC</option>
                                            <option value="external">Externe</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <select name="relationship_type" class="form-select form-select-sm" required>
                                            <option value="origin">Origine</option>
                                            <option value="justification">Justification</option>
                                            <option value="evidence">Preuve</option>
                                            <option value="version_of">Version de</option>
                                            <option value="dependency">Dépendance</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <input type="text" name="source_type" class="form-control form-control-sm" placeholder="Type (finding, problem...)" required>
                                    </div>
                                    <div class="col-6">
                                        <input type="text" name="source_reference" class="form-control form-control-sm" placeholder="Réf (DEC-001...)">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes explicatives...">
                                </div>
                                <button type="submit" class="btn btn-outline-primary btn-sm">Ajouter le lien</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection
