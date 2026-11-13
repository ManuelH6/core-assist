@extends('layouts.app')

@section('title', 'Nouveau Collaborateur - CoreAssist')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-7">
            <div class="mb-4">
                <x-back-link :href="route('collaborators.index')" label="Retour à l'équipe & staffing" />
                <div class="ba-eyebrow mt-2">RESSOURCES & TALENTS</div>
                <h1 class="h2 mb-1">Nouveau Collaborateur<span class="ba-dot">.</span></h1>
                <p class="text-muted mb-0">Ajoutez un membre d'équipe et définissez sa capacité contractuelle.</p>
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
                    <form action="{{ route('collaborators.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Ex: Sarah Martin" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Adresse email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="sarah.martin@cabinet.com" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="role_title" class="form-label fw-semibold">Poste / Fonction au sein du cabinet <span class="text-danger">*</span></label>
                            <input type="text" id="role_title" name="role_title" class="form-control @error('role_title') is-invalid @enderror" placeholder="Ex: Auditeur Senior / Consultant Financier" value="{{ old('role_title', 'Consultant') }}" required>
                            @error('role_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="weekly_capacity_hours" class="form-label fw-semibold">Capacité contractuelle (h/semaine) <span class="text-danger">*</span></label>
                                <input type="number" id="weekly_capacity_hours" name="weekly_capacity_hours" class="form-control @error('weekly_capacity_hours') is-invalid @enderror" value="{{ old('weekly_capacity_hours', 35) }}" required min="1" max="168">
                                <div class="form-text">Volume horaire de travail par semaine (ex: 35h).</div>
                                @error('weekly_capacity_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="hourly_rate" class="form-label fw-semibold">Taux horaire indicatif (FCFA)</label>
                                <input type="number" step="0.01" id="hourly_rate" name="hourly_rate" class="form-control @error('hourly_rate') is-invalid @enderror" placeholder="Ex: 5000" value="{{ old('hourly_rate', 0) }}">
                                <div class="form-text">Pour le calcul de rentabilité cabinet.</div>
                                @error('hourly_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <x-form-actions :cancel-url="route('collaborators.index')" submit-label="Enregistrer le collaborateur" />
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
