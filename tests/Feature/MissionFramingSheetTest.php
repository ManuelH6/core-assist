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
use App\Modules\MissionFramingSheet\Models\MissionFramingSheet;
use App\Modules\MissionFramingSheet\Services\MissionFramingSheetService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MissionFramingSheetTest extends TestCase
{
    use RefreshDatabase;

    private function createFullMissionContext(): array
    {
        $user = User::factory()->create(['name' => 'Jean Consultant']);

        $client = Client::create([
            'reference' => 'CLI-001',
            'name' => 'Boulangerie Patisserie Saint-Honore',
            'type' => ClientType::Entreprise,
            'sector' => 'Artisanat & Commerce',
            'created_by' => $user->id,
        ]);

        $request = ClientRequest::create([
            'client_id' => $client->id,
            'reference' => 'REQ-2026-001',
            'title' => 'Diagnostic de rentabilité et réorganisation',
            'description' => 'Baisse de marge constatée sur les produits de panification.',
            'source_channel' => 'direct',
            'received_at' => now(),
            'status' => RequestStatus::Framed,
            'created_by' => $user->id,
        ]);

        $understanding = InitialUnderstanding::create([
            'request_id' => $request->id,
            'context' => 'Entreprise familiale de 15 salariés en zone semi-urbaine.',
            'expressed_need' => 'Comprendre pourquoi la rentabilité nette diminue malgré un CA constant.',
            'reformulated_need' => 'Dérive des coûts matières et surcoûts énergétiques non répercutés.',
            'central_question' => 'Quels sont les leviers de marge immédiatement activables ?',
            'prepared_by' => $user->id,
            'completed_at' => now(),
        ]);

        $framing = Framing::create([
            'request_id' => $request->id,
            'reference' => 'CADRE-2026-001',
            'title' => 'Cadrage global Diagnostic 360°',
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
        ]);

        $framingVersion = FramingVersion::create([
            'framing_id' => $framing->id,
            'version_number' => 1,
            'content' => [
                'objectives' => 'Restaurer un taux de marge brute supérieur à 65% sous 6 mois.',
                'deliverables' => 'Rapport 360°, plan de redressement, grille de suivi de marge.',
                'conditions' => 'Accès aux états comptables et aux fiches techniques de production.',
            ],
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'submitted_at' => now(),
            'client_decision_at' => now(),
        ]);

        $mission = Mission::create([
            'framing_id' => $framing->id,
            'reference' => 'MIS-2026-001',
            'title' => 'Diagnostic 360° - Phase 1',
            'objective' => 'Analyser en profondeur les 4 axes fondamentaux de rentabilité.',
            'status' => 'in_progress',
            'created_by' => $user->id,
            'start_date' => now()->toDateString(),
            'due_date' => now()->addMonths(2)->toDateString(),
        ]);

        return compact('user', 'client', 'request', 'understanding', 'framing', 'framingVersion', 'mission');
    }

    public function test_service_can_extract_inherited_initial_data(): void
    {
        $context = $this->createFullMissionContext();
        $service = app(MissionFramingSheetService::class);

        $data = $service->getInitialData($context['mission']);

        $this->assertSame('Boulangerie Patisserie Saint-Honore', $data['client']['name']);
        $this->assertSame('Artisanat & Commerce', $data['client']['sector']);
        $this->assertSame('Entreprise familiale de 15 salariés en zone semi-urbaine.', $data['organization_context']);
        $this->assertSame('Comprendre pourquoi la rentabilité nette diminue malgré un CA constant.', $data['expressed_need']);
        $this->assertSame('Quels sont les leviers de marge immédiatement activables ?', $data['identified_problem']);
        $this->assertSame('Restaurer un taux de marge brute supérieur à 65% sous 6 mois.', $data['general_objectives']);
        $this->assertSame('Rapport 360°, plan de redressement, grille de suivi de marge.', $data['expected_results']);
        $this->assertSame('Accès aux états comptables et aux fiches techniques de production.', $data['agreed_conditions']);
    }

    public function test_service_prepares_framing_with_provenance_and_defaults(): void
    {
        $context = $this->createFullMissionContext();
        $service = app(MissionFramingSheetService::class);

        $prepared = $service->prepareFraming($context['mission']);

        $this->assertSame($context['mission']->id, $prepared['mission_id']);
        $this->assertSame('Diagnostic 360° - Phase 1', $prepared['specific_data']['title']);
        $this->assertSame('Analyser en profondeur les 4 axes fondamentaux de rentabilité.', $prepared['specific_data']['specific_objective']);

        // Vérification de la traçabilité initiale
        $provenance = $prepared['field_provenance'];
        $this->assertArrayHasKey('organization_context', $provenance);
        $this->assertSame('inherited', $provenance['organization_context']['origin']);
        $this->assertFalse($provenance['organization_context']['is_modified']);

        $this->assertArrayHasKey('specific_objective', $provenance);
        $this->assertSame('added', $provenance['specific_objective']['origin']);
    }

    public function test_service_validation_requires_mandatory_fields(): void
    {
        $service = app(MissionFramingSheetService::class);

        $this->expectException(ValidationException::class);
        $service->validateFraming([
            'title' => '',
            'specific_objective' => '',
        ]);
    }

    public function test_service_can_save_framing_sheet_with_provenance_and_snapshots(): void
    {
        $context = $this->createFullMissionContext();
        $service = app(MissionFramingSheetService::class);

        $payload = [
            'title' => 'Diagnostic Opérationnel Restructuré',
            'specific_objective' => 'Audit des coûts de revient et rentabilité par ligne de pains.',
            'expected_results' => 'Matrice des marges par produit et plan de redressement.',
            'responsible_id' => $context['user']->id,
            'planned_start_date' => now()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'execution_conditions' => 'Présence sur site les mardis et jeudis matin.',
            'operational_constraints' => 'Ne pas perturber les fournées matinales (4h-8h).',
            'additional_observations' => 'Direction générale très demandeuse de résultats rapides.',
            'inherited_data' => [
                'organization_context' => 'Entreprise familiale de 15 salariés avec boutique rénovée.', // Modifié !
            ],
        ];

        $sheet = $service->saveFraming($context['mission'], $payload, $context['user']);

        $this->assertInstanceOf(MissionFramingSheet::class, $sheet);
        $this->assertDatabaseHas('mission_framing_sheets', [
            'mission_id' => $context['mission']->id,
            'title' => 'Diagnostic Opérationnel Restructuré',
            'status' => 'draft',
        ]);

        // Vérification de la traçabilité des modifications sur l'hérité
        $provenance = $sheet->field_provenance;
        $this->assertTrue($provenance['organization_context']['is_modified']);
        $this->assertSame('Entreprise familiale de 15 salariés en zone semi-urbaine.', $provenance['organization_context']['original_value']);
        $this->assertSame('Entreprise familiale de 15 salariés avec boutique rénovée.', $provenance['organization_context']['current_value']);
        $this->assertNotNull($provenance['organization_context']['modified_at']);

        // Vérification des snapshots
        $this->assertIsArray($sheet->input_snapshot);
        $this->assertIsArray($sheet->output_snapshot);
        $this->assertSame('Diagnostic Opérationnel Restructuré', $sheet->output_snapshot['mission']['title']);
        $this->assertSame('Boulangerie Patisserie Saint-Honore', $sheet->output_snapshot['client']['name']);
        $this->assertSame('Audit des coûts de revient et rentabilité par ligne de pains.', $sheet->output_snapshot['objectives']['specific_objective']);
    }

    public function test_service_can_submit_and_validate_review(): void
    {
        $context = $this->createFullMissionContext();
        $service = app(MissionFramingSheetService::class);
        $dgUser = User::factory()->create(['name' => 'Directeur Général']);

        $sheet = $service->saveFraming($context['mission'], [
            'title' => 'Mission Cadre',
            'specific_objective' => 'Objectif opérationnel défini.',
        ], $context['user']);

        $this->assertSame('draft', $sheet->status);

        // Soumission
        $sheet = $service->submitForReview($sheet, $context['user']);
        $this->assertSame('submitted', $sheet->status);
        $this->assertNotNull($sheet->submitted_at);

        // Validation
        $sheet = $service->validateReview($sheet, $dgUser);
        $this->assertSame('validated', $sheet->status);
        $this->assertNotNull($sheet->validated_at);
        $this->assertSame($dgUser->id, $sheet->validated_by);
    }

    public function test_controller_api_endpoints(): void
    {
        $context = $this->createFullMissionContext();
        $mission = $context['mission'];

        // 1. Initial data
        $response = $this->getJson(route('api.framing-sheet.initial-data', $mission));
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.inherited_data.client.name', 'Boulangerie Patisserie Saint-Honore');

        // 2. Store framing sheet
        $postData = [
            'title' => 'Fiche cadrage API',
            'specific_objective' => 'Objectif défini via API.',
            'responsible_id' => $context['user']->id,
        ];
        $response = $this->postJson(route('api.framing-sheet.store', $mission), $postData);
        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Fiche cadrage API');

        // 3. Show framing sheet
        $response = $this->getJson(route('api.framing-sheet.show', $mission));
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Fiche cadrage API');

        // 4. Update framing sheet
        $updateData = [
            'title' => 'Fiche cadrage API Mise à Jour',
            'specific_objective' => 'Objectif défini via API mis à jour.',
        ];
        $response = $this->putJson(route('api.framing-sheet.update', $mission), $updateData);
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Fiche cadrage API Mise à Jour');

        // 5. Submit for review
        $response = $this->postJson(route('api.framing-sheet.submit', $mission));
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'submitted');
    }

    public function test_controller_renders_web_views(): void
    {
        $context = $this->createFullMissionContext();
        $mission = $context['mission'];

        // 1. Vue de création / saisie
        $response = $this->get(route('missions.framing-sheet.initial-data', $mission));
        $response->assertOk()
            ->assertViewIs('framing-sheets.create')
            ->assertSee('Mission Framing Sheet')
            ->assertSee('Boulangerie Patisserie Saint-Honore');

        // 2. Enregistrement en web
        $postResponse = $this->post(route('missions.framing-sheet.store', $mission), [
            'title' => 'Cadrage Web Test',
            'specific_objective' => 'Objectif test web',
        ]);
        $postResponse->assertRedirect(route('missions.framing-sheet.show', $mission));

        // 3. Vue de consultation
        $showResponse = $this->get(route('missions.framing-sheet.show', $mission));
        $showResponse->assertOk()
            ->assertViewIs('framing-sheets.show')
            ->assertSee('Cadrage Web Test')
            ->assertSee('Objectif test web');
    }
}
