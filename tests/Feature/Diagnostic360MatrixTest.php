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
use App\Modules\Diagnostic360Matrix\Models\Diagnostic360Matrix;
use App\Modules\Diagnostic360Matrix\Services\Diagnostic360MatrixService;
use App\Modules\DiagnosticIntakeRegister\Services\DiagnosticIntakeRegisterService;
use App\Modules\DiagnosticScopeCanvas\Services\DiagnosticScopeCanvasService;
use App\Modules\MissionFramingSheet\Services\MissionFramingSheetService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\StakeholderInputGrid\Services\StakeholderInputGridService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Diagnostic360MatrixTest extends TestCase
{
    use RefreshDatabase;

    private function createCompleteDiagnosticContext(): array
    {
        $user = User::factory()->create(['name' => 'Marc Senior Consultant']);

        $client = Client::create([
            'reference' => 'CLI-005',
            'name' => 'Ateliers Mécaniques de Lorraine',
            'type' => ClientType::Entreprise,
            'sector' => 'Industrie manufacturière',
            'created_by' => $user->id,
        ]);

        $request = ClientRequest::create([
            'client_id' => $client->id,
            'reference' => 'REQ-2026-005',
            'title' => 'Diagnostic 360° Performance & Marges',
            'description' => 'Érosion continue de l’EBITDA et désorganisation logistique.',
            'source_channel' => 'direct',
            'received_at' => now(),
            'status' => RequestStatus::Framed,
            'created_by' => $user->id,
        ]);

        InitialUnderstanding::create([
            'request_id' => $request->id,
            'context' => 'PME industrielle familiale de 80 salariés, confrontation à des surcoûts matières.',
            'expressed_need' => 'Comprendre les causes de la dégradation de marge et des retards.',
            'prepared_by' => $user->id,
            'completed_at' => now(),
        ]);

        $framing = Framing::create([
            'request_id' => $request->id,
            'reference' => 'CADRE-2026-005',
            'title' => 'Cadrage Diagnostic 360°',
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'validated_at' => now(),
        ]);

        FramingVersion::create([
            'framing_id' => $framing->id,
            'version_number' => 1,
            'content' => [
                'title' => 'Cadrage Validé - Performance Industrielle',
                'context' => 'Contexte usine et finance consolidé',
                'objectives' => 'Restaurer la rentabilité et fiabiliser la supply chain.',
            ],
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'submitted_at' => now(),
            'client_decision_at' => now(),
        ]);

        $mission = Mission::create([
            'framing_id' => $framing->id,
            'reference' => 'MIS-2026-005',
            'title' => 'Diagnostic 360° - Ateliers Mécaniques',
            'objective' => 'Audit complet 360° finance, opérations et organisation.',
            'status' => 'in_progress',
        ]);

        // 1. Mission Framing Sheet (MFS)
        $mfsService = app(MissionFramingSheetService::class);
        $mfs = $mfsService->saveFraming($mission, [
            'title' => 'Cadrage Opérationnel Ateliers',
            'specific_objective' => 'Identifier les causes de la fuite de marge et cartographier les goulets.',
            'responsible_id' => $user->id,
        ], $user);

        // 2. Diagnostic Scope Canvas (DSC) avec axes inclus et un axe exclu
        $dscService = app(DiagnosticScopeCanvasService::class);
        $scope = $dscService->saveScope($mission, [
            'scope_items' => [
                [
                    'id' => 'item_fin_margin',
                    'domain' => 'Finance & Rentabilité',
                    'axis' => 'Marge Brute & Prix de Revient',
                    'subaxis' => 'Calcul des coûts matières',
                    'description' => 'Fiabilité des fiches de coût standard',
                    'status' => 'included',
                ],
                [
                    'id' => 'item_ops_flow',
                    'domain' => 'Opérations Industrielles',
                    'axis' => 'Gestion des Flux & Stocks',
                    'subaxis' => 'Goulets d’étranglement atelier',
                    'description' => 'Temps de cycle et taux de rebuts',
                    'status' => 'included',
                ],
                [
                    'id' => 'item_excluded_tax',
                    'domain' => 'Fiscalité Internationale',
                    'axis' => 'Prix de transfert transfrontaliers',
                    'subaxis' => 'Holdings étrangères',
                    'description' => 'Hors champ de la mission convenu avec la direction',
                    'status' => 'excluded',
                    'justification' => 'Hors champ de la mission convenu avec la direction générale.',
                ],
            ],
        ], $user);

        // 3. Diagnostic Intake Register (DIR) avec 1 document reçu
        $dirService = app(DiagnosticIntakeRegisterService::class);
        $dir = $dirService->generateInitialRegister($mission, $user);
        $receivedDoc = $dirService->registerReceivedItem($dir, [
            'title' => 'Grand livre analytique 2025',
            'item_type' => 'accounting_file',
            'source_origin' => 'Comptabilité générale',
            'reliability_score' => 'solid',
            'notes' => 'Fichier certifié par l’expert-comptable.',
        ], $user);

        // 4. Stakeholder Input Grid (SIG) avec 1 entretien réalisé
        $sigService = app(StakeholderInputGridService::class);
        $sig = $sigService->initializeGrid($mission, $user);
        $stakeholder = $sigService->identifyStakeholder($sig, [
            'name' => 'Jean-Luc Directeur Usine',
            'stakeholder_type' => 'internal',
            'position' => 'Directeur des Opérations',
            'role_in_diagnostic' => 'Expert process de fabrication',
        ]);
        $receivedContrib = $sigService->recordContribution($sig, [
            'stakeholder_id' => $stakeholder->id,
            'modality' => 'interview',
            'summary' => 'Confirmation d’une dérive de 15% sur les consommations d’acier et outillage.',
            'reliability_perception' => 'solid',
        ], $user);

        return compact('user', 'client', 'request', 'framing', 'mission', 'mfs', 'scope', 'dir', 'receivedDoc', 'sig', 'receivedContrib');
    }

    public function test_service_prepare_diagnostic_matrix_auto_populates_from_dsc_and_skips_excluded(): void
    {
        $context = $this->createCompleteDiagnosticContext();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(Diagnostic360MatrixService::class);
        $matrix = $service->prepareDiagnosticMatrix($mission, $user);

        $this->assertInstanceOf(Diagnostic360Matrix::class, $matrix);
        $this->assertEquals($mission->id, $matrix->mission_id);
        $this->assertNotEmpty($matrix->input_snapshot);

        // Vérification : seuls les 2 axes inclus sont instanciés, l'axe exclu est ignoré
        $assessments = $matrix->assessments()->get();
        $this->assertCount(2, $assessments);

        $axes = $assessments->pluck('axis')->all();
        $this->assertContains('Marge Brute & Prix de Revient', $axes);
        $this->assertContains('Gestion des Flux & Stocks', $axes);
        $this->assertNotContains('Prix de transfert transfrontaliers', $axes);

        // Snapshot d'entrée contient le contexte
        $this->assertEquals(2, $matrix->input_snapshot['included_axes_count']);
        $this->assertEquals(1, $matrix->input_snapshot['excluded_axes_count']);
    }

    public function test_service_register_assessment_updates_situation_and_rating(): void
    {
        $context = $this->createCompleteDiagnosticContext();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(Diagnostic360MatrixService::class);
        $matrix = $service->prepareDiagnosticMatrix($mission, $user);
        $assessment = $matrix->assessments()->first();

        $updated = $service->registerAssessment($assessment, [
            'observed_situation' => 'Les fiches de coût standard ne sont plus révisées depuis 2 ans.',
            'assessment_text' => 'Sous-estimation systématique des frais de revient sur 40% des références.',
            'rating_level' => 'vulnerable',
            'status' => 'assessed',
        ], $user);

        $this->assertEquals('vulnerable', $updated->rating_level);
        $this->assertEquals('assessed', $updated->status);
        $this->assertStringContainsString('fiches de coût standard', $updated->observed_situation);
    }

    public function test_service_link_evidence_with_dir_and_sig_sources(): void
    {
        $context = $this->createCompleteDiagnosticContext();
        $mission = $context['mission'];
        $user = $context['user'];
        $receivedDoc = $context['receivedDoc'];
        $receivedContrib = $context['receivedContrib'];

        $service = app(Diagnostic360MatrixService::class);
        $matrix = $service->prepareDiagnosticMatrix($mission, $user);
        $assessmentFin = $matrix->assessments()->where('domain', 'Finance & Rentabilité')->first();
        $assessmentOps = $matrix->assessments()->where('domain', 'Opérations Industrielles')->first();

        // 1. Lier un document DIR existant
        $evidence1 = $service->linkEvidence($assessmentFin, [
            'source_type' => 'dir_item',
            'received_item_id' => $receivedDoc->id,
            'title' => 'Élément de preuve',
            'assessed_reliability' => 'solid',
            'relevance_degree' => 'direct',
            'notes' => 'Vérification chiffrée des écarts d’incorporation',
        ], $user);

        $this->assertNotNull($evidence1->id);
        $this->assertEquals($receivedDoc->id, $evidence1->received_item_id);
        $this->assertStringContainsString('Grand livre', $evidence1->title);
        $this->assertCount(1, $assessmentFin->fresh()->evidences);

        // 2. Lier une contribution SIG existante
        $evidence2 = $service->linkEvidence($assessmentOps, [
            'source_type' => 'sig_contribution',
            'received_contribution_id' => $receivedContrib->id,
            'title' => 'Élément de preuve',
            'assessed_reliability' => 'solid',
            'relevance_degree' => 'direct',
        ], $user);

        $this->assertNotNull($evidence2->id);
        $this->assertEquals($receivedContrib->id, $evidence2->received_contribution_id);
        $this->assertStringContainsString('Jean-Luc', $evidence2->title);
        $this->assertCount(1, $assessmentOps->fresh()->evidences);
    }

    public function test_service_record_findings_strengths_difficulties_hypotheses(): void
    {
        $context = $this->createCompleteDiagnosticContext();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(Diagnostic360MatrixService::class);
        $matrix = $service->prepareDiagnosticMatrix($mission, $user);
        $assessment = $matrix->assessments()->first();

        // 1. Point fort
        $strength = $service->identifyStrength($assessment, [
            'title' => 'Savoir-faire technique reconnu sur l’usinage de précision',
            'description' => 'Fidélité client élevée sur les pièces à forte complexité.',
            'severity_or_importance' => 'major',
        ], $user);

        $this->assertEquals('strength', $strength->finding_type);
        $this->assertEquals('major', $strength->severity_or_importance);

        // 2. Difficulté
        $difficulty = $service->identifyDifficulty($assessment, [
            'title' => 'Dérive des temps de changement de série',
            'description' => 'Temps d’arrêt machine doublés par rapport au standard prévisionnel.',
            'possible_causes' => 'Manque de procédures SMED standardisées',
            'potential_consequences' => 'Retards de livraison et surcoûts d’heures supplémentaires',
            'severity_or_importance' => 'critical',
            'is_validated' => true,
        ], $user);

        $this->assertEquals('difficulty', $difficulty->finding_type);
        $this->assertEquals('critical', $difficulty->severity_or_importance);
        $this->assertTrue($difficulty->is_validated);

        // 3. Hypothèse
        $hypothesis = $service->recordHypothesis($assessment, [
            'title' => 'Sous-tarification potentielle des petites séries',
            'description' => 'Les frais fixes de réglage ne seraient pas répercutés dans le devis.',
        ], $user);

        $this->assertEquals('hypothesis', $hypothesis->finding_type);

        $this->assertCount(1, $assessment->fresh()->strengths);
        $this->assertCount(1, $assessment->fresh()->difficulties);
        $this->assertCount(1, $assessment->fresh()->hypotheses);
    }

    public function test_service_consolidate_axis_and_domain_summaries(): void
    {
        $context = $this->createCompleteDiagnosticContext();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(Diagnostic360MatrixService::class);
        $matrix = $service->prepareDiagnosticMatrix($mission, $user);

        // Synthèse d'axe
        $axisSummary = $service->consolidateAxisAssessment(
            $matrix,
            'Finance & Rentabilité',
            'Marge Brute & Prix de Revient',
            [
                'summary_text' => 'Marge en érosion de 4 points sur l’exercice écoulé.',
                'strengths_summary' => 'Bonne tenue du chiffre d’affaires global.',
                'difficulties_summary' => 'Hausse des matières non répercutée.',
            ]
        );

        $this->assertNotNull($axisSummary->id);
        $this->assertEquals('Finance & Rentabilité', $axisSummary->domain);

        // Synthèse de domaine
        $domainSummary = $service->consolidateDomainAssessment(
            $matrix,
            'Finance & Rentabilité',
            [
                'overall_rating' => 'vulnerable',
                'summary_text' => 'Le domaine financier requiert une refonte du contrôle de gestion industriel.',
                'key_takeaways' => 'Revoir d’urgence les barèmes de devis.',
            ]
        );

        $this->assertNotNull($domainSummary->id);
        $this->assertEquals('vulnerable', $domainSummary->overall_rating);
    }

    public function test_service_identify_information_gaps_flags_missing_evidences(): void
    {
        $context = $this->createCompleteDiagnosticContext();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(Diagnostic360MatrixService::class);
        $matrix = $service->prepareDiagnosticMatrix($mission, $user);

        // Initialement, aucun axe n'a de preuve rattachée
        $gaps = $service->identifyInformationGaps($matrix);
        $this->assertCount(2, $gaps);
        $this->assertEquals('absence_of_evidence', $gaps[0]['gap_type']);

        // Rattacher une preuve à l'un des axes
        $assessmentFin = $matrix->assessments()->where('domain', 'Finance & Rentabilité')->first();
        $service->linkEvidence($assessmentFin, [
            'source_type' => 'direct_observation',
            'title' => 'Observation sur poste de travail',
            'assessed_reliability' => 'solid',
        ], $user);

        $remainingGaps = $service->identifyInformationGaps($matrix);
        $this->assertCount(1, $remainingGaps);
        $this->assertEquals('Opérations Industrielles', $remainingGaps[0]['domain']);
    }

    public function test_service_generate_snapshot_captures_all_metrics(): void
    {
        $context = $this->createCompleteDiagnosticContext();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(Diagnostic360MatrixService::class);
        $matrix = $service->prepareDiagnosticMatrix($mission, $user);
        $assessment = $matrix->assessments()->first();

        $service->registerAssessment($assessment, [
            'observed_situation' => 'Analyse terminée',
            'status' => 'assessed',
        ], $user);

        $service->identifyDifficulty($assessment, [
            'title' => 'Fuite de marge critique',
            'severity_or_importance' => 'critical',
        ], $user);

        $service->consolidateDomainAssessment($matrix, $assessment->domain, [
            'overall_rating' => 'vulnerable',
            'summary_text' => 'Fragilité confirmée',
        ]);

        $snapshot = $service->generateSnapshot($matrix);

        $this->assertIsArray($snapshot);
        $this->assertEquals($matrix->id, $snapshot['matrix_id']);
        $this->assertEquals(2, $snapshot['counts']['total_assessments']);
        $this->assertEquals(1, $snapshot['counts']['completed_assessments']);
        $this->assertEquals(1, $snapshot['counts']['difficulties_count']);
        $this->assertNotEmpty($snapshot['critical_difficulties']);
        $this->assertArrayHasKey($assessment->domain, $snapshot['domain_ratings']);
    }

    public function test_controller_show_html_view_and_json_api(): void
    {
        $context = $this->createCompleteDiagnosticContext();
        $mission = $context['mission'];
        $user = $context['user'];

        $this->actingAs($user);

        // 1. Accès Web HTML
        $response = $this->get(route('missions.diagnostic-matrix.show', $mission));
        $response->assertOk();
        $response->assertViewIs('diagnostic-matrices.show');
        $response->assertSee('360° Diagnostic Matrix™');
        $response->assertSee('Marge Brute & Prix de Revient');

        // 2. Accès JSON API
        $jsonResponse = $this->getJson(route('missions.diagnostic-matrix.show', $mission));
        $jsonResponse->assertOk();
        $jsonResponse->assertJsonPath('success', true);
        $jsonResponse->assertJsonStructure([
            'success',
            'data' => [
                'matrix',
                'assessments',
                'axis_summaries',
                'domain_summaries',
            ],
        ]);
    }

    public function test_controller_endpoints_for_assessment_evidence_finding_and_domain(): void
    {
        $context = $this->createCompleteDiagnosticContext();
        $mission = $context['mission'];
        $user = $context['user'];

        $this->actingAs($user);

        $service = app(Diagnostic360MatrixService::class);
        $matrix = $service->prepareDiagnosticMatrix($mission, $user);
        $assessment = $matrix->assessments()->first();

        // 1. Update Assessment
        $resUpdate = $this->post(route('missions.diagnostic-matrix.assessments.update', [$mission, $assessment]), [
            'observed_situation' => 'Inventaire physique incomplet lors de la clôture annuelle.',
            'assessment_text' => 'Écart d’inventaire non expliqué supérieur à 50k€.',
            'rating_level' => 'critical',
            'status' => 'assessed',
        ]);
        $resUpdate->assertRedirect();
        $this->assertEquals('critical', $assessment->fresh()->rating_level);

        // 2. Link Evidence
        $resEvidence = $this->post(route('missions.diagnostic-matrix.assessments.evidence', [$mission, $assessment]), [
            'source_type' => 'direct_observation',
            'title' => 'Relevé contradictoire de stock',
            'content_summary' => 'Comptage physique effectué le 14/01',
            'assessed_reliability' => 'solid',
            'relevance_degree' => 'direct',
        ]);
        $resEvidence->assertRedirect();
        $this->assertCount(1, $assessment->fresh()->evidences);

        // 3. Record Finding
        $resFinding = $this->post(route('missions.diagnostic-matrix.assessments.findings', [$mission, $assessment]), [
            'finding_type' => 'difficulty',
            'title' => 'Absence d’inventaires tournants réguliers',
            'description' => 'Détection tardive des pertes ou démarques inconnues',
            'severity_or_importance' => 'major',
        ]);
        $resFinding->assertRedirect();
        $this->assertCount(1, $assessment->fresh()->difficulties);

        // 4. Consolidate Domain
        $resDomain = $this->post(route('missions.diagnostic-matrix.consolidate-domain', $mission), [
            'domain' => $assessment->domain,
            'overall_rating' => 'vulnerable',
            'summary_text' => 'Procédure de contrôle interne défaillante sur le suivi des stocks.',
        ]);
        $resDomain->assertRedirect();
        $this->assertDatabaseHas('diagnostic_domain_summaries', [
            'matrix_id' => $matrix->id,
            'domain' => $assessment->domain,
            'overall_rating' => 'vulnerable',
        ]);

        // 5. Generate Snapshot
        $resSnapshot = $this->post(route('missions.diagnostic-matrix.snapshot', $mission));
        $resSnapshot->assertRedirect();
        $this->assertNotNull($matrix->fresh()->output_snapshot);
    }
}
