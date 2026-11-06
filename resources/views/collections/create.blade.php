@extends('layouts.app')

@section('title', 'Nouvelle Collecte Financière')

@section('content')
<div class="container">
    <div class="page-header" style="max-width: 650px; margin: 0 auto 1.5rem auto;">
        <div>
            <h1 class="page-title">
                <x-icon name="clipboard" size="24" /> Saisie d'une Collecte
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Renseignez les données journalières de production et vente de votre boulangerie.</p>
        </div>
        <div>
            <a href="{{ route('collections.index') }}" class="btn btn-secondary btn-sm">&larr; Retour</a>
        </div>
    </div>

    <div class="form-card">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul style="margin-left: 1.25rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('collections.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="title" class="form-label">Titre de la collecte / Nom du produit *</label>
                <input type="text" id="title" name="title" class="form-input" placeholder="Ex: Baguette de tradition (Fournée quotidienne)" value="{{ old('title', 'Baguette de tradition') }}" required>
                @error('title')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="entry_date" class="form-label">Date d'activité</label>
                    <input type="date" id="entry_date" name="entry_date" class="form-input" value="{{ old('entry_date', date('Y-m-d')) }}">
                    @error('entry_date')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="currency" class="form-label">Devise</label>
                    <select id="currency" name="currency" class="form-select">
                        <option value="XOF" {{ old('currency', 'XOF') == 'XOF' ? 'selected' : '' }}>FCFA (XOF)</option>
                        <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>Euro (€)</option>
                        <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>Dollar ($)</option>
                    </select>
                    @error('currency')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="selling_price" class="form-label">Prix de vente unitaire *</label>
                    <input type="number" step="0.01" id="selling_price" name="selling_price" class="form-input" placeholder="Ex: 200" value="{{ old('selling_price', '200') }}" required>
                    @error('selling_price')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="daily_quantity" class="form-label">Quantité vendue par jour *</label>
                    <input type="number" id="daily_quantity" name="daily_quantity" class="form-input" placeholder="Ex: 300" value="{{ old('daily_quantity', '300') }}" required>
                    @error('daily_quantity')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="unit_production_cost" class="form-label">Coût de production unitaire *</label>
                <input type="number" step="0.01" id="unit_production_cost" name="unit_production_cost" class="form-input" placeholder="Ex: 120 (farine, levure, énergie, main d'oeuvre)" value="{{ old('unit_production_cost', '120') }}" required>
                @error('unit_production_cost')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="actions" style="justify-content: flex-end; margin-top: 1.5rem;">
                <a href="{{ route('collections.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check" size="16" /> Enregistrer la collecte
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
