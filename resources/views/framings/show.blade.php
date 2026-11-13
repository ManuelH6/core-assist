@extends('layouts.app')

@section('title', $framing->reference . ' - Cadrage de la demande')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('requests.show', $framing->request)" label="Retour à la demande" />
    </div>

    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="ba-eyebrow">CADRAGE {{ $framing->reference }} &bull; {{ strtoupper($framing->request?->client?->name ?? '') }}</div>
            <h1 class="page-title mb-1">{{ $framing->title }}<span class="ba-dot">.</span></h1>
            <p class="page-subtitle">Demande d'origine : {{ $framing->request?->reference }} &mdash; {{ $framing->request?->title }}</p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-secondary text-white px-3 py-2 fs-6">{{ $framing->status->label() }}</span>
            @if($framing->status === \App\Enums\FramingStatus::Accepted)
                <a href="{{ route('missions.create', ['framing_id' => $framing->id]) }}" class="btn btn-primary">
                    <x-icon name="plus" size="16" /> Créer la mission
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-4" role="alert">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold">Historique des versions de cadrage ({{ $framing->versions->count() }})</h2>
                </div>
                <div class="card-body">
                    @foreach($framing->versions as $version)
                        <div class="border rounded p-3 mb-3 {{ $version->status === \App\Enums\FramingStatus::Accepted ? 'border-success bg-success bg-opacity-10' : 'bg-light' }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong>Version V{{ $version->version_number }}</strong>
                                    <span class="small text-muted ms-2">par {{ $version->preparer?->name ?? 'Cabinet' }}</span>
                                </div>
                                <span class="badge {{ $version->status === \App\Enums\FramingStatus::Accepted ? 'bg-success' : ($version->status === \App\Enums\FramingStatus::Submitted ? 'bg-warning text-dark' : ($version->status === \App\Enums\FramingStatus::Rejected ? 'bg-danger' : 'bg-secondary text-white')) }}">
                                    {{ $version->status->label() }}
                                </span>
                            </div>

                            @if(!empty($version->content['objectives']))
                                <div class="small mb-2"><strong>Objectifs :</strong> {{ $version->content['objectives'] }}</div>
                            @endif
                            @if(!empty($version->content['scope_summary']))
                                <div class="small mb-2"><strong>Périmètre :</strong> {{ $version->content['scope_summary'] }}</div>
                            @endif
                            @if(!empty($version->content['deliverables']))
                                <div class="small mb-2"><strong>Livrables :</strong> {{ $version->content['deliverables'] }}</div>
                            @endif
                            @if(!empty($version->content['timeline']))
                                <div class="small mb-2"><strong>Calendrier :</strong> {{ $version->content['timeline'] }}</div>
                            @endif
                            @if(!empty($version->content['conditions']))
                                <div class="small mb-2"><strong>Conditions :</strong> {{ $version->content['conditions'] }}</div>
                            @endif

                            @if($version->client_feedback)
                                <div class="small text-dark mt-2 pt-2 border-top">
                                    <strong>Retour client :</strong> {{ $version->client_feedback }}
                                </div>
                            @endif

                            @if($version->status === \App\Enums\FramingStatus::Draft)
                                <form action="{{ route('framings.versions.submit', [$framing, $version]) }}" method="POST" class="mt-3 pt-2 border-top">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <x-icon name="check" size="14" /> Soumettre au client (Portail)
                                    </button>
                                </form>
                            @elseif($version->status === \App\Enums\FramingStatus::Submitted)
                                <form action="{{ route('framings.versions.decision', [$framing, $version]) }}" method="POST" class="mt-3 pt-2 border-top">
                                    @csrf
                                    <div class="mb-2">
                                        <label for="feedback-{{ $version->id }}" class="form-label small fw-semibold">Commentaire de décision client (si enregistré hors portail)</label>
                                        <input type="text" id="feedback-{{ $version->id }}" name="client_feedback" class="form-control" placeholder="Observations du client">
                                    </div>
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <button type="submit" name="decision" value="accept" class="btn btn-success btn-sm flex-fill">
                                            <x-icon name="check" size="14" /> Acter l'accord client
                                        </button>
                                        <button type="submit" name="decision" value="reject" class="btn btn-outline-danger btn-sm flex-fill">
                                            Acter un refus
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            @if($framing->missions->isNotEmpty())
                <div class="card border shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h2 class="h6 mb-0 fw-bold">Missions créées depuis ce cadrage ({{ $framing->missions->count() }})</h2>
                    </div>
                    <div class="card-body">
                        @foreach($framing->missions as $mission)
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <div class="font-monospace small text-muted">{{ $mission->reference ?? 'Mission #' . $mission->id }}</div>
                                    <div class="fw-bold">{{ $mission->title }}</div>
                                </div>
                                <a href="{{ route('missions.show', $mission) }}" class="btn btn-outline-secondary btn-sm">Ouvrir &rarr;</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card border shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h2 class="h6 mb-0 fw-bold">Préparer une nouvelle version révisée</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('framings.versions.store', $framing) }}" method="POST">
                        @csrf
                        @php
                            $latest = $framing->versions->first();
                        @endphp
                        <div class="mb-2">
                            <label for="new-objectives" class="form-label">Objectifs <span class="text-danger">*</span></label>
                            <textarea id="new-objectives" name="objectives" rows="3" class="form-control" required>{{ $latest?->content['objectives'] ?? '' }}</textarea>
                        </div>
                        <div class="mb-2">
                            <label for="new-scope" class="form-label">Périmètre envisagé</label>
                            <textarea id="new-scope" name="scope_summary" rows="2" class="form-control">{{ $latest?->content['scope_summary'] ?? '' }}</textarea>
                        </div>
                        <div class="mb-2">
                            <label for="new-deliverables" class="form-label">Livrables</label>
                            <input type="text" id="new-deliverables" name="deliverables" class="form-control" value="{{ $latest?->content['deliverables'] ?? '' }}">
                        </div>
                        <div class="mb-2">
                            <label for="new-timeline" class="form-label">Calendrier</label>
                            <input type="text" id="new-timeline" name="timeline" class="form-control" value="{{ $latest?->content['timeline'] ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label for="new-conditions" class="form-label">Conditions particulières</label>
                            <textarea id="new-conditions" name="conditions" rows="2" class="form-control">{{ $latest?->content['conditions'] ?? '' }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <x-icon name="plus" size="16" /> Créer la version suivante
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
