@extends('layouts.app')

@section('title', 'Mission Framing Sheet™ - ' . $sheet->title)

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">DIAGNOSTIC 360° &bull; OUTIL 01</div>
            <h1 class="page-title mb-1">{{ $sheet->title }}<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                Cadre opérationnel de référence de la mission <strong>{{ $mission->reference ?? $mission->title }}</strong>
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            @if($sheet->status === 'draft')
                <span class="badge bg-secondary text-white px-3 py-2 fs-6">Brouillon</span>
                <a href="{{ route('missions.framing-sheet.initial-data', $mission) }}" class="btn btn-outline-primary">
                    <x-icon name="pen" size="16" /> Modifier
                </a>
                <form action="{{ route('missions.framing-sheet.submit', $mission) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Confirmer la soumission de la fiche pour revue interne ?');">
                        <x-icon name="check" size="16" /> Soumettre pour revue
                    </button>
                </form>
            @elseif($sheet->status === 'submitted')
                <span class="badge bg-warning text-dark px-3 py-2 fs-6">En cours de revue interne</span>
                <a href="{{ route('missions.framing-sheet.initial-data', $mission) }}" class="btn btn-outline-secondary btn-sm">
                    <x-icon name="pen" size="14" /> Ajuster la fiche
                </a>
            @elseif($sheet->status === 'validated')
                <span class="badge bg-success px-3 py-2 fs-6">Validé & Figeable</span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        {{-- Colonne gauche : Cadre opérationnel de la mission --}}
        <div class="col-12 col-lg-7">
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                        <x-icon name="briefcase" size="18" />
                        Périmètre opérationnel de la mission
                    </h2>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Objectif spécifique de la mission</div>
                        <div class="p-3 bg-light rounded text-dark fs-6">
                            {{ $sheet->specific_objective }}
                        </div>
                    </div>

                    @if($sheet->expected_results)
                        <div class="mb-4">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Résultats spécifiques attendus</div>
                            <div class="p-3 bg-light rounded text-secondary">
                                {{ $sheet->expected_results }}
                            </div>
                        </div>
                    @endif

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small text-uppercase fw-semibold mb-1">Responsable de mission</div>
                                <div class="fw-bold">{{ $sheet->responsible ? $sheet->responsible->name : 'Non assigné' }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small text-uppercase fw-semibold mb-1">Chef de mission / Superviseur</div>
                                <div class="fw-bold">{{ $sheet->missionLeader ? $sheet->missionLeader->name : 'Non assigné' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small text-uppercase fw-semibold mb-1">Démarrage prévisionnel</div>
                                <div class="fw-bold">{{ $sheet->planned_start_date ? $sheet->planned_start_date->format('d/m/Y') : 'Non défini' }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small text-uppercase fw-semibold mb-1">Fin prévisionnelle</div>
                                <div class="fw-bold">{{ $sheet->planned_end_date ? $sheet->planned_end_date->format('d/m/Y') : 'Non définie' }}</div>
                            </div>
                        </div>
                    </div>

                    @if($sheet->execution_conditions)
                        <div class="mb-3">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Conditions particulières d'exécution</div>
                            <div class="text-secondary small">{{ $sheet->execution_conditions }}</div>
                        </div>
                    @endif

                    @if($sheet->operational_constraints)
                        <div class="mb-3">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Contraintes opérationnelles</div>
                            <div class="text-secondary small">{{ $sheet->operational_constraints }}</div>
                        </div>
                    @endif

                    @if($sheet->additional_observations)
                        <div class="mb-0">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Observations complémentaires</div>
                            <div class="text-secondary small">{{ $sheet->additional_observations }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Historique et traçabilité de provenance --}}
            @if(!empty($sheet->field_provenance))
                <div class="card border shadow-sm">
                    <div class="card-header bg-light py-3 border-bottom">
                        <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                            <x-icon name="clock" size="18" />
                            Traçabilité des données héritées & adaptations
                        </h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="ps-3">Élément</th>
                                        <th scope="col">Origine</th>
                                        <th scope="col">Statut</th>
                                        <th scope="col" class="pe-3">Observation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sheet->field_provenance as $field => $prov)
                                        @if(($prov['origin'] ?? '') === 'inherited')
                                            <tr>
                                                <td class="ps-3 fw-semibold">{{ ucfirst(str_replace('_', ' ', $field)) }}</td>
                                                <td><span class="badge bg-light text-dark border">Hérité</span></td>
                                                <td>
                                                    @if(!empty($prov['is_modified']))
                                                        <span class="badge bg-warning text-dark">Adapté pour la mission</span>
                                                    @else
                                                        <span class="badge bg-success">Conforme à l'original</span>
                                                    @endif
                                                </td>
                                                <td class="pe-3 small text-muted">
                                                    @if(!empty($prov['is_modified']))
                                                        Valeur d'origine conservée dans le snapshot
                                                    @else
                                                        Issu du cadrage ou de la compréhension client
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Colonne droite : Cadre hérité du client & Articulation méthodologique --}}
        <div class="col-12 col-lg-5">
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-light py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                        <x-icon name="user" size="18" />
                        Cadre issu de la demande client
                    </h2>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Client</div>
                        <div class="fw-bold">{{ $sheet->inherited_data['client']['name'] ?? 'Non spécifié' }}</div>
                        @if(!empty($sheet->inherited_data['client']['sector']))
                            <div class="text-muted small">{{ $sheet->inherited_data['client']['sector'] }}</div>
                        @endif
                    </div>

                    @if(!empty($sheet->inherited_data['organization_context']))
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Contexte de l'organisation</div>
                            <div class="small text-secondary">{{ $sheet->inherited_data['organization_context'] }}</div>
                        </div>
                    @endif

                    @if(!empty($sheet->inherited_data['expressed_need']))
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Besoin initial exprimé</div>
                            <div class="small text-secondary">{{ $sheet->inherited_data['expressed_need'] }}</div>
                        </div>
                    @endif

                    @if(!empty($sheet->inherited_data['identified_problem']))
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Problématique centrale identifiée</div>
                            <div class="small text-secondary">{{ $sheet->inherited_data['identified_problem'] }}</div>
                        </div>
                    @endif

                    @if(!empty($sheet->inherited_data['general_objectives']))
                        <div class="mb-0">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Objectifs généraux du cadrage validé</div>
                            <div class="small text-secondary">{{ $sheet->inherited_data['general_objectives'] }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border border-primary border-opacity-25 bg-primary bg-opacity-10 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-bold text-primary mb-2 d-flex align-items-center gap-2">
                        <x-icon name="layers" size="18" />
                        Alimentation du Diagnostic 360°
                    </h2>
                    <p class="small text-secondary mb-0">
                        Cette fiche constitue le cadre de travail de référence. Ses objectifs, contraintes et données d'organisation alimenteront directement le <strong>Diagnostic Scope Canvas™</strong> et le <strong>Diagnostic Intake Register™</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
