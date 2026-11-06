@extends('layouts.app')

@section('title', 'Missions & Projets - Cabinet')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <x-icon name="briefcase" size="24" /> Missions & Engagements
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Suivi des missions, avancement des dossiers et charge hebdomadaire mobilisée.</p>
        </div>
        <div>
            <a href="{{ route('missions.create') }}" class="btn btn-primary">
                <x-icon name="plus" size="14" /> Nouvelle Mission
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- HUB DE VIGILANCE (Affiché si des alertes existent) -->
    @if(!empty($alertsSummary['has_alerts']) && !empty($activeAlerts))
        <div class="vigilance-card">
            <div class="vigilance-header">
                <div class="vigilance-title">
                    <span><x-icon name="alert-triangle" size="18" /> Centre de Vigilance Cabinet</span>
                    <span class="badge badge-overdue">{{ $alertsSummary['total_alerts'] }} alerte(s) active(s)</span>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    <span style="color: #fb7185;">{{ $alertsSummary['total_critical'] }} critique(s)</span> &bull;
                    <span style="color: #facc15;">{{ $alertsSummary['total_warnings'] }} avertissement(s)</span>
                </div>
            </div>

            <div class="vigilance-grid">
                @foreach(array_slice($activeAlerts, 0, 4) as $alert)
                    <div class="vigilance-item {{ $alert['level'] === 'critical' ? 'vigilance-item-critical' : 'vigilance-item-warning' }}">
                        <div style="min-width: 0;">
                            <div style="font-size: 0.9rem; font-weight: 600; color: #fff; margin-bottom: 0.2rem; display: flex; align-items: center; gap: 0.4rem;">
                                <x-icon :name="$alert['icon']" size="15" />
                                <span>{{ $alert['title'] }}</span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
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
                    <a href="{{ route('missions.index', ['filter' => 'alerts']) }}" style="font-size: 0.85rem; color: #fb7185; text-decoration: none;">
                        &rarr; Voir toutes les {{ count($activeAlerts) }} alertes filtrées
                    </a>
                </div>
            @endif
        </div>
    @endif

    <!-- Indicateurs clés du portefeuille -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Missions Actives</div>
            <div class="stat-value stat-value-primary">{{ $summary['active_missions'] }} <span style="font-size: 0.9rem; color: var(--text-muted);">/ {{ $summary['total_missions'] }} au total</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Charge Hebdomadaire Engagée</div>
            <div class="stat-value stat-value-warning">{{ number_format($summary['total_weekly_hours'], 1) }} h/sem</div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Volume horaire d'équipe mobilisé par semaine</p>
        </div>
        <div class="stat-card">
            <div class="stat-label">Interventions en Cours</div>
            <div class="stat-value stat-value-success">{{ $summary['active_tasks'] }} <span style="font-size: 0.9rem; color: var(--text-muted);">tâches actives</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">État de Santé des Délais</div>
            @if(!empty($alertsSummary['overdue_missions_count']) && $alertsSummary['overdue_missions_count'] > 0)
                <div class="stat-value" style="color: #fb7185;">
                    {{ $alertsSummary['overdue_missions_count'] }} en retard
                </div>
                <p style="color: #fb7185; font-size: 0.85rem; margin-top: 0.5rem;">Action requise sur les échéances</p>
            @else
                <div class="stat-value stat-value-success">100% à l'heure</div>
                <p style="color: #4ade80; font-size: 0.85rem; margin-top: 0.5rem;">Aucun retard de mission détecté</p>
            @endif
        </div>
    </div>

    <!-- Barre de filtres rapides -->
    <div class="filter-bar">
        <a href="{{ route('missions.index') }}" class="filter-pill {{ empty($filter) ? 'active' : '' }}">
            Toutes les missions ({{ $summary['total_missions'] }})
        </a>
        <a href="{{ route('missions.index', ['filter' => 'alerts']) }}" class="filter-pill filter-pill-alert {{ $filter === 'alerts' ? 'active' : '' }}">
            <x-icon name="alert-circle" size="13" /> En alerte ({{ $alertsSummary['overdue_missions_count'] + $alertsSummary['at_risk_missions_count'] }})
        </a>
        <a href="{{ route('missions.index', ['filter' => 'in_progress']) }}" class="filter-pill {{ $filter === 'in_progress' ? 'active' : '' }}">
            En cours ({{ $summary['active_missions'] }})
        </a>
        <a href="{{ route('missions.index', ['filter' => 'completed']) }}" class="filter-pill {{ $filter === 'completed' ? 'active' : '' }}">
            Terminées ({{ $summary['completed_missions'] }})
        </a>
    </div>

    <div class="table-container">
        @if($missions->isEmpty())
            <div class="empty-state">
                <p style="font-size: 1.1rem; margin-bottom: 1rem;">
                    @if($filter === 'alerts')
                        Excellente nouvelle : aucune mission n'est actuellement en retard ou à risque.
                    @else
                        Aucune mission trouvée pour ce filtre.
                    @endif
                </p>
                <a href="{{ route('missions.index') }}" class="btn btn-secondary btn-sm">Réinitialiser les filtres</a>
            </div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Mission</th>
                        <th>Responsable</th>
                        <th>Statut & Alertes</th>
                        <th>Charge Hebdo</th>
                        <th style="width: 25%;">Progression</th>
                        <th>Échéance</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($missions as $mission)
                        @php
                            $progress = $mission->progressPercentage();
                            $statusLabels = [
                                'draft' => 'Brouillon',
                                'in_progress' => 'En cours',
                                'completed' => 'Terminée',
                                'on_hold' => 'En pause',
                            ];
                            $isOverdue = $mission->isOverdue();
                            $isAtRisk = $mission->isAtRisk();
                        @endphp
                        <tr style="{{ $isOverdue ? 'background-color: rgba(244, 63, 94, 0.05);' : '' }}">
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <a href="{{ route('missions.show', $mission) }}" style="color: var(--text-primary); text-decoration: none; font-weight: 600;">
                                        {{ $mission->title }}
                                    </a>
                                </div>
                                @if($mission->description)
                                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">{{ Str::limit($mission->description, 60) }}</p>
                                @endif
                            </td>
                            <td>
                                @if($mission->manager)
                                    <span>{{ $mission->manager->name }}</span>
                                    <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">{{ $mission->manager->role_title }}</span>
                                @else
                                    <span style="color: var(--text-muted);">Non assigné</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.35rem; align-items: flex-start;">
                                    <span class="status-pill status-{{ $mission->status }}">
                                        {{ $statusLabels[$mission->status] ?? $mission->status }}
                                    </span>
                                    @if($isOverdue)
                                        <span class="badge badge-overdue" style="font-size: 0.75rem; padding: 0.15rem 0.5rem;">
                                            <x-icon name="alert-circle" size="12" /> Retard (+{{ $mission->daysOverdue() }}j)
                                        </span>
                                    @elseif($isAtRisk)
                                        <span class="badge badge-at-risk" style="font-size: 0.75rem; padding: 0.15rem 0.5rem;">
                                            <x-icon name="alert-triangle" size="12" /> Risque dérive
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <strong>{{ number_format($mission->totalWeeklyHours(), 1) }} h/sem</strong>
                            </td>
                            <td>
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted);">
                                    <span>{{ $mission->tasks->where('status', 'done')->count() }} / {{ $mission->tasks->count() }} tâches</span>
                                    <span>{{ $progress }}%</span>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: {{ $progress }}%;"></div>
                                </div>
                            </td>
                            <td>
                                @if($mission->due_date)
                                    <span style="color: {{ $isOverdue ? '#fb7185' : 'inherit' }}; font-weight: {{ $isOverdue ? '600' : 'normal' }};">
                                        {{ $mission->due_date->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span style="color: var(--text-muted);">-</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('missions.show', $mission) }}" class="btn btn-secondary btn-sm">Ouvrir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
