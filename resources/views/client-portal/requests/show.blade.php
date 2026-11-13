@extends('layouts.app')

@section('title', $clientRequest->reference . ' - Suivi de demande')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('client-portal.index')" label="Retour à mes demandes" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">DEMANDE {{ $clientRequest->reference }}</div>
            <h1 class="page-title mb-1">{{ $clientRequest->title }}<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">
                Déposée le {{ $clientRequest->received_at?->format('d/m/Y à H:i') }}
            </p>
        </div>
        <span class="badge bg-secondary text-white px-3 py-2 fs-6">
            {{ $clientRequest->status->label() }}
        </span>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-4" role="alert">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold">Votre demande initiale</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="small text-muted text-uppercase fw-semibold mb-1">Besoin exprimé</div>
                        <p class="mb-0 text-dark" style="white-space: pre-line;">{{ $clientRequest->description }}</p>
                    </div>

                    @if($clientRequest->initialUnderstanding?->context)
                        <div class="mb-3 pt-3 border-top">
                            <div class="small text-muted text-uppercase fw-semibold mb-1">Contexte communiqué</div>
                            <p class="mb-0 small text-secondary">{{ $clientRequest->initialUnderstanding->context }}</p>
                        </div>
                    @endif

                    @if($clientRequest->initialUnderstanding?->expected_outcome)
                        <div class="pt-3 border-top">
                            <div class="small text-muted text-uppercase fw-semibold mb-1">Résultat attendu</div>
                            <p class="mb-0 small text-secondary">{{ $clientRequest->initialUnderstanding->expected_outcome }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold">Propositions de cadrage du cabinet</h2>
                </div>
                <div class="card-body">
                    @php
                        $visibleFramings = $clientRequest->framings->filter(fn ($f) => $f->versions->isNotEmpty());
                    @endphp

                    @forelse($visibleFramings as $framing)
                        <div class="mb-4 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                <div>
                                    <div class="font-monospace small text-muted">{{ $framing->reference }}</div>
                                    <h3 class="h6 fw-bold mb-0">{{ $framing->title }}</h3>
                                </div>
                                <span class="badge bg-secondary text-white">{{ $framing->status->label() }}</span>
                            </div>

                            @foreach($framing->versions as $version)
                                <div class="border rounded p-3 mb-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>Proposition V{{ $version->version_number }}</strong>
                                        <span class="badge {{ $version->status === \App\Enums\FramingStatus::Accepted ? 'bg-success' : ($version->status === \App\Enums\FramingStatus::Submitted ? 'bg-warning text-dark' : 'bg-danger') }}">
                                            {{ $version->status->label() }}
                                        </span>
                                    </div>

                                    @if(!empty($version->content['objectives']))
                                        <div class="small mb-2">
                                            <strong>Objectifs :</strong> {{ $version->content['objectives'] }}
                                        </div>
                                    @endif
                                    @if(!empty($version->content['scope_summary']))
                                        <div class="small mb-2">
                                            <strong>Périmètre envisagé :</strong> {{ $version->content['scope_summary'] }}
                                        </div>
                                    @endif
                                    @if(!empty($version->content['deliverables']))
                                        <div class="small mb-2">
                                            <strong>Livrables attendus :</strong> {{ $version->content['deliverables'] }}
                                        </div>
                                    @endif
                                    @if(!empty($version->content['timeline']))
                                        <div class="small mb-2">
                                            <strong>Calendrier indicatif :</strong> {{ $version->content['timeline'] }}
                                        </div>
                                    @endif
                                    @if(!empty($version->content['conditions']))
                                        <div class="small mb-2">
                                            <strong>Modalités :</strong> {{ $version->content['conditions'] }}
                                        </div>
                                    @endif

                                    @if($version->client_feedback)
                                        <div class="small text-muted mt-2 pt-2 border-top">
                                            <strong>Votre retour :</strong> {{ $version->client_feedback }}
                                        </div>
                                    @endif

                                    @if($version->status === \App\Enums\FramingStatus::Submitted)
                                        <form action="{{ route('client-portal.requests.framings.decide', [$clientRequest, $version]) }}" method="POST" class="mt-3 pt-3 border-top">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="feedback-{{ $version->id }}" class="form-label small fw-semibold">Commentaire ou précision (optionnel)</label>
                                                <textarea id="feedback-{{ $version->id }}" name="client_feedback" rows="2" class="form-control" placeholder="Précisez vos attentes ou réserves éventuelles"></textarea>
                                            </div>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" name="decision" value="accept" class="btn btn-success flex-fill">
                                                    <x-icon name="check" size="16" /> Accepter cette proposition
                                                </button>
                                                <button type="submit" name="decision" value="reject" class="btn btn-outline-danger flex-fill">
                                                    Refuser / Demander une révision
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            @endforeach

                            @if($framing->missions->isNotEmpty())
                                <div class="mt-2">
                                    <div class="small fw-semibold text-success mb-1">Mission(s) engagée(s) :</div>
                                    @foreach($framing->missions as $mission)
                                        <div class="small text-dark">
                                            &bull; <strong>{{ $mission->reference ?? 'Mission #' . $mission->id }}</strong> : {{ $mission->title }}
                                            <span class="badge bg-light text-dark border ms-1">{{ $mission->status }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted small mb-0">
                            Votre demande est en cours d'analyse par le cabinet. Vous retrouverez ici la proposition de cadrage dès qu'elle sera prête.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
