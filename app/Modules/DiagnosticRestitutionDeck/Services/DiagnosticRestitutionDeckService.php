<?php

namespace App\Modules\DiagnosticRestitutionDeck\Services;

use App\Models\User;
use App\Modules\Diagnostic360Matrix\Models\Diagnostic360Matrix;
use App\Modules\DiagnosticIntakeRegister\Models\DiagnosticIntakeRegister;
use App\Modules\DiagnosticRestitutionDeck\Models\DiagnosticRestitutionDeck;
use App\Modules\DiagnosticRestitutionDeck\Models\RestitutionContent;
use App\Modules\DiagnosticRestitutionDeck\Models\RestitutionSection;
use App\Modules\DiagnosticRestitutionDeck\Models\RestitutionSource;
use App\Modules\DiagnosticRestitutionDeck\Models\RestitutionVersion;
use App\Modules\DiagnosticScopeCanvas\Models\DiagnosticScopeCanvas;
use App\Modules\MissionFramingSheet\Models\MissionFramingSheet;
use App\Modules\ProblemStructuringGrid\Models\ProblemStructuringGrid;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\StakeholderInputGrid\Models\StakeholderInputGrid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DiagnosticRestitutionDeckService
{
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

    /**
     * Récupère l'ensemble des données d'amont utiles au DRD sans aucune ressaisie.
     * Distingue rigoureusement : périmètre prévu, effectivement examiné, axes évalués, partiellement évalués et non évalués.
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

        // Périmètre prévu (DSC)
        $scopeItems = $scope ? collect($scope->scope_items ?? []) : collect();
        $includedScopeItems = $scopeItems->filter(fn ($i) => ($i['status'] ?? '') === 'included')->values();
        $excludedScopeItems = $scopeItems->filter(fn ($i) => ($i['status'] ?? '') === 'excluded')->values();

        // Évaluations réelles (D360)
        $assessments = $matrix ? $matrix->assessments()->with(['findings', 'evidences'])->get() : new Collection;
        $evaluatedAxes = $assessments->whereIn('status', ['assessed', 'validated'])->values();
        $inProgressAxes = $assessments->where('status', 'in_progress')->values();
        $pendingAxes = $assessments->where('status', 'pending')->values();

        // Synthèses et constats D360
        $domainSummaries = $matrix ? $matrix->domainSummaries()->get() : new Collection;
        $axisSummaries = $matrix ? $matrix->axisSummaries()->get() : new Collection;
        $findings = $matrix ? $matrix->findings()->get() : new Collection;
        $strengths = $findings->where('finding_type', 'strength')->values();
        $difficulties = $findings->where('finding_type', 'difficulty')->values();

        // Problèmes structurés PSG
        $problems = $psg ? $psg->problems()->with(['causes', 'consequences', 'priorityAssessment'])->get() : new Collection;
        $p1Problems = $problems->filter(fn ($p) => ($p->priorityAssessment?->priority_level ?? '') === 'P1')->values();
        $p2Problems = $problems->filter(fn ($p) => ($p->priorityAssessment?->priority_level ?? '') === 'P2')->values();

        return [
            'mission' => [
                'id' => $mission->id,
                'title' => $mission->title,
                'reference' => $mission->reference,
                'client' => $mission->framing?->client?->name,
                'objectives' => $framing?->operational_objectives,
                'expected_deliverables' => $framing?->expected_deliverables,
                'methodology' => $framing?->methodology ?? 'Diagnostic 360° CoreAssist',
            ],
            'scope_distinction' => [
                'planned_total_count' => $scopeItems->count(),
                'included_count' => $includedScopeItems->count(),
                'excluded_items' => $excludedScopeItems->all(),
                'evaluated_axes_count' => $evaluatedAxes->count(),
                'partially_evaluated_axes_count' => $inProgressAxes->count(),
                'non_evaluated_axes_count' => $pendingAxes->count(),
                'evaluated_axes' => $evaluatedAxes,
                'in_progress_axes' => $inProgressAxes,
                'pending_axes' => $pendingAxes,
            ],
            'intake' => [
                'received_documents_count' => $intake?->receivedItems()->count() ?? 0,
            ],
            'stakeholders' => [
                'interviewed_count' => $stakeholders?->receivedContributions()->count() ?? 0,
            ],
            'd360' => [
                'domain_summaries' => $domainSummaries,
                'axis_summaries' => $axisSummaries,
                'strengths' => $strengths,
                'difficulties' => $difficulties,
                'evidences_count' => $matrix?->evidences()->count() ?? 0,
            ],
            'psg' => [
                'total_problems' => $problems->count(),
                'p1_critical' => $p1Problems,
                'p2_high' => $p2Problems,
                'all_problems' => $problems,
                'relations_count' => $psg?->relations()->count() ?? 0,
            ],
        ];
    }

    /**
     * Initialise ou charge le Diagnostic Restitution Deck pour la mission,
     * et instancie automatiquement la structure éditoriale standard en 8 sections.
     */
    public function prepareRestitutionDeck(Mission $mission, ?User $user = null): DiagnosticRestitutionDeck
    {
        return DB::transaction(function () use ($mission, $user) {
            $initialData = $this->getInitialData($mission);

            $deck = DiagnosticRestitutionDeck::firstOrCreate(
                ['mission_id' => $mission->id],
                [
                    'title' => 'Diagnostic Restitution Deck™ - '.$mission->title,
                    'subtitle' => 'Restitution stratégique des conclusions du Diagnostic 360° & Trajectoire de redressement',
                    'status' => 'in_progress',
                    'created_by' => $user?->id,
                ]
            );

            // Mise à jour de l'instantané des entrées
            $deck->update([
                'input_snapshot' => [
                    'generated_at' => now()->toIso8601String(),
                    'mission_reference' => $initialData['mission']['reference'],
                    'included_axes' => $initialData['scope_distinction']['included_count'],
                    'evaluated_axes' => $initialData['scope_distinction']['evaluated_axes_count'],
                    'strengths_count' => count($initialData['d360']['strengths']),
                    'difficulties_count' => count($initialData['d360']['difficulties']),
                    'problems_count' => $initialData['psg']['total_problems'],
                ],
            ]);

            // Si aucune section n'existe, instancier la trame éditoriale standard recommandée
            if ($deck->sections()->count() === 0) {
                $this->seedStandardSections($deck, $initialData, $user);
            }

            return $deck->load(['sections.contents.sources', 'versions']);
        });
    }

    /**
     * Génère la trame standard des 8 sections éditoriales pré-remplies avec les données d'amont.
     *
     * @param  array<string, mixed>  $initialData
     */
    private function seedStandardSections(DiagnosticRestitutionDeck $deck, array $initialData, ?User $user = null): void
    {
        $standardSections = [
            [
                'position' => 1,
                'section_key' => 'presentation',
                'title' => '1. Présentation de la mission & Contexte',
                'objective' => 'Rappeler le mandat confié au cabinet, le contexte de l’organisation et les objectifs convenus.',
                'contents' => [
                    [
                        'content_type' => 'text',
                        'title' => 'Contexte d’intervention',
                        'body_text' => 'Mission initiée pour répondre aux enjeux de performance et de pérennité de l’entreprise '.($initialData['mission']['client'] ?? 'cliente').'.',
                    ],
                ],
            ],
            [
                'position' => 2,
                'section_key' => 'methodology',
                'title' => '2. Démarche diagnostique & Sources mobilisées',
                'objective' => 'Expliquer la méthode rigoureuse d’investigation, les pièces collectées (DIR) et les entretiens menés (SIG).',
                'contents' => [
                    [
                        'content_type' => 'callout',
                        'title' => 'Dispositif d’investigation du cabinet',
                        'body_text' => sprintf(
                            'La démarche a mobilisé %d document(s) probant(s) analysé(s) et %d contribution(s) recueillie(s) auprès des parties prenantes.',
                            $initialData['intake']['received_documents_count'],
                            $initialData['stakeholders']['interviewed_count']
                        ),
                    ],
                ],
            ],
            [
                'position' => 3,
                'section_key' => 'scope',
                'title' => '3. Périmètre d’investigation & Couverture réelle',
                'objective' => 'Distinguer en toute transparence le périmètre prévu, les axes audités en profondeur et les sujets exclus.',
                'contents' => [
                    [
                        'content_type' => 'summary_table',
                        'title' => 'Couverture effective des investigations',
                        'structured_data' => [
                            'planned_axes' => $initialData['scope_distinction']['included_count'],
                            'evaluated_axes' => $initialData['scope_distinction']['evaluated_axes_count'],
                            'partially_evaluated' => $initialData['scope_distinction']['partially_evaluated_axes_count'],
                            'excluded_items_count' => count($initialData['scope_distinction']['excluded_items']),
                        ],
                    ],
                ],
            ],
            [
                'position' => 4,
                'section_key' => 'general_synthesis',
                'title' => '4. Synthèse générale des résultats 360°',
                'objective' => 'Donner une vue d’ensemble immédiate sur les cotations et niveaux de maturité par grand domaine.',
                'contents' => [
                    [
                        'content_type' => 'domain_rating',
                        'title' => 'Cotation macroscopique par grand domaine',
                        'body_text' => 'Appréciation globale de la santé de l’organisation sur chacun des piliers analysés.',
                    ],
                ],
            ],
            [
                'position' => 5,
                'section_key' => 'domain_analysis',
                'title' => '5. Analyse détaillée & Points d’appui de l’organisation',
                'objective' => 'Mettre en valeur les forces démontrées de l’entreprise tout en situant les vulnérabilités par domaine.',
                'contents' => [
                    [
                        'content_type' => 'strengths_list',
                        'title' => 'Points forts & Compétences clés capitalisables',
                        'structured_data' => collect($initialData['d360']['strengths'])->pluck('title')->take(8)->all(),
                    ],
                ],
            ],
            [
                'position' => 6,
                'section_key' => 'problems',
                'title' => '6. Problèmes structurés & Analyse des causes racines',
                'objective' => 'Présenter les dysfonctionnements qualifiés dans le PSG en démontrant leurs causes prouvées et leurs impacts.',
                'contents' => [
                    [
                        'content_type' => 'problems_list',
                        'title' => 'Cartographie des problèmes structurés',
                        'body_text' => sprintf('Un ensemble de %d problème(s) majeur(s) a été formalisé avec leurs arbres des causes.', $initialData['psg']['total_problems']),
                    ],
                ],
            ],
            [
                'position' => 7,
                'section_key' => 'priorities',
                'title' => '7. Enjeux prioritaires d’arbitrage',
                'objective' => 'Mettre en exergue les urgences vitales (P1) et les chantiers structurants prioritaires (P2).',
                'contents' => [
                    [
                        'content_type' => 'callout',
                        'title' => 'Focalisation stratégique recommandée',
                        'body_text' => 'Concentration impérative des ressources sur les défaillances critiques conditionnant la pérennité de l’entreprise.',
                    ],
                ],
            ],
            [
                'position' => 8,
                'section_key' => 'conclusion',
                'title' => '8. Conclusion diagnostique & Trajectoire cible',
                'objective' => 'Délivrer une synthèse interprétative transversale pour préparer le dialogue décisionnel (LDC).',
                'contents' => [
                    [
                        'content_type' => 'conclusion_box',
                        'title' => 'Synthèse générale et enseignements majeurs du diagnostic',
                        'body_text' => 'Le diagnostic met en évidence un savoir-faire métier indéniable mais fragilisé par des processus de pilotage désynchronisés.',
                    ],
                ],
            ],
        ];

        foreach ($standardSections as $sData) {
            $section = $deck->sections()->create([
                'position' => $sData['position'],
                'section_key' => $sData['section_key'],
                'title' => $sData['title'],
                'objective' => $sData['objective'],
                'status' => 'to_prepare',
            ]);

            foreach ($sData['contents'] as $pos => $cData) {
                $section->contents()->create([
                    'position' => $pos + 1,
                    'content_type' => $cData['content_type'],
                    'title' => $cData['title'],
                    'body_text' => $cData['body_text'] ?? null,
                    'structured_data' => $cData['structured_data'] ?? null,
                    'is_custom_editorial' => false,
                    'created_by' => $user?->id,
                ]);
            }
        }
    }

    /**
     * Ajoute une nouvelle section dans le deck.
     *
     * @param  array<string, mixed>  $data
     */
    public function addSection(DiagnosticRestitutionDeck $deck, array $data): RestitutionSection
    {
        $position = $data['position'] ?? ($deck->sections()->max('position') + 1);

        return $deck->sections()->create([
            'position' => $position,
            'title' => $data['title'] ?? 'Nouvelle section',
            'section_key' => $data['section_key'] ?? null,
            'objective' => $data['objective'] ?? null,
            'status' => $data['status'] ?? 'to_prepare',
            'observations' => $data['observations'] ?? null,
        ]);
    }

    /**
     * Met à jour une section existante.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSection(RestitutionSection $section, array $data): RestitutionSection
    {
        $section->update([
            'title' => $data['title'] ?? $section->title,
            'objective' => $data['objective'] ?? $section->objective,
            'status' => $data['status'] ?? $section->status,
            'observations' => $data['observations'] ?? $section->observations,
        ]);

        return $section->fresh(['contents.sources']);
    }

    /**
     * Réorganise l'ordre des sections du deck.
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorderSections(DiagnosticRestitutionDeck $deck, array $orderedIds): void
    {
        DB::transaction(function () use ($deck, $orderedIds) {
            foreach ($orderedIds as $index => $id) {
                $deck->sections()->where('id', $id)->update(['position' => $index + 1]);
            }
        });
    }

    /**
     * Supprime une section et ses contenus associés.
     */
    public function removeSection(RestitutionSection $section): void
    {
        $section->delete();
    }

    /**
     * Ajoute un contenu éditorial dans une section.
     *
     * @param  array<string, mixed>  $data
     */
    public function addContent(RestitutionSection $section, array $data, ?User $user = null): RestitutionContent
    {
        $position = $data['position'] ?? ($section->contents()->max('position') + 1);

        return $section->contents()->create([
            'position' => $position,
            'content_type' => $data['content_type'] ?? 'text',
            'title' => $data['title'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'structured_data' => $data['structured_data'] ?? null,
            'is_custom_editorial' => (bool) ($data['is_custom_editorial'] ?? true),
            'created_by' => $user?->id,
        ]);
    }

    /**
     * Insère un résultat provenant d'un outil d'amont (D360, PSG, etc.) et conserve la traçabilité de sa source.
     *
     * @param  array<string, mixed>  $data
     */
    public function importToolResult(
        RestitutionSection $section,
        string $sourceTool,
        string $sourceType,
        int|string $sourceId,
        array $data,
        ?User $user = null
    ): RestitutionContent {
        return DB::transaction(function () use ($section, $sourceTool, $sourceType, $sourceId, $data, $user) {
            $content = $this->addContent($section, [
                'content_type' => $data['content_type'] ?? 'text',
                'title' => $data['title'] ?? 'Résultat importé',
                'body_text' => $data['body_text'] ?? null,
                'structured_data' => $data['structured_data'] ?? null,
                'is_custom_editorial' => false,
            ], $user);

            $this->linkContentSource($content, [
                'source_tool' => $sourceTool,
                'source_type' => $sourceType,
                'source_id' => is_numeric($sourceId) ? (int) $sourceId : null,
                'source_reference' => (string) $sourceId,
                'source_summary' => $data['source_summary'] ?? ($data['title'] ?? null),
                'notes' => $data['source_notes'] ?? null,
            ]);

            return $content->load('sources');
        });
    }

    /**
     * Associe formellement un contenu à sa source justificative.
     *
     * @param  array<string, mixed>  $sourceData
     */
    public function linkContentSource(RestitutionContent $content, array $sourceData): RestitutionSource
    {
        return $content->sources()->create([
            'source_tool' => $sourceData['source_tool'] ?? 'd360',
            'source_type' => $sourceData['source_type'] ?? 'finding',
            'source_id' => $sourceData['source_id'] ?? null,
            'source_reference' => $sourceData['source_reference'] ?? null,
            'source_summary' => $sourceData['source_summary'] ?? null,
            'notes' => $sourceData['notes'] ?? null,
        ]);
    }

    /**
     * Met à jour le texte et les données d'un contenu éditorial.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateContent(RestitutionContent $content, array $data): RestitutionContent
    {
        $content->update([
            'title' => $data['title'] ?? $content->title,
            'body_text' => $data['body_text'] ?? $content->body_text,
            'structured_data' => $data['structured_data'] ?? $content->structured_data,
            'is_custom_editorial' => $data['is_custom_editorial'] ?? $content->is_custom_editorial,
        ]);

        return $content->fresh('sources');
    }

    /**
     * Valide formellement une section par le chef de mission.
     */
    public function validateSection(RestitutionSection $section, ?User $user = null): RestitutionSection
    {
        $section->update([
            'status' => 'validated',
            'validated_by' => $user?->id,
            'validated_at' => now(),
        ]);

        return $section;
    }

    /**
     * Assemble l'ensemble des sections et contenus en un document de restitution consolidé.
     *
     * @return array<string, mixed>
     */
    public function consolidateRestitution(DiagnosticRestitutionDeck $deck): array
    {
        $sections = $deck->sections()->with(['contents.sources'])->get();

        return [
            'deck_id' => $deck->id,
            'mission_id' => $deck->mission_id,
            'title' => $deck->title,
            'subtitle' => $deck->subtitle,
            'readiness_percentage' => $deck->readinessPercentage(),
            'sections_count' => $sections->count(),
            'validated_sections_count' => $sections->where('status', 'validated')->count(),
            'sections' => $sections->map(fn (RestitutionSection $s) => [
                'position' => $s->position,
                'title' => $s->title,
                'objective' => $s->objective,
                'status' => $s->status,
                'contents' => $s->contents->map(fn (RestitutionContent $c) => [
                    'position' => $c->position,
                    'type' => $c->content_type,
                    'title' => $c->title,
                    'body' => $c->body_text,
                    'data' => $c->structured_data,
                    'sources' => $c->sources->map(fn ($src) => [
                        'tool' => $src->source_tool,
                        'reference' => $src->source_reference,
                        'summary' => $src->source_summary,
                    ])->all(),
                ])->all(),
            ])->all(),
        ];
    }

    /**
     * Génère l'instantané de sortie (output_snapshot) et crée une version figée de restitution (`RestitutionVersion`).
     *
     * @return array<string, mixed>
     */
    public function generateSnapshot(DiagnosticRestitutionDeck $deck, ?string $versionTitle = null, ?User $user = null): array
    {
        $consolidated = $this->consolidateRestitution($deck);

        $nextVersionNum = $deck->versions()->max('version_number') + 1;
        $title = $versionTitle ?: ('Restitution v'.$nextVersionNum.' - '.now()->format('d/m/Y'));

        $deck->update([
            'output_snapshot' => $consolidated,
            'status' => $deck->readinessPercentage() >= 100.0 ? 'ready_for_review' : 'in_progress',
        ]);

        $version = $deck->versions()->create([
            'version_number' => $nextVersionNum,
            'title' => $title,
            'summary' => sprintf(
                'Version consolidée comprenant %d section(s) dont %d validée(s).',
                $consolidated['sections_count'],
                $consolidated['validated_sections_count']
            ),
            'restitution_snapshot' => $consolidated,
            'created_by' => $user?->id,
            'published_at' => now(),
        ]);

        return [
            'snapshot' => $consolidated,
            'version' => $version,
        ];
    }
}
