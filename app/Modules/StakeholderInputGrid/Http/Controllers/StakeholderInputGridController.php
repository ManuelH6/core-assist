<?php

namespace App\Modules\StakeholderInputGrid\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\StakeholderInputGrid\Models\ExpectedContribution;
use App\Modules\StakeholderInputGrid\Models\Stakeholder;
use App\Modules\StakeholderInputGrid\Services\StakeholderInputGridService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StakeholderInputGridController extends Controller
{
    public function __construct(
        protected StakeholderInputGridService $sigService
    ) {}

    /**
     * Consulter le Stakeholder Input Grid d'une mission.
     */
    public function show(Request $request, Mission $mission): JsonResponse|View|RedirectResponse
    {
        $grid = $mission->stakeholderGrid;

        if (! $grid) {
            $grid = $this->sigService->initializeGrid($mission, $request->user());
        }

        $progress = $this->sigService->getMobilizationProgress($grid);
        $users = User::orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'grid' => $grid->load(['stakeholders.expectedContributions', 'expectedContributions.assignedUser', 'receivedContributions']),
                    'progress' => $progress,
                ],
            ]);
        }

        $stakeholders = $grid->stakeholders()->with('expectedContributions')->get();
        $expectedContributions = $grid->expectedContributions()->with(['stakeholder', 'assignedUser'])->get();
        $receivedContributions = $grid->receivedContributions()->with(['stakeholder', 'expectedContribution', 'requiredInformation', 'recorder'])->latest()->get();
        $requiredInformation = $mission->intakeRegister?->requiredInformation ?? collect();
        $preparation = $this->sigService->prepareStakeholderInputGrid($mission);

        return view('stakeholder-grids.show', compact('mission', 'grid', 'progress', 'stakeholders', 'expectedContributions', 'receivedContributions', 'users', 'requiredInformation', 'preparation'));
    }

    /**
     * Initialiser ou actualiser la grille des parties prenantes.
     */
    public function initialize(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $grid = $this->sigService->initializeGrid($mission, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Grille des parties prenantes initialisée avec succès.',
                'data' => $grid,
            ]);
        }

        return redirect()->route('missions.stakeholder-grid.show', $mission)
            ->with('success', 'Grille des parties prenantes initialisée avec les suggestions du diagnostic.');
    }

    /**
     * Enregistrer une nouvelle partie prenante.
     */
    public function storeStakeholder(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $grid = $mission->stakeholderGrid ?? $this->sigService->initializeGrid($mission, $request->user());
        $stakeholder = $this->sigService->identifyStakeholder($grid, $request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Partie prenante enregistrée avec succès.',
                'data' => $stakeholder,
            ], 201);
        }

        return redirect()->route('missions.stakeholder-grid.show', $mission)
            ->with('success', 'Partie prenante ajoutée avec succès.');
    }

    /**
     * Modifier une partie prenante.
     */
    public function updateStakeholder(Request $request, Mission $mission, Stakeholder $stakeholder): JsonResponse|RedirectResponse
    {
        $updated = $this->sigService->updateStakeholder($stakeholder, $request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Partie prenante mise à jour.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('missions.stakeholder-grid.show', $mission)
            ->with('success', 'Partie prenante mise à jour.');
    }

    /**
     * Définir une contribution attendue.
     */
    public function storeExpectedContribution(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $grid = $mission->stakeholderGrid ?? $this->sigService->initializeGrid($mission, $request->user());
        $contribution = $this->sigService->defineExpectedContribution($grid, $request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Contribution attendue consignée.',
                'data' => $contribution,
            ], 201);
        }

        return redirect()->route('missions.stakeholder-grid.show', $mission)
            ->with('success', 'Contribution attendue enregistrée.');
    }

    /**
     * Affecter un consultant responsable de la mobilisation.
     */
    public function assignMobilization(Request $request, Mission $mission, ExpectedContribution $contribution): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $assigned = $this->sigService->assignMobilization($contribution, $validated['assigned_to'] ?? null);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Responsable de mobilisation affecté.',
                'data' => $assigned,
            ]);
        }

        return redirect()->route('missions.stakeholder-grid.show', $mission)
            ->with('success', 'Responsable de mobilisation affecté.');
    }

    /**
     * Actualiser le statut de mobilisation.
     */
    public function updateMobilizationStatus(Request $request, Mission $mission, ExpectedContribution $contribution): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:to_solicit,solicited,scheduled,completed,unavailable,cancelled'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $updated = $this->sigService->updateMobilizationStatus($contribution, $validated['status'], $validated['scheduled_at'] ?? null);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Statut de mobilisation mis à jour.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('missions.stakeholder-grid.show', $mission)
            ->with('success', 'Statut de mobilisation actualisé.');
    }

    /**
     * Enregistrer une contribution effectivement recueillie.
     */
    public function storeReceivedContribution(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $grid = $mission->stakeholderGrid ?? $this->sigService->initializeGrid($mission, $request->user());
        $received = $this->sigService->recordContribution($grid, $request->all(), $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Contribution recueillie enregistrée avec succès.',
                'data' => $received,
            ], 201);
        }

        return redirect()->route('missions.stakeholder-grid.show', $mission)
            ->with('success', 'Contribution recueillie enregistrée avec succès.');
    }

    /**
     * Obtenir l'avancement métrique de la mobilisation.
     */
    public function progress(Request $request, Mission $mission): JsonResponse
    {
        $grid = $mission->stakeholderGrid;
        if (! $grid) {
            return response()->json([
                'success' => false,
                'message' => 'Grille non initialisée.',
            ], 404);
        }

        $progress = $this->sigService->getMobilizationProgress($grid);

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }
}
