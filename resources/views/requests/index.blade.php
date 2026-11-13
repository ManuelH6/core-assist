@extends('layouts.app')

@section('title', 'Demandes Clients - CoreAssist')

@section('content')
<div class="container py-4">
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">RELATION CLIENT &amp; CADRAGE</div>
            <h1 class="page-title mb-1">Demandes Clients<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">Sollicitations reçues depuis le portail client ou enregistrées par le cabinet.</p>
        </div>

        <a href="{{ route('requests.create') }}" class="btn btn-primary">
            <x-icon name="plus" size="16" /> Enregistrer une demande
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="stat-label">Total</div>
                    <div class="stat-value stat-value-primary">{{ $counts['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="stat-label">Nouvelles (Reçues)</div>
                    <div class="stat-value text-warning">{{ $counts['received'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="stat-label">À l'étude / Cadrées</div>
                    <div class="stat-value text-dark">{{ $counts['being_studied'] + $counts['framed'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="stat-label">Converties en mission</div>
                    <div class="stat-value stat-value-success">{{ $counts['converted'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres bureau --}}
    <div class="d-none d-md-flex flex-wrap align-items-center gap-2 mb-4">
        <a href="{{ route('requests.index') }}" class="btn btn-sm {{ !$statusFilter ? 'btn-secondary' : 'btn-outline-secondary' }}">Toutes</a>
        @foreach($statuses as $st)
            <a href="{{ route('requests.index', ['status' => $st->value]) }}" class="btn btn-sm {{ $statusFilter === $st->value ? 'btn-secondary' : 'btn-outline-secondary' }}">
                {{ $st->label() }}
            </a>
        @endforeach
    </div>

    {{-- Filtre mobile par tiroir bas --}}
    <div class="d-md-none mb-3">
        <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="offcanvas" data-bs-target="#requestsFilterSheet">
            <x-icon name="filter" size="16" /> Filtres {{ $statusFilter ? '(1)' : '' }}
        </button>
    </div>

    <div class="offcanvas offcanvas-bottom" tabindex="-1" id="requestsFilterSheet" aria-labelledby="requestsFilterSheetLabel" style="height: auto; max-height: 75vh;">
        <div class="offcanvas-header border-bottom">
            <h2 class="h6 offcanvas-title fw-bold" id="requestsFilterSheetLabel">Filtrer les demandes</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('requests.index') }}" method="GET">
                <div class="mb-3">
                    <label for="mobile-filter-status" class="form-label">Statut</label>
                    <select id="mobile-filter-status" name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st->value }}" {{ $statusFilter === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Appliquer les filtres</button>
            </form>
        </div>
    </div>

    @if($requests->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon"><x-icon name="clipboard" size="24" /></div>
            <div class="empty-state-title">Aucune demande trouvée</div>
            <p class="empty-state-text">Aucune sollicitation ne correspond aux critères sélectionnés.</p>
        </div>
    @else
        <div class="card border shadow-sm d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">Référence</th>
                            <th scope="col">Client &amp; Objet</th>
                            <th scope="col">Origine</th>
                            <th scope="col">Date</th>
                            <th scope="col">Statut</th>
                            <th scope="col" class="pe-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $req)
                            <tr>
                                <td class="ps-3 font-monospace small fw-bold">{{ $req->reference }}</td>
                                <td>
                                    <div class="small text-muted">{{ $req->client?->name }}</div>
                                    <div class="fw-bold text-dark">{{ $req->title }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $req->source_channel)) }}</span>
                                </td>
                                <td class="small text-muted">{{ $req->received_at?->format('d/m/Y') }}</td>
                                <td><span class="badge bg-secondary text-white">{{ $req->status->label() }}</span></td>
                                <td class="pe-3 text-end">
                                    <a href="{{ route('requests.show', $req) }}" class="btn btn-outline-secondary btn-sm">
                                        Instruire &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mobile-card-list d-md-none">
            @foreach($requests as $req)
                <x-mobile-card
                    :title="$req->title"
                    :subtitle="($req->client?->name ?? '') . ' • ' . $req->reference"
                    :status="$req->status->label()"
                    :action-url="route('requests.show', $req)"
                    action-label="Instruire la demande &rarr;"
                >
                    <x-slot:grid>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Origine</span>
                            <span class="mobile-data-value">{{ ucfirst(str_replace('_', ' ', $req->source_channel)) }}</span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Reçue le</span>
                            <span class="mobile-data-value">{{ $req->received_at?->format('d/m/Y') }}</span>
                        </div>
                    </x-slot:grid>
                </x-mobile-card>
            @endforeach
        </div>
    @endif
</div>
@endsection
