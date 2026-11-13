@extends('layouts.app')

@section('title', $client->name . ' - Fiche Client')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('clients.index')" label="Retour aux clients" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">CLIENT &bull; {{ strtoupper($client->client_type->label()) }}</div>
            <h1 class="page-title mb-1">{{ $client->name }}<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                {{ $client->sector ?? 'Secteur non précisé' }}
                @if($client->email) &bull; {{ $client->email }} @endif
                @if($client->phone) &bull; {{ $client->phone }} @endif
            </p>
        </div>

        <a href="{{ route('requests.create', ['client_id' => $client->id]) }}" class="btn btn-primary">
            <x-icon name="plus" size="16" /> Nouvelle demande pour ce client
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4" role="alert">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0 fw-bold">Historique des demandes ({{ $client->requests->count() }})</h2>
                </div>
                <div class="card-body">
                    @forelse($client->requests as $req)
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 py-2 border-bottom">
                            <div>
                                <div class="font-monospace small text-muted">{{ $req->reference }} &bull; {{ $req->received_at?->format('d/m/Y') }}</div>
                                <div class="fw-bold text-dark">{{ $req->title }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary text-white">{{ $req->status->label() }}</span>
                                <a href="{{ route('requests.show', $req) }}" class="btn btn-outline-secondary btn-sm">Ouvrir &rarr;</a>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Aucune demande enregistrée pour ce client.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold">Comptes d'accès Portail Client ({{ $client->users->count() }})</h2>
                </div>
                <div class="card-body">
                    @forelse($client->users as $portalUser)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-semibold text-dark">{{ $portalUser->name }}</div>
                                <div class="small text-muted">{{ $portalUser->email }}</div>
                            </div>
                            <span class="badge bg-light text-dark border">Client</span>
                        </div>
                    @empty
                        <p class="small text-muted mb-3">Aucun compte portail créé pour ce client.</p>
                    @endforelse

                    <form action="{{ route('clients.users.store', $client) }}" method="POST" class="mt-3 pt-2">
                        @csrf
                        <h3 class="h6 fw-bold mb-3">Créer un accès portail</h3>

                        <div class="mb-2">
                            <label for="portal-user-name" class="form-label">Nom de l'interlocuteur <span class="text-danger">*</span></label>
                            <input type="text" id="portal-user-name" name="name" class="form-control" required placeholder="Ex : Mme Awa Koné (DG)">
                        </div>

                        <div class="mb-2">
                            <label for="portal-user-email" class="form-label">E-mail de connexion <span class="text-danger">*</span></label>
                            <input type="email" id="portal-user-email" name="email" class="form-control" required placeholder="awa@entreprise.com">
                        </div>

                        <div class="mb-3">
                            <label for="portal-user-password" class="form-label">Mot de passe initial <span class="text-danger">*</span></label>
                            <input type="password" id="portal-user-password" name="password" class="form-control" required minlength="6">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <x-icon name="plus" size="16" /> Créer l'accès portail
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
