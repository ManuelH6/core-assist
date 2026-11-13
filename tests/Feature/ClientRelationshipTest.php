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
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_client_with_enum_type(): void
    {
        $client = Client::create([
            'name' => 'Boulangerie Moderne SARL',
            'client_type' => ClientType::Entreprise,
            'sector' => 'Agroalimentaire & Artisanat',
            'email' => 'contact@boulangerie-moderne.com',
            'phone' => '+229 97 00 00 01',
            'address' => 'Cotonou, Bénin',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('clients', [
            'name' => 'Boulangerie Moderne SARL',
            'client_type' => 'entreprise',
        ]);
        $this->assertSame(ClientType::Entreprise, $client->client_type);
        $this->assertSame('Entreprise', $client->client_type->label());
    }

    public function test_can_create_request_and_initial_understanding_for_client(): void
    {
        $user = User::factory()->create();

        $client = Client::create([
            'name' => 'Cabinet Alpha',
            'client_type' => ClientType::Entreprise,
        ]);

        $request = ClientRequest::create([
            'client_id' => $client->id,
            'reference' => 'REQ-2026-001',
            'title' => 'Diagnostic organisationnel et de rentabilité',
            'description' => 'Baisse de marge constatée sur les 6 derniers mois.',
            'source_channel' => 'rencontre',
            'received_at' => now(),
            'status' => RequestStatus::Received,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('requests', [
            'reference' => 'REQ-2026-001',
            'status' => 'received',
        ]);

        $understanding = InitialUnderstanding::create([
            'request_id' => $request->id,
            'context' => 'Entreprise en croissance avec 25 salariés.',
            'expressed_need' => 'Améliorer la rentabilité des points de vente.',
            'reformulated_need' => 'Analyser la structure des coûts de revient et le plan de staffing.',
            'expected_outcome' => 'Recommandations d\'optimisation de marge sous 3 mois.',
            'central_question' => 'Quels postes de coûts et d\'organisation génèrent la dérive de marge ?',
            'known_constraints' => ['Budget plafonné', 'Disponibilité des équipes le matin uniquement'],
            'missing_information' => ['Comptes d\'exploitation analytiques N-1'],
            'prepared_by' => $user->id,
            'completed_at' => now(),
        ]);

        $this->assertDatabaseHas('initial_understandings', [
            'request_id' => $request->id,
        ]);

        $this->assertSame($request->id, $understanding->request->id);
        $this->assertSame($understanding->id, $request->initialUnderstanding->id);
        $this->assertCount(2, $understanding->known_constraints);
    }

    public function test_can_create_framing_and_multiple_versions(): void
    {
        $user = User::factory()->create();

        $client = Client::create([
            'name' => 'Industrie Sud',
            'client_type' => ClientType::Entreprise,
        ]);

        $request = ClientRequest::create([
            'client_id' => $client->id,
            'reference' => 'REQ-2026-002',
            'title' => 'Restructuration RH & Organisation',
            'description' => 'Réorganisation complète de la direction des opérations.',
            'source_channel' => 'email',
            'received_at' => now(),
            'status' => RequestStatus::Framed,
            'created_by' => $user->id,
        ]);

        $framing = Framing::create([
            'request_id' => $request->id,
            'reference' => 'CAD-2026-001',
            'title' => 'Cadrage stratégique - Réorganisation RH',
            'status' => FramingStatus::Submitted,
            'prepared_by' => $user->id,
        ]);

        // Version 1
        $version1 = FramingVersion::create([
            'framing_id' => $framing->id,
            'version_number' => 1,
            'content' => [
                'contexte' => 'Diagnostic préliminaire de 2 semaines',
                'objectifs' => ['Cartographie des postes', 'Bilan des compétences'],
                'delais' => '4 semaines',
            ],
            'status' => FramingStatus::Submitted,
            'prepared_by' => $user->id,
            'submitted_at' => now()->subDays(2),
            'client_decision_at' => now()->subDay(),
            'client_feedback' => 'Souhaite intégrer un volet sur la gestion des conflits.',
        ]);

        // Version 2 (Acceptée par le client)
        $version2 = FramingVersion::create([
            'framing_id' => $framing->id,
            'version_number' => 2,
            'content' => [
                'contexte' => 'Diagnostic de 3 semaines avec volet climat social',
                'objectifs' => ['Cartographie des postes', 'Bilan des compétences', 'Audit climat social'],
                'delais' => '5 semaines',
            ],
            'status' => FramingStatus::Accepted,
            'prepared_by' => $user->id,
            'submitted_at' => now()->subDay(),
            'client_decision_at' => now(),
            'client_feedback' => 'Proposition validée par le Conseil de Direction.',
        ]);

        $framing->update(['status' => FramingStatus::Accepted]);

        $this->assertCount(2, $framing->versions);
        $this->assertSame(2, $framing->latestVersion->version_number);
        $this->assertSame(FramingStatus::Accepted, $framing->latestVersion->status);

        // Création de la mission rattachée au cadrage
        $mission = Mission::create([
            'framing_id' => $framing->id,
            'reference' => 'MIS-2026-001',
            'title' => 'Mission Diagnostic RH & Climat Social',
            'objective' => 'Cartographier les compétences et apaiser le climat social.',
            'status' => 'in_progress',
            'created_by' => $user->id,
            'started_at' => now(),
        ]);

        $this->assertDatabaseHas('missions', [
            'reference' => 'MIS-2026-001',
            'framing_id' => $framing->id,
        ]);

        $this->assertSame($framing->id, $mission->framing->id);
        $this->assertCount(1, $framing->missions);
    }
}
