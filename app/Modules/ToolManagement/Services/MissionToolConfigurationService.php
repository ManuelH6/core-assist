<?php

namespace App\Modules\ToolManagement\Services;

use App\Enums\MissionRole;
use App\Enums\MissionToolUsageStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\ProjectManagement\Http\Models\MissionMember;
use App\Modules\ToolManagement\Http\Models\MissionToolUsage;
use App\Modules\ToolManagement\Http\Models\Tool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MissionToolConfigurationService
{
    /**
     * Actions configurables par outil sur une mission.
     *
     * @return array<string, string>
     */
    public function availableActions(): array
    {
        return [
            'view' => 'Consulter',
            'edit' => 'Saisir / Modifier',
            'submit' => 'Soumettre pour revue',
            'validate' => 'Valider officiellement',
        ];
    }

    /**
     * Rôles opérationnels configurables sur une mission.
     *
     * @return array<string, string>
     */
    public function configurableRoles(): array
    {
        return [
            MissionRole::Manager->value => MissionRole::Manager->label(),
            MissionRole::MissionLead->value => MissionRole::MissionLead->label(),
            MissionRole::Consultant->value => MissionRole::Consultant->label(),
            MissionRole::Contributor->value => MissionRole::Contributor->label(),
        ];
    }

    /**
     * Règles métier spécifiques configurables selon le code de l'outil.
     *
     * @return array<string, array{label: string, default: bool}>
     */
    public function toolSpecificRules(string $toolCode): array
    {
        return match ($toolCode) {
            'D360-DSC' => [
                'lock_after_validation' => [
                    'label' => 'Verrouiller le périmètre après validation',
                    'default' => true,
                ],
            ],
            'D360-DIR' => [
                'allow_custom_items' => [
                    'label' => 'Autoriser l\'ajout de besoins hors référentiel initial',
                    'default' => true,
                ],
            ],
            'D360-SIG' => [
                'allow_custom_stakeholders' => [
                    'label' => 'Autoriser l\'ajout de nouvelles parties prenantes en cours de mission',
                    'default' => true,
                ],
            ],
            'D360-DRD' => [
                'client_can_download' => [
                    'label' => 'Autoriser le téléchargement côté Espace Client',
                    'default' => true,
                ],
                'client_can_comment' => [
                    'label' => 'Autoriser les observations client sur les sections',
                    'default' => true,
                ],
            ],
            'D360-NSR' => [
                'client_can_decide' => [
                    'label' => 'Soumettre les actions proposées à l\'arbitrage client',
                    'default' => true,
                ],
            ],
            default => [],
        };
    }

    /**
     * Configuration par défaut d'un outil avant personnalisation sur la mission.
     *
     * @return array<string, mixed>
     */
    public function defaultConfigurationForTool(string $toolCode): array
    {
        $allRoles = array_keys($this->configurableRoles());
        $leadAndManager = [MissionRole::Manager->value, MissionRole::MissionLead->value];
        $execRoles = [MissionRole::Manager->value, MissionRole::MissionLead->value, MissionRole::Consultant->value];

        $permissions = match ($toolCode) {
            'D360-MFS', 'D360-DSC' => [
                'view' => $allRoles,
                'edit' => $leadAndManager,
                'submit' => $leadAndManager,
                'validate' => [MissionRole::Manager->value],
            ],
            'D360-DRD', 'D360-NSR' => [
                'view' => $allRoles,
                'edit' => $execRoles,
                'submit' => $leadAndManager,
                'validate' => $leadAndManager,
            ],
            default => [
                'view' => $allRoles,
                'edit' => $execRoles,
                'submit' => $execRoles,
                'validate' => $leadAndManager,
            ],
        };

        $rules = [];
        foreach ($this->toolSpecificRules($toolCode) as $ruleKey => $meta) {
            $rules[$ruleKey] = $meta['default'];
        }

        return [
            'allowed_roles' => $allRoles,
            'permissions' => $permissions,
            'rules' => $rules,
        ];
    }

    /**
     * Retourner la matrice complète des outils du catalogue et de leur paramétrage pour la mission.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getMissionToolMatrix(Mission $mission): Collection
    {
        $tools = Tool::with('currentVersion')
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        $usagesByVersion = $mission->toolUsages()
            ->get()
            ->keyBy('tool_version_id');

        $hasAnyConfiguration = $usagesByVersion->isNotEmpty();

        return $tools->map(function (Tool $tool) use ($usagesByVersion, $hasAnyConfiguration): array {
            $version = $tool->currentVersion;
            $usage = $version ? $usagesByVersion->get($version->id) : null;
            $defaults = $this->defaultConfigurationForTool($tool->code);
            $config = array_replace_recursive($defaults, $usage?->configuration ?? []);

            $enabled = $hasAnyConfiguration
                ? ($usage !== null && $usage->status !== MissionToolUsageStatus::Disabled)
                : true;

            return [
                'tool' => $tool,
                'version' => $version,
                'usage' => $usage,
                'enabled' => $enabled,
                'configuration' => $config,
                'specific_rules' => $this->toolSpecificRules($tool->code),
            ];
        });
    }

    /**
     * Enregistrer la configuration des outils et la composition de l'équipe d'une mission.
     *
     * @param  array<string, array<string, mixed>>  $toolsInput
     * @param  array<int|string, array<string, mixed>>  $membersInput
     */
    public function saveConfiguration(
        Mission $mission,
        array $toolsInput,
        array $membersInput,
        User $configuredBy
    ): void {
        DB::transaction(function () use ($mission, $toolsInput, $membersInput, $configuredBy) {
            $tools = Tool::with('currentVersion')->where('status', 'active')->get();

            foreach ($tools as $tool) {
                $version = $tool->currentVersion;
                if (! $version) {
                    continue;
                }

                $input = $toolsInput[$tool->code] ?? [];
                $isEnabled = ! empty($input['enabled']);
                $defaults = $this->defaultConfigurationForTool($tool->code);

                $allowedRoles = array_values(array_intersect(
                    array_keys($this->configurableRoles()),
                    array_map('strval', (array) ($input['allowed_roles'] ?? $defaults['allowed_roles']))
                ));

                $permissions = [];
                foreach (array_keys($this->availableActions()) as $actionKey) {
                    $submittedRoles = (array) ($input['permissions'][$actionKey] ?? []);
                    $permissions[$actionKey] = array_values(array_intersect($allowedRoles, array_map('strval', $submittedRoles)));
                }

                $rules = [];
                foreach ($this->toolSpecificRules($tool->code) as $ruleKey => $meta) {
                    $rules[$ruleKey] = ! empty($input['rules'][$ruleKey]);
                }

                MissionToolUsage::updateOrCreate(
                    [
                        'mission_id' => $mission->id,
                        'tool_version_id' => $version->id,
                    ],
                    [
                        'name' => $tool->name,
                        'configuration' => [
                            'allowed_roles' => $allowedRoles,
                            'permissions' => $permissions,
                            'rules' => $rules,
                        ],
                        'status' => $isEnabled ? MissionToolUsageStatus::Active : MissionToolUsageStatus::Disabled,
                        'configured_by' => $configuredBy->id,
                    ]
                );
            }

            $activeUserIds = [];
            foreach ($membersInput as $memberRow) {
                if (empty($memberRow['selected']) || empty($memberRow['user_id'])) {
                    continue;
                }

                $userId = (int) $memberRow['user_id'];
                $roleValue = (string) ($memberRow['mission_role'] ?? MissionRole::Consultant->value);
                $roleEnum = MissionRole::tryFrom($roleValue) ?? MissionRole::Consultant;

                MissionMember::updateOrCreate(
                    [
                        'mission_id' => $mission->id,
                        'user_id' => $userId,
                    ],
                    [
                        'mission_role' => $roleEnum,
                        'status' => 'active',
                        'joined_at' => now(),
                    ]
                );

                $activeUserIds[] = $userId;
            }

            if (! empty($membersInput)) {
                $mission->members()
                    ->whereNotIn('user_id', $activeUserIds)
                    ->update(['status' => 'removed']);
            }
        });
    }

    /**
     * Vérifier si un outil est activé pour la mission.
     */
    public function isToolEnabled(Mission $mission, string $toolCode): bool
    {
        if (! $mission->toolUsages()->exists()) {
            return true;
        }

        $usage = $this->findUsageByCode($mission, $toolCode);

        return $usage !== null && $usage->status !== MissionToolUsageStatus::Disabled;
    }

    /**
     * Vérifier si un utilisateur est autorisé à effectuer une action donnée sur un outil de la mission.
     */
    public function canUserPerform(
        Mission $mission,
        ?User $user,
        string $toolCode,
        string $action = 'view'
    ): bool {
        if ($user === null) {
            return true;
        }

        if ($user->isClient()) {
            return false;
        }

        if (! $this->isToolEnabled($mission, $toolCode)) {
            return false;
        }

        if ($user->hasRole(UserRole::Dg)) {
            return true;
        }

        $effectiveRole = $this->resolveEffectiveRole($mission, $user);
        if ($effectiveRole === null) {
            return false;
        }

        $usage = $this->findUsageByCode($mission, $toolCode);
        $config = $usage?->configuration ?? $this->defaultConfigurationForTool($toolCode);

        $allowedRoles = (array) ($config['allowed_roles'] ?? []);
        if (! in_array($effectiveRole, $allowedRoles, true)) {
            return false;
        }

        $actionRoles = (array) ($config['permissions'][$action] ?? $allowedRoles);

        return in_array($effectiveRole, $actionRoles, true);
    }

    /**
     * Retourner la carte des permissions effectives pour un utilisateur sur un outil donné.
     *
     * @return array<string, bool>
     */
    public function getEffectivePermissions(Mission $mission, ?User $user, string $toolCode): array
    {
        $result = [];
        foreach (array_keys($this->availableActions()) as $action) {
            $result[$action] = $this->canUserPerform($mission, $user, $toolCode, $action);
        }

        return $result;
    }

    /**
     * Lire une règle spécifique configurée pour un outil sur la mission.
     */
    public function getToolRule(Mission $mission, string $toolCode, string $ruleKey, mixed $default = false): mixed
    {
        $usage = $this->findUsageByCode($mission, $toolCode);

        return $usage?->configuration['rules'][$ruleKey] ?? $default;
    }

    private function findUsageByCode(Mission $mission, string $toolCode): ?MissionToolUsage
    {
        return $mission->toolUsages()
            ->whereHas('toolVersion.tool', fn ($query) => $query->where('code', $toolCode))
            ->first();
    }

    private function resolveEffectiveRole(Mission $mission, User $user): ?string
    {
        $hasConfiguredMembers = $mission->members()->where('status', 'active')->exists();

        if ($hasConfiguredMembers) {
            $member = $mission->members()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($member) {
                return $member->mission_role->value;
            }

            if ($user->hasRole(UserRole::Manager)) {
                return MissionRole::Manager->value;
            }

            return null;
        }

        return match ($user->role) {
            UserRole::Manager => MissionRole::Manager->value,
            UserRole::MissionLead => MissionRole::MissionLead->value,
            UserRole::Consultant => MissionRole::Consultant->value,
            UserRole::Expert => MissionRole::Contributor->value,
            default => null,
        };
    }
}
