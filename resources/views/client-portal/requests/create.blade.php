@extends('layouts.app')

@section('title', 'Nouvelle Demande - Portail Client')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-7">
            <div class="mb-3">
                <x-back-link :href="route('client-portal.index')" label="Retour à mes demandes" />
            </div>

            <div class="page-header mb-4">
                <div class="ba-eyebrow">PORTAIL CLIENT &bull; {{ strtoupper($client->name) }}</div>
                <h1 class="page-title mb-1">Déposer une demande<span class="ba-dot">.</span></h1>
                <p class="page-subtitle">Décrivez votre besoin ou votre problématique. Le cabinet examinera votre demande et préparera une proposition de cadrage.</p>
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
                    <form action="{{ route('client-portal.requests.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="title" class="form-label">Objet de votre demande <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title') }}"
                                required
                                autofocus
                                placeholder="Ex : Diagnostic de trésorerie et rentabilité de nos agences"
                            >
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description du besoin &amp; difficultés constatées <span class="text-danger">*</span></label>
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                class="form-control @error('description') is-invalid @enderror"
                                required
                                placeholder="Expliquez la situation actuelle, les difficultés rencontrées et ce qui motive votre sollicitation."
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="context" class="form-label">Contexte de votre organisation (optionnel)</label>
                            <textarea
                                id="context"
                                name="context"
                                rows="3"
                                class="form-control @error('context') is-invalid @enderror"
                                placeholder="Taille de l'équipe, nombre de sites, changements récents..."
                            >{{ old('context') }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label for="expected_outcome" class="form-label">Résultat attendu / échéance souhaitée (optionnel)</label>
                            <textarea
                                id="expected_outcome"
                                name="expected_outcome"
                                rows="2"
                                class="form-control @error('expected_outcome') is-invalid @enderror"
                                placeholder="Ex : Plan d'action concret sous 6 semaines avant le comité de direction"
                            >{{ old('expected_outcome') }}</textarea>
                        </div>

                        <x-form-actions :cancel-url="route('client-portal.index')" submit-label="Envoyer ma demande" />
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
