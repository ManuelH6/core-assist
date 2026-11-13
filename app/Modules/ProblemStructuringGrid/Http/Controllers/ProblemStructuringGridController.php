<?php

namespace App\Modules\ProblemStructuringGrid\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\ProblemStructuringGrid\Models\ProblemCause;
use App\Modules\ProblemStructuringGrid\Models\StructuredProblem;
use App\Modules\ProblemStructuringGrid\Services\ProblemStructuringGridService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProblemStructuringGridController extends Controller
{
    public function __construct(
        protected ProblemStructuringGridService $psgService
    ) {}

    /**
     * Affiche ou retourne la grille de structuration des problèmes pour une mission.
     */
    public function show(Request $request, Mission $mission): JsonResponse|View
    {
        $grid = $mission->problemGrid;

        if (! $grid) {
            $grid = $this->psgService->prepareProblemStructuringGrid($mission, $request->user());
        }

        $initialData = $this->psgService->getInitialData($mission);
        $prioritizedProblems = $this->psgService->prioritizeProblems($grid);
        $structure = $this->psgService->generateProblemStructure($grid);
        $relations = $grid->relations()->with(['sourceProblem', 'targetProblem'])->get();
        $users = User::orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'grid' => $grid,
                    'problems' => $prioritizedProblems,
                    'relations' => $relations,
                    'structure' => $structure,
                ],
            ]);
        }

        return view('problem-grids.show', compact(
            'mission',
            'grid',
            'prioritizedProblems',
            'structure',
            'relations',
            'initialData',
            'users'
        ));
    }

    /**
     * Initialise ou synchronise la grille avec les difficultés constatées dans le D360.
     */
    public function prepare(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $grid = $this->psgService->prepareProblemStructuringGrid($mission, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Grille de structuration des problèmes initialisée avec succès.',
                'data' => $grid,
            ]);
        }

        return redirect()->route('missions.problem-grid.show', $mission)
            ->with('success', 'Grille synchronisée avec succès depuis les constats du Diagnostic 360°.');
    }

    /**
     * Enregistre un nouveau problème structuré.
     */
    public function storeProblem(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reference' => 'nullable|string|max:30',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'domain' => 'required|string|max:100',
            'axis' => 'required|string|max:150',
            'subaxis' => 'nullable|string|max:150',
            'diagnostic_finding_id' => 'nullable|exists:diagnostic_findings,id',
            'manifestations' => 'nullable|string',
            'status' => 'nullable|string|in:to_examine,in_analysis,structured,validated',
        ]);

        $grid = $mission->problemGrid;
        if (! $grid) {
            $grid = $this->psgService->prepareProblemStructuringGrid($mission, $request->user());
        }

        $problem = $this->psgService->createProblem($grid, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Problème enregistré avec succès.',
                'data' => $problem,
            ]);
        }

        return redirect()->route('missions.problem-grid.show', $mission)
            ->with('success', "Problème {$problem->reference} consigné.");
    }

    /**
     * Met à jour un problème structuré.
     */
    public function updateProblem(Request $request, Mission $mission, StructuredProblem $problem): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'domain' => 'nullable|string|max:100',
            'axis' => 'nullable|string|max:150',
            'subaxis' => 'nullable|string|max:150',
            'manifestations' => 'nullable|string',
            'status' => 'required|string|in:to_examine,in_analysis,structured,validated',
        ]);

        $updated = $this->psgService->updateProblem($problem, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Problème mis à jour.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('missions.problem-grid.show', $mission)
            ->with('success', "Problème {$problem->reference} mis à jour.");
    }

    /**
     * Enregistre une cause pour un problème.
     */
    public function storeCause(Request $request, Mission $mission, StructuredProblem $problem): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'cause' => 'required|string|max:255',
            'nature' => 'required|string|in:organizational,human,financial,technical,strategic,other',
            'explanation' => 'nullable|string',
            'confirmation_level' => 'required|string|in:hypothesis,partially_supported,confirmed',
            'comments' => 'nullable|string',
        ]);

        $cause = $this->psgService->registerCause($problem, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cause enregistrée.',
                'data' => $cause,
            ]);
        }

        return redirect()->route('missions.problem-grid.show', $mission)
            ->with('success', 'Cause associée au problème.');
    }

    /**
     * Met à jour le niveau de confirmation d'une cause.
     */
    public function updateCauseLevel(Request $request, Mission $mission, ProblemCause $cause): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'confirmation_level' => 'required|string|in:hypothesis,partially_supported,confirmed',
        ]);

        $updated = $this->psgService->updateConfirmationLevel($cause, $validated['confirmation_level']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Niveau de confirmation mis à jour.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('missions.problem-grid.show', $mission)
            ->with('success', 'Niveau de confirmation de la cause actualisé.');
    }

    /**
     * Enregistre une conséquence pour un problème.
     */
    public function storeConsequence(Request $request, Mission $mission, StructuredProblem $problem): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'consequence' => 'required|string|max:255',
            'description' => 'nullable|string',
            'domain' => 'nullable|string|max:100',
            'importance' => 'required|string|in:minor,medium,high,critical',
            'comments' => 'nullable|string',
        ]);

        $consequence = $this->psgService->registerConsequence($problem, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Conséquence enregistrée.',
                'data' => $consequence,
            ]);
        }

        return redirect()->route('missions.problem-grid.show', $mission)
            ->with('success', 'Conséquence enregistrée pour ce problème.');
    }

    /**
     * Établit une relation entre deux problèmes.
     */
    public function storeRelation(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'source_problem_id' => 'required|exists:structured_problems,id',
            'target_problem_id' => 'required|exists:structured_problems,id|different:source_problem_id',
            'relation_type' => 'required|string|in:contributes_to,causes,aggravates,depends_on,shares_common_cause_with',
            'explanation' => 'nullable|string',
            'is_hypothetical' => 'nullable|boolean',
        ]);

        $grid = $mission->problemGrid;
        if (! $grid) {
            $grid = $this->psgService->prepareProblemStructuringGrid($mission, $request->user());
        }

        $source = StructuredProblem::findOrFail($validated['source_problem_id']);
        $target = StructuredProblem::findOrFail($validated['target_problem_id']);

        $relation = $this->psgService->linkProblems(
            $grid,
            $source,
            $target,
            $validated['relation_type'],
            $validated['explanation'] ?? null,
            (bool) ($validated['is_hypothetical'] ?? false)
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Relation entre problèmes enregistrée.',
                'data' => $relation,
            ]);
        }

        return redirect()->route('missions.problem-grid.show', $mission)
            ->with('success', 'Relation établie entre les deux problèmes.');
    }

    /**
     * Enregistre l'évaluation de priorité d'un problème.
     */
    public function assessPriority(Request $request, Mission $mission, StructuredProblem $problem): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'severity' => 'required|string|in:minor,moderate,major,critical',
            'frequency' => 'required|string|in:isolated,occasional,frequent,systemic',
            'scope_extent' => 'required|string|in:localized,departmental,transversal,company_wide',
            'urgency' => 'required|string|in:low,medium,high,immediate',
            'strategic_impact' => 'required|string|in:low,medium,high,vital',
            'priority_level' => 'required|string|in:P1,P2,P3,P4',
            'rationale' => 'nullable|string',
        ]);

        $assessment = $this->psgService->assessProblem($problem, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Priorisation du problème enregistrée.',
                'data' => $assessment,
            ]);
        }

        return redirect()->route('missions.problem-grid.show', $mission)
            ->with('success', "Priorisation enregistrée pour {$problem->reference}.");
    }

    /**
     * Associe un élément de preuve justificatif à un problème.
     */
    public function linkEvidence(Request $request, Mission $mission, StructuredProblem $problem): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'cause_id' => 'nullable|exists:problem_causes,id',
            'consequence_id' => 'nullable|exists:problem_consequences,id',
            'source_type' => 'required|string|in:d360_evidence,dir_item,sig_contribution,observation,other',
            'd360_evidence_id' => 'nullable|exists:diagnostic_evidences,id',
            'received_item_id' => 'nullable|exists:received_items,id',
            'received_contribution_id' => 'nullable|exists:received_contributions,id',
            'title' => 'required|string|max:255',
            'content_summary' => 'nullable|string',
            'assessed_reliability' => 'nullable|string|in:solid,to_corroborate,weak,contradictory',
            'notes' => 'nullable|string',
        ]);

        $evidence = $this->psgService->linkEvidence($problem, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Élément de preuve rattaché.',
                'data' => $evidence,
            ]);
        }

        return redirect()->route('missions.problem-grid.show', $mission)
            ->with('success', 'Élément probant associé.');
    }

    /**
     * Génère l'instantané de sortie du PSG.
     */
    public function generateSnapshot(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $grid = $mission->problemGrid;
        if (! $grid) {
            $grid = $this->psgService->prepareProblemStructuringGrid($mission, $request->user());
        }

        $snapshot = $this->psgService->generateSnapshot($grid);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Instantané généré avec succès.',
                'data' => $snapshot,
            ]);
        }

        return redirect()->route('missions.problem-grid.show', $mission)
            ->with('success', 'Instantané consolidé du Problem Structuring Grid généré.');
    }
}
