@extends('layouts.app')

@section('title', $collaborator->name . ' - Profil & Charge')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <div style="margin-bottom: 0.5rem;">
                <a href="{{ route('collaborators.index') }}" class="btn btn-secondary btn-sm">&larr; Toute l'équipe</a>
            </div>
            <h1 class="page-title">{{ $collaborator->name }}</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                {{ $collaborator->role_title }} &bull; {{ $collaborator->email }}
            </p>
        </div>
        <div>
            <form action="{{ route('collaborators.destroy', $collaborator) }}" method="POST" onsubmit="return confirm('Confirmer la suppression de ce collaborateur ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary btn-sm" style="color: #fb7185;">Supprimer</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $load = $collaborator->currentWeeklyLoad();
        $rate = $collaborator->staffingRate();
        $isOver = $collaborator->isOverCapacity();
        $available = max(0, $collaborator->weekly_capacity_hours - $load);
    @endphp

    <!-- KPIs individuels de staffing -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Capacité Hebdomadaire</div>
            <div class="stat-value stat-value-primary">{{ $collaborator->weekly_capacity_hours }} h/sem</div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Temps contractuel disponible</p>
        </div>

        <div class="stat-card">
            <div class="stat-label">Charge Engagée Actuelle</div>
            <div class="stat-value" style="color: {{ $isOver ? '#fb7185' : '#facc15' }};">
                {{ number_format($load, 1) }} h/sem
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Sur {{ $collaborator->activeTasks->count() }} intervention(s) active(s)</p>
        </div>

        <div class="stat-card">
            <div class="stat-label">Taux d'Occupation / Staffing</div>
            <div class="stat-value" style="color: {{ $isOver ? '#fb7185' : ($rate >= 80 ? '#60a5fa' : '#4ade80') }};">
                {{ $rate }}%
            </div>
            <div class="progress-track" style="margin-top: 0.75rem;">
                <div class="progress-fill" style="width: {{ min($rate, 100) }}%; background: {{ $isOver ? '#f43f5e' : ($rate >= 80 ? '#3b82f6' : '#22c55e') }};"></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Disponibilité Restante</div>
            <div class="stat-value {{ $available > 0 ? 'stat-value-success' : 'stat-value-warning' }}">
                {{ number_format($available, 1) }} h/sem
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">
                {{ $isOver ? 'Attention : En dépassement de charge' : 'Capacité libre pour nouveau dossier' }}
            </p>
        </div>
    </div>

    <!-- Répartition des heures par mission et interventions -->
    <div style="margin-top: 2rem;">
        <h2 style="font-size: 1.35rem; margin-bottom: 1rem;">
            <x-icon name="briefcase" size="20" /> Répartition hebdomadaire des interventions ({{ $collaborator->tasks->count() }})
        </h2>

        <div class="table-container">
            @if($collaborator->tasks->isEmpty())
                <div class="empty-state">
                    <p style="margin-bottom: 0.5rem;">Ce collaborateur n'a aucune intervention assignée.</p>
                    <a href="{{ route('missions.index') }}" class="btn btn-secondary btn-sm">Voir les missions disponibles</a>
                </div>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mission / Dossier</th>
                            <th>Intervention</th>
                            <th>Temps alloué</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Période</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($collaborator->tasks as $task)
                            <tr>
                                <td>
                                    <a href="{{ route('missions.show', $task->mission) }}" style="color: var(--primary); text-decoration: none; font-weight: 600;">
                                        {{ $task->mission->title }}
                                    </a>
                                </td>
                                <td>
                                    <strong>{{ $task->title }}</strong>
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
                                    <span class="badge {{ $task->status === 'done' ? 'badge-green' : '' }}">
                                        {{ $task->status === 'done' ? 'Terminée' : ($task->status === 'doing' ? 'En cours' : 'À faire') }}
                                    </span>
                                </td>
                                <td>
                                    {{ $task->start_date ? $task->start_date->format('d/m') : '-' }} &rarr; {{ $task->due_date ? $task->due_date->format('d/m/Y') : '-' }}
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('missions.show', $task->mission) }}" class="btn btn-secondary btn-sm">Voir la mission</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
