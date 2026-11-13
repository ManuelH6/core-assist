<?php

namespace App\Modules\ClientRelationship\Http\Controllers;

use App\Enums\FramingStatus;
use App\Http\Controllers\Controller;
use App\Modules\ClientRelationship\Http\Models\Framing;
use App\Modules\ClientRelationship\Http\Models\FramingVersion;
use App\Modules\ClientRelationship\Http\Models\Request as ClientRequest;
use App\Modules\ClientRelationship\Services\ClientRelationshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FramingController extends Controller
{
    public function __construct(
        protected ClientRelationshipService $clientService
    ) {}

    public function store(Request $request, ClientRequest $clientRequest): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'objectives' => ['required', 'string'],
            'scope_summary' => ['nullable', 'string'],
            'deliverables' => ['nullable', 'string'],
            'timeline' => ['nullable', 'string'],
            'conditions' => ['nullable', 'string'],
        ]);

        $framing = $this->clientService->createFraming($clientRequest, $validated, $request->user());

        return redirect()
            ->route('framings.show', $framing)
            ->with('success', 'Cadrage créé avec la version V1.');
    }

    public function show(Framing $framing): View
    {
        $framing->load([
            'request.client',
            'request.initialUnderstanding',
            'versions' => fn ($query) => $query->orderByDesc('version_number'),
            'versions.preparer',
            'missions',
        ]);

        return view('framings.show', compact('framing'));
    }

    public function storeVersion(Request $request, Framing $framing): RedirectResponse
    {
        $validated = $request->validate([
            'objectives' => ['required', 'string'],
            'scope_summary' => ['nullable', 'string'],
            'deliverables' => ['nullable', 'string'],
            'timeline' => ['nullable', 'string'],
            'conditions' => ['nullable', 'string'],
        ]);

        $version = $this->clientService->addFramingVersion($framing, $validated, $request->user());

        return redirect()
            ->route('framings.show', $framing)
            ->with('success', 'Version V'.$version->version_number.' ajoutée au cadrage.');
    }

    public function submitVersion(Framing $framing, FramingVersion $version): RedirectResponse
    {
        if ($version->framing_id !== $framing->id) {
            abort(404);
        }

        if ($version->status !== FramingStatus::Draft) {
            return back()->with('error', 'Seule une version en brouillon peut être soumise.');
        }

        $this->clientService->submitFramingVersion($version);

        return redirect()
            ->route('framings.show', $framing)
            ->with('success', 'La version V'.$version->version_number.' a été soumise au client (visible sur son portail).');
    }

    public function recordDecision(Request $request, Framing $framing, FramingVersion $version): RedirectResponse
    {
        if ($version->framing_id !== $framing->id) {
            abort(404);
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:accept,reject'],
            'client_feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $accepted = $validated['decision'] === 'accept';
        $this->clientService->recordClientDecision($version, $accepted, $validated['client_feedback'] ?? null);

        $message = $accepted
            ? 'Accord client enregistré. Vous pouvez maintenant créer la mission.'
            : 'Refus client enregistré. Vous pouvez préparer une nouvelle version révisée.';

        return redirect()
            ->route('framings.show', $framing)
            ->with('success', $message);
    }
}
