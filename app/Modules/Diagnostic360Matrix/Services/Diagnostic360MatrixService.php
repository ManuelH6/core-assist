<?php

namespace App\Modules\Diagnostic360Matrix\Services;

use App\Models\User;
use App\Modules\Diagnostic360Matrix\Models\Diagnostic360Matrix;
use App\Modules\Diagnostic360Matrix\Models\DiagnosticAssessment;
use App\Modules\Diagnostic360Matrix\Models\DiagnosticAxisSummary;
use App\Modules\Diagnostic360Matrix\Models\DiagnosticDomainSummary;
use App\Modules\Diagnostic360Matrix\Models\DiagnosticEvidence;
use App\Modules\Diagnostic360Matrix\Models\DiagnosticFinding;
use App\Modules\DiagnosticIntakeRegister\Models\DiagnosticIntakeRegister;
use App\Modules\DiagnosticIntakeRegister\Models\ReceivedItem;
use App\Modules\DiagnosticScopeCanvas\Models\DiagnosticScopeCanvas;
use App\Modules\MissionFramingSheet\Models\MissionFramingSheet;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\StakeholderInputGrid\Models\ReceivedContribution;
use App\Modules\StakeholderInputGrid\Models\StakeholderInputGrid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Diagnostic360MatrixService
{
    /**
     * Récupère le cadre de mission opérationnel (MFS).
     */
    public function getMissionFraming(Mission $mission): ?MissionFramingSheet
    {
        return $mission->framingSheet;
    }

    /**
     * Récupère le périmètre de diagnostic validé (DSC).
     */
    public function getDiagnosticScope(Mission $mission): ?DiagnosticScopeCanvas
    {
        return $mission->scopeCanvas;
    }

    /**
     * Récupère le registre de collecte d'informations (DIR).
     */
    public function getIntakeRegister(Mission $mission): ?DiagnosticIntakeRegister
    {
        return $mission->intakeRegister;
    }

    /**
     * Récupère la grille des parties prenantes (SIG).
     */
    public function getStakeholderGrid(Mission $mission): ?StakeholderInputGrid
    {
        return $mission->stakeholderGrid;
    }

    /**
     * Récupère l'ensemble des données d'amont nécessaires au D360 sans aucune ressaisie.
     *
     * @return array<string, mixed>
     */
    public function getInitialData(Mission $mission): array
    {
        $framing = $this->getMissionFraming($mission);
        $scope = $this->getDiagnosticScope($mission);
        $intake = $this->getIntakeRegister($mission);
        $stakeholderGrid = $this->getStakeholderGrid($mission);

        $scopeItems = $scope ? collect($scope->scope_items ?? []) : collect();
        $includedItems = $scopeItems->filter(fn ($item) => ($item['status'] ?? 'included') === 'included')->values();
        $excludedItems = $scopeItems->filter(fn ($item) => ($item['status'] ?? 'included') === 'excluded')->values();

        $receivedItems = $intake ? $intake->receivedItems()->with('requiredInformation')->get() : new Collection;
        $receivedContributions = $stakeholderGrid ? $stakeholderGrid->receivedContributions()->with(['stakeholder', 'expectedContribution'])->get() : new Collection;

        return [
            'mission' => [
                'id' => $mission->id,
                'title' => $mission->title,
                'reference' => $mission->reference,
                'client' => $mission->framing?->client?->name,
            ],
            'framing' => [
                'exists' => $framing !== null,
                'operational_objectives' => $framing?->operational_objectives,
                'expected_deliverables' => $framing?->expected_deliverables,
                'methodology' => $framing?->methodology,
            ],
            'scope' => [
                'exists' => $scope !== null,
                'included_items' => $includedItems->all(),
                'excluded_items' => $excludedItems->all(),
                'domains' => $includedItems->pluck('domain')->unique()->values()->all(),
            ],
            'intake' => [
                'exists' => $intake !== null,
                'received_count' => $receivedItems->count(),
                'items' => $receivedItems,
            ],
            'stakeholders' => [
                'exists' => $stakeholderGrid !== null,
                'contributions_count' => $receivedContributions->count(),
                'contributions' => $receivedContributions,
            ],
        ];
    }

    /**
     * Initialise ou met à jour la matrice 360° pour la mission en instanciant les axes d'évaluation
     * à partir du périmètre retenu (DSC) et en liant les éléments en amont.
     */
    public function prepareDiagnosticMatrix(Mission $mission, ?User $user = null): Diagnostic360Matrix
    {
        return DB::transaction(function () use ($mission, $user) {
            $initialData = $this->getInitialData($mission);

            $matrix = Diagnostic360Matrix::firstOrCreate(
                ['mission_id' => $mission->id],
                [
                    'title' => 'Matrice de Diagnostic 360° - '.$mission->title,
                    'status' => 'in_progress',
                    'created_by' => $user?->id,
                ]
            );

            // Mise à jour de l'instantané des entrées
            $matrix->update([
                'input_snapshot' => [
                    'generated_at' => now()->toIso8601String(),
                    'framing_summary' => $initialData['framing'],
                    'included_axes_count' => count($initialData['scope']['included_items']),
                    'excluded_axes_count' => count($initialData['scope']['excluded_items']),
                    'received_documents_count' => $initialData['intake']['received_count'],
                    'received_contributions_count' => $initialData['stakeholders']['contributions_count'],
                ],
            ]);

            // Instanciation automatique des évaluations pour chaque axe inclus dans le DSC
            foreach ($initialData['scope']['included_items'] as $item) {
                $scopeItemId = (string) ($item['id'] ?? '');
                $domain = $item['domain'] ?? 'Général';
                $axis = $item['axis'] ?? 'Axe standard';
                $subaxis = $item['subaxis'] ?? null;
                $evaluatedElement = $item['description'] ?? ($item['axis'] ?? null);

                // Vérifier si une évaluation existe déjà pour cet élément de périmètre
                $existing = $matrix->assessments()
                    ->where(function ($q) use ($scopeItemId, $domain, $axis) {
                        if ($scopeItemId !== '') {
                            $q->where('scope_item_id', $scopeItemId);
                        } else {
                            $q->where('domain', $domain)->where('axis', $axis);
                        }
                    })
                    ->first();

                if (! $existing) {
                    $matrix->assessments()->create([
                        'scope_item_id' => $scopeItemId !== '' ? $scopeItemId : null,
                        'domain' => $domain,
                        'axis' => $axis,
                        'subaxis' => $subaxis,
                        'evaluated_element' => $evaluatedElement,
                        'status' => 'pending',
                        'evaluator_id' => $user?->id,
                    ]);
                }
            }

            return $matrix->load(['assessments.findings', 'assessments.evidences', 'axisSummaries', 'domainSummaries']);
        });
    }

    /**
     * Récupère les éléments de preuve documentaires disponibles (DIR).
     *
     * @return Collection<int, ReceivedItem>
     */
    public function getAvailableInformation(Mission $mission): Collection
    {
        return $mission->intakeRegister?->receivedItems()->with('requiredInformation')->get() ?? new Collection;
    }

    /**
     * Récupère les contributions des parties prenantes disponibles (SIG).
     *
     * @return Collection<int, ReceivedContribution>
     */
    public function getAvailableContributions(Mission $mission): Collection
    {
        return $mission->stakeholderGrid?->receivedContributions()->with(['stakeholder', 'expectedContribution'])->get() ?? new Collection;
    }

    /**
     * Enregistre ou met à jour l'évaluation sur un axe spécifique.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerAssessment(DiagnosticAssessment $assessment, array $data, ?User $user = null): DiagnosticAssessment
    {
        $status = $data['status'] ?? 'assessed';
        if (! in_array($status, ['pending', 'in_progress', 'assessed', 'validated'], true)) {
            $status = 'assessed';
        }

        $assessment->update([
            'observed_situation' => $data['observed_situation'] ?? $assessment->observed_situation,
            'assessment_text' => $data['assessment_text'] ?? $assessment->assessment_text,
            'rating_level' => $data['rating_level'] ?? $assessment->rating_level,
            'status' => $status,
            'evaluator_id' => $user?->id ?? $assessment->evaluator_id,
        ]);

        return $assessment->fresh(['findings', 'evidences']);
    }

    /**
     * Lie un élément de preuve (document DIR, contribution SIG ou observation) à une évaluation.
     *
     * @param  array<string, mixed>  $data
     */
    public function linkEvidence(DiagnosticAssessment $assessment, array $data, ?User $user = null): DiagnosticEvidence
    {
        $matrix = $assessment->matrix;

        $sourceType = $data['source_type'] ?? 'direct_observation';
        $receivedItemId = $data['received_item_id'] ?? null;
        $receivedContributionId = $data['received_contribution_id'] ?? null;
        $title = $data['title'] ?? 'Élément de preuve';
        $contentSummary = $data['content_summary'] ?? null;
        $reliability = $data['assessed_reliability'] ?? 'solid';

        // Si lié à un receivedItem existant, enrichir le titre/résumé si non fournis
        if ($receivedItemId && (! $contentSummary || $title === 'Élément de preuve')) {
            $item = ReceivedItem::find($receivedItemId);
            if ($item) {
                $title = $title === 'Élément de preuve' ? ('[Document DIR] '.$item->title) : $title;
                $contentSummary = $contentSummary ?: ($item->notes ?? 'Document transmis le '.($item->received_at?->format('d/m/Y') ?? 'N/A'));
            }
        }

        // Si lié à une contribution SIG existante
        if ($receivedContributionId && (! $contentSummary || $title === 'Élément de preuve')) {
            $contrib = ReceivedContribution::with('stakeholder')->find($receivedContributionId);
            if ($contrib) {
                $title = $title === 'Élément de preuve' ? ('[Entretien SIG] '.($contrib->stakeholder?->name ?? 'Partie prenante')) : $title;
                $contentSummary = $contentSummary ?: $contrib->summary;
            }
        }

        // Créer l'enregistrement d'évidence dans la matrice
        $evidence = $matrix->evidences()->create([
            'source_type' => $sourceType,
            'received_item_id' => $receivedItemId,
            'received_contribution_id' => $receivedContributionId,
            'title' => $title,
            'content_summary' => $contentSummary,
            'assessed_reliability' => $reliability,
            'limits_or_reservations' => $data['limits_or_reservations'] ?? null,
            'comments' => $data['comments'] ?? null,
            'recorded_by' => $user?->id,
        ]);

        // Attacher à l'évaluation via la table pivot
        $assessment->evidences()->attach($evidence->id, [
            'relevance_degree' => $data['relevance_degree'] ?? 'direct',
            'notes' => $data['notes'] ?? null,
        ]);

        return $evidence;
    }

    /**
     * Enregistre un constat (Point fort, Difficulté, Hypothèse) sur un axe évalué.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordFinding(DiagnosticAssessment $assessment, array $data, ?User $user = null): DiagnosticFinding
    {
        $type = $data['finding_type'] ?? 'difficulty';
        if (! in_array($type, ['strength', 'difficulty', 'hypothesis'], true)) {
            throw new InvalidArgumentException("Type de constat invalide: {$type}");
        }

        return $assessment->findings()->create([
            'matrix_id' => $assessment->matrix_id,
            'finding_type' => $type,
            'title' => $data['title'] ?? 'Constat sans titre',
            'description' => $data['description'] ?? null,
            'possible_causes' => $data['possible_causes'] ?? null,
            'potential_consequences' => $data['potential_consequences'] ?? null,
            'severity_or_importance' => $data['severity_or_importance'] ?? 'medium',
            'is_validated' => (bool) ($data['is_validated'] ?? false),
            'identified_by' => $user?->id,
        ]);
    }

    /**
     * Enregistre un point fort sur une évaluation.
     *
     * @param  array<string, mixed>  $data
     */
    public function identifyStrength(DiagnosticAssessment $assessment, array $data, ?User $user = null): DiagnosticFinding
    {
        $data['finding_type'] = 'strength';

        return $this->recordFinding($assessment, $data, $user);
    }

    /**
     * Enregistre une difficulté ou faiblesse sur une évaluation.
     *
     * @param  array<string, mixed>  $data
     */
    public function identifyDifficulty(DiagnosticAssessment $assessment, array $data, ?User $user = null): DiagnosticFinding
    {
        $data['finding_type'] = 'difficulty';

        return $this->recordFinding($assessment, $data, $user);
    }

    /**
     * Enregistre une hypothèse d'investigation sur une évaluation.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordHypothesis(DiagnosticAssessment $assessment, array $data, ?User $user = null): DiagnosticFinding
    {
        $data['finding_type'] = 'hypothesis';

        return $this->recordFinding($assessment, $data, $user);
    }

    /**
     * Consolide la synthèse d'un axe au sein de la matrice.
     *
     * @param  array<string, mixed>  $data
     */
    public function consolidateAxisAssessment(
        Diagnostic360Matrix $matrix,
        string $domain,
        string $axis,
        array $data
    ): DiagnosticAxisSummary {
        return DiagnosticAxisSummary::updateOrCreate(
            [
                'matrix_id' => $matrix->id,
                'domain' => $domain,
                'axis' => $axis,
            ],
            [
                'summary_text' => $data['summary_text'] ?? null,
                'strengths_summary' => $data['strengths_summary'] ?? null,
                'difficulties_summary' => $data['difficulties_summary'] ?? null,
                'information_gaps' => $data['information_gaps'] ?? null,
                'contradictions_noted' => $data['contradictions_noted'] ?? null,
                'further_investigations_needed' => $data['further_investigations_needed'] ?? null,
            ]
        );
    }

    /**
     * Consolide la synthèse globale d'un domaine au sein de la matrice.
     *
     * @param  array<string, mixed>  $data
     */
    public function consolidateDomainAssessment(
        Diagnostic360Matrix $matrix,
        string $domain,
        array $data
    ): DiagnosticDomainSummary {
        return DiagnosticDomainSummary::updateOrCreate(
            [
                'matrix_id' => $matrix->id,
                'domain' => $domain,
            ],
            [
                'overall_rating' => $data['overall_rating'] ?? null,
                'summary_text' => $data['summary_text'] ?? null,
                'key_takeaways' => $data['key_takeaways'] ?? null,
            ]
        );
    }

    /**
     * Identifie les angles morts et lacunes d'information résiduelles sur les axes examinés.
     *
     * @return array<int, array<string, mixed>>
     */
    public function identifyInformationGaps(Diagnostic360Matrix $matrix): array
    {
        $gaps = [];
        $assessments = $matrix->assessments()->with('evidences')->get();

        foreach ($assessments as $assessment) {
            $evidenceCount = $assessment->evidences->count();
            $hasLowReliability = $assessment->evidences->whereIn('assessed_reliability', ['weak', 'contradictory'])->count() > 0;

            if ($evidenceCount === 0) {
                $gaps[] = [
                    'assessment_id' => $assessment->id,
                    'domain' => $assessment->domain,
                    'axis' => $assessment->axis,
                    'gap_type' => 'absence_of_evidence',
                    'severity' => 'high',
                    'message' => "Aucun élément de preuve (document DIR ou entretien SIG) n'est rattaché à cet axe.",
                ];
            } elseif ($hasLowReliability) {
                $gaps[] = [
                    'assessment_id' => $assessment->id,
                    'domain' => $assessment->domain,
                    'axis' => $assessment->axis,
                    'gap_type' => 'fragile_or_contradictory_evidence',
                    'severity' => 'medium',
                    'message' => 'Des éléments de preuve présentent une fiabilité faible ou des contradictions à approfondir.',
                ];
            }
        }

        return $gaps;
    }

    /**
     * Génère l'instantané complet de sortie (output_snapshot) pour alimentation des outils suivants (PSG, DRD, etc.).
     *
     * @return array<string, mixed>
     */
    public function generateSnapshot(Diagnostic360Matrix $matrix): array
    {
        $assessments = $matrix->assessments()->with(['findings', 'evidences'])->get();
        $findings = $matrix->findings()->get();
        $domainSummaries = $matrix->domainSummaries()->get();
        $axisSummaries = $matrix->axisSummaries()->get();
        $gaps = $this->identifyInformationGaps($matrix);

        $strengths = $findings->where('finding_type', 'strength')->values();
        $difficulties = $findings->where('finding_type', 'difficulty')->values();
        $hypotheses = $findings->where('finding_type', 'hypothesis')->values();

        $snapshot = [
            'generated_at' => now()->toIso8601String(),
            'matrix_id' => $matrix->id,
            'mission_id' => $matrix->mission_id,
            'title' => $matrix->title,
            'status' => $matrix->status,
            'progress_percentage' => $matrix->evaluationProgressPercentage(),
            'counts' => [
                'total_assessments' => $assessments->count(),
                'completed_assessments' => $assessments->whereIn('status', ['assessed', 'validated'])->count(),
                'strengths_count' => $strengths->count(),
                'difficulties_count' => $difficulties->count(),
                'hypotheses_count' => $hypotheses->count(),
                'evidences_count' => $matrix->evidences()->count(),
                'information_gaps_count' => count($gaps),
            ],
            'domain_ratings' => $domainSummaries->mapWithKeys(fn ($ds) => [$ds->domain => [
                'rating' => $ds->overall_rating,
                'summary' => $ds->summary_text,
                'key_takeaways' => $ds->key_takeaways,
            ]])->all(),
            'critical_difficulties' => $difficulties->whereIn('severity_or_importance', ['critical', 'major'])->values()->all(),
            'key_strengths' => $strengths->take(10)->values()->all(),
            'information_gaps' => $gaps,
        ];

        $matrix->update([
            'output_snapshot' => $snapshot,
        ]);

        return $snapshot;
    }
}
