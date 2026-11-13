<?php

namespace Database\Seeders;

use App\Enums\ClientType;
use App\Enums\FramingStatus;
use App\Enums\MissionRole;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\ClientRelationship\Http\Models\Client;
use App\Modules\ClientRelationship\Http\Models\Framing;
use App\Modules\ClientRelationship\Http\Models\FramingVersion;
use App\Modules\ClientRelationship\Http\Models\InitialUnderstanding;
use App\Modules\ClientRelationship\Http\Models\Request as ClientRequest;
use App\Modules\DiagnosticScopeCanvas\Services\DiagnosticScopeCanvasService;
use App\Modules\MissionFramingSheet\Services\MissionFramingSheetService;
use App\Modules\ProjectManagement\Http\Models\Collaborator;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\ToolManagement\Services\MissionToolConfigurationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoreAssistDemoSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        // 1. Utilisateurs du cabinet
        $dg = User::updateOrCreate(
            ['email' => 'dg@coreassist.test'],
            ['name' => 'Hélène Kouassi', 'password' => $password, 'role' => UserRole::Dg, 'client_id' => null]
        );

        $manager = User::updateOrCreate(
            ['email' => 'manager@coreassist.test'],
            ['name' => 'Marc-Antoine Dossou', 'password' => $password, 'role' => UserRole::Manager, 'client_id' => null]
        );

        $lead = User::updateOrCreate(
            ['email' => 'chef@coreassist.test'],
            ['name' => 'Sophie Mensah', 'password' => $password, 'role' => UserRole::MissionLead, 'client_id' => null]
        );

        $consultant = User::updateOrCreate(
            ['email' => 'consultant@coreassist.test'],
            ['name' => 'Paul Consultant', 'password' => $password, 'role' => UserRole::Consultant, 'client_id' => null]
        );

        $expert = User::updateOrCreate(
            ['email' => 'expert@coreassist.test'],
            ['name' => 'Nadia Soglo', 'password' => $password, 'role' => UserRole::Expert, 'client_id' => null]
        );

        // Collaborateurs associés pour le staffing
        $managerCollab = Collaborator::firstOrCreate(
            ['email' => 'manager@coreassist.test'],
            ['name' => $manager->name, 'role_title' => 'Manager de portefeuille', 'weekly_capacity_hours' => 40, 'hourly_rate' => 15000, 'is_active' => true]
        );

        Collaborator::firstOrCreate(
            ['email' => 'chef@coreassist.test'],
            ['name' => $lead->name, 'role_title' => 'Cheffe de mission', 'weekly_capacity_hours' => 40, 'hourly_rate' => 12000, 'is_active' => true]
        );

        Collaborator::firstOrCreate(
            ['email' => 'consultant@coreassist.test'],
            ['name' => $consultant->name, 'role_title' => 'Consultant Senior', 'weekly_capacity_hours' => 35, 'hourly_rate' => 8500, 'is_active' => true]
        );

        // 2. Clients et comptes d'accès Portail Client
        $savanes = Client::firstOrCreate(
            ['name' => 'Atelier Textile des Savanes'],
            [
                'client_type' => ClientType::Entreprise,
                'sector' => 'Industrie & Confection',
                'email' => 'direction@atelier-savanes.test',
                'phone' => '+229 97 11 22 33',
                'address' => 'Zone Industrielle, Cotonou',
                'status' => 'active',
            ]
        );

        $boulangerie = Client::firstOrCreate(
            ['name' => 'Boulangerie Moderne SARL'],
            [
                'client_type' => ClientType::Entreprise,
                'sector' => 'Agroalimentaire & Distribution',
                'email' => 'contact@boulangerie-moderne.test',
                'phone' => '+229 96 44 55 66',
                'address' => 'Avenue Steinmetz, Cotonou',
                'status' => 'active',
            ]
        );

        $clientSavanesUser = User::updateOrCreate(
            ['email' => 'client.savanes@coreassist.test'],
            ['name' => 'Awa Koné (DG Savanes)', 'password' => $password, 'role' => UserRole::Client, 'client_id' => $savanes->id]
        );

        $clientBoulangerieUser = User::updateOrCreate(
            ['email' => 'client.boulangerie@coreassist.test'],
            ['name' => 'Jean-Luc Zinsou (Boulangerie Moderne)', 'password' => $password, 'role' => UserRole::Client, 'client_id' => $boulangerie->id]
        );

        // 3. Demandes à différents stades
        // A. Nouvelle demande soumise depuis le portail client (statut : Reçue)
        $req1 = ClientRequest::firstOrCreate(
            ['reference' => 'REQ-2026-101'],
            [
                'client_id' => $boulangerie->id,
                'title' => 'Audit de rentabilité des 4 points de vente',
                'description' => 'Nous constatons une baisse de marge nette depuis 6 mois malgré un chiffre d\'affaires stable sur nos 4 boutiques.',
                'source_channel' => 'portail_client',
                'received_at' => now()->subDays(2),
                'status' => RequestStatus::Received,
                'created_by' => $clientBoulangerieUser->id,
            ]
        );

        InitialUnderstanding::firstOrCreate(
            ['request_id' => $req1->id],
            [
                'context' => 'Réseau de 4 boulangeries-pâtisseries employant 38 salariés.',
                'expressed_need' => $req1->description,
                'expected_outcome' => 'Identifier les postes de dérive de coûts et rétablir la marge sous 8 semaines.',
                'prepared_by' => $clientBoulangerieUser->id,
            ]
        );

        // B. Demande avec proposition de cadrage soumise au client (en attente de décision sur le portail)
        $req2 = ClientRequest::firstOrCreate(
            ['reference' => 'REQ-2026-102'],
            [
                'client_id' => $boulangerie->id,
                'title' => 'Optimisation du BFR et des délais fournisseurs',
                'description' => 'Tensions récurrentes de trésorerie en fin de mois liées au décalage entre achats de matières premières et encaissements.',
                'source_channel' => 'portail_client',
                'received_at' => now()->subDays(6),
                'status' => RequestStatus::Framed,
                'created_by' => $clientBoulangerieUser->id,
            ]
        );

        InitialUnderstanding::firstOrCreate(
            ['request_id' => $req2->id],
            [
                'context' => 'Achats centralisés auprès de 6 minoteries et fournisseurs locaux.',
                'expressed_need' => $req2->description,
                'reformulated_need' => 'Diagnostiquer le cycle d\'exploitation, les stocks de sécurité et les conditions de règlement.',
                'central_question' => 'Comment réduire le BFR de 20% sans perturber l\'approvisionnement quotidien ?',
                'known_constraints' => ['Maintien de la continuité d\'approvisionnement'],
                'missing_information' => ['Balance âgée fournisseurs des 6 derniers mois'],
                'prepared_by' => $manager->id,
                'completed_at' => now()->subDays(4),
            ]
        );

        $framingPending = Framing::firstOrCreate(
            ['reference' => 'CAD-2026-102'],
            [
                'request_id' => $req2->id,
                'title' => 'Cadrage Diagnostic Trésorerie & BFR',
                'status' => FramingStatus::Submitted,
                'prepared_by' => $manager->id,
            ]
        );

        FramingVersion::firstOrCreate(
            ['framing_id' => $framingPending->id, 'version_number' => 1],
            [
                'content' => [
                    'objectives' => 'Cartographier le BFR, identifier les goulots de trésorerie et proposer un plan de renégociation.',
                    'scope_summary' => 'Finance & Rentabilité (Trésorerie & BFR) et Opérations (Approvisionnements).',
                    'deliverables' => 'Diagnostic BFR, tableau prévisionnel de trésorerie à 13 semaines, plan d\'action.',
                    'timeline' => '4 semaines',
                    'conditions' => 'Accès aux relevés bancaires et balances âgées dès J+2.',
                ],
                'status' => FramingStatus::Submitted,
                'prepared_by' => $manager->id,
                'submitted_at' => now()->subDay(),
            ]
        );

        // C. Demande acceptée et convertie en mission active avec configuration sélective des outils
        $req3 = ClientRequest::firstOrCreate(
            ['reference' => 'REQ-2026-103'],
            [
                'client_id' => $savanes->id,
                'title' => 'Diagnostic de compétitivité industrielle et rebuts atelier',
                'description' => 'Hausse de 12% des rebuts matières sur les chaînes de coupe et d\'assemblage.',
                'source_channel' => 'portail_client',
                'received_at' => now()->subDays(12),
                'status' => RequestStatus::Converted,
                'created_by' => $clientSavanesUser->id,
            ]
        );

        InitialUnderstanding::firstOrCreate(
            ['request_id' => $req3->id],
            [
                'context' => 'Unité de confection de 45 salariés tournant en 2 équipes.',
                'expressed_need' => $req3->description,
                'reformulated_need' => 'Analyser la chaîne de coupe, le stockage tissus et le pilotage qualité atelier.',
                'central_question' => 'Comment ramener le taux de rebut sous 4% en 3 mois ?',
                'prepared_by' => $manager->id,
                'completed_at' => now()->subDays(10),
            ]
        );

        $framingAccepted = Framing::firstOrCreate(
            ['reference' => 'CAD-2026-103'],
            [
                'request_id' => $req3->id,
                'title' => 'Cadrage Diagnostic Opérationnel Atelier Savanes',
                'status' => FramingStatus::Accepted,
                'prepared_by' => $manager->id,
            ]
        );

        FramingVersion::firstOrCreate(
            ['framing_id' => $framingAccepted->id, 'version_number' => 1],
            [
                'content' => [
                    'objectives' => 'Réduire le taux de rebut de 12% à moins de 4% et fiabiliser les flux atelier.',
                    'scope_summary' => 'Opérations & Production, Organisation & RH.',
                    'deliverables' => 'Matrice des pertes, cartographie des flux et feuille de route atelier.',
                    'timeline' => '6 semaines',
                ],
                'status' => FramingStatus::Accepted,
                'prepared_by' => $manager->id,
                'submitted_at' => now()->subDays(9),
                'client_decision_at' => now()->subDays(8),
                'client_feedback' => 'Accord validé par la direction générale.',
            ]
        );

        $mission = Mission::firstOrCreate(
            ['reference' => 'MIS-2026-001'],
            [
                'framing_id' => $framingAccepted->id,
                'title' => 'Diagnostic Opérationnel — Atelier Textile des Savanes',
                'description' => 'Audit des processus de coupe, gestion des stocks tissus et pilotage des équipes d\'atelier.',
                'objective' => 'Réduire le taux de rebut sous 4% et structurer le suivi de production.',
                'status' => 'in_progress',
                'created_by' => $dg->id,
                'manager_id' => $managerCollab->id,
                'start_date' => now()->subDays(7)->toDateString(),
                'due_date' => now()->addWeeks(5)->toDateString(),
                'budget' => 2500000,
            ]
        );

        // Configuration des outils & droits pour cette mission de démonstration
        $configService = app(MissionToolConfigurationService::class);
        if (! $mission->toolUsages()->exists()) {
            $toolsConfig = [
                'D360-MFS' => [
                    'enabled' => '1',
                    'allowed_roles' => ['manager', 'mission_lead', 'consultant'],
                    'permissions' => [
                        'view' => ['manager', 'mission_lead', 'consultant'],
                        'edit' => ['manager', 'mission_lead'],
                        'submit' => ['mission_lead'],
                        'validate' => ['manager'],
                    ],
                ],
                'D360-DSC' => [
                    'enabled' => '1',
                    'allowed_roles' => ['manager', 'mission_lead', 'consultant'],
                    'permissions' => [
                        'view' => ['manager', 'mission_lead', 'consultant'],
                        'edit' => ['manager', 'mission_lead'],
                        'submit' => ['mission_lead'],
                        'validate' => ['manager'],
                    ],
                    'rules' => ['lock_after_validation' => '1'],
                ],
                'D360-DIR' => [
                    'enabled' => '1',
                    'allowed_roles' => ['manager', 'mission_lead', 'consultant'],
                    'permissions' => [
                        'view' => ['manager', 'mission_lead', 'consultant'],
                        'edit' => ['manager', 'mission_lead', 'consultant'],
                        'submit' => ['mission_lead', 'consultant'],
                        'validate' => ['manager', 'mission_lead'],
                    ],
                    'rules' => ['allow_custom_items' => '1'],
                ],
                'D360-SIG' => [
                    'enabled' => '1',
                    'allowed_roles' => ['manager', 'mission_lead', 'consultant'],
                    'permissions' => [
                        'view' => ['manager', 'mission_lead', 'consultant'],
                        'edit' => ['manager', 'mission_lead', 'consultant'],
                        'submit' => ['mission_lead', 'consultant'],
                        'validate' => ['manager', 'mission_lead'],
                    ],
                ],
                'D360-D360' => [
                    'enabled' => '1',
                    'allowed_roles' => ['manager', 'mission_lead', 'consultant'],
                    'permissions' => [
                        'view' => ['manager', 'mission_lead', 'consultant'],
                        'edit' => ['manager', 'mission_lead', 'consultant'],
                        'submit' => ['mission_lead', 'consultant'],
                        'validate' => ['manager', 'mission_lead'],
                    ],
                ],
                'D360-PSG' => [
                    'enabled' => '1',
                    'allowed_roles' => ['manager', 'mission_lead'],
                    'permissions' => [
                        'view' => ['manager', 'mission_lead'],
                        'edit' => ['manager', 'mission_lead'],
                        'submit' => ['mission_lead'],
                        'validate' => ['manager'],
                    ],
                ],
                // Outils désactivés volontairement pour illustrer la restriction par mission
                'D360-DRD' => ['enabled' => '0'],
                'D360-LDC' => ['enabled' => '1', 'allowed_roles' => ['manager', 'mission_lead', 'consultant'], 'permissions' => ['view' => ['manager', 'mission_lead', 'consultant'], 'edit' => ['manager', 'mission_lead']]],
                'D360-DAP' => ['enabled' => '0'],
                'D360-NSR' => ['enabled' => '0'],
            ];

            $membersConfig = [
                $manager->id => ['user_id' => $manager->id, 'selected' => '1', 'mission_role' => MissionRole::Manager->value],
                $lead->id => ['user_id' => $lead->id, 'selected' => '1', 'mission_role' => MissionRole::MissionLead->value],
                $consultant->id => ['user_id' => $consultant->id, 'selected' => '1', 'mission_role' => MissionRole::Consultant->value],
            ];

            $configService->saveConfiguration($mission, $toolsConfig, $membersConfig, $dg);
        }

        if (! $mission->framingSheet) {
            app(MissionFramingSheetService::class)->saveFraming($mission, [
                'title' => 'Cadrage Opérationnel — Atelier Savanes',
                'specific_objective' => 'Examiner la chaîne de coupe, les stocks tissus et l\'encadrement de proximité.',
                'expected_results' => 'Plan de réduction des rebuts sous 4%.',
                'responsible_id' => $lead->id,
            ], $lead);
        }

        if (! $mission->scopeCanvas) {
            app(DiagnosticScopeCanvasService::class)->saveScope($mission, [
                'scope_items' => [
                    [
                        'domain' => 'Opérations & Production',
                        'axis' => 'Processus de fabrication / exécution',
                        'subaxis' => 'Taux de rebut, gaspillage et pertes matières',
                        'element' => 'Taux de rebut, gaspillage et pertes matières',
                        'depth' => 'approfondi',
                        'status' => 'included',
                        'observation' => 'Axe prioritaire suite aux pertes constatées en coupe.',
                    ],
                    [
                        'domain' => 'Opérations & Production',
                        'axis' => 'Achats & Approvisionnements',
                        'subaxis' => 'Gestion et rotation des stocks',
                        'element' => 'Gestion et rotation des stocks',
                        'depth' => 'standard',
                        'status' => 'included',
                    ],
                    [
                        'domain' => 'Finance & Rentabilité',
                        'axis' => 'Structure de financement & Endettement',
                        'subaxis' => 'Capacité d’endettement & garanties',
                        'element' => 'Capacité d’endettement & garanties',
                        'depth' => 'standard',
                        'status' => 'excluded',
                        'justification' => 'Hors périmètre de la phase opérationnelle atelier.',
                    ],
                ],
            ], $lead);
        }
    }
}
