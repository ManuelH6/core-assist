<?php

namespace App\Modules\ProblemStructuringGrid\Services;

use App\Models\User;
use App\Modules\Diagnostic360Matrix\Models\Diagnostic360Matrix;
use App\Modules\Diagnostic360Matrix\Models\DiagnosticFinding;
use App\Modules\DiagnosticIntakeRegister\Models\DiagnosticIntakeRegister;
use App\Modules\DiagnosticScopeCanvas\Models\DiagnosticScopeCanvas;
use App\Modules\MissionFramingSheet\Models\MissionFramingSheet;
use App\Modules\ProblemStructuringGrid\Models\ProblemCause;
use App\Modules\ProblemStructuringGrid\Models\ProblemConsequence;
use App\Modules\ProblemStructuringGrid\Models\ProblemEvidence;
use App\Modules\ProblemStructuringGrid\Models\ProblemPriorityAssessment;
use App\Modules\ProblemStructuringGrid\Models\ProblemRelation;
use App\Modules\ProblemStructuringGrid\Models\ProblemStructuringGrid;
use App\Modules\ProblemStructuringGrid\Models\StructuredProblem;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\StakeholderInputGrid\Models\StakeholderInputGrid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProblemStructuringGridService
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

    /**
     * Récupère l'ensemble des données d'amont utiles au PSG sans aucune ressaisie.
     *
     * @return array<string, mixed>
     */
    public function getInitialData(Mission $mission): array
    {
        $framing = $this->getMissionFraming($mission);
        $scope = $this->getDiagnosticScope($mission);
        $intake = $this->getIntakeRegister($mission);
        $stakeholders = $this->getStakeholderGrid($mission);
        $matrix = $this->getDiagnosticMatrix($mission);

        $findings = $matrix ? $matrix->findings()->with(['assessment'])->get() : new Collection;
        $difficulties = $findings->where('finding_type', 'difficulty')->values();
        $strengths = $findings->where('finding_type', 'strength')->values();
        $hypotheses = $findings->where('finding_type', 'hypothesis')->values();
        $evidences = $matrix ? $matrix->evidences()->get() : new Collection;

        return [
            'mission' => [
                'id' => $mission->id,
                'title' => $mission->title,
                'reference' => $mission->reference,
                'client' => $mission->framing?->client?->name,
                'objectives' => $framing?->operational_objectives,
            ],
            'scope' => [
                'exists' => $scope !== null,
                'included_items' => $scope ? collect($scope->scope_items ?? [])->filter(fn ($i) => ($i['status'] ?? '') === 'included')->values()->all() : [],
            ],
            'd360' => [
                'exists' => $matrix !== null,
                'status' => $matrix?->status,
                'difficulties_count' => $difficulties->count(),
                'difficulties' => $difficulties,
                'strengths' => $strengths,
                'hypotheses' => $hypotheses,
                'evidences_count' => $evidences->count(),
                'evidences' => $evidences,
            ],
            'intake_items_count' => $intake?->receivedItems()->count() ?? 0,
            'stakeholder_contributions_count' => $stakeholders?->receivedContributions()->count() ?? 0,
        ];
    }

    /**
     * Récupère les constats issus de la matrice D360.
     *
     * @return Collection<int, DiagnosticFinding>
     */
    public function getDiagnosticFindings(Mission $mission, ?string $type = null): Collection
    {
        $matrix = $this->getDiagnosticMatrix($mission);
        if (! $matrix) {
            return new Collection;
        }

        $query = $matrix->findings()->with('assessment');
        if ($type) {
            $query->where('finding_type', $type);
        }

        return $query->get();
    }

    /**
     * Initialise ou met à jour la grille de structuration des problèmes.
     * Convertit automatiquement les difficultés constatées dans le D360 en fiches problèmes initiales.
     */
    public function prepareProblemStructuringGrid(Mission $mission, ?User $user = null): ProblemStructuringGrid
    {
        return DB::transaction(function () use ($mission, $user) {
            $initialData = $this->getInitialData($mission);

            $grid = ProblemStructuringGrid::firstOrCreate(
                ['mission_id' => $mission->id],
                [
                    'title' => 'Problem Structuring Grid™ - '.$mission->title,
                    'status' => 'in_progress',
                    'created_by' => $user?->id,
                ]
            );

            // Mise à jour de l'instantané des entrées
            $grid->update([
                'input_snapshot' => [
                    'generated_at' => now()->toIso8601String(),
                    'difficulties_count' => $initialData['d360']['difficulties_count'],
                    'strengths_count' => count($initialData['d360']['strengths']),
                    'hypotheses_count' => count($initialData['d360']['hypotheses']),
                    'evidences_count' => $initialData['d360']['evidences_count'],
                ],
            ]);

            // Instanciation automatique des problèmes à partir des difficultés D360
            $difficulties = $initialData['d360']['difficulties'];
            $index = $grid->problems()->count() + 1;

            foreach ($difficulties as $difficulty) {
                $alreadyImported = $grid->problems()->where('diagnostic_finding_id', $difficulty->id)->exists();
                if ($alreadyImported) {
                    continue;
                }

                $assessment = $difficulty->assessment;
                $reference = sprintf('PRB-%03d', $index++);

                $problem = $grid->problems()->create([
                    'reference' => $reference,
                    'title' => $difficulty->title,
                    'description' => $difficulty->description,
                    'domain' => $assessment?->domain ?? 'Général',
                    'axis' => $assessment?->axis ?? 'Non spécifié',
                    'subaxis' => $assessment?->subaxis,
                    'scope_item_id' => $assessment?->scope_item_id,
                    'diagnostic_finding_id' => $difficulty->id,
                    'manifestations' => $difficulty->potential_consequences,
                    'status' => 'to_examine',
                    'created_by' => $user?->id,
                ]);

                // Si des causes possibles étaient déjà ébauchées dans le constat D360, créer une cause initiale en hypothèse
                if ($difficulty->possible_causes) {
                    $problem->causes()->create([
                        'cause' => $difficulty->possible_causes,
                        'nature' => 'organizational',
                        'explanation' => 'Cause issue du constat D360 n° '.$difficulty->id,
                        'confirmation_level' => 'hypothesis',
                        'identified_by' => $user?->id,
                    ]);
                }

                // Si des conséquences étaient relevées dans le constat D360, créer une conséquence initiale
                if ($difficulty->potential_consequences) {
                    $problem->consequences()->create([
                        'consequence' => $difficulty->potential_consequences,
                        'domain' => $assessment?->domain,
                        'importance' => match ($difficulty->severity_or_importance) {
                            'critical' => 'critical',
                            'major', 'high' => 'high',
                            'minor' => 'minor',
                            default => 'medium',
                        },
                    ]);
                }

                // Initialiser une évaluation de priorité par défaut cohérente avec le D360
                $problem->priorityAssessment()->create([
                    'severity' => match ($difficulty->severity_or_importance) {
                        'critical' => 'critical',
                        'major', 'high' => 'major',
                        'minor' => 'minor',
                        default => 'moderate',
                    },
                    'priority_level' => match ($difficulty->severity_or_importance) {
                        'critical' => 'P1',
                        'major', 'high' => 'P2',
                        'minor' => 'P4',
                        default => 'P3',
                    },
                    'assessed_by' => $user?->id,
                    'assessed_at' => now(),
                ]);
            }

            return $grid->load(['problems.causes', 'problems.consequences', 'problems.priorityAssessment', 'problems.outgoingRelations', 'relations']);
        });
    }

    /**
     * Crée un nouveau problème manuellement ou à partir d'un constat D360.
     *
     * @param  array<string, mixed>  $data
     */
    public function createProblem(ProblemStructuringGrid $grid, array $data, ?User $user = null): StructuredProblem
    {
        $findingId = $data['diagnostic_finding_id'] ?? null;
        $finding = $findingId ? DiagnosticFinding::with('assessment')->find($findingId) : null;

        $reference = $data['reference'] ?? null;
        if (! $reference) {
            $nextNum = $grid->problems()->count() + 1;
            $reference = sprintf('PRB-%03d', $nextNum);
        }

        $domain = $data['domain'] ?? ($finding?->assessment?->domain ?? 'Général');
        $axis = $data['axis'] ?? ($finding?->assessment?->axis ?? 'Axe standard');
        $subaxis = $data['subaxis'] ?? $finding?->assessment?->subaxis;
        $scopeItemId = $data['scope_item_id'] ?? $finding?->assessment?->scope_item_id;

        $problem = $grid->problems()->create([
            'reference' => $reference,
            'title' => $data['title'] ?? ($finding?->title ?? 'Problème à qualifier'),
            'description' => $data['description'] ?? $finding?->description,
            'domain' => $domain,
            'axis' => $axis,
            'subaxis' => $subaxis,
            'scope_item_id' => $scopeItemId,
            'diagnostic_finding_id' => $findingId,
            'manifestations' => $data['manifestations'] ?? null,
            'status' => $data['status'] ?? 'to_examine',
            'created_by' => $user?->id,
        ]);

        return $problem;
    }

    /**
     * Met à jour un problème structuré.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProblem(StructuredProblem $problem, array $data): StructuredProblem
    {
        $problem->update([
            'title' => $data['title'] ?? $problem->title,
            'description' => $data['description'] ?? $problem->description,
            'domain' => $data['domain'] ?? $problem->domain,
            'axis' => $data['axis'] ?? $problem->axis,
            'subaxis' => $data['subaxis'] ?? $problem->subaxis,
            'manifestations' => $data['manifestations'] ?? $problem->manifestations,
            'status' => $data['status'] ?? $problem->status,
        ]);

        return $problem->fresh(['causes', 'consequences', 'priorityAssessment', 'evidences']);
    }

    /**
     * Enregistre une cause pour un problème.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerCause(StructuredProblem $problem, array $data, ?User $user = null): ProblemCause
    {
        $level = $data['confirmation_level'] ?? 'hypothesis';
        if (! in_array($level, ['hypothesis', 'partially_supported', 'confirmed'], true)) {
            $level = 'hypothesis';
        }

        return $problem->causes()->create([
            'cause' => $data['cause'] ?? 'Cause non définie',
            'nature' => $data['nature'] ?? 'organizational',
            'explanation' => $data['explanation'] ?? null,
            'confirmation_level' => $level,
            'comments' => $data['comments'] ?? null,
            'identified_by' => $user?->id,
        ]);
    }

    /**
     * Fait évoluer le niveau de confirmation d'une cause.
     */
    public function updateConfirmationLevel(ProblemCause $cause, string $level): ProblemCause
    {
        if (! in_array($level, ['hypothesis', 'partially_supported', 'confirmed'], true)) {
            throw new InvalidArgumentException("Niveau de confirmation invalide : {$level}");
        }

        $cause->update(['confirmation_level' => $level]);

        return $cause;
    }

    /**
     * Enregistre une conséquence pour un problème.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerConsequence(StructuredProblem $problem, array $data): ProblemConsequence
    {
        $importance = $data['importance'] ?? 'medium';
        if (! in_array($importance, ['minor', 'medium', 'high', 'critical'], true)) {
            $importance = 'medium';
        }

        return $problem->consequences()->create([
            'consequence' => $data['consequence'] ?? 'Conséquence non définie',
            'description' => $data['description'] ?? null,
            'domain' => $data['domain'] ?? $problem->domain,
            'importance' => $importance,
            'comments' => $data['comments'] ?? null,
        ]);
    }

    /**
     * Associe un élément de preuve justificatif à un problème, une cause ou une conséquence.
     *
     * @param  array<string, mixed>  $data
     */
    public function linkEvidence(StructuredProblem $problem, array $data): ProblemEvidence
    {
        $grid = $problem->grid;

        return $problem->evidences()->create([
            'grid_id' => $grid->id,
            'cause_id' => $data['cause_id'] ?? null,
            'consequence_id' => $data['consequence_id'] ?? null,
            'source_type' => $data['source_type'] ?? 'observation',
            'd360_evidence_id' => $data['d360_evidence_id'] ?? null,
            'received_item_id' => $data['received_item_id'] ?? null,
            'received_contribution_id' => $data['received_contribution_id'] ?? null,
            'title' => $data['title'] ?? 'Preuve étayant le problème',
            'content_summary' => $data['content_summary'] ?? null,
            'assessed_reliability' => $data['assessed_reliability'] ?? 'solid',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Établit ou met à jour une relation causale ou d'influence entre deux problèmes.
     */
    public function linkProblems(
        ProblemStructuringGrid $grid,
        StructuredProblem $source,
        StructuredProblem $target,
        string $relationType,
        ?string $explanation = null,
        bool $isHypothetical = false
    ): ProblemRelation {
        if ($source->id === $target->id) {
            throw new InvalidArgumentException('Un problème ne peut pas être relié à lui-même.');
        }

        $validTypes = ['contributes_to', 'causes', 'aggravates', 'depends_on', 'shares_common_cause_with'];
        if (! in_array($relationType, $validTypes, true)) {
            throw new InvalidArgumentException("Type de relation invalide : {$relationType}");
        }

        return ProblemRelation::updateOrCreate(
            [
                'grid_id' => $grid->id,
                'source_problem_id' => $source->id,
                'target_problem_id' => $target->id,
                'relation_type' => $relationType,
            ],
            [
                'explanation' => $explanation,
                'is_hypothetical' => $isHypothetical,
            ]
        );
    }

    /**
     * Enregistre l'évaluation multicritère et la priorité d'un problème.
     *
     * @param  array<string, mixed>  $data
     */
    public function assessProblem(StructuredProblem $problem, array $data, ?User $user = null): ProblemPriorityAssessment
    {
        return ProblemPriorityAssessment::updateOrCreate(
            ['problem_id' => $problem->id],
            [
                'severity' => $data['severity'] ?? 'moderate',
                'frequency' => $data['frequency'] ?? 'occasional',
                'scope_extent' => $data['scope_extent'] ?? 'departmental',
                'urgency' => $data['urgency'] ?? 'medium',
                'strategic_impact' => $data['strategic_impact'] ?? 'medium',
                'priority_level' => $data['priority_level'] ?? 'P2',
                'rationale' => $data['rationale'] ?? null,
                'assessed_by' => $user?->id,
                'assessed_at' => now(),
            ]
        );
    }

    /**
     * Retourne les problèmes hiérarchisés selon leur niveau de priorité (P1 > P2 > P3 > P4).
     *
     * @return Collection<int, StructuredProblem>
     */
    public function prioritizeProblems(ProblemStructuringGrid $grid): Collection
    {
        return $grid->problems()
            ->with(['priorityAssessment', 'causes', 'consequences', 'outgoingRelations', 'incomingRelations'])
            ->get()
            ->sortBy(function (StructuredProblem $problem) {
                $priority = $problem->priorityAssessment?->priority_level ?? 'P4';

                return match ($priority) {
                    'P1' => 1,
                    'P2' => 2,
                    'P3' => 3,
                    default => 4,
                };
            })
            ->values();
    }

    /**
     * Produit la cartographie relationnelle consolidée (nœuds, arêtes, causes racines, problèmes centraux).
     *
     * @return array<string, mixed>
     */
    public function generateProblemStructure(ProblemStructuringGrid $grid): array
    {
        $problems = $grid->problems()->with(['causes', 'consequences', 'priorityAssessment'])->get();
        $relations = $grid->relations()->with(['sourceProblem', 'targetProblem'])->get();

        $nodes = $problems->map(fn (StructuredProblem $p) => [
            'id' => $p->id,
            'reference' => $p->reference,
            'title' => $p->title,
            'domain' => $p->domain,
            'status' => $p->status,
            'priority' => $p->priorityAssessment?->priority_level ?? 'P3',
            'causes_count' => $p->causes->count(),
            'confirmed_causes_count' => $p->causes->where('confirmation_level', 'confirmed')->count(),
            'consequences_count' => $p->consequences->count(),
        ])->all();

        $edges = $relations->map(fn (ProblemRelation $r) => [
            'id' => $r->id,
            'source_id' => $r->source_problem_id,
            'target_id' => $r->target_problem_id,
            'relation_type' => $r->relation_type,
            'is_hypothetical' => $r->is_hypothetical,
            'explanation' => $r->explanation,
        ])->all();

        // Détection des problèmes racines (ceux qui causent ou contribuent à d'autres sans être provoqués)
        $targets = collect($edges)->pluck('target_id')->unique();
        $sources = collect($edges)->pluck('source_id')->unique();
        $rootProblemIds = $sources->diff($targets)->values()->all();

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'root_problem_ids' => $rootProblemIds,
            'total_problems' => count($nodes),
            'total_relations' => count($edges),
        ];
    }

    /**
     * Génère l'instantané de sortie (output_snapshot) pour alimentation du Diagnostic Restitution Deck (DRD).
     *
     * @return array<string, mixed>
     */
    public function generateSnapshot(ProblemStructuringGrid $grid): array
    {
        $problems = $this->prioritizeProblems($grid);
        $structure = $this->generateProblemStructure($grid);

        $p1Count = $problems->filter(fn ($p) => ($p->priorityAssessment?->priority_level ?? '') === 'P1')->count();
        $p2Count = $problems->filter(fn ($p) => ($p->priorityAssessment?->priority_level ?? '') === 'P2')->count();

        $confirmedCauses = ProblemCause::whereIn('problem_id', $problems->pluck('id'))
            ->where('confirmation_level', 'confirmed')
            ->get();

        $criticalConsequences = ProblemConsequence::whereIn('problem_id', $problems->pluck('id'))
            ->where('importance', 'critical')
            ->get();

        $snapshot = [
            'generated_at' => now()->toIso8601String(),
            'grid_id' => $grid->id,
            'mission_id' => $grid->mission_id,
            'status' => $grid->status,
            'progress_percentage' => $grid->structuringProgressPercentage(),
            'counts' => [
                'total_problems' => $problems->count(),
                'p1_critical' => $p1Count,
                'p2_high' => $p2Count,
                'confirmed_causes' => $confirmedCauses->count(),
                'critical_consequences' => $criticalConsequences->count(),
                'relations_count' => $structure['total_relations'],
            ],
            'prioritized_problems' => $problems->map(fn ($p) => [
                'reference' => $p->reference,
                'title' => $p->title,
                'domain' => $p->domain,
                'priority' => $p->priorityAssessment?->priority_level,
                'severity' => $p->priorityAssessment?->severity,
                'urgency' => $p->priorityAssessment?->urgency,
                'causes' => $p->causes->pluck('cause')->all(),
                'consequences' => $p->consequences->pluck('consequence')->all(),
            ])->all(),
            'structure' => $structure,
        ];

        $grid->update([
            'output_snapshot' => $snapshot,
        ]);

        return $snapshot;
    }
}
