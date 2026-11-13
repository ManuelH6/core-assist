<?php

namespace App\Modules\ProjectManagement\Http\Services;

use App\Modules\ProjectManagement\Http\Models\Collaborator;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\ProjectManagement\Http\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class AlertService
{
    /**
     * Missions en retard sur leur date d'échéance.
     */
    public function getOverdueMissions(): Collection
    {
        return Mission::with('manager')
            ->overdue()
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Missions à risque (échéance sous 7 jours et progression < 50%).
     */
    public function getAtRiskMissions(): Collection
    {
        return Mission::with(['manager', 'tasks'])
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [today(), today()->addDays(7)])
            ->get()
            ->filter(fn (Mission $m) => $m->progressPercentage() < 50);
    }

    /**
     * Tâches en retard sur leur date limite.
     */
    public function getOverdueTasks(): Collection
    {
        return Task::with(['mission', 'collaborator'])
            ->overdue()
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Tâches orphelines (actives sans aucun collaborateur).
     */
    public function getOrphanTasks(): Collection
    {
        return Task::with('mission')
            ->orphan()
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Tâches urgentes non encore démarrées (statut todo).
     */
    public function getUrgentPendingTasks(): Collection
    {
        return Task::with(['mission', 'collaborator'])
            ->where('status', 'todo')
            ->whereIn('priority', ['urgent', 'high'])
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', today());
            })
            ->get();
    }

    /**
     * Collaborateurs en dépassement de capacité (> 100%).
     */
    public function getOverloadedCollaborators(): Collection
    {
        return Collaborator::with(['activeTasks.mission'])
            ->where('is_active', true)
            ->get()
            ->filter(fn (Collaborator $c) => $c->isOverCapacity());
    }

    /**
     * Synthèse globale des compteurs d'alertes pour les dashboards et badges de navigation.
     */
    public function getAlertsSummary(): array
    {
        $overdueMissions = $this->getOverdueMissions();
        $atRiskMissions = $this->getAtRiskMissions();
        $overdueTasks = $this->getOverdueTasks();
        $orphanTasks = $this->getOrphanTasks();
        $urgentPendingTasks = $this->getUrgentPendingTasks();
        $overloadedStaff = $this->getOverloadedCollaborators();

        $totalCritical = $overdueMissions->count() + $overdueTasks->count() + $overloadedStaff->count();
        $totalWarnings = $atRiskMissions->count() + $orphanTasks->count() + $urgentPendingTasks->count();

        return [
            'total_alerts' => $totalCritical + $totalWarnings,
            'total_critical' => $totalCritical,
            'total_warnings' => $totalWarnings,
            'overdue_missions_count' => $overdueMissions->count(),
            'at_risk_missions_count' => $atRiskMissions->count(),
            'overdue_tasks_count' => $overdueTasks->count(),
            'orphan_tasks_count' => $orphanTasks->count(),
            'urgent_pending_count' => $urgentPendingTasks->count(),
            'overloaded_staff_count' => $overloadedStaff->count(),
            'has_alerts' => ($totalCritical + $totalWarnings) > 0,
        ];
    }

    /**
     * Liste exhaustive et structurée de toutes les alertes actives pour affichage centralisé.
     */
    public function getAllActiveAlerts(): array
    {
        $alerts = [];

        // 1. Missions en retard
        foreach ($this->getOverdueMissions() as $mission) {
            $alerts[] = [
                'type' => 'mission_overdue',
                'level' => 'critical',
                'icon' => 'alert-circle',
                'title' => 'Mission en retard : '.$mission->title,
                'detail' => 'Échéance dépassée de '.$mission->daysOverdue().' jour(s) (prévue le '.$mission->due_date->format('d/m/Y').')',
                'url' => route('missions.show', $mission),
                'action_label' => 'Voir la mission',
            ];
        }

        // 2. Missions à risque
        foreach ($this->getAtRiskMissions() as $mission) {
            $alerts[] = [
                'type' => 'mission_at_risk',
                'level' => 'warning',
                'icon' => 'alert-triangle',
                'title' => 'Risque de dérive : '.$mission->title,
                'detail' => 'Échéance dans '.$mission->daysRemaining().' jour(s) mais seulement '.$mission->progressPercentage().'% réalisé',
                'url' => route('missions.show', $mission),
                'action_label' => 'Accélérer',
            ];
        }

        // 3. Tâches en retard
        foreach ($this->getOverdueTasks() as $task) {
            $alerts[] = [
                'type' => 'task_overdue',
                'level' => 'critical',
                'icon' => 'clock',
                'title' => 'Intervention en retard : '.$task->title,
                'detail' => 'Dossier '.$task->mission->title.' • Assigné à '.($task->collaborator?->name ?? 'Personne').' • Retard de '.$task->daysOverdue().' jour(s)',
                'url' => route('missions.show', $task->mission_id),
                'action_label' => 'Consulter',
            ];
        }

        // 4. Tâches orphelines
        foreach ($this->getOrphanTasks() as $task) {
            $alerts[] = [
                'type' => 'task_orphan',
                'level' => 'warning',
                'icon' => 'user',
                'title' => 'Intervention non affectée : '.$task->title,
                'detail' => 'Mission '.$task->mission->title.' ('.$task->weekly_hours.' h/sem à pourvoir)',
                'url' => route('missions.show', $task->mission_id),
                'action_label' => 'Affecter',
            ];
        }

        // 5. Collaborateurs en sur-staffing
        foreach ($this->getOverloadedCollaborators() as $collab) {
            $alerts[] = [
                'type' => 'staff_overloaded',
                'level' => 'critical',
                'icon' => 'flame',
                'title' => 'Collaborateur en surcharge : '.$collab->name,
                'detail' => $collab->currentWeeklyLoad().'h affectées pour un contrat de '.$collab->weekly_capacity_hours.'h/sem ('.$collab->staffingRate().'%)',
                'url' => route('collaborators.show', $collab),
                'action_label' => 'Ajuster planning',
            ];
        }

        return $alerts;
    }
}
