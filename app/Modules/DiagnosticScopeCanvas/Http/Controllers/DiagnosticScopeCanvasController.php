<?php

namespace App\Modules\DiagnosticScopeCanvas\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\DiagnosticScopeCanvas\Services\DiagnosticScopeCanvasService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\ToolManagement\Services\MissionToolConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class DiagnosticScopeCanvasController extends Controller
{
    public function __construct(
        protected DiagnosticScopeCanvasService $scopeService,
        protected MissionToolConfigurationService $configService
    ) {}

    /**
     * Charger les données de préparation du périmètre (contexte mission + référentiel).
     */
    public function initialData(Request $request, Mission $mission): JsonResponse|View
    {
        $requiredAction = $request->wantsJson() ? 'view' : 'edit';
        $this->authorizeToolAction($request, $mission, $requiredAction);

        $data = $this->scopeService->prepareScope($mission);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('scope-canvases.edit', compact('mission', 'data'));
    }

    /**
     * Consulter le Diagnostic Scope Canvas d'une mission.
     */
    public function show(Request $request, Mission $mission): JsonResponse|View
    {
        $this->authorizeToolAction($request, $mission, 'view');

        $canvas = $this->scopeService->getScope($mission);

        if (! $canvas) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun Diagnostic Scope Canvas trouvé pour cette mission.',
                ], 404);
            }

            if ($this->configService->canUserPerform($mission, $request->user(), 'D360-DSC', 'edit')) {
                return redirect()->route('missions.scope-canvas.initial-data', $mission);
            }

            abort(403, 'Aucun périmètre défini et vous n\'êtes pas autorisé à le configurer.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $canvas,
            ]);
        }

        $dscPermissions = $this->configService->getEffectivePermissions($mission, $request->user(), 'D360-DSC');

        return view('scope-canvases.show', compact('mission', 'canvas', 'dscPermissions'));
    }

    /**
     * Enregistrer ou mettre à jour le périmètre du diagnostic.
     */
    public function store(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $this->authorizeToolAction($request, $mission, 'edit');

        return $this->persistScope(
            $request,
            $mission,
            201,
            'Périmètre du diagnostic enregistré avec succès.'
        );
    }

    /**
     * Mettre à jour le périmètre du diagnostic.
     */
    public function update(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $this->authorizeToolAction($request, $mission, 'edit');

        return $this->persistScope(
            $request,
            $mission,
            200,
            'Périmètre du diagnostic mis à jour avec succès.'
        );
    }

    /**
     * Soumettre le périmètre pour revue interne.
     */
    public function submit(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $this->authorizeToolAction($request, $mission, 'submit');

        $canvas = $this->scopeService->getScope($mission);

        if (! $canvas) {
            abort(404, 'Diagnostic Scope Canvas introuvable pour cette mission.');
        }

        try {
            $canvas = $this->scopeService->submitForReview($canvas, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->validationErrorResponse($request, $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Périmètre soumis pour revue avec succès.',
                'data' => $canvas,
            ]);
        }

        return redirect()->route('missions.scope-canvas.show', $mission)
            ->with('success', 'Périmètre soumis pour revue avec succès.');
    }

    /**
     * Valider officiellement le périmètre d'investigation.
     */
    public function validateScope(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $this->authorizeToolAction($request, $mission, 'validate');

        $canvas = $this->scopeService->getScope($mission);

        if (! $canvas) {
            abort(404, 'Diagnostic Scope Canvas introuvable pour cette mission.');
        }

        $validator = $request->user() ?? User::firstOrFail();
        $canvas = $this->scopeService->validateReview($canvas, $validator);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Périmètre validé avec succès.',
                'data' => $canvas,
            ]);
        }

        return redirect()->route('missions.scope-canvas.show', $mission)
            ->with('success', 'Périmètre d’investigation validé avec succès.');
    }

    /**
     * Persister le périmètre et retourner la réponse adaptée au canal (web ou API).
     */
    private function persistScope(
        Request $request,
        Mission $mission,
        int $statusCode,
        string $successMessage
    ): JsonResponse|RedirectResponse {
        try {
            $canvas = $this->scopeService->saveScope($mission, $request->all(), $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->validationErrorResponse($request, $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data' => $canvas,
            ], $statusCode);
        }

        return redirect()->route('missions.scope-canvas.show', $mission)
            ->with('success', $successMessage);
    }

    /**
     * Retourner une erreur métier exploitable sans provoquer d'erreur 500.
     */
    private function validationErrorResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return back()
            ->withInput()
            ->with('error', $message);
    }

    private function authorizeToolAction(Request $request, Mission $mission, string $action): void
    {
        if (! $this->configService->canUserPerform($mission, $request->user(), 'D360-DSC', $action)) {
            abort(403, 'Cette action n\'est pas autorisée selon la configuration de la mission.');
        }
    }
}
