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
use App\Modules\DiagnosticScopeCanvas\Models\DiagnosticScopeCanvas;
use App\Modules\DiagnosticScopeCanvas\Services\DiagnosticScopeCanvasService;
use App\Modules\MissionFramingSheet\Services\MissionFramingSheetService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DiagnosticScopeCanvasTest extends TestCase
{
    use RefreshDatabase;

    private function createMissionWithFramingSheet(): array
    {
        $user = User::factory()->create(['name' => 'Paul Consultant']);

        $client = Client::create([
            'reference' => 'CLI-002',
            'name' => 'Atelier Textile des Savanes',
            'type' => ClientType::Entreprise,
            'sector' => 'Industrie & Confection',
            'created_by' => $user->id,
        ]);

        $request = ClientRequest::create([
            'client_id' => $client->id,
            'reference' => 'REQ-2026-002',
            'title' => 'Diagnostic de compétitivité industrielle',
            'description' => 'Besoin d’évaluation des processus industriels et rendements d’atelier.',
            'source_channel' => 'partenaire',
            'received_at' => now(),
            'status' => RequestStatus::Framed,
            'created_by' => $user->id,
        ]);

        InitialUnderstanding::create([
            'request_id' => $request->id,
            'context' => 'Unité de confection de 45 salariés, forte hausse des rebuts.',
            'expressed_need' => 'Améliorer le rendement et réduire les pertes matières.',
            'central_question' => 'Comment restructurer la chaîne de coupe et d’assemblage ?',
            'prepared_by' => $user->id,
            'completed_at' => now(),
        ]);

        $framing = Framing::create([
            'request_id' => $request->id,
            'reference' => 'CADRE-2026-002',
            'title' => 'Cadrage Diagnostic Opérationnel & Rendement',
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
        ]);

        FramingVersion::create([
            'framing_id' => $framing->id,
            'version_number' => 1,
            'content' => [
                'objectives' => 'Diminuer le taux de rebut de 12% à moins de 4%.',
                'deliverables' => 'Cartographie des flux de fabrication, plan d’action atelier.',
            ],
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'submitted_at' => now(),
            'client_decision_at' => now(),
        ]);

        $mission = Mission::create([
            'framing_id' => $framing->id,
            'reference' => 'MIS-2026-002',
            'title' => 'Mission Diagnostic d’Atelier',
            'objective' => 'Analyser le poste de coupe, les approvisionnements et la maintenance.',
            'status' => 'in_progress',
            'created_by' => $user->id,
            'start_date' => now()->toDateString(),
            'due_date' => now()->addMonths(2)->toDateString(),
        ]);

        // Création du Mission Framing Sheet (Outil 01)
        $mfsService = app(MissionFramingSheetService::class);
        $mfsService->saveFraming($mission, [
            'title' => 'Cadrage Opérationnel Atelier',
            'specific_objective' => 'Examiner les flux matières et le rendement de l’atelier de confection.',
            'expected_results' => 'Matrice des pertes par machine et plan de réorganisation.',
            'responsible_id' => $user->id,
        ], $user);

        return compact('user', 'client', 'request', 'framing', 'mission');
    }

    public function test_service_initial_data_extracts_mfs_context(): void
    {
        $context = $this->createMissionWithFramingSheet();
        $service = app(DiagnosticScopeCanvasService::class);

        $initialData = $service->getInitialData($context['mission']);

        $this->assertSame('Atelier Textile des Savanes', $initialData['client']['name']);
        $this->assertSame('Examiner les flux matières et le rendement de l’atelier de confection.', $initialData['framing_sheet']['specific_objective']);
        $this->assertSame('Unité de confection de 45 salariés, forte hausse des rebuts.', $initialData['framing_sheet']['organization_context']);
    }

    public function test_service_scope_reference_provides_canonical_domains(): void
    {
        $service = app(DiagnosticScopeCanvasService::class);
        $reference = $service->getScopeReference();

        $this->assertArrayHasKey('finance', $reference);
        $this->assertArrayHasKey('operations', $reference);
        $this->assertArrayHasKey('commercial', $reference);
        $this->assertArrayHasKey('organization', $reference);
        $this->assertArrayHasKey('strategy', $reference);

        $this->assertArrayHasKey('profitability', $reference['finance']['axes']);
        $this->assertArrayHasKey('production_process', $reference['operations']['axes']);
    }

    public function test_service_validation_enforces_business_rules(): void
    {
        $service = app(DiagnosticScopeCanvasService::class);

        // 1. Rejette un tableau vide
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('au moins un élément');
        $service->validateScope([]);
    }

    public function test_service_validation_requires_justification_for_excluded_items(): void
    {
        $service = app(DiagnosticScopeCanvasService::class);

        $items = [
            [
                'domain' => 'Finance',
                'axis' => 'Rentabilité',
                'status' => 'included',
            ],
            [
                'domain' => 'Finance',
                'axis' => 'Investissements',
                'status' => 'excluded',
                'justification' => '', // Manquante !
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('justification explicite est obligatoire');
        $service->validateScope($items);
    }

    public function test_service_validation_requires_at_least_one_included_item(): void
    {
        $service = app(DiagnosticScopeCanvasService::class);

        $items = [
            [
                'domain' => 'Finance',
                'axis' => 'Investissements',
                'status' => 'excluded',
                'justification' => 'Hors périmètre selon accord commercial.',
            ],
            [
                'domain' => 'Commercial',
                'axis' => 'Positionnement',
                'status' => 'to_confirm',
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('au moins un élément avec le statut Inclus');
        $service->validateScope($items);
    }

    public function test_service_can_save_scope_with_snapshots(): void
    {
        $context = $this->createMissionWithFramingSheet();
        $service = app(DiagnosticScopeCanvasService::class);
        $mission = $context['mission'];

        $scopeItems = [
            [
                'domain' => 'Opérations & Production',
                'axis' => 'Processus de fabrication',
                'subaxis' => 'Flux opérationnels et goulots d’étranglement',
                'element' => 'Flux de coupe',
                'depth' => 'approfondi',
                'status' => 'included',
                'justification' => null,
                'observation' => 'Priorité absolue suite aux pertes matières.',
            ],
            [
                'domain' => 'Opérations & Production',
                'axis' => 'Achats & Approvisionnements',
                'subaxis' => 'Gestion et rotation des stocks',
                'element' => 'Stocks tissus',
                'depth' => 'standard',
                'status' => 'included',
                'justification' => null,
            ],
            [
                'domain' => 'Finance & Rentabilité',
                'axis' => 'Structure de financement',
                'subaxis' => 'Capacité d’endettement',
                'status' => 'excluded',
                'justification' => 'Le diagnostic porte exclusivement sur l’atelier opérationnel.',
            ],
            [
                'domain' => 'Organisation & RH',
                'axis' => 'Compétences',
                'subaxis' => 'Polyvalence opératrices',
                'status' => 'to_confirm',
                'observation' => 'À valider avec le directeur d’usine lors du cadrage terrain.',
            ],
        ];

        $canvas = $service->saveScope($mission, [
            'title' => 'Diagnostic Scope Canvas - Atelier Savanes',
            'scope_items' => $scopeItems,
        ], $context['user']);

        $this->assertInstanceOf(DiagnosticScopeCanvas::class, $canvas);
        $this->assertDatabaseHas('diagnostic_scope_canvases', [
            'mission_id' => $mission->id,
            'title' => 'Diagnostic Scope Canvas - Atelier Savanes',
            'status' => 'draft',
        ]);

        $this->assertCount(2, $canvas->included_items);
        $this->assertCount(1, $canvas->excluded_items);
        $this->assertCount(1, $canvas->to_confirm_items);
        $this->assertSame(['Opérations & Production'], $canvas->included_domains);

        // Snapshots vérifiés
        $this->assertIsArray($canvas->input_snapshot);
        $this->assertIsArray($canvas->output_snapshot);
        $this->assertSame(4, $canvas->output_snapshot['summary']['total_items']);
        $this->assertSame(2, $canvas->output_snapshot['summary']['included_count']);
        $this->assertSame(1, $canvas->output_snapshot['summary']['excluded_count']);
        $this->assertSame(1, $canvas->output_snapshot['summary']['to_confirm_count']);
        $this->assertSame(1, $canvas->output_snapshot['summary']['depth_breakdown']['approfondi']);
        $this->assertSame(1, $canvas->output_snapshot['summary']['depth_breakdown']['standard']);
    }

    public function test_service_workflow_transitions(): void
    {
        $context = $this->createMissionWithFramingSheet();
        $service = app(DiagnosticScopeCanvasService::class);
        $dg = User::factory()->create(['name' => 'Directeur Associé']);

        $canvas = $service->saveScope($context['mission'], [
            'scope_items' => [
                [
                    'domain' => 'Finance',
                    'axis' => 'Rentabilité',
                    'status' => 'included',
                    'depth' => 'standard',
                ],
            ],
        ], $context['user']);

        $this->assertSame('draft', $canvas->status);

        // Soumission
        $canvas = $service->submitForReview($canvas, $context['user']);
        $this->assertSame('submitted', $canvas->status);
        $this->assertNotNull($canvas->submitted_at);

        // Validation
        $canvas = $service->validateReview($canvas, $dg);
        $this->assertSame('validated', $canvas->status);
        $this->assertNotNull($canvas->validated_at);
        $this->assertSame($dg->id, $canvas->validated_by);
    }

    public function test_controller_api_and_web_endpoints(): void
    {
        $context = $this->createMissionWithFramingSheet();
        $mission = $context['mission'];

        // 1. Initial data API
        $response = $this->getJson(route('api.scope-canvas.initial-data', $mission));
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.initial_data.client.name', 'Atelier Textile des Savanes');

        // 2. Initial data Web view
        $webResp = $this->get(route('missions.scope-canvas.initial-data', $mission));
        $webResp->assertOk()
            ->assertViewIs('scope-canvases.edit')
            ->assertSee('Diagnostic Scope Canvas');

        // 3. Store API
        $payload = [
            'title' => 'Scope Canvas API Test',
            'scope_items' => [
                [
                    'domain' => 'Opérations',
                    'axis' => 'Rendement machine',
                    'status' => 'included',
                    'depth' => 'approfondi',
                ],
                [
                    'domain' => 'Finance',
                    'axis' => 'Trésorerie',
                    'status' => 'excluded',
                    'justification' => 'Non prioritaire.',
                ],
            ],
        ];

        $storeResponse = $this->postJson(route('api.scope-canvas.store', $mission), $payload);
        $storeResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Scope Canvas API Test');

        // 4. Show Web view
        $showResponse = $this->get(route('missions.scope-canvas.show', $mission));
        $showResponse->assertOk()
            ->assertViewIs('scope-canvases.show')
            ->assertSee('Scope Canvas API Test')
            ->assertSee('Rendement machine');

        // 5. Submit for review API
        $submitResponse = $this->postJson(route('api.scope-canvas.submit', $mission));
        $submitResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'submitted');

        // 6. Validate API
        $validateResponse = $this->postJson(route('api.scope-canvas.validate', $mission));
        $validateResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'validated');
    }

    public function test_prepare_scope_starts_empty_for_new_canvas_and_loads_saved_items_when_existing(): void
    {
        $context = $this->createMissionWithFramingSheet();
        $mission = $context['mission'];
        $service = app(DiagnosticScopeCanvasService::class);

        $initialPrepared = $service->prepareScope($mission);
        $this->assertSame([], $initialPrepared['scope_items']);
        $this->assertArrayHasKey('finance', $initialPrepared['reference']);

        $service->saveScope($mission, [
            'scope_items' => [
                [
                    'domain' => 'Finance & Rentabilité',
                    'axis' => 'Rentabilité & Marges',
                    'subaxis' => 'Marge brute d’exploitation',
                    'status' => 'included',
                    'depth' => 'standard',
                ],
            ],
        ], $context['user']);

        $mission->refresh();
        $existingPrepared = $service->prepareScope($mission);
        $this->assertCount(1, $existingPrepared['scope_items']);
        $this->assertSame('Marge brute d’exploitation', $existingPrepared['scope_items'][0]['subaxis']);
    }

    public function test_edit_view_renders_progressive_builder_without_duplicate_form_inputs(): void
    {
        $context = $this->createMissionWithFramingSheet();
        $mission = $context['mission'];

        $response = $this->get(route('missions.scope-canvas.initial-data', $mission));
        $response->assertOk()
            ->assertSee('Ajouter un axe au périmètre')
            ->assertSee('id="scope-items-hidden-inputs"', false)
            ->assertSee('id="builder-axis-item"', false);
    }

    public function test_web_store_persists_scope_and_redirects_to_show(): void
    {
        $context = $this->createMissionWithFramingSheet();
        $mission = $context['mission'];

        $response = $this->post(route('missions.scope-canvas.store', $mission), [
            'scope_items' => [
                [
                    'id' => '',
                    'domain' => 'Finance & Rentabilité',
                    'axis' => 'Rentabilité & Marges',
                    'subaxis' => 'Marge brute d’exploitation',
                    'element' => 'Marge brute d’exploitation',
                    'status' => 'included',
                    'depth' => 'approfondi',
                    'justification' => '',
                    'observation' => 'Priorité CFO',
                ],
            ],
        ]);

        $response->assertRedirect(route('missions.scope-canvas.show', $mission))
            ->assertSessionHas('success');

        $canvas = $mission->scopeCanvas()->first();
        $this->assertNotNull($canvas);
        $this->assertCount(1, $canvas->included_items);
        $this->assertNotEmpty($canvas->scope_items[0]['id']);
    }

    public function test_web_and_api_store_handle_invalid_scope_gracefully(): void
    {
        $context = $this->createMissionWithFramingSheet();
        $mission = $context['mission'];

        // Web : aucun élément inclus -> redirection avec erreur flash (pas de 500)
        $webResponse = $this->from(route('missions.scope-canvas.initial-data', $mission))
            ->post(route('missions.scope-canvas.store', $mission), [
                'scope_items' => [
                    [
                        'domain' => 'Finance & Rentabilité',
                        'axis' => 'Trésorerie & BFR',
                        'status' => 'to_confirm',
                    ],
                ],
            ]);

        $webResponse->assertRedirect(route('missions.scope-canvas.initial-data', $mission))
            ->assertSessionHas('error', 'Le périmètre doit comporter au moins un élément avec le statut Inclus.');

        // API : aucun élément inclus -> 422 JSON
        $apiResponse = $this->postJson(route('api.scope-canvas.store', $mission), [
            'scope_items' => [
                [
                    'domain' => 'Finance & Rentabilité',
                    'axis' => 'Trésorerie & BFR',
                    'status' => 'to_confirm',
                ],
            ],
        ]);

        $apiResponse->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Le périmètre doit comporter au moins un élément avec le statut Inclus.');
    }
}
