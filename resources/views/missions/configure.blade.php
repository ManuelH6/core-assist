@extends('layouts.app')

@section('title', 'Configuration des Outils & Autorisations - ' . $mission->title)

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header mb-4">
        <div class="ba-eyebrow">PARAMÉTRAGE DE MISSION &bull; {{ $mission->reference ?? 'MISSION #' . $mission->id }}</div>
        <h1 class="page-title mb-1">Outils, Équipe &amp; Autorisations<span class="ba-dot">.</span></h1>
        <p class="page-subtitle">
            Activez uniquement les outils nécessaires à <strong>{{ $mission->title }}</strong> et définissez précisément ce que chaque rôle est autorisé à faire.
        </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
    @endif

    <form action="{{ route('missions.configuration.update', $mission) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Section 1 : Composition de l'équipe de mission --}}
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h2 class="h6 mb-0 fw-bold">1. Équipe affectée à la mission</h2>
                <p class="small text-muted mb-0">Seuls les membres sélectionnés (et le DG/Manager) pourront accéder aux outils activés selon leur rôle.</p>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    @foreach($cabinetUsers as $u)
                        @php
                            $existingMember = $activeMembers->get($u->id);
                            $defaultRole = $existingMember?->mission_role?->value ?? match($u->role?->value) {
                                'dg', 'manager' => 'manager',
                                'mission_lead' => 'mission_lead',
                                'expert' => 'contributor',
                                default => 'consultant',
                            };
                        @endphp
                        <div class="col-12 col-md-6">
                            <div class="border rounded p-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                                <div class="form-check mb-0">
                                    <input type="hidden" name="members[{{ $u->id }}][user_id]" value="{{ $u->id }}">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="member-{{ $u->id }}"
                                        name="members[{{ $u->id }}][selected]"
                                        value="1"
                                        {{ $existingMember ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label fw-semibold" for="member-{{ $u->id }}">
                                        {{ $u->name }}
                                        <span class="d-block small text-muted fw-normal">{{ $u->role?->label() }} ({{ $u->email }})</span>
                                    </label>
                                </div>
                                <div style="min-width: 170px;">
                                    <select name="members[{{ $u->id }}][mission_role]" class="form-select form-select-sm" aria-label="Rôle sur la mission pour {{ $u->name }}">
                                        @foreach($roles as $roleKey => $roleLabel)
                                            <option value="{{ $roleKey }}" {{ $defaultRole === $roleKey ? 'selected' : '' }}>
                                                {{ $roleLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Section 2 : Paramétrage et permissions par outil --}}
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h2 class="h6 mb-0 fw-bold">2. Boîte à outils activée &amp; matrice d'autorisations</h2>
                <p class="small text-muted mb-0">Décochez un outil pour le masquer de la mission. Réglez les rôles et actions autorisés pour chaque outil activé.</p>
            </div>
            <div class="card-body p-3">
                @foreach($toolMatrix as $row)
                    @php
                        $tool = $row['tool'];
                        $config = $row['configuration'];
                        $enabled = $row['enabled'];
                        $specificRules = $row['specific_rules'];
                    @endphp

                    <div class="border rounded p-3 mb-3 {{ $enabled ? 'bg-white' : 'bg-light' }}">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 pb-3 border-bottom">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary text-white font-monospace">{{ $tool->code }}</span>
                                    <h3 class="h6 fw-bold mb-0">{{ $tool->name }}</h3>
                                </div>
                                <p class="small text-muted mb-0 mt-1">{{ $tool->description }}</p>
                            </div>
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="tool-enabled-{{ $tool->code }}"
                                    name="tools[{{ $tool->code }}][enabled]"
                                    value="1"
                                    {{ $enabled ? 'checked' : '' }}
                                >
                                <label class="form-check-label fw-semibold small" for="tool-enabled-{{ $tool->code }}">
                                    Activé sur la mission
                                </label>
                            </div>
                        </div>

                        <div class="row g-3 pt-3">
                            <div class="col-12 col-lg-4">
                                <div class="small fw-bold text-dark mb-2">Rôles autorisés sur cet outil</div>
                                @foreach($roles as $roleKey => $roleLabel)
                                    <div class="form-check mb-1">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="role-{{ $tool->code }}-{{ $roleKey }}"
                                            name="tools[{{ $tool->code }}][allowed_roles][]"
                                            value="{{ $roleKey }}"
                                            {{ in_array($roleKey, $config['allowed_roles'] ?? [], true) ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label small" for="role-{{ $tool->code }}-{{ $roleKey }}">
                                            {{ $roleLabel }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="col-12 col-lg-8">
                                <div class="small fw-bold text-dark mb-2">Actions autorisées par rôle</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="small">Action</th>
                                                @foreach($roles as $roleKey => $roleLabel)
                                                    <th class="small text-center">{{ $roleLabel }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($actions as $actionKey => $actionLabel)
                                                <tr>
                                                    <td class="small fw-semibold">{{ $actionLabel }}</td>
                                                    @foreach($roles as $roleKey => $roleLabel)
                                                        <td class="text-center">
                                                            <input
                                                                class="form-check-input"
                                                                type="checkbox"
                                                                name="tools[{{ $tool->code }}][permissions][{{ $actionKey }}][]"
                                                                value="{{ $roleKey }}"
                                                                aria-label="{{ $actionLabel }} - {{ $roleLabel }} ({{ $tool->code }})"
                                                                {{ in_array($roleKey, $config['permissions'][$actionKey] ?? [], true) ? 'checked' : '' }}
                                                            >
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if(!empty($specificRules))
                                    <div class="mt-3 pt-2 border-top">
                                        <div class="small fw-bold text-dark mb-2">Règles de fonctionnement sur la mission</div>
                                        @foreach($specificRules as $ruleKey => $meta)
                                            <div class="form-check mb-1">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    id="rule-{{ $tool->code }}-{{ $ruleKey }}"
                                                    name="tools[{{ $tool->code }}][rules][{{ $ruleKey }}]"
                                                    value="1"
                                                    {{ !empty($config['rules'][$ruleKey]) ? 'checked' : '' }}
                                                >
                                                <label class="form-check-label small" for="rule-{{ $tool->code }}-{{ $ruleKey }}">
                                                    {{ $meta['label'] }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card-footer bg-white p-3 border-top">
                <x-form-actions :cancel-url="route('missions.show', $mission)" submit-label="Enregistrer la configuration de mission" />
            </div>
        </div>
    </form>
</div>
@endsection
