<?php

namespace App\Modules\ClientRelationship\Http\Controllers;

use App\Enums\ClientType;
use App\Http\Controllers\Controller;
use App\Modules\ClientRelationship\Http\Models\Client;
use App\Modules\ClientRelationship\Services\ClientRelationshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct(
        protected ClientRelationshipService $clientService
    ) {}

    public function index(): View
    {
        $clients = Client::withCount(['requests', 'users'])
            ->orderBy('name')
            ->get();

        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        $clientTypes = ClientType::cases();

        return view('clients.create', compact('clientTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_type' => ['required', new Enum(ClientType::class)],
            'sector' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $client = $this->clientService->createClient($validated);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client enregistré avec succès.');
    }

    public function show(Client $client): View
    {
        $client->load([
            'requests' => fn ($query) => $query->latest('received_at'),
            'users' => fn ($query) => $query->orderBy('name'),
        ]);

        return view('clients.show', compact('client'));
    }

    public function storeUser(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $this->clientService->createClientPortalUser($client, $validated);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Compte d\'accès portail créé pour le client.');
    }
}
