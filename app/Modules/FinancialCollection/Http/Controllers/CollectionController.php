<?php

namespace App\Modules\FinancialCollection\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FinancialCollection\Http\Services\FinancialCollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function __construct(
        protected FinancialCollectionService $collectionService
    ) {}

    /**
     * Liste des collectes financières.
     */
    public function index(Request $request): View|JsonResponse
    {
        $collections = $this->collectionService->getAll();
        $collections->load('analyses');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $collections,
            ]);
        }

        return view('collections.index', compact('collections'));
    }

    /**
     * Afficher le formulaire de création d'une collecte.
     */
    public function create(): View
    {
        return view('collections.create');
    }

    /**
     * Enregistrer une nouvelle collecte financière.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'entry_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'daily_quantity' => ['required', 'integer', 'min:0'],
            'unit_production_cost' => ['required', 'numeric', 'min:0'],
            'created_by' => ['nullable', 'exists:users,id'],
        ]);

        if (empty($validated['currency'])) {
            $validated['currency'] = 'XOF';
        }

        if (empty($validated['created_by']) && $request->user()) {
            $validated['created_by'] = $request->user()->id;
        }

        $collection = $this->collectionService->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collecte financière créée avec succès.',
                'data' => $collection,
            ], 201);
        }

        return redirect()->route('collections.show', $collection)
            ->with('success', 'Collecte financière créée avec succès.');
    }

    /**
     * Afficher les détails d'une collecte.
     */
    public function show(Request $request, int $id): View|JsonResponse
    {
        $collection = $this->collectionService->findById($id);
        $collection->load('analyses');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $collection,
            ]);
        }

        return view('collections.show', compact('collection'));
    }

    /**
     * Mettre à jour une collecte.
     */
    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $collection = $this->collectionService->findById($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'entry_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'selling_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'daily_quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            'unit_production_cost' => ['sometimes', 'required', 'numeric', 'min:0'],
        ]);

        $updated = $this->collectionService->update($collection, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collecte mise à jour avec succès.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('collections.show', $collection)
            ->with('success', 'Collecte mise à jour avec succès.');
    }

    /**
     * Supprimer une collecte.
     */
    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $collection = $this->collectionService->findById($id);
        $this->collectionService->delete($collection);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collecte supprimée avec succès.',
            ]);
        }

        return redirect()->route('collections.index')
            ->with('success', 'Collecte supprimée avec succès.');
    }
}
