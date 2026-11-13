@extends('layouts.app')

@section('title', $collaborator->name . ' - Profil & Charge')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('collaborators.index')" label="Retour à l'équipe & staffing" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <div class="ba-eyebrow">FICHE COLLABORATEUR</div>
            <h1 class="page-title mb-1">
                {{ $collaborator->name }}<span class="ba-dot">.</span>
            </h1>
            <p class="page-subtitle">
                <span class="badge bg-light text-dark border me-1">{{ $collaborator->role_title }}</span>
                <span class="text-muted">{{ $collaborator->email }}</span>
                @if($collaborator->hourly_rate > 0)
                    &bull; <span class="text-muted">Taux : {{ number_format($collaborator->hourly_rate, 0, ',', ' ') }} FCFA/h</span>
                @endif
            </p>
        </div>
        <div>
            <form action="{{ route('collaborators.destroy', $collaborator) }}" method="POST" onsubmit="return confirm('Confirmer la suppression de ce collaborateur ?');">
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

    @php
        $load = $collaborator->currentWeeklyLoad();
        $rate = $collaborator->staffingRate();
        $isOver = $collaborator->isOverCapacity();
        $available = max(0, $collaborator->weekly_capacity_hours - $load);
    @endphp

    <!-- KPIs individuels de staffing -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Capacité Hebdomadaire</div>
                    <div class="stat-value stat-value-primary">{{ $collaborator->weekly_capacity_hours }} h/sem</div>
                    <div class="text-muted small mt-1">Temps contractuel disponible</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Charge Engagée Actuelle</div>
                    <div class="stat-value" style="color: {{ $isOver ? 'var(--ba-error-strong)' : 'var(--ba-graphite-900)' }};">
                        {{ number_format($load, 1) }} h/sem
                    </div>
                    <div class="text-muted small mt-1">Sur {{ $collaborator->activeTasks->count() }} intervention(s) active(s)</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Taux d'Occupation / Staffing</div>
                    <div class="stat-value" style="color: {{ $isOver ? 'var(--ba-error-strong)' : ($rate >= 80 ? 'var(--ba-graphite-900)' : 'var(--ba-success)') }};">
                        {{ $rate }}%
                    </div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar {{ $isOver ? 'bg-danger' : ($rate >= 80 ? 'bg-primary' : 'bg-success') }}" role="progressbar" style="width: {{ min($rate, 100) }}%;" aria-valuenow="{{ min($rate, 100) }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Disponibilité Restante</div>
                    <div class="stat-value {{ $available > 0 ? 'stat-value-success' : 'stat-value-warning' }}">
                        {{ number_format($available, 1) }} h/sem
                    </div>
                    <div class="small mt-1 {{ $isOver ? 'text-danger' : 'text-muted' }}">
                        {{ $isOver ? 'Attention : En dépassement de charge' : 'Capacité libre pour nouveau dossier' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Répartition des heures par mission et interventions -->
    <div class="mb-4">
        <h2 class="h5 fw-bold text-dark mb-3">
            <x-icon name="briefcase" size="18" /> Répartition hebdomadaire des interventions ({{ $collaborator->tasks->count() }})
        </h2>

        @if($collaborator->tasks->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon">
                    <x-icon name="check-circle" size="24" />
                </div>
                <div class="empty-state-title">Aucune intervention assignée</div>
                <p class="empty-state-text">Ce collaborateur n'a actuellement aucune intervention active programmée.</p>
                <div class="empty-state-actions">
                    <a href="{{ route('missions.index') }}" class="btn btn-outline-secondary btn-sm">Consulter les missions</a>
                </div>
            </div>
        @else
            <div class="card shadow-sm border mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Mission / Dossier</th>
                                <th>Intervention</th>
                                <th>Temps alloué</th>
                                <th>Priorité</th>
                                <th>Statut</th>
                                <th>Période</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($collaborator->tasks as $task)
                                <tr>
                                    <td class="ps-4">
                                        <a href="{{ route('missions.show', $task->mission) }}" class="fw-semibold text-decoration-none text-dark">
                                            {{ $task->mission->title }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $task->title }}</div>
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
                                        <span class="badge {{ $task->status === 'done' ? 'badge-green' : 'bg-light text-secondary border' }}">
                                            {{ $task->status === 'done' ? 'Terminée' : ($task->status === 'doing' ? 'En cours' : 'À faire') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-secondary small">
                                            {{ $task->start_date ? $task->start_date->format('d/m') : '-' }} &rarr; {{ $task->due_date ? $task->due_date->format('d/m/Y') : '-' }}
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="{{ route('missions.show', $task->mission) }}" class="btn btn-outline-secondary btn-sm">Dossier &rarr;</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
