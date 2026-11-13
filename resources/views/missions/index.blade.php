@extends('layouts.app')

@section('title', 'Missions & Engagements - CoreAssist')

@section('content')
<div class="container py-4">
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <div class="ba-eyebrow">PORTEFEUILLE D'ENGAGEMENTS</div>
            <h1 class="page-title mb-1">
                <x-icon name="briefcase" size="24" /> Missions & Engagements<span class="ba-dot">.</span>
            </h1>
            <p class="page-subtitle">Suivi des missions, avancement des dossiers et charge hebdomadaire mobilisée.</p>
        </div>
        <div class="w-100 w-md-auto">
            <a href="{{ route('missions.create') }}" class="btn btn-primary w-100">
                <x-icon name="plus" size="14" /> Nouvelle Mission
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <!-- HUB DE VIGILANCE (Affiché si des alertes existent) -->
    @if(!empty($alertsSummary['has_alerts']) && !empty($activeAlerts))
        <div class="vigilance-card mb-4">
            <div class="vigilance-header">
                <div class="vigilance-title">
                    <span><x-icon name="alert-triangle" size="18" /> Centre de Vigilance Cabinet</span>
                    <span class="badge badge-overdue">{{ $alertsSummary['total_alerts'] }} alerte(s) active(s)</span>
                </div>
                <div style="font-size: 0.85rem; color: var(--color-ink-muted);">
                    <span style="color: var(--color-rust); font-weight: 600;">{{ $alertsSummary['total_critical'] }} critique(s)</span> -
                    <span style="color: var(--color-ochre); font-weight: 600;">{{ $alertsSummary['total_warnings'] }} avertissement(s)</span>
                </div>
            </div>

            <div class="vigilance-grid">
                @foreach(array_slice($activeAlerts, 0, 4) as $alert)
                    <div class="vigilance-item {{ $alert['level'] === 'critical' ? 'vigilance-item-critical' : 'vigilance-item-warning' }}">
                        <div style="min-width: 0;">
                            <div style="font-size: 0.9rem; font-weight: 700; color: var(--color-ink-display); margin-bottom: 0.2rem; display: flex; align-items: center; gap: 0.4rem;">
                                <x-icon :name="$alert['icon']" size="15" />
                                <span>{{ $alert['title'] }}</span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--color-ink-secondary);">
                                {{ $alert['detail'] }}
                            </div>
                        </div>
                        <a href="{{ $alert['url'] }}" class="btn btn-secondary btn-sm" style="white-space: nowrap; font-size: 0.8rem; padding: 0.3rem 0.6rem;">
                            {{ $alert['action_label'] }}
                        </a>
                    </div>
                @endforeach
            </div>

            @if(count($activeAlerts) > 4)
                <div style="margin-top: 1rem; text-align: right;">
                    <a href="{{ route('missions.index', ['filter' => 'alerts']) }}" style="font-size: 0.85rem; color: var(--color-rust); font-weight: 600; text-decoration: none;">
                        &rarr; Voir toutes les {{ count($activeAlerts) }} alertes filtrées
                    </a>
                </div>
            @endif
        </div>
    @endif

    <!-- Indicateurs clés du portefeuille -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Missions Actives</div>
                    <div class="stat-value stat-value-primary">{{ $summary['active_missions'] }} <span style="font-size: 0.9rem; color: var(--ba-graphite-500); font-weight: 400;">/ {{ $summary['total_missions'] }} au total</span></div>
                    <div class="text-muted small mt-1">Dossiers en cours de réalisation</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Charge Hebdomadaire Engagée</div>
                    <div class="stat-value stat-value-warning">{{ number_format($summary['total_weekly_hours'], 1) }} h/sem</div>
                    <div class="text-muted small mt-1">Volume horaire d'équipe mobilisé</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Interventions en Cours</div>
                    <div class="stat-value stat-value-success">{{ $summary['active_tasks'] }} <span style="font-size: 0.9rem; color: var(--ba-graphite-500); font-weight: 400;">tâches actives</span></div>
                    <div class="text-muted small mt-1">Affectations de travail en cours</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">État de Santé des Délais</div>
                    @if(!empty($alertsSummary['overdue_missions_count']) && $alertsSummary['overdue_missions_count'] > 0)
                        <div class="stat-value" style="color: var(--ba-error-strong); font-size: clamp(1.2rem, 1.8vw, 1.5rem); white-space: nowrap;">
                            {{ $alertsSummary['overdue_missions_count'] }} en retard
                        </div>
                        <div class="small mt-1" style="color: var(--ba-error-strong); font-weight: 500;">Action requise sur les échéances</div>
                    @else
                        <div class="stat-value stat-value-success" style="font-size: clamp(1.2rem, 1.8vw, 1.5rem); white-space: nowrap;">100% à l'heure</div>
                        <div class="small mt-1" style="color: var(--ba-success); font-weight: 500;">Aucun retard de mission détecté</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @php
        $filterCounts = [
            '' => $summary['total_missions'],
            'alerts' => $alertsSummary['overdue_missions_count'] + $alertsSummary['at_risk_missions_count'],
            'in_progress' => $summary['active_missions'],
            'completed' => $summary['completed_missions'],
        ];
        $filterLabels = [
            '' => 'Toutes les missions',
            'alerts' => 'En alerte',
            'in_progress' => 'En cours',
            'completed' => 'Terminées',
        ];
    @endphp

    <!-- Pattern Desktop : Barre de filtres segmentée (>= 768px) -->
    <div class="filter-bar mb-4 d-none d-md-flex">
        <a href="{{ route('missions.index') }}" class="filter-pill {{ empty($filter) ? 'active' : '' }}">
            Toutes les missions ({{ $summary['total_missions'] }})
        </a>
        <a href="{{ route('missions.index', ['filter' => 'alerts']) }}" class="filter-pill filter-pill-alert {{ $filter === 'alerts' ? 'active' : '' }}">
            <x-icon name="alert-circle" size="13" /> En alerte ({{ $filterCounts['alerts'] }})
        </a>
        <a href="{{ route('missions.index', ['filter' => 'in_progress']) }}" class="filter-pill {{ $filter === 'in_progress' ? 'active' : '' }}">
            En cours ({{ $summary['active_missions'] }})
        </a>
        <a href="{{ route('missions.index', ['filter' => 'completed']) }}" class="filter-pill {{ $filter === 'completed' ? 'active' : '' }}">
            Terminées ({{ $summary['completed_missions'] }})
        </a>
    </div>

    <!-- Pattern Mobile : Déclencheur Bottom Sheet sous le pouce (< 768px) -->
    <div class="d-flex d-md-none justify-content-between align-items-center mb-3">
        <button class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileFilterDrawer" aria-controls="mobileFilterDrawer">
            <x-icon name="filter" size="14" />
            <span>Filtre : <strong>{{ $filterLabels[$filter ?? ''] ?? 'Toutes' }}</strong></span>
            <span class="badge-pill-count ms-1">{{ $filterCounts[$filter ?? ''] ?? $summary['total_missions'] }}</span>
        </button>
        @if(!empty($filter))
            <a href="{{ route('missions.index') }}" class="text-muted small text-decoration-none">
                Réinitialiser
            </a>
        @endif
    </div>

    <!-- Bottom Sheet Mobile pour les Filtres -->
    <div class="offcanvas offcanvas-bottom" tabindex="-1" id="mobileFilterDrawer" aria-labelledby="mobileFilterDrawerLabel">
        <div class="offcanvas-header border-bottom py-3 px-4">
            <h5 class="offcanvas-title h6 fw-bold mb-0 d-flex align-items-center gap-2" id="mobileFilterDrawerLabel">
                <x-icon name="filter" size="18" /> Filtrer le portefeuille
            </h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
        </div>
        <div class="offcanvas-body p-3">
            <div class="list-group list-group-flush mb-3">
                <a href="{{ route('missions.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 {{ empty($filter) ? 'active bg-light text-dark fw-bold' : '' }}">
                    <span>Toutes les missions</span>
                    <span class="badge bg-light text-dark border">{{ $summary['total_missions'] }}</span>
                </a>
                <a href="{{ route('missions.index', ['filter' => 'alerts']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 {{ $filter === 'alerts' ? 'active bg-danger-subtle text-danger fw-bold' : '' }}">
                    <span class="d-flex align-items-center gap-2 text-danger">
                        <x-icon name="alert-circle" size="14" /> En alerte
                    </span>
                    <span class="badge bg-danger text-white">{{ $filterCounts['alerts'] }}</span>
                </a>
                <a href="{{ route('missions.index', ['filter' => 'in_progress']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 {{ $filter === 'in_progress' ? 'active bg-light text-dark fw-bold' : '' }}">
                    <span>En cours</span>
                    <span class="badge bg-light text-dark border">{{ $summary['active_missions'] }}</span>
                </a>
                <a href="{{ route('missions.index', ['filter' => 'completed']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 {{ $filter === 'completed' ? 'active bg-light text-dark fw-bold' : '' }}">
                    <span>Terminées</span>
                    <span class="badge bg-light text-dark border">{{ $summary['completed_missions'] }}</span>
                </a>
            </div>
            <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="offcanvas">Fermer</button>
        </div>
    </div>

    @if($missions->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                <x-icon name="briefcase" size="24" />
            </div>
            <div class="empty-state-title">Aucune mission trouvée</div>
            <p class="empty-state-text">
                @if($filter === 'alerts')
                    Excellente nouvelle : aucune mission n'est actuellement en retard ou à risque.
                @elseif($filter === 'in_progress')
                    Aucune mission actuellement en cours dans le portefeuille.
                @elseif($filter === 'completed')
                    Aucune mission terminée pour le moment.
                @else
                    Aucun dossier ne correspond aux critères de filtre appliqués.
                @endif
            </p>
            <div class="empty-state-actions">
                <a href="{{ route('missions.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" size="14" /> Nouvelle Mission
                </a>
                <a href="{{ route('missions.index') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser les filtres</a>
            </div>
        </div>
    @else
        @php
            $statusLabels = [
                'draft' => 'Brouillon',
                'in_progress' => 'En cours',
                'completed' => 'Terminée',
                'on_hold' => 'En pause',
            ];
        @endphp

        <!-- Design Pattern Mobile : Cartes empilées pour écrans tactiles (< 768px) -->
        <div class="mobile-card-list d-md-none">
            @foreach($missions as $mission)
                @php
                    $progress = $mission->progressPercentage();
                    $isOverdue = $mission->isOverdue();
                    $isAtRisk = $mission->isAtRisk();
                @endphp
                <div class="mobile-data-card {{ $isOverdue ? 'border-danger' : '' }}">
                    <div class="mobile-data-card-header">
                        <div>
                            <a href="{{ route('missions.show', $mission) }}" class="mobile-data-card-title">
                                {{ $mission->title }}
                            </a>
                            @if($mission->description)
                                <div class="text-muted small mt-1">{{ Str::limit($mission->description, 70) }}</div>
                            @endif
                        </div>
                        <div class="d-flex flex-column gap-1 align-items-end flex-shrink-0">
                            <span class="status-pill status-{{ $mission->status }}">
                                {{ $statusLabels[$mission->status] ?? $mission->status }}
                            </span>
                            @if($isOverdue)
                                <span class="badge badge-overdue">
                                    <x-icon name="alert-circle" size="10" /> Retard (+{{ $mission->daysOverdue() }}j)
                                </span>
                            @elseif($isAtRisk)
                                <span class="badge badge-at-risk">
                                    <x-icon name="alert-triangle" size="10" /> Risque dérive
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mobile-data-grid">
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Responsable</span>
                            <span class="mobile-data-value text-truncate">
                                {{ $mission->manager ? $mission->manager->name : 'Non assigné' }}
                            </span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Échéance</span>
                            <span class="mobile-data-value {{ $isOverdue ? 'text-danger' : '' }}">
                                {{ $mission->due_date ? $mission->due_date->format('d/m/Y') : 'Non définie' }}
                            </span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Charge Hebdo</span>
                            <span class="mobile-data-value">{{ number_format($mission->totalWeeklyHours(), 1) }} h/sem</span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Tâches</span>
                            <span class="mobile-data-value">{{ $mission->tasks->where('status', 'done')->count() }} / {{ $mission->tasks->count() }} faites</span>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between text-muted small mb-1">
                            <span>Avancement</span>
                            <strong class="text-dark">{{ $progress }}%</strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: {{ $progress }}%;"></div>
                        </div>
                    </div>

                    <a href="{{ route('missions.show', $mission) }}" class="btn btn-outline-secondary btn-sm w-100">
                        Consulter la mission &rarr;
                    </a>
                </div>
            @endforeach
        </div>

        <!-- Design Pattern Desktop : Tableau avec première colonne collante (> 768px) -->
        <div class="card shadow-sm border mb-4 d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover table-sticky-first align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Mission</th>
                            <th>Responsable</th>
                            <th>Statut & Alertes</th>
                            <th>Charge Hebdo</th>
                            <th style="min-width: 180px;">Progression</th>
                            <th>Échéance</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($missions as $mission)
                            @php
                                $progress = $mission->progressPercentage();
                                $isOverdue = $mission->isOverdue();
                                $isAtRisk = $mission->isAtRisk();
                            @endphp
                            <tr class="{{ $isOverdue ? 'table-danger-subtle' : '' }}">
                                <td class="ps-4">
                                    <div>
                                        <a href="{{ route('missions.show', $mission) }}" class="fw-semibold text-decoration-none text-dark">
                                            {{ $mission->title }}
                                        </a>
                                    </div>
                                    @if($mission->description)
                                        <div class="text-muted small mt-1">{{ Str::limit($mission->description, 60) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($mission->manager)
                                        <div class="fw-medium">{{ $mission->manager->name }}</div>
                                        <div class="text-muted small">{{ $mission->manager->role_title }}</div>
                                    @else
                                        <span class="text-muted small">Non assigné</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1 align-items-start">
                                        <span class="status-pill status-{{ $mission->status }}">
                                            {{ $statusLabels[$mission->status] ?? $mission->status }}
                                        </span>
                                        @if($isOverdue)
                                            <span class="badge badge-overdue">
                                                <x-icon name="alert-circle" size="12" /> Retard (+{{ $mission->daysOverdue() }}j)
                                            </span>
                                        @elseif($isAtRisk)
                                            <span class="badge badge-at-risk">
                                                <x-icon name="alert-triangle" size="12" /> Risque dérive
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ number_format($mission->totalWeeklyHours(), 1) }} h/sem</span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between text-muted small mb-1">
                                        <span>{{ $mission->tasks->where('status', 'done')->count() }} / {{ $mission->tasks->count() }} tâches</span>
                                        <span class="fw-semibold">{{ $progress }}%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>
                                    @if($mission->due_date)
                                        <span class="{{ $isOverdue ? 'text-danger fw-semibold' : 'text-secondary' }}">
                                            {{ $mission->due_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('missions.show', $mission) }}" class="btn btn-outline-secondary btn-sm">Consulter &rarr;</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
