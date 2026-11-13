<?php

namespace App\Modules\DiagnosticRestitutionDeck\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\DiagnosticRestitutionDeck\Models\RestitutionContent;
use App\Modules\DiagnosticRestitutionDeck\Models\RestitutionSection;
use App\Modules\DiagnosticRestitutionDeck\Services\DiagnosticRestitutionDeckService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticRestitutionDeckController extends Controller
{
    public function __construct(
        protected DiagnosticRestitutionDeckService $drdService
    ) {}

    /**
     * Affiche ou retourne le deck de restitution diagnostique d'une mission.
     */
    public function show(Request $request, Mission $mission): JsonResponse|View
    {
        $deck = $mission->restitutionDeck;

        if (! $deck) {
            $deck = $this->drdService->prepareRestitutionDeck($mission, $request->user());
        }

        $initialData = $this->drdService->getInitialData($mission);
        $sections = $deck->sections()->with(['contents.sources', 'validator'])->get();
        $versions = $deck->versions()->latest('version_number')->get();
        $users = User::orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'deck' => $deck,
                    'sections' => $sections,
                    'versions' => $versions,
                    'initial_data' => $initialData,
                ],
            ]);
        }

        return view('restitution-decks.show', compact(
            'mission',
            'deck',
            'sections',
            'versions',
            'initialData',
            'users'
        ));
    }

    /**
     * Initialise ou synchronise le deck de restitution avec les données d'amont.
     */
    public function prepare(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $deck = $this->drdService->prepareRestitutionDeck($mission, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Diagnostic Restitution Deck initialisé avec succès.',
                'data' => $deck,
            ]);
        }

        return redirect()->route('missions.restitution-deck.show', $mission)
            ->with('success', 'Trame de restitution initialisée avec succès.');
    }

    /**
     * Ajoute une nouvelle section.
     */
    public function storeSection(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'section_key' => 'nullable|string|max:50',
            'objective' => 'nullable|string',
            'position' => 'nullable|integer',
            'observations' => 'nullable|string',
        ]);

        $deck = $mission->restitutionDeck;
        if (! $deck) {
            $deck = $this->drdService->prepareRestitutionDeck($mission, $request->user());
        }

        $section = $this->drdService->addSection($deck, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Section ajoutée avec succès.',
                'data' => $section,
            ]);
        }

        return redirect()->route('missions.restitution-deck.show', $mission)
            ->with('success', "Section « {$section->title} » ajoutée.");
    }

    /**
     * Met à jour une section.
     */
    public function updateSection(Request $request, Mission $mission, RestitutionSection $section): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'objective' => 'nullable|string',
            'status' => 'required|string|in:to_prepare,drafting,to_review,validated',
            'observations' => 'nullable|string',
        ]);

        $updated = $this->drdService->updateSection($section, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Section mise à jour.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('missions.restitution-deck.show', $mission)
            ->with('success', 'Section mise à jour.');
    }

    /**
     * Supprime une section.
     */
    public function destroySection(Request $request, Mission $mission, RestitutionSection $section): JsonResponse|RedirectResponse
    {
        $this->drdService->removeSection($section);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Section supprimée.',
            ]);
        }

        return redirect()->route('missions.restitution-deck.show', $mission)
            ->with('success', 'Section retirée de la restitution.');
    }

    /**
     * Ajoute un contenu dans une section.
     */
    public function storeContent(Request $request, Mission $mission, RestitutionSection $section): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'content_type' => 'required|string|in:text,summary_table,domain_rating,strengths_list,difficulties_list,problems_list,causal_tree,conclusion_box,callout',
            'title' => 'nullable|string|max:255',
            'body_text' => 'nullable|string',
            'structured_data' => 'nullable|array',
            'is_custom_editorial' => 'nullable|boolean',
        ]);

        $content = $this->drdService->addContent($section, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Contenu ajouté à la section.',
                'data' => $content,
            ]);
        }

        return redirect()->route('missions.restitution-deck.show', $mission)
            ->with('success', 'Contenu ajouté.');
    }

    /**
     * Insère un résultat d'un autre outil en conservant le lien avec sa source.
     */
    public function importResult(Request $request, Mission $mission, RestitutionSection $section): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'source_tool' => 'required|string|in:mfs,dsc,dir,sig,d360,psg',
            'source_type' => 'required|string',
            'source_id' => 'required',
            'content_type' => 'nullable|string',
            'title' => 'required|string|max:255',
            'body_text' => 'nullable|string',
            'source_summary' => 'nullable|string',
            'source_notes' => 'nullable|string',
        ]);

        $content = $this->drdService->importToolResult(
            $section,
            $validated['source_tool'],
            $validated['source_type'],
            $validated['source_id'],
            $validated,
            $request->user()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Résultat importé avec succès.',
                'data' => $content,
            ]);
        }

        return redirect()->route('missions.restitution-deck.show', $mission)
            ->with('success', 'Résultat d’analyse importé dans la section.');
    }

    /**
     * Met à jour un contenu éditorial.
     */
    public function updateContent(Request $request, Mission $mission, RestitutionContent $content): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'body_text' => 'nullable|string',
            'structured_data' => 'nullable|array',
            'is_custom_editorial' => 'nullable|boolean',
        ]);

        $updated = $this->drdService->updateContent($content, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Contenu mis à jour.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('missions.restitution-deck.show', $mission)
            ->with('success', 'Contenu rédactionnel mis à jour.');
    }

    /**
     * Valide formellement une section.
     */
    public function validateSection(Request $request, Mission $mission, RestitutionSection $section): JsonResponse|RedirectResponse
    {
        $validatedSection = $this->drdService->validateSection($section, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Section validée.',
                'data' => $validatedSection,
            ]);
        }

        return redirect()->route('missions.restitution-deck.show', $mission)
            ->with('success', "Section « {$section->title} » validée.");
    }

    /**
     * Génère l'instantané consolidé et archive une version de la restitution.
     */
    public function generateSnapshot(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $deck = $mission->restitutionDeck;
        if (! $deck) {
            $deck = $this->drdService->prepareRestitutionDeck($mission, $request->user());
        }

        $versionTitle = $request->input('version_title');
        $result = $this->drdService->generateSnapshot($deck, $versionTitle, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Instantané et version de restitution générés.',
                'data' => $result,
            ]);
        }

        return redirect()->route('missions.restitution-deck.show', $mission)
            ->with('success', "Version {$result['version']->version_number} de la restitution consolidée avec succès.");
    }
}
