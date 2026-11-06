@extends('layouts.app')

@section('title', 'Créer une Mission')

@section('content')
<div class="container">
    <div class="page-header" style="max-width: 650px; margin: 0 auto 1.5rem auto;">
        <div>
            <h1 class="page-title">
                <x-icon name="briefcase" size="24" /> Nouvelle Mission
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Créez un nouveau dossier, projet ou mission d'expertise.</p>
        </div>
        <div>
            <a href="{{ route('missions.index') }}" class="btn btn-secondary btn-sm">&larr; Retour</a>
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

        <form action="{{ route('missions.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="title" class="form-label">Titre de la mission *</label>
                <input type="text" id="title" name="title" class="form-input" placeholder="Ex: Audit financier & refonte des coûts de production" value="{{ old('title') }}" required>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Objectifs & Périmètre</label>
                <textarea id="description" name="description" class="form-input" rows="3" placeholder="Description sommaire du dossier et livrables attendus">{{ old('description') }}</textarea>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="manager_id" class="form-label">Responsable de mission</label>
                    <select id="manager_id" name="manager_id" class="form-select">
                        <option value="">-- Sélectionner un collaborateur --</option>
                        @foreach($collaborators as $col)
                            <option value="{{ $col->id }}" {{ old('manager_id') == $col->id ? 'selected' : '' }}>
                                {{ $col->name }} ({{ $col->role_title }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Statut initial *</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="draft" {{ old('status', 'in_progress') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                        <option value="in_progress" {{ old('status', 'in_progress') == 'in_progress' ? 'selected' : '' }}>En cours</option>
                        <option value="on_hold" {{ old('status') == 'on_hold' ? 'selected' : '' }}>En pause</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Terminée</option>
                    </select>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="start_date" class="form-label">Date de démarrage</label>
                    <input type="date" id="start_date" name="start_date" class="form-input" value="{{ old('start_date', date('Y-m-d')) }}">
                </div>

                <div class="form-group">
                    <label for="due_date" class="form-label">Date d'échéance</label>
                    <input type="date" id="due_date" name="due_date" class="form-input" value="{{ old('due_date') }}">
                </div>
            </div>

            <div class="form-group">
                <label for="budget" class="form-label">Budget prévisionnel (FCFA ou devise cabinet)</label>
                <input type="number" step="0.01" id="budget" name="budget" class="form-input" placeholder="Ex: 1500000" value="{{ old('budget') }}">
            </div>

            <div class="actions" style="justify-content: flex-end; margin-top: 1.5rem;">
                <a href="{{ route('missions.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check" size="16" /> Créer la mission
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
