@extends('layouts.app')

@section('title', 'Cadrage Opérationnel - ' . $mission->title)

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header mb-4">
        <div class="ba-eyebrow">DIAGNOSTIC 360° &bull; OUTIL 01</div>
        <h1 class="page-title mb-1">Mission Framing Sheet™<span class="ba-dot">.</span></h1>
        <p class="page-subtitle">
            Formalisation du cadre de référence opérationnel pour la mission <strong>{{ $mission->reference ?? $mission->title }}</strong>.
        </p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <div class="fw-semibold mb-2">Veuillez corriger les informations suivantes :</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('missions.framing-sheet.store', $mission) }}" method="POST">
        @csrf

        <div class="row g-4">
            {{-- Colonne gauche : Informations héritées (Demande & Cadrage validé) --}}
            <div class="col-12 col-lg-5">
                <div class="card border shadow-sm mb-4">
                    <div class="card-header bg-light py-3 border-bottom">
                        <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                            <x-icon name="clipboard" size="18" />
                            Cadre hérité du client & de la demande
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Client</div>
                            <div class="fw-bold fs-6">{{ $data['inherited_data']['client']['name'] ?? 'Non spécifié' }}</div>
                            @if(!empty($data['inherited_data']['client']['sector']))
                                <div class="text-muted small">{{ $data['inherited_data']['client']['sector'] }}</div>
                            @endif
                        </div>

                        <div class="mb-3 pb-3 border-bottom">
                            <label for="inherited_organization_context" class="form-label fw-semibold">Contexte de l'organisation</label>
                            <textarea id="inherited_organization_context" name="inherited_data[organization_context]" class="form-control" rows="3">{{ old('inherited_data.organization_context', $data['inherited_data']['organization_context'] ?? '') }}</textarea>
                            <div class="form-text small">Issu de la compréhension initiale. Modifiable avec traçabilité d'historique.</div>
                        </div>

                        <div class="mb-3 pb-3 border-bottom">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Besoin exprimé initial</div>
                            <div class="p-2 bg-light rounded text-secondary small">
                                {{ $data['inherited_data']['expressed_need'] ?? 'Aucun besoin exprimé consigné.' }}
                            </div>
                        </div>

                        <div class="mb-3 pb-3 border-bottom">
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Problématique / Question centrale</div>
                            <div class="p-2 bg-light rounded text-secondary small">
                                {{ $data['inherited_data']['identified_problem'] ?? 'Non consignée.' }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="inherited_general_objectives" class="form-label fw-semibold">Objectifs généraux du cadrage validé</label>
                            <textarea id="inherited_general_objectives" name="inherited_data[general_objectives]" class="form-control" rows="3">{{ old('inherited_data.general_objectives', $data['inherited_data']['general_objectives'] ?? '') }}</textarea>
                            <div class="form-text small">Engagements contractuels de référence pris avec le client.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Colonne droite : Paramètres propres à la mission --}}
            <div class="col-12 col-lg-7">
                <div class="card border shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                            <x-icon name="briefcase" size="18" />
                            Cadre opérationnel propre à la mission
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Intitulé opérationnel <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $data['specific_data']['title'] ?? $mission->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="specific_objective" class="form-label fw-semibold">Objectif spécifique de la mission <span class="text-danger">*</span></label>
                            <textarea id="specific_objective" name="specific_objective" class="form-control @error('specific_objective') is-invalid @enderror" rows="3" required placeholder="Ex: Analyser les 4 axes de rentabilité et identifier les pertes de marge">{{ old('specific_objective', $data['specific_data']['specific_objective'] ?? $mission->objective) }}</textarea>
                            @error('specific_objective')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="expected_results" class="form-label fw-semibold">Résultats opérationnels spécifiques attendus</label>
                            <textarea id="expected_results" name="expected_results" class="form-control @error('expected_results') is-invalid @enderror" rows="2" placeholder="Ex: Matrice des coûts par produit, synthèse de restitution et plan d'action">{{ old('expected_results', $data['specific_data']['expected_results'] ?? '') }}</textarea>
                            @error('expected_results')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="responsible_id" class="form-label fw-semibold">Responsable de mission</label>
                                <select id="responsible_id" name="responsible_id" class="form-select @error('responsible_id') is-invalid @enderror">
                                    <option value="">-- Sélectionner un utilisateur --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('responsible_id', $data['specific_data']['responsible_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('responsible_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="mission_leader_id" class="form-label fw-semibold">Chef de mission / Superviseur</label>
                                <select id="mission_leader_id" name="mission_leader_id" class="form-select @error('mission_leader_id') is-invalid @enderror">
                                    <option value="">-- Sélectionner un superviseur --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('mission_leader_id', $data['specific_data']['mission_leader_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('mission_leader_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="planned_start_date" class="form-label fw-semibold">Date de démarrage prévisionnelle</label>
                                <input type="date" id="planned_start_date" name="planned_start_date" class="form-control @error('planned_start_date') is-invalid @enderror" value="{{ old('planned_start_date', $data['specific_data']['planned_start_date'] ?? '') }}">
                                @error('planned_start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="planned_end_date" class="form-label fw-semibold">Date de fin prévisionnelle</label>
                                <input type="date" id="planned_end_date" name="planned_end_date" class="form-control @error('planned_end_date') is-invalid @enderror" value="{{ old('planned_end_date', $data['specific_data']['planned_end_date'] ?? '') }}">
                                @error('planned_end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="execution_conditions" class="form-label fw-semibold">Conditions particulières d'exécution</label>
                            <textarea id="execution_conditions" name="execution_conditions" class="form-control @error('execution_conditions') is-invalid @enderror" rows="2" placeholder="Ex: Interventions sur site les mardis et jeudis matin">{{ old('execution_conditions', $data['specific_data']['execution_conditions'] ?? '') }}</textarea>
                            @error('execution_conditions')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="operational_constraints" class="form-label fw-semibold">Contraintes opérationnelles</label>
                            <textarea id="operational_constraints" name="operational_constraints" class="form-control @error('operational_constraints') is-invalid @enderror" rows="2" placeholder="Ex: Disponibilité restreinte du directeur financier, confidentialité accrue">{{ old('operational_constraints', $data['specific_data']['operational_constraints'] ?? '') }}</textarea>
                            @error('operational_constraints')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="additional_observations" class="form-label fw-semibold">Observations complémentaires</label>
                            <textarea id="additional_observations" name="additional_observations" class="form-control @error('additional_observations') is-invalid @enderror" rows="2" placeholder="Notes et consignes utiles à l'équipe de consultants">{{ old('additional_observations', $data['specific_data']['additional_observations'] ?? '') }}</textarea>
                            @error('additional_observations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <x-form-actions :cancel-url="route('missions.show', $mission)" submit-label="Enregistrer le cadrage opérationnel" />
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
