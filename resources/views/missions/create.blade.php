@extends('layouts.app')

@section('title', 'Nouvelle Mission - CoreAssist')

@section('content')
@php
    $inheritedTitle = old('title', $framing?->request?->title ?? '');
    $inheritedDescription = old('description', $framing?->latestVersion?->content['scope_summary'] ?? $framing?->request?->description ?? '');
    $inheritedObjective = old('objective', $framing?->latestVersion?->content['objectives'] ?? $framing?->request?->initialUnderstanding?->reformulated_need ?? '');
@endphp

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-7">
            <div class="mb-4">
                <x-back-link :href="$framing ? route('framings.show', $framing) : route('missions.index')" :label="$framing ? 'Retour au cadrage' : 'Retour aux missions'" />
                <div class="ba-eyebrow mt-2">PORTEFEUILLE D'ENGAGEMENTS</div>
                <h1 class="h2 mb-1">Nouvelle Mission<span class="ba-dot">.</span></h1>
                <p class="text-muted mb-0">Créez un nouveau dossier, puis configurez les outils et autorisations de l'équipe.</p>
            </div>

            @if($framing)
                <div class="card border border-primary border-opacity-25 bg-primary bg-opacity-10 mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="small fw-bold text-primary mb-1">
                            Données héritées du cadrage {{ $framing->reference }} ({{ $framing->request?->client?->name }})
                        </div>
                        <div class="small text-secondary">
                            Demande {{ $framing->request?->reference }} &bull; Les informations clés ci-dessous ont été pré-remplies sans ressaisie.
                        </div>
                    </div>
                </div>
            @endif

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
                    <form action="{{ route('missions.store') }}" method="POST">
                        @csrf

                        @if($framing)
                            <input type="hidden" name="framing_id" value="{{ $framing->id }}">
                        @endif

                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Titre de la mission <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" placeholder="Ex: Audit financier & optimisation des coûts d'exploitation" value="{{ $inheritedTitle }}" required autofocus>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="objective" class="form-label fw-semibold">Objectif principal</label>
                            <textarea id="objective" name="objective" class="form-control @error('objective') is-invalid @enderror" rows="2" placeholder="Objectif opérationnel de la mission">{{ $inheritedObjective }}</textarea>
                            @error('objective')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Contexte &amp; Périmètre sommaire</label>
                            <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Description sommaire du dossier et livrables attendus">{{ $inheritedDescription }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="manager_id" class="form-label fw-semibold">Responsable de mission</label>
                                <select id="manager_id" name="manager_id" class="form-select @error('manager_id') is-invalid @enderror">
                                    <option value="">-- Sélectionner un collaborateur --</option>
                                    @foreach($collaborators as $col)
                                        <option value="{{ $col->id }}" {{ old('manager_id') == $col->id ? 'selected' : '' }}>
                                            {{ $col->name }} ({{ $col->role_title }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('manager_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label fw-semibold">Statut initial <span class="text-danger">*</span></label>
                                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="draft" {{ old('status', 'in_progress') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                                    <option value="in_progress" {{ old('status', 'in_progress') == 'in_progress' ? 'selected' : '' }}>En cours</option>
                                    <option value="on_hold" {{ old('status') == 'on_hold' ? 'selected' : '' }}>En pause</option>
                                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Terminée</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label fw-semibold">Date de démarrage</label>
                                <input type="date" id="start_date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', date('Y-m-d')) }}">
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="due_date" class="form-label fw-semibold">Date d'échéance</label>
                                <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="budget" class="form-label fw-semibold">Budget prévisionnel (FCFA)</label>
                            <input type="number" step="0.01" id="budget" name="budget" class="form-control @error('budget') is-invalid @enderror" placeholder="Ex: 1500000" value="{{ old('budget') }}">
                            <div class="form-text">Budget d'honoraires prévus pour cette mission.</div>
                            @error('budget')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <x-form-actions :cancel-url="route('missions.index')" submit-label="Créer et configurer les outils" />
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
