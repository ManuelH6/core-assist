@extends('layouts.app')

@section('title', 'Équipe & Staffing Hebdomadaire - Cabinet')

@section('content')
<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <x-icon name="users" size="24" /> Équipe & Capacité de Staffing
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Suivi de la charge hebdomadaire, détection des surcharges et capacité disponible.</p>
        </div>
        <div>
            <a href="{{ route('collaborators.create') }}" class="btn btn-primary">
                <x-icon name="plus" size="14" /> Nouveau Collaborateur
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- KPIs globaux de staffing du cabinet -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Capacité Totale du Cabinet</div>
            <div class="stat-value stat-value-primary">{{ $teamWorkload['total_capacity_hours'] }} h/sem</div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Pour {{ $teamWorkload['total_collaborators'] }} collaborateurs actifs</p>
        </div>

        <div class="stat-card">
            <div class="stat-label">Charge Hebdomadaire Engagée</div>
            <div class="stat-value stat-value-warning">{{ number_format($teamWorkload['total_assigned_hours'], 1) }} h/sem</div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Total des interventions en cours</p>
        </div>

        <div class="stat-card">
            <div class="stat-label">Taux d'Occupation Global</div>
            <div class="stat-value {{ $teamWorkload['global_staffing_rate'] > 100 ? 'stat-value-warning' : 'stat-value-success' }}">
                {{ $teamWorkload['global_staffing_rate'] }} %
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Rapport charge / capacité globale</p>
        </div>

        <div class="stat-card">
            <div class="stat-label">Alertes Surcharge (> 100%)</div>
            <div class="stat-value" style="color: {{ $teamWorkload['overloaded_count'] > 0 ? '#fb7185' : '#4ade80' }};">
                {{ $teamWorkload['overloaded_count'] }}
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">Collaborateur(s) en dépassement d'heures</p>
        </div>
    </div>

    <div class="table-container">
        @if($collaborators->isEmpty())
            <div class="empty-state">
                <p style="font-size: 1.1rem; margin-bottom: 1rem;">Aucun collaborateur enregistré dans l'équipe.</p>
                <a href="{{ route('collaborators.create') }}" class="btn btn-secondary">Ajouter un premier collaborateur</a>
            </div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Collaborateur</th>
                        <th>Rôle</th>
                        <th>Capacité Hebdo</th>
                        <th>Charge Affectée</th>
                        <th style="width: 25%;">Taux de Staffing</th>
                        <th>Dossiers en cours</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($collaborators as $col)
                        @php
                            $load = $col->currentWeeklyLoad();
                            $rate = $col->staffingRate();
                            $isOver = $col->isOverCapacity();
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $col->name }}</strong>
                                <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">{{ $col->email }}</span>
                            </td>
                            <td>{{ $col->role_title }}</td>
                            <td>{{ $col->weekly_capacity_hours }} h/sem</td>
                            <td>
                                <strong style="color: {{ $isOver ? '#fb7185' : 'var(--text-primary)' }};">
                                    {{ number_format($load, 1) }} h/sem
                                </strong>
                            </td>
                            <td>
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 0.2rem;">
                                    <span>
                                        @if($isOver)
                                            <span style="color: #fb7185; font-weight: 600;">
                                                <x-icon name="alert-triangle" size="12" /> Surcharge (+{{ number_format($load - $col->weekly_capacity_hours, 1) }}h)
                                            </span>
                                        @elseif($rate >= 80)
                                            <span style="color: #60a5fa; font-weight: 600;">Optimal</span>
                                        @else
                                            <span style="color: #4ade80; font-weight: 600;">Disponible ({{ number_format($col->weekly_capacity_hours - $load, 1) }}h libres)</span>
                                        @endif
                                    </span>
                                    <strong>{{ $rate }}%</strong>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: {{ min($rate, 100) }}%; background: {{ $isOver ? '#f43f5e' : ($rate >= 80 ? '#3b82f6' : '#22c55e') }};"></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge">{{ $col->activeTasks->count() }} active(s)</span>
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('collaborators.show', $col) }}" class="btn btn-secondary btn-sm">Fiche</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
