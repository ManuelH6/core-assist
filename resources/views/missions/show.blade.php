@extends('layouts.app')

@section('title', $mission->title . ' - Détail de la Mission')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <div style="margin-bottom: 0.5rem;">
                <a href="{{ route('missions.index') }}" class="btn btn-secondary btn-sm">&larr; Toutes les missions</a>
            </div>
            <h1 class="page-title">{{ $mission->title }}</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Responsable : <strong>{{ $mission->manager ? $mission->manager->name : 'Non assigné' }}</strong>
                &bull; Période : {{ $mission->start_date ? $mission->start_date->format('d/m/Y') : 'Immédiat' }} &rarr; {{ $mission->due_date ? $mission->due_date->format('d/m/Y') : 'Non définie' }}
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <span class="status-pill status-{{ $mission->status }}">
                {{ strtoupper($mission->status) }}
            </span>
            <form action="{{ route('missions.destroy', $mission) }}" method="POST" onsubmit="return confirm('Confirmer la suppression de cette mission ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary btn-sm" style="color: #fb7185;">Supprimer</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($mission->isOverdue())
        <div class="alert alert-danger" style="display: flex; align-items: center; gap: 0.75rem;">
            <x-icon name="alert-circle" size="24" class="text-danger" />
            <div>
                <strong>Alerte Échéance Dépassée :</strong> Cette mission devait être livrée le {{ $mission->due_date->format('d/m/Y') }} et accuse un retard de <strong>{{ $mission->daysOverdue() }} jour(s)</strong>.
            </div>
        </div>
    @elseif($mission->isAtRisk())
        <div class="alert" style="background-color: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24; display: flex; align-items: center; gap: 0.75rem;">
            <x-icon name="alert-triangle" size="24" />
            <div>
                <strong>Risque de Dérive :</strong> L'échéance arrive dans {{ now()->diffInDays($mission->due_date) }} jour(s) alors que l'avancement n'est que de {{ $mission->progressPercentage() }}%.
            </div>
        </div>
    @endif

    @php
        $progress = $mission->progressPercentage();
        $totalWeekly = $mission->totalWeeklyHours();
    @endphp

    <!-- KPIs de la mission -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Avancement Global</div>
            <div class="stat-value stat-value-primary">{{ $progress }}%</div>
            <div class="progress-track" style="margin-top: 0.75rem;">
                <div class="progress-fill" style="width: {{ $progress }}%;"></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Staffing Hebdomadaire Alloué</div>
            <div class="stat-value stat-value-warning">{{ number_format($totalWeekly, 1) }} h/sem</div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Charge d'équipe mobilisée par semaine</p>
        </div>

        <div class="stat-card">
            <div class="stat-label">Tâches / Interventions</div>
            <div class="stat-value stat-value-success">
                {{ $mission->tasks->where('status', 'done')->count() }} / {{ $mission->tasks->count() }}
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">{{ $mission->tasks->whereIn('status', ['todo', 'doing'])->count() }} en cours</p>
        </div>

        @if($mission->budget)
            <div class="stat-card">
                <div class="stat-label">Budget Alloué</div>
                <div class="stat-value">{{ number_format($mission->budget, 0, ',', ' ') }} FCFA</div>
            </div>
        @endif
    </div>

    <!-- Section Interventions & Tâches -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 2rem;" class="mission-layout">
        <!-- Colonne Gauche : Liste des interventions -->
        <div>
            <h2 style="font-size: 1.35rem; margin-bottom: 1rem;">
                <x-icon name="clipboard" size="20" /> Interventions & Staffing sur le dossier ({{ $mission->tasks->count() }})
            </h2>

            <div class="table-container">
                @if($mission->tasks->isEmpty())
                    <div class="empty-state">
                        <p style="margin-bottom: 0.5rem;">Aucune intervention planifiée pour le moment.</p>
                        <p style="font-size: 0.85rem;">Utilisez le formulaire ci-contre pour allouer un collaborateur et ses heures par semaine.</p>
                    </div>
                @else
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Intervention</th>
                                <th>Collaborateur</th>
                                <th>Charge Hebdo</th>
                                <th>Priorité</th>
                                <th>Échéance & Alertes</th>
                                <th>Statut</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mission->tasks as $task)
                                @php
                                    $isTaskOverdue = $task->isOverdue();
                                @endphp
                                <tr style="{{ $isTaskOverdue ? 'background-color: rgba(244, 63, 94, 0.08);' : '' }}">
                                    <td>
                                        <strong>{{ $task->title }}</strong>
                                        @if($task->description)
                                            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.2rem;">{{ $task->description }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->collaborator)
                                            <a href="{{ route('collaborators.show', $task->collaborator) }}" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                                                {{ $task->collaborator->name }}
                                            </a>
                                            <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">{{ $task->collaborator->role_title }}</span>
                                        @else
                                            <span class="badge" style="border-color: #f59e0b; color: #fbbf24; font-size: 0.75rem;">
                                                <x-icon name="user" size="12" /> Non affecté
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong style="color: var(--text-primary);">{{ number_format($task->weekly_hours, 1) }} h/sem</strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $task->priority }}">
                                            {{ ucfirst($task->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($task->due_date)
                                            <div style="font-size: 0.85rem; {{ $isTaskOverdue ? 'color: #fb7185; font-weight: 600;' : '' }}">
                                                {{ $task->due_date->format('d/m/Y') }}
                                            </div>
                                            @if($isTaskOverdue)
                                                <span class="badge badge-overdue" style="font-size: 0.7rem; padding: 0.1rem 0.4rem;">
                                                    <x-icon name="alert-circle" size="11" /> +{{ $task->daysOverdue() }}j retard
                                                </span>
                                            @endif
                                        @else
                                            <span style="color: var(--text-muted); font-size: 0.85rem;">Non fixée</span>
                                        @endif

                                        @if($task->isUrgentPending())
                                            <div style="margin-top: 0.2rem;">
                                                <span class="badge badge-overdue" style="font-size: 0.7rem; padding: 0.1rem 0.4rem;">
                                                    <x-icon name="alert-triangle" size="11" /> Démarrage urgent
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('tasks.status', $task) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <select name="status" class="form-select" onchange="this.form.submit()" style="padding: 0.2rem 0.5rem; font-size: 0.8rem; width: auto;">
                                                <option value="todo" {{ $task->status === 'todo' ? 'selected' : '' }}>À faire</option>
                                                <option value="doing" {{ $task->status === 'doing' ? 'selected' : '' }}>En cours</option>
                                                <option value="done" {{ $task->status === 'done' ? 'selected' : '' }}>Terminée</option>
                                                <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td style="text-align: right;">
                                        <form action="{{ route('tasks.destroy', $task) }}" method="POST" style="display: inline;" onsubmit="return confirm('Supprimer cette intervention ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color: #fb7185; padding: 0.2rem 0.5rem;">&times;</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <!-- Colonne Droite : Formulaire d'affectation rapide -->
        <div>
            <div class="card">
                <h3 class="card-title">
                    <x-icon name="plus" size="18" /> Ajouter une Intervention
                </h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.25rem;">
                    Dans un cabinet, vous affectez un collaborateur à raison d'un <strong>nombre d'heures par semaine</strong>.
                </p>

                <form action="{{ route('tasks.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="mission_id" value="{{ $mission->id }}">

                    <div class="form-group">
                        <label for="task_title" class="form-label">Libellé de l'intervention *</label>
                        <input type="text" id="task_title" name="title" class="form-input" placeholder="Ex: Révision des comptes & cadrage" required>
                    </div>

                    <div class="form-group">
                        <label for="collaborator_id" class="form-label">Collaborateur assigné</label>
                        <select id="collaborator_id" name="collaborator_id" class="form-select">
                            <option value="">-- Non assigné (À pourvoir) --</option>
                            @foreach($collaborators as $col)
                                <option value="{{ $col->id }}">
                                    {{ $col->name }} (Charge actuelle : {{ number_format($col->currentWeeklyLoad(), 1) }}/{{ $col->weekly_capacity_hours }}h)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="weekly_hours" class="form-label">Charge allouée (h/semaine) *</label>
                        <input type="number" step="0.5" id="weekly_hours" name="weekly_hours" class="form-input" placeholder="Ex: 8.0" value="8.0" required>
                        <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem;">Temps par semaine que le collaborateur passera sur ce dossier.</p>
                    </div>

                    <div class="form-group">
                        <label for="priority" class="form-label">Priorité *</label>
                        <select id="priority" name="priority" class="form-select" required>
                            <option value="medium" selected>Normale (Medium)</option>
                            <option value="high">Haute (High)</option>
                            <option value="urgent">Urgente</option>
                            <option value="low">Basse</option>
                        </select>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="task_start_date" class="form-label">Date début</label>
                            <input type="date" id="task_start_date" name="start_date" class="form-input" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label for="task_due_date" class="form-label">Échéance</label>
                            <input type="date" id="task_due_date" name="due_date" class="form-input">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                        <x-icon name="check" size="16" /> Allouer l'intervention
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
