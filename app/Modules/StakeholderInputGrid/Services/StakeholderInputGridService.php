<?php

namespace App\Modules\StakeholderInputGrid\Services;

use App\Models\User;
use App\Modules\DiagnosticIntakeRegister\Models\RequiredInformation;
use App\Modules\DiagnosticScopeCanvas\Models\DiagnosticScopeCanvas;
use App\Modules\MissionFramingSheet\Models\MissionFramingSheet;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\StakeholderInputGrid\Models\ExpectedContribution;
use App\Modules\StakeholderInputGrid\Models\ReceivedContribution;
use App\Modules\StakeholderInputGrid\Models\Stakeholder;
use App\Modules\StakeholderInputGrid\Models\StakeholderInputGrid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StakeholderInputGridService
{
    /**
     * Récupérer le cadre de référence opérationnel produit par le Mission Framing Sheet.
     */
    public function getMissionFraming(Mission $mission): ?MissionFramingSheet
    {
        return $mission->framingSheet;
    }

    /**
     * Récupérer le périmètre d'investigation validé dans le Diagnostic Scope Canvas.
     */
    public function getDiagnosticScope(Mission $mission): ?DiagnosticScopeCanvas
    {
        return $mission->scopeCanvas;
    }

    /**
     * Récupérer les besoins d'informations recensés dans le Diagnostic Intake Register.
     *
     * @return Collection<int, RequiredInformation>
     */
    public function getRequiredInformation(Mission $mission): Collection
    {
        return $mission->intakeRegister?->requiredInformation ?? new Collection;
    }

    /**
     * Préparer la cartographie des parties prenantes en consolidant les données des 3 outils précédents sans ressaisie.
     *
     * @return array<string, mixed>
     */
    public function prepareStakeholderInputGrid(Mission $mission): array
    {
        $framing = $this->getMissionFraming($mission);
        $scope = $this->getDiagnosticScope($mission);
        $requiredInfos = $this->getRequiredInformation($mission);

        return [
            'mission_id' => $mission->id,
            'mission_title' => $mission->title,
            'framing' => [
                'id' => $framing?->id,
                'specific_objective' => $framing?->specific_objective,
                'organization_context' => $framing?->output_snapshot['context']['organization_context'] ?? null,
                'client_name' => $framing?->output_snapshot['client']['name'] ?? null,
            ],
            'scope' => [
                'id' => $scope?->id,
                'included_items' => $scope?->included_items ?? [],
                'excluded_items' => $scope?->excluded_items ?? [],
                'included_domains' => $scope?->included_domains ?? [],
            ],
            'intake' => [
                'required_information' => $requiredInfos,
                'pending_count' => $requiredInfos->whereIn('status', ['pending', 'in_progress'])->count(),
            ],
        ];
    }

    /**
     * Récupérer le contexte initial issu de MFS, DSC et DIR.
     *
     * @return array<string, mixed>
     */
    public function getInitialData(Mission $mission): array
    {
        $mission->loadMissing([
            'framingSheet',
            'scopeCanvas',
            'intakeRegister.requiredInformation',
        ]);

        return [
            'mission_id' => $mission->id,
            'mission_title' => $mission->title,
            'framing_sheet' => $mission->framingSheet?->output_snapshot ?? [],
            'included_scope_items' => $mission->scopeCanvas?->included_items ?? [],
            'included_domains' => $mission->scopeCanvas?->included_domains ?? [],
            'required_information_count' => $mission->intakeRegister?->requiredInformation()->count() ?? 0,
        ];
    }

    /**
     * Initialiser la grille avec suggestions automatiques basées sur les domaines du DSC.
     */
    public function initializeGrid(Mission $mission, ?User $user = null): StakeholderInputGrid
    {
        $initialData = $this->getInitialData($mission);
        $includedDomains = $initialData['included_domains'];

        return DB::transaction(function () use ($mission, $initialData, $includedDomains, $user) {
            /** @var StakeholderInputGrid $grid */
            $grid = $mission->stakeholderGrid()->firstOrNew(['mission_id' => $mission->id]);

            $inputSnapshot = [
                'initial_data' => $initialData,
                'initialized_at' => now()->toIso8601String(),
                'initialized_by' => $user?->id,
            ];

            $grid->fill([
                'title' => $grid->title ?? 'Stakeholder Input Grid™',
                'status' => 'active',
                'input_snapshot' => $inputSnapshot,
                'created_by' => $grid->created_by ?? $user?->id,
            ]);
            $grid->save();

            // Suggérer les parties prenantes standard si aucune n'existe encore
            if ($grid->stakeholders()->count() === 0) {
                $this->seedSuggestedStakeholders($grid, $includedDomains);
            }

            $grid->output_snapshot = $this->generateSnapshot($grid);
            $grid->save();

            return $grid->fresh(['stakeholders.expectedContributions', 'receivedContributions']);
        });
    }

    /**
     * Enregistrer une nouvelle partie prenante.
     *
     * @param  array<string, mixed>  $data
     */
    public function identifyStakeholder(StakeholderInputGrid $grid, array $data): Stakeholder
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Le nom ou la désignation de la partie prenante est obligatoire.');
        }

        $stakeholder = $grid->stakeholders()->create([
            'name' => $name,
            'stakeholder_type' => $data['stakeholder_type'] ?? 'internal',
            'position' => ! empty($data['position']) ? trim((string) $data['position']) : null,
            'organization' => ! empty($data['organization']) ? trim((string) $data['organization']) : null,
            'role_in_diagnostic' => ! empty($data['role_in_diagnostic']) ? trim((string) $data['role_in_diagnostic']) : null,
            'contact_details' => ! empty($data['contact_details']) ? trim((string) $data['contact_details']) : null,
            'observations' => ! empty($data['observations']) ? trim((string) $data['observations']) : null,
        ]);

        $this->refreshGridSnapshot($grid);

        return $stakeholder;
    }

    /**
     * Modifier les informations d'une partie prenante.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateStakeholder(Stakeholder $stakeholder, array $data): Stakeholder
    {
        $stakeholder->update(array_filter([
            'name' => isset($data['name']) ? trim((string) $data['name']) : null,
            'stakeholder_type' => $data['stakeholder_type'] ?? null,
            'position' => array_key_exists('position', $data) ? $data['position'] : null,
            'organization' => array_key_exists('organization', $data) ? $data['organization'] : null,
            'role_in_diagnostic' => array_key_exists('role_in_diagnostic', $data) ? $data['role_in_diagnostic'] : null,
            'contact_details' => array_key_exists('contact_details', $data) ? $data['contact_details'] : null,
            'observations' => array_key_exists('observations', $data) ? $data['observations'] : null,
        ], fn ($val) => $val !== null));

        $this->refreshGridSnapshot($stakeholder->grid);

        return $stakeholder;
    }

    /**
     * Définir une contribution attendue en exploitant le périmètre DSC et les besoins DIR.
     *
     * @param  array<string, mixed>  $data
     */
    public function defineExpectedContribution(StakeholderInputGrid $grid, array $data): ExpectedContribution
    {
        $stakeholderId = $data['stakeholder_id'] ?? null;
        $scopeItemId = $data['scope_item_id'] ?? null;
        $requiredInfoId = $data['required_information_id'] ?? null;

        $domain = trim((string) ($data['domain'] ?? ''));
        $axis = trim((string) ($data['axis'] ?? ''));
        $subaxis = ! empty($data['subaxis']) ? trim((string) $data['subaxis']) : null;
        $contribution = trim((string) ($data['expected_contribution'] ?? ''));

        // Résolution automatique depuis le DSC si un scope_item_id est transmis
        if ($scopeItemId) {
            $scope = $this->getDiagnosticScope($grid->mission);
            if ($scope) {
                $scopeItems = collect($scope->scope_items ?? []);
                $matchedScopeItem = $scopeItems->firstWhere('id', $scopeItemId);

                if ($matchedScopeItem) {
                    // Règle d'étanchéité : interdire la mobilisation sur un sujet exclu
                    if (($matchedScopeItem['status'] ?? '') === 'excluded') {
                        throw new InvalidArgumentException("L'axe '{$matchedScopeItem['axis']}' est exclu du périmètre validé (DSC). Impossible de mobiliser une partie prenante sur un sujet hors périmètre.");
                    }

                    $domain = $matchedScopeItem['domain'];
                    $axis = $matchedScopeItem['axis'];
                    $subaxis = $matchedScopeItem['subaxis'] ?? null;
                }
            }
        }

        // Résolution automatique depuis le DIR si un required_information_id est transmis
        if ($requiredInfoId) {
            $requiredInfo = RequiredInformation::find($requiredInfoId);
            if ($requiredInfo) {
                $domain = $domain !== '' ? $domain : $requiredInfo->domain;
                $axis = $axis !== '' ? $axis : $requiredInfo->axis;
                $subaxis = $subaxis ?: $requiredInfo->subaxis;

                if ($contribution === '') {
                    $contribution = "Recueil d'informations et explications sur : ".$requiredInfo->information_name;
                }
            }
        }

        if (! $stakeholderId || $domain === '' || $axis === '' || $contribution === '') {
            throw new InvalidArgumentException("La partie prenante, le domaine, l'axe et la contribution attendue sont obligatoires.");
        }

        $expected = $grid->expectedContributions()->create([
            'stakeholder_id' => $stakeholderId,
            'scope_item_id' => $scopeItemId,
            'required_information_id' => $requiredInfoId,
            'domain' => $domain,
            'axis' => $axis,
            'subaxis' => $subaxis,
            'expected_contribution' => $contribution,
            'justification' => ! empty($data['justification']) ? trim((string) $data['justification']) : null,
            'modality' => $data['modality'] ?? 'interview',
            'priority' => $data['priority'] ?? 'normal',
            'assigned_to' => $data['assigned_to'] ?? null,
            'status' => $data['status'] ?? 'to_solicit',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'observations' => ! empty($data['observations']) ? trim((string) $data['observations']) : null,
        ]);

        $this->refreshGridSnapshot($grid);

        return $expected;
    }

    /**
     * Affecter un consultant responsable de la mobilisation.
     */
    public function assignMobilization(ExpectedContribution $contribution, User|int|null $user): ExpectedContribution
    {
        $userId = $user instanceof User ? $user->id : $user;

        $contribution->assigned_to = $userId;
        if ($contribution->status === 'to_solicit' && $userId !== null) {
            $contribution->status = 'solicited';
        }

        $contribution->save();
        $this->refreshGridSnapshot($contribution->grid);

        return $contribution;
    }

    /**
     * Faire évoluer le statut de mobilisation d'une contribution attendue.
     */
    public function updateMobilizationStatus(
        ExpectedContribution $contribution,
        string $status,
        ?string $scheduledAt = null
    ): ExpectedContribution {
        if (! in_array($status, ['to_solicit', 'solicited', 'scheduled', 'completed', 'unavailable', 'cancelled'], true)) {
            throw new InvalidArgumentException("Statut de mobilisation '{$status}' invalide.");
        }

        $contribution->status = $status;

        if ($status === 'scheduled' && $scheduledAt !== null) {
            $contribution->scheduled_at = $scheduledAt;
        }

        if ($status === 'completed' && $contribution->completed_at === null) {
            $contribution->completed_at = now();
        }

        $contribution->save();
        $this->refreshGridSnapshot($contribution->grid);

        return $contribution;
    }

    /**
     * Enregistrer une contribution effectivement recueillie.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordContribution(StakeholderInputGrid $grid, array $data, ?User $user = null): ReceivedContribution
    {
        $stakeholderId = $data['stakeholder_id'] ?? null;
        $summary = trim((string) ($data['summary'] ?? ''));

        if (! $stakeholderId || $summary === '') {
            throw new InvalidArgumentException('La partie prenante et le résumé de la contribution sont obligatoires.');
        }

        return DB::transaction(function () use ($grid, $data, $stakeholderId, $summary, $user) {
            /** @var ReceivedContribution $received */
            $received = $grid->receivedContributions()->create([
                'stakeholder_id' => $stakeholderId,
                'expected_contribution_id' => $data['expected_contribution_id'] ?? null,
                'received_at' => ! empty($data['received_at']) ? $data['received_at'] : now(),
                'modality' => $data['modality'] ?? 'interview',
                'summary' => $summary,
                'key_findings' => ! empty($data['key_findings']) ? trim((string) $data['key_findings']) : null,
                'associated_document' => ! empty($data['associated_document']) ? trim((string) $data['associated_document']) : null,
                'recorded_by' => $data['recorded_by'] ?? $user?->id,
                'reliability_perception' => $data['reliability_perception'] ?? 'to_evaluate',
                'observations' => ! empty($data['observations']) ? trim((string) $data['observations']) : null,
            ]);

            // Si liée à une contribution attendue, marquer celle-ci comme complétée
            if (! empty($data['expected_contribution_id'])) {
                $expected = $grid->expectedContributions()->find($data['expected_contribution_id']);
                if ($expected) {
                    $expected->status = 'completed';
                    $expected->completed_at = now();
                    $expected->save();
                }
            }

            // Liaison éventuelle avec des besoins d'informations du DIR
            if (! empty($data['linked_required_information_ids']) && is_array($data['linked_required_information_ids'])) {
                foreach ($data['linked_required_information_ids'] as $infoId) {
                    $info = RequiredInformation::find($infoId);
                    if ($info) {
                        $this->linkContributionToInformationNeed($received, $info, $data['contribution_value'] ?? null);
                    }
                }
            }

            $this->refreshGridSnapshot($grid);

            return $received->fresh(['stakeholder', 'expectedContribution', 'requiredInformation']);
        });
    }

    /**
     * Relier une contribution recueillie à un besoin d'information du DIR.
     */
    public function linkContributionToInformationNeed(
        ReceivedContribution $contribution,
        RequiredInformation|int $info,
        ?string $contributionValue = null
    ): void {
        $infoId = $info instanceof RequiredInformation ? $info->id : $info;

        $contribution->requiredInformation()->syncWithoutDetaching([
            $infoId => [
                'contribution_value' => $contributionValue,
            ],
        ]);

        $this->refreshGridSnapshot($contribution->grid);
    }

    /**
     * Calculer l'état d'avancement de la mobilisation des parties prenantes.
     *
     * @return array<string, mixed>
     */
    public function getMobilizationProgress(StakeholderInputGrid $grid): array
    {
        $expected = $grid->expectedContributions()->get();
        $totalExpected = $expected->count();
        $stakeholdersCount = $grid->stakeholders()->count();
        $receivedCount = $grid->receivedContributions()->count();

        if ($totalExpected === 0) {
            return [
                'stakeholders_count' => $stakeholdersCount,
                'total_expected' => 0,
                'completed_count' => 0,
                'pending_count' => 0,
                'unavailable_count' => 0,
                'mobilization_percentage' => 0.0,
                'received_contributions_count' => $receivedCount,
                'by_domain' => [],
            ];
        }

        $completed = $expected->where('status', 'completed')->count();
        $unavailable = $expected->whereIn('status', ['unavailable', 'cancelled'])->count();
        $pending = $expected->whereIn('status', ['to_solicit', 'solicited', 'scheduled'])->count();

        $byDomain = [];
        foreach ($expected->groupBy('domain') as $domain => $items) {
            $domTotal = $items->count();
            $domDone = $items->where('status', 'completed')->count();
            $byDomain[$domain] = [
                'total' => $domTotal,
                'completed' => $domDone,
                'percentage' => $domTotal > 0 ? round(($domDone / $domTotal) * 100, 1) : 0.0,
            ];
        }

        return [
            'stakeholders_count' => $stakeholdersCount,
            'total_expected' => $totalExpected,
            'completed_count' => $completed,
            'pending_count' => $pending,
            'unavailable_count' => $unavailable,
            'mobilization_percentage' => round(($completed / $totalExpected) * 100, 1),
            'received_contributions_count' => $receivedCount,
            'by_domain' => $byDomain,
        ];
    }

    /**
     * Produire la photographie complète de la grille de parties prenantes.
     *
     * @return array<string, mixed>
     */
    public function generateSnapshot(StakeholderInputGrid $grid): array
    {
        $progress = $this->getMobilizationProgress($grid);
        $mission = $grid->mission;

        return [
            'grid_id' => $grid->id,
            'mission' => [
                'id' => $mission?->id ?? $grid->mission_id,
                'reference' => $mission?->reference,
                'title' => $mission?->title,
            ],
            'title' => $grid->title,
            'status' => $grid->status,
            'metrics' => $progress,
            'snapshot_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Actualiser l'instantané de sortie.
     */
    public function refreshGridSnapshot(StakeholderInputGrid $grid): void
    {
        $grid->output_snapshot = $this->generateSnapshot($grid);
        $grid->save();
    }

    /**
     * Suggérer des catégories de parties prenantes canoniques selon les domaines.
     *
     * @param  array<int, string>  $domains
     */
    private function seedSuggestedStakeholders(StakeholderInputGrid $grid, array $domains): void
    {
        // 1. Toujours suggérer la Direction Générale
        $dg = $grid->stakeholders()->create([
            'name' => 'Direction Générale / Gérant',
            'stakeholder_type' => 'internal',
            'position' => 'Dirigeant exécutif',
            'role_in_diagnostic' => 'Décideur principal & vision stratégique',
            'observations' => 'Interlocuteur sponsor du diagnostic.',
        ]);

        $grid->expectedContributions()->create([
            'stakeholder_id' => $dg->id,
            'domain' => 'Stratégie & Pilotage',
            'axis' => 'Vision & Priorités d’entreprise',
            'expected_contribution' => 'Explication des objectifs prioritaires et arbitrage des contraintes.',
            'modality' => 'interview',
            'priority' => 'critical',
            'status' => 'to_solicit',
        ]);

        // 2. Suggestions adaptées aux domaines inclus
        foreach ($domains as $domain) {
            if (str_contains(mb_strtolower($domain), 'finance')) {
                $daf = $grid->stakeholders()->create([
                    'name' => 'Responsable Financier / Comptable',
                    'stakeholder_type' => 'internal',
                    'position' => 'DAF / Chef comptable',
                    'role_in_diagnostic' => 'Expert chiffres & états financiers',
                ]);
                $grid->expectedContributions()->create([
                    'stakeholder_id' => $daf->id,
                    'domain' => 'Finance & Rentabilité',
                    'axis' => 'Rentabilité & Marges',
                    'expected_contribution' => 'Justification des écarts de marges et structure des coûts.',
                    'modality' => 'interview',
                    'priority' => 'high',
                    'status' => 'to_solicit',
                ]);
            }

            if (str_contains(mb_strtolower($domain), 'opérations') || str_contains(mb_strtolower($domain), 'production')) {
                $prod = $grid->stakeholders()->create([
                    'name' => 'Responsable d’Atelier / Production',
                    'stakeholder_type' => 'internal',
                    'position' => 'Directeur de production',
                    'role_in_diagnostic' => 'Responsable opérationnel de flux',
                ]);
                $grid->expectedContributions()->create([
                    'stakeholder_id' => $prod->id,
                    'domain' => 'Opérations & Production',
                    'axis' => 'Processus de fabrication',
                    'expected_contribution' => 'Explication des temps de cycle et causes des rebuts.',
                    'modality' => 'interview',
                    'priority' => 'high',
                    'status' => 'to_solicit',
                ]);
            }

            if (str_contains(mb_strtolower($domain), 'commercial')) {
                $com = $grid->stakeholders()->create([
                    'name' => 'Responsable Commercial / Ventes',
                    'stakeholder_type' => 'internal',
                    'position' => 'Directeur des ventes',
                    'role_in_diagnostic' => 'Responsable de la relation client',
                ]);
                $grid->expectedContributions()->create([
                    'stakeholder_id' => $com->id,
                    'domain' => 'Commercial & Marché',
                    'axis' => 'Clientèle & Performance',
                    'expected_contribution' => 'Perception de l’attractivité de l’offre et retour des clients.',
                    'modality' => 'interview',
                    'priority' => 'normal',
                    'status' => 'to_solicit',
                ]);
            }
        }
    }
}
