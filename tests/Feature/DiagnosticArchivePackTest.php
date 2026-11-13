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
use App\Modules\DiagnosticArchivePack\Models\ArchiveCategory;
use App\Modules\DiagnosticArchivePack\Models\ArchiveItem;
use App\Modules\DiagnosticArchivePack\Models\ArchiveVersion;
use App\Modules\DiagnosticArchivePack\Models\DiagnosticArchivePack;
use App\Modules\DiagnosticArchivePack\Services\DiagnosticArchivePackService;
use App\Modules\DiagnosticIntakeRegister\Services\DiagnosticIntakeRegisterService;
use App\Modules\DiagnosticRestitutionDeck\Services\DiagnosticRestitutionDeckService;
use App\Modules\DiagnosticScopeCanvas\Services\DiagnosticScopeCanvasService;
use App\Modules\LiveDecisionCapture\Services\LiveDecisionCaptureService;
use App\Modules\MissionFramingSheet\Services\MissionFramingSheetService;
use App\Modules\ProblemStructuringGrid\Services\ProblemStructuringGridService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\StakeholderInputGrid\Services\StakeholderInputGridService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticArchivePackTest extends TestCase
{
    use RefreshDatabase;

    private function createCompleteMissionWithAllTools(): array
    {
        $user = User::factory()->create(['name' => 'Alexandre Archiviste']);

        $client = Client::create([
            'reference' => 'CLI-009',
            'name' => 'Aéronautique & Systèmes d’Aquitaine',
            'type' => ClientType::Entreprise,
            'sector' => 'Aéronautique & Défense',
            'created_by' => $user->id,
        ]);

        $request = ClientRequest::create([
            'client_id' => $client->id,
            'reference' => 'REQ-2026-009',
            'title' => 'Diagnostic Global Compétitivité & Supply',
            'description' => 'Audit 360° des processus industriels et supply chain.',
            'source_channel' => 'direct',
            'received_at' => now(),
            'status' => RequestStatus::Framed,
            'created_by' => $user->id,
        ]);

        InitialUnderstanding::create([
            'request_id' => $request->id,
            'context' => 'Sous-traitant de rang 1 de 450 personnes.',
            'expressed_need' => 'Archiver l’ensemble de la mission diagnostique.',
            'prepared_by' => $user->id,
            'completed_at' => now(),
        ]);

        $framing = Framing::create([
            'request_id' => $request->id,
            'reference' => 'CADRE-2026-009',
            'title' => 'Cadrage Opérationnel Aéro',
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'validated_at' => now(),
        ]);

        FramingVersion::create([
            'framing_id' => $framing->id,
            'version_number' => 1,
            'content' => [
                'title' => 'Cadrage Validé - Performance Aéro',
                'context' => 'Audit global 360°',
                'objectives' => 'Mener le diagnostic et archiver les éléments justificatifs.',
            ],
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'submitted_at' => now(),
            'client_decision_at' => now(),
        ]);

        $mission = Mission::create([
            'framing_id' => $framing->id,
            'reference' => 'MIS-2026-009',
            'title' => 'Mission Diagnostic Compétitivité Aéro',
            'objective' => 'Délivrer un diagnostic étayé et un dossier d’archives complet.',
            'status' => 'in_progress',
        ]);

        // 1. MFS
        app(MissionFramingSheetService::class)->saveFraming($mission, [
            'title' => 'Cadrage Opérationnel Aéro',
            'specific_objective' => 'Formaliser et archiver le diagnostic complet.',
            'responsible_id' => $user->id,
        ], $user);

        // 2. DSC
        app(DiagnosticScopeCanvasService::class)->saveScope($mission, [
            'scope_items' => [
                [
                    'domain' => 'Opérations & Usinage',
                    'axis' => 'Usinage pièces titane',
                    'depth' => 'approfondi',
                    'status' => 'included',
                    'justification' => 'Taux de rebut élevé',
                ],
                [
                    'domain' => 'Achats & Fournisseurs',
                    'axis' => 'Qualité des billettes forgées',
                    'depth' => 'standard',
                    'status' => 'included',
                    'justification' => 'Non-conformités récurrentes',
                ],
            ],
        ], $user);

        // 3. DIR
        $dirService = app(DiagnosticIntakeRegisterService::class);
        $dir = $dirService->generateInitialRegister($mission, $user);
        $reqInfo = $dir->requiredInformation()->first();

        $dirService->registerReceivedItem($dir, [
            'title' => 'Fichier Rebuts Titane T1-2026.xlsx',
            'file_name' => 'rebuts_titane.xlsx',
            'item_type' => 'document',
            'reliability_score' => 'reliable',
            'source_origin' => 'Direction Qualité',
            'file_path' => 'uploads/missions/rebuts_titane.xlsx',
            'linked_information_ids' => $reqInfo ? [$reqInfo->id] : [],
        ], $user);

        // 4. SIG
        $sigService = app(StakeholderInputGridService::class);
        $sig = $sigService->initializeGrid($mission, $user);
        $stakeholder = $sigService->identifyStakeholder($sig, [
            'name' => 'Bernard Directeur Industriel',
            'role_title' => 'Directeur Industriel',
            'organization_entity' => 'Usine Aquitaine',
            'category' => 'internal_management',
        ]);

        // 5. D360
        $d360Service = app(Diagnostic360MatrixService::class);
        $matrix = $d360Service->prepareDiagnosticMatrix($mission, $user);
        $assessment = $matrix->assessments()->first();
        if ($assessment) {
            $d360Service->identifyDifficulty($assessment, [
                'title' => 'Usure anormale des outils d’usinage titane',
                'description' => 'Fréquence de remplacement des fraises 40% supérieure aux abaques.',
                'severity_or_importance' => 'critical',
                'is_validated' => true,
            ], $user);
        }

        // 6. PSG
        $psgService = app(ProblemStructuringGridService::class);
        $psg = $psgService->prepareProblemStructuringGrid($mission, $user);

        // 7. DRD
        $drdService = app(DiagnosticRestitutionDeckService::class);
        $drd = $drdService->prepareRestitutionDeck($mission, $user);

        // 8. LDC
        $ldcService = app(LiveDecisionCaptureService::class);
        $ldc = $ldcService->prepareDecisionCapture($mission, $user);
        $decision = $ldcService->registerDecision($ldc, [
            'title' => 'Changement de fournisseur de nuances carbure',
            'decision_text' => 'Validation du basculement vers des outils avec revêtement DLC haute résistance.',
            'nature' => 'arbitrage',
            'decision_maker_type' => 'client',
            'decision_maker_name' => 'Bernard Directeur Industriel',
            'status' => 'taken',
        ], $user);

        return compact('user', 'client', 'mission', 'dir', 'sig', 'matrix', 'psg', 'drd', 'ldc', 'decision');
    }

    public function test_service_initializes_archive_pack_and_default_categories(): void
    {
        $context = $this->createCompleteMissionWithAllTools();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(DiagnosticArchivePackService::class);
        $pack = $service->initializeArchive($mission, $user);

        $this->assertInstanceOf(DiagnosticArchivePack::class, $pack);
        $this->assertEquals($mission->id, $pack->mission_id);
        $this->assertEquals('in_progress', $pack->status);
        $this->assertEquals('DAP-MIS-2026-009', $pack->reference);
        $this->assertNotEmpty($pack->input_snapshot);

        // Vérification des 9 catégories système
        $categories = ArchiveCategory::all();
        $this->assertCount(9, $categories);
        $this->assertTrue($categories->contains('code', 'cadrage'));
        $this->assertTrue($categories->contains('code', 'analyses'));
        $this->assertTrue($categories->contains('code', 'decisions'));
    }

    public function test_service_auto_collects_elements_from_eight_upstream_tools(): void
    {
        $context = $this->createCompleteMissionWithAllTools();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(DiagnosticArchivePackService::class);
        $pack = $service->initializeArchive($mission, $user);

        $results = $service->collectAvailableElements($pack, $user);

        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, $results['mfs']);
        $this->assertGreaterThanOrEqual(1, $results['dsc']);
        $this->assertGreaterThanOrEqual(1, $results['dir']);
        $this->assertGreaterThanOrEqual(1, $results['sig']);
        $this->assertGreaterThanOrEqual(1, $results['d360']);
        $this->assertGreaterThanOrEqual(1, $results['psg']);
        $this->assertGreaterThanOrEqual(1, $results['drd']);
        $this->assertGreaterThanOrEqual(1, $results['ldc']);

        // Vérification des éléments archivés
        $items = $pack->items()->get();
        $this->assertGreaterThanOrEqual(8, $items->count());

        // Chaque item doit posséder sa version initiale v1.0
        foreach ($items as $item) {
            $this->assertCount(1, $item->versions);
            $this->assertEquals(1, $item->current_version_number);
            $this->assertNotEmpty($item->reference);
        }

        // Test d'idempotence : une seconde exécution ne duplique pas les éléments
        $secondRun = $service->collectAvailableElements($pack, $user);
        $this->assertEquals(0, array_sum($secondRun));
        $this->assertEquals($items->count(), $pack->items()->count());
    }

    public function test_service_registers_manual_archive_item_and_version_history(): void
    {
        $context = $this->createCompleteMissionWithAllTools();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(DiagnosticArchivePackService::class);
        $pack = $service->initializeArchive($mission, $user);

        $catAnalyse = ArchiveCategory::where('code', 'analyses')->first();
        $catDocs = ArchiveCategory::where('code', 'documents_complementaires')->first();

        // Création d'un élément manuel avec multi-catégorisation
        $item = $service->registerArchiveItem($pack, [
            'title' => 'Rapport métallurgique micrographie éprouvettes titane',
            'category_id' => $catAnalyse->id,
            'categories' => [$catAnalyse->id, $catDocs->id],
            'item_type' => 'document',
            'location_type' => 'file_path',
            'location_path' => 'archives/labo/micrographie_titane.pdf',
            'description' => 'Examen externe du laboratoire démontrant une porosité sous couche.',
        ], $user);

        $this->assertInstanceOf(ArchiveItem::class, $item);
        $this->assertEquals('ARC-001', $item->reference);
        $this->assertEquals(1, $item->current_version_number);
        $this->assertCount(2, $item->categories);

        // Ajout d'une nouvelle version sans écraser l'ancienne
        $version2 = $service->registerVersion($item, [
            'version_tag' => 'v2.0-complété',
            'location_path' => 'archives/labo/micrographie_titane_v2.pdf',
            'changelog' => 'Ajout des conclusions spectrales complémentaires du laboratoire.',
        ], $user);

        $this->assertInstanceOf(ArchiveVersion::class, $version2);
        $this->assertEquals(2, $version2->version_number);

        $item->refresh();
        $this->assertEquals(2, $item->current_version_number);
        $this->assertCount(2, $item->versions);
        $this->assertEquals('archives/labo/micrographie_titane_v2.pdf', $item->location_path);

        // Liaison de source justificative
        $source = $service->linkSource($item, [
            'source_tool' => 'dir',
            'source_type' => 'intake_item',
            'relationship_type' => 'evidence',
            'notes' => 'Pièce fournie par le laboratoire CETIM',
        ]);

        $this->assertEquals('dir', $source->source_tool);
        $this->assertCount(1, $item->fresh()->sources);
    }

    public function test_service_search_archive_and_history(): void
    {
        $context = $this->createCompleteMissionWithAllTools();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(DiagnosticArchivePackService::class);
        $pack = $service->initializeArchive($mission, $user);
        $service->collectAvailableElements($pack, $user);

        // Recherche par mot-clé
        $searchResults = $service->searchArchive($pack, 'titane');
        $this->assertGreaterThanOrEqual(1, $searchResults->count());

        // Recherche par outil
        $ldcResults = $service->searchArchive($pack, null, null, 'ldc');
        $this->assertGreaterThanOrEqual(1, $ldcResults->count());
        $this->assertEquals('ldc', $ldcResults->first()->source_tool);

        // Récupération de l'historique
        $history = $service->getArchiveHistory($pack);
        $this->assertGreaterThanOrEqual(8, $history->count());
    }

    public function test_service_completeness_and_close_archive(): void
    {
        $context = $this->createCompleteMissionWithAllTools();
        $mission = $context['mission'];
        $user = $context['user'];

        $service = app(DiagnosticArchivePackService::class);
        $pack = $service->initializeArchive($mission, $user);
        $service->collectAvailableElements($pack, $user);

        $completeness = $service->checkArchiveCompleteness($pack);

        $this->assertEquals(100, $completeness['completeness_score']);
        $this->assertTrue($completeness['is_fully_complete']);
        $this->assertEquals(8, $completeness['covered_count']);

        // Clôture formelle
        $closed = $service->closeArchive($pack, 'Dossier archivé et vérifié par le directeur de mission.');
        $this->assertTrue($closed->isClosed());
        $this->assertNotNull($closed->closed_at);
        $this->assertNotEmpty($closed->output_snapshot);
        $this->assertEquals(100, $closed->output_snapshot['completeness_score']);
        $this->assertNotEmpty($closed->output_snapshot['inventory']);
    }

    public function test_web_routes_for_diagnostic_archive_pack(): void
    {
        $context = $this->createCompleteMissionWithAllTools();
        $mission = $context['mission'];
        $user = $context['user'];

        // GET archive pack page
        $response = $this->actingAs($user)->get(route('missions.archive-pack.show', $mission));
        $response->assertStatus(200);
        $response->assertSee('Diagnostic Archive Pack');
        $response->assertSee('Aéronautique &amp; Systèmes d’Aquitaine', false);

        // POST collect elements
        $collectResponse = $this->actingAs($user)->post(route('missions.archive-pack.collect', $mission));
        $collectResponse->assertRedirect();

        $pack = $mission->archivePack;
        $this->assertNotNull($pack);
        $this->assertGreaterThanOrEqual(8, $pack->items()->count());

        $catDocs = ArchiveCategory::where('code', 'documents_complementaires')->first();

        // POST manual item
        $itemResponse = $this->actingAs($user)->post(route('missions.archive-pack.items.store', $mission), [
            'title' => 'Procès-verbal de recette finale usinage',
            'category_id' => $catDocs->id,
            'item_type' => 'document',
            'location_type' => 'file_path',
            'location_path' => 'docs/pv_recette.pdf',
            'description' => 'Document signé par le client.',
        ]);
        $itemResponse->assertRedirect();
        $this->assertDatabaseHas('archive_items', [
            'title' => 'Procès-verbal de recette finale usinage',
            'pack_id' => $pack->id,
        ]);

        $item = ArchiveItem::where('title', 'Procès-verbal de recette finale usinage')->first();

        // POST version
        $versionResponse = $this->actingAs($user)->post(route('missions.archive-pack.items.versions.store', [$mission, $item]), [
            'version_tag' => 'v2.0-signé',
            'location_path' => 'docs/pv_recette_signe.pdf',
            'changelog' => 'Version contresignée par le client.',
        ]);
        $versionResponse->assertRedirect();
        $this->assertDatabaseHas('archive_versions', [
            'archive_item_id' => $item->id,
            'version_number' => 2,
        ]);

        // POST close pack
        $closeResponse = $this->actingAs($user)->post(route('missions.archive-pack.close', $mission), [
            'observations' => 'Contrôle qualité validé.',
        ]);
        $closeResponse->assertRedirect();
        $pack->refresh();
        $this->assertEquals('closed', $pack->status);
    }
}
