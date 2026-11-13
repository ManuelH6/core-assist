<?php

namespace App\Modules\ClientRelationship\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Modules\ClientRelationship\Http\Models\Client;
use App\Modules\ClientRelationship\Http\Models\Request as ClientRequest;
use App\Modules\ClientRelationship\Services\ClientRelationshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function __construct(
        protected ClientRelationshipService $clientService
    ) {}

    public function index(Request $request): View
    {
        $statusFilter = $request->query('status');
        $clientFilter = $request->query('client_id');

        $query = ClientRequest::with(['client', 'initialUnderstanding', 'framings.latestVersion'])
            ->latest('received_at');

        if ($statusFilter && RequestStatus::tryFrom($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        if ($clientFilter) {
            $query->where('client_id', $clientFilter);
        }

        $requests = $query->get();
        $clients = Client::orderBy('name')->get();
        $statuses = RequestStatus::cases();

        $counts = [
            'total' => ClientRequest::count(),
            'received' => ClientRequest::where('status', RequestStatus::Received->value)->count(),
            'being_studied' => ClientRequest::where('status', RequestStatus::BeingStudied->value)->count(),
            'framed' => ClientRequest::where('status', RequestStatus::Framed->value)->count(),
            'converted' => ClientRequest::where('status', RequestStatus::Converted->value)->count(),
        ];

        return view('requests.index', compact(
            'requests',
            'clients',
            'statuses',
            'statusFilter',
            'clientFilter',
            'counts'
        ));
    }

    public function create(Request $request): View
    {
        $clients = Client::orderBy('name')->get();
        $selectedClientId = $request->query('client_id');

        return view('requests.create', compact('clients', 'selectedClientId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'source_channel' => ['required', 'in:email,telephone,rencontre,portail_client,partenaire'],
            'received_at' => ['nullable', 'date'],
            'context' => ['nullable', 'string'],
            'expected_outcome' => ['nullable', 'string'],
        ]);

        $client = Client::findOrFail($validated['client_id']);
        $clientRequest = $this->clientService->submitRequest($client, $validated, $request->user());

        return redirect()
            ->route('requests.show', $clientRequest)
            ->with('success', 'Demande enregistrée avec sa fiche de compréhension initiale.');
    }

    public function show(ClientRequest $clientRequest): View
    {
        $clientRequest->load([
            'client',
            'creator',
            'initialUnderstanding.preparer',
            'framings.latestVersion',
            'framings.missions',
        ]);

        $statuses = RequestStatus::cases();

        return view('requests.show', compact('clientRequest', 'statuses'));
    }

    public function updateUnderstanding(Request $request, ClientRequest $clientRequest): RedirectResponse
    {
        $validated = $request->validate([
            'context' => ['nullable', 'string'],
            'expressed_need' => ['nullable', 'string'],
            'reformulated_need' => ['nullable', 'string'],
            'expected_outcome' => ['nullable', 'string'],
            'central_question' => ['nullable', 'string'],
            'known_constraints_text' => ['nullable', 'string'],
            'missing_information_text' => ['nullable', 'string'],
            'initial_notes' => ['nullable', 'string'],
            'mark_completed' => ['nullable', 'boolean'],
        ]);

        $this->clientService->updateInitialUnderstanding($clientRequest, $validated, $request->user());

        return redirect()
            ->route('requests.show', $clientRequest)
            ->with('success', 'Fiche de compréhension initiale enregistrée.');
    }

    public function updateStatus(Request $request, ClientRequest $clientRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', new Enum(RequestStatus::class)],
        ]);

        $status = RequestStatus::from($validated['status']);
        $this->clientService->updateRequestStatus($clientRequest, $status);

        return redirect()
            ->route('requests.show', $clientRequest)
            ->with('success', 'Statut de la demande mis à jour : '.$status->label().'.');
    }
}
