<?php

namespace Tests\Feature;

use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\ProjectManagement\Http\Models\Task;
use App\Modules\ProjectManagement\Http\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_overdue_mission(): void
    {
        $overdueMission = Mission::create([
            'title' => 'Mission En Retard',
            'status' => 'in_progress',
            'due_date' => now()->subDays(4)->format('Y-m-d'),
        ]);

        $this->assertTrue($overdueMission->isOverdue());
        $this->assertGreaterThanOrEqual(4, $overdueMission->daysOverdue());

        $alertService = app(AlertService::class);
        $overdueList = $alertService->getOverdueMissions();

        $this->assertTrue($overdueList->contains('id', $overdueMission->id));
    }

    public function test_completed_mission_is_never_overdue(): void
    {
        $completedMission = Mission::create([
            'title' => 'Mission Terminée dans le passé',
            'status' => 'completed',
            'due_date' => now()->subDays(10)->format('Y-m-d'),
        ]);

        $this->assertFalse($completedMission->isOverdue());
        $this->assertFalse($completedMission->isAtRisk());
    }

    public function test_detects_mission_at_risk(): void
    {
        $atRiskMission = Mission::create([
            'title' => 'Mission Risque Dérive',
            'status' => 'in_progress',
            'due_date' => now()->addDays(3)->format('Y-m-d'),
        ]);

        Task::create([
            'mission_id' => $atRiskMission->id,
            'title' => 'Action 1',
            'status' => 'todo',
            'weekly_hours' => 5,
        ]);

        // Progression 0% et échéance dans 3 jours (< 7 jours)
        $this->assertTrue($atRiskMission->isAtRisk());

        $alertService = app(AlertService::class);
        $atRiskList = $alertService->getAtRiskMissions();

        $this->assertTrue($atRiskList->contains('id', $atRiskMission->id));
    }

    public function test_detects_overdue_and_orphan_tasks(): void
    {
        $mission = Mission::create([
            'title' => 'Dossier Support',
            'status' => 'in_progress',
        ]);

        $overdueTask = Task::create([
            'mission_id' => $mission->id,
            'title' => 'Intervention Dépassée',
            'status' => 'todo',
            'weekly_hours' => 6,
            'due_date' => now()->subDays(2)->format('Y-m-d'),
        ]);

        $this->assertTrue($overdueTask->isOverdue());
        $this->assertTrue($overdueTask->isOrphan());
        $this->assertGreaterThanOrEqual(2, $overdueTask->daysOverdue());

        $alertService = app(AlertService::class);
        $this->assertTrue($alertService->getOverdueTasks()->contains('id', $overdueTask->id));
        $this->assertTrue($alertService->getOrphanTasks()->contains('id', $overdueTask->id));
    }

    public function test_vigilance_hub_and_alerts_filter_in_blade(): void
    {
        $overdueMission = Mission::create([
            'title' => 'Mission Audit Retardé',
            'status' => 'in_progress',
            'due_date' => now()->subDays(5)->format('Y-m-d'),
        ]);

        $ontimeMission = Mission::create([
            'title' => 'Mission Conforme',
            'status' => 'in_progress',
            'due_date' => now()->addMonth()->format('Y-m-d'),
        ]);

        // 1. Accès à la page générale : le Hub de vigilance s'affiche
        $response = $this->get(route('missions.index'));
        $response->assertOk()
            ->assertSee('Centre de Vigilance Cabinet', false)
            ->assertSee('Mission Audit Retardé')
            ->assertSee('Mission Conforme');

        // 2. Accès avec filtre d'alertes uniquement
        $response = $this->get(route('missions.index', ['filter' => 'alerts']));
        $response->assertOk()
            ->assertSee('Mission Audit Retardé')
            ->assertDontSee('Mission Conforme');
    }

    public function test_mission_show_renders_clean_status_integer_days_and_diagnostic_tools(): void
    {
        $mission = Mission::create([
            'title' => 'Audit financier',
            'status' => 'in_progress',
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->get(route('missions.show', $mission));

        $response->assertOk()
            ->assertSee('Audit financier')
            ->assertSee('En cours')
            ->assertDontSee('IN_PROGRESS')
            ->assertSee('dans 5 jours')
            ->assertSee('Outils du Diagnostic 360°')
            ->assertSee('Cadrage Opérationnel')
            ->assertSee('Feuille de route');
    }
}
