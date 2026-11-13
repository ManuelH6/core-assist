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
use App\Modules\DiagnosticIntakeRegister\Models\DiagnosticIntakeRegister;
use App\Modules\DiagnosticIntakeRegister\Models\ReceivedItem;
use App\Modules\DiagnosticIntakeRegister\Models\RequiredInformation;
use App\Modules\DiagnosticIntakeRegister\Services\DiagnosticIntakeRegisterService;
use App\Modules\DiagnosticScopeCanvas\Services\DiagnosticScopeCanvasService;
use App\Modules\MissionFramingSheet\Services\MissionFramingSheetService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DiagnosticIntakeRegisterTest extends TestCase
{
    use RefreshDatabase;

    private function createFullMissionContextWithScope(): array
    {
        $user = User::factory()->create(['name' => 'Marc Consultant']);

        $client = Client::create([
            'reference' => 'CLI-003',
            'name' => 'Industries Agroalimentaires du Centre',
            'type' => ClientType::Entreprise,
            'sector' => 'Agroalimentaire',
            'created_by' => $user->id,
        ]);

        $request = ClientRequest::create([
            'client_id' => $client->id,
            'reference' => 'REQ-2026-003',
            'title' => 'Diagnostic 360° Agroalimentaire',
            'description' => 'Évaluation complète des processus et rentabilité.',
            'source_channel' => 'direct',
            'received_at' => now(),
            'status' => RequestStatus::Framed,
            'created_by' => $user->id,
        ]);

        InitialUnderstanding::create([
            'request_id' => $request->id,
            'context' => 'PME de transformation de fruits, 30 salariés.',
            'expressed_need' => 'Améliorer les marges et moderniser le contrôle de gestion.',
            'prepared_by' => $user->id,
            'completed_at' => now(),
        ]);

        $framing = Framing::create([
            'request_id' => $request->id,
            'reference' => 'CADRE-2026-003',
            'title' => 'Cadrage Diagnostic 360°',
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
        ]);

        FramingVersion::create([
            'framing_id' => $framing->id,
            'version_number' => 1,
            'content' => ['objectives' => 'Restructurer les marges.'],
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'submitted_at' => now(),
            'client_decision_at' => now(),
        ]);

        $mission = Mission::create([
            'framing_id' => $framing->id,
            'reference' => 'MIS-2026-003',
            'title' => 'Mission Diagnostic Agro 360',
            'status' => 'in_progress',
            'created_by' => $user->id,
        ]);

        // Outil 01: MFS
        app(MissionFramingSheetService::class)->saveFraming($mission, [
            'title' => 'Cadrage Opérationnel Agro',
            'specific_objective' => 'Identifier les pertes de matières premières et optimiser les coûts.',
            'responsible_id' => $user->id,
        ], $user);

        // Outil 02: DSC avec Finance et Opérations inclus
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
                    'axis' => 'Processus de fabrication',
                    'status' => 'included',
                    'depth' => 'standard',
                ],
                [
                    'domain' => 'Commercial & Marché',
                    'axis' => 'Positionnement',
                    'status' => 'excluded',
                    'justification' => 'Non prioritaire.',
                ],
            ],
        ], $user);

        return compact('user', 'client', 'mission');
    }

    public function test_service_generates_initial_register_from_dsc_scope(): void
    {
        $context = $this->createFullMissionContextWithScope();
        $service = app(DiagnosticIntakeRegisterService::class);
        $mission = $context['mission'];

        $register = $service->generateInitialRegister($mission, $context['user']);

        $this->assertInstanceOf(DiagnosticIntakeRegister::class, $register);
        $this->assertDatabaseHas('diagnostic_intake_registers', [
            'mission_id' => $mission->id,
            'status' => 'active',
        ]);

        // Vérification de la création automatique des besoins d'informations pour les axes inclus
        $this->assertGreaterThan(0, $register->requiredInformation()->count());
        $this->assertTrue(
            $register->requiredInformation()->where('domain', 'Finance & Rentabilité')->exists()
        );
        $this->assertTrue(
            $register->requiredInformation()->where('domain', 'Opérations & Production')->exists()
        );
    }

    public function test_service_can_add_and_validate_required_information(): void
    {
        $context = $this->createFullMissionContextWithScope();
        $service = app(DiagnosticIntakeRegisterService::class);
        $register = $service->generateInitialRegister($context['mission'], $context['user']);

        // 1. Succès
        $info = $service->addRequiredInformation($register, [
            'domain' => 'Finance & Rentabilité',
            'axis' => 'Rentabilité',
            'information_name' => 'Fiches techniques de coût des confitures',
            'expected_source' => 'Atelier cuisine',
            'priority' => 'high',
        ]);

        $this->assertInstanceOf(RequiredInformation::class, $info);
        $this->assertDatabaseHas('required_information', [
            'register_id' => $register->id,
            'information_name' => 'Fiches techniques de coût des confitures',
            'priority' => 'high',
        ]);

        // 2. Échec de validation
        $this->expectException(InvalidArgumentException::class);
        $service->addRequiredInformation($register, [
            'domain' => '',
            'axis' => '',
            'information_name' => '',
        ]);
    }

    public function test_service_assign_responsible_updates_status(): void
    {
        $context = $this->createFullMissionContextWithScope();
        $service = app(DiagnosticIntakeRegisterService::class);
        $register = $service->generateInitialRegister($context['mission'], $context['user']);

        $info = $register->requiredInformation()->first();
        $this->assertSame('pending', $info->status);

        $assigned = $service->assignResponsible($info, $context['user']);
        $this->assertSame($context['user']->id, $assigned->assigned_to);
        $this->assertSame('in_progress', $assigned->status);
    }

    public function test_service_register_received_item_and_link_to_information(): void
    {
        $context = $this->createFullMissionContextWithScope();
        $service = app(DiagnosticIntakeRegisterService::class);
        $register = $service->generateInitialRegister($context['mission'], $context['user']);

        $info1 = $register->requiredInformation()->first();
        $info2 = $register->requiredInformation()->skip(1)->first();

        // Enregistrer une pièce reçue et lier aux 2 informations
        $item = $service->registerReceivedItem($register, [
            'title' => 'Grand Livre 2025 & Balances Analytiques.xlsx',
            'item_type' => 'export_file',
            'source_origin' => 'Service Comptable',
            'linked_information_ids' => [$info1->id, $info2->id],
            'coverage_degree' => 'full',
            'notes' => 'Fichier certifié complet.',
        ], $context['user']);

        $this->assertInstanceOf(ReceivedItem::class, $item);
        $this->assertCount(2, $item->requiredInformation);

        // Vérification de la transition automatique de statut
        $this->assertSame('received', $info1->fresh()->status);
        $this->assertSame('received', $info2->fresh()->status);
        $this->assertNotNull($info1->fresh()->received_at);
    }

    public function test_service_update_collection_status_and_verification(): void
    {
        $context = $this->createFullMissionContextWithScope();
        $service = app(DiagnosticIntakeRegisterService::class);
        $register = $service->generateInitialRegister($context['mission'], $context['user']);

        $info = $register->requiredInformation()->first();
        $dg = User::factory()->create(['name' => 'Directeur de Mission']);

        $updated = $service->updateCollectionStatus($info, 'verified', 'verified_valid', $dg);

        $this->assertSame('verified', $updated->status);
        $this->assertSame('verified_valid', $updated->verification_status);
        $this->assertNotNull($updated->verified_at);
        $this->assertSame($dg->id, $updated->verified_by);
    }

    public function test_service_progress_calculation(): void
    {
        $context = $this->createFullMissionContextWithScope();
        $service = app(DiagnosticIntakeRegisterService::class);
        $register = $service->generateInitialRegister($context['mission'], $context['user']);

        // Marquer la moitié des éléments reçus
        $items = $register->requiredInformation()->take(2)->get();
        foreach ($items as $item) {
            $service->updateCollectionStatus($item, 'received');
        }

        $progress = $service->getCollectionProgress($register);

        $this->assertGreaterThan(0, $progress['total_items']);
        $this->assertGreaterThanOrEqual(2, $progress['received_count']);
        $this->assertGreaterThan(0.0, $progress['progress_percentage']);
        $this->assertArrayHasKey('by_domain', $progress);
    }

    public function test_controller_api_and_web_endpoints(): void
    {
        $context = $this->createFullMissionContextWithScope();
        $mission = $context['mission'];

        // 1. Show Web view (auto-initializes register)
        $response = $this->get(route('missions.intake-register.show', $mission));
        $response->assertOk()
            ->assertViewIs('intake-registers.show')
            ->assertSee('Diagnostic Intake Register');

        // 2. Show JSON API
        $apiResponse = $this->getJson(route('api.intake-register.show', $mission));
        $apiResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.register.mission_id', $mission->id);

        $info = RequiredInformation::first();

        // 3. Assign responsible via API
        $assignResp = $this->postJson(route('api.intake-register.information.assign', [$mission, $info]), [
            'assigned_to' => $context['user']->id,
        ]);
        $assignResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assigned_to', $context['user']->id);

        // 4. Update status via API
        $statusResp = $this->postJson(route('api.intake-register.information.status', [$mission, $info]), [
            'status' => 'received',
        ]);
        $statusResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'received');

        // 5. Store received item via API
        $docResp = $this->postJson(route('api.intake-register.received-items.store', $mission), [
            'title' => 'Rapport Qualité Usine 2025.pdf',
            'item_type' => 'document',
            'linked_information_ids' => [$info->id],
        ]);
        $docResp->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Rapport Qualité Usine 2025.pdf');

        // 6. Progress API
        $progressResp = $this->getJson(route('api.intake-register.progress', $mission));
        $progressResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['total_items', 'received_count', 'progress_percentage']]);
    }
}
