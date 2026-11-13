<?php

namespace Tests\Feature;

use App\Enums\ClientType;
use App\Enums\FramingStatus;
use App\Enums\RequestStatus;
use App\Models\User;
use App\Modules\ClientRelationship\Http\Models\Client;
use App\Modules\ClientRelationship\Http\Models\Framing;
use App\Modules\ClientRelationship\Http\Models\FramingVersion;
use App\Modules\ClientRelationship\Http\Models\InitialUnderstanding;
use App\Modules\ClientRelationship\Http\Models\Request as ClientRequest;
use App\Modules\Diagnostic360Matrix\Services\Diagnostic360MatrixService;
use App\Modules\DiagnosticScopeCanvas\Services\DiagnosticScopeCanvasService;
use App\Modules\LiveDecisionCapture\Services\LiveDecisionCaptureService;
use App\Modules\MissionFramingSheet\Services\MissionFramingSheetService;
use App\Modules\NextStepRouter\Models\NextStepAction;
use App\Modules\NextStepRouter\Models\NextStepRouter;
use App\Modules\NextStepRouter\Models\Recommendation;
use App\Modules\NextStepRouter\Services\NextStepRouterService;
use App\Modules\ProblemStructuringGrid\Services\ProblemStructuringGridService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextStepRouterTest extends TestCase
{
    use RefreshDatabase;

    private function createCompleteMissionContextWithUpstreamTools(): array
    {
        $user = User::factory()->create(['name' => 'Alexandre Stratège']);

        $client = Client::create([
            'reference' => 'CLI-010',
            'name' => 'Pharmaceutique & Santé Biologique',
            'type' => ClientType::Entreprise,
            'sector' => 'Santé & Pharmacie',
            'created_by' => $user->id,
        ]);

        $request = ClientRequest::create([
            'client_id' => $client->id,
            'reference' => 'REQ-2026-010',
            'title' => 'Diagnostic Organisationnel & Qualité BPF',
            'description' => 'Mise en conformité des lignes de conditionnement stérile.',
            'source_channel' => 'direct',
            'received_at' => now(),
            'status' => RequestStatus::Framed,
            'created_by' => $user->id,
        ]);

        InitialUnderstanding::create([
            'request_id' => $request->id,
            'context' => 'Laboratoire de production de solutions injectables.',
            'expressed_need' => 'Identifier les causes des dérives de mirage et sécuriser les cadences.',
            'prepared_by' => $user->id,
            'completed_at' => now(),
        ]);

        $framing = Framing::create([
            'request_id' => $request->id,
            'reference' => 'CADRE-2026-010',
            'title' => 'Cadrage Opérationnel Pharma',
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'validated_at' => now(),
        ]);

        FramingVersion::create([
            'framing_id' => $framing->id,
            'version_number' => 1,
            'content' => [
                'title' => 'Cadrage Validé - Performance Stérile',
                'context' => 'Audit 360° et feuille de route',
                'objectives' => 'Formuler les recommandations et ordonnancer les prochaines étapes.',
            ],
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'submitted_at' => now(),
            'client_decision_at' => now(),
        ]);

        $mission = Mission::create([
            'framing_id' => $framing->id,
            'reference' => 'MIS-2026-010',
            'title' => 'Mission Diagnostic Pharma & Roadmap',
            'objective' => 'Délivrer une feuille de route priorisée et suivie.',
            'status' => 'in_progress',
        ]);

        // MFS
        app(MissionFramingSheetService::class)->saveFraming($mission, [
            'title' => 'Cadrage Opérationnel Pharma',
            'specific_objective' => 'Construire la feuille de route opérationnelle post-diagnostic.',
            'responsible_id' => $user->id,
        ], $user);

        // DSC
        app(DiagnosticScopeCanvasService::class)->saveScope($mission, [
            'scope_items' => [
                [
                    'domain' => 'Production & BPF',
                    'axis' => 'Mirage automatique et manuel des ampoules',
                    'depth' => 'approfondi',
                    'status' => 'included',
                    'justification' => 'Taux de faux-rebuts élevé',
                ],
                [
                    'domain' => 'Maintenance & Métrologie',
                    'axis' => 'Étalonnage des caméras d’inspection optique',
                    'depth' => 'approfondi',
                    'status' => 'included',
                    'justification' => 'Dérive des seuils de sensibilité',
                ],
            ],
        ], $user);

        // D360
        $d360Service = app(Diagnostic360MatrixService::class);
        $matrix = $d360Service->prepareDiagnosticMatrix($mission, $user);
        $assessment = $matrix->assessments()->first();

        if ($assessment) {
            $d360Service->identifyDifficulty($assessment, [
                'title' => 'Dérive de sensibilité des caméras d’inspection de mirage',
                'description' => 'Faux rejets constatés sur 8,2% des flacons conformes.',
                'severity_or_importance' => 'critical',
                'is_validated' => true,
            ], $user);
        }

        // PSG
        $psgService = app(ProblemStructuringGridService::class);
        $psg = $psgService->prepareProblemStructuringGrid($mission, $user);
        $problem = $psg->problems()->first();

        // LDC
        $ldcService = app(LiveDecisionCaptureService::class);
        $ldc = $ldcService->prepareDecisionCapture($mission, $user);
        $decision = $ldcService->registerDecision($ldc, [
            'title' => 'Arrêt programmé de la ligne 2 pour recalibration optique',
            'decision_text' => 'Validation de l’intervention technique de 48 heures en semaine 36.',
            'nature' => 'arbitrage',
            'decision_maker_type' => 'client',
            'decision_maker_name' => 'Directeur d’Usine Pharma',
            'status' => 'taken',
        ], $user);

        return compact('user', 'client', 'mission', 'matrix', 'problem', 'decision');
    }

    public function test_service_initializes_next_step_router_and_loads_upstream_data(): void
    {
        $context = $this->createCompleteMissionContextWithUpstreamTools();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(NextStepRouterService::class);
        $router = $service->prepareNextStepRouter($mission, $user);

        $this->assertInstanceOf(NextStepRouter::class, $router);
        $this->assertEquals($mission->id, $router->mission_id);
        $this->assertEquals('in_progress', $router->status);
        $this->assertEquals('NSR-MIS-2026-010', $router->reference);
        $this->assertNotEmpty($router->input_snapshot);
        $this->assertGreaterThanOrEqual(1, $router->input_snapshot['problems_count']);
        $this->assertGreaterThanOrEqual(1, $router->input_snapshot['decisions_count']);
    }

    public function test_service_prepare_recommendations_from_structured_problems(): void
    {
        $context = $this->createCompleteMissionContextWithUpstreamTools();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(NextStepRouterService::class);
        $router = $service->prepareNextStepRouter($mission, $user);

        $recommendations = $service->prepareRecommendations($router, $user);

        $this->assertIsArray($recommendations);
        $this->assertCount(1, $recommendations);
        $rec = $recommendations[0];

        $this->assertInstanceOf(Recommendation::class, $rec);
        $this->assertEquals('REC-001', $rec->reference);
        $this->assertEquals('critical', $rec->priority);
        $this->assertStringContainsString('Dérive de sensibilité', $rec->title);

        // Une action corrective associée doit avoir été générée
        $this->assertCount(1, $rec->actions);
        $action = $rec->actions->first();
        $this->assertEquals('ACT-001', $action->reference);
        $this->assertEquals('immediate', $action->horizon);
        $this->assertEquals($context['problem']->reference, $action->problem_reference);
    }

    public function test_service_manual_recommendation_and_action_creation(): void
    {
        $context = $this->createCompleteMissionContextWithUpstreamTools();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(NextStepRouterService::class);
        $router = $service->prepareNextStepRouter($mission, $user);

        $rec = $service->createRecommendation($router, [
            'title' => 'Harmoniser la formation des opérateurs de mirage manuel',
            'domain' => 'Production & BPF',
            'priority' => 'high',
            'justification' => 'Uniformiser les critères de détection humaine des micro-fissures.',
        ], $user);

        $this->assertEquals('REC-001', $rec->reference);
        $this->assertEquals('proposed', $rec->status);

        $action = $service->createAction($router, [
            'recommendation_id' => $rec->id,
            'title' => 'Rédiger le livret étalon de défauts visuels',
            'priority' => 'high',
            'horizon' => 'short_term',
            'responsible_name' => 'Responsable Assurance Qualité',
            'due_date' => now()->addDays(20)->format('Y-m-d'),
            'expected_result' => 'Livret validé par la Pharmacie Responsable et diffusé aux miriers.',
        ], $user);

        $this->assertEquals('ACT-001', $action->reference);
        $this->assertEquals($rec->id, $action->recommendation_id);
        $this->assertEquals('proposed', $action->status);
    }

    public function test_service_dynamic_roadmap_by_horizon(): void
    {
        $context = $this->createCompleteMissionContextWithUpstreamTools();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(NextStepRouterService::class);
        $router = $service->prepareNextStepRouter($mission, $user);

        $service->createAction($router, ['title' => 'Calibrage d’urgence banc de mirage', 'horizon' => 'immediate'], $user);
        $service->createAction($router, ['title' => 'Mise à niveau procédure BPF', 'horizon' => 'short_term'], $user);
        $service->createAction($router, ['title' => 'Certification ligne stérile', 'horizon' => 'medium_term'], $user);

        $roadmap = $router->roadmapByHorizon();

        $this->assertIsArray($roadmap);
        $this->assertArrayHasKey('immediate', $roadmap);
        $this->assertArrayHasKey('short_term', $roadmap);
        $this->assertArrayHasKey('medium_term', $roadmap);

        $this->assertCount(1, $roadmap['immediate']['actions']);
        $this->assertCount(1, $roadmap['short_term']['actions']);
        $this->assertCount(1, $roadmap['medium_term']['actions']);
    }

    public function test_service_action_status_workflow_and_snapshot(): void
    {
        $context = $this->createCompleteMissionContextWithUpstreamTools();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(NextStepRouterService::class);
        $router = $service->prepareNextStepRouter($mission, $user);

        $action = $service->createAction($router, ['title' => 'Mise à jour des protocoles d’inspection'], $user);

        // Proposed -> In Progress
        $service->updateActionStatus($action, 'in_progress');
        $action->refresh();
        $this->assertEquals('in_progress', $action->status);

        // Record Action Result (Completed)
        $service->recordActionResult($action, 'Protocole v3.0 approuvé et validé sans réserve par l’ANSM.');
        $action->refresh();
        $this->assertEquals('completed', $action->status);
        $this->assertEquals('Protocole v3.0 approuvé et validé sans réserve par l’ANSM.', $action->actual_result);

        // Snapshot
        $snapshot = $service->generateSnapshot($router);
        $this->assertIsArray($snapshot);
        $this->assertEquals(100, $snapshot['completion_percentage']);
        $this->assertEquals(1, $snapshot['counts']['completed_actions']);
    }

    public function test_web_routes_for_next_step_router(): void
    {
        $context = $this->createCompleteMissionContextWithUpstreamTools();
        $mission = $context['mission'];
        $user = $context['user'];

        // GET show
        $response = $this->actingAs($user)->get(route('missions.next-step-router.show', $mission));
        $response->assertStatus(200);
        $response->assertSee('Next-Step Router');
        $response->assertSee('Pharmaceutique &amp; Santé Biologique', false);

        // POST prepare recommendations
        $prepResponse = $this->actingAs($user)->post(route('missions.next-step-router.prepare', $mission));
        $prepResponse->assertRedirect();
        $this->assertDatabaseHas('recommendations', [
            'next_step_router_id' => $mission->nextStepRouter->id,
        ]);

        // POST create action
        $actionResponse = $this->actingAs($user)->post(route('missions.next-step-router.actions.store', $mission), [
            'title' => 'Audit à blanc inspection optique',
            'priority' => 'critical',
            'horizon' => 'immediate',
            'responsible_name' => 'Auditeur Qualité Externe',
            'expected_result' => 'Rapport de pré-inspection blanc conforme.',
        ]);
        $actionResponse->assertRedirect();
        $this->assertDatabaseHas('next_step_actions', [
            'title' => 'Audit à blanc inspection optique',
        ]);

        $action = NextStepAction::where('title', 'Audit à blanc inspection optique')->first();

        // PUT status
        $statusResponse = $this->actingAs($user)->put(route('missions.next-step-router.actions.status.update', [$mission, $action]), [
            'status' => 'validated',
        ]);
        $statusResponse->assertRedirect();
        $action->refresh();
        $this->assertEquals('validated', $action->status);
    }
}
