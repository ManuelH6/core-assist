@extends('layouts.app')

@section('title', 'Connexion - CoreAssist')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="text-center mb-4">
                <div class="ba-eyebrow">ACCÈS SÉCURISÉ</div>
                <h1 class="page-title mb-1">Connexion<span class="ba-dot">.</span></h1>
                <p class="page-subtitle">Accédez à votre espace cabinet ou à votre portail client.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger mb-4" role="alert">{{ session('error') }}</div>
            @endif

            <div class="card border shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('login.attempt') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse e-mail</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="nom@entreprise.com"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                                autocomplete="current-password"
                                placeholder="Votre mot de passe"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label small" for="remember">Rester connecté sur cet appareil</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <x-icon name="lock" size="16" /> Se connecter
                        </button>
                    </form>
                </div>
            </div>

            @if(app()->environment('local'))
                <div class="card border mt-4 shadow-sm">
                    <div class="card-header bg-white py-2 border-bottom">
                        <span class="small fw-bold text-dark">Comptes de démonstration (mot de passe : <code>password</code>)</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm js-fill-login" data-email="dg@coreassist.test">DG</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm js-fill-login" data-email="manager@coreassist.test">Manager</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm js-fill-login" data-email="chef@coreassist.test">Chef de mission</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm js-fill-login" data-email="consultant@coreassist.test">Consultant</button>
                            <button type="button" class="btn btn-outline-primary btn-sm js-fill-login" data-email="client.savanes@coreassist.test">Client (Atelier Savanes)</button>
                            <button type="button" class="btn btn-outline-primary btn-sm js-fill-login" data-email="client.boulangerie@coreassist.test">Client (Boulangerie Moderne)</button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@if(app()->environment('local'))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    document.querySelectorAll('.js-fill-login').forEach((btn) => {
        btn.addEventListener('click', () => {
            emailInput.value = btn.dataset.email || '';
            passwordInput.value = 'password';
            emailInput.focus();
        });
    });
});
</script>
@endpush
@endif
