<?php

namespace App\Modules\Analysis\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analysis\Http\Services\FinancialAnalysisService;
use App\Modules\FinancialCollection\Http\Models\FinancialCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    public function __construct(
        protected FinancialAnalysisService $analysisService
    ) {}

    /**
     * Liste des analyses effectuées.
     */
    public function index(Request $request): View|JsonResponse
    {
        $analyses = $this->analysisService->getAll();
        $collections = FinancialCollection::latest()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $analyses,
            ]);
        }

        return view('analyses.index', compact('analyses', 'collections'));
    }

    /**
     * Exécuter une nouvelle analyse financière à partir d'une collecte.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'financial_collection_id' => ['required', 'exists:financial_collections,id'],
        ]);

        $collection = FinancialCollection::findOrFail($validated['financial_collection_id']);

        $analysis = $this->analysisService->analyzeCollection($collection);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Analyse financière générée avec succès.',
                'data' => $analysis,
            ], 201);
        }

        return redirect()->route('analyses.show', $analysis)
            ->with('success', 'Analyse financière calculée avec succès !');
    }

    /**
     * Consulter le détail d'une analyse (avec son snapshot historique).
     */
    public function show(Request $request, int $id): View|JsonResponse
    {
        $analysis = $this->analysisService->findById($id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $analysis,
            ]);
        }

        return view('analyses.show', compact('analysis'));
    }

    /**
     * Liste des analyses d'une collecte spécifique.
     */
    public function byCollection(int $collectionId): JsonResponse
    {
        $analyses = $this->analysisService->getByCollectionId($collectionId);

        return response()->json([
            'success' => true,
            'data' => $analyses,
        ]);
    }
}
