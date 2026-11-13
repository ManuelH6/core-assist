<?php

namespace App\Modules\MissionFramingSheet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\MissionFramingSheet\Services\MissionFramingSheetService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MissionFramingSheetController extends Controller
{
    public function __construct(
        protected MissionFramingSheetService $framingService
    ) {}

    /**
     * Obtenir les données préparatoires de la fiche opérationnelle (héritées + spécifiques).
     */
    public function initialData(Request $request, Mission $mission): JsonResponse|View
    {
        $data = $this->framingService->prepareFraming($mission);
        $users = User::orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('framing-sheets.create', compact('mission', 'data', 'users'));
    }

    /**
     * Consulter la fiche de cadrage opérationnel d'une mission.
     */
    public function show(Request $request, Mission $mission): JsonResponse|View
    {
        $sheet = $this->framingService->getFraming($mission);

        if (! $sheet) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune fiche de cadrage opérationnel trouvée pour cette mission.',
                ], 404);
            }

            return redirect()->route('missions.show', $mission)->with('error', 'Fiche non trouvée.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $sheet,
            ]);
        }

        return view('framing-sheets.show', compact('mission', 'sheet'));
    }

    /**
     * Enregistrer ou mettre à jour la fiche de cadrage opérationnel.
     */
    public function store(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $sheet = $this->framingService->saveFraming($mission, $request->all(), $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Fiche de cadrage opérationnel enregistrée avec succès.',
                'data' => $sheet,
            ], 201);
        }

        return redirect()->route('missions.framing-sheet.show', $mission)
            ->with('success', 'Fiche opérationnelle enregistrée avec succès.');
    }

    /**
     * Mettre à jour la fiche de cadrage opérationnel.
     */
    public function update(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $sheet = $this->framingService->saveFraming($mission, $request->all(), $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Fiche de cadrage opérationnel mise à jour avec succès.',
                'data' => $sheet,
            ]);
        }

        return redirect()->route('missions.framing-sheet.show', $mission)
            ->with('success', 'Fiche opérationnelle mise à jour avec succès.');
    }

    /**
     * Soumettre la fiche opérationnelle pour revue.
     */
    public function submit(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $sheet = $this->framingService->getFraming($mission);

        if (! $sheet) {
            abort(404, 'Fiche opérationnelle introuvable.');
        }

        $sheet = $this->framingService->submitForReview($sheet, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Fiche soumise pour revue avec succès.',
                'data' => $sheet,
            ]);
        }

        return redirect()->route('missions.framing-sheet.show', $mission)
            ->with('success', 'Fiche soumise pour revue avec succès.');
    }
}
