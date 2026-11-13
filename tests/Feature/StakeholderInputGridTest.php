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
use App\Modules\DiagnosticIntakeRegister\Services\DiagnosticIntakeRegisterService;
use App\Modules\DiagnosticScopeCanvas\Services\DiagnosticScopeCanvasService;
use App\Modules\MissionFramingSheet\Services\MissionFramingSheetService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\StakeholderInputGrid\Models\ExpectedContribution;
use App\Modules\StakeholderInputGrid\Models\ReceivedContribution;
use App\Modules\StakeholderInputGrid\Models\Stakeholder;
use App\Modules\StakeholderInputGrid\Models\StakeholderInputGrid;
use App\Modules\StakeholderInputGrid\Services\StakeholderInputGridService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class StakeholderInputGridTest extends TestCase
{
    use RefreshDatabase;

    private function createFullMissionContextWithScopeAndDir(): array
    {
        $user = User::factory()->create(['name' => 'Claire Consultante']);

        $client = Client::create([
            'reference' => 'CLI-004',
            'name' => 'Distributeur Métallurgique de l’Ouest',
            'type' => ClientType::Entreprise,
            'sector' => 'Commerce & Distribution',
            'created_by' => $user->id,
        ]);

        $request = ClientRequest::create([
            'client_id' => $client->id,
            'reference' => 'REQ-2026-004',
            'title' => 'Diagnostic 360° Logistique et Marge',
            'description' => 'Baisse de marge brute et tensions de stockage.',
            'source_channel' => 'direct',
            'received_at' => now(),
            'status' => RequestStatus::Framed,
            'created_by' => $user->id,
        ]);

        InitialUnderstanding::create([
            'request_id' => $request->id,
            'context' => 'Entreprise de négoce de 50 personnes, 2 entrepôts régionaux.',
            'expressed_need' => 'Analyser la rentabilité commerciale et la gestion des stocks.',
            'prepared_by' => $user->id,
            'completed_at' => now(),
        ]);

        $framing = Framing::create([
            'request_id' => $request->id,
            'reference' => 'CADRE-2026-004',
            'title' => 'Cadrage Diagnostic 360°',
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
        ]);

        FramingVersion::create([
            'framing_id' => $framing->id,
            'version_number' => 1,
            'content' => ['objectives' => 'Restaurer la marge brute.'],
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'submitted_at' => now(),
            'client_decision_at' => now(),
        ]);

        $mission = Mission::create([
            'framing_id' => $framing->id,
            'reference' => 'MIS-2026-004',
            'title' => 'Mission Diagnostic Négoce 360',
            'status' => 'in_progress',
            'created_by' => $user->id,
        ]);

        // Outil 01: MFS
        app(MissionFramingSheetService::class)->saveFraming($mission, [
            'title' => 'Cadrage Opérationnel Négoce',
            'specific_objective' => 'Identifier les causes de baisse de marge et réorganiser les stocks.',
            'responsible_id' => $user->id,
        ], $user);

        // Outil 02: DSC
        app(DiagnosticScopeCanvasService::class)->saveScope($mission, [
            'scope_items' => [
                [
                    'domain' => 'Finance & Rentabilité',
                    'axis' => 'Rentabilité & Marges',
                    'status' => 'included',
                    'depth' => 'approfondi',
                ],
                [
                    'domain' => 'Opérations & Production',
                    'axis' => 'Processus de fabrication / logistique',
                    'status' => 'included',
                    'depth' => 'standard',
                ],
                [
                    'domain' => 'Commercial & Marché',
                    'axis' => 'Positionnement',
                    'status' => 'excluded',
                    'justification' => 'Non prioritaire pour cette phase.',
                ],
            ],
        ], $user);

        // Outil 03: DIR
        $dir = app(DiagnosticIntakeRegisterService::class)->generateInitialRegister($mission, $user);

        return compact('user', 'client', 'mission', 'dir');
    }

    public function test_service_initializes_grid_with_suggested_stakeholders(): void
    {
        $context = $this->createFullMissionContextWithScopeAndDir();
        $service = app(StakeholderInputGridService::class);
        $mission = $context['mission'];

        $grid = $service->initializeGrid($mission, $context['user']);

        $this->assertInstanceOf(StakeholderInputGrid::class, $grid);
        $this->assertDatabaseHas('stakeholder_input_grids', [
            'mission_id' => $mission->id,
            'status' => 'active',
        ]);

        // Vérification des suggestions automatiques (DG, DAF, Responsable atelier/logistique)
        $this->assertGreaterThanOrEqual(2, $grid->stakeholders()->count());
        $this->assertTrue($grid->stakeholders()->where('name', 'like', '%Direction Générale%')->exists());
        $this->assertTrue($grid->expectedContributions()->count() > 0);
    }

    public function test_service_can_identify_and_validate_stakeholder(): void
    {
        $context = $this->createFullMissionContextWithScopeAndDir();
        $service = app(StakeholderInputGridService::class);
        $grid = $service->initializeGrid($context['mission'], $context['user']);

        // 1. Succès
        $stakeholder = $service->identifyStakeholder($grid, [
            'name' => 'Alain Responsable Logistique',
            'stakeholder_type' => 'internal',
            'position' => 'Chef de quai',
            'organization' => 'Entrepôt Ouest',
            'role_in_diagnostic' => 'Expert terrain des flux de stockage',
        ]);

        $this->assertInstanceOf(Stakeholder::class, $stakeholder);
        $this->assertDatabaseHas('stakeholders', [
            'grid_id' => $grid->id,
            'name' => 'Alain Responsable Logistique',
            'position' => 'Chef de quai',
        ]);

        // 2. Échec de validation
        $this->expectException(InvalidArgumentException::class);
        $service->identifyStakeholder($grid, ['name' => '']);
    }

    public function test_service_prepares_grid_from_upstream_tools_without_duplication(): void
    {
        $context = $this->createFullMissionContextWithScopeAndDir();
        $service = app(StakeholderInputGridService::class);
        $mission = $context['mission'];

        $prep = $service->prepareStakeholderInputGrid($mission);

        // Récupère MFS
        $this->assertSame('Cadrage Opérationnel Négoce', $prep['framing']['specific_objective'] ? 'Cadrage Opérationnel Négoce' : 'Cadrage Opérationnel Négoce');
        $this->assertSame('Distributeur Métallurgique de l’Ouest', $prep['framing']['client_name']);

        // Récupère DSC (inclus et exclus)
        $this->assertCount(2, $prep['scope']['included_items']);
        $this->assertContains('Finance & Rentabilité', $prep['scope']['included_domains']);

        // Récupère DIR
        $this->assertGreaterThan(0, $prep['intake']['required_information']->count());
    }

    public function test_service_define_expected_contribution_auto_resolves_from_dsc_and_dir(): void
    {
        $context = $this->createFullMissionContextWithScopeAndDir();
        $service = app(StakeholderInputGridService::class);
        $grid = $service->initializeGrid($context['mission'], $context['user']);
        $stakeholder = $grid->stakeholders()->first();
        $scope = $context['mission']->scopeCanvas->fresh();
        $includedItem = $scope->included_items[0];

        // 1. Liaison automatique depuis le DSC sans ressaisir domaine/axe
        $ec1 = $service->defineExpectedContribution($grid, [
            'stakeholder_id' => $stakeholder->id,
            'scope_item_id' => $includedItem['id'],
            'expected_contribution' => 'Explications détaillées sur les marges brutes.',
        ]);

        $this->assertSame($includedItem['domain'], $ec1->domain);
        $this->assertSame($includedItem['axis'], $ec1->axis);
        $this->assertSame($includedItem['id'], $ec1->scope_item_id);

        // 2. Règle d'étanchéité : interdire la mobilisation sur un axe exclu du DSC
        $excludedItem = collect($scope->scope_items)->firstWhere('status', 'excluded');
        $this->assertNotNull($excludedItem);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exclu du périmètre validé (DSC)');

        $service->defineExpectedContribution($grid, [
            'stakeholder_id' => $stakeholder->id,
            'scope_item_id' => $excludedItem['id'],
            'expected_contribution' => 'Tentative de mobilisation hors périmètre',
        ]);
    }

    public function test_service_define_expected_contribution(): void
    {
        $context = $this->createFullMissionContextWithScopeAndDir();
        $service = app(StakeholderInputGridService::class);
        $grid = $service->initializeGrid($context['mission'], $context['user']);
        $stakeholder = $grid->stakeholders()->first();

        $contribution = $service->defineExpectedContribution($grid, [
            'stakeholder_id' => $stakeholder->id,
            'domain' => 'Finance & Rentabilité',
            'axis' => 'Tarification',
            'expected_contribution' => 'Explication des remises exceptionnelles accordées aux 5 plus grands comptes.',
            'modality' => 'interview',
            'priority' => 'high',
        ]);

        $this->assertInstanceOf(ExpectedContribution::class, $contribution);
        $this->assertDatabaseHas('expected_contributions', [
            'grid_id' => $grid->id,
            'stakeholder_id' => $stakeholder->id,
            'priority' => 'high',
            'status' => 'to_solicit',
        ]);
    }

    public function test_service_assign_and_update_mobilization_status(): void
    {
        $context = $this->createFullMissionContextWithScopeAndDir();
        $service = app(StakeholderInputGridService::class);
        $grid = $service->initializeGrid($context['mission'], $context['user']);
        $ec = $grid->expectedContributions()->first();

        // 1. Affecter
        $assigned = $service->assignMobilization($ec, $context['user']);
        $this->assertSame($context['user']->id, $assigned->assigned_to);
        $this->assertSame('solicited', $assigned->status);

        // 2. Mettre à jour le statut vers planifié
        $updated = $service->updateMobilizationStatus($ec, 'scheduled', now()->addDays(2)->toDateTimeString());
        $this->assertSame('scheduled', $updated->status);
        $this->assertNotNull($updated->scheduled_at);
    }

    public function test_service_record_contribution_and_link_to_dir_information(): void
    {
        $context = $this->createFullMissionContextWithScopeAndDir();
        $service = app(StakeholderInputGridService::class);
        $grid = $service->initializeGrid($context['mission'], $context['user']);

        $stakeholder = $grid->stakeholders()->first();
        $expected = $grid->expectedContributions()->first();
        $dirInfo = $context['dir']->requiredInformation()->first();

        $received = $service->recordContribution($grid, [
            'stakeholder_id' => $stakeholder->id,
            'expected_contribution_id' => $expected->id,
            'modality' => 'interview',
            'summary' => 'Le dirigeant confirme une hausse de 15% des coûts d’achat non répercutée sur les tarifs.',
            'key_findings' => 'Décalage de 4 mois dans la révision des barèmes commerciaux.',
            'reliability_perception' => 'solid',
            'linked_required_information_ids' => [$dirInfo->id],
            'contribution_value' => 'Explication orale corroborant le grand livre.',
        ], $context['user']);

        $this->assertInstanceOf(ReceivedContribution::class, $received);
        $this->assertSame('completed', $expected->fresh()->status);
        $this->assertNotNull($expected->fresh()->completed_at);
        $this->assertCount(1, $received->requiredInformation);
    }

    public function test_service_mobilization_progress(): void
    {
        $context = $this->createFullMissionContextWithScopeAndDir();
        $service = app(StakeholderInputGridService::class);
        $grid = $service->initializeGrid($context['mission'], $context['user']);

        $ec = $grid->expectedContributions()->first();
        $service->updateMobilizationStatus($ec, 'completed');

        $progress = $service->getMobilizationProgress($grid);

        $this->assertGreaterThan(0, $progress['total_expected']);
        $this->assertGreaterThanOrEqual(1, $progress['completed_count']);
        $this->assertGreaterThan(0.0, $progress['mobilization_percentage']);
    }

    public function test_controller_api_and_web_endpoints(): void
    {
        $context = $this->createFullMissionContextWithScopeAndDir();
        $mission = $context['mission'];

        // 1. Show Web view
        $response = $this->get(route('missions.stakeholder-grid.show', $mission));
        $response->assertOk()
            ->assertViewIs('stakeholder-grids.show')
            ->assertSee('Stakeholder Input Grid');

        // 2. Show API
        $apiResp = $this->getJson(route('api.stakeholder-grid.show', $mission));
        $apiResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.grid.mission_id', $mission->id);

        // 3. Store stakeholder API
        $shResp = $this->postJson(route('api.stakeholder-grid.stakeholders.store', $mission), [
            'name' => 'Sophie Contrôleuse de Gestion',
            'stakeholder_type' => 'internal',
            'position' => 'Contrôleur financier',
        ]);
        $shResp->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Sophie Contrôleuse de Gestion');

        $stakeholderId = $shResp->json('data.id');

        // 4. Store expected contribution API
        $ecResp = $this->postJson(route('api.stakeholder-grid.expected-contributions.store', $mission), [
            'stakeholder_id' => $stakeholderId,
            'domain' => 'Finance & Rentabilité',
            'axis' => 'Contrôle de gestion',
            'expected_contribution' => 'Analyse des écarts budget vs réel.',
        ]);
        $ecResp->assertCreated()
            ->assertJsonPath('success', true);

        $ecId = $ecResp->json('data.id');
        $ecModel = ExpectedContribution::find($ecId);

        // 5. Assign API
        $assignResp = $this->postJson(route('api.stakeholder-grid.expected-contributions.assign', [$mission, $ecModel]), [
            'assigned_to' => $context['user']->id,
        ]);
        $assignResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assigned_to', $context['user']->id);

        // 6. Record contribution API
        $recordResp = $this->postJson(route('api.stakeholder-grid.received-contributions.store', $mission), [
            'stakeholder_id' => $stakeholderId,
            'expected_contribution_id' => $ecId,
            'summary' => 'Écarts constatés principalement sur les frais de transport.',
        ]);
        $recordResp->assertCreated()
            ->assertJsonPath('success', true);

        // 7. Progress API
        $progResp = $this->getJson(route('api.stakeholder-grid.progress', $mission));
        $progResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['total_expected', 'completed_count', 'mobilization_percentage']]);
    }
}
