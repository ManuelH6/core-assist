@extends('layouts.app')

@section('title', 'Nouveau Collaborateur')

@section('content')
<div class="container">
    <div class="page-header" style="max-width: 650px; margin: 0 auto 1.5rem auto;">
        <div>
            <h1 class="page-title">
                <x-icon name="user" size="24" /> Nouveau Collaborateur
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Ajoutez un membre d'équipe et définissez sa capacité hebdomadaire.</p>
        </div>
        <div>
            <a href="{{ route('collaborators.index') }}" class="btn btn-secondary btn-sm">&larr; Retour</a>
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

        <form action="{{ route('collaborators.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name" class="form-label">Nom complet *</label>
                <input type="text" id="name" name="name" class="form-input" placeholder="Ex: Sarah Martin" value="{{ old('name') }}" required>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Adresse email *</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="sarah.martin@cabinet.com" value="{{ old('email') }}" required>
            </div>

            <div class="form-group">
                <label for="role_title" class="form-label">Poste / Fonction au sein du cabinet *</label>
                <input type="text" id="role_title" name="role_title" class="form-input" placeholder="Ex: Auditeur Senior / Consultant Comptable" value="{{ old('role_title', 'Consultant') }}" required>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="weekly_capacity_hours" class="form-label">Capacité contractuelle (h/semaine) *</label>
                    <input type="number" id="weekly_capacity_hours" name="weekly_capacity_hours" class="form-input" value="{{ old('weekly_capacity_hours', 35) }}" required min="1" max="168">
                    <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem;">Volume horaire de travail par semaine (ex: 35h).</p>
                </div>

                <div class="form-group">
                    <label for="hourly_rate" class="form-label">Coût / Taux horaire indicatif</label>
                    <input type="number" step="0.01" id="hourly_rate" name="hourly_rate" class="form-input" placeholder="Ex: 5000" value="{{ old('hourly_rate', 0) }}">
                    <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem;">Pour le calcul de rentabilité cabinet.</p>
                </div>
            </div>

            <div class="actions" style="justify-content: flex-end; margin-top: 1.5rem;">
                <a href="{{ route('collaborators.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check" size="16" /> Enregistrer le collaborateur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
