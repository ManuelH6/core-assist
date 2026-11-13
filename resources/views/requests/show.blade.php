@extends('layouts.app')

@section('title', $clientRequest->reference . ' - Compréhension & Examen')

@section('content')
@php
    $understanding = $clientRequest->initialUnderstanding;
@endphp

<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('requests.index')" label="Retour aux demandes" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">DEMANDE {{ $clientRequest->reference }} &bull; {{ strtoupper($clientRequest->client?->name ?? '') }}</div>
            <h1 class="page-title mb-1">{{ $clientRequest->title }}<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                Reçue le {{ $clientRequest->received_at?->format('d/m/Y') }} via {{ str_replace('_', ' ', $clientRequest->source_channel) }}
            </p>
        </div>

        <span class="badge bg-secondary text-white px-3 py-2 fs-6">{{ $clientRequest->status->label() }}</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4" role="alert">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        {{-- Colonne gauche : Demande brute + Statut + Cadrages liés --}}
        <div class="col-12 col-lg-5">
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold">Expression brute du client (conservée intacte)</h2>
                </div>
                <div class="card-body">
                    <p class="mb-3 text-dark" style="white-space: pre-line;">{{ $clientRequest->description }}</p>
                    <div class="small text-muted pt-2 border-top">
                        Client : <strong>{{ $clientRequest->client?->name }}</strong>
                        @if($clientRequest->creator)
                            &bull; Saisie par : {{ $clientRequest->creator->name }}
                        @endif
                    </div>
                </div>
            </div>

            {{-- Décision d'examen / changement de statut --}}
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold">Décision d'examen</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('requests.status.update', $clientRequest) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="request-status" class="form-label">Statut réel de la demande</label>
                            <select id="request-status" name="status" class="form-select">
                                @foreach($statuses as $st)
                                    <option value="{{ $st->value }}" {{ $clientRequest->status === $st ? 'selected' : '' }}>
                                        {{ $st->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary w-100">Mettre à jour le statut</button>
                    </form>
                </div>
            </div>

            {{-- Cadrages existants ou création d'un cadrage --}}
            <div class="card border shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold">Cadrage &amp; Proposition ({{ $clientRequest->framings->count() }})</h2>
                </div>
                <div class="card-body">
                    @forelse($clientRequest->framings as $framing)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="font-monospace small text-muted">{{ $framing->reference }}</div>
                                    <div class="fw-bold">{{ $framing->title }}</div>
                                </div>
                                <span class="badge bg-secondary text-white">{{ $framing->status->label() }}</span>
                            </div>
                            <a href="{{ route('framings.show', $framing) }}" class="btn btn-primary btn-sm w-100">
                                Ouvrir le cadrage &rarr;
                            </a>
                        </div>
                    @empty
                        <p class="small text-muted">Aucun cadrage initié pour le moment. Vous pouvez décider de poursuivre en préparant la première proposition.</p>
                    @endforelse

                    <hr>
                    <h3 class="h6 fw-bold mb-3">Préparer un nouveau cadrage</h3>
                    <form action="{{ route('framings.store', $clientRequest) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label for="framing-title" class="form-label">Titre du cadrage <span class="text-danger">*</span></label>
                            <input type="text" id="framing-title" name="title" class="form-control" required value="{{ old('title', 'Cadrage - ' . $clientRequest->title) }}">
                        </div>
                        <div class="mb-2">
                            <label for="framing-objectives" class="form-label">Objectifs proposés <span class="text-danger">*</span></label>
                            <textarea id="framing-objectives" name="objectives" rows="2" class="form-control" required>{{ old('objectives', $understanding?->reformulated_need ?? $clientRequest->description) }}</textarea>
                        </div>
                        <div class="mb-2">
                            <label for="framing-deliverables" class="form-label">Livrables attendus</label>
                            <input type="text" id="framing-deliverables" name="deliverables" class="form-control" placeholder="Ex : Rapport de diagnostic, plan d'action priorisé">
                        </div>
                        <div class="mb-3">
                            <label for="framing-timeline" class="form-label">Calendrier indicatif</label>
                            <input type="text" id="framing-timeline" name="timeline" class="form-control" placeholder="Ex : 6 semaines">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <x-icon name="plus" size="16" /> Créer le cadrage (V1)
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Colonne droite : Fiche de compréhension initiale --}}
        <div class="col-12 col-lg-7">
            <div class="card border shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h6 mb-0 fw-bold">Fiche de compréhension initiale</h2>
                        <p class="small text-muted mb-0">Reformulation méthodologique du besoin avant cadrage.</p>
                    </div>
                    @if($understanding?->completed_at)
                        <span class="badge bg-success">Complétée</span>
                    @else
                        <span class="badge bg-warning text-dark">À compléter</span>
                    @endif
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('requests.understanding.update', $clientRequest) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="context" class="form-label">Contexte du client</label>
                            <textarea id="context" name="context" rows="3" class="form-control">{{ old('context', $understanding?->context) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="reformulated_need" class="form-label">Besoin reformulé par le cabinet</label>
                            <textarea id="reformulated_need" name="reformulated_need" rows="3" class="form-control" placeholder="Quelle est la problématique réelle derrière la plainte exprimée ?">{{ old('reformulated_need', $understanding?->reformulated_need) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="central_question" class="form-label">Question centrale à instruire</label>
                            <input type="text" id="central_question" name="central_question" class="form-control" value="{{ old('central_question', $understanding?->central_question) }}">
                        </div>

                        <div class="mb-3">
                            <label for="expected_outcome" class="form-label">Résultat espéré</label>
                            <textarea id="expected_outcome" name="expected_outcome" rows="2" class="form-control">{{ old('expected_outcome', $understanding?->expected_outcome) }}</textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="known_constraints_text" class="form-label">Contraintes connues (1 par ligne)</label>
                                <textarea id="known_constraints_text" name="known_constraints_text" rows="3" class="form-control">{{ old('known_constraints_text', implode("\n", $understanding?->known_constraints ?? [])) }}</textarea>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="missing_information_text" class="form-label">Informations manquantes (1 par ligne)</label>
                                <textarea id="missing_information_text" name="missing_information_text" rows="3" class="form-control">{{ old('missing_information_text', implode("\n", $understanding?->missing_information ?? [])) }}</textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="initial_notes" class="form-label">Notes internes d'examen</label>
                            <textarea id="initial_notes" name="initial_notes" rows="2" class="form-control">{{ old('initial_notes', $understanding?->initial_notes) }}</textarea>
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="mark_completed" name="mark_completed" value="1" {{ $understanding?->completed_at ? 'checked' : '' }}>
                            <label class="form-check-label small" for="mark_completed">Marquer la fiche de compréhension comme complétée</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 w-sm-auto">
                            <x-icon name="check" size="16" /> Enregistrer la compréhension initiale
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
