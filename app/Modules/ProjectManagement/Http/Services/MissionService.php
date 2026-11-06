<?php

namespace App\Modules\ProjectManagement\Http\Services;

use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Database\Eloquent\Collection;

class MissionService
{
    public function getAll(): Collection
    {
        return Mission::with(['manager', 'tasks.collaborator'])->latest()->get();
    }

    public function findById(int|string $id): Mission
    {
        return Mission::with(['manager', 'tasks.collaborator'])->findOrFail($id);
    }

    public function create(array $data): Mission
    {
        return Mission::create($data);
    }

    public function update(Mission $mission, array $data): Mission
    {
        $mission->update($data);
        return $mission;
    }

    public function delete(Mission $mission): bool
    {
        return (bool) $mission->delete();
    }

    /**
     * Résumé pour le dashboard de pilotage.
     */
    public function getDashboardSummary(): array
    {
        $missions = Mission::with('tasks')->get();

        $activeMissions = $missions->whereIn('status', ['draft', 'in_progress', 'on_hold']);
        $completedMissions = $missions->where('status', 'completed');

        $totalTasks = $missions->flatMap->tasks;
        $activeTasks = $totalTasks->whereIn('status', ['todo', 'doing', 'in_progress']);

        return [
            'total_missions' => $missions->count(),
            'active_missions' => $activeMissions->count(),
            'completed_missions' => $completedMissions->count(),
            'total_tasks' => $totalTasks->count(),
            'active_tasks' => $activeTasks->count(),
            'total_weekly_hours' => (float) $activeTasks->sum('weekly_hours'),
        ];
    }
}
