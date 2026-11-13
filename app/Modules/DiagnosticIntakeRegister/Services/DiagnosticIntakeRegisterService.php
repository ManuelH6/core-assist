<?php

namespace App\Modules\DiagnosticIntakeRegister\Services;

use App\Models\User;
use App\Modules\DiagnosticIntakeRegister\Models\DiagnosticIntakeRegister;
use App\Modules\DiagnosticIntakeRegister\Models\ReceivedItem;
use App\Modules\DiagnosticIntakeRegister\Models\RequiredInformation;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DiagnosticIntakeRegisterService
{
    /**
     * Récupérer le contexte initial issu du cadrage et du périmètre DSC.
     *
     * @return array<string, mixed>
     */
    public function getInitialData(Mission $mission): array
    {
        $mission->loadMissing([
            'framingSheet',
            'scopeCanvas',
        ]);

        $scopeCanvas = $mission->scopeCanvas;
        $includedItems = $scopeCanvas?->included_items ?? [];

        return [
            'mission_id' => $mission->id,
            'mission_title' => $mission->title,
            'framing_sheet_id' => $mission->framingSheet?->id,
            'scope_canvas_id' => $scopeCanvas?->id,
            'included_domains' => $scopeCanvas?->included_domains ?? [],
            'included_scope_items' => $includedItems,
        ];
    }

    /**
     * Générer le registre initial d'informations à collecter à partir des axes retenus dans le DSC.
     */
    public function generateInitialRegister(Mission $mission, ?User $user = null): DiagnosticIntakeRegister
    {
        $initialData = $this->getInitialData($mission);
        $includedItems = $initialData['included_scope_items'];

        return DB::transaction(function () use ($mission, $initialData, $includedItems, $user) {
            /** @var DiagnosticIntakeRegister $register */
            $register = $mission->intakeRegister()->firstOrNew(['mission_id' => $mission->id]);

            $inputSnapshot = [
                'initial_data' => $initialData,
                'generated_at' => now()->toIso8601String(),
                'generated_by' => $user?->id,
            ];

            $register->fill([
                'title' => $register->title ?? 'Diagnostic Intake Register™',
                'status' => 'active',
                'input_snapshot' => $inputSnapshot,
                'created_by' => $register->created_by ?? $user?->id,
            ]);
            $register->save();

            // Génération des besoins d'informations standard pour chaque axe inclus
            $templates = $this->getStandardInformationTemplates();

            foreach ($includedItems as $scopeItem) {
                $domain = $scopeItem['domain'] ?? 'Général';
                $axis = $scopeItem['axis'] ?? 'Axe';
                $subaxis = $scopeItem['subaxis'] ?? null;

                $suggestedList = $this->matchSuggestionsForAxis($domain, $axis, $subaxis, $templates);

                foreach ($suggestedList as $suggestion) {
                    $exists = $register->requiredInformation()
                        ->where('domain', $domain)
                        ->where('axis', $axis)
                        ->where('information_name', $suggestion['information_name'])
                        ->exists();

                    if (! $exists) {
                        $register->requiredInformation()->create([
                            'domain' => $domain,
                            'axis' => $axis,
                            'subaxis' => $subaxis,
                            'information_name' => $suggestion['information_name'],
                            'justification' => $suggestion['justification'] ?? "Nécessaire à l'évaluation de l'axe {$axis}.",
                            'expected_source' => $suggestion['expected_source'] ?? 'Service concerné',
                            'collection_method' => $suggestion['collection_method'] ?? 'document',
                            'priority' => $suggestion['priority'] ?? 'medium',
                            'status' => 'pending',
                            'verification_status' => 'unverified',
                        ]);
                    }
                }
            }

            $register->output_snapshot = $this->generateSnapshot($register);
            $register->save();

            return $register->fresh(['requiredInformation.assignedUser', 'receivedItems']);
        });
    }

    /**
     * Ajouter manuellement une information recherchée.
     *
     * @param  array<string, mixed>  $data
     */
    public function addRequiredInformation(DiagnosticIntakeRegister $register, array $data): RequiredInformation
    {
        $name = trim((string) ($data['information_name'] ?? ''));
        $domain = trim((string) ($data['domain'] ?? ''));
        $axis = trim((string) ($data['axis'] ?? ''));

        if ($name === '' || $domain === '' || $axis === '') {
            throw new InvalidArgumentException("L'information recherchée, le domaine et l'axe sont obligatoires.");
        }

        $info = $register->requiredInformation()->create([
            'domain' => $domain,
            'axis' => $axis,
            'subaxis' => ! empty($data['subaxis']) ? trim((string) $data['subaxis']) : null,
            'information_name' => $name,
            'justification' => ! empty($data['justification']) ? trim((string) $data['justification']) : null,
            'expected_source' => ! empty($data['expected_source']) ? trim((string) $data['expected_source']) : null,
            'collection_method' => $data['collection_method'] ?? 'document',
            'priority' => $data['priority'] ?? 'medium',
            'assigned_to' => $data['assigned_to'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'verification_status' => 'unverified',
            'observations' => ! empty($data['observations']) ? trim((string) $data['observations']) : null,
        ]);

        $this->refreshRegisterSnapshot($register);

        return $info;
    }

    /**
     * Modifier une demande d'information.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateRequiredInformation(RequiredInformation $info, array $data): RequiredInformation
    {
        $info->update(array_filter([
            'domain' => isset($data['domain']) ? trim((string) $data['domain']) : null,
            'axis' => isset($data['axis']) ? trim((string) $data['axis']) : null,
            'subaxis' => array_key_exists('subaxis', $data) ? $data['subaxis'] : null,
            'information_name' => isset($data['information_name']) ? trim((string) $data['information_name']) : null,
            'justification' => array_key_exists('justification', $data) ? $data['justification'] : null,
            'expected_source' => array_key_exists('expected_source', $data) ? $data['expected_source'] : null,
            'collection_method' => $data['collection_method'] ?? null,
            'priority' => $data['priority'] ?? null,
            'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : null,
            'status' => $data['status'] ?? null,
            'verification_status' => $data['verification_status'] ?? null,
            'observations' => array_key_exists('observations', $data) ? $data['observations'] : null,
        ], fn ($val) => $val !== null));

        $this->refreshRegisterSnapshot($info->register);

        return $info;
    }

    /**
     * Affecter un collaborateur responsable de la collecte.
     */
    public function assignResponsible(RequiredInformation $info, User|int|null $user): RequiredInformation
    {
        $userId = $user instanceof User ? $user->id : $user;

        $info->assigned_to = $userId;
        if ($info->status === 'pending' && $userId !== null) {
            $info->status = 'in_progress';
        }

        $info->save();
        $this->refreshRegisterSnapshot($info->register);

        return $info;
    }

    /**
     * Enregistrer un document ou un élément physique/numérique reçu.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerReceivedItem(DiagnosticIntakeRegister $register, array $data, ?User $user = null): ReceivedItem
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException("L'intitulé du document ou de la pièce reçue est obligatoire.");
        }

        return DB::transaction(function () use ($register, $data, $title, $user) {
            /** @var ReceivedItem $item */
            $item = $register->receivedItems()->create([
                'title' => $title,
                'item_type' => $data['item_type'] ?? 'document',
                'source_origin' => $data['source_origin'] ?? null,
                'file_path' => $data['file_path'] ?? null,
                'file_name' => $data['file_name'] ?? null,
                'file_size' => $data['file_size'] ?? null,
                'received_at' => ! empty($data['received_at']) ? $data['received_at'] : now(),
                'received_by' => $data['received_by'] ?? $user?->id,
                'reliability_score' => $data['reliability_score'] ?? 'to_assess',
                'notes' => $data['notes'] ?? null,
            ]);

            // Association immédiate avec des informations recherchées si fournies
            if (! empty($data['linked_information_ids']) && is_array($data['linked_information_ids'])) {
                foreach ($data['linked_information_ids'] as $infoId) {
                    $info = $register->requiredInformation()->find($infoId);
                    if ($info) {
                        $this->linkReceivedItem($item, $info, $data['coverage_degree'] ?? 'full');
                    }
                }
            }

            $this->refreshRegisterSnapshot($register);

            return $item->fresh(['requiredInformation']);
        });
    }

    /**
     * Associer un élément reçu à une information recherchée.
     */
    public function linkReceivedItem(ReceivedItem $item, RequiredInformation $info, string $coverage = 'full', ?string $notes = null): void
    {
        $item->requiredInformation()->syncWithoutDetaching([
            $info->id => [
                'coverage_degree' => $coverage,
                'notes' => $notes,
            ],
        ]);

        // Mise à jour de l'état de l'information : passe en "received" si elle était en attente
        if (in_array($info->status, ['pending', 'in_progress'], true)) {
            $info->status = 'received';
            $info->received_at = now();
            $info->save();
        }

        $this->refreshRegisterSnapshot($item->register);
    }

    /**
     * Mettre à jour l'état de collecte et le statut de vérification de l'information.
     */
    public function updateCollectionStatus(
        RequiredInformation $info,
        string $status,
        ?string $verificationStatus = null,
        ?User $verifiedBy = null
    ): RequiredInformation {
        if (! in_array($status, ['pending', 'in_progress', 'received', 'verified', 'unavailable'], true)) {
            throw new InvalidArgumentException("Statut '{$status}' non valide.");
        }

        $info->status = $status;

        if ($status === 'received' && $info->received_at === null) {
            $info->received_at = now();
        }

        if ($status === 'verified') {
            $info->verification_status = $verificationStatus ?? 'verified_valid';
            $info->verified_at = now();
            $info->verified_by = $verifiedBy?->id;
        }

        $info->save();
        $this->refreshRegisterSnapshot($info->register);

        return $info;
    }

    /**
     * Calculer l'avancement global de la collecte et la synthèse de qualité.
     *
     * @return array<string, mixed>
     */
    public function getCollectionProgress(DiagnosticIntakeRegister $register): array
    {
        $all = $register->requiredInformation()->get();
        $total = $all->count();

        if ($total === 0) {
            return [
                'total_items' => 0,
                'received_count' => 0,
                'verified_count' => 0,
                'pending_count' => 0,
                'progress_percentage' => 0.0,
                'verification_percentage' => 0.0,
                'by_domain' => [],
                'by_priority' => [],
            ];
        }

        $received = $all->whereIn('status', ['received', 'verified'])->count();
        $verified = $all->where('status', 'verified')->count();
        $pending = $all->whereIn('status', ['pending', 'in_progress'])->count();

        // Ventilation par domaine
        $byDomain = [];
        foreach ($all->groupBy('domain') as $domain => $items) {
            $domTotal = $items->count();
            $domRecv = $items->whereIn('status', ['received', 'verified'])->count();
            $byDomain[$domain] = [
                'total' => $domTotal,
                'received' => $domRecv,
                'percentage' => $domTotal > 0 ? round(($domRecv / $domTotal) * 100, 1) : 0.0,
            ];
        }

        // Ventilation par priorité des éléments encore en attente
        $pendingByPriority = [
            'high' => $all->where('priority', 'high')->whereIn('status', ['pending', 'in_progress'])->count(),
            'medium' => $all->where('priority', 'medium')->whereIn('status', ['pending', 'in_progress'])->count(),
            'low' => $all->where('priority', 'low')->whereIn('status', ['pending', 'in_progress'])->count(),
        ];

        return [
            'total_items' => $total,
            'received_count' => $received,
            'verified_count' => $verified,
            'pending_count' => $pending,
            'progress_percentage' => round(($received / $total) * 100, 1),
            'verification_percentage' => round(($verified / $total) * 100, 1),
            'by_domain' => $byDomain,
            'pending_by_priority' => $pendingByPriority,
        ];
    }

    /**
     * Produire une photographie complète du registre de collecte.
     *
     * @return array<string, mixed>
     */
    public function generateSnapshot(DiagnosticIntakeRegister $register): array
    {
        $progress = $this->getCollectionProgress($register);
        $mission = $register->mission;

        return [
            'register_id' => $register->id,
            'mission' => [
                'id' => $mission?->id ?? $register->mission_id,
                'reference' => $mission?->reference,
                'title' => $mission?->title,
            ],
            'title' => $register->title,
            'status' => $register->status,
            'metrics' => $progress,
            'received_items_count' => $register->receivedItems()->count(),
            'snapshot_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Mettre à jour l'instantané de sortie du registre.
     */
    public function refreshRegisterSnapshot(DiagnosticIntakeRegister $register): void
    {
        $register->output_snapshot = $this->generateSnapshot($register);
        $register->save();
    }

    /**
     * Catalogue des modèles standard d'informations requises par domaine et axe.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    private function getStandardInformationTemplates(): array
    {
        return [
            'Finance & Rentabilité' => [
                [
                    'information_name' => 'États financiers des 3 derniers exercices clos (Bilans & Comptes de résultat)',
                    'justification' => 'Analyse rétrospective des marges, SIG et équilibres financiers.',
                    'expected_source' => 'Expert-comptable / Direction Financière',
                    'collection_method' => 'document',
                    'priority' => 'high',
                ],
                [
                    'information_name' => 'Balance générale et grand livre analytique de l’exercice en cours',
                    'justification' => 'Calcul des coûts récents et identification des dérives de charges.',
                    'expected_source' => 'Comptabilité interne',
                    'collection_method' => 'data_extraction',
                    'priority' => 'high',
                ],
                [
                    'information_name' => 'Détail des ventes et marges par produit ou famille d’activité',
                    'justification' => 'Calcul de la rentabilité unitaire et marge brute par segment.',
                    'expected_source' => 'Logiciel de facturation / Gestion commerciale',
                    'collection_method' => 'data_extraction',
                    'priority' => 'high',
                ],
                [
                    'information_name' => 'Relevés bancaires et états de rapprochement des 6 derniers mois',
                    'justification' => 'Vérification de la tension de trésorerie et des agios supportés.',
                    'expected_source' => 'Trésorerie / Banques',
                    'collection_method' => 'document',
                    'priority' => 'medium',
                ],
            ],

            'Opérations & Production' => [
                [
                    'information_name' => 'Cartographie des flux de production et schémas d’atelier',
                    'justification' => 'Identification des goulots d’étranglement et temps de cycle.',
                    'expected_source' => 'Responsable de production',
                    'collection_method' => 'observation',
                    'priority' => 'high',
                ],
                [
                    'information_name' => 'Relevé des rebuts, pertes matières et retouches de fabrication',
                    'justification' => 'Quantification financière des pertes matières sur la marge brute.',
                    'expected_source' => 'Fiches de contrôle qualité atelier',
                    'collection_method' => 'document',
                    'priority' => 'high',
                ],
                [
                    'information_name' => 'État des stocks valorisés et inventaire physique récent',
                    'justification' => 'Vérification du niveau de sur-stockage et risque d’obsolescence.',
                    'expected_source' => 'Magasinier / Gestion des stocks',
                    'collection_method' => 'data_extraction',
                    'priority' => 'medium',
                ],
            ],

            'Commercial & Marché' => [
                [
                    'information_name' => 'Grille tarifaire officielle et politique de remises clients',
                    'justification' => 'Analyse de l’élasticité-prix et des dérives de remises non autorisées.',
                    'expected_source' => 'Direction commerciale',
                    'collection_method' => 'document',
                    'priority' => 'medium',
                ],
                [
                    'information_name' => 'Fichier clients avec chiffre d’affaires ventilé sur 24 mois',
                    'justification' => 'Mesure de la concentration du portefeuille et de la dépendance.',
                    'expected_source' => 'CRM / Facturation',
                    'collection_method' => 'data_extraction',
                    'priority' => 'high',
                ],
            ],

            'Organisation & Ressources Humaines' => [
                [
                    'information_name' => 'Organigramme nominatif et répartition des responsabilités',
                    'justification' => 'Vérification de la clarté managériale et des doublons de postes.',
                    'expected_source' => 'Direction générale / RH',
                    'collection_method' => 'interview',
                    'priority' => 'medium',
                ],
                [
                    'information_name' => 'Livre de paie ou registre de masse salariale par service',
                    'justification' => 'Évaluation du ratio masse salariale / valeur ajoutée.',
                    'expected_source' => 'Service RH / Paie',
                    'collection_method' => 'document',
                    'priority' => 'high',
                ],
            ],

            'Stratégie & Pilotage' => [
                [
                    'information_name' => 'Tableaux de bord de gestion et indicateurs KPI actuels',
                    'justification' => 'Appréciation de la capacité de pilotage en temps réel du dirigeant.',
                    'expected_source' => 'Contrôle de gestion / DG',
                    'collection_method' => 'document',
                    'priority' => 'medium',
                ],
            ],
        ];
    }

    /**
     * Trouver les suggestions adaptées pour un domaine ou axe donné.
     *
     * @param  array<string, mixed>  $templates
     * @return array<int, array<string, string>>
     */
    private function matchSuggestionsForAxis(string $domain, string $axis, ?string $subaxis, array $templates): array
    {
        // Recherche par domaine exact
        foreach ($templates as $templateDomain => $items) {
            if (str_contains(mb_strtolower($domain), mb_strtolower(explode('&', $templateDomain)[0]))) {
                return $items;
            }
        }

        // Repli générique si domaine non reconnu
        return [
            [
                'information_name' => "Données et pièces justificatives relatives à {$axis}",
                'justification' => "Indispensable pour documenter l'axe {$axis}.",
                'expected_source' => 'Responsable de domaine',
                'collection_method' => 'document',
                'priority' => 'medium',
            ],
        ];
    }
}
