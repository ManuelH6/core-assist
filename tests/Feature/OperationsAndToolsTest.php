<?php

namespace Tests\Feature;

use App\Enums\AssignmentRole;
use App\Enums\MissionRole;
use App\Enums\MissionToolUsageStatus;
use App\Enums\ToolExecutionStatus;
use App\Enums\ToolVersionStatus;
use App\Enums\WorkPriority;
use App\Enums\WorkStatus;
use App\Enums\WorkType;
use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\ProjectManagement\Http\Models\MissionMember;
use App\Modules\ProjectManagement\Http\Models\Work;
use App\Modules\ProjectManagement\Http\Models\WorkAssignment;
use App\Modules\ToolManagement\Http\Models\MissionToolUsage;
use App\Modules\ToolManagement\Http\Models\Tool;
use App\Modules\ToolManagement\Http\Models\ToolExecution;
use App\Modules\ToolManagement\Http\Models\ToolVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsAndToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_mobilize_team_with_contextual_mission_roles(): void
    {
        $creator = User::factory()->create();
        $leadUser = User::factory()->create(['name' => 'Jean Chef']);
        $consultantUser = User::factory()->create(['name' => 'Claire Consultante']);

        $mission = Mission::create([
            'title' => 'Diagnostic RH & Climat Social',
            'status' => 'in_progress',
            'created_by' => $creator->id,
        ]);

        $leadMember = MissionMember::create([
            'mission_id' => $mission->id,
            'user_id' => $leadUser->id,
            'mission_role' => MissionRole::MissionLead,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $consultantMember = MissionMember::create([
            'mission_id' => $mission->id,
            'user_id' => $consultantUser->id,
            'mission_role' => MissionRole::Consultant,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->assertCount(2, $mission->members);
        $this->assertSame(MissionRole::MissionLead, $leadMember->mission_role);
        $this->assertSame('Chef de mission', $leadMember->mission_role->label());
        $this->assertSame($leadUser->id, $mission->teamUsers->first()->id);
    }

    public function test_can_structure_works_tree_and_assign_collaborators(): void
    {
        $manager = User::factory()->create();
        $consultant = User::factory()->create();

        $mission = Mission::create([
            'title' => 'Étude de Faisabilité Boulangerie',
            'status' => 'in_progress',
            'created_by' => $manager->id,
        ]);

        // Travail racine (parent)
        $parentWork = Work::create([
            'mission_id' => $mission->id,
            'reference' => 'WRK-001',
            'title' => 'Étude de marché locale',
            'work_type' => WorkType::Study,
            'priority' => WorkPriority::High,
            'status' => WorkStatus::InProgress,
            'created_by' => $manager->id,
        ]);

        // Sous-travail (enfant)
        $childWork = Work::create([
            'mission_id' => $mission->id,
            'parent_id' => $parentWork->id,
            'reference' => 'WRK-001-A',
            'title' => 'Analyse de la concurrence directe (zone 5km)',
            'work_type' => WorkType::Investigation,
            'priority' => WorkPriority::Normal,
            'status' => WorkStatus::Draft,
            'created_by' => $manager->id,
        ]);

        $this->assertSame($parentWork->id, $childWork->parent->id);
        $this->assertCount(1, $parentWork->children);

        // Affectation d'un collaborateur
        $assignment = WorkAssignment::create([
            'work_id' => $childWork->id,
            'user_id' => $consultant->id,
            'assignment_role' => AssignmentRole::Lead,
            'assigned_by' => $manager->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        $this->assertCount(1, $childWork->assignments);
        $this->assertSame(AssignmentRole::Lead, $assignment->assignment_role);
        $this->assertSame('Responsable du travail', $assignment->assignment_role->label());
        $this->assertSame($consultant->id, $childWork->assignedUsers->first()->id);
    }

    public function test_can_manage_tool_catalog_configure_usage_and_record_executions(): void
    {
        $admin = User::factory()->create();
        $consultant = User::factory()->create();

        // 1. Outil dans le catalogue du cabinet
        $tool = Tool::create([
            'code' => 'STAKEHOLDER_GRID',
            'name' => 'Stakeholder Input Grid™',
            'description' => 'Cartographie des parties prenantes et analyse de leur influence.',
            'category' => 'Diagnostic Organisationnel',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        // Version 1 de l'outil
        $version = ToolVersion::create([
            'tool_id' => $tool->id,
            'version_number' => '1.0.0',
            'definition' => [
                'inputs' => ['stakeholder_name', 'influence_level', 'interest_level'],
                'outputs' => ['power_interest_quadrant', 'engagement_strategy'],
            ],
            'status' => ToolVersionStatus::Published,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        $this->assertSame(ToolVersionStatus::Published, $version->status);
        $this->assertSame($version->id, $tool->latestVersion->id);

        // 2. Configuration de l'outil pour une mission spécifique
        $mission = Mission::create([
            'title' => 'Mission Diagnostic Agroalimentaire',
            'status' => 'in_progress',
            'created_by' => $admin->id,
        ]);

        $toolUsage = MissionToolUsage::create([
            'mission_id' => $mission->id,
            'tool_version_id' => $version->id,
            'name' => 'Parties prenantes - Usine de production Sud',
            'configuration' => [
                'axes_retenus' => ['Fournisseurs', 'Distributeurs', 'Syndicats'],
                'seuil_priorite' => 4,
            ],
            'status' => MissionToolUsageStatus::Active,
            'configured_by' => $admin->id,
        ]);

        $this->assertSame(MissionToolUsageStatus::Active, $toolUsage->status);
        $this->assertSame('Parties prenantes - Usine de production Sud', $toolUsage->name);

        // 3. Liaison Travail <-> Outil configuré
        $work = Work::create([
            'mission_id' => $mission->id,
            'reference' => 'WRK-STAKEHOLDERS',
            'title' => 'Entretiens avec les syndicats et riverains',
            'work_type' => WorkType::Investigation,
            'priority' => WorkPriority::High,
            'status' => WorkStatus::InProgress,
            'created_by' => $admin->id,
        ]);

        $work->toolUsages()->attach($toolUsage->id);

        $this->assertCount(1, $work->toolUsages);
        $this->assertSame($toolUsage->id, $work->toolUsages->first()->id);

        // 4. Exécution de l'outil avec données d'entrée et de sortie
        $execution1 = ToolExecution::create([
            'mission_tool_usage_id' => $toolUsage->id,
            'execution_number' => 1,
            'input_data' => [
                'participants' => 12,
                'reponses' => ['satisfaction' => 65],
            ],
            'output_data' => [
                'quadrant' => 'High Power / High Interest',
                'recommandation' => 'Gérer de près avec réunions hebdomadaires',
            ],
            'status' => ToolExecutionStatus::Completed,
            'executed_by' => $consultant->id,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);

        $this->assertCount(1, $toolUsage->executions);
        $this->assertSame(ToolExecutionStatus::Completed, $execution1->status);
        $this->assertSame('High Power / High Interest', $execution1->output_data['quadrant']);
    }
}
