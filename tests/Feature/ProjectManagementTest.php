<?php

namespace Tests\Feature;

use App\Modules\ProjectManagement\Http\Models\Collaborator;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\ProjectManagement\Http\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_collaborator_and_track_capacity(): void
    {
        $response = $this->post(route('collaborators.store'), [
            'name' => 'Marc Dupont',
            'email' => 'marc.dupont@cabinet.com',
            'role_title' => 'Auditeur Senior',
            'weekly_capacity_hours' => 35,
            'hourly_rate' => 6000,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('collaborators', [
            'name' => 'Marc Dupont',
            'email' => 'marc.dupont@cabinet.com',
            'weekly_capacity_hours' => 35,
        ]);
    }

    public function test_can_create_mission_with_manager(): void
    {
        $manager = Collaborator::create([
            'name' => 'Directeur Associé',
            'email' => 'associe@cabinet.com',
            'role_title' => 'Associé Gérant',
            'weekly_capacity_hours' => 40,
        ]);

        $response = $this->post(route('missions.store'), [
            'title' => 'Audit Financier Annuel',
            'description' => 'Contrôle légal et révision des comptes',
            'status' => 'in_progress',
            'start_date' => '2026-11-01',
            'due_date' => '2026-12-15',
            'budget' => 3500000,
            'manager_id' => $manager->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('missions', [
            'title' => 'Audit Financier Annuel',
            'manager_id' => $manager->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_cabinet_weekly_hours_staffing_and_workload_calculation(): void
    {
        $collab = Collaborator::create([
            'name' => 'Alice Lemoine',
            'email' => 'alice@cabinet.com',
            'role_title' => 'Consultante',
            'weekly_capacity_hours' => 35,
        ]);

        $mission = Mission::create([
            'title' => 'Mission Conseil Stratégique',
            'status' => 'in_progress',
        ]);

        // 1. Première tâche : 15h / semaine
        $task1 = Task::create([
            'mission_id' => $mission->id,
            'collaborator_id' => $collab->id,
            'title' => 'Analyse des processus',
            'weekly_hours' => 15.0,
            'status' => 'in_progress',
        ]);

        // 2. Deuxième tâche : 10h / semaine
        $task2 = Task::create([
            'mission_id' => $mission->id,
            'collaborator_id' => $collab->id,
            'title' => 'Modélisation des coûts',
            'weekly_hours' => 10.0,
            'status' => 'todo',
        ]);

        // Vérifications de charge : 15 + 10 = 25h/sem sur 35h (71.4%)
        $this->assertEquals(25.0, $collab->currentWeeklyLoad());
        $this->assertEquals(71.4, $collab->staffingRate());
        $this->assertFalse($collab->isOverCapacity());

        // 3. Troisième tâche : ajout de 15h -> total 40h / 35h (114.3% -> Surcharge !)
        $task3 = Task::create([
            'mission_id' => $mission->id,
            'collaborator_id' => $collab->id,
            'title' => 'Assistance inventaire',
            'weekly_hours' => 15.0,
            'status' => 'todo',
        ]);

        $this->assertEquals(40.0, $collab->currentWeeklyLoad());
        $this->assertEquals(114.3, $collab->staffingRate());
        $this->assertTrue($collab->isOverCapacity());

        // 4. Clôture de la tâche 1 (terminée) : la charge active retombe à 25h
        $this->post(route('tasks.status', $task1), ['status' => 'done']);

        $collab->refresh();
        $this->assertEquals(25.0, $collab->currentWeeklyLoad());
        $this->assertFalse($collab->isOverCapacity());
    }

    public function test_mission_progress_percentage(): void
    {
        $mission = Mission::create([
            'title' => 'Mission Test Progression',
            'status' => 'in_progress',
        ]);

        Task::create([
            'mission_id' => $mission->id,
            'title' => 'Tâche 1',
            'weekly_hours' => 5,
            'status' => 'done',
        ]);

        Task::create([
            'mission_id' => $mission->id,
            'title' => 'Tâche 2',
            'weekly_hours' => 5,
            'status' => 'todo',
        ]);

        // 1 terminée sur 2 = 50%
        $this->assertEquals(50, $mission->progressPercentage());
    }

    public function test_can_render_blade_views_for_missions_and_collaborators(): void
    {
        $collab = Collaborator::create([
            'name' => 'Paul Valery',
            'email' => 'paul@cabinet.com',
            'role_title' => 'Chef de Mission',
            'weekly_capacity_hours' => 35,
        ]);

        $mission = Mission::create([
            'title' => 'Audit Opérationnel',
            'manager_id' => $collab->id,
            'status' => 'in_progress',
        ]);

        // Page liste des missions
        $response = $this->get(route('missions.index'));
        $response->assertOk()
            ->assertSee('Audit Opérationnel')
            ->assertSee('Missions & Engagements', false);

        // Page détail d'une mission
        $response = $this->get(route('missions.show', $mission));
        $response->assertOk()
            ->assertSee('Audit Opérationnel')
            ->assertSee('Interventions & Staffing', false);

        // Page équipe & staffing
        $response = $this->get(route('collaborators.index'));
        $response->assertOk()
            ->assertSee('Paul Valery')
            ->assertSee('Capacité de Staffing', false);

        // Page profil collaborateur
        $response = $this->get(route('collaborators.show', $collab));
        $response->assertOk()
            ->assertSee('Paul Valery')
            ->assertSee('35 h/sem');
    }
}
