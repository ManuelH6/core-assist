<?php

namespace App\Modules\MissionFramingSheet\Services;

use App\Enums\FramingStatus;
use App\Models\User;
use App\Modules\MissionFramingSheet\Models\MissionFramingSheet;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MissionFramingSheetService
{
    /**
     * Récupérer les informations héritées issues de la demande, du cadrage et du client.
     *
     * @return array<string, mixed>
     */
    public function getInitialData(Mission $mission): array
    {
        $mission->loadMissing([
            'framing.request.client',
            'framing.request.initialUnderstanding',
            'framing.versions',
        ]);

        $framing = $mission->framing;
        $request = $framing?->request;
        $client = $request?->client;
        $understanding = $request?->initialUnderstanding;

        // Version validée prioritaire, sinon dernière version disponible
        $validatedVersion = $framing?->versions
            ?->where('status', FramingStatus::Accepted)
            ->sortByDesc('version_number')
            ->first() ?? $framing?->versions?->sortByDesc('version_number')->first();

        $framingContent = is_array($validatedVersion?->content) ? $validatedVersion->content : [];

        return [
            'client' => [
                'id' => $client?->id,
                'name' => $client?->name,
                'type' => $client?->type?->value ?? $client?->type,
                'sector' => $client?->sector,
            ],
            'organization_context' => $understanding?->context,
            'expressed_need' => $understanding?->expressed_need,
            'identified_problem' => $understanding?->central_question ?? $understanding?->reformulated_need,
            'general_objectives' => $framingContent['objectives'] ?? $framingContent['general_objectives'] ?? $framing?->title,
            'expected_results' => $framingContent['deliverables'] ?? $framingContent['expected_results'] ?? null,
            'agreed_conditions' => $framingContent['conditions'] ?? $framingContent['modalities'] ?? null,
        ];
    }

    /**
     * Construire les données initiales de la fiche en assemblant héritage et spécificités de mission.
     *
     * @param  array<string, mixed>  $customData
     * @return array<string, mixed>
     */
    public function prepareFraming(Mission $mission, array $customData = []): array
    {
        $inherited = $this->getInitialData($mission);

        $specific = [
            'title' => $customData['title'] ?? $mission->title,
            'specific_objective' => $customData['specific_objective'] ?? $mission->objective,
            'expected_results' => $customData['expected_results'] ?? null,
            'responsible_id' => $customData['responsible_id'] ?? $mission->created_by,
            'mission_leader_id' => $customData['mission_leader_id'] ?? null,
            'planned_start_date' => $customData['planned_start_date'] ?? $mission->start_date?->format('Y-m-d'),
            'planned_end_date' => $customData['planned_end_date'] ?? $mission->due_date?->format('Y-m-d'),
            'execution_conditions' => $customData['execution_conditions'] ?? null,
            'operational_constraints' => $customData['operational_constraints'] ?? null,
            'additional_observations' => $customData['additional_observations'] ?? null,
        ];

        // Traçabilité initiale de la provenance
        $provenance = [];

        foreach ($inherited as $key => $value) {
            $provenance[$key] = [
                'field' => $key,
                'origin' => 'inherited',
                'source' => $key === 'client' ? 'client_file' : ($key === 'organization_context' || $key === 'expressed_need' || $key === 'identified_problem' ? 'initial_understanding' : 'validated_framing'),
                'original_value' => $value,
                'current_value' => $value,
                'is_modified' => false,
                'modified_at' => null,
            ];
        }

        foreach ($specific as $key => $value) {
            $provenance[$key] = [
                'field' => $key,
                'origin' => 'added',
                'source' => 'mission',
                'original_value' => null,
                'current_value' => $value,
                'is_modified' => false,
                'modified_at' => null,
            ];
        }

        return [
            'mission_id' => $mission->id,
            'inherited_data' => $inherited,
            'specific_data' => $specific,
            'field_provenance' => $provenance,
        ];
    }

    /**
     * Valider les informations obligatoires de la fiche opérationnelle.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateFraming(array $data): array
    {
        return Validator::make($data, [
            'title' => ['required', 'string', 'max:255'],
            'specific_objective' => ['required', 'string'],
            'expected_results' => ['nullable', 'string'],
            'responsible_id' => ['nullable', 'integer', 'exists:users,id'],
            'mission_leader_id' => ['nullable', 'integer', 'exists:users,id'],
            'planned_start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'execution_conditions' => ['nullable', 'string'],
            'operational_constraints' => ['nullable', 'string'],
            'additional_observations' => ['nullable', 'string'],
            'inherited_data' => ['nullable', 'array'],
        ])->validate();
    }

    /**
     * Enregistrer ou mettre à jour la fiche opérationnelle avec traçabilité et snapshots.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveFraming(Mission $mission, array $data, ?User $user = null): MissionFramingSheet
    {
        $validated = $this->validateFraming($data);
        $initialData = $this->getInitialData($mission);

        /** @var MissionFramingSheet $sheet */
        $sheet = $mission->framingSheet()->firstOrNew(['mission_id' => $mission->id]);

        $customInherited = $validated['inherited_data'] ?? [];
        $currentInheritedData = array_merge($initialData, $customInherited);

        // Mise à jour de la traçabilité de provenance
        $provenance = $sheet->field_provenance ?? [];

        foreach ($initialData as $key => $originalValue) {
            $submittedValue = $customInherited[$key] ?? $originalValue;
            $isModified = ($submittedValue !== $originalValue);

            $provenance[$key] = [
                'field' => $key,
                'origin' => 'inherited',
                'source' => $key === 'client' ? 'client_file' : ($key === 'organization_context' || $key === 'expressed_need' || $key === 'identified_problem' ? 'initial_understanding' : 'validated_framing'),
                'original_value' => $originalValue,
                'current_value' => $submittedValue,
                'is_modified' => $isModified,
                'modified_at' => $isModified ? now()->toIso8601String() : ($provenance[$key]['modified_at'] ?? null),
            ];
        }

        foreach ([
            'title', 'specific_objective', 'expected_results', 'responsible_id',
            'mission_leader_id', 'planned_start_date', 'planned_end_date',
            'execution_conditions', 'operational_constraints', 'additional_observations',
        ] as $field) {
            $value = $validated[$field] ?? null;
            $provenance[$field] = [
                'field' => $field,
                'origin' => 'added',
                'source' => 'mission',
                'original_value' => $provenance[$field]['original_value'] ?? null,
                'current_value' => $value,
                'is_modified' => isset($provenance[$field]),
                'modified_at' => now()->toIso8601String(),
            ];
        }

        $inputSnapshot = [
            'initial_data' => $initialData,
            'submitted_payload' => $validated,
            'prepared_at' => now()->toIso8601String(),
            'prepared_by' => $user?->id,
        ];

        $sheet->fill([
            'title' => $validated['title'],
            'specific_objective' => $validated['specific_objective'],
            'expected_results' => $validated['expected_results'] ?? null,
            'responsible_id' => $validated['responsible_id'] ?? null,
            'mission_leader_id' => $validated['mission_leader_id'] ?? null,
            'planned_start_date' => $validated['planned_start_date'] ?? null,
            'planned_end_date' => $validated['planned_end_date'] ?? null,
            'execution_conditions' => $validated['execution_conditions'] ?? null,
            'operational_constraints' => $validated['operational_constraints'] ?? null,
            'additional_observations' => $validated['additional_observations'] ?? null,
            'inherited_data' => $currentInheritedData,
            'field_provenance' => $provenance,
            'input_snapshot' => $inputSnapshot,
            'created_by' => $sheet->created_by ?? $user?->id,
        ]);

        $sheet->output_snapshot = $this->generateSnapshot($sheet);
        $sheet->save();

        return $sheet->fresh(['responsible', 'missionLeader', 'creator', 'validator']);
    }

    /**
     * Produire une photographie des données de référence de la mission exploitable par les autres outils.
     *
     * @return array<string, mixed>
     */
    public function generateSnapshot(MissionFramingSheet $sheet): array
    {
        $mission = $sheet->mission;

        return [
            'sheet_id' => $sheet->id,
            'mission' => [
                'id' => $mission?->id ?? $sheet->mission_id,
                'reference' => $mission?->reference,
                'title' => $sheet->title,
            ],
            'client' => $sheet->inherited_data['client'] ?? null,
            'context' => [
                'organization_context' => $sheet->inherited_data['organization_context'] ?? null,
                'expressed_need' => $sheet->inherited_data['expressed_need'] ?? null,
                'identified_problem' => $sheet->inherited_data['identified_problem'] ?? null,
            ],
            'objectives' => [
                'general_objectives' => $sheet->inherited_data['general_objectives'] ?? null,
                'specific_objective' => $sheet->specific_objective,
                'expected_results' => $sheet->expected_results,
            ],
            'team' => [
                'responsible' => $sheet->responsible ? [
                    'id' => $sheet->responsible->id,
                    'name' => $sheet->responsible->name,
                ] : null,
                'mission_leader' => $sheet->missionLeader ? [
                    'id' => $sheet->missionLeader->id,
                    'name' => $sheet->missionLeader->name,
                ] : null,
            ],
            'schedule' => [
                'planned_start_date' => $sheet->planned_start_date?->format('Y-m-d'),
                'planned_end_date' => $sheet->planned_end_date?->format('Y-m-d'),
            ],
            'operational_conditions' => [
                'execution_conditions' => $sheet->execution_conditions,
                'operational_constraints' => $sheet->operational_constraints,
                'additional_observations' => $sheet->additional_observations,
            ],
            'status' => $sheet->status,
            'snapshot_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Récupérer la fiche enregistrée d'une mission.
     */
    public function getFraming(Mission $mission): ?MissionFramingSheet
    {
        return $mission->framingSheet()
            ->with(['responsible', 'missionLeader', 'creator', 'validator'])
            ->first();
    }

    /**
     * Soumettre la fiche pour revue selon le cycle commun.
     */
    public function submitForReview(MissionFramingSheet $sheet, ?User $user = null): MissionFramingSheet
    {
        $sheet->status = 'submitted';
        $sheet->submitted_at = now();
        $sheet->output_snapshot = $this->generateSnapshot($sheet);
        $sheet->save();

        return $sheet;
    }

    /**
     * Valider la fiche lors de la revue.
     */
    public function validateReview(MissionFramingSheet $sheet, User $validator): MissionFramingSheet
    {
        $sheet->status = 'validated';
        $sheet->validated_at = now();
        $sheet->validated_by = $validator->id;
        $sheet->output_snapshot = $this->generateSnapshot($sheet);
        $sheet->save();

        return $sheet;
    }
}
