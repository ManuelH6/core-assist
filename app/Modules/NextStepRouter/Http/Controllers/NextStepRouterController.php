<?php

namespace App\Modules\NextStepRouter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NextStepRouter\Models\NextStepAction;
use App\Modules\NextStepRouter\Models\Recommendation;
use App\Modules\NextStepRouter\Services\NextStepRouterService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NextStepRouterController extends Controller
{
    public function __construct(
        protected NextStepRouterService $routerService
    ) {}

    /**
     * Affiche l'espace Next-Step Router™ de la mission.
     */
    public function show(Request $request, Mission $mission): View|JsonResponse
    {
        $router = $mission->nextStepRouter;

        if (! $router) {
            $router = $this->routerService->prepareNextStepRouter($mission, $request->user());
        }

        $router->load([
            'recommendations.actions',
            'actions.recommendation',
            'actions.responsible',
        ]);

        $initialData = $this->routerService->getInitialData($mission);
        $recommendations = $router->recommendations;
        $actions = $router->actions;
        $roadmapByHorizon = $router->roadmapByHorizon();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'router' => $router,
                    'initial_data' => $initialData,
                    'roadmap' => $roadmapByHorizon,
                ],
            ]);
        }

        return view('next-step-routers.show', compact('mission', 'router', 'initialData', 'recommendations', 'actions', 'roadmapByHorizon'));
    }

    /**
     * Génère automatiquement des propositions de recommandations à partir du diagnostic.
     */
    public function prepare(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $router = $mission->nextStepRouter;
        if (! $router) {
            $router = $this->routerService->prepareNextStepRouter($mission, $request->user());
        }

        $recommendations = $this->routerService->prepareRecommendations($router, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => count($recommendations).' proposition(s) de recommandation générée(s).',
                'data' => $recommendations,
            ]);
        }

        return redirect()->route('missions.next-step-router.show', $mission)
            ->with('success', count($recommendations).' proposition(s) de recommandation formulée(s) à partir du diagnostic.');
    }

    /**
     * Crée une recommandation stratégique.
     */
    public function storeRecommendation(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'domain' => 'nullable|string|max:100',
            'axis' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'justification' => 'nullable|string',
            'priority' => 'required|string|in:critical,high,medium,low',
        ]);

        $router = $mission->nextStepRouter;
        if (! $router) {
            $router = $this->routerService->prepareNextStepRouter($mission, $request->user());
        }

        $recommendation = $this->routerService->createRecommendation($router, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Recommandation enregistrée avec succès.',
                'data' => $recommendation,
            ]);
        }

        return redirect()->route('missions.next-step-router.show', $mission)
            ->with('success', "Recommandation {$recommendation->reference} créée.");
    }

    /**
     * Met à jour une recommandation existante.
     */
    public function updateRecommendation(Request $request, Mission $mission, Recommendation $recommendation): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'domain' => 'nullable|string|max:100',
            'axis' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'justification' => 'nullable|string',
            'priority' => 'required|string|in:critical,high,medium,low',
            'status' => 'required|string|in:proposed,validated,in_progress,implemented,rejected',
        ]);

        $updated = $this->routerService->updateRecommendation($recommendation, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Recommandation actualisée.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('missions.next-step-router.show', $mission)
            ->with('success', "Recommandation {$recommendation->reference} actualisée.");
    }

    /**
     * Crée une action concrète dans le Next-Step Router™.
     */
    public function storeAction(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'recommendation_id' => 'nullable|exists:recommendations,id',
            'description' => 'nullable|string',
            'expected_result' => 'nullable|string',
            'priority' => 'required|string|in:critical,high,medium,low',
            'horizon' => 'required|string|in:immediate,short_term,medium_term,long_term',
            'due_date' => 'nullable|date',
            'responsible_name' => 'nullable|string|max:255',
            'responsible_id' => 'nullable|exists:users,id',
            'problem_reference' => 'nullable|string|max:50',
            'finding_reference' => 'nullable|string|max:50',
            'decision_reference' => 'nullable|string|max:50',
        ]);

        $router = $mission->nextStepRouter;
        if (! $router) {
            $router = $this->routerService->prepareNextStepRouter($mission, $request->user());
        }

        $action = $this->routerService->createAction($router, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Action créée avec succès.',
                'data' => $action,
            ]);
        }

        return redirect()->route('missions.next-step-router.show', $mission)
            ->with('success', "Action {$action->reference} enregistrée.");
    }

    /**
     * Met à jour une action.
     */
    public function updateAction(Request $request, Mission $mission, NextStepAction $action): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'recommendation_id' => 'nullable|exists:recommendations,id',
            'description' => 'nullable|string',
            'expected_result' => 'nullable|string',
            'priority' => 'required|string|in:critical,high,medium,low',
            'horizon' => 'required|string|in:immediate,short_term,medium_term,long_term',
            'due_date' => 'nullable|date',
            'responsible_name' => 'nullable|string|max:255',
            'responsible_id' => 'nullable|exists:users,id',
            'status' => 'required|string|in:proposed,validated,planned,in_progress,completed,blocked,abandoned',
            'actual_result' => 'nullable|string',
        ]);

        $updated = $this->routerService->updateAction($action, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Action actualisée.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('missions.next-step-router.show', $mission)
            ->with('success', "Action {$action->reference} actualisée.");
    }

    /**
     * Met à jour l'état d'une action (workflow).
     */
    public function updateActionStatus(Request $request, Mission $mission, NextStepAction $action): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:proposed,validated,planned,in_progress,completed,blocked,abandoned',
        ]);

        $updated = $this->routerService->updateActionStatus($action, $validated['status']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Statut de l’action actualisé.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('missions.next-step-router.show', $mission)
            ->with('success', "Statut de l'action {$action->reference} mis à jour : « {$validated['status']} ».");
    }

    /**
     * Consolide la photographie d'inventaire complet (`output_snapshot`).
     */
    public function snapshot(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $router = $mission->nextStepRouter;
        if (! $router) {
            $router = $this->routerService->prepareNextStepRouter($mission, $request->user());
        }

        $snapshot = $this->routerService->generateSnapshot($router);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Instantané consolidé enregistré.',
                'data' => $snapshot,
            ]);
        }

        return redirect()->route('missions.next-step-router.show', $mission)
            ->with('success', 'Feuille de route et plan d’action consolidés dans l’audit trail.');
    }
}
