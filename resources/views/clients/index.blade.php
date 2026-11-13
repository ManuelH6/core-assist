@extends('layouts.app')

@section('title', 'Répertoire Clients - CoreAssist')

@section('content')
<div class="container py-4">
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">RELATION CLIENT</div>
            <h1 class="page-title mb-1">Clients<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">Référentiel des organisations accompagnées et gestion de leurs accès au portail client.</p>
        </div>

        <a href="{{ route('clients.create') }}" class="btn btn-primary">
            <x-icon name="plus" size="16" /> Nouveau client
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
    @endif

    @if($clients->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon"><x-icon name="briefcase" size="24" /></div>
            <div class="empty-state-title">Aucun client enregistré</div>
            <p class="empty-state-text">Créez une fiche client pour enregistrer ses demandes et lui ouvrir un accès au portail.</p>
            <div class="empty-state-actions">
                <a href="{{ route('clients.create') }}" class="btn btn-primary">
                    <x-icon name="plus" size="16" /> Ajouter un client
                </a>
            </div>
        </div>
    @else
        <div class="card border shadow-sm d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">Client</th>
                            <th scope="col">Type</th>
                            <th scope="col">Secteur</th>
                            <th scope="col">Demandes</th>
                            <th scope="col">Accès Portail</th>
                            <th scope="col" class="pe-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $client)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ $client->name }}</div>
                                    <div class="small text-muted">{{ $client->email ?? $client->phone ?? 'Contact non renseigné' }}</div>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $client->client_type->label() }}</span></td>
                                <td class="small text-secondary">{{ $client->sector ?? '-' }}</td>
                                <td><span class="badge bg-secondary text-white">{{ $client->requests_count }} demande(s)</span></td>
                                <td class="small text-muted">{{ $client->users_count }} compte(s)</td>
                                <td class="pe-3 text-end">
                                    <a href="{{ route('clients.show', $client) }}" class="btn btn-outline-secondary btn-sm">
                                        Consulter &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mobile-card-list d-md-none">
            @foreach($clients as $client)
                <x-mobile-card
                    :title="$client->name"
                    :subtitle="$client->sector ?? $client->client_type->label()"
                    :status="$client->requests_count . ' demande(s)'"
                    :action-url="route('clients.show', $client)"
                    action-label="Consulter la fiche &rarr;"
                >
                    <x-slot:grid>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Type</span>
                            <span class="mobile-data-value">{{ $client->client_type->label() }}</span>
                        </div>
                        <div class="mobile-data-item">
                            <span class="mobile-data-label">Accès portail</span>
                            <span class="mobile-data-value">{{ $client->users_count }} compte(s)</span>
                        </div>
                    </x-slot:grid>
                </x-mobile-card>
            @endforeach
        </div>
    @endif
</div>
@endsection
