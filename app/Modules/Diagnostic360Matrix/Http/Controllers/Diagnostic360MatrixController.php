<?php

namespace App\Modules\Diagnostic360Matrix\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Diagnostic360Matrix\Models\DiagnosticAssessment;
use App\Modules\Diagnostic360Matrix\Services\Diagnostic360MatrixService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Diagnostic360MatrixController extends Controller
{
    public function __construct(
        protected Diagnostic360MatrixService $matrixService
    ) {}

    /**
     * Affiche ou retourne la Matrice de Diagnostic 360° pour une mission.
     */
    public function show(Request $request, Mission $mission): JsonResponse|View
    {
        $matrix = $mission->diagnosticMatrix;

        if (! $matrix) {
            $matrix = $this->matrixService->prepareDiagnosticMatrix($mission, $request->user());
        }

        $initialData = $this->matrixService->getInitialData($mission);
        $availableInformation = $this->matrixService->getAvailableInformation($mission);
        $availableContributions = $this->matrixService->getAvailableContributions($mission);
        $gaps = $this->matrixService->identifyInformationGaps($matrix);
        $users = User::orderBy('name')->get();

        $assessments = $matrix->assessments()
            ->with(['findings', 'evidences.receivedItem', 'evidences.receivedContribution', 'evaluator'])
            ->get();

        $axisSummaries = $matrix->axisSummaries()->get()->keyBy(fn ($s) => $s->domain.'__'.$s->axis);
        $domainSummaries = $matrix->domainSummaries()->get()->keyBy('domain');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'matrix' => $matrix,
                    'assessments' => $assessments,
                    'axis_summaries' => $axisSummaries,
                    'domain_summaries' => $domainSummaries,
                    'available_information_count' => $availableInformation->count(),
                    'available_contributions_count' => $availableContributions->count(),
                    'information_gaps' => $gaps,
                ],
            ]);
        }

        return view('diagnostic-matrices.show', compact(
            'mission',
            'matrix',
            'assessments',
            'axisSummaries',
            'domainSummaries',
            'initialData',
            'availableInformation',
            'availableContributions',
            'gaps',
            'users'
        ));
    }

    /**
     * Initialise ou synchronise la matrice avec le périmètre DSC actuel.
     */
    public function prepare(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $matrix = $this->matrixService->prepareDiagnosticMatrix($mission, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Matrice de Diagnostic 360° initialisée avec succès.',
                'data' => $matrix,
            ]);
        }

        return redirect()->route('missions.diagnostic-matrix.show', $mission)
            ->with('success', 'Matrice synchronisée avec succès à partir du périmètre retenu.');
    }

    /**
     * Enregistre l'évaluation d'un axe.
     */
    public function updateAssessment(Request $request, Mission $mission, DiagnosticAssessment $assessment): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'observed_situation' => 'nullable|string',
            'assessment_text' => 'nullable|string',
            'rating_level' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:pending,in_progress,assessed,validated',
        ]);

        $updatedAssessment = $this->matrixService->registerAssessment($assessment, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Évaluation de l’axe enregistrée.',
                'data' => $updatedAssessment,
            ]);
        }

        return redirect()->route('missions.diagnostic-matrix.show', $mission)
            ->with('success', "Évaluation enregistrée pour l'axe {$assessment->axis}.");
    }

    /**
     * Associe un élément de preuve (document DIR, contribution SIG ou observation) à une évaluation.
     */
    public function linkEvidence(Request $request, Mission $mission, DiagnosticAssessment $assessment): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'source_type' => 'required|string|in:dir_item,sig_contribution,direct_observation,interview,other',
            'received_item_id' => 'nullable|exists:received_items,id',
            'received_contribution_id' => 'nullable|exists:received_contributions,id',
            'title' => 'required|string|max:255',
            'content_summary' => 'nullable|string',
            'assessed_reliability' => 'nullable|string|in:solid,to_corroborate,weak,contradictory',
            'limits_or_reservations' => 'nullable|string',
            'relevance_degree' => 'nullable|string|in:direct,indirect,contextual',
            'notes' => 'nullable|string',
        ]);

        $evidence = $this->matrixService->linkEvidence($assessment, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Élément de preuve rattaché avec succès.',
                'data' => $evidence,
            ]);
        }

        return redirect()->route('missions.diagnostic-matrix.show', $mission)
            ->with('success', 'Élément de preuve rattaché à l’évaluation.');
    }

    /**
     * Enregistre un constat (Point fort, Difficulté, Hypothèse).
     */
    public function recordFinding(Request $request, Mission $mission, DiagnosticAssessment $assessment): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'finding_type' => 'required|string|in:strength,difficulty,hypothesis',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'possible_causes' => 'nullable|string',
            'potential_consequences' => 'nullable|string',
            'severity_or_importance' => 'nullable|string|in:minor,medium,major,critical,high',
            'is_validated' => 'nullable|boolean',
        ]);

        $finding = $this->matrixService->recordFinding($assessment, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Constat enregistré avec succès.',
                'data' => $finding,
            ]);
        }

        return redirect()->route('missions.diagnostic-matrix.show', $mission)
            ->with('success', 'Nouveau constat consigné.');
    }

    /**
     * Enregistre la consolidation d'un axe.
     */
    public function consolidateAxis(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:100',
            'axis' => 'required|string|max:150',
            'summary_text' => 'nullable|string',
            'strengths_summary' => 'nullable|string',
            'difficulties_summary' => 'nullable|string',
            'information_gaps' => 'nullable|string',
            'contradictions_noted' => 'nullable|string',
            'further_investigations_needed' => 'nullable|string',
        ]);

        $matrix = $mission->diagnosticMatrix;
        if (! $matrix) {
            $matrix = $this->matrixService->prepareDiagnosticMatrix($mission, $request->user());
        }

        $summary = $this->matrixService->consolidateAxisAssessment(
            $matrix,
            $validated['domain'],
            $validated['axis'],
            $validated
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Synthèse d’axe enregistrée.',
                'data' => $summary,
            ]);
        }

        return redirect()->route('missions.diagnostic-matrix.show', $mission)
            ->with('success', "Synthèse d'axe consolidée.");
    }

    /**
     * Enregistre la consolidation globale d'un domaine.
     */
    public function consolidateDomain(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:100',
            'overall_rating' => 'nullable|string|max:50',
            'summary_text' => 'nullable|string',
            'key_takeaways' => 'nullable|string',
        ]);

        $matrix = $mission->diagnosticMatrix;
        if (! $matrix) {
            $matrix = $this->matrixService->prepareDiagnosticMatrix($mission, $request->user());
        }

        $summary = $this->matrixService->consolidateDomainAssessment(
            $matrix,
            $validated['domain'],
            $validated
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Synthèse de domaine enregistrée.',
                'data' => $summary,
            ]);
        }

        return redirect()->route('missions.diagnostic-matrix.show', $mission)
            ->with('success', "Synthèse du domaine {$validated['domain']} enregistrée.");
    }

    /**
     * Génère l'instantané de sortie du diagnostic 360°.
     */
    public function generateSnapshot(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $matrix = $mission->diagnosticMatrix;
        if (! $matrix) {
            $matrix = $this->matrixService->prepareDiagnosticMatrix($mission, $request->user());
        }

        $snapshot = $this->matrixService->generateSnapshot($matrix);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Instantané généré avec succès.',
                'data' => $snapshot,
            ]);
        }

        return redirect()->route('missions.diagnostic-matrix.show', $mission)
            ->with('success', 'Instantané consolidé généré avec succès.');
    }
}
