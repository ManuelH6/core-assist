<?php

namespace App\Modules\LiveDecisionCapture\Services;

use App\Models\User;
use App\Modules\Diagnostic360Matrix\Models\Diagnostic360Matrix;
use App\Modules\DiagnosticIntakeRegister\Models\DiagnosticIntakeRegister;
use App\Modules\DiagnosticRestitutionDeck\Models\DiagnosticRestitutionDeck;
use App\Modules\DiagnosticScopeCanvas\Models\DiagnosticScopeCanvas;
use App\Modules\LiveDecisionCapture\Models\CapturedDecision;
use App\Modules\LiveDecisionCapture\Models\DecisionCommitment;
use App\Modules\LiveDecisionCapture\Models\DecisionExchange;
use App\Modules\LiveDecisionCapture\Models\DecisionSource;
use App\Modules\LiveDecisionCapture\Models\DiscussionPoint;
use App\Modules\LiveDecisionCapture\Models\LiveDecisionCapture;
use App\Modules\MissionFramingSheet\Models\MissionFramingSheet;
use App\Modules\ProblemStructuringGrid\Models\ProblemStructuringGrid;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\StakeholderInputGrid\Models\StakeholderInputGrid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LiveDecisionCaptureService
{
    public function getMissionFraming(Mission $mission): ?MissionFramingSheet
    {
        return $mission->framingSheet;
    }

    public function getDiagnosticScope(Mission $mission): ?DiagnosticScopeCanvas
    {
        return $mission->scopeCanvas;
    }

    public function getIntakeRegister(Mission $mission): ?DiagnosticIntakeRegister
    {
        return $mission->intakeRegister;
    }

    public function getStakeholderGrid(Mission $mission): ?StakeholderInputGrid
    {
        return $mission->stakeholderGrid;
    }

    public function getDiagnosticMatrix(Mission $mission): ?Diagnostic360Matrix
    {
        return $mission->diagnosticMatrix;
    }

    public function getProblemGrid(Mission $mission): ?ProblemStructuringGrid
    {
        return $mission->problemGrid;
    }

    public function getRestitutionDeck(Mission $mission): ?DiagnosticRestitutionDeck
    {
        return $mission->restitutionDeck;
    }

    /**
     * Récupère l'ensemble des données d'amont utiles au LDC sans aucune ressaisie.
     *
     * @return array<string, mixed>
     */
    public function getInitialData(Mission $mission): array
    {
        $framing = $this->getMissionFraming($mission);
        $scope = $this->getDiagnosticScope($mission);
        $stakeholders = $this->getStakeholderGrid($mission);
        $matrix = $this->getDiagnosticMatrix($mission);
        $psg = $this->getProblemGrid($mission);
        $deck = $this->getRestitutionDeck($mission);

        $stakeholderList = $stakeholders ? $stakeholders->stakeholders()->get() : new Collection;
        $problems = $psg ? $psg->problems()->get() : new Collection;
        $findings = $matrix ? $matrix->findings()->get() : new Collection;
        $sections = $deck ? $deck->sections()->get() : new Collection;

        return [
            'mission' => [
                'id' => $mission->id,
                'title' => $mission->title,
                'reference' => $mission->reference,
                'client' => $mission->framing?->client?->name,
                'objectives' => $framing?->operational_objectives,
            ],
            'scope_domains' => $scope ? collect($scope->scope_items ?? [])->pluck('domain')->unique()->values()->all() : [],
            'stakeholders' => $stakeholderList,
            'problems' => $problems,
            'findings' => $findings,
            'restitution_sections' => $sections,
        ];
    }

    /**
     * Initialise ou charge le conteneur Live Decision Capture pour la mission.
     */
    public function prepareDecisionCapture(Mission $mission, ?User $user = null): LiveDecisionCapture
    {
        return DB::transaction(function () use ($mission, $user) {
            $initialData = $this->getInitialData($mission);

            $capture = LiveDecisionCapture::firstOrCreate(
                ['mission_id' => $mission->id],
                [
                    'title' => 'Live Decision Capture™ - '.$mission->title,
                    'status' => 'in_progress',
                    'created_by' => $user?->id,
                ]
            );

            // Mise à jour de l'instantané d'entrée
            $capture->update([
                'input_snapshot' => [
                    'generated_at' => now()->toIso8601String(),
                    'mission_reference' => $initialData['mission']['reference'],
                    'stakeholders_count' => $initialData['stakeholders']->count(),
                    'problems_count' => $initialData['problems']->count(),
                    'findings_count' => $initialData['findings']->count(),
                ],
            ]);

            return $capture->load(['exchanges.discussionPoints', 'decisions.commitments', 'decisions.sources']);
        });
    }

    /**
     * Enregistre un échange / réunion / comité.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerExchange(LiveDecisionCapture $capture, array $data, ?User $user = null): DecisionExchange
    {
        return $capture->exchanges()->create([
            'title' => $data['title'] ?? 'Comité d’arbitrage',
            'exchanged_at' => $data['exchanged_at'] ?? now(),
            'modality' => $data['modality'] ?? 'meeting',
            'location' => $data['location'] ?? null,
            'context' => $data['context'] ?? null,
            'observations' => $data['observations'] ?? null,
            'attendees' => $data['attendees'] ?? null,
            'recorded_by' => $user?->id,
        ]);
    }

    /**
     * Met à jour un échange.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateExchange(DecisionExchange $exchange, array $data): DecisionExchange
    {
        $exchange->update([
            'title' => $data['title'] ?? $exchange->title,
            'exchanged_at' => $data['exchanged_at'] ?? $exchange->exchanged_at,
            'modality' => $data['modality'] ?? $exchange->modality,
            'location' => $data['location'] ?? $exchange->location,
            'context' => $data['context'] ?? $exchange->context,
            'observations' => $data['observations'] ?? $exchange->observations,
            'attendees' => $data['attendees'] ?? $exchange->attendees,
        ]);

        return $exchange->fresh(['discussionPoints', 'decisions']);
    }

    /**
     * Ajoute un point discuté dans un échange.
     *
     * @param  array<string, mixed>  $data
     */
    public function addDiscussionPoint(DecisionExchange $exchange, array $data): DiscussionPoint
    {
        return $exchange->discussionPoints()->create([
            'subject' => $data['subject'] ?? 'Point d’échange',
            'description' => $data['description'] ?? null,
            'domain' => $data['domain'] ?? null,
            'axis' => $data['axis'] ?? null,
            'problem_id' => $data['problem_id'] ?? null,
            'observations' => $data['observations'] ?? null,
        ]);
    }

    /**
     * Formalise une décision ou un arbitrage.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerDecision(LiveDecisionCapture $capture, array $data, ?User $user = null): CapturedDecision
    {
        $reference = $data['reference'] ?? null;
        if (! $reference) {
            $nextNum = $capture->decisions()->count() + 1;
            $reference = sprintf('DEC-%03d', $nextNum);
        }

        $nature = $data['nature'] ?? 'arbitrage';
        $makerType = $data['decision_maker_type'] ?? 'client';
        $status = $data['status'] ?? 'taken';

        return $capture->decisions()->create([
            'exchange_id' => $data['exchange_id'] ?? null,
            'point_id' => $data['point_id'] ?? null,
            'reference' => $reference,
            'title' => $data['title'] ?? 'Décision actée',
            'decision_text' => $data['decision_text'] ?? ($data['title'] ?? 'Décision sans texte détaillé'),
            'nature' => $nature,
            'decision_maker_type' => $makerType,
            'decision_maker_name' => $data['decision_maker_name'] ?? null,
            'justification' => $data['justification'] ?? null,
            'decided_at' => $data['decided_at'] ?? now(),
            'status' => $status,
            'revision_notes' => $data['revision_notes'] ?? null,
            'previous_version_id' => $data['previous_version_id'] ?? null,
            'recorded_by' => $user?->id,
        ]);
    }

    /**
     * Met à jour une décision existante.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateDecision(CapturedDecision $decision, array $data): CapturedDecision
    {
        $decision->update([
            'title' => $data['title'] ?? $decision->title,
            'decision_text' => $data['decision_text'] ?? $decision->decision_text,
            'nature' => $data['nature'] ?? $decision->nature,
            'decision_maker_type' => $data['decision_maker_type'] ?? $decision->decision_maker_type,
            'decision_maker_name' => $data['decision_maker_name'] ?? $decision->decision_maker_name,
            'justification' => $data['justification'] ?? $decision->justification,
            'status' => $data['status'] ?? $decision->status,
        ]);

        return $decision->fresh(['commitments', 'sources']);
    }

    /**
     * Confirme formellement une décision proposée ou en attente.
     */
    public function confirmDecision(CapturedDecision $decision, ?string $notes = null): CapturedDecision
    {
        $decision->update([
            'status' => 'taken',
            'revision_notes' => $notes ?: $decision->revision_notes,
        ]);

        return $decision;
    }

    /**
     * Enregistre une révision d'une décision en conservant l'historique complet.
     *
     * @param  array<string, mixed>  $newData
     */
    public function reviseDecision(CapturedDecision $decision, array $newData, ?User $user = null): CapturedDecision
    {
        return DB::transaction(function () use ($decision, $newData, $user) {
            // Marquer l'ancienne décision comme révisée
            $decision->update([
                'status' => 'revised',
                'revision_notes' => $newData['revision_reason'] ?? 'Révisée par une nouvelle décision',
            ]);

            // Créer la nouvelle décision avec filiation
            $nextNum = $decision->capture->decisions()->count() + 1;
            $newRef = sprintf('%s-REV', $decision->reference);

            return $decision->capture->decisions()->create([
                'exchange_id' => $newData['exchange_id'] ?? $decision->exchange_id,
                'point_id' => $newData['point_id'] ?? $decision->point_id,
                'reference' => $newRef,
                'title' => $newData['title'] ?? $decision->title,
                'decision_text' => $newData['decision_text'] ?? $decision->decision_text,
                'nature' => $newData['nature'] ?? $decision->nature,
                'decision_maker_type' => $newData['decision_maker_type'] ?? $decision->decision_maker_type,
                'decision_maker_name' => $newData['decision_maker_name'] ?? $decision->decision_maker_name,
                'justification' => $newData['justification'] ?? $decision->justification,
                'decided_at' => now(),
                'status' => 'taken',
                'revision_notes' => 'Révision de la décision '.$decision->reference,
                'previous_version_id' => $decision->id,
                'recorded_by' => $user?->id,
            ]);
        });
    }

    /**
     * Annule une décision avec motif d'annulation explicite.
     */
    public function cancelDecision(CapturedDecision $decision, string $reason): CapturedDecision
    {
        $decision->update([
            'status' => 'cancelled',
            'revision_notes' => 'ANNULATION : '.$reason,
        ]);

        return $decision;
    }

    /**
     * Relie une décision à ses sources justificatives d'amont (D360, PSG, etc.).
     *
     * @param  array<string, mixed>  $sourceData
     */
    public function linkDecisionToSource(CapturedDecision $decision, array $sourceData): DecisionSource
    {
        return $decision->sources()->create([
            'source_tool' => $sourceData['source_tool'] ?? 'psg',
            'source_type' => $sourceData['source_type'] ?? 'problem',
            'source_id' => $sourceData['source_id'] ?? null,
            'source_reference' => $sourceData['source_reference'] ?? null,
            'source_summary' => $sourceData['source_summary'] ?? null,
            'notes' => $sourceData['notes'] ?? null,
        ]);
    }

    /**
     * Enregistre un engagement ou une action résultant d'une décision.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerCommitment(CapturedDecision $decision, array $data): DecisionCommitment
    {
        return $decision->commitments()->create([
            'action' => $data['action'] ?? 'Action à mener',
            'responsible_name' => $data['responsible_name'] ?? null,
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'assigned_to_stakeholder_id' => $data['assigned_to_stakeholder_id'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'expected_outcome' => $data['expected_outcome'] ?? null,
            'status' => $data['status'] ?? 'to_do',
            'comments' => $data['comments'] ?? null,
        ]);
    }

    /**
     * Affecte un responsable à un engagement.
     */
    public function assignCommitment(
        DecisionCommitment $commitment,
        ?User $user = null,
        ?int $stakeholderId = null,
        ?string $responsibleName = null
    ): DecisionCommitment {
        $commitment->update([
            'assigned_to_user_id' => $user?->id,
            'assigned_to_stakeholder_id' => $stakeholderId,
            'responsible_name' => $responsibleName ?? ($user?->name ?? $commitment->responsible_name),
        ]);

        return $commitment;
    }

    /**
     * Actualise l'état d'un engagement.
     */
    public function updateCommitmentStatus(DecisionCommitment $commitment, string $status, ?string $comments = null): DecisionCommitment
    {
        $validStatuses = ['to_do', 'in_progress', 'completed', 'blocked', 'cancelled'];
        if (! in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException("Statut d'engagement invalide : {$status}");
        }

        $commitment->update([
            'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null,
            'comments' => $comments ?: $commitment->comments,
        ]);

        return $commitment;
    }

    /**
     * Remonte la chaîne généalogique des versions d'une décision révisée.
     *
     * @return Collection<int, CapturedDecision>
     */
    public function getDecisionHistory(CapturedDecision $decision): Collection
    {
        $history = new Collection([$decision]);
        $current = $decision;

        while ($current->previous_version_id !== null) {
            $prev = CapturedDecision::find($current->previous_version_id);
            if (! $prev) {
                break;
            }
            $history->prepend($prev);
            $current = $prev;
        }

        return $history;
    }

    /**
     * Génère l'instantané consolidé (output_snapshot) de l'ensemble des décisions et engagements.
     *
     * @return array<string, mixed>
     */
    public function generateSnapshot(LiveDecisionCapture $capture): array
    {
        $exchanges = $capture->exchanges()->with('discussionPoints')->get();
        $decisions = $capture->decisions()->with(['commitments', 'sources'])->get();
        $commitments = DecisionCommitment::whereIn('decision_id', $decisions->pluck('id'))->get();

        $snapshot = [
            'generated_at' => now()->toIso8601String(),
            'capture_id' => $capture->id,
            'mission_id' => $capture->mission_id,
            'status' => $capture->status,
            'commitments_completion_percentage' => $capture->commitmentsCompletionPercentage(),
            'counts' => [
                'exchanges_count' => $exchanges->count(),
                'decisions_count' => $decisions->count(),
                'taken_decisions_count' => $decisions->where('status', 'taken')->count(),
                'revised_decisions_count' => $decisions->where('status', 'revised')->count(),
                'cancelled_decisions_count' => $decisions->where('status', 'cancelled')->count(),
                'total_commitments' => $commitments->count(),
                'completed_commitments' => $commitments->where('status', 'completed')->count(),
                'in_progress_commitments' => $commitments->where('status', 'in_progress')->count(),
                'blocked_commitments' => $commitments->where('status', 'blocked')->count(),
            ],
            'decisions' => $decisions->map(fn (CapturedDecision $d) => [
                'reference' => $d->reference,
                'title' => $d->title,
                'nature' => $d->nature,
                'maker' => $d->decision_maker_name ?: $d->decision_maker_type,
                'status' => $d->status,
                'decided_at' => $d->decided_at?->format('Y-m-d H:i'),
                'commitments_count' => $d->commitments->count(),
            ])->all(),
            'open_commitments' => $commitments->whereIn('status', ['to_do', 'in_progress', 'blocked'])->map(fn (DecisionCommitment $c) => [
                'action' => $c->action,
                'responsible' => $c->responsible_name,
                'due_date' => $c->due_date?->format('Y-m-d'),
                'status' => $c->status,
            ])->values()->all(),
        ];

        $capture->update([
            'output_snapshot' => $snapshot,
        ]);

        return $snapshot;
    }
}
