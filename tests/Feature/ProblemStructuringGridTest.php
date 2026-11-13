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
use App\Modules\MissionFramingSheet\Services\MissionFramingSheetService;
use App\Modules\ProblemStructuringGrid\Models\ProblemStructuringGrid;
use App\Modules\ProblemStructuringGrid\Services\ProblemStructuringGridService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProblemStructuringGridTest extends TestCase
{
    use RefreshDatabase;

    private function createCompleteDiagnosticContextWithD360Findings(): array
    {
        $user = User::factory()->create(['name' => 'Sophie Consultante Senior']);

        $client = Client::create([
            'reference' => 'CLI-006',
            'name' => 'Fonderies & Aciéries Réunies',
            'type' => ClientType::Entreprise,
            'sector' => 'Métallurgie lourde',
            'created_by' => $user->id,
        ]);

        $request = ClientRequest::create([
            'client_id' => $client->id,
            'reference' => 'REQ-2026-006',
            'title' => 'Diagnostic 360° Organisation & Rentabilité',
            'description' => 'Dégradation continue du résultat d’exploitation et retards de livraison.',
            'source_channel' => 'direct',
            'received_at' => now(),
            'status' => RequestStatus::Framed,
            'created_by' => $user->id,
        ]);

        InitialUnderstanding::create([
            'request_id' => $request->id,
            'context' => 'Entreprise de 120 salariés avec 2 unités de coulée.',
            'expressed_need' => 'Comprendre l’origine des goulets d’étranglement et des surcoûts d’exploitation.',
            'prepared_by' => $user->id,
            'completed_at' => now(),
        ]);

        $framing = Framing::create([
            'request_id' => $request->id,
            'reference' => 'CADRE-2026-006',
            'title' => 'Cadrage Opérationnel Métallurgie',
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'validated_at' => now(),
        ]);

        FramingVersion::create([
            'framing_id' => $framing->id,
            'version_number' => 1,
            'content' => [
                'title' => 'Cadrage Validé - Performance Industrielle',
                'context' => 'Audit industriel et financier complet',
                'objectives' => 'Restaurer la rentabilité opérationnelle.',
            ],
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'submitted_at' => now(),
            'client_decision_at' => now(),
        ]);

        $mission = Mission::create([
            'framing_id' => $framing->id,
            'reference' => 'MIS-2026-006',
            'title' => 'Mission Diagnostic Métallurgie 360°',
            'objective' => 'Identifier les dysfonctionnements et modéliser l’arbre des causes.',
            'status' => 'in_progress',
        ]);

        // MFS
        app(MissionFramingSheetService::class)->saveFraming($mission, [
            'title' => 'Cadrage Opérationnel Fonderies',
            'specific_objective' => 'Diagnostiquer la fuite de rentabilité et la planification des coulées.',
            'responsible_id' => $user->id,
        ], $user);

        // DSC
        app(DiagnosticScopeCanvasService::class)->saveScope($mission, [
            'scope_items' => [
                [
                    'id' => 'item_ops_planning',
                    'domain' => 'Opérations & Production',
                    'axis' => 'Ordonnancement & Coulées',
                    'subaxis' => 'Planning hebdomadaire',
                    'description' => 'Gestion de la charge machine et synchronisation des équipes',
                    'status' => 'included',
                ],
                [
                    'id' => 'item_fin_costs',
                    'domain' => 'Finance & Rentabilité',
                    'axis' => 'Coûts de revient & Heures supplémentaires',
                    'subaxis' => 'Dérive des frais de main d’œuvre',
                    'description' => 'Analyse des surcoûts d’urgence',
                    'status' => 'included',
                ],
            ],
        ], $user);

        // D360 Matrix
        $d360Service = app(Diagnostic360MatrixService::class);
        $matrix = $d360Service->prepareDiagnosticMatrix($mission, $user);

        $assessmentOps = $matrix->assessments()->where('domain', 'Opérations & Production')->first();
        $assessmentFin = $matrix->assessments()->where('domain', 'Finance & Rentabilité')->first();

        // 2 Difficultés constatées dans le D360
        $diff1 = $d360Service->identifyDifficulty($assessmentOps, [
            'title' => 'Modifications incessantes de l’ordonnancement d’atelier',
            'description' => 'Changement quotidien des plannings de coulée au gré des urgences commerciales.',
            'possible_causes' => 'Absence d’arbitrage formel entre ventes et production',
            'potential_consequences' => 'Arrêts non programmés des fours et rebuts thermiques accrus',
            'severity_or_importance' => 'critical',
            'is_validated' => true,
        ], $user);

        $diff2 = $d360Service->identifyDifficulty($assessmentFin, [
            'title' => 'Explosion des primes et heures supplémentaires d’urgence',
            'description' => 'Recours systématique au travail de nuit et de week-end pour rattraper les retards.',
            'possible_causes' => 'Retards accumulés en amont sur les plannings de coulée',
            'potential_consequences' => 'Dégradation de 3,5 points de la marge d’EBITDA',
            'severity_or_importance' => 'major',
            'is_validated' => true,
        ], $user);

        return compact('user', 'client', 'request', 'framing', 'mission', 'matrix', 'diff1', 'diff2');
    }

    public function test_service_prepare_problem_structuring_grid_auto_imports_d360_difficulties(): void
    {
        $context = $this->createCompleteDiagnosticContextWithD360Findings();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(ProblemStructuringGridService::class);
        $grid = $service->prepareProblemStructuringGrid($mission, $user);

        $this->assertInstanceOf(ProblemStructuringGrid::class, $grid);
        $this->assertEquals($mission->id, $grid->mission_id);
        $this->assertNotEmpty($grid->input_snapshot);

        // Les 2 difficultés D360 doivent avoir été importées en fiches problèmes initiales
        $problems = $grid->problems()->get();
        $this->assertCount(2, $problems);

        $this->assertEquals('PRB-001', $problems[0]->reference);
        $this->assertEquals('PRB-002', $problems[1]->reference);

        // Vérification de l'import des causes initiales et évaluations par défaut
        $this->assertCount(1, $problems[0]->causes);
        $this->assertEquals('hypothesis', $problems[0]->causes->first()->confirmation_level);
        $this->assertCount(1, $problems[0]->consequences);
        $this->assertNotNull($problems[0]->priorityAssessment);
        $this->assertEquals('P1', $problems[0]->priorityAssessment->priority_level);
    }

    public function test_service_create_problem_manual_and_from_finding(): void
    {
        $context = $this->createCompleteDiagnosticContextWithD360Findings();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(ProblemStructuringGridService::class);
        $grid = $service->prepareProblemStructuringGrid($mission, $user);

        $problem = $service->createProblem($grid, [
            'title' => 'Pénurie de matières premières critiques chez les sous-traitants',
            'domain' => 'Achats & Approvisionnements',
            'axis' => 'Sécurisation des approvisionnements',
            'manifestations' => 'Ruptures de stock hebdomadaires',
            'status' => 'to_examine',
        ], $user);

        $this->assertNotNull($problem->id);
        $this->assertEquals('PRB-003', $problem->reference);
        $this->assertEquals('Achats & Approvisionnements', $problem->domain);
    }

    public function test_service_update_problem_fields_and_status(): void
    {
        $context = $this->createCompleteDiagnosticContextWithD360Findings();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(ProblemStructuringGridService::class);
        $grid = $service->prepareProblemStructuringGrid($mission, $user);
        $problem = $grid->problems()->first();

        $updated = $service->updateProblem($problem, [
            'title' => 'Instabilité chronique de l’ordonnancement d’atelier',
            'status' => 'structured',
            'manifestations' => 'Taux de rebut thermique supérieur à 12%',
        ]);

        $this->assertEquals('structured', $updated->status);
        $this->assertStringContainsString('Instabilité chronique', $updated->title);
    }

    public function test_service_register_cause_and_update_confirmation_level(): void
    {
        $context = $this->createCompleteDiagnosticContextWithD360Findings();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(ProblemStructuringGridService::class);
        $grid = $service->prepareProblemStructuringGrid($mission, $user);
        $problem = $grid->problems()->first();

        $cause = $service->registerCause($problem, [
            'cause' => 'Inexistence d’un accord de niveau de service (SLA) avec les commerciaux',
            'nature' => 'organizational',
            'explanation' => 'Chaque commercial promet des délais sans consulter le plan de charge.',
            'confirmation_level' => 'partially_supported',
        ], $user);

        $this->assertNotNull($cause->id);
        $this->assertEquals('partially_supported', $cause->confirmation_level);

        // Faire évoluer vers 'confirmed'
        $updatedCause = $service->updateConfirmationLevel($cause, 'confirmed');
        $this->assertEquals('confirmed', $updatedCause->confirmation_level);

        $this->expectException(InvalidArgumentException::class);
        $service->updateConfirmationLevel($cause, 'invalid_level');
    }

    public function test_service_register_consequence_with_cross_domain_impact(): void
    {
        $context = $this->createCompleteDiagnosticContextWithD360Findings();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(ProblemStructuringGridService::class);
        $grid = $service->prepareProblemStructuringGrid($mission, $user);
        $problem = $grid->problems()->first();

        $cons = $service->registerConsequence($problem, [
            'consequence' => 'Pertes de clients stratégiques dues aux retards répétés',
            'domain' => 'Commercial & Marché',
            'importance' => 'critical',
            'description' => '2 clients grands comptes ont activé des pénalités contractuelles.',
        ]);

        $this->assertNotNull($cons->id);
        $this->assertEquals('Commercial & Marché', $cons->domain);
        $this->assertEquals('critical', $cons->importance);
    }

    public function test_service_link_problems_creates_directed_causal_relations(): void
    {
        $context = $this->createCompleteDiagnosticContextWithD360Findings();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(ProblemStructuringGridService::class);
        $grid = $service->prepareProblemStructuringGrid($mission, $user);
        $problems = $grid->problems()->get();
        $prob1 = $problems[0]; // Désorganisation ordonnancement
        $prob2 = $problems[1]; // Surcoûts heures supp

        $relation = $service->linkProblems(
            $grid,
            $prob1,
            $prob2,
            'causes',
            'Les décalages continuels de planning contraignent à faire tourner les équipes de nuit pour honorer les livraisons',
            false
        );

        $this->assertNotNull($relation->id);
        $this->assertEquals('causes', $relation->relation_type);
        $this->assertFalse($relation->is_hypothetical);
        $this->assertCount(1, $prob1->fresh()->outgoingRelations);
        $this->assertCount(1, $prob2->fresh()->incomingRelations);

        // Tentative de lier un problème à lui-même -> exception
        $this->expectException(InvalidArgumentException::class);
        $service->linkProblems($grid, $prob1, $prob1, 'causes');
    }

    public function test_service_assess_problem_priority_multicriteria(): void
    {
        $context = $this->createCompleteDiagnosticContextWithD360Findings();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(ProblemStructuringGridService::class);
        $grid = $service->prepareProblemStructuringGrid($mission, $user);
        $problem = $grid->problems()->first();

        $assessment = $service->assessProblem($problem, [
            'severity' => 'critical',
            'frequency' => 'systemic',
            'scope_extent' => 'company_wide',
            'urgency' => 'immediate',
            'strategic_impact' => 'vital',
            'priority_level' => 'P1',
            'rationale' => 'L’instabilité de l’outil industriel menace directement la trésorerie.',
        ], $user);

        $this->assertEquals('P1', $assessment->priority_level);
        $this->assertEquals('vital', $assessment->strategic_impact);
        $this->assertEquals('immediate', $assessment->urgency);
    }

    public function test_service_generate_problem_structure_and_snapshot(): void
    {
        $context = $this->createCompleteDiagnosticContextWithD360Findings();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(ProblemStructuringGridService::class);
        $grid = $service->prepareProblemStructuringGrid($mission, $user);
        $problems = $grid->problems()->get();

        $service->linkProblems($grid, $problems[0], $problems[1], 'causes');

        $structure = $service->generateProblemStructure($grid);
        $this->assertEquals(2, $structure['total_problems']);
        $this->assertEquals(1, $structure['total_relations']);
        $this->assertContains($problems[0]->id, $structure['root_problem_ids']);

        $snapshot = $service->generateSnapshot($grid);
        $this->assertIsArray($snapshot);
        $this->assertEquals($grid->id, $snapshot['grid_id']);
        $this->assertEquals(2, $snapshot['counts']['total_problems']);
        $this->assertEquals(1, $snapshot['counts']['relations_count']);
        $this->assertNotNull($grid->fresh()->output_snapshot);
    }

    public function test_controller_show_html_view_and_json_api(): void
    {
        $context = $this->createCompleteDiagnosticContextWithD360Findings();
        $mission = $context['mission'];
        $user = $context['user'];

        $this->actingAs($user);

        // 1. Vue Web HTML
        $response = $this->get(route('missions.problem-grid.show', $mission));
        $response->assertOk();
        $response->assertViewIs('problem-grids.show');
        $response->assertSee('Problem Structuring Grid™');
        $response->assertSee('PRB-001');

        // 2. Réponse JSON API
        $jsonRes = $this->getJson(route('missions.problem-grid.show', $mission));
        $jsonRes->assertOk();
        $jsonRes->assertJsonPath('success', true);
        $jsonRes->assertJsonStructure([
            'success',
            'data' => [
                'grid',
                'problems',
                'relations',
                'structure',
            ],
        ]);
    }

    public function test_controller_endpoints_for_problem_cause_consequence_and_relation(): void
    {
        $context = $this->createCompleteDiagnosticContextWithD360Findings();
        $mission = $context['mission'];
        $user = $context['user'];

        $this->actingAs($user);

        $service = app(ProblemStructuringGridService::class);
        $grid = $service->prepareProblemStructuringGrid($mission, $user);
        $problems = $grid->problems()->get();
        $p1 = $problems[0];
        $p2 = $problems[1];

        // 1. Store Cause
        $resCause = $this->post(route('missions.problem-grid.problems.causes.store', [$mission, $p1]), [
            'cause' => 'Défaut d’étalonnage des sondes de température',
            'nature' => 'technical',
            'explanation' => 'Rebuts thermiques générés par une fausse lecture',
            'confirmation_level' => 'partially_supported',
        ]);
        $resCause->assertRedirect();
        $this->assertDatabaseHas('problem_causes', [
            'problem_id' => $p1->id,
            'cause' => 'Défaut d’étalonnage des sondes de température',
        ]);

        // 2. Store Consequence
        $resCons = $this->post(route('missions.problem-grid.problems.consequences.store', [$mission, $p1]), [
            'consequence' => 'Pertes de matières premières nobles',
            'domain' => 'Finance & Rentabilité',
            'importance' => 'high',
        ]);
        $resCons->assertRedirect();
        $this->assertDatabaseHas('problem_consequences', [
            'problem_id' => $p1->id,
            'consequence' => 'Pertes de matières premières nobles',
        ]);

        // 3. Store Relation
        $resRel = $this->post(route('missions.problem-grid.relations.store', $mission), [
            'source_problem_id' => $p1->id,
            'target_problem_id' => $p2->id,
            'relation_type' => 'aggravates',
            'explanation' => 'Accentue la pression sur les équipes de maintenance',
        ]);
        $resRel->assertRedirect();
        $this->assertDatabaseHas('problem_relations', [
            'grid_id' => $grid->id,
            'source_problem_id' => $p1->id,
            'target_problem_id' => $p2->id,
            'relation_type' => 'aggravates',
        ]);

        // 4. Assess Priority
        $resPrio = $this->post(route('missions.problem-grid.problems.priority', [$mission, $p1]), [
            'severity' => 'critical',
            'frequency' => 'systemic',
            'scope_extent' => 'company_wide',
            'urgency' => 'immediate',
            'strategic_impact' => 'vital',
            'priority_level' => 'P1',
            'rationale' => 'À traiter en priorité absolue dans le plan de restructuration',
        ]);
        $resPrio->assertRedirect();
        $this->assertDatabaseHas('problem_priority_assessments', [
            'problem_id' => $p1->id,
            'priority_level' => 'P1',
        ]);

        // 5. Generate Snapshot
        $resSnap = $this->post(route('missions.problem-grid.snapshot', $mission));
        $resSnap->assertRedirect();
        $this->assertNotNull($grid->fresh()->output_snapshot);
    }
}
