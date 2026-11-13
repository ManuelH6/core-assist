<?php

namespace App\Modules\NextStepRouter\Services;

use App\Models\User;
use App\Modules\Diagnostic360Matrix\Models\Diagnostic360Matrix;
use App\Modules\DiagnosticArchivePack\Models\DiagnosticArchivePack;
use App\Modules\DiagnosticIntakeRegister\Models\DiagnosticIntakeRegister;
use App\Modules\DiagnosticRestitutionDeck\Models\DiagnosticRestitutionDeck;
use App\Modules\DiagnosticScopeCanvas\Models\DiagnosticScopeCanvas;
use App\Modules\LiveDecisionCapture\Models\LiveDecisionCapture;
use App\Modules\MissionFramingSheet\Models\MissionFramingSheet;
use App\Modules\NextStepRouter\Models\NextStepAction;
use App\Modules\NextStepRouter\Models\NextStepRouter;
use App\Modules\NextStepRouter\Models\Recommendation;
use App\Modules\ProblemStructuringGrid\Models\ProblemStructuringGrid;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\StakeholderInputGrid\Models\StakeholderInputGrid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class NextStepRouterService
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

    public function getDecisionCapture(Mission $mission): ?LiveDecisionCapture
    {
        return $mission->decisionCapture;
    }

    public function getArchivePack(Mission $mission): ?DiagnosticArchivePack
    {
        return $mission->archivePack;
    }

    /**
     * Récupère l'ensemble des données d'amont utiles au Next-Step Router™ sans aucune ressaisie.
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
        $ldc = $this->getDecisionCapture($mission);

        $stakeholderList = $stakeholders ? $stakeholders->stakeholders()->get() : new Collection;
        $problems = $psg ? $psg->problems()->with('priorityAssessment')->get() : new Collection;
        $findings = $matrix ? $matrix->findings()->get() : new Collection;
        $decisions = $ldc ? $ldc->decisions()->get() : new Collection;

        return [
            'mission' => [
                'id' => $mission->id,
                'title' => $mission->title,
                'reference' => $mission->reference,
                'client' => $mission->framing?->request?->client?->name ?? $mission->framing?->client?->name,
                'objectives' => $framing?->operational_objectives,
            ],
            'scope_domains' => $scope ? collect($scope->scope_items ?? [])->pluck('domain')->unique()->values()->all() : [],
            'problems' => $problems,
            'findings' => $findings,
            'decisions' => $decisions,
            'stakeholders' => $stakeholderList,
        ];
    }

    /**
     * Initialise ou charge le conteneur Next-Step Router™ pour la mission.
     */
    public function prepareNextStepRouter(Mission $mission, ?User $user = null): NextStepRouter
    {
        return DB::transaction(function () use ($mission, $user) {
            $ref = sprintf('NSR-%s', $mission->reference ?: ('MIS-'.$mission->id));

            $router = NextStepRouter::firstOrCreate(
                ['mission_id' => $mission->id],
                [
                    'reference' => $ref,
                    'title' => 'Next-Step Router™ - '.$mission->title,
                    'description' => 'Feuille de route, recommandations stratégiques et plan d’action opérationnel issu du diagnostic 360°.',
                    'status' => 'in_progress',
                    'responsible_id' => $user?->id,
                ]
            );

            $initialData = $this->getInitialData($mission);
            $router->update([
                'input_snapshot' => [
                    'generated_at' => now()->toIso8601String(),
                    'mission_reference' => $initialData['mission']['reference'],
                    'problems_count' => $initialData['problems']->count(),
                    'findings_count' => $initialData['findings']->count(),
                    'decisions_count' => $initialData['decisions']->count(),
                ],
            ]);

            return $router->load(['recommendations.actions', 'actions.recommendation']);
        });
    }

    /**
     * Prépare et propose des recommandations automatiques à partir des problèmes critiques PSG.
     *
     * @return array<int, Recommendation>
     */
    public function prepareRecommendations(NextStepRouter $router, ?User $user = null): array
    {
        $initialData = $this->getInitialData($router->mission);
        $problems = $initialData['problems'];
        $created = [];

        DB::transaction(function () use ($router, $problems, $user, &$created) {
            foreach ($problems as $problem) {
                $exists = $router->recommendations()
                    ->where('title', 'like', '%'.$problem->title.'%')
                    ->exists();

                if (! $exists) {
                    $nextNum = $router->recommendations()->count() + 1;
                    $ref = sprintf('REC-%03d', $nextNum);

                    $rec = $router->recommendations()->create([
                        'reference' => $ref,
                        'title' => 'Traiter le dysfonctionnement : '.$problem->title,
                        'domain' => $problem->domain,
                        'axis' => $problem->axis,
                        'description' => 'Recommandation issue du problème structuré '.$problem->reference.'. '.($problem->description ?? ''),
                        'justification' => 'Résorber les causes racines et sécuriser la performance durable de l’organisation.',
                        'priority' => $problem->priorityAssessment?->priority_level === 'P1' ? 'critical' : 'high',
                        'status' => 'proposed',
                        'input_snapshot' => [
                            'problem_reference' => $problem->reference,
                            'problem_title' => $problem->title,
                        ],
                        'created_by' => $user?->id,
                    ]);

                    // Créer une première action corrective associée
                    $actionNextNum = $router->actions()->count() + 1;
                    $actRef = sprintf('ACT-%03d', $actionNextNum);

                    $router->actions()->create([
                        'recommendation_id' => $rec->id,
                        'reference' => $actRef,
                        'title' => 'Plan de correction immédiat : '.$problem->title,
                        'description' => 'Mise en œuvre des mesures correctives et des arbitrages requis.',
                        'expected_result' => 'Maîtrise opérationnelle du problème '.$problem->reference,
                        'priority' => $rec->priority,
                        'horizon' => $rec->priority === 'critical' ? 'immediate' : 'short_term',
                        'status' => 'proposed',
                        'problem_reference' => $problem->reference,
                    ]);

                    $created[] = $rec;
                }
            }
        });

        return $created;
    }

    /**
     * Enregistre manuellement une recommandation.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRecommendation(NextStepRouter $router, array $data, ?User $user = null): Recommendation
    {
        $nextNum = $router->recommendations()->count() + 1;
        $ref = $data['reference'] ?? sprintf('REC-%03d', $nextNum);

        return $router->recommendations()->create([
            'reference' => $ref,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'justification' => $data['justification'] ?? null,
            'priority' => $data['priority'] ?? 'high',
            'status' => $data['status'] ?? 'proposed',
            'domain' => $data['domain'] ?? null,
            'axis' => $data['axis'] ?? null,
            'input_snapshot' => $data['input_snapshot'] ?? null,
            'created_by' => $user?->id,
        ]);
    }

    /**
     * Met à jour une recommandation.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateRecommendation(Recommendation $rec, array $data): Recommendation
    {
        $rec->update([
            'title' => $data['title'] ?? $rec->title,
            'description' => $data['description'] ?? $rec->description,
            'justification' => $data['justification'] ?? $rec->justification,
            'priority' => $data['priority'] ?? $rec->priority,
            'status' => $data['status'] ?? $rec->status,
            'domain' => $data['domain'] ?? $rec->domain,
            'axis' => $data['axis'] ?? $rec->axis,
        ]);

        return $rec->fresh(['actions']);
    }

    /**
     * Crée une action concrète dans le Next-Step Router™.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAction(NextStepRouter $router, array $data, ?User $user = null): NextStepAction
    {
        $nextNum = $router->actions()->count() + 1;
        $ref = $data['reference'] ?? sprintf('ACT-%03d', $nextNum);

        return $router->actions()->create([
            'recommendation_id' => $data['recommendation_id'] ?? null,
            'reference' => $ref,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'expected_result' => $data['expected_result'] ?? null,
            'responsible_id' => $data['responsible_id'] ?? ($data['assigned_to_user_id'] ?? null),
            'responsible_name' => $data['responsible_name'] ?? null,
            'priority' => $data['priority'] ?? 'high',
            'horizon' => $data['horizon'] ?? 'short_term',
            'planned_start_date' => $data['planned_start_date'] ?? null,
            'due_date' => $data['due_date'] ?? ($data['target_date'] ?? null),
            'status' => $data['status'] ?? 'proposed',
            'problem_reference' => $data['problem_reference'] ?? null,
            'finding_reference' => $data['finding_reference'] ?? null,
            'decision_reference' => $data['decision_reference'] ?? null,
        ]);
    }

    /**
     * Met à jour une action.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAction(NextStepAction $action, array $data): NextStepAction
    {
        $action->update([
            'title' => $data['title'] ?? $action->title,
            'recommendation_id' => array_key_exists('recommendation_id', $data) ? $data['recommendation_id'] : $action->recommendation_id,
            'description' => $data['description'] ?? $action->description,
            'expected_result' => $data['expected_result'] ?? $action->expected_result,
            'responsible_id' => $data['responsible_id'] ?? $action->responsible_id,
            'responsible_name' => $data['responsible_name'] ?? $action->responsible_name,
            'priority' => $data['priority'] ?? $action->priority,
            'horizon' => $data['horizon'] ?? $action->horizon,
            'planned_start_date' => $data['planned_start_date'] ?? $action->planned_start_date,
            'due_date' => $data['due_date'] ?? $action->due_date,
            'status' => $data['status'] ?? $action->status,
            'actual_result' => $data['actual_result'] ?? $action->actual_result,
        ]);

        return $action->fresh(['recommendation', 'responsible']);
    }

    /**
     * Met à jour le statut d'une action.
     */
    public function updateActionStatus(NextStepAction $action, string $newStatus): NextStepAction
    {
        $action->update(['status' => $newStatus]);

        return $action;
    }

    /**
     * Enregistre le résultat effectivement constaté.
     */
    public function recordActionResult(NextStepAction $action, string $actualResult): NextStepAction
    {
        $action->update([
            'actual_result' => $actualResult,
            'status' => 'completed',
        ]);

        return $action;
    }

    /**
     * Produit et enregistre l'instantané consolidé (`output_snapshot`) du Next-Step Router™.
     *
     * @return array<string, mixed>
     */
    public function generateSnapshot(NextStepRouter $router): array
    {
        $recommendations = $router->recommendations()->with('actions')->get();
        $actions = $router->actions()->with('recommendation')->get();
        $roadmap = $router->roadmapByHorizon();

        $snapshot = [
            'generated_at' => now()->toIso8601String(),
            'router_reference' => $router->reference,
            'mission_id' => $router->mission_id,
            'mission_reference' => $router->mission?->reference,
            'status' => $router->status,
            'completion_percentage' => $router->completionPercentage(),
            'counts' => [
                'recommendations_count' => $recommendations->count(),
                'actions_count' => $actions->count(),
                'proposed_actions' => $actions->where('status', 'proposed')->count(),
                'validated_actions' => $actions->where('status', 'validated')->count(),
                'in_progress_actions' => $actions->where('status', 'in_progress')->count(),
                'completed_actions' => $actions->where('status', 'completed')->count(),
                'blocked_actions' => $actions->where('status', 'blocked')->count(),
            ],
            'recommendations' => $recommendations->map(fn (Recommendation $r) => [
                'reference' => $r->reference,
                'title' => $r->title,
                'priority' => $r->priority,
                'status' => $r->status,
                'actions_count' => $r->actions->count(),
            ])->all(),
            'actions' => $actions->map(fn (NextStepAction $a) => [
                'reference' => $a->reference,
                'title' => $a->title,
                'priority' => $a->priority,
                'horizon' => $a->horizon,
                'responsible' => $a->responsible_name ?: $a->responsible?->name,
                'status' => $a->status,
                'due_date' => $a->due_date?->format('Y-m-d'),
                'problem_reference' => $a->problem_reference,
            ])->all(),
            'roadmap_summary' => [
                'immediate' => $roadmap['immediate']['actions']->count(),
                'short_term' => $roadmap['short_term']['actions']->count(),
                'medium_term' => $roadmap['medium_term']['actions']->count(),
                'long_term' => $roadmap['long_term']['actions']->count(),
            ],
        ];

        $router->update([
            'output_snapshot' => $snapshot,
        ]);

        return $snapshot;
    }
}
