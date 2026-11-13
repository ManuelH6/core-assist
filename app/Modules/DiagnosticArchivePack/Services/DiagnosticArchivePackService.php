<?php

namespace App\Modules\DiagnosticArchivePack\Services;

use App\Models\User;
use App\Modules\Diagnostic360Matrix\Models\Diagnostic360Matrix;
use App\Modules\DiagnosticArchivePack\Models\ArchiveCategory;
use App\Modules\DiagnosticArchivePack\Models\ArchiveItem;
use App\Modules\DiagnosticArchivePack\Models\ArchiveSource;
use App\Modules\DiagnosticArchivePack\Models\ArchiveVersion;
use App\Modules\DiagnosticArchivePack\Models\DiagnosticArchivePack;
use App\Modules\DiagnosticIntakeRegister\Models\DiagnosticIntakeRegister;
use App\Modules\DiagnosticRestitutionDeck\Models\DiagnosticRestitutionDeck;
use App\Modules\DiagnosticScopeCanvas\Models\DiagnosticScopeCanvas;
use App\Modules\LiveDecisionCapture\Models\LiveDecisionCapture;
use App\Modules\MissionFramingSheet\Models\MissionFramingSheet;
use App\Modules\ProblemStructuringGrid\Models\ProblemStructuringGrid;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\StakeholderInputGrid\Models\StakeholderInputGrid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DiagnosticArchivePackService
{
    /**
     * Catégories système standard du référentiel documentaire.
     *
     * @var array<int, array<string, mixed>>
     */
    public const DEFAULT_CATEGORIES = [
        [
            'code' => 'cadrage',
            'name' => 'Cadrage Opérationnel',
            'description' => 'Fiches de cadrage, objectifs et engagements initiaux de la mission.',
            'icon' => 'file-text',
            'position' => 1,
        ],
        [
            'code' => 'perimetre',
            'name' => 'Périmètre du Diagnostic',
            'description' => 'Délimitations, domaines audités, axes approfondis et exclusions justifiées.',
            'icon' => 'compass',
            'position' => 2,
        ],
        [
            'code' => 'collecte',
            'name' => 'Collecte Documentaire & Données',
            'description' => 'Pièces collectées, données chiffrées, états financiers et éléments reçus.',
            'icon' => 'folder-down',
            'position' => 3,
        ],
        [
            'code' => 'consultations',
            'name' => 'Consultations & Parties Prenantes',
            'description' => 'Cartographie des acteurs, entretiens, contributions et comptes rendus d’échange.',
            'icon' => 'users',
            'position' => 4,
        ],
        [
            'code' => 'analyses',
            'name' => 'Analyses & Évaluations 360°',
            'description' => 'Matrices d’appréciation, constats factuels, points forts et difficultés.',
            'icon' => 'bar-chart-2',
            'position' => 5,
        ],
        [
            'code' => 'problemes',
            'name' => 'Problèmes Structurés & Causes',
            'description' => 'Arbres de causes racines, chaînes de conséquences et grilles de criticité.',
            'icon' => 'alert-triangle',
            'position' => 6,
        ],
        [
            'code' => 'restitutions',
            'name' => 'Restitutions & Versions de Rapports',
            'description' => 'Supports de présentation, trames éditoriales et versions du rapport formel.',
            'icon' => 'layout',
            'position' => 7,
        ],
        [
            'code' => 'decisions',
            'name' => 'Décisions, Arbitrages & Engagements',
            'description' => 'Actes formels de décision, relevés de conclusions et suivi des engagements.',
            'icon' => 'check-square',
            'position' => 8,
        ],
        [
            'code' => 'documents_complementaires',
            'name' => 'Documents Complémentaires & Annexes',
            'description' => 'Notes d’observations, annexes techniques et pièces justificatives tierces.',
            'icon' => 'paperclip',
            'position' => 9,
        ],
    ];

    public function getMissionFraming(Mission $mission): ?MissionFramingSheet
    {
        return $mission->framingSheet;
    }

    public function getDiagnosticScope(Mission $mission): ?DiagnosticScopeCanvas
    {
        return $mission->scopeCanvas;
    }

    public function getIntakeRegister(Mission $mission): ?DiagnosticIntakeRegister
    {
        return $mission->intakeRegister;
    }

    public function getStakeholderGrid(Mission $mission): ?StakeholderInputGrid
    {
        return $mission->stakeholderGrid;
    }

    public function getDiagnosticMatrix(Mission $mission): ?Diagnostic360Matrix
    {
        return $mission->diagnosticMatrix;
    }

    public function getProblemGrid(Mission $mission): ?ProblemStructuringGrid
    {
        return $mission->problemGrid;
    }

    public function getRestitutionDeck(Mission $mission): ?DiagnosticRestitutionDeck
    {
        return $mission->restitutionDeck;
    }

    public function getDecisionCapture(Mission $mission): ?LiveDecisionCapture
    {
        return $mission->decisionCapture;
    }

    /**
     * Initialise les catégories documentaires système si absentes.
     *
     * @return Collection<int, ArchiveCategory>
     */
    public function ensureDefaultCategories(): Collection
    {
        foreach (self::DEFAULT_CATEGORIES as $cat) {
            ArchiveCategory::firstOrCreate(
                ['code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'icon' => $cat['icon'],
                    'position' => $cat['position'],
                    'is_system' => true,
                ]
            );
        }

        return ArchiveCategory::orderBy('position')->get();
    }

    /**
     * Récupère l'ensemble des données des outils amont disponibles pour l'archivage.
     *
     * @return array<string, mixed>
     */
    public function getInitialData(Mission $mission): array
    {
        $framing = $this->getMissionFraming($mission);
        $scope = $this->getDiagnosticScope($mission);
        $intake = $this->getIntakeRegister($mission);
        $stakeholders = $this->getStakeholderGrid($mission);
        $matrix = $this->getDiagnosticMatrix($mission);
        $psg = $this->getProblemGrid($mission);
        $deck = $this->getRestitutionDeck($mission);
        $ldc = $this->getDecisionCapture($mission);

        return [
            'mission' => [
                'id' => $mission->id,
                'title' => $mission->title,
                'reference' => $mission->reference,
                'client' => $mission->framing?->request?->client?->name ?? $mission->framing?->client?->name,
            ],
            'tools_availability' => [
                'mfs' => $framing !== null,
                'dsc' => $scope !== null,
                'dir' => $intake !== null,
                'sig' => $stakeholders !== null,
                'd360' => $matrix !== null,
                'psg' => $psg !== null,
                'drd' => $deck !== null,
                'ldc' => $ldc !== null,
            ],
            'counts' => [
                'received_items' => $intake ? $intake->receivedItems()->count() : 0,
                'stakeholders' => $stakeholders ? $stakeholders->stakeholders()->count() : 0,
                'findings' => $matrix ? $matrix->findings()->count() : 0,
                'problems' => $psg ? $psg->problems()->count() : 0,
                'restitution_sections' => $deck ? $deck->sections()->count() : 0,
                'decisions' => $ldc ? $ldc->decisions()->count() : 0,
            ],
        ];
    }

    /**
     * Initialise ou charge le dossier Diagnostic Archive Pack™ pour la mission.
     */
    public function initializeArchive(Mission $mission, ?User $user = null): DiagnosticArchivePack
    {
        $this->ensureDefaultCategories();

        return DB::transaction(function () use ($mission, $user) {
            $ref = sprintf('DAP-%s', $mission->reference ?: ('MIS-'.$mission->id));

            $pack = DiagnosticArchivePack::firstOrCreate(
                ['mission_id' => $mission->id],
                [
                    'reference' => $ref,
                    'title' => 'Dossier d’Archive Diagnostique - '.$mission->title,
                    'description' => 'Conservation structurée de l’ensemble des données, pièces, évaluations et arbitrages du diagnostic 360°.',
                    'status' => 'in_progress',
                    'responsible_id' => $user?->id,
                    'completeness_score' => 0,
                ]
            );

            // Photographie d'entrée initiale
            $initialData = $this->getInitialData($mission);
            $pack->update([
                'input_snapshot' => [
                    'generated_at' => now()->toIso8601String(),
                    'mission_reference' => $initialData['mission']['reference'],
                    'tools_availability' => $initialData['tools_availability'],
                    'counts' => $initialData['counts'],
                ],
            ]);

            return $pack->load(['items.primaryCategory', 'items.categories', 'items.versions', 'items.sources']);
        });
    }

    /**
     * Auto-découvre et référence automatiquement tous les éléments issus des 8 outils du diagnostic 360°.
     *
     * @return array<string, int> Nombre d'éléments traités
     */
    public function collectAvailableElements(DiagnosticArchivePack $pack, ?User $user = null): array
    {
        $this->ensureDefaultCategories();
        $mission = $pack->mission;
        $categories = ArchiveCategory::all()->keyBy('code');

        $imported = [
            'mfs' => 0,
            'dsc' => 0,
            'dir' => 0,
            'sig' => 0,
            'd360' => 0,
            'psg' => 0,
            'drd' => 0,
            'ldc' => 0,
        ];

        DB::transaction(function () use ($pack, $mission, $categories, $user, &$imported) {
            // 1. Outil 01 - MFS (Mission Framing Sheet)
            $framing = $this->getMissionFraming($mission);
            if ($framing && ! $this->itemExists($pack, 'mfs', $framing->id)) {
                $item = $this->createAutoItem($pack, [
                    'category_id' => $categories['cadrage']?->id,
                    'title' => 'Cadrage Opérationnel de Mission (MFS)',
                    'item_type' => 'tool_snapshot',
                    'source_tool' => 'mfs',
                    'source_id' => $framing->id,
                    'source_reference' => 'MFS-'.$mission->id,
                    'description' => 'Fiche formalisant le cadre de référence opérationnel et les objectifs concrets de la mission.',
                    'location_type' => 'database_record',
                    'location_path' => 'missions/'.$mission->id.'/framing-sheet',
                    'metadata' => [
                        'objectives' => $framing->operational_objectives,
                        'specific_objective' => $framing->specific_objective,
                    ],
                ], $user);

                $this->linkSource($item, [
                    'source_tool' => 'mfs',
                    'source_type' => 'framing',
                    'source_id' => $framing->id,
                    'relationship_type' => 'origin',
                    'notes' => 'Cadre de référence initial',
                ]);
                $imported['mfs']++;
            }

            // 2. Outil 02 - DSC (Diagnostic Scope Canvas)
            $scope = $this->getDiagnosticScope($mission);
            if ($scope && ! $this->itemExists($pack, 'dsc', $scope->id)) {
                $item = $this->createAutoItem($pack, [
                    'category_id' => $categories['perimetre']?->id,
                    'title' => 'Périmètre Diagnostique & Délimitations (DSC)',
                    'item_type' => 'tool_snapshot',
                    'source_tool' => 'dsc',
                    'source_id' => $scope->id,
                    'source_reference' => 'DSC-'.$mission->id,
                    'description' => 'Cartographie des domaines et axes retenus pour le diagnostic avec niveaux de profondeur.',
                    'location_type' => 'database_record',
                    'location_path' => 'missions/'.$mission->id.'/scope-canvas',
                    'metadata' => [
                        'scope_items_count' => count($scope->scope_items ?? []),
                        'status' => $scope->status,
                    ],
                ], $user);

                $this->linkSource($item, [
                    'source_tool' => 'dsc',
                    'source_type' => 'scope_item',
                    'source_id' => $scope->id,
                    'relationship_type' => 'origin',
                    'notes' => 'Délimitation contractuelle du périmètre',
                ]);
                $imported['dsc']++;
            }

            // 3. Outil 03 - DIR (Diagnostic Intake Register & Pièces Collectées)
            $intake = $this->getIntakeRegister($mission);
            if ($intake) {
                foreach ($intake->receivedItems()->get() as $receivedItem) {
                    if (! $this->itemExists($pack, 'dir', $receivedItem->id)) {
                        $item = $this->createAutoItem($pack, [
                            'category_id' => $categories['collecte']?->id,
                            'title' => 'Pièce collectée : '.($receivedItem->title ?: 'Document sans titre'),
                            'item_type' => 'document',
                            'source_tool' => 'dir',
                            'source_id' => $receivedItem->id,
                            'source_reference' => 'DIR-ITEM-'.$receivedItem->id,
                            'description' => $receivedItem->notes ?? 'Pièce fournie dans le cadre de la collecte d’information.',
                            'location_type' => $receivedItem->file_path ? 'file_path' : 'database_record',
                            'location_path' => $receivedItem->file_path ?: ('intake-registers/items/'.$receivedItem->id),
                            'author_name' => $receivedItem->source_origin,
                            'produced_at' => $receivedItem->received_at,
                            'metadata' => [
                                'file_name' => $receivedItem->file_name,
                                'file_size' => $receivedItem->file_size,
                                'reliability' => $receivedItem->reliability_score,
                            ],
                        ], $user);

                        $this->linkSource($item, [
                            'source_tool' => 'dir',
                            'source_type' => 'intake_item',
                            'source_id' => $receivedItem->id,
                            'source_reference' => 'DIR-ITEM-'.$receivedItem->id,
                            'relationship_type' => 'evidence',
                            'notes' => 'Preuve documentaire collectée',
                        ]);
                        $imported['dir']++;
                    }
                }
            }

            // 4. Outil 04 - SIG (Stakeholder Input Grid & Contributions)
            $stakeholders = $this->getStakeholderGrid($mission);
            if ($stakeholders) {
                foreach ($stakeholders->stakeholders()->with('receivedContributions')->get() as $stk) {
                    if (! $this->itemExists($pack, 'sig', $stk->id)) {
                        $item = $this->createAutoItem($pack, [
                            'category_id' => $categories['consultations']?->id,
                            'title' => 'Entretien & Contributions : '.$stk->name.($stk->role_title ? ' ('.$stk->role_title.')' : ''),
                            'item_type' => 'contribution',
                            'source_tool' => 'sig',
                            'source_id' => $stk->id,
                            'source_reference' => $stk->reference,
                            'description' => $stk->influence_or_interest ?? 'Contributions recueillies lors des consultations d’acteurs clés.',
                            'location_type' => 'database_record',
                            'location_path' => 'stakeholders/'.$stk->id,
                            'author_name' => $stk->name,
                            'metadata' => [
                                'role' => $stk->role_title,
                                'entity' => $stk->organization_entity,
                                'contributions_count' => $stk->receivedContributions->count(),
                            ],
                        ], $user);

                        $this->linkSource($item, [
                            'source_tool' => 'sig',
                            'source_type' => 'stakeholder',
                            'source_id' => $stk->id,
                            'source_reference' => $stk->reference,
                            'relationship_type' => 'origin',
                            'notes' => 'Acteur interviewé et contributions associées',
                        ]);
                        $imported['sig']++;
                    }
                }
            }

            // 5. Outil 05 - D360 (360° Diagnostic Matrix & Constats)
            $matrix = $this->getDiagnosticMatrix($mission);
            if ($matrix && ! $this->itemExists($pack, 'd360', $matrix->id)) {
                $item = $this->createAutoItem($pack, [
                    'category_id' => $categories['analyses']?->id,
                    'title' => 'Matrice d’Évaluation Diagnostique 360° (D360)',
                    'item_type' => 'tool_snapshot',
                    'source_tool' => 'd360',
                    'source_id' => $matrix->id,
                    'source_reference' => 'D360-'.$mission->id,
                    'description' => 'Évaluation systématique des domaines, appréciations et constats probants.',
                    'location_type' => 'database_record',
                    'location_path' => 'missions/'.$mission->id.'/diagnostic-matrix',
                    'metadata' => [
                        'assessments_count' => $matrix->assessments()->count(),
                        'findings_count' => $matrix->findings()->count(),
                    ],
                ], $user);

                $this->linkSource($item, [
                    'source_tool' => 'd360',
                    'source_type' => 'finding',
                    'source_id' => $matrix->id,
                    'relationship_type' => 'evidence',
                    'notes' => 'Ensemble des cotations et constats probants',
                ]);
                $imported['d360']++;
            }

            // 6. Outil 06 - PSG (Problem Structuring Grid & Arbres des Causes)
            $psg = $this->getProblemGrid($mission);
            if ($psg) {
                foreach ($psg->problems()->get() as $problem) {
                    if (! $this->itemExists($pack, 'psg', $problem->id)) {
                        $item = $this->createAutoItem($pack, [
                            'category_id' => $categories['problemes']?->id,
                            'title' => 'Problème structuré : '.$problem->title,
                            'item_type' => 'data_record',
                            'source_tool' => 'psg',
                            'source_id' => $problem->id,
                            'source_reference' => $problem->reference,
                            'description' => $problem->description ?? 'Analyse causale et qualification du problème.',
                            'location_type' => 'database_record',
                            'location_path' => 'problem-grids/problems/'.$problem->id,
                            'metadata' => [
                                'domain' => $problem->domain,
                                'nature' => $problem->nature,
                                'causes_count' => $problem->causes()->count(),
                                'consequences_count' => $problem->consequences()->count(),
                            ],
                        ], $user);

                        $this->linkSource($item, [
                            'source_tool' => 'psg',
                            'source_type' => 'problem',
                            'source_id' => $problem->id,
                            'source_reference' => $problem->reference,
                            'relationship_type' => 'origin',
                            'notes' => 'Fiche problème structuré',
                        ]);
                        $imported['psg']++;
                    }
                }
            }

            // 7. Outil 07 - DRD (Diagnostic Restitution Deck & Versions)
            $deck = $this->getRestitutionDeck($mission);
            if ($deck) {
                if (! $this->itemExists($pack, 'drd', $deck->id)) {
                    $item = $this->createAutoItem($pack, [
                        'category_id' => $categories['restitutions']?->id,
                        'title' => 'Support & Trame de Restitution Diagnostique (DRD)',
                        'item_type' => 'tool_snapshot',
                        'source_tool' => 'drd',
                        'source_id' => $deck->id,
                        'source_reference' => 'DRD-'.$mission->id,
                        'description' => 'Trame éditoriale des 8 sections du diagnostic 360°.',
                        'location_type' => 'database_record',
                        'location_path' => 'missions/'.$mission->id.'/restitution-deck',
                        'metadata' => [
                            'sections_count' => $deck->sections()->count(),
                            'versions_count' => $deck->versions()->count(),
                        ],
                    ], $user);

                    $this->linkSource($item, [
                        'source_tool' => 'drd',
                        'source_type' => 'section',
                        'source_id' => $deck->id,
                        'relationship_type' => 'origin',
                        'notes' => 'Trame de restitution',
                    ]);
                    $imported['drd']++;
                }

                // Archiver également chaque version figée de la restitution
                foreach ($deck->versions()->get() as $version) {
                    if (! $this->itemExists($pack, 'drd_version', $version->id)) {
                        $item = $this->createAutoItem($pack, [
                            'category_id' => $categories['restitutions']?->id,
                            'title' => 'Livrable de Restitution - Version '.$version->version_number.' ('.$version->title.')',
                            'item_type' => 'restitution_version',
                            'source_tool' => 'drd',
                            'source_id' => $version->id,
                            'source_reference' => sprintf('DRD-V%02d', $version->version_number),
                            'current_version_number' => $version->version_number,
                            'description' => 'Photographie complète archivée de la restitution.',
                            'location_type' => 'database_record',
                            'location_path' => 'restitution-decks/versions/'.$version->id,
                            'produced_at' => $version->published_at ?? $version->created_at,
                            'metadata' => [
                                'version_number' => $version->version_number,
                            ],
                        ], $user);

                        $imported['drd']++;
                    }
                }
            }

            // 8. Outil 08 - LDC (Live Decision Capture & Décisions)
            $ldc = $this->getDecisionCapture($mission);
            if ($ldc) {
                foreach ($ldc->decisions()->get() as $decision) {
                    if (! $this->itemExists($pack, 'ldc', $decision->id)) {
                        $item = $this->createAutoItem($pack, [
                            'category_id' => $categories['decisions']?->id,
                            'title' => 'Décision actée ['.$decision->reference.'] : '.$decision->title,
                            'item_type' => 'decision',
                            'source_tool' => 'ldc',
                            'source_id' => $decision->id,
                            'source_reference' => $decision->reference,
                            'description' => $decision->decision_text,
                            'location_type' => 'database_record',
                            'location_path' => 'missions/'.$mission->id.'/decision-capture',
                            'produced_at' => $decision->decided_at,
                            'author_name' => $decision->decision_maker_name ?: $decision->decision_maker_type,
                            'metadata' => [
                                'nature' => $decision->nature,
                                'status' => $decision->status,
                                'maker_type' => $decision->decision_maker_type,
                            ],
                        ], $user);

                        $this->linkSource($item, [
                            'source_tool' => 'ldc',
                            'source_type' => 'decision',
                            'source_id' => $decision->id,
                            'source_reference' => $decision->reference,
                            'relationship_type' => 'justification',
                            'notes' => 'Arbitrage et engagement formel',
                        ]);
                        $imported['ldc']++;
                    }
                }
            }

            // Calcul et mise à jour de la complétude
            $this->checkArchiveCompleteness($pack);
        });

        return $imported;
    }

    /**
     * Vérifie si un élément source est déjà archivé dans ce pack.
     */
    private function itemExists(DiagnosticArchivePack $pack, string $sourceTool, int $sourceId): bool
    {
        return $pack->items()
            ->where('source_tool', $sourceTool)
            ->where('source_id', $sourceId)
            ->exists();
    }

    /**
     * Crée un élément archivé avec sa version initiale 1.
     *
     * @param  array<string, mixed>  $data
     */
    private function createAutoItem(DiagnosticArchivePack $pack, array $data, ?User $user = null): ArchiveItem
    {
        $nextNum = $pack->items()->count() + 1;
        $ref = sprintf('ARC-%03d', $nextNum);

        $item = $pack->items()->create([
            'category_id' => $data['category_id'] ?? null,
            'reference' => $ref,
            'title' => $data['title'],
            'item_type' => $data['item_type'] ?? 'data_record',
            'source_tool' => $data['source_tool'] ?? 'external',
            'source_id' => $data['source_id'] ?? null,
            'source_reference' => $data['source_reference'] ?? null,
            'current_version_number' => $data['current_version_number'] ?? 1,
            'author_name' => $data['author_name'] ?? ($user?->name ?? 'Cabinet CoreAssist'),
            'author_id' => $user?->id,
            'produced_at' => $data['produced_at'] ?? now(),
            'archived_at' => now(),
            'description' => $data['description'] ?? null,
            'location_type' => $data['location_type'] ?? 'database_record',
            'location_path' => $data['location_path'] ?? null,
            'storage_disk' => $data['storage_disk'] ?? 'local',
            'metadata' => $data['metadata'] ?? null,
            'status' => 'available',
        ]);

        if ($data['category_id'] ?? null) {
            $item->categories()->syncWithoutDetaching([$data['category_id']]);
        }

        // Version initiale v1.0
        $item->versions()->create([
            'version_number' => $data['current_version_number'] ?? 1,
            'version_tag' => 'v'.($data['current_version_number'] ?? 1).'.0-initial',
            'title' => $item->title,
            'description' => 'Archivage initial de l’élément.',
            'location_path' => $item->location_path,
            'payload_snapshot' => $item->metadata,
            'created_by' => $user?->id,
            'produced_at' => $item->produced_at,
            'archived_at' => now(),
            'changelog' => 'Intégration initiale dans le Diagnostic Archive Pack™.',
        ]);

        return $item;
    }

    /**
     * Enregistre manuellement un nouvel élément dans le dossier d'archive.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerArchiveItem(DiagnosticArchivePack $pack, array $data, ?User $user = null): ArchiveItem
    {
        return DB::transaction(function () use ($pack, $data, $user) {
            $nextNum = $pack->items()->count() + 1;
            $ref = $data['reference'] ?? sprintf('ARC-%03d', $nextNum);

            $item = $pack->items()->create([
                'category_id' => $data['category_id'] ?? null,
                'reference' => $ref,
                'title' => $data['title'],
                'item_type' => $data['item_type'] ?? 'document',
                'source_tool' => $data['source_tool'] ?? 'external',
                'source_id' => $data['source_id'] ?? null,
                'source_reference' => $data['source_reference'] ?? null,
                'current_version_number' => 1,
                'author_name' => $data['author_name'] ?? ($user?->name ?? 'Cabinet CoreAssist'),
                'author_id' => $user?->id,
                'produced_at' => $data['produced_at'] ?? now(),
                'archived_at' => now(),
                'description' => $data['description'] ?? null,
                'location_type' => $data['location_type'] ?? 'file_path',
                'location_path' => $data['location_path'] ?? null,
                'storage_disk' => $data['storage_disk'] ?? 'local',
                'metadata' => $data['metadata'] ?? null,
                'status' => $data['status'] ?? 'available',
            ]);

            // Catégories (primaire et secondaires)
            $categoryIds = [];
            if (! empty($data['category_id'])) {
                $categoryIds[] = (int) $data['category_id'];
            }
            if (! empty($data['categories'])) {
                $categoryIds = array_unique(array_merge($categoryIds, (array) $data['categories']));
            }
            if (! empty($categoryIds)) {
                $item->categories()->sync($categoryIds);
            }

            // Version 1 initiale
            $item->versions()->create([
                'version_number' => 1,
                'version_tag' => 'v1.0-initial',
                'title' => $item->title,
                'description' => $item->description,
                'location_path' => $item->location_path,
                'payload_snapshot' => $item->metadata,
                'created_by' => $user?->id,
                'produced_at' => $item->produced_at,
                'archived_at' => now(),
                'changelog' => 'Création initiale dans l’archive.',
            ]);

            $this->checkArchiveCompleteness($pack);

            return $item->fresh(['categories', 'versions', 'sources']);
        });
    }

    /**
     * Importe un document externe dans l'archive.
     *
     * @param  array<string, mixed>  $data
     */
    public function importDocument(DiagnosticArchivePack $pack, array $data, ?User $user = null): ArchiveItem
    {
        $defaultCat = ArchiveCategory::where('code', 'documents_complementaires')->first();

        $data['category_id'] = $data['category_id'] ?? $defaultCat?->id;
        $data['item_type'] = 'document';
        $data['source_tool'] = 'external';

        return $this->registerArchiveItem($pack, $data, $user);
    }

    /**
     * Relie un élément archivé à une source justificative d'amont.
     *
     * @param  array<string, mixed>  $sourceData
     */
    public function linkSource(ArchiveItem $item, array $sourceData): ArchiveSource
    {
        return $item->sources()->create([
            'source_tool' => $sourceData['source_tool'] ?? 'external',
            'source_type' => $sourceData['source_type'] ?? 'document',
            'source_id' => $sourceData['source_id'] ?? null,
            'source_reference' => $sourceData['source_reference'] ?? null,
            'relationship_type' => $sourceData['relationship_type'] ?? 'origin',
            'notes' => $sourceData['notes'] ?? null,
        ]);
    }

    /**
     * Enregistre une nouvelle version d'un élément d'archive sans écraser l'historique existant.
     *
     * @param  array<string, mixed>  $versionData
     */
    public function registerVersion(ArchiveItem $item, array $versionData, ?User $user = null): ArchiveVersion
    {
        return DB::transaction(function () use ($item, $versionData, $user) {
            $nextVersionNumber = $item->current_version_number + 1;
            $versionTag = $versionData['version_tag'] ?? ('v'.$nextVersionNumber.'.0');

            $version = $item->versions()->create([
                'version_number' => $nextVersionNumber,
                'version_tag' => $versionTag,
                'title' => $versionData['title'] ?? $item->title,
                'description' => $versionData['description'] ?? $item->description,
                'location_path' => $versionData['location_path'] ?? $item->location_path,
                'payload_snapshot' => $versionData['payload_snapshot'] ?? $item->metadata,
                'created_by' => $user?->id,
                'produced_at' => $versionData['produced_at'] ?? now(),
                'archived_at' => now(),
                'changelog' => $versionData['changelog'] ?? 'Mise à jour de version.',
            ]);

            // Mise à jour de l'item parent
            $item->update([
                'current_version_number' => $nextVersionNumber,
                'title' => $versionData['title'] ?? $item->title,
                'location_path' => $versionData['location_path'] ?? $item->location_path,
                'metadata' => $versionData['payload_snapshot'] ?? $item->metadata,
            ]);

            return $version;
        });
    }

    /**
     * Met à jour les métadonnées d'un élément archivé.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateArchiveItem(ArchiveItem $item, array $data): ArchiveItem
    {
        $item->update([
            'title' => $data['title'] ?? $item->title,
            'category_id' => $data['category_id'] ?? $item->category_id,
            'description' => $data['description'] ?? $item->description,
            'status' => $data['status'] ?? $item->status,
            'location_path' => $data['location_path'] ?? $item->location_path,
            'author_name' => $data['author_name'] ?? $item->author_name,
        ]);

        if (isset($data['categories'])) {
            $item->categories()->sync((array) $data['categories']);
        }

        return $item->fresh(['categories', 'versions', 'sources']);
    }

    /**
     * Associe un élément à une ou plusieurs catégories (règle de gestion multi-catégorie).
     *
     * @param  int|array<int, int>  $categoryIds
     */
    public function classifyArchiveItem(ArchiveItem $item, int|array $categoryIds): void
    {
        $ids = (array) $categoryIds;
        $item->categories()->sync($ids);

        if (! empty($ids) && ! in_array($item->category_id, $ids, true)) {
            $item->update(['category_id' => $ids[0]]);
        }
    }

    /**
     * Recherche dans les éléments archivés.
     *
     * @return Collection<int, ArchiveItem>
     */
    public function searchArchive(
        DiagnosticArchivePack $pack,
        ?string $query = null,
        ?string $categoryCode = null,
        ?string $tool = null
    ): Collection {
        $q = $pack->items()->with(['primaryCategory', 'categories', 'versions', 'sources']);

        if (! empty($query)) {
            $term = '%'.trim($query).'%';
            $q->where(function ($sub) use ($term) {
                $sub->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('reference', 'like', $term)
                    ->orWhere('author_name', 'like', $term);
            });
        }

        if (! empty($categoryCode)) {
            $q->whereHas('categories', function ($catQ) use ($categoryCode) {
                $catQ->where('code', $categoryCode);
            });
        }

        if (! empty($tool)) {
            $q->where('source_tool', $tool);
        }

        return $q->orderBy('reference')->get();
    }

    /**
     * Récupère l'historique complet des versions et des pièces archivées.
     *
     * @return Collection<int, ArchiveVersion>
     */
    public function getArchiveHistory(DiagnosticArchivePack $pack): Collection
    {
        return ArchiveVersion::whereIn('archive_item_id', $pack->items()->pluck('id'))
            ->with(['item.primaryCategory', 'creator'])
            ->orderByDesc('archived_at')
            ->get();
    }

    /**
     * Contrôle la complétude du dossier d'archive vis-à-vis des 8 outils attendus.
     *
     * @return array<string, mixed>
     */
    public function checkArchiveCompleteness(DiagnosticArchivePack $pack): array
    {
        $expectedTools = ['mfs', 'dsc', 'dir', 'sig', 'd360', 'psg', 'drd', 'ldc'];
        $toolsLabels = [
            'mfs' => 'Mission Framing Sheet™ (Cadrage)',
            'dsc' => 'Diagnostic Scope Canvas™ (Périmètre)',
            'dir' => 'Diagnostic Intake Register™ (Collecte)',
            'sig' => 'Stakeholder Input Grid™ (Consultations)',
            'd360' => '360° Diagnostic Matrix™ (Analyses)',
            'psg' => 'Problem Structuring Grid™ (Problèmes)',
            'drd' => 'Diagnostic Restitution Deck™ (Restitutions)',
            'ldc' => 'Live Decision Capture™ (Décisions)',
        ];

        $items = $pack->items()->get();
        $covered = [];
        $missing = [];

        foreach ($expectedTools as $tool) {
            $count = $items->where('source_tool', $tool)->count();
            if ($count > 0) {
                $covered[$tool] = [
                    'label' => $toolsLabels[$tool],
                    'items_count' => $count,
                    'status' => 'covered',
                ];
            } else {
                $missing[$tool] = [
                    'label' => $toolsLabels[$tool],
                    'status' => 'missing',
                ];
            }
        }

        $coveredCount = count($covered);
        $totalCount = count($expectedTools);
        $score = (int) round(($coveredCount / $totalCount) * 100);

        $pack->update([
            'completeness_score' => $score,
            'status' => ($score === 100 && $pack->status === 'in_progress') ? 'compiled' : $pack->status,
        ]);

        return [
            'completeness_score' => $score,
            'total_expected' => $totalCount,
            'covered_count' => $coveredCount,
            'is_fully_complete' => $score === 100,
            'covered_tools' => $covered,
            'missing_tools' => $missing,
        ];
    }

    /**
     * Clôture formelle du dossier d'archive.
     */
    public function closeArchive(DiagnosticArchivePack $pack, ?string $observations = null): DiagnosticArchivePack
    {
        $pack->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closure_observations' => $observations ?: 'Dossier d’archive diagnostique vérifié et clôturé.',
        ]);

        $this->generateSnapshot($pack);

        return $pack;
    }

    /**
     * Produit et enregistre l'instantané consolidé (`output_snapshot`) de l'archive.
     *
     * @return array<string, mixed>
     */
    public function generateSnapshot(DiagnosticArchivePack $pack): array
    {
        $items = $pack->items()->with(['primaryCategory', 'categories', 'versions', 'sources'])->get();
        $completeness = $this->checkArchiveCompleteness($pack);

        $itemsByCategory = [];
        foreach (ArchiveCategory::all() as $cat) {
            $catItems = $items->filter(fn ($it) => $it->category_id === $cat->id || $it->categories->contains('id', $cat->id));
            $itemsByCategory[$cat->code] = [
                'name' => $cat->name,
                'count' => $catItems->count(),
            ];
        }

        $snapshot = [
            'generated_at' => now()->toIso8601String(),
            'pack_reference' => $pack->reference,
            'mission_id' => $pack->mission_id,
            'mission_reference' => $pack->mission?->reference,
            'status' => $pack->status,
            'closed_at' => $pack->closed_at?->toIso8601String(),
            'completeness_score' => $completeness['completeness_score'],
            'total_items_count' => $items->count(),
            'total_versions_count' => ArchiveVersion::whereIn('archive_item_id', $items->pluck('id'))->count(),
            'categories_summary' => $itemsByCategory,
            'completeness_details' => $completeness,
            'inventory' => $items->map(fn (ArchiveItem $it) => [
                'reference' => $it->reference,
                'title' => $it->title,
                'item_type' => $it->item_type,
                'source_tool' => $it->source_tool,
                'source_reference' => $it->source_reference,
                'category' => $it->primaryCategory?->name,
                'current_version' => $it->current_version_number,
                'versions_count' => $it->versions->count(),
                'sources_count' => $it->sources->count(),
                'status' => $it->status,
                'archived_at' => $it->archived_at?->format('Y-m-d H:i'),
            ])->all(),
        ];

        $pack->update([
            'output_snapshot' => $snapshot,
        ]);

        return $snapshot;
    }
}
