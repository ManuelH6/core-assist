<?php

namespace Database\Seeders;

use App\Enums\ToolVersionStatus;
use App\Models\User;
use App\Modules\ToolManagement\Http\Models\Tool;
use App\Modules\ToolManagement\Http\Models\ToolVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagnostic360ToolsSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::query()->orderBy('id')->value('id');

        if (! $creatorId) {
            throw new RuntimeException(
                'Aucun utilisateur trouvé. Exécutez le seeder des utilisateurs avant celui des outils.'
            );
        }

        $tools = $this->tools();

        DB::transaction(function () use ($tools, $creatorId) {
            foreach ($tools as $data) {
                $tool = Tool::firstOrCreate(
                    ['code' => $data['code']],
                    [
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'purpose' => $data['purpose'],
                        'category' => 'atomic',
                        'status' => 'active',
                        'created_by' => $creatorId,
                    ]
                );

                $version = ToolVersion::firstOrCreate(
                    [
                        'tool_id' => $tool->id,
                        'version_number' => '1.0',
                    ],
                    [
                        'definition' => $data['definition'],
                        'configuration_schema' => null,
                        'business_rules' => null,
                        'interface_type' => $data['interface_type'],
                        'output_definition' => $data['output_definition'],
                        'status' => ToolVersionStatus::Published,
                        'published_at' => now(),
                        'created_by' => $creatorId,
                    ]
                );

                if ($version->status !== ToolVersionStatus::Published) {
                    throw new RuntimeException(
                        "La version 1.0 de l'outil {$tool->code} existe, mais n'est pas publiée."
                    );
                }

                // Ne remplace pas une version courante déjà définie.
                if (! $tool->current_version_id) {
                    $tool->current_version_id = $version->id;
                    $tool->save();
                }
            }
        });
    }

    private function tools(): array
    {
        return [
            [
                'code' => 'D360-MFS',
                'name' => 'Mission Framing Sheet™',
                'description' => 'Formalisation du cadre opérationnel de la mission.',
                'purpose' => 'Reprendre les éléments essentiels de la demande et du cadrage pour établir le cadre de référence de la mission.',
                'interface_type' => 'sheet',
                'definition' => [
                    'purpose' => 'Formaliser le cadre opérationnel de la mission.',
                    'principles' => [
                        'Réutiliser les informations issues de la demande et du cadrage.',
                        'Distinguer les informations héritées des informations ajoutées pour la mission.',
                        'Conserver la traçabilité des adaptations.',
                    ],
                    'sections' => [
                        'client_and_context',
                        'initial_need',
                        'mission_objectives',
                        'operational_conditions',
                        'mission_specific_information',
                    ],
                ],
                'output_definition' => [
                    'type' => 'mission_framing',
                    'description' => 'Cadre opérationnel de référence de la mission.',
                ],
            ],

            [
                'code' => 'D360-DSC',
                'name' => 'Diagnostic Scope Canvas™',
                'description' => 'Définition du périmètre réel du diagnostic.',
                'purpose' => 'Définir les domaines, axes, sous-axes et niveaux d’approfondissement couverts par le diagnostic.',
                'interface_type' => 'canvas',
                'definition' => [
                    'purpose' => 'Définir et visualiser le périmètre du diagnostic.',
                    'principles' => [
                        'Sélectionner les domaines et axes pertinents.',
                        'Préciser la profondeur d’analyse attendue.',
                        'Identifier explicitement les éléments inclus et exclus.',
                        'Permettre l’adaptation de modèles existants.',
                    ],
                    'sections' => [
                        'diagnostic_domains',
                        'axes_and_sub_axes',
                        'analysis_depth',
                        'included_elements',
                        'excluded_elements',
                    ],
                ],
                'output_definition' => [
                    'type' => 'diagnostic_scope',
                    'description' => 'Périmètre structuré du diagnostic.',
                ],
            ],

            [
                'code' => 'D360-DIR',
                'name' => 'Diagnostic Intake Register™',
                'description' => 'Registre des informations recherchées et de leur collecte.',
                'purpose' => 'Recenser les informations nécessaires au diagnostic et suivre leur obtention.',
                'interface_type' => 'register',
                'definition' => [
                    'purpose' => 'Organiser les besoins d’information du diagnostic.',
                    'principles' => [
                        'Distinguer information recherchée, source attendue et élément reçu.',
                        'Permettre plusieurs éléments reçus pour une même information.',
                        'Ne pas considérer automatiquement un élément reçu comme vérifié.',
                        'Permettre à un élément reçu d’alimenter plusieurs informations recherchées.',
                    ],
                    'fields' => [
                        'domain_or_axis',
                        'information_required',
                        'justification',
                        'expected_source',
                        'collection_method',
                        'priority',
                        'responsible',
                        'status',
                        'observations',
                    ],
                ],
                'output_definition' => [
                    'type' => 'information_intake_register',
                    'description' => 'Registre de suivi des informations nécessaires au diagnostic.',
                ],
            ],

            [
                'code' => 'D360-SIG',
                'name' => 'Stakeholder Input Grid™',
                'description' => 'Identification des parties prenantes et de leurs contributions attendues.',
                'purpose' => 'Identifier les parties prenantes à mobiliser et les contributions qu’elles peuvent apporter au diagnostic.',
                'interface_type' => 'grid',
                'definition' => [
                    'purpose' => 'Structurer la mobilisation des parties prenantes.',
                    'principles' => [
                        'Distinguer une partie prenante identifiée d’une contribution effectivement obtenue.',
                        'Préciser les domaines sur lesquels chaque partie prenante peut contribuer.',
                        'Confronter les contributions aux documents et observations disponibles.',
                    ],
                    'fields' => [
                        'stakeholder',
                        'stakeholder_type',
                        'role',
                        'concerned_domains',
                        'expected_contribution',
                        'importance',
                        'mobilization_method',
                        'accessibility',
                    ],
                ],
                'output_definition' => [
                    'type' => 'stakeholder_contribution_map',
                    'description' => 'Matrice des parties prenantes et contributions attendues.',
                ],
            ],

            [
                'code' => 'D360-D360',
                'name' => '360° Diagnostic Matrix™',
                'description' => 'Documentation et évaluation de la situation par domaine et axe.',
                'purpose' => 'Analyser la situation de l’organisation à partir des critères et éléments de preuve disponibles.',
                'interface_type' => 'matrix',
                'definition' => [
                    'purpose' => 'Documenter et apprécier la situation diagnostiquée.',
                    'principles' => [
                        'Relier les appréciations aux éléments justificatifs disponibles.',
                        'Distinguer points forts, insuffisances et éléments à approfondir.',
                        'Ne pas assimiler automatiquement une absence d’information à une faiblesse.',
                        'Utiliser un score uniquement lorsqu’il est pertinent.',
                    ],
                    'fields' => [
                        'domain_or_axis',
                        'criterion',
                        'observed_situation',
                        'supporting_evidence',
                        'assessment',
                        'strengths',
                        'shortcomings',
                        'score',
                        'points_to_investigate',
                    ],
                ],
                'output_definition' => [
                    'type' => 'diagnostic_assessment_matrix',
                    'description' => 'Matrice des constats et appréciations du diagnostic.',
                ],
            ],

            [
                'code' => 'D360-PSG',
                'name' => 'Problem Structuring Grid™',
                'description' => 'Structuration des problèmes, causes, conséquences et priorités.',
                'purpose' => 'Organiser les problèmes identifiés et analyser leurs relations pour faciliter leur hiérarchisation.',
                'interface_type' => 'grid',
                'definition' => [
                    'purpose' => 'Structurer les problèmes issus du diagnostic.',
                    'principles' => [
                        'Distinguer problème, manifestation, cause et conséquence.',
                        'Identifier les relations entre les problèmes.',
                        'Conserver le caractère hypothétique des causes non confirmées.',
                        'Permettre à un problème de concerner plusieurs domaines.',
                    ],
                    'fields' => [
                        'problem',
                        'manifestations',
                        'potential_causes',
                        'consequences',
                        'related_domains',
                        'relationships',
                        'priority',
                        'evidence',
                    ],
                ],
                'output_definition' => [
                    'type' => 'problem_structure',
                    'description' => 'Structure organisée des problèmes et de leurs relations.',
                ],
            ],

            [
                'code' => 'D360-DRD',
                'name' => 'Diagnostic Restitution Deck™',
                'description' => 'Composition structurée de la restitution des résultats du diagnostic.',
                'purpose' => 'Assembler les principaux résultats du diagnostic dans un support de restitution structuré.',
                'interface_type' => 'deck',
                'definition' => [
                    'purpose' => 'Construire le support de restitution du diagnostic.',
                    'principles' => [
                        'Réutiliser les résultats issus des outils d’analyse.',
                        'Maintenir les références vers les analyses sources.',
                        'Identifier les contenus concernés lorsqu’un constat source évolue.',
                        'Permettre plusieurs formes de présentation des résultats.',
                    ],
                    'sections' => [
                        'executive_summary',
                        'scope_and_methodology',
                        'strengths',
                        'key_findings',
                        'priority_problems',
                        'analysis_and_explanations',
                        'recommendations',
                        'conclusion',
                    ],
                    'content_types' => [
                        'text',
                        'table',
                        'chart',
                        'diagram',
                    ],
                ],
                'output_definition' => [
                    'type' => 'diagnostic_restitution',
                    'description' => 'Support structuré de restitution des résultats.',
                ],
            ],

            [
                'code' => 'D360-LDC',
                'name' => 'Live Decision Capture',
                'description' => 'Enregistrement des décisions et engagements issus des échanges.',
                'purpose' => 'Consigner les décisions, engagements, désaccords et demandes de précision formulés pendant les échanges.',
                'interface_type' => 'capture',
                'definition' => [
                    'purpose' => 'Tracer les éléments importants issus des échanges.',
                    'principles' => [
                        'Distinguer proposition, décision et engagement.',
                        'Conserver les désaccords et demandes de précision.',
                        'Ne pas confondre décision prise et action effectivement réalisée.',
                    ],
                    'fields' => [
                        'meeting_or_exchange',
                        'date',
                        'subject',
                        'proposition',
                        'decision',
                        'commitment',
                        'disagreement',
                        'clarification_request',
                        'deferred_item',
                        'responsible',
                    ],
                ],
                'output_definition' => [
                    'type' => 'decision_record',
                    'description' => 'Registre des décisions et engagements issus des échanges.',
                ],
            ],

            [
                'code' => 'D360-DAP',
                'name' => 'Diagnostic Archive Pack™',
                'description' => 'Constitution du dossier documentaire et de traçabilité du diagnostic.',
                'purpose' => 'Organiser et conserver les éléments documentaires et les traces utiles du diagnostic.',
                'interface_type' => 'archive',
                'definition' => [
                    'purpose' => 'Constituer le dossier d’archive du diagnostic.',
                    'principles' => [
                        'Organiser les éléments par catégories.',
                        'Permettre un archivage progressif.',
                        'Conserver les références et versions des éléments utilisés.',
                        'Ne pas recréer les données déjà produites par les autres outils.',
                    ],
                    'sections' => [
                        'mission_documents',
                        'collection_material',
                        'analysis_material',
                        'decisions_and_reviews',
                        'deliverables',
                        'archive_metadata',
                    ],
                ],
                'output_definition' => [
                    'type' => 'diagnostic_archive',
                    'description' => 'Dossier documentaire structuré et traçable.',
                ],
            ],

            [
                'code' => 'D360-NSR',
                'name' => 'Next-Step Router™',
                'description' => 'Organisation des suites possibles à donner aux résultats du diagnostic.',
                'purpose' => 'Structurer les orientations possibles à partir des résultats, priorités et décisions du client.',
                'interface_type' => 'router',
                'definition' => [
                    'purpose' => 'Organiser les suites envisageables après le diagnostic.',
                    'principles' => [
                        'Relier les orientations aux résultats et priorités du diagnostic.',
                        'Distinguer orientation proposée et orientation retenue.',
                        'Ne pas considérer une orientation comme une mission automatiquement engagée.',
                    ],
                    'orientation_types' => [
                        'client_action',
                        'cabinet_support',
                        'additional_study',
                        'external_partner',
                        'deferred_decision',
                    ],
                    'fields' => [
                        'related_finding',
                        'priority',
                        'proposed_orientation',
                        'selected_orientation',
                        'decision_status',
                        'comments',
                    ],
                ],
                'output_definition' => [
                    'type' => 'next_step_orientation',
                    'description' => 'Orientations et suites possibles du diagnostic.',
                ],
            ],
        ];
    }
}
