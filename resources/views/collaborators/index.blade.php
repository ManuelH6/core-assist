@extends('layouts.app')

@section('title', 'Équipe & Staffing Hebdomadaire - CoreAssist')

@section('content')
<div class="container py-4">
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <div class="ba-eyebrow">RESSOURCES & CHARGE D'ÉQUIPE</div>
            <h1 class="page-title mb-1">
                <x-icon name="users" size="24" /> Équipe & Capacité<span class="ba-dot">.</span>
            </h1>
            <p class="page-subtitle">Suivi de la charge hebdomadaire, détection des surcharges et capacité disponible.</p>
        </div>
        <div class="w-100 w-md-auto">
            <a href="{{ route('collaborators.create') }}" class="btn btn-primary w-100">
                <x-icon name="plus" size="14" /> Nouveau Collaborateur
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <!-- KPIs globaux de staffing du cabinet -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Capacité Totale du Cabinet</div>
                    <div class="stat-value stat-value-primary">{{ $teamWorkload['total_capacity_hours'] }} h/sem</div>
                    <div class="text-muted small mt-1">Pour {{ $teamWorkload['total_collaborators'] }} collaborateur(s) actif(s)</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Charge Hebdomadaire Engagée</div>
                    <div class="stat-value stat-value-warning">{{ number_format($teamWorkload['total_assigned_hours'], 1) }} h/sem</div>
                    <div class="text-muted small mt-1">Total des interventions en cours</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Taux d'Occupation Global</div>
                    <div class="stat-value {{ $teamWorkload['global_staffing_rate'] > 100 ? 'stat-value-warning' : 'stat-value-success' }}">
                        {{ $teamWorkload['global_staffing_rate'] }} %
                    </div>
                    <div class="text-muted small mt-1">Rapport charge / capacité globale</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 border shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                    <div class="stat-label">Alertes Surcharge (> 100%)</div>
                    <div class="stat-value" style="color: {{ $teamWorkload['overloaded_count'] > 0 ? 'var(--ba-error-strong)' : 'var(--ba-success)' }};">
                        {{ $teamWorkload['overloaded_count'] }}
                    </div>
                    <div class="text-muted small mt-1">Collaborateur(s) en dépassement</div>
                </div>
            </div>
        </div>
    </div>

    @if($collaborators->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                <x-icon name="users" size="24" />
            </div>
            <div class="empty-state-title">Aucun collaborateur enregistré</div>
            <p class="empty-state-text">Constituez votre équipe pour suivre les temps et équilibrer la charge opérationnelle.</p>
            <div class="empty-state-actions">
                <a href="{{ route('collaborators.create') }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" size="14" /> Ajouter un collaborateur
                </a>
            </div>
        </div>
    @else
        <!-- Design Pattern Mobile : Cartes empilées pour écrans tactiles (< 768px) -->
        <div class="mobile-card-list d-md-none">
            @foreach($collaborators as $col)
                @php
                    $load = $col->currentWeeklyLoad();
                    $rate = $col->staffingRate();
                    $isOver = $col->isOverCapacity();
                @endphp
                <div class="mobile-data-card {{ $isOver ? 'border-danger' : '' }}">
                    <div class="mobile-data-card-header">
                        <div>
                            <a href="{{ route('collaborators.show', $col) }}" class="mobile-data-card-title">
                                {{ $col->name }}
                            </a>
                            <div class="text-muted small">{{ $col->email }}</div>
                        </div>
                        <span class="badge bg-light text-dark border">{{ $col->role_title }}</span>
                    </div>

                    <div class="mobile-data-grid">
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Capacité Hebdo</span>
                            <span class="mobile-data-value">{{ $col->weekly_capacity_hours }} h/sem</span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Charge Affectée</span>
                            <span class="mobile-data-value" style="color: {{ $isOver ? 'var(--ba-error-strong)' : 'inherit' }};">
                                {{ number_format($load, 1) }} h/sem
                            </span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Dossiers en cours</span>
                            <span class="mobile-data-value">{{ $col->activeTasks->count() }} active(s)</span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">État Staffing</span>
                            <span class="mobile-data-value">
                                @if($isOver)
                                    <span class="text-danger fw-bold"><x-icon name="alert-triangle" size="12" /> Surcharge</span>
                                @elseif($rate >= 80)
                                    <span class="text-dark">Optimal</span>
                                @else
                                    <span class="text-success">Disponible</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between text-muted small mb-1">
                            <span>Taux d'occupation</span>
                            <strong class="text-dark">{{ $rate }}%</strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar {{ $isOver ? 'bg-danger' : ($rate >= 80 ? 'bg-primary' : 'bg-success') }}" style="width: {{ min($rate, 100) }}%;"></div>
                        </div>
                    </div>

                    <a href="{{ route('collaborators.show', $col) }}" class="btn btn-outline-secondary btn-sm w-100">
                        Consulter la fiche &rarr;
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
                            <th class="ps-4">Collaborateur</th>
                            <th>Rôle</th>
                            <th>Capacité Hebdo</th>
                            <th>Charge Affectée</th>
                            <th style="min-width: 200px;">Taux de Staffing</th>
                            <th>Dossiers en cours</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($collaborators as $col)
                            @php
                                $load = $col->currentWeeklyLoad();
                                $rate = $col->staffingRate();
                                $isOver = $col->isOverCapacity();
                            @endphp
                            <tr class="{{ $isOver ? 'table-danger-subtle' : '' }}">
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $col->name }}</div>
                                    <div class="text-muted small">{{ $col->email }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $col->role_title }}</span>
                                </td>
                                <td>{{ $col->weekly_capacity_hours }} h/sem</td>
                                <td>
                                    <strong style="color: {{ $isOver ? 'var(--ba-error-strong)' : 'var(--ba-graphite-900)' }};">
                                        {{ number_format($load, 1) }} h/sem
                                    </strong>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between text-muted small mb-1">
                                        <span>
                                            @if($isOver)
                                                <span class="text-danger fw-bold">
                                                    <x-icon name="alert-triangle" size="12" /> Surcharge (+{{ number_format($load - $col->weekly_capacity_hours, 1) }}h)
                                                </span>
                                            @elseif($rate >= 80)
                                                <span class="fw-bold text-dark">Optimal</span>
                                            @else
                                                <span class="text-success fw-medium">Disponible ({{ number_format($col->weekly_capacity_hours - $load, 1) }}h libres)</span>
                                            @endif
                                        </span>
                                        <strong class="text-dark">{{ $rate }}%</strong>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar {{ $isOver ? 'bg-danger' : ($rate >= 80 ? 'bg-primary' : 'bg-success') }}" role="progressbar" style="width: {{ min($rate, 100) }}%;" aria-valuenow="{{ min($rate, 100) }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">{{ $col->activeTasks->count() }} active(s)</span>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('collaborators.show', $col) }}" class="btn btn-outline-secondary btn-sm">Consulter &rarr;</a>
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
