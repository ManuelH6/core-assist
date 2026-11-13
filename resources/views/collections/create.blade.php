@extends('layouts.app')

@section('title', 'Saisie d\'une Collecte Financière - CoreAssist')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-7">
            <div class="mb-4">
                <x-back-link :href="route('collections.index')" label="Retour aux collectes" />
                <div class="ba-eyebrow mt-2">DONNÉES D'EXPLOITATION</div>
                <h1 class="h2 mb-1">Saisie d'une Collecte<span class="ba-dot">.</span></h1>
                <p class="text-muted mb-0">Renseignez les données journalières de production, ventes et coûts directs.</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm border">
                <div class="card-body p-4 p-md-5">
                    <form action="{{ route('collections.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Titre de la collecte / Nom du produit <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" placeholder="Ex: Production quotidienne - Atelier principal" value="{{ old('title') }}" required autofocus>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="entry_date" class="form-label fw-semibold">Date d'activité</label>
                                <input type="date" id="entry_date" name="entry_date" class="form-control @error('entry_date') is-invalid @enderror" value="{{ old('entry_date', date('Y-m-d')) }}">
                                @error('entry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="currency" class="form-label fw-semibold">Devise</label>
                                <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror">
                                    <option value="XOF" {{ old('currency', 'XOF') == 'XOF' ? 'selected' : '' }}>FCFA (XOF)</option>
                                    <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>Euro (€)</option>
                                    <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>Dollar ($)</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="selling_price" class="form-label fw-semibold">Prix de vente unitaire <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" id="selling_price" name="selling_price" class="form-control @error('selling_price') is-invalid @enderror" placeholder="Ex: 250" value="{{ old('selling_price') }}" required>
                                @error('selling_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="daily_quantity" class="form-label fw-semibold">Quantité vendue par jour <span class="text-danger">*</span></label>
                                <input type="number" id="daily_quantity" name="daily_quantity" class="form-control @error('daily_quantity') is-invalid @enderror" placeholder="Ex: 400" value="{{ old('daily_quantity') }}" required>
                                @error('daily_quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="unit_production_cost" class="form-label fw-semibold">Coût de production unitaire <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" id="unit_production_cost" name="unit_production_cost" class="form-control @error('unit_production_cost') is-invalid @enderror" placeholder="Ex: 150 (matières premières, consommables, main d'oeuvre directe)" value="{{ old('unit_production_cost') }}" required>
                            <div class="form-text">Coût direct engagé pour la fabrication ou la livraison unitaire.</div>
                            @error('unit_production_cost')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <x-form-actions :cancel-url="route('collections.index')" submit-label="Enregistrer la collecte" />
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
