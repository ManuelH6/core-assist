<?php

namespace App\Modules\ClientRelationship\Services;

use App\Enums\FramingStatus;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\ClientRelationship\Http\Models\Client;
use App\Modules\ClientRelationship\Http\Models\Framing;
use App\Modules\ClientRelationship\Http\Models\FramingVersion;
use App\Modules\ClientRelationship\Http\Models\InitialUnderstanding;
use App\Modules\ClientRelationship\Http\Models\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClientRelationshipService
{
    /**
     * Créer un nouveau client.
     *
     * @param  array<string, mixed>  $data
     */
    public function createClient(array $data): Client
    {
        return Client::create([
            'name' => trim((string) $data['name']),
            'client_type' => $data['client_type'],
            'sector' => $data['sector'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Créer un compte d'accès portail rattaché à un client.
     *
     * @param  array<string, mixed>  $data
     */
    public function createClientPortalUser(Client $client, array $data): User
    {
        return User::create([
            'name' => trim((string) $data['name']),
            'email' => strtolower(trim((string) $data['email'])),
            'password' => Hash::make((string) $data['password']),
            'role' => UserRole::Client,
            'client_id' => $client->id,
        ]);
    }

    /**
     * Enregistrer une demande client et créer systématiquement sa fiche de compréhension initiale.
     *
     * @param  array<string, mixed>  $data
     */
    public function submitRequest(Client $client, array $data, User $creator): ClientRequest
    {
        return DB::transaction(function () use ($client, $data, $creator) {
            $reference = $this->generateRequestReference();

            $clientRequest = ClientRequest::create([
                'client_id' => $client->id,
                'reference' => $reference,
                'title' => trim((string) $data['title']),
                'description' => trim((string) $data['description']),
                'source_channel' => (string) ($data['source_channel'] ?? 'portail_client'),
                'received_at' => $data['received_at'] ?? now(),
                'status' => RequestStatus::Received,
                'created_by' => $creator->id,
            ]);

            InitialUnderstanding::create([
                'request_id' => $clientRequest->id,
                'context' => ! empty($data['context']) ? trim((string) $data['context']) : null,
                'expressed_need' => trim((string) $data['description']),
                'expected_outcome' => ! empty($data['expected_outcome']) ? trim((string) $data['expected_outcome']) : null,
                'prepared_by' => $creator->id,
            ]);

            return $clientRequest->load(['client', 'initialUnderstanding']);
        });
    }

    /**
     * Mettre à jour la fiche de compréhension initiale d'une demande.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateInitialUnderstanding(ClientRequest $clientRequest, array $data, User $user): InitialUnderstanding
    {
        return DB::transaction(function () use ($clientRequest, $data, $user) {
            /** @var InitialUnderstanding $understanding */
            $understanding = $clientRequest->initialUnderstanding()->firstOrNew([
                'request_id' => $clientRequest->id,
            ]);

            $knownConstraints = $this->parseLines($data['known_constraints_text'] ?? $data['known_constraints'] ?? null);
            $missingInformation = $this->parseLines($data['missing_information_text'] ?? $data['missing_information'] ?? null);

            $understanding->fill([
                'context' => $data['context'] ?? $understanding->context,
                'expressed_need' => $data['expressed_need'] ?? $understanding->expressed_need ?? $clientRequest->description,
                'reformulated_need' => $data['reformulated_need'] ?? null,
                'expected_outcome' => $data['expected_outcome'] ?? null,
                'central_question' => $data['central_question'] ?? null,
                'known_constraints' => $knownConstraints,
                'missing_information' => $missingInformation,
                'initial_notes' => $data['initial_notes'] ?? null,
                'prepared_by' => $understanding->prepared_by ?? $user->id,
                'completed_at' => ! empty($data['mark_completed']) ? now() : $understanding->completed_at,
            ]);
            $understanding->save();

            if ($clientRequest->status === RequestStatus::Received) {
                $clientRequest->update(['status' => RequestStatus::BeingStudied]);
            }

            return $understanding->fresh();
        });
    }

    /**
     * Mettre à jour le statut d'une demande.
     */
    public function updateRequestStatus(ClientRequest $clientRequest, RequestStatus $status): ClientRequest
    {
        $clientRequest->update(['status' => $status]);

        return $clientRequest->fresh();
    }

    /**
     * Créer un cadrage avec une première version V1 pour une demande.
     *
     * @param  array<string, mixed>  $data
     */
    public function createFraming(ClientRequest $clientRequest, array $data, User $user): Framing
    {
        return DB::transaction(function () use ($clientRequest, $data, $user) {
            $framing = Framing::create([
                'request_id' => $clientRequest->id,
                'reference' => $this->generateFramingReference(),
                'title' => trim((string) $data['title']),
                'status' => FramingStatus::Draft,
                'prepared_by' => $user->id,
            ]);

            FramingVersion::create([
                'framing_id' => $framing->id,
                'version_number' => 1,
                'content' => $this->buildVersionContent($data),
                'status' => FramingStatus::Draft,
                'prepared_by' => $user->id,
            ]);

            if (in_array($clientRequest->status, [RequestStatus::Received, RequestStatus::BeingStudied], true)) {
                $clientRequest->update(['status' => RequestStatus::Framed]);
            }

            return $framing->load(['versions', 'latestVersion']);
        });
    }

    /**
     * Ajouter une nouvelle version révisée à un cadrage existant.
     *
     * @param  array<string, mixed>  $data
     */
    public function addFramingVersion(Framing $framing, array $data, User $user): FramingVersion
    {
        return DB::transaction(function () use ($framing, $data, $user) {
            $framing->versions()
                ->whereIn('status', [FramingStatus::Draft->value, FramingStatus::Submitted->value])
                ->update(['status' => FramingStatus::Superseded->value]);

            $nextNumber = ((int) $framing->versions()->max('version_number')) + 1;

            $version = FramingVersion::create([
                'framing_id' => $framing->id,
                'version_number' => $nextNumber,
                'content' => $this->buildVersionContent($data),
                'status' => FramingStatus::Draft,
                'prepared_by' => $user->id,
            ]);

            $framing->update(['status' => FramingStatus::Draft]);

            return $version;
        });
    }

    /**
     * Soumettre une version de cadrage au client pour examen et décision.
     */
    public function submitFramingVersion(FramingVersion $version): FramingVersion
    {
        return DB::transaction(function () use ($version) {
            $version->update([
                'status' => FramingStatus::Submitted,
                'submitted_at' => now(),
            ]);

            $version->framing->update(['status' => FramingStatus::Submitted]);
            $version->framing->request->update(['status' => RequestStatus::Framed]);

            return $version->fresh();
        });
    }

    /**
     * Enregistrer la décision du client sur une version de cadrage soumise.
     */
    public function recordClientDecision(
        FramingVersion $version,
        bool $accepted,
        ?string $feedback = null
    ): FramingVersion {
        return DB::transaction(function () use ($version, $accepted, $feedback) {
            $newStatus = $accepted ? FramingStatus::Accepted : FramingStatus::Rejected;

            $version->update([
                'status' => $newStatus,
                'client_decision_at' => now(),
                'client_feedback' => $feedback !== null ? trim($feedback) : null,
            ]);

            $version->framing->update(['status' => $newStatus]);

            return $version->fresh();
        });
    }

    private function generateRequestReference(): string
    {
        $year = now()->format('Y');
        $sequence = ClientRequest::whereYear('created_at', $year)->count() + 1;

        do {
            $reference = sprintf('REQ-%s-%03d', $year, $sequence);
            $sequence++;
        } while (ClientRequest::where('reference', $reference)->exists());

        return $reference;
    }

    private function generateFramingReference(): string
    {
        $year = now()->format('Y');
        $sequence = Framing::whereYear('created_at', $year)->count() + 1;

        do {
            $reference = sprintf('CAD-%s-%03d', $year, $sequence);
            $sequence++;
        } while (Framing::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildVersionContent(array $data): array
    {
        return [
            'objectives' => trim((string) ($data['objectives'] ?? '')),
            'scope_summary' => ! empty($data['scope_summary']) ? trim((string) $data['scope_summary']) : null,
            'deliverables' => ! empty($data['deliverables']) ? trim((string) $data['deliverables']) : null,
            'timeline' => ! empty($data['timeline']) ? trim((string) $data['timeline']) : null,
            'conditions' => ! empty($data['conditions']) ? trim((string) $data['conditions']) : null,
        ];
    }

    /**
     * @param  array<int, string>|string|null  $input
     * @return array<int, string>
     */
    private function parseLines(array|string|null $input): array
    {
        if (is_array($input)) {
            return array_values(array_filter(array_map('trim', $input), fn (string $line) => $line !== ''));
        }

        if ($input === null || trim($input) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $input) ?: [];

        return array_values(array_filter(array_map('trim', $lines), fn (string $line) => $line !== ''));
    }
}
