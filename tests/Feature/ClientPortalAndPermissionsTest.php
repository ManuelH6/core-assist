<?php

namespace Tests\Feature;

use App\Enums\ClientType;
use App\Enums\FramingStatus;
use App\Enums\MissionRole;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\ClientRelationship\Http\Models\Client;
use App\Modules\ClientRelationship\Http\Models\Framing;
use App\Modules\ClientRelationship\Http\Models\FramingVersion;
use App\Modules\ClientRelationship\Http\Models\Request as ClientRequest;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientPortalAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_client_to_portal_and_cabinet_to_workspace(): void
    {
        $client = Client::create([
            'name' => 'Entreprise Test',
            'client_type' => ClientType::Entreprise,
        ]);

        $clientUser = User::factory()->create([
            'email' => 'client@test.local',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Client,
            'client_id' => $client->id,
        ]);

        $managerUser = User::factory()->create([
            'email' => 'manager@test.local',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Manager,
        ]);

        // Connexion client -> Portail client
        $this->post(route('login.attempt'), [
            'email' => 'client@test.local',
            'password' => 'secret123',
        ])->assertRedirect(route('client-portal.index'));

        $this->assertAuthenticatedAs($clientUser);

        // Déconnexion
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        // Connexion manager -> Demandes
        $this->post(route('login.attempt'), [
            'email' => 'manager@test.local',
            'password' => 'secret123',
        ])->assertRedirect(route('requests.index'));

        $this->assertAuthenticatedAs($managerUser);
    }

    public function test_client_can_submit_request_from_portal_and_cannot_access_other_client_or_cabinet(): void
    {
        $clientA = Client::create(['name' => 'Client A', 'client_type' => ClientType::Entreprise]);
        $clientB = Client::create(['name' => 'Client B', 'client_type' => ClientType::Entreprise]);

        $userA = User::factory()->create([
            'role' => UserRole::Client,
            'client_id' => $clientA->id,
        ]);

        $userB = User::factory()->create([
            'role' => UserRole::Client,
            'client_id' => $clientB->id,
        ]);

        $this->actingAs($userA);

        $response = $this->post(route('client-portal.requests.store'), [
            'title' => 'Audit des achats et stocks',
            'description' => 'Nous observons des ruptures fréquentes sur nos références principales.',
            'context' => 'Entrepôt central de 12 salariés.',
            'expected_outcome' => 'Réduire les ruptures de 50%.',
        ]);

        $createdRequest = ClientRequest::first();
        $this->assertNotNull($createdRequest);
        $response->assertRedirect(route('client-portal.requests.show', $createdRequest));

        $this->assertSame($clientA->id, $createdRequest->client_id);
        $this->assertSame('portail_client', $createdRequest->source_channel);
        $this->assertSame(RequestStatus::Received, $createdRequest->status);
        $this->assertNotNull($createdRequest->initialUnderstanding);

        // Isolation entre clients
        $this->actingAs($userB)
            ->get(route('client-portal.requests.show', $createdRequest))
            ->assertForbidden();

        // Client bloqué des écrans cabinet
        $this->actingAs($userA)
            ->get(route('requests.index'))
            ->assertForbidden();

        $this->actingAs($userA)
            ->get(route('missions.index'))
            ->assertForbidden();
    }

    public function test_client_can_accept_submitted_framing_then_cabinet_creates_mission_with_configuration(): void
    {
        $this->seed(DatabaseSeeder::class);

        $client = Client::where('name', 'Boulangerie Moderne SARL')->firstOrFail();
        $clientUser = User::where('email', 'client.boulangerie@coreassist.test')->firstOrFail();
        $manager = User::where('email', 'manager@coreassist.test')->firstOrFail();
        $consultant = User::where('email', 'consultant@coreassist.test')->firstOrFail();

        $clientRequest = ClientRequest::where('reference', 'REQ-2026-102')->firstOrFail();
        $framing = Framing::where('request_id', $clientRequest->id)->firstOrFail();
        $version = FramingVersion::where('framing_id', $framing->id)->firstOrFail();

        // Le client accepte la proposition depuis son portail
        $this->actingAs($clientUser)
            ->post(route('client-portal.requests.framings.decide', [$clientRequest, $version]), [
                'decision' => 'accept',
                'client_feedback' => 'Validé pour démarrage immédiat.',
            ])
            ->assertRedirect(route('client-portal.requests.show', $clientRequest));

        $this->assertSame(FramingStatus::Accepted, $framing->fresh()->status);

        // Le manager crée la mission depuis le cadrage accepté -> redirection vers configuration
        $storeResp = $this->actingAs($manager)
            ->post(route('missions.store'), [
                'framing_id' => $framing->id,
                'title' => 'Mission BFR Boulangerie Moderne',
                'description' => 'Optimisation du cycle fournisseurs.',
                'objective' => 'Réduire le BFR de 20%.',
                'status' => 'in_progress',
            ]);

        $mission = Mission::where('title', 'Mission BFR Boulangerie Moderne')->firstOrFail();
        $storeResp->assertRedirect(route('missions.configuration.edit', $mission));
        $this->assertSame(RequestStatus::Converted, $clientRequest->fresh()->status);

        // Le manager configure uniquement MFS + DSC (DSC en lecture seule pour consultant), et désactive DRD
        $this->actingAs($manager)
            ->put(route('missions.configuration.update', $mission), [
                'members' => [
                    $manager->id => ['user_id' => $manager->id, 'selected' => '1', 'mission_role' => MissionRole::Manager->value],
                    $consultant->id => ['user_id' => $consultant->id, 'selected' => '1', 'mission_role' => MissionRole::Consultant->value],
                ],
                'tools' => [
                    'D360-MFS' => [
                        'enabled' => '1',
                        'allowed_roles' => ['manager', 'consultant'],
                        'permissions' => [
                            'view' => ['manager', 'consultant'],
                            'edit' => ['manager'],
                        ],
                    ],
                    'D360-DSC' => [
                        'enabled' => '1',
                        'allowed_roles' => ['manager', 'consultant'],
                        'permissions' => [
                            'view' => ['manager', 'consultant'],
                            'edit' => ['manager'],
                            'submit' => ['manager'],
                            'validate' => ['manager'],
                        ],
                    ],
                    'D360-DRD' => ['enabled' => '0'],
                ],
            ])
            ->assertRedirect(route('missions.show', $mission));

        // Le consultant voit la mission sans DRD, et est bloqué en édition sur le DSC (403)
        $this->actingAs($consultant)
            ->get(route('missions.show', $mission))
            ->assertOk()
            ->assertSee('Périmètre d’analyse')
            ->assertDontSee('Restitution Client');

        $this->actingAs($consultant)
            ->get(route('missions.scope-canvas.initial-data', $mission))
            ->assertForbidden();

        $this->actingAs($consultant)
            ->post(route('missions.scope-canvas.store', $mission), [
                'scope_items' => [
                    [
                        'domain' => 'Finance & Rentabilité',
                        'axis' => 'Trésorerie & BFR',
                        'status' => 'included',
                    ],
                ],
            ])
            ->assertForbidden();

        // Le consultant ne peut pas ouvrir l'écran de configuration réservé au DG/Manager
        $this->actingAs($consultant)
            ->get(route('missions.configuration.edit', $mission))
            ->assertForbidden();
    }
}
