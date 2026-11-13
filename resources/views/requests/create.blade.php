@extends('layouts.app')

@section('title', 'Nouvelle Demande Client - CoreAssist')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-7">
            <div class="mb-3">
                <x-back-link :href="route('requests.index')" label="Retour aux demandes" />
            </div>

            <div class="page-header mb-4">
                <div class="ba-eyebrow">RELATION CLIENT</div>
                <h1 class="page-title mb-1">Enregistrer une demande<span class="ba-dot">.</span></h1>
                <p class="page-subtitle">La fiche de compréhension initiale est créée automatiquement à l'enregistrement.</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger mb-4" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card border shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('requests.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="client_id" class="form-label">Client <span class="text-danger">*</span></label>
                            <select id="client_id" name="client_id" class="form-select" required>
                                <option value="">-- Sélectionner un client --</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $selectedClientId) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="source_channel" class="form-label">Canal de réception <span class="text-danger">*</span></label>
                                <select id="source_channel" name="source_channel" class="form-select" required>
                                    <option value="rencontre" {{ old('source_channel') === 'rencontre' ? 'selected' : '' }}>Rencontre / Réunion</option>
                                    <option value="email" {{ old('source_channel') === 'email' ? 'selected' : '' }}>E-mail</option>
                                    <option value="telephone" {{ old('source_channel') === 'telephone' ? 'selected' : '' }}>Téléphone</option>
                                    <option value="partenaire" {{ old('source_channel') === 'partenaire' ? 'selected' : '' }}>Recommandation / Partenaire</option>
                                    <option value="portail_client" {{ old('source_channel') === 'portail_client' ? 'selected' : '' }}>Portail client</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="received_at" class="form-label">Date de réception</label>
                                <input type="date" id="received_at" name="received_at" class="form-control" value="{{ old('received_at', date('Y-m-d')) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Objet de la demande <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Expression brute du besoin <span class="text-danger">*</span></label>
                            <textarea id="description" name="description" rows="4" class="form-control" required>{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="context" class="form-label">Contexte initial connu</label>
                            <textarea id="context" name="context" rows="2" class="form-control">{{ old('context') }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label for="expected_outcome" class="form-label">Résultat espéré par le client</label>
                            <textarea id="expected_outcome" name="expected_outcome" rows="2" class="form-control">{{ old('expected_outcome') }}</textarea>
                        </div>

                        <x-form-actions :cancel-url="route('requests.index')" submit-label="Enregistrer la demande" />
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
