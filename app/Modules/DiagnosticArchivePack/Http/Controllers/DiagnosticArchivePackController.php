<?php

namespace App\Modules\DiagnosticArchivePack\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DiagnosticArchivePack\Models\ArchiveCategory;
use App\Modules\DiagnosticArchivePack\Models\ArchiveItem;
use App\Modules\DiagnosticArchivePack\Services\DiagnosticArchivePackService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticArchivePackController extends Controller
{
    public function __construct(
        protected DiagnosticArchivePackService $archiveService
    ) {}

    /**
     * Affiche le dossier Diagnostic Archive Pack™ pour la mission.
     */
    public function show(Request $request, Mission $mission): View|JsonResponse
    {
        $pack = $mission->archivePack;

        if (! $pack) {
            $pack = $this->archiveService->initializeArchive($mission, $request->user());
        }

        $pack->load(['items.primaryCategory', 'items.categories', 'items.versions', 'items.sources']);
        $categories = ArchiveCategory::orderBy('position')->get();
        $completeness = $this->archiveService->checkArchiveCompleteness($pack);
        $initialData = $this->archiveService->getInitialData($mission);
        $history = $this->archiveService->getArchiveHistory($pack)->take(20);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'pack' => $pack,
                    'completeness' => $completeness,
                    'initial_data' => $initialData,
                ],
            ]);
        }

        return view('archive-packs.show', compact('mission', 'pack', 'categories', 'completeness', 'initialData', 'history'));
    }

    /**
     * Auto-collecte tous les éléments issus des 8 outils du diagnostic 360°.
     */
    public function collect(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $pack = $mission->archivePack;
        if (! $pack) {
            $pack = $this->archiveService->initializeArchive($mission, $request->user());
        }

        $results = $this->archiveService->collectAvailableElements($pack, $request->user());
        $totalCollected = array_sum($results);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$totalCollected} éléments découverts et synchronisés.",
                'data' => [
                    'collected' => $results,
                    'total' => $totalCollected,
                    'completeness' => $this->archiveService->checkArchiveCompleteness($pack),
                ],
            ]);
        }

        return redirect()->route('missions.archive-pack.show', $mission)
            ->with('success', "Collecte automatique réussie : {$totalCollected} élément(s) synchronisé(s) dans le dossier d’archive.");
    }

    /**
     * Enregistre manuellement un élément dans l'archive.
     */
    public function storeItem(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:archive_categories,id',
            'item_type' => 'required|string|in:document,tool_snapshot,data_record,contribution,decision,restitution_version,deliverable,export,other',
            'source_tool' => 'nullable|string|in:mfs,dsc,dir,sig,d360,psg,drd,ldc,nsr,external,mission',
            'source_reference' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'author_name' => 'nullable|string|max:255',
            'location_type' => 'nullable|string|in:database_record,file_path,external_url',
            'location_path' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:archive_categories,id',
        ]);

        $pack = $mission->archivePack;
        if (! $pack) {
            $pack = $this->archiveService->initializeArchive($mission, $request->user());
        }

        $item = $this->archiveService->registerArchiveItem($pack, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Élément archivé avec succès.',
                'data' => $item,
            ]);
        }

        return redirect()->route('missions.archive-pack.show', $mission)
            ->with('success', "Élément « {$item->title} » enregistré dans l'archive.");
    }

    /**
     * Met à jour les métadonnées d'un élément d'archive.
     */
    public function updateItem(Request $request, Mission $mission, ArchiveItem $item): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:archive_categories,id',
            'description' => 'nullable|string',
            'author_name' => 'nullable|string|max:255',
            'location_path' => 'nullable|string',
            'status' => 'required|string|in:available,replaced,deprecated,archived',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:archive_categories,id',
        ]);

        $updated = $this->archiveService->updateArchiveItem($item, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Élément d’archive actualisé.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('missions.archive-pack.show', $mission)
            ->with('success', "Élément {$item->reference} actualisé.");
    }

    /**
     * Enregistre une nouvelle version d'un élément d'archive.
     */
    public function storeVersion(Request $request, Mission $mission, ArchiveItem $item): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'version_tag' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'location_path' => 'nullable|string',
            'changelog' => 'required|string',
        ]);

        $version = $this->archiveService->registerVersion($item, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Nouvelle version enregistrée.',
                'data' => $version,
            ]);
        }

        return redirect()->route('missions.archive-pack.show', $mission)
            ->with('success', "Version {$version->version_tag} enregistrée pour l’élément {$item->reference}.");
    }

    /**
     * Relie un élément d'archive à une source justificative.
     */
    public function storeSource(Request $request, Mission $mission, ArchiveItem $item): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'source_tool' => 'required|string|in:mfs,dsc,dir,sig,d360,psg,drd,ldc,nsr,project,client,external',
            'source_type' => 'required|string|max:50',
            'source_reference' => 'nullable|string|max:50',
            'relationship_type' => 'required|string|in:origin,justification,evidence,version_of,dependency',
            'notes' => 'nullable|string',
        ]);

        $source = $this->archiveService->linkSource($item, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lien de source probante enregistré.',
                'data' => $source,
            ]);
        }

        return redirect()->route('missions.archive-pack.show', $mission)
            ->with('success', "Source probante reliée à {$item->reference}.");
    }

    /**
     * Clôture formelle du dossier d'archive.
     */
    public function close(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $pack = $mission->archivePack;
        if (! $pack) {
            $pack = $this->archiveService->initializeArchive($mission, $request->user());
        }

        $observations = $request->input('observations');
        $closedPack = $this->archiveService->closeArchive($pack, $observations);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Dossier d’archive clôturé avec succès.',
                'data' => $closedPack,
            ]);
        }

        return redirect()->route('missions.archive-pack.show', $mission)
            ->with('success', 'Dossier d’archive vérifié et clôturé avec succès.');
    }

    /**
     * Consolide la photographie d'inventaire complet (`output_snapshot`).
     */
    public function snapshot(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $pack = $mission->archivePack;
        if (! $pack) {
            $pack = $this->archiveService->initializeArchive($mission, $request->user());
        }

        $snapshot = $this->archiveService->generateSnapshot($pack);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Inventaire consolidé enregistré.',
                'data' => $snapshot,
            ]);
        }

        return redirect()->route('missions.archive-pack.show', $mission)
            ->with('success', 'Photographie d’inventaire et audit trail consolidés.');
    }

    /**
     * Recherche dans les éléments archivés.
     */
    public function search(Request $request, Mission $mission): JsonResponse|View
    {
        $pack = $mission->archivePack;
        if (! $pack) {
            $pack = $this->archiveService->initializeArchive($mission, $request->user());
        }

        $query = $request->input('q');
        $categoryCode = $request->input('category');
        $tool = $request->input('tool');

        $items = $this->archiveService->searchArchive($pack, $query, $categoryCode, $tool);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $items->count(),
                'data' => $items,
            ]);
        }

        $categories = ArchiveCategory::orderBy('position')->get();
        $completeness = $this->archiveService->checkArchiveCompleteness($pack);
        $initialData = $this->archiveService->getInitialData($mission);
        $history = $this->archiveService->getArchiveHistory($pack)->take(20);

        return view('archive-packs.show', compact('mission', 'pack', 'categories', 'completeness', 'initialData', 'history', 'items'));
    }
}
