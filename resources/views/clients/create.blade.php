@extends('layouts.app')

@section('title', 'Nouveau Client - CoreAssist')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-7">
            <div class="mb-3">
                <x-back-link :href="route('clients.index')" label="Retour aux clients" />
            </div>

            <div class="page-header mb-4">
                <div class="ba-eyebrow">RELATION CLIENT</div>
                <h1 class="page-title mb-1">Nouveau Client<span class="ba-dot">.</span></h1>
                <p class="page-subtitle">Enregistrez un nouveau client dans le référentiel du cabinet.</p>
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
                    <form action="{{ route('clients.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Raison sociale / Nom <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required autofocus>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="client_type" class="form-label">Type de client <span class="text-danger">*</span></label>
                                <select id="client_type" name="client_type" class="form-select" required>
                                    @foreach($clientTypes as $type)
                                        <option value="{{ $type->value }}" {{ old('client_type') === $type->value ? 'selected' : '' }}>
                                            {{ $type->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="sector" class="form-label">Secteur d'activité</label>
                                <input type="text" id="sector" name="sector" class="form-control" value="{{ old('sector') }}" placeholder="Ex : Industrie, Agroalimentaire, Services">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label">E-mail de contact</label>
                                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="tel" id="phone" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <input type="text" id="address" name="address" class="form-control" value="{{ old('address') }}">
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label">Notes internes</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>

                        <x-form-actions :cancel-url="route('clients.index')" submit-label="Enregistrer le client" />
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
