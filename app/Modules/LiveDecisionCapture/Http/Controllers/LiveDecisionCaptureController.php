<?php

namespace App\Modules\LiveDecisionCapture\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\LiveDecisionCapture\Models\CapturedDecision;
use App\Modules\LiveDecisionCapture\Models\DecisionCommitment;
use App\Modules\LiveDecisionCapture\Models\DecisionExchange;
use App\Modules\LiveDecisionCapture\Services\LiveDecisionCaptureService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveDecisionCaptureController extends Controller
{
    public function __construct(
        protected LiveDecisionCaptureService $ldcService
    ) {}

    /**
     * Affiche ou retourne le registre des décisions et arbitrages pris en réunion.
     */
    public function show(Request $request, Mission $mission): JsonResponse|View
    {
        $capture = $mission->decisionCapture;

        if (! $capture) {
            $capture = $this->ldcService->prepareDecisionCapture($mission, $request->user());
        }

        $initialData = $this->ldcService->getInitialData($mission);
        $exchanges = $capture->exchanges()->with(['discussionPoints.problem', 'decisions', 'recorder'])->get();
        $decisions = $capture->decisions()->with(['commitments.assignedUser', 'commitments.assignedStakeholder', 'exchange', 'discussionPoint', 'sources'])->get();
        $commitments = DecisionCommitment::whereIn('decision_id', $decisions->pluck('id'))->with(['decision', 'assignedUser'])->latest()->get();
        $users = User::orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'capture' => $capture,
                    'exchanges' => $exchanges,
                    'decisions' => $decisions,
                    'commitments' => $commitments,
                    'initial_data' => $initialData,
                ],
            ]);
        }

        return view('decision-captures.show', compact(
            'mission',
            'capture',
            'exchanges',
            'decisions',
            'commitments',
            'initialData',
            'users'
        ));
    }

    /**
     * Initialise ou synchronise le conteneur de capture de décisions.
     */
    public function prepare(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $capture = $this->ldcService->prepareDecisionCapture($mission, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Live Decision Capture initialisé avec succès.',
                'data' => $capture,
            ]);
        }

        return redirect()->route('missions.decision-capture.show', $mission)
            ->with('success', 'Registre des décisions prêt.');
    }

    /**
     * Enregistre un échange / réunion / comité.
     */
    public function storeExchange(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'exchanged_at' => 'required|date',
            'modality' => 'required|string|in:meeting,interview,videoconference,workshop,steering_committee,phone_call,other',
            'location' => 'nullable|string|max:255',
            'context' => 'nullable|string',
            'observations' => 'nullable|string',
        ]);

        $capture = $mission->decisionCapture;
        if (! $capture) {
            $capture = $this->ldcService->prepareDecisionCapture($mission, $request->user());
        }

        $exchange = $this->ldcService->registerExchange($capture, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Échange consigné avec succès.',
                'data' => $exchange,
            ]);
        }

        return redirect()->route('missions.decision-capture.show', $mission)
            ->with('success', "Échange « {$exchange->title} » enregistré.");
    }

    /**
     * Ajoute un point discuté dans un échange.
     */
    public function storeDiscussionPoint(Request $request, Mission $mission, DecisionExchange $exchange): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'domain' => 'nullable|string|max:100',
            'axis' => 'nullable|string|max:150',
            'problem_id' => 'nullable|exists:structured_problems,id',
            'observations' => 'nullable|string',
        ]);

        $point = $this->ldcService->addDiscussionPoint($exchange, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Point discuté ajouté à l’échange.',
                'data' => $point,
            ]);
        }

        return redirect()->route('missions.decision-capture.show', $mission)
            ->with('success', 'Point discuté ajouté à l’ordre du jour.');
    }

    /**
     * Formalise une décision ou un arbitrage.
     */
    public function storeDecision(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'exchange_id' => 'nullable|exists:decision_exchanges,id',
            'point_id' => 'nullable|exists:discussion_points,id',
            'reference' => 'nullable|string|max:30',
            'title' => 'required|string|max:255',
            'decision_text' => 'required|string',
            'nature' => 'required|string|in:validation,arbitrage,accord,refusal,postponement,orientation,other',
            'decision_maker_type' => 'required|string|in:client,cabinet,joint,steering_committee',
            'decision_maker_name' => 'nullable|string|max:255',
            'justification' => 'nullable|string',
            'decided_at' => 'required|date',
            'status' => 'nullable|string|in:proposed,taken,to_confirm,revised,cancelled',
        ]);

        $capture = $mission->decisionCapture;
        if (! $capture) {
            $capture = $this->ldcService->prepareDecisionCapture($mission, $request->user());
        }

        $decision = $this->ldcService->registerDecision($capture, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Décision formalisée avec succès.',
                'data' => $decision,
            ]);
        }

        return redirect()->route('missions.decision-capture.show', $mission)
            ->with('success', "Décision {$decision->reference} actée.");
    }

    /**
     * Met à jour une décision.
     */
    public function updateDecision(Request $request, Mission $mission, CapturedDecision $decision): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'decision_text' => 'required|string',
            'nature' => 'required|string|in:validation,arbitrage,accord,refusal,postponement,orientation,other',
            'decision_maker_type' => 'required|string|in:client,cabinet,joint,steering_committee',
            'decision_maker_name' => 'nullable|string|max:255',
            'justification' => 'nullable|string',
            'status' => 'required|string|in:proposed,taken,to_confirm,revised,cancelled',
        ]);

        $updated = $this->ldcService->updateDecision($decision, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Décision mise à jour.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('missions.decision-capture.show', $mission)
            ->with('success', "Décision {$decision->reference} actualisée.");
    }

    /**
     * Confirme une décision.
     */
    public function confirmDecision(Request $request, Mission $mission, CapturedDecision $decision): JsonResponse|RedirectResponse
    {
        $notes = $request->input('notes');
        $confirmed = $this->ldcService->confirmDecision($decision, $notes);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Décision confirmée.',
                'data' => $confirmed,
            ]);
        }

        return redirect()->route('missions.decision-capture.show', $mission)
            ->with('success', "Décision {$decision->reference} confirmée.");
    }

    /**
     * Enregistre une révision de décision.
     */
    public function reviseDecision(Request $request, Mission $mission, CapturedDecision $decision): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'decision_text' => 'required|string',
            'nature' => 'required|string',
            'decision_maker_type' => 'required|string',
            'decision_maker_name' => 'nullable|string',
            'justification' => 'nullable|string',
            'revision_reason' => 'required|string',
        ]);

        $revised = $this->ldcService->reviseDecision($decision, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Décision révisée avec succès.',
                'data' => $revised,
            ]);
        }

        return redirect()->route('missions.decision-capture.show', $mission)
            ->with('success', "Révision {$revised->reference} créée.");
    }

    /**
     * Annule une décision.
     */
    public function cancelDecision(Request $request, Mission $mission, CapturedDecision $decision): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $cancelled = $this->ldcService->cancelDecision($decision, $validated['reason']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Décision annulée.',
                'data' => $cancelled,
            ]);
        }

        return redirect()->route('missions.decision-capture.show', $mission)
            ->with('success', "Décision {$decision->reference} annulée.");
    }

    /**
     * Enregistre un engagement résultant d'une décision.
     */
    public function storeCommitment(Request $request, Mission $mission, CapturedDecision $decision): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|max:255',
            'responsible_name' => 'nullable|string|max:255',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'assigned_to_stakeholder_id' => 'nullable|exists:stakeholders,id',
            'due_date' => 'nullable|date',
            'expected_outcome' => 'nullable|string',
            'status' => 'nullable|string|in:to_do,in_progress,completed,blocked,cancelled',
            'comments' => 'nullable|string',
        ]);

        $commitment = $this->ldcService->registerCommitment($decision, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Engagement enregistré avec succès.',
                'data' => $commitment,
            ]);
        }

        return redirect()->route('missions.decision-capture.show', $mission)
            ->with('success', 'Engagement associé à la décision.');
    }

    /**
     * Met à jour le statut d'un engagement.
     */
    public function updateCommitmentStatus(Request $request, Mission $mission, DecisionCommitment $commitment): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:to_do,in_progress,completed,blocked,cancelled',
            'comments' => 'nullable|string',
        ]);

        $updated = $this->ldcService->updateCommitmentStatus($commitment, $validated['status'], $validated['comments'] ?? null);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Statut de l’engagement actualisé.',
                'data' => $updated,
            ]);
        }

        return redirect()->route('missions.decision-capture.show', $mission)
            ->with('success', 'Statut de l’action mis à jour.');
    }

    /**
     * Génère l'instantané consolidé des décisions et engagements.
     */
    public function generateSnapshot(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $capture = $mission->decisionCapture;
        if (! $capture) {
            $capture = $this->ldcService->prepareDecisionCapture($mission, $request->user());
        }

        $snapshot = $this->ldcService->generateSnapshot($capture);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Instantané consolidé du LDC généré.',
                'data' => $snapshot,
            ]);
        }

        return redirect()->route('missions.decision-capture.show', $mission)
            ->with('success', 'Instantané consolidé des décisions et engagements généré.');
    }
}
