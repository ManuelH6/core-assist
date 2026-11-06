<?php

namespace App\Modules\ProjectManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ProjectManagement\Http\Services\CollaboratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollaboratorController extends Controller
{
    public function __construct(
        protected CollaboratorService $collaboratorService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $collaborators = $this->collaboratorService->getAll();
        $teamWorkload = $this->collaboratorService->getTeamWorkloadSummary();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $collaborators,
                'summary' => $teamWorkload,
            ]);
        }

        return view('collaborators.index', compact('collaborators', 'teamWorkload'));
    }

    public function create(): View
    {
        return view('collaborators.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:collaborators,email'],
            'role_title' => ['required', 'string', 'max:255'],
            'weekly_capacity_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $collaborator = $this->collaboratorService->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collaborateur créé avec succès.',
                'data' => $collaborator,
            ], 201);
        }

        return redirect()->route('collaborators.show', $collaborator)
            ->with('success', 'Collaborateur ajouté à l\'équipe.');
    }

    public function show(Request $request, int $id): View|JsonResponse
    {
        $collaborator = $this->collaboratorService->findById($id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $collaborator,
            ]);
        }

        return view('collaborators.show', compact('collaborator'));
    }

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $collaborator = $this->collaboratorService->findById($id);
        $this->collaboratorService->delete($collaborator);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collaborateur supprimé.',
            ]);
        }

        return redirect()->route('collaborators.index')
            ->with('success', 'Collaborateur supprimé.');
    }
}
