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
use App\Modules\DiagnosticRestitutionDeck\Models\DiagnosticRestitutionDeck;
use App\Modules\DiagnosticRestitutionDeck\Services\DiagnosticRestitutionDeckService;
use App\Modules\DiagnosticScopeCanvas\Services\DiagnosticScopeCanvasService;
use App\Modules\MissionFramingSheet\Services\MissionFramingSheetService;
use App\Modules\ProblemStructuringGrid\Services\ProblemStructuringGridService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticRestitutionDeckTest extends TestCase
{
    use RefreshDatabase;

    private function createCompleteDiagnosticContextWithPSG(): array
    {
        $user = User::factory()->create(['name' => 'Alexandre Directeur de Mission']);

        $client = Client::create([
            'reference' => 'CLI-007',
            'name' => 'Textiles & Confection de Picardie',
            'type' => ClientType::Entreprise,
            'sector' => 'Textile & Habillement',
            'created_by' => $user->id,
        ]);

        $request = ClientRequest::create([
            'client_id' => $client->id,
            'reference' => 'REQ-2026-007',
            'title' => 'Diagnostic Restitution Restructuration',
            'description' => 'Baisse de marge et surstocks saisonniers.',
            'source_channel' => 'direct',
            'received_at' => now(),
            'status' => RequestStatus::Framed,
            'created_by' => $user->id,
        ]);

        InitialUnderstanding::create([
            'request_id' => $request->id,
            'context' => 'Entreprise de 95 salariés avec confection sur commande.',
            'expressed_need' => 'Préparer le plan de restitution stratégique.',
            'prepared_by' => $user->id,
            'completed_at' => now(),
        ]);

        $framing = Framing::create([
            'request_id' => $request->id,
            'reference' => 'CADRE-2026-007',
            'title' => 'Cadrage Opérationnel Textile',
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'validated_at' => now(),
        ]);

        FramingVersion::create([
            'framing_id' => $framing->id,
            'version_number' => 1,
            'content' => [
                'title' => 'Cadrage Validé - Performance Textile',
                'context' => 'Audit organisationnel et financier',
                'objectives' => 'Restituer les conclusions stratégiques.',
            ],
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'submitted_at' => now(),
            'client_decision_at' => now(),
        ]);

        $mission = Mission::create([
            'framing_id' => $framing->id,
            'reference' => 'MIS-2026-007',
            'title' => 'Mission Restitution Textile 360°',
            'objective' => 'Délivrer une restitution probante et priorisée.',
            'status' => 'in_progress',
        ]);

        // MFS
        app(MissionFramingSheetService::class)->saveFraming($mission, [
            'title' => 'Cadrage Opérationnel Restitution',
            'specific_objective' => 'Formaliser la restitution globale du diagnostic.',
            'responsible_id' => $user->id,
        ], $user);

        // DSC
        app(DiagnosticScopeCanvasService::class)->saveScope($mission, [
            'scope_items' => [
                [
                    'id' => 'item_fin_margin',
                    'domain' => 'Finance & Rentabilité',
                    'axis' => 'Marge Brute par Collection',
                    'subaxis' => 'Analyse des remises',
                    'description' => 'Érosion du taux de marque',
                    'status' => 'included',
                ],
                [
                    'id' => 'item_ops_supply',
                    'domain' => 'Opérations & Logistique',
                    'axis' => 'Gestion des stocks saisonniers',
                    'subaxis' => 'Rotation des rouleaux',
                    'description' => 'Dormants tissus',
                    'status' => 'included',
                ],
                [
                    'id' => 'item_excluded_export',
                    'domain' => 'Export International',
                    'axis' => 'Filiales Asie',
                    'description' => 'Périmètre non concerné',
                    'status' => 'excluded',
                    'justification' => 'Hors champ convenu avec le client pour cette phase.',
                ],
            ],
        ], $user);

        // D360
        $d360Service = app(Diagnostic360MatrixService::class);
        $matrix = $d360Service->prepareDiagnosticMatrix($mission, $user);
        $assessmentFin = $matrix->assessments()->where('domain', 'Finance & Rentabilité')->first();
        $diff = $d360Service->identifyDifficulty($assessmentFin, [
            'title' => 'Pratique généralisée de remises commerciales hors barème',
            'description' => 'Perte moyenne de 6 points de marge brute sur la collection hiver.',
            'severity_or_importance' => 'critical',
            'is_validated' => true,
        ], $user);

        // PSG
        $psgService = app(ProblemStructuringGridService::class);
        $psg = $psgService->prepareProblemStructuringGrid($mission, $user);

        return compact('user', 'client', 'request', 'framing', 'mission', 'matrix', 'diff', 'psg');
    }

    public function test_service_prepare_restitution_deck_seeds_standard_eight_sections_and_distinguishes_scope(): void
    {
        $context = $this->createCompleteDiagnosticContextWithPSG();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(DiagnosticRestitutionDeckService::class);
        $deck = $service->prepareRestitutionDeck($mission, $user);

        $this->assertInstanceOf(DiagnosticRestitutionDeck::class, $deck);
        $this->assertEquals($mission->id, $deck->mission_id);
        $this->assertNotEmpty($deck->input_snapshot);

        // Vérification des 8 sections standards pré-générées
        $sections = $deck->sections()->get();
        $this->assertCount(8, $sections);

        $keys = $sections->pluck('section_key')->all();
        $this->assertContains('presentation', $keys);
        $this->assertContains('methodology', $keys);
        $this->assertContains('scope', $keys);
        $this->assertContains('general_synthesis', $keys);
        $this->assertContains('domain_analysis', $keys);
        $this->assertContains('problems', $keys);
        $this->assertContains('priorities', $keys);
        $this->assertContains('conclusion', $keys);

        // Vérification de la distinction du périmètre dans initialData
        $initialData = $service->getInitialData($mission);
        $this->assertEquals(3, $initialData['scope_distinction']['planned_total_count']);
        $this->assertEquals(2, $initialData['scope_distinction']['included_count']);
        $this->assertCount(1, $initialData['scope_distinction']['excluded_items']);
    }

    public function test_service_add_and_update_section(): void
    {
        $context = $this->createCompleteDiagnosticContextWithPSG();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(DiagnosticRestitutionDeckService::class);
        $deck = $service->prepareRestitutionDeck($mission, $user);

        // Ajouter une 9e section personnalisée
        $section = $service->addSection($deck, [
            'title' => '9. Trajectoire de déploiement et jalons à 90 jours',
            'objective' => 'Présenter les 3 premières actions immédiates',
        ]);

        $this->assertNotNull($section->id);
        $this->assertEquals(9, $section->position);

        // Mettre à jour
        $updated = $service->updateSection($section, [
            'title' => '9. Plan de transition d’urgence',
            'status' => 'drafting',
        ]);

        $this->assertEquals('9. Plan de transition d’urgence', $updated->title);
        $this->assertEquals('drafting', $updated->status);
    }

    public function test_service_reorder_and_remove_section(): void
    {
        $context = $this->createCompleteDiagnosticContextWithPSG();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(DiagnosticRestitutionDeckService::class);
        $deck = $service->prepareRestitutionDeck($mission, $user);
        $sections = $deck->sections()->get();

        // Réorganiser
        $reorderedIds = [$sections[1]->id, $sections[0]->id, ...$sections->slice(2)->pluck('id')->all()];
        $service->reorderSections($deck, $reorderedIds);

        $this->assertEquals(1, $sections[1]->fresh()->position);
        $this->assertEquals(2, $sections[0]->fresh()->position);

        // Supprimer une section
        $service->removeSection($sections[7]);
        $this->assertCount(7, $deck->fresh()->sections);
    }

    public function test_service_add_and_update_content(): void
    {
        $context = $this->createCompleteDiagnosticContextWithPSG();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(DiagnosticRestitutionDeckService::class);
        $deck = $service->prepareRestitutionDeck($mission, $user);
        $section = $deck->sections()->first();

        $content = $service->addContent($section, [
            'content_type' => 'text',
            'title' => 'Synthèse introductive',
            'body_text' => 'L’intervention s’est déroulée sur 4 semaines auprès des sites de production.',
        ], $user);

        $this->assertNotNull($content->id);
        $this->assertEquals('Synthèse introductive', $content->title);

        $updated = $service->updateContent($content, [
            'title' => 'Synthèse introductive révisée',
            'body_text' => 'Texte édité et validé.',
        ]);

        $this->assertEquals('Synthèse introductive révisée', $updated->title);
    }

    public function test_service_import_tool_result_with_source_linkage(): void
    {
        $context = $this->createCompleteDiagnosticContextWithPSG();
        $mission = $context['mission'];
        $user = $context['user'];
        $diff = $context['diff'];

        $service = app(DiagnosticRestitutionDeckService::class);
        $deck = $service->prepareRestitutionDeck($mission, $user);
        $section = $deck->sections()->where('section_key', 'domain_analysis')->first();

        $imported = $service->importToolResult(
            $section,
            'd360',
            'finding',
            $diff->id,
            [
                'content_type' => 'callout',
                'title' => 'Constat critique : '.$diff->title,
                'body_text' => 'Perte de marge avérée due aux remises non encadrées.',
                'source_summary' => 'Constat D360 validé n° '.$diff->id,
            ],
            $user
        );

        $this->assertNotNull($imported->id);
        $this->assertCount(1, $imported->sources);
        $this->assertEquals('d360', $imported->sources->first()->source_tool);
        $this->assertEquals($diff->id, $imported->sources->first()->source_id);
    }

    public function test_service_validate_section(): void
    {
        $context = $this->createCompleteDiagnosticContextWithPSG();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(DiagnosticRestitutionDeckService::class);
        $deck = $service->prepareRestitutionDeck($mission, $user);
        $section = $deck->sections()->first();

        $validated = $service->validateSection($section, $user);

        $this->assertEquals('validated', $validated->status);
        $this->assertEquals($user->id, $validated->validated_by);
        $this->assertNotNull($validated->validated_at);
    }

    public function test_service_consolidate_restitution_and_generate_versioned_snapshot(): void
    {
        $context = $this->createCompleteDiagnosticContextWithPSG();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(DiagnosticRestitutionDeckService::class);
        $deck = $service->prepareRestitutionDeck($mission, $user);

        // Valider 1 section
        $service->validateSection($deck->sections()->first(), $user);

        $result = $service->generateSnapshot($deck, 'Version 1.0 Pré-Restitution', $user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('snapshot', $result);
        $this->assertArrayHasKey('version', $result);

        $version = $result['version'];
        $this->assertEquals(1, $version->version_number);
        $this->assertEquals('Version 1.0 Pré-Restitution', $version->title);
        $this->assertNotNull($deck->fresh()->output_snapshot);
        $this->assertEquals(1, $deck->versions()->count());
    }

    public function test_controller_show_html_view_and_json_api(): void
    {
        $context = $this->createCompleteDiagnosticContextWithPSG();
        $mission = $context['mission'];
        $user = $context['user'];

        $this->actingAs($user);

        // 1. Vue Web HTML
        $response = $this->get(route('missions.restitution-deck.show', $mission));
        $response->assertOk();
        $response->assertViewIs('restitution-decks.show');
        $response->assertSee('Diagnostic Restitution Deck™');
        $response->assertSee('Présentation de la mission');

        // 2. API JSON
        $jsonRes = $this->getJson(route('missions.restitution-deck.show', $mission));
        $jsonRes->assertOk();
        $jsonRes->assertJsonPath('success', true);
        $jsonRes->assertJsonStructure([
            'success',
            'data' => [
                'deck',
                'sections',
                'versions',
                'initial_data',
            ],
        ]);
    }

    public function test_controller_endpoints_for_sections_contents_and_versions(): void
    {
        $context = $this->createCompleteDiagnosticContextWithPSG();
        $mission = $context['mission'];
        $user = $context['user'];

        $this->actingAs($user);

        $service = app(DiagnosticRestitutionDeckService::class);
        $deck = $service->prepareRestitutionDeck($mission, $user);
        $section = $deck->sections()->first();

        // 1. Store Section
        $resSec = $this->post(route('missions.restitution-deck.sections.store', $mission), [
            'title' => 'Annexe Méthodologique',
            'objective' => 'Préciser les formules de calcul du besoin en fonds de roulement',
        ]);
        $resSec->assertRedirect();
        $this->assertDatabaseHas('restitution_sections', [
            'deck_id' => $deck->id,
            'title' => 'Annexe Méthodologique',
        ]);

        // 2. Validate Section
        $resVal = $this->post(route('missions.restitution-deck.sections.validate', [$mission, $section]));
        $resVal->assertRedirect();
        $this->assertEquals('validated', $section->fresh()->status);

        // 3. Store Content
        $resCont = $this->post(route('missions.restitution-deck.sections.contents.store', [$mission, $section]), [
            'content_type' => 'callout',
            'title' => 'Signal d’alerte',
            'body_text' => 'Les délais clients se sont accrus de 18 jours.',
        ]);
        $resCont->assertRedirect();
        $this->assertDatabaseHas('restitution_contents', [
            'section_id' => $section->id,
            'title' => 'Signal d’alerte',
        ]);

        // 4. Generate Snapshot
        $resSnap = $this->post(route('missions.restitution-deck.snapshot', $mission), [
            'version_title' => 'Restitution Finale Validée Client',
        ]);
        $resSnap->assertRedirect();
        $this->assertDatabaseHas('restitution_versions', [
            'deck_id' => $deck->id,
            'title' => 'Restitution Finale Validée Client',
        ]);
    }
}
