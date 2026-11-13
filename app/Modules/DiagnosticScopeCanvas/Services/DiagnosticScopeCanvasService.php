<?php

namespace App\Modules\DiagnosticScopeCanvas\Services;

use App\Models\User;
use App\Modules\DiagnosticScopeCanvas\Models\DiagnosticScopeCanvas;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DiagnosticScopeCanvasService
{
    /**
     * Récupérer les informations de contexte de la mission et du Mission Framing Sheet.
     *
     * @return array<string, mixed>
     */
    public function getInitialData(Mission $mission): array
    {
        $mission->loadMissing([
            'framingSheet.responsible',
            'framing.request.client',
            'framing.latestVersion',
        ]);

        $framingSheet = $mission->framingSheet;
        $sheetSnapshot = $framingSheet?->output_snapshot ?? [];

        return [
            'mission' => [
                'id' => $mission->id,
                'reference' => $mission->reference,
                'title' => $mission->title,
                'objective' => $mission->objective,
            ],
            'framing_sheet' => [
                'sheet_id' => $framingSheet?->id,
                'status' => $framingSheet?->status,
                'specific_objective' => $framingSheet?->specific_objective ?? $sheetSnapshot['objectives']['specific_objective'] ?? null,
                'expected_results' => $framingSheet?->expected_results ?? $sheetSnapshot['objectives']['expected_results'] ?? null,
                'organization_context' => $sheetSnapshot['context']['organization_context'] ?? $framingSheet?->inherited_data['organization_context'] ?? null,
                'identified_problem' => $sheetSnapshot['context']['identified_problem'] ?? $framingSheet?->inherited_data['identified_problem'] ?? null,
                'general_objectives' => $sheetSnapshot['objectives']['general_objectives'] ?? $framingSheet?->inherited_data['general_objectives'] ?? null,
            ],
            'client' => $sheetSnapshot['client'] ?? [
                'name' => $mission->framing?->request?->client?->name,
                'sector' => $mission->framing?->request?->client?->sector,
            ],
        ];
    }

    /**
     * Référentiel méthodologique standard de diagnostic du cabinet (domaines, axes et sous-axes).
     *
     * @return array<string, array{label: string, axes: array<string, array{label: string, subaxes: array<string, string>}>}>
     */
    public function getScopeReference(): array
    {
        return [
            'finance' => [
                'label' => 'Finance & Rentabilité',
                'axes' => [
                    'profitability' => [
                        'label' => 'Rentabilité & Marges',
                        'subaxes' => [
                            'gross_margin' => 'Marge brute d’exploitation',
                            'net_margin' => 'Marge nette & structure de résultat',
                            'product_margin' => 'Rentabilité par ligne d’activité ou produit',
                        ],
                    ],
                    'cash_flow' => [
                        'label' => 'Trésorerie & BFR',
                        'subaxes' => [
                            'cash_management' => 'Gestion des liquidités & prévisionnel',
                            'wcr' => 'Besoin en fonds de roulement (BFR)',
                            'payment_terms' => 'Délais de paiement clients & fournisseurs',
                        ],
                    ],
                    'debt_structure' => [
                        'label' => 'Structure de financement & Endettement',
                        'subaxes' => [
                            'borrowing_capacity' => 'Capacité d’endettement & garanties',
                            'solvency' => 'Solvabilité & équilibre haut de bilan',
                        ],
                    ],
                    'fixed_costs' => [
                        'label' => 'Charges de structure & Investissements',
                        'subaxes' => [
                            'break_even' => 'Point mort & seuil de rentabilité',
                            'capex' => 'Politique et amortissement des investissements',
                        ],
                    ],
                ],
            ],

            'operations' => [
                'label' => 'Opérations & Production',
                'axes' => [
                    'production_process' => [
                        'label' => 'Processus de fabrication / exécution',
                        'subaxes' => [
                            'workflow' => 'Flux opérationnels et goulots d’étranglement',
                            'waste_rate' => 'Taux de rebut, gaspillage et pertes matières',
                        ],
                    ],
                    'procurement' => [
                        'label' => 'Achats & Approvisionnements',
                        'subaxes' => [
                            'inventory' => 'Gestion et rotation des stocks',
                            'supplier_relations' => 'Dépendance et conditions fournisseurs',
                        ],
                    ],
                    'capacity' => [
                        'label' => 'Productivité & Capacités',
                        'subaxes' => [
                            'equipment_utilization' => 'Taux d’utilisation des équipements',
                            'cycle_times' => 'Temps de cycle et respect des délais',
                        ],
                    ],
                ],
            ],

            'commercial' => [
                'label' => 'Commercial & Marché',
                'axes' => [
                    'offer_portfolio' => [
                        'label' => 'Portefeuille d’offres & Tarification',
                        'subaxes' => [
                            'product_mix' => 'Cohérence de gamme et mix-produits',
                            'pricing' => 'Politique tarifaire et élasticité-prix',
                        ],
                    ],
                    'sales_pipeline' => [
                        'label' => 'Clientèle & Performance commerciale',
                        'subaxes' => [
                            'customer_concentration' => 'Dépendance et concentration du portefeuille',
                            'retention' => 'Fidélisation et satisfaction clients',
                        ],
                    ],
                    'market_position' => [
                        'label' => 'Positionnement & Concurrence',
                        'subaxes' => [
                            'competitive_edge' => 'Avantage concurrentiel et différenciation',
                        ],
                    ],
                ],
            ],

            'organization' => [
                'label' => 'Organisation & Ressources Humaines',
                'axes' => [
                    'governance' => [
                        'label' => 'Gouvernance & Répartition des rôles',
                        'subaxes' => [
                            'org_chart' => 'Clarté des fiches de poste et délégations',
                            'decision_flow' => 'Processus de décision interne',
                        ],
                    ],
                    'human_resources' => [
                        'label' => 'Compétences & Climat social',
                        'subaxes' => [
                            'skills_versatility' => 'Polyvalence et dépendance aux personnes clés',
                            'turnover_absenteeism' => 'Turn-over, absentéisme et motivation',
                        ],
                    ],
                    'information_systems' => [
                        'label' => 'Outils & Système d’information',
                        'subaxes' => [
                            'software_tools' => 'Adéquation des outils de gestion et logiciels',
                            'data_reliability' => 'Fiabilité des données et indicateurs de pilotage',
                        ],
                    ],
                ],
            ],

            'strategy' => [
                'label' => 'Stratégie & Pilotage',
                'axes' => [
                    'strategic_vision' => [
                        'label' => 'Vision & Priorités d’entreprise',
                        'subaxes' => [
                            'strategic_goals' => 'Clarté de la stratégie à 3 ans',
                            'action_priorities' => 'Alignement des investissements avec les priorités',
                        ],
                    ],
                    'performance_monitoring' => [
                        'label' => 'Contrôle de gestion & Tableaux de bord',
                        'subaxes' => [
                            'kpi_dashboards' => 'Existence d’indicateurs hebdomadaires/mensuels',
                            'remedial_actions' => 'Capacité de réaction face aux dérives',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Construire les données initiales de périmètre pour la mission.
     *
     * @return array<string, mixed>
     */
    public function prepareScope(Mission $mission): array
    {
        $initialData = $this->getInitialData($mission);
        $reference = $this->getScopeReference();

        // Récupérer le canvas existant s'il existe
        $existingCanvas = $mission->scopeCanvas;
        $items = $existingCanvas?->scope_items ?? [];

        return [
            'mission_id' => $mission->id,
            'initial_data' => $initialData,
            'reference' => $reference,
            'scope_items' => $items,
        ];
    }

    /**
     * Valider la cohérence du périmètre selon les règles métier du cabinet.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     *
     * @throws InvalidArgumentException
     */
    public function validateScope(array $items): array
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Le périmètre doit comporter au moins un élément.');
        }

        $validatedItems = [];
        $hasAtLeastOneIncluded = false;

        foreach ($items as $index => $item) {
            $id = ! empty($item['id']) ? (string) $item['id'] : (string) Str::uuid();
            $domain = trim((string) ($item['domain'] ?? ''));
            $axis = trim((string) ($item['axis'] ?? ''));
            $subaxis = ! empty($item['subaxis']) ? trim((string) $item['subaxis']) : null;
            $element = ! empty($item['element']) ? trim((string) $item['element']) : null;

            if ($domain === '' || $axis === '') {
                throw new InvalidArgumentException('Chaque élément de périmètre doit avoir un domaine et un axe renseignés (élément n°'.($index + 1).').');
            }

            $status = (string) ($item['status'] ?? 'to_confirm');
            if (! in_array($status, ['included', 'excluded', 'to_confirm'], true)) {
                throw new InvalidArgumentException("Le statut '{$status}' est invalide. Valeurs autorisées: included, excluded, to_confirm.");
            }

            $justification = ! empty($item['justification']) ? trim((string) $item['justification']) : null;

            // Règle métier : un élément exclu doit obligatoirement avoir une justification
            if ($status === 'excluded' && empty($justification)) {
                throw new InvalidArgumentException("L'élément '{$axis}' (Domaine {$domain}) est exclu : une justification explicite est obligatoire.");
            }

            $depth = (string) ($item['depth'] ?? 'standard');
            if (! in_array($depth, ['standard', 'approfondi', 'rapide'], true)) {
                $depth = 'standard';
            }

            if ($status === 'included') {
                $hasAtLeastOneIncluded = true;
            }

            $validatedItems[] = [
                'id' => $id,
                'domain' => $domain,
                'axis' => $axis,
                'subaxis' => $subaxis,
                'element' => $element,
                'depth' => $depth,
                'status' => $status,
                'justification' => $justification,
                'observation' => ! empty($item['observation']) ? trim((string) $item['observation']) : null,
            ];
        }

        if (! $hasAtLeastOneIncluded) {
            throw new InvalidArgumentException('Le périmètre doit comporter au moins un élément avec le statut Inclus.');
        }

        return $validatedItems;
    }

    /**
     * Enregistrer ou mettre à jour le Diagnostic Scope Canvas.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveScope(Mission $mission, array $data, ?User $user = null): DiagnosticScopeCanvas
    {
        $rawItems = $data['scope_items'] ?? $data['items'] ?? [];
        $validatedItems = $this->validateScope($rawItems);

        $initialData = $this->getInitialData($mission);

        /** @var DiagnosticScopeCanvas $canvas */
        $canvas = $mission->scopeCanvas()->firstOrNew(['mission_id' => $mission->id]);

        $inputSnapshot = [
            'initial_data' => $initialData,
            'submitted_at' => now()->toIso8601String(),
            'prepared_by' => $user?->id,
        ];

        $canvas->fill([
            'title' => $data['title'] ?? $canvas->title ?? 'Diagnostic Scope Canvas™',
            'scope_items' => $validatedItems,
            'input_snapshot' => $inputSnapshot,
            'created_by' => $canvas->created_by ?? $user?->id,
        ]);

        $canvas->output_snapshot = $this->generateSnapshot($canvas);
        $canvas->save();

        return $canvas->fresh(['creator', 'validator']);
    }

    /**
     * Produire une photographie complète du périmètre retenu.
     *
     * @return array<string, mixed>
     */
    public function generateSnapshot(DiagnosticScopeCanvas $canvas): array
    {
        $mission = $canvas->mission;

        $included = $canvas->included_items;
        $excluded = $canvas->excluded_items;
        $toConfirm = $canvas->to_confirm_items;

        $depthCounts = [
            'standard' => 0,
            'approfondi' => 0,
            'rapide' => 0,
        ];

        foreach ($included as $item) {
            $depth = $item['depth'] ?? 'standard';
            if (isset($depthCounts[$depth])) {
                $depthCounts[$depth]++;
            }
        }

        return [
            'canvas_id' => $canvas->id,
            'mission' => [
                'id' => $mission?->id ?? $canvas->mission_id,
                'reference' => $mission?->reference,
                'title' => $mission?->title,
            ],
            'title' => $canvas->title,
            'status' => $canvas->status,
            'summary' => [
                'total_items' => count($canvas->scope_items ?? []),
                'included_count' => count($included),
                'excluded_count' => count($excluded),
                'to_confirm_count' => count($toConfirm),
                'included_domains' => $canvas->included_domains,
                'depth_breakdown' => $depthCounts,
            ],
            'included_items' => $included,
            'excluded_items' => $excluded,
            'to_confirm_items' => $toConfirm,
            'snapshot_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Récupérer le canvas de périmètre associé à la mission.
     */
    public function getScope(Mission $mission): ?DiagnosticScopeCanvas
    {
        return $mission->scopeCanvas()
            ->with(['creator', 'validator'])
            ->first();
    }

    /**
     * Soumettre le périmètre pour revue et validation.
     */
    public function submitForReview(DiagnosticScopeCanvas $canvas, ?User $user = null): DiagnosticScopeCanvas
    {
        // Re-valider que le périmètre est cohérent
        $this->validateScope($canvas->scope_items ?? []);

        $canvas->status = 'submitted';
        $canvas->submitted_at = now();
        $canvas->output_snapshot = $this->generateSnapshot($canvas);
        $canvas->save();

        return $canvas;
    }

    /**
     * Valider officiellement le périmètre d'investigation.
     */
    public function validateReview(DiagnosticScopeCanvas $canvas, User $validator): DiagnosticScopeCanvas
    {
        $canvas->status = 'validated';
        $canvas->validated_at = now();
        $canvas->validated_by = $validator->id;
        $canvas->output_snapshot = $this->generateSnapshot($canvas);
        $canvas->save();

        return $canvas;
    }
}
