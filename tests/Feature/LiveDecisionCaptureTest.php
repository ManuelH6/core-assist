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
use App\Modules\DiagnosticRestitutionDeck\Services\DiagnosticRestitutionDeckService;
use App\Modules\DiagnosticScopeCanvas\Services\DiagnosticScopeCanvasService;
use App\Modules\LiveDecisionCapture\Models\CapturedDecision;
use App\Modules\LiveDecisionCapture\Models\DecisionExchange;
use App\Modules\LiveDecisionCapture\Models\LiveDecisionCapture;
use App\Modules\LiveDecisionCapture\Services\LiveDecisionCaptureService;
use App\Modules\MissionFramingSheet\Services\MissionFramingSheetService;
use App\Modules\ProblemStructuringGrid\Services\ProblemStructuringGridService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveDecisionCaptureTest extends TestCase
{
    use RefreshDatabase;

    private function createCompleteMissionContext(): array
    {
        $user = User::factory()->create(['name' => 'Alexandre Arbitrageur']);

        $client = Client::create([
            'reference' => 'CLI-008',
            'name' => 'Manufacture Navale de l’Atlantique',
            'type' => ClientType::Entreprise,
            'sector' => 'Construction Navale',
            'created_by' => $user->id,
        ]);

        $request = ClientRequest::create([
            'client_id' => $client->id,
            'reference' => 'REQ-2026-008',
            'title' => 'Diagnostic Restructuration Navale',
            'description' => 'Arbitrages sur la réorganisation des ateliers et de la supply chain.',
            'source_channel' => 'direct',
            'received_at' => now(),
            'status' => RequestStatus::Framed,
            'created_by' => $user->id,
        ]);

        InitialUnderstanding::create([
            'request_id' => $request->id,
            'context' => 'Chantier naval de 320 collaborateurs.',
            'expressed_need' => 'Décider des priorités d’action industrielle.',
            'prepared_by' => $user->id,
            'completed_at' => now(),
        ]);

        $framing = Framing::create([
            'request_id' => $request->id,
            'reference' => 'CADRE-2026-008',
            'title' => 'Cadrage Opérationnel Navale',
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'validated_at' => now(),
        ]);

        FramingVersion::create([
            'framing_id' => $framing->id,
            'version_number' => 1,
            'content' => [
                'title' => 'Cadrage Validé - Performance Navale',
                'context' => 'Audit opérationnel complet',
                'objectives' => 'Décider du schéma directeur.',
            ],
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'submitted_at' => now(),
            'client_decision_at' => now(),
        ]);

        $mission = Mission::create([
            'framing_id' => $framing->id,
            'reference' => 'MIS-2026-008',
            'title' => 'Mission Décision & Arbitrage Naval',
            'objective' => 'Formaliser et piloter les décisions stratégiques.',
            'status' => 'in_progress',
        ]);

        // MFS
        app(MissionFramingSheetService::class)->saveFraming($mission, [
            'title' => 'Cadrage Opérationnel Naval',
            'specific_objective' => 'Conduire la démarche d’arbitrage décisionnel.',
            'responsible_id' => $user->id,
        ], $user);

        // DSC
        app(DiagnosticScopeCanvasService::class)->saveScope($mission, [
            'scope_items' => [
                [
                    'domain' => 'Production & Ingénierie',
                    'axis' => 'Flux d’assemblage de coque',
                    'depth' => 'approfondi',
                    'status' => 'included',
                    'justification' => 'Goulot d’étranglement identifié',
                ],
                [
                    'domain' => 'Achats & Approvisionnements',
                    'axis' => 'Délais composants acier',
                    'depth' => 'standard',
                    'status' => 'included',
                    'justification' => 'Retards fournisseurs critiques',
                ],
            ],
        ], $user);

        // D360 Matrix
        $d360Service = app(Diagnostic360MatrixService::class);
        $matrix = $d360Service->prepareDiagnosticMatrix($mission, $user);
        $assessment = $matrix->assessments()->first();

        $diff = null;
        if ($assessment) {
            $diff = $d360Service->identifyDifficulty($assessment, [
                'title' => 'Cadence de coque inférieure de 28% aux standards du secteur.',
                'description' => 'Temps de cycle mesuré à 42 jours vs 30 jours cible.',
                'severity_or_importance' => 'critical',
                'is_validated' => true,
            ], $user);
        }

        // PSG Grid
        $psgService = app(ProblemStructuringGridService::class);
        $psg = $psgService->prepareProblemStructuringGrid($mission, $user);
        $problem = $psg->problems()->first();

        // Restitution Deck
        app(DiagnosticRestitutionDeckService::class)->prepareRestitutionDeck($mission, $user);

        return [
            'user' => $user,
            'client' => $client,
            'mission' => $mission,
            'problem' => $problem,
            'diff' => $diff,
            'finding' => $assessment ? $assessment->findings()->first() : null,
        ];
    }

    public function test_can_prepare_live_decision_capture_and_load_upstream_data(): void
    {
        $context = $this->createCompleteMissionContext();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(LiveDecisionCaptureService::class);
        $capture = $service->prepareDecisionCapture($mission, $user);

        $this->assertInstanceOf(LiveDecisionCapture::class, $capture);
        $this->assertEquals($mission->id, $capture->mission_id);
        $this->assertEquals('in_progress', $capture->status);
        $this->assertNotEmpty($capture->input_snapshot);
        $this->assertEquals('MIS-2026-008', $capture->input_snapshot['mission_reference']);
        $this->assertGreaterThanOrEqual(1, $capture->input_snapshot['problems_count']);

        $initialData = $service->getInitialData($mission);
        $this->assertCount(1, $initialData['problems']);
        $this->assertEquals('Cadence de coque inférieure de 28% aux standards du secteur.', $initialData['problems']->first()->title);
    }

    public function test_can_register_exchange_and_discussion_points(): void
    {
        $context = $this->createCompleteMissionContext();
        $mission = $context['mission'];
        $user = $context['user'];
        $problem = $context['problem'];

        $service = app(LiveDecisionCaptureService::class);
        $capture = $service->prepareDecisionCapture($mission, $user);

        $exchange = $service->registerExchange($capture, [
            'title' => 'Comité Stratégique de Direction Navale',
            'exchanged_at' => now()->format('Y-m-d H:i:s'),
            'modality' => 'steering_committee',
            'location' => 'Salle du Conseil de Direction, Saint-Nazaire',
            'context' => 'Validation des arbitrages sur la modernisation du banc de soudure.',
            'attendees' => [
                ['name' => 'Jean-Marc Directeur Général', 'role' => 'Commanditaire', 'organization' => 'Manufacture Navale'],
                ['name' => 'Sophie Directrice Industrielle', 'role' => 'Opérations', 'organization' => 'Manufacture Navale'],
                ['name' => 'Alexandre Arbitrageur', 'role' => 'Directeur de Mission', 'organization' => 'CoreAssist'],
            ],
        ], $user);

        $this->assertInstanceOf(DecisionExchange::class, $exchange);
        $this->assertEquals('Comité Stratégique de Direction Navale', $exchange->title);
        $this->assertEquals('steering_committee', $exchange->modality);
        $this->assertCount(3, $exchange->attendees);

        $point = $service->addDiscussionPoint($exchange, [
            'subject' => 'Capacité d’investissement banc de soudure',
            'description' => 'Arbitrer entre la modernisation de la ligne existante ou l’externalisation du tronçon avant.',
            'domain' => 'Production & Ingénierie',
            'axis' => 'Flux d’assemblage de coque',
            'problem_id' => $problem->id,
            'observations' => 'Consensus de l’équipe industrielle en faveur du rétrofit interne sous 90 jours.',
        ]);

        $this->assertEquals('Capacité d’investissement banc de soudure', $point->subject);
        $this->assertEquals($problem->id, $point->problem_id);
        $this->assertCount(1, $exchange->discussionPoints);
    }

    public function test_can_register_decision_with_commitments_and_sources(): void
    {
        $context = $this->createCompleteMissionContext();
        $mission = $context['mission'];
        $user = $context['user'];
        $problem = $context['problem'];
        $finding = $context['finding'];

        $service = app(LiveDecisionCaptureService::class);
        $capture = $service->prepareDecisionCapture($mission, $user);

        $exchange = $service->registerExchange($capture, [
            'title' => 'Réunion d’Arbitrage Investissements',
            'modality' => 'meeting',
        ], $user);

        $decision = $service->registerDecision($capture, [
            'exchange_id' => $exchange->id,
            'title' => 'Modernisation prioritaire du banc de soudure automatisé',
            'decision_text' => 'Validation du budget de 180 000 € pour la modernisation du banc avec immobilisation programmée en semaine 42.',
            'nature' => 'budget_allocation',
            'decision_maker_type' => 'client',
            'decision_maker_name' => 'Jean-Marc Directeur Général',
            'justification' => 'Gain attendu de 12 jours sur le temps de cycle de coque.',
            'status' => 'taken',
        ], $user);

        $this->assertInstanceOf(CapturedDecision::class, $decision);
        $this->assertEquals('DEC-001', $decision->reference);
        $this->assertEquals('budget_allocation', $decision->nature);
        $this->assertEquals('client', $decision->decision_maker_type);
        $this->assertEquals('taken', $decision->status);

        // Sources linking
        $sourceProblem = $service->linkDecisionToSource($decision, [
            'source_tool' => 'psg',
            'source_type' => 'problem',
            'source_id' => $problem->id,
            'notes' => 'Résolution du problème structuré de goulot',
        ]);
        $this->assertEquals('problem', $sourceProblem->source_type);
        $this->assertEquals($problem->id, $sourceProblem->source_id);

        if ($finding) {
            $sourceFinding = $service->linkDecisionToSource($decision, [
                'source_tool' => 'd360',
                'source_type' => 'finding',
                'source_id' => $finding->id,
                'notes' => 'Preuve d’écart de cadence',
            ]);
            $this->assertEquals('finding', $sourceFinding->source_type);
        }

        // Action Commitment
        $commitment = $service->registerCommitment($decision, [
            'action' => 'Lancer l’appel d’offres pour le rétrofit des automates',
            'responsible_name' => 'Sophie Directrice Industrielle',
            'due_date' => now()->addDays(15)->format('Y-m-d'),
        ]);

        $this->assertEquals('to_do', $commitment->status);
        $this->assertEquals('Sophie Directrice Industrielle', $commitment->responsible_name);

        // Update commitment status
        $updatedCommitment = $service->updateCommitmentStatus($commitment, 'in_progress', 'Cahier des charges envoyé aux intégrateurs.');
        $this->assertEquals('in_progress', $updatedCommitment->status);
        $this->assertEquals('Cahier des charges envoyé aux intégrateurs.', $updatedCommitment->comments);
    }

    public function test_can_revise_and_cancel_decisions_with_traceability(): void
    {
        $context = $this->createCompleteMissionContext();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(LiveDecisionCaptureService::class);
        $capture = $service->prepareDecisionCapture($mission, $user);

        $decision = $service->registerDecision($capture, [
            'title' => 'Sous-traitance temporaire de l’assemblage coque',
            'decision_text' => 'Sous-traiter les tronçons 3 et 4 à un chantier partenaire.',
            'nature' => 'operational_change',
            'decision_maker_type' => 'joint',
            'status' => 'taken',
        ], $user);

        // Revise decision
        $revisedDecision = $service->reviseDecision($decision, [
            'title' => 'Sous-traitance partielle limitée au tronçon 4 uniquement',
            'decision_text' => 'Après vérification des capacités, le tronçon 3 reste en interne. Seul le tronçon 4 est confié au partenaire.',
            'justification' => 'Coût de sous-traitance trop élevé sur le tronçon 3.',
            'revision_reason' => 'Révision convenue suite au chiffrage définitif du partenaire.',
        ], $user);

        $decision->refresh();
        $this->assertEquals('revised', $decision->status);
        $this->assertEquals('DEC-001-REV', $revisedDecision->reference);
        $this->assertEquals($decision->id, $revisedDecision->previous_version_id);
        $this->assertEquals('taken', $revisedDecision->status);

        // Cancel the revised decision
        $cancelledDecision = $service->cancelDecision($revisedDecision, 'Le partenaire a décliné la prise en charge pour cause de surcharge de planning.');
        $this->assertEquals('cancelled', $cancelledDecision->status);
        $this->assertStringContainsString('Le partenaire a décliné', $cancelledDecision->revision_notes);
    }

    public function test_can_generate_consolidated_snapshot(): void
    {
        $context = $this->createCompleteMissionContext();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(LiveDecisionCaptureService::class);
        $capture = $service->prepareDecisionCapture($mission, $user);

        $decision = $service->registerDecision($capture, [
            'title' => 'Validation du plan de cadencement 2027',
            'nature' => 'strategic_alignment',
            'status' => 'taken',
        ], $user);

        $service->registerCommitment($decision, [
            'action' => 'Diffuser le nouveau calendrier aux chefs d’ateliers',
            'responsible_name' => 'Sophie Directrice Industrielle',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $snapshot = $service->generateSnapshot($capture);

        $this->assertIsArray($snapshot);
        $this->assertEquals($mission->id, $snapshot['mission_id']);
        $this->assertEquals(1, $snapshot['counts']['decisions_count']);
        $this->assertEquals(1, $snapshot['counts']['taken_decisions_count']);
        $this->assertEquals(1, $snapshot['counts']['total_commitments']);
        $this->assertCount(1, $snapshot['decisions']);
        $this->assertCount(1, $snapshot['open_commitments']);
    }

    public function test_web_routes_for_live_decision_capture(): void
    {
        $context = $this->createCompleteMissionContext();
        $mission = $context['mission'];
        $user = $context['user'];

        // GET decision capture page
        $response = $this->actingAs($user)->get(route('missions.decision-capture.show', $mission));
        $response->assertStatus(200);
        $response->assertSee('Live Decision Capture');
        $response->assertSee('Manufacture Navale');

        // POST new exchange
        $exchangeResponse = $this->actingAs($user)->post(route('missions.decision-capture.exchanges.store', $mission), [
            'title' => 'Comité de Suivi Web Test',
            'exchanged_at' => now()->format('Y-m-d'),
            'modality' => 'meeting',
            'location' => 'Salle A',
            'context' => 'Point d’avancement hebdomadaire',
        ]);
        $exchangeResponse->assertRedirect();
        $this->assertDatabaseHas('decision_exchanges', [
            'title' => 'Comité de Suivi Web Test',
        ]);

        $exchange = DecisionExchange::where('title', 'Comité de Suivi Web Test')->first();

        // POST discussion point
        $pointResponse = $this->actingAs($user)->post(route('missions.decision-capture.exchanges.points.store', [$mission, $exchange]), [
            'subject' => 'Approvisionnement tôles fortes',
            'description' => 'Point bloquant sur les nuances spécifiques d’acier.',
            'domain' => 'Achats & Approvisionnements',
        ]);
        $pointResponse->assertRedirect();
        $this->assertDatabaseHas('discussion_points', [
            'subject' => 'Approvisionnement tôles fortes',
            'exchange_id' => $exchange->id,
        ]);

        // POST decision
        $decisionResponse = $this->actingAs($user)->post(route('missions.decision-capture.decisions.store', $mission), [
            'exchange_id' => $exchange->id,
            'title' => 'Passation de commande groupée acier',
            'decision_text' => 'Validation de l’engagement ferme d’achat de 600 tonnes de tôle d’acier.',
            'nature' => 'arbitrage',
            'decision_maker_type' => 'client',
            'decision_maker_name' => 'Jean-Marc Directeur Général',
            'decided_at' => now()->format('Y-m-d'),
            'justification' => 'Éviter la rupture d’approvisionnement au quatrième trimestre.',
        ]);
        $decisionResponse->assertRedirect();
        $this->assertDatabaseHas('captured_decisions', [
            'title' => 'Passation de commande groupée acier',
            'reference' => 'DEC-001',
        ]);

        $decision = CapturedDecision::where('reference', 'DEC-001')->first();

        // POST commitment
        $commitmentResponse = $this->actingAs($user)->post(route('missions.decision-capture.decisions.commitments.store', [$mission, $decision]), [
            'action' => 'Émettre le bon de commande signé',
            'responsible_name' => 'Service Achats',
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ]);
        $commitmentResponse->assertRedirect();
        $this->assertDatabaseHas('decision_commitments', [
            'action' => 'Émettre le bon de commande signé',
            'decision_id' => $decision->id,
        ]);

        // POST revise decision
        $reviseResponse = $this->actingAs($user)->post(route('missions.decision-capture.decisions.revise', [$mission, $decision]), [
            'title' => 'Passation de commande groupée acier révisée à 450 tonnes',
            'decision_text' => 'Ajustement de la commande à 450 tonnes suite à révision des cadences.',
            'nature' => 'budget_allocation',
            'decision_maker_type' => 'client',
            'revision_reason' => 'Ajustement de volume pour préserver la trésorerie.',
        ]);
        $reviseResponse->assertRedirect();
        $this->assertDatabaseHas('captured_decisions', [
            'id' => $decision->id,
            'status' => 'revised',
        ]);
        $this->assertDatabaseHas('captured_decisions', [
            'reference' => 'DEC-001-REV',
            'previous_version_id' => $decision->id,
            'status' => 'taken',
        ]);
    }
}
