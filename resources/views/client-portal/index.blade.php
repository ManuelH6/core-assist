@extends('layouts.app')

@section('title', 'Mon Espace Client - ' . $client->name)

@section('content')
<div class="container py-4">
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">PORTAIL CLIENT &bull; {{ strtoupper($client->name) }}</div>
            <h1 class="page-title mb-1">Mes Demandes &amp; Dossiers<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">Déposez une nouvelle demande d'intervention et suivez l'avancement de vos dossiers avec le cabinet.</p>
        </div>

        <a href="{{ route('client-portal.requests.create') }}" class="btn btn-primary">
            <x-icon name="plus" size="16" /> Nouvelle demande
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-4" role="alert">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="stat-label">Demandes déposées</div>
                    <div class="stat-value stat-value-primary">{{ $requests->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="stat-label">À l'étude / Reçues</div>
                    <div class="stat-value text-warning">
                        {{ $requests->whereIn('status', [\App\Enums\RequestStatus::Received, \App\Enums\RequestStatus::BeingStudied])->count() }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="stat-label">Cadrages en attente</div>
                    <div class="stat-value text-dark">
                        {{ $requests->filter(fn ($r) => $r->framings->contains(fn ($f) => $f->status === \App\Enums\FramingStatus::Submitted))->count() }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="stat-label">Missions lancées</div>
                    <div class="stat-value stat-value-success">
                        {{ $requests->where('status', \App\Enums\RequestStatus::Converted)->count() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($requests->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">
                <x-icon name="clipboard" size="24" />
            </div>
            <div class="empty-state-title">Aucune demande enregistrée</div>
            <p class="empty-state-text">Exprimez votre besoin pour démarrer une étude ou un diagnostic avec le cabinet.</p>
            <div class="empty-state-actions">
                <a href="{{ route('client-portal.requests.create') }}" class="btn btn-primary">
                    <x-icon name="plus" size="16" /> Déposer ma première demande
                </a>
            </div>
        </div>
    @else
        {{-- Vue bureau (>= 768px) --}}
        <div class="card border shadow-sm d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">Référence</th>
                            <th scope="col">Objet de la demande</th>
                            <th scope="col">Date</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Proposition de cadrage</th>
                            <th scope="col" class="pe-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $req)
                            @php
                                $submittedFraming = $req->framings->first(fn ($f) => in_array($f->status, [\App\Enums\FramingStatus::Submitted, \App\Enums\FramingStatus::Accepted, \App\Enums\FramingStatus::Rejected], true));
                            @endphp
                            <tr>
                                <td class="ps-3 font-monospace small fw-bold">{{ $req->reference }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $req->title }}</div>
                                    <div class="small text-muted">{{ Str::limit($req->description, 90) }}</div>
                                </td>
                                <td class="small text-muted">{{ $req->received_at?->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-secondary text-white">{{ $req->status->label() }}</span>
                                </td>
                                <td>
                                    @if($submittedFraming)
                                        <span class="badge {{ $submittedFraming->status === \App\Enums\FramingStatus::Accepted ? 'bg-success' : ($submittedFraming->status === \App\Enums\FramingStatus::Submitted ? 'bg-warning text-dark' : 'bg-danger') }}">
                                            {{ $submittedFraming->status->label() }}
                                        </span>
                                    @else
                                        <span class="small text-muted">En préparation</span>
                                    @endif
                                </td>
                                <td class="pe-3 text-end">
                                    <a href="{{ route('client-portal.requests.show', $req) }}" class="btn btn-outline-secondary btn-sm">
                                        Consulter &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Vue cartes empilées sur mobile (< 768px) --}}
        <div class="mobile-card-list d-md-none">
            @foreach($requests as $req)
                <x-mobile-card
                    :title="$req->title"
                    :subtitle="$req->reference . ' • ' . ($req->received_at?->format('d/m/Y') ?? '')"
                    :status="$req->status->label()"
                    :action-url="route('client-portal.requests.show', $req)"
                    action-label="Consulter le suivi &rarr;"
                >
                    <p class="small text-muted mb-0">{{ Str::limit($req->description, 120) }}</p>
                </x-mobile-card>
            @endforeach
        </div>
    @endif
</div>
@endsection
