@extends('layouts.app')

@section('title', 'Diagnostic Restitution Deck™ - ' . $mission->title)

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">DIAGNOSTIC 360° &bull; OUTIL 07</div>
            <h1 class="page-title mb-1">Diagnostic Restitution Deck™<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                Structuration éditoriale, séquençage et préparation de la restitution &bull; Mission <strong>{{ $mission->reference ?? $mission->title }}</strong>
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <form action="{{ route('missions.restitution-deck.prepare', $mission) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Synchroniser et régénérer la trame si nécessaire">
                    <x-icon name="refresh-cw" size="14" /> Actualiser les sources
                </button>
            </form>

            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddSection">
                <x-icon name="plus" size="14" /> Nouvelle section
            </button>

            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSnapshot">
                <x-icon name="check-circle" size="14" /> Archiver une version
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Bandeau métrique de progression éditoriale du Deck --}}
    <div class="card border shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                <div>
                    <h2 class="h6 fw-bold mb-0">Avancement de la préparation de la restitution</h2>
                    <div class="small text-muted">
                        {{ $sections->where('status', 'validated')->count() }} sur {{ $sections->count() }} section(s) formellement validée(s) par le chef de mission
                    </div>
                </div>
                <div class="fw-bold fs-5 text-primary">
                    {{ $deck->readinessPercentage() }}%
                </div>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $deck->readinessPercentage() }}%;" aria-valuenow="{{ $deck->readinessPercentage() }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <div class="row g-3 mt-3 pt-3 border-top">
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Sections totales</div>
                    <div class="fw-bold fs-5 text-dark">{{ $sections->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Sections validées</div>
                    <div class="fw-bold fs-5 text-success">{{ $sections->where('status', 'validated')->count() }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Axes examinés (D360)</div>
                    <div class="fw-bold fs-5 text-primary">{{ $initialData['scope_distinction']['evaluated_axes_count'] }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Points d'appui (Forces)</div>
                    <div class="fw-bold fs-5 text-success">{{ count($initialData['d360']['strengths']) }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Problèmes structurés</div>
                    <div class="fw-bold fs-5 text-danger">{{ $initialData['psg']['total_problems'] }}</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-muted small text-uppercase fw-semibold">Versions archivées</div>
                    <div class="fw-bold fs-5 text-secondary">{{ $versions->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Encadré de Transparence Méthodologique : Périmètre Prévu vs Réellement Examiné --}}
    <div class="card border-primary border-start-primary border-start-4 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex align-items-center gap-2 mb-2 text-primary fw-bold">
                <x-icon name="check-circle" size="18" /> Rigueur du périmètre & Couverture d'investigation
            </div>
            <div class="row g-3 small">
                <div class="col-md-3">
                    <div class="text-muted">Périmètre contractuel initial :</div>
                    <strong class="text-dark">{{ $initialData['scope_distinction']['planned_total_count'] }} axes définis</strong>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Axes audités en profondeur :</div>
                    <strong class="text-success">{{ $initialData['scope_distinction']['evaluated_axes_count'] }} axes instruits et étayés</strong>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Axes en cours / partiels :</div>
                    <strong class="text-warning">{{ $initialData['scope_distinction']['partially_evaluated_axes_count'] }} axe(s)</strong>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Axes expressément exclus :</div>
                    <strong class="text-secondary">{{ count($initialData['scope_distinction']['excluded_items']) }} sujet(s) hors champ motivé(s)</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Sections éditoriales de restitution --}}
    <div class="d-flex flex-column gap-4 mb-4">
        @forelse($sections as $section)
            @php
                $statusBadgeClass = match($section->status) {
                    'validated' => 'bg-success text-white',
                    'to_review' => 'bg-info text-white',
                    'drafting' => 'bg-warning text-dark',
                    default => 'bg-secondary text-white',
                };
            @endphp
            <div class="card border shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border font-monospace">#{{ $section->position }}</span>
                        <h2 class="h5 mb-0 fw-bold text-dark">{{ $section->title }}</h2>
                        <span class="badge {{ $statusBadgeClass }}">
                            {{ match($section->status) {
                                'validated' => 'Validée',
                                'to_review' => 'À réviser',
                                'drafting' => 'En rédaction',
                                default => 'À préparer',
                            } }}
                        </span>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if($section->status !== 'validated')
                            <form action="{{ route('missions.restitution-deck.sections.validate', [$mission, $section]) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-success btn-sm">
                                    <x-icon name="check" size="14" /> Valider
                                </button>
                            </form>
                        @endif

                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddContent_{{ $section->id }}">
                            <x-icon name="plus" size="14" /> Ajouter contenu
                        </button>

                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportResult_{{ $section->id }}">
                            <x-icon name="download" size="14" /> Importer résultat
                        </button>

                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditSection_{{ $section->id }}">
                            <x-icon name="pen" size="14" />
                        </button>

                        <form action="{{ route('missions.restitution-deck.sections.destroy', [$mission, $section]) }}" method="POST" onsubmit="return confirm('Supprimer cette section et ses contenus ?');" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <x-icon name="trash" size="14" />
                            </button>
                        </form>
                    </div>
                </div>

                @if($section->objective)
                    <div class="p-3 bg-light border-bottom text-muted small">
                        <strong>Objectif de la section :</strong> {{ $section->objective }}
                        @if($section->observations)
                            <div class="mt-1 text-secondary fst-italic">Note consultant : {{ $section->observations }}</div>
                        @endif
                    </div>
                @endif

                <div class="card-body p-3">
                    @forelse($section->contents as $content)
                        <div class="p-3 mb-3 border rounded bg-white shadow-xs">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <span class="badge bg-light text-dark border me-1">{{ ucfirst($content->content_type) }}</span>
                                    @if($content->title)
                                        <strong class="text-dark">{{ $content->title }}</strong>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-link btn-sm p-0 text-secondary" data-bs-toggle="modal" data-bs-target="#modalEditContent_{{ $content->id }}">
                                    <x-icon name="pen" size="14" /> Modifier
                                </button>
                            </div>

                            @if($content->body_text)
                                <div class="text-dark small mb-2" style="white-space: pre-line;">{{ $content->body_text }}</div>
                            @endif

                            @if($content->structured_data)
                                <div class="p-2 bg-light rounded small mb-2">
                                    @if(is_array($content->structured_data))
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($content->structured_data as $key => $val)
                                                @if(is_string($val) || is_numeric($val))
                                                    <span class="badge bg-white text-dark border p-2">
                                                        @if(!is_numeric($key)) <span class="text-muted">{{ str_replace('_', ' ', $key) }} :</span> @endif
                                                        <strong>{{ $val }}</strong>
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Sources associées --}}
                            @if($content->sources->isNotEmpty())
                                <div class="d-flex flex-wrap align-items-center gap-1 mt-2 pt-2 border-top">
                                    <span class="text-muted" style="font-size: 0.7rem;">Sources certifiées :</span>
                                    @foreach($content->sources as $source)
                                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.65rem;" title="{{ $source->source_summary }}">
                                            [{{ strtoupper($source->source_tool) }}] {{ $source->source_reference ?? $source->source_type }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-3 text-muted small fst-italic">
                            Aucun contenu rédigé pour cette section. Cliquez sur <strong>+ Ajouter contenu</strong> ou <strong>Importer résultat</strong>.
                        </div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="card border shadow-sm p-5 text-center">
                <div class="text-muted mb-3">
                    <x-icon name="file-text" size="36" />
                </div>
                <h2 class="h5 fw-bold mb-2">Aucune section dans le deck</h2>
                <p class="text-muted small mb-4">
                    Le Diagnostic Restitution Deck prépare et structure la restitution client en exploitant vos travaux du D360 et du PSG.
                </p>
                <form action="{{ route('missions.restitution-deck.prepare', $mission) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        Initialiser la trame standard en 8 sections
                    </button>
                </form>
            </div>
        @endforelse
    </div>

    {{-- Historique des versions de restitution archivées --}}
    @if($versions->isNotEmpty())
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                    <x-icon name="archive" size="16" /> Historique des versions consolidées ({{ $versions->count() }})
                </h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 15%;">Version</th>
                                <th style="width: 30%;">Intitulé</th>
                                <th style="width: 35%;">Résumé du jalon</th>
                                <th style="width: 20%;" class="text-end">Archivée le</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($versions as $version)
                                <tr>
                                    <td>
                                        <span class="badge bg-dark font-monospace">Version {{ $version->version_number }}</span>
                                    </td>
                                    <td>
                                        <strong class="text-dark">{{ $version->title }}</strong>
                                    </td>
                                    <td class="small text-muted">
                                        {{ $version->summary }}
                                    </td>
                                    <td class="text-end small text-secondary">
                                        {{ $version->published_at?->format('d/m/Y à H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Modale : Ajouter Section --}}
    <div class="modal fade" id="modalAddSection" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('missions.restitution-deck.sections.store', $mission) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Ajouter une section à la restitution</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Titre de la section</label>
                            <input type="text" name="title" class="form-control" placeholder="Ex: 9. Plan de transition opérationnelle..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Objectif pédagogique / Ce que le client doit comprendre</label>
                            <textarea name="objective" rows="3" class="form-control" placeholder="Objectif de clarification ou d'orientation pour cette section..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Observations & Instructions de rédaction</label>
                            <input type="text" name="observations" class="form-control" placeholder="Consignes particulières pour les consultants...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer la section</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modale : Archiver une Version --}}
    <div class="modal fade" id="modalSnapshot" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('missions.restitution-deck.snapshot', $mission) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Archiver une version de restitution</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">
                            Cette action génère une copie figée (`RestitutionVersion`) de l'intégralité des sections, contenus et sources actuels.
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Intitulé de la version</label>
                            <input type="text" name="version_title" class="form-control" placeholder="Ex: Version pré-comité de direction..." value="Restitution du {{ now()->format('d/m/Y') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">Consolider & Archiver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modales dynamiques par Section & par Contenu --}}
    @foreach($sections as $section)
        {{-- Modale Édition Section --}}
        <div class="modal fade" id="modalEditSection_{{ $section->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.restitution-deck.sections.update', [$mission, $section]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Modifier la section</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Titre</label>
                                    <input type="text" name="title" class="form-control" value="{{ $section->title }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Statut</label>
                                    <select name="status" class="form-select">
                                        <option value="to_prepare" {{ $section->status === 'to_prepare' ? 'selected' : '' }}>À préparer</option>
                                        <option value="drafting" {{ $section->status === 'drafting' ? 'selected' : '' }}>En rédaction</option>
                                        <option value="to_review" {{ $section->status === 'to_review' ? 'selected' : '' }}>À réviser</option>
                                        <option value="validated" {{ $section->status === 'validated' ? 'selected' : '' }}>Validée</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Objectif</label>
                                <textarea name="objective" rows="3" class="form-control">{{ $section->objective }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Observations</label>
                                <input type="text" name="observations" class="form-control" value="{{ $section->observations }}">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modale Ajouter Contenu dans Section --}}
        <div class="modal fade" id="modalAddContent_{{ $section->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.restitution-deck.sections.contents.store', [$mission, $section]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <span class="badge bg-secondary mb-1">Section : {{ $section->title }}</span>
                                <h5 class="modal-title fw-bold">Rédiger un nouveau contenu</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Format du contenu</label>
                                    <select name="content_type" class="form-select" required>
                                        <option value="text">Texte explicatif / Analyse</option>
                                        <option value="callout">Encadré d'alerte / Point cardinal</option>
                                        <option value="conclusion_box">Bloc de conclusion stratégique</option>
                                        <option value="domain_rating">Tableau d'appréciation</option>
                                        <option value="strengths_list">Liste de points forts</option>
                                        <option value="problems_list">Liste de problèmes structurés</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Sous-titre / En-tête (Optionnel)</label>
                                    <input type="text" name="title" class="form-control" placeholder="Titre du paragraphe ou du bloc...">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Corps du texte rédactionnel</label>
                                <textarea name="body_text" rows="5" class="form-control" placeholder="Rédigez l'analyse ou la synthèse destinée au client..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Ajouter le contenu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modale Importer Résultat d'Outil --}}
        <div class="modal fade" id="modalImportResult_{{ $section->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('missions.restitution-deck.sections.import-result', [$mission, $section]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <span class="badge bg-secondary mb-1">Section : {{ $section->title }}</span>
                                <h5 class="modal-title fw-bold">Importer un résultat issu des outils d'amont</h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Outil source</label>
                                    <select name="source_tool" class="form-select" required>
                                        <option value="d360">D360 &bull; 360° Diagnostic Matrix™</option>
                                        <option value="psg">PSG &bull; Problem Structuring Grid™</option>
                                        <option value="dsc">DSC &bull; Diagnostic Scope Canvas™</option>
                                        <option value="dir">DIR &bull; Diagnostic Intake Register™</option>
                                        <option value="sig">SIG &bull; Stakeholder Input Grid™</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type de résultat</label>
                                    <input type="text" name="source_type" class="form-control" placeholder="Ex: difficulty, strength, structured_problem..." value="difficulty" required>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Identifiant / Référence source</label>
                                    <input type="text" name="source_id" class="form-control" placeholder="Ex: PRB-001 ou ID D360" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Titre du bloc importé</label>
                                    <input type="text" name="title" class="form-control" placeholder="Titre dans la restitution..." required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Texte restitué / Synthèse éditoriale</label>
                                <textarea name="body_text" rows="4" class="form-control" placeholder="Formulation adaptée pour la compréhension du client..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Importer le résultat</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modales d'édition de chaque contenu individuel --}}
        @foreach($section->contents as $content)
            <div class="modal fade" id="modalEditContent_{{ $content->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form action="{{ route('missions.restitution-deck.contents.update', [$mission, $content]) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold">Modifier le contenu éditorial</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Titre du bloc</label>
                                    <input type="text" name="title" class="form-control" value="{{ $content->title }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Corps du texte</label>
                                    <textarea name="body_text" rows="5" class="form-control">{{ $content->body_text }}</textarea>
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
    @endforeach
</div>
@endsection
