<?php

namespace App\Modules\ClientRelationship\Http\Controllers;

use App\Enums\FramingStatus;
use App\Http\Controllers\Controller;
use App\Modules\ClientRelationship\Http\Models\FramingVersion;
use App\Modules\ClientRelationship\Http\Models\Request as ClientRequest;
use App\Modules\ClientRelationship\Services\ClientRelationshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientPortalController extends Controller
{
    public function __construct(
        protected ClientRelationshipService $clientService
    ) {}

    /**
     * Tableau de bord du portail client : demandes déposées et dossiers en cours.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $client = $user->client;

        if (! $client) {
            abort(403, 'Aucun profil client n\'est rattaché à votre compte.');
        }

        $requests = $client->requests()
            ->with(['framings.latestVersion', 'framings.missions'])
            ->latest('received_at')
            ->get();

        return view('client-portal.index', compact('client', 'requests'));
    }

    /**
     * Formulaire de dépôt d'une nouvelle demande par le client.
     */
    public function createRequest(Request $request): View
    {
        $client = $request->user()->client;

        if (! $client) {
            abort(403, 'Aucun profil client n\'est rattaché à votre compte.');
        }

        return view('client-portal.requests.create', compact('client'));
    }

    /**
     * Enregistrer une nouvelle demande soumise depuis le portail client.
     */
    public function storeRequest(Request $request): RedirectResponse
    {
        $user = $request->user();
        $client = $user->client;

        if (! $client) {
            abort(403, 'Aucun profil client n\'est rattaché à votre compte.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'context' => ['nullable', 'string'],
            'expected_outcome' => ['nullable', 'string'],
        ], [
            'title.required' => 'Veuillez indiquer l\'objet de votre demande.',
            'description.required' => 'Veuillez décrire votre besoin ou problématique.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
        ]);

        $clientRequest = $this->clientService->submitRequest($client, [
            ...$validated,
            'source_channel' => 'portail_client',
        ], $user);

        return redirect()
            ->route('client-portal.requests.show', $clientRequest)
            ->with('success', 'Votre demande a bien été enregistrée sous la référence '.$clientRequest->reference.'.');
    }

    /**
     * Consulter le suivi d'une demande côté client (sans exposer les notes internes du cabinet).
     */
    public function showRequest(Request $request, ClientRequest $clientRequest): View
    {
        $client = $request->user()->client;

        if (! $client || $clientRequest->client_id !== $client->id) {
            abort(403, 'Vous n\'avez pas accès à cette demande.');
        }

        $clientRequest->load([
            'initialUnderstanding',
            'framings.versions' => fn ($query) => $query->whereIn('status', [
                FramingStatus::Submitted->value,
                FramingStatus::Accepted->value,
                FramingStatus::Rejected->value,
            ])->orderByDesc('version_number'),
            'framings.missions',
        ]);

        return view('client-portal.requests.show', compact('client', 'clientRequest'));
    }

    /**
     * Enregistrer la décision du client sur une proposition de cadrage soumise.
     */
    public function decideFraming(
        Request $request,
        ClientRequest $clientRequest,
        FramingVersion $version
    ): RedirectResponse {
        $client = $request->user()->client;

        if (! $client || $clientRequest->client_id !== $client->id) {
            abort(403, 'Vous n\'avez pas accès à cette demande.');
        }

        if ($version->framing->request_id !== $clientRequest->id) {
            abort(404);
        }

        if ($version->status !== FramingStatus::Submitted) {
            return back()->with('error', 'Cette proposition n\'est plus en attente de décision.');
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:accept,reject'],
            'client_feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $accepted = $validated['decision'] === 'accept';
        $this->clientService->recordClientDecision($version, $accepted, $validated['client_feedback'] ?? null);

        $message = $accepted
            ? 'Votre accord sur la proposition de cadrage a bien été enregistré.'
            : 'Votre refus et vos remarques ont bien été transmis au cabinet.';

        return redirect()
            ->route('client-portal.requests.show', $clientRequest)
            ->with('success', $message);
    }
}
