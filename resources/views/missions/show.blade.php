@extends('layouts.app')

@section('title', $mission->title . ' - Détail de la Mission')

@section('content')
@php
    $statusLabels = [
        'draft' => 'Brouillon',
        'in_progress' => 'En cours',
        'completed' => 'Terminée',
        'on_hold' => 'En pause',
    ];

    $diagnosticTools = [
        [
            'num' => '01',
            'code' => 'MFS',
            'tool_code' => 'D360-MFS',
            'label' => 'Cadrage Opérationnel',
            'icon' => 'clipboard',
            'route' => $mission->framingSheet ? route('missions.framing-sheet.show', $mission) : route('missions.framing-sheet.initial-data', $mission),
            'active' => (bool) $mission->framingSheet,
        ],
        [
            'num' => '02',
            'code' => 'DSC',
            'tool_code' => 'D360-DSC',
            'label' => 'Périmètre d’analyse',
            'icon' => 'layers',
            'route' => ($mission->scopeCanvas || ! ($toolPermissions['D360-DSC']['edit'] ?? true)) ? route('missions.scope-canvas.show', $mission) : route('missions.scope-canvas.initial-data', $mission),
            'active' => (bool) $mission->scopeCanvas,
        ],
        [
            'num' => '03',
            'code' => 'DIR',
            'tool_code' => 'D360-DIR',
            'label' => 'Registre de Collecte',
            'icon' => 'file',
            'route' => route('missions.intake-register.show', $mission),
            'active' => (bool) $mission->intakeRegister,
        ],
        [
            'num' => '04',
            'code' => 'SIG',
            'tool_code' => 'D360-SIG',
            'label' => 'Parties Prenantes',
            'icon' => 'users',
            'route' => route('missions.stakeholder-grid.show', $mission),
            'active' => (bool) $mission->stakeholderGrid,
        ],
        [
            'num' => '05',
            'code' => 'D360',
            'tool_code' => 'D360-D360',
            'label' => 'Matrice 360°',
            'icon' => 'layers',
            'route' => route('missions.diagnostic-matrix.show', $mission),
            'active' => (bool) $mission->diagnosticMatrix,
        ],
        [
            'num' => '06',
            'code' => 'PSG',
            'tool_code' => 'D360-PSG',
            'label' => 'Problèmes Structurés',
            'icon' => 'git-pull-request',
            'route' => route('missions.problem-grid.show', $mission),
            'active' => (bool) $mission->problemGrid,
        ],
        [
            'num' => '07',
            'code' => 'DRD',
            'tool_code' => 'D360-DRD',
            'label' => 'Restitution Client',
            'icon' => 'file-text',
            'route' => route('missions.restitution-deck.show', $mission),
            'active' => (bool) $mission->restitutionDeck,
        ],
        [
            'num' => '08',
            'code' => 'LDC',
            'tool_code' => 'D360-LDC',
            'label' => 'Décisions Actées',
            'icon' => 'check-square',
            'route' => route('missions.decision-capture.show', $mission),
            'active' => (bool) $mission->decisionCapture,
        ],
        [
            'num' => '09',
            'code' => 'DAP',
            'tool_code' => 'D360-DAP',
            'label' => 'Dossier d’Archives',
            'icon' => 'folder',
            'route' => route('missions.archive-pack.show', $mission),
            'active' => (bool) $mission->archivePack,
        ],
        [
            'num' => '10',
            'code' => 'NSR',
            'tool_code' => 'D360-NSR',
            'label' => 'Feuille de route',
            'icon' => 'arrow-right-circle',
            'route' => route('missions.next-step-router.show', $mission),
            'active' => (bool) $mission->nextStepRouter,
        ],
    ];

    $visibleTools = collect($diagnosticTools)->filter(function (array $tool) use ($toolPermissions): bool {
        return $toolPermissions[$tool['tool_code']]['view'] ?? true;
    })->values();
@endphp

<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.index')" label="Retour aux missions" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div class="min-w-0 flex-grow-1">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="ba-eyebrow mb-0">DOSSIER MISSION</span>
                <span class="status-pill status-{{ $mission->status }}">
                    {{ $statusLabels[$mission->status] ?? $mission->status }}
                </span>
            </div>
            <h1 class="page-title mb-1">
                {{ $mission->title }}<span class="ba-dot">.</span>
            </h1>
            <p class="page-subtitle">
                Responsable : <strong class="text-dark">{{ $mission->manager ? $mission->manager->name : 'Non assigné' }}</strong>
                &bull; Période : {{ $mission->start_date ? $mission->start_date->format('d/m/Y') : 'Immédiat' }} &rarr; {{ $mission->due_date ? $mission->due_date->format('d/m/Y') : 'Non définie' }}
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
            @if(auth()->user()?->canConfigureMissions())
                <a href="{{ route('missions.configuration.edit', $mission) }}" class="btn btn-outline-primary btn-sm">
                    <x-icon name="pen" size="14" /> Configurer outils &amp; équipe
                </a>
            @endif
            <form action="{{ route('missions.destroy', $mission) }}" method="POST" onsubmit="return confirm('Confirmer la suppression de cette mission ?');" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <x-icon name="trash" size="14" /> Supprimer
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if($mission->isOverdue())
        <div class="alert alert-danger d-flex align-items-center gap-3 mb-4" role="alert">
            <x-icon name="alert-circle" size="24" class="text-danger flex-shrink-0" />
            <div>
                <strong>Alerte Échéance Dépassée :</strong> Cette mission devait être livrée le {{ $mission->due_date->format('d/m/Y') }} et accuse un retard de <strong>{{ $mission->daysOverdue() }} jour(s)</strong>.
            </div>
        </div>
    @elseif($mission->isAtRisk())
        @php
            $daysLeft = $mission->daysRemaining();
        @endphp
        <div class="alert alert-warning d-flex align-items-center gap-3 mb-4" role="alert">
            <x-icon name="alert-triangle" size="24" class="flex-shrink-0" />
            <div>
                <strong>Risque de Dérive :</strong> L'échéance arrive {{ $daysLeft === 0 ? "aujourd'hui" : 'dans '.$daysLeft.' jour'.($daysLeft > 1 ? 's' : '') }} alors que l'avancement n'est que de {{ $mission->progressPercentage() }}%.
            </div>
        </div>
    @endif

    <!-- Parcours Diagnostic 360° -->
    <div class="card border shadow-sm mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
                <div>
                    <h2 class="h6 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <x-icon name="layers" size="16" /> Outils du Diagnostic 360°
                    </h2>
                    <div class="text-muted small">
                        @if($hasToolConfiguration ?? false)
                            Modules activés et autorisés pour votre profil sur cette mission
                        @else
                            Accès direct aux 10 modules méthodologiques de la mission
                        @endif
                    </div>
                </div>
                <span class="badge bg-light text-dark border">
                    {{ $visibleTools->where('active', true)->count() }} / {{ $visibleTools->count() }} initialisés
                </span>
            </div>

            @if($visibleTools->isEmpty())
                <div class="text-muted small py-2">
                    Aucun outil du Diagnostic 360° n'est activé ou autorisé pour votre profil sur cette mission.
                </div>
            @else
                <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-5 g-2">
                    @foreach($visibleTools as $tool)
                        <div class="col">
                            <a href="{{ $tool['route'] }}" class="tool-nav-tile {{ $tool['active'] ? 'is-active' : '' }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="tool-nav-code">{{ $tool['num'] }} &middot; {{ $tool['code'] }}</span>
                                    <x-icon :name="$tool['icon']" size="14" class="{{ $tool['active'] ? 'text-success' : 'text-muted' }}" />
                                </div>
                                <div class="tool-nav-title">{{ $tool['label'] }}</div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @php
        $progress = $mission->progressPercentage();
        $totalWeekly = $mission->totalWeeklyHours();
    @endphp

    <!-- KPIs de la mission -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Avancement Global</div>
                    <div class="stat-value stat-value-primary">{{ $progress }}%</div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Staffing Hebdomadaire Alloué</div>
                    <div class="stat-value stat-value-warning">{{ number_format($totalWeekly, 1) }} h/sem</div>
                    <div class="text-muted small mt-1">Charge d'équipe mobilisée par semaine</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Tâches & Livrables</div>
                    <div class="stat-value stat-value-success">
                        {{ $mission->tasks->where('status', 'done')->count() }} / {{ $mission->tasks->count() }}
                    </div>
                    <div class="text-muted small mt-1">{{ $mission->tasks->whereIn('status', ['todo', 'doing'])->count() }} intervention(s) en cours</div>
                </div>
            </div>
        </div>

        @if($mission->budget)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border shadow-sm">
                    <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                        <div class="stat-label">Budget Alloué</div>
                        <div class="stat-value">{{ number_format($mission->budget, 0, ',', ' ') }} <span class="fs-6 text-muted fw-normal">FCFA</span></div>
                        <div class="text-muted small mt-1">Enveloppe d'honoraires prévus</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Section Interventions & Staffing -->
    <div class="row g-4">
        <!-- Colonne Gauche : Liste des interventions -->
        <div class="col-12 col-lg-8">
            <h2 class="h5 fw-bold text-dark mb-3">
                <x-icon name="clipboard" size="18" /> Interventions & Staffing sur le dossier ({{ $mission->tasks->count() }})
            </h2>

            @if($mission->tasks->isEmpty())
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <x-icon name="clipboard" size="24" />
                    </div>
                    <div class="empty-state-title">Aucune intervention planifiée</div>
                    <p class="empty-state-text">Allouez un collaborateur avec son volume horaire hebdomadaire pour structurer la mission.</p>
                </div>
            @else
                <!-- Design Pattern Mobile : Cartes d'interventions pour écrans tactiles (< 768px) -->
                <div class="mobile-card-list d-md-none">
                    @foreach($mission->tasks as $task)
                        @php
                            $isTaskOverdue = $task->isOverdue();
                        @endphp
                        <div class="mobile-data-card {{ $isTaskOverdue ? 'border-danger' : '' }}">
                            <div class="mobile-data-card-header">
                                <div>
                                    <div class="mobile-data-card-title">{{ $task->title }}</div>
                                    @if($task->description)
                                        <div class="text-muted small mt-1">{{ $task->description }}</div>
                                    @endif
                                </div>
                                <span class="badge badge-{{ $task->priority }}">
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </div>

                            <div class="mobile-data-grid">
                                <div class="mobile-data-item">
                                    <span class="mobile-data-label">Collaborateur</span>
                                    <span class="mobile-data-value text-truncate">
                                        @if($task->collaborator)
                                            <a href="{{ route('collaborators.show', $task->collaborator) }}" class="text-dark fw-semibold text-decoration-none">
                                                {{ $task->collaborator->name }}
                                            </a>
                                        @else
                                            <span class="text-warning small">Non affecté</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="mobile-data-item">
                                    <span class="mobile-data-label">Charge allouée</span>
                                    <span class="mobile-data-value">{{ number_format($task->weekly_hours, 1) }} h/sem</span>
                                </div>
                                <div class="mobile-data-item">
                                    <span class="mobile-data-label">Échéance</span>
                                    <span class="mobile-data-value {{ $isTaskOverdue ? 'text-danger' : '' }}">
                                        {{ $task->due_date ? $task->due_date->format('d/m/Y') : 'Non fixée' }}
                                        @if($isTaskOverdue)
                                            <span class="badge badge-overdue d-inline-block mt-1">+{{ $task->daysOverdue() }}j retard</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="mobile-data-item">
                                    <span class="mobile-data-label">Statut</span>
                                    <form action="{{ route('tasks.status', $task) }}" method="POST">
                                        @csrf
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="font-size: 0.8rem;">
                                            <option value="todo" {{ $task->status === 'todo' ? 'selected' : '' }}>À faire</option>
                                            <option value="doing" {{ $task->status === 'doing' ? 'selected' : '' }}>En cours</option>
                                            <option value="done" {{ $task->status === 'done' ? 'selected' : '' }}>Terminée</option>
                                            <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                                        </select>
                                    </form>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Supprimer cette intervention ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <x-icon name="trash" size="12" /> Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Design Pattern Desktop : Tableau avec première colonne collante (> 768px) -->
                <div class="card shadow-sm border mb-4 d-none d-md-block">
                    <div class="table-responsive">
                        <table class="table table-hover table-sticky-first align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Intervention</th>
                                    <th>Collaborateur</th>
                                    <th>Charge Hebdo</th>
                                    <th>Priorité</th>
                                    <th>Échéance</th>
                                    <th>Statut</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mission->tasks as $task)
                                    @php
                                        $isTaskOverdue = $task->isOverdue();
                                    @endphp
                                    <tr class="{{ $isTaskOverdue ? 'table-danger-subtle' : '' }}">
                                        <td class="ps-4">
                                            <div class="fw-semibold">{{ $task->title }}</div>
                                            @if($task->description)
                                                <div class="text-muted small mt-1">{{ $task->description }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($task->collaborator)
                                                <a href="{{ route('collaborators.show', $task->collaborator) }}" class="fw-semibold text-decoration-none text-dark">
                                                    {{ $task->collaborator->name }}
                                                </a>
                                                <div class="text-muted small">{{ $task->collaborator->role_title }}</div>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                    <x-icon name="user" size="11" /> Non affecté
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong class="text-dark">{{ number_format($task->weekly_hours, 1) }} h/sem</strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $task->priority }}">
                                                {{ ucfirst($task->priority) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($task->due_date)
                                                <div class="small {{ $isTaskOverdue ? 'text-danger fw-bold' : 'text-secondary' }}">
                                                    {{ $task->due_date->format('d/m/Y') }}
                                                </div>
                                                @if($isTaskOverdue)
                                                    <span class="badge badge-overdue">
                                                        <x-icon name="alert-circle" size="10" /> +{{ $task->daysOverdue() }}j
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif

                                            @if($task->isUrgentPending())
                                                <div class="mt-1">
                                                    <span class="badge badge-overdue">
                                                        <x-icon name="alert-triangle" size="10" /> Urgent
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <form action="{{ route('tasks.status', $task) }}" method="POST">
                                                @csrf
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto; min-height: 32px; font-size: 0.8rem;">
                                                    <option value="todo" {{ $task->status === 'todo' ? 'selected' : '' }}>À faire</option>
                                                    <option value="doing" {{ $task->status === 'doing' ? 'selected' : '' }}>En cours</option>
                                                    <option value="done" {{ $task->status === 'done' ? 'selected' : '' }}>Terminée</option>
                                                    <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette intervention ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" aria-label="Supprimer">
                                                    <x-icon name="trash" size="12" />
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <!-- Colonne Droite : Formulaire d'affectation rapide -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border">
                <div class="card-body p-4">
                    <h3 class="h6 fw-bold text-dark mb-2">
                        <x-icon name="plus" size="16" /> Ajouter une Intervention
                    </h3>
                    <p class="text-muted small mb-3">
                        Affectez un collaborateur à raison d'un volume horaire par semaine.
                    </p>

                    <form action="{{ route('tasks.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="mission_id" value="{{ $mission->id }}">

                        <div class="mb-3">
                            <label for="task_title" class="form-label fw-semibold small">Libellé de l'intervention <span class="text-danger">*</span></label>
                            <input type="text" id="task_title" name="title" class="form-control" placeholder="Ex: Révision des comptes & cadrage" required>
                        </div>

                        <div class="mb-3">
                            <label for="collaborator_id" class="form-label fw-semibold small">Collaborateur assigné</label>
                            <select id="collaborator_id" name="collaborator_id" class="form-select">
                                <option value="">-- Non assigné (À pourvoir) --</option>
                                @foreach($collaborators as $col)
                                    <option value="{{ $col->id }}">
                                        {{ $col->name }} (Charge : {{ number_format($col->currentWeeklyLoad(), 1) }}/{{ $col->weekly_capacity_hours }}h)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="weekly_hours" class="form-label fw-semibold small">Charge allouée (h/semaine) <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" id="weekly_hours" name="weekly_hours" class="form-control" placeholder="Ex: 8.0" value="8.0" required>
                            <div class="form-text">Temps par semaine mobilisé sur ce dossier.</div>
                        </div>

                        <div class="mb-3">
                            <label for="priority" class="form-label fw-semibold small">Priorité <span class="text-danger">*</span></label>
                            <select id="priority" name="priority" class="form-select" required>
                                <option value="medium" selected>Normale (Medium)</option>
                                <option value="high">Haute (High)</option>
                                <option value="urgent">Urgente</option>
                                <option value="low">Basse</option>
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="task_start_date" class="form-label fw-semibold small">Début</label>
                                <input type="date" id="task_start_date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-6">
                                <label for="task_due_date" class="form-label fw-semibold small">Échéance</label>
                                <input type="date" id="task_due_date" name="due_date" class="form-control">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <x-icon name="check" size="16" /> Allouer l'intervention
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
