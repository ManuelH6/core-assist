<?php

namespace App\Modules\ProjectManagement\Http\Services;

use App\Modules\ProjectManagement\Http\Models\Collaborator;
use Illuminate\Database\Eloquent\Collection;

class CollaboratorService
{
    public function getAll(): Collection
    {
        return Collaborator::with(['activeTasks.mission'])->latest()->get();
    }

    public function findById(int|string $id): Collaborator
    {
        return Collaborator::with(['tasks.mission', 'managedMissions'])->findOrFail($id);
    }

    public function create(array $data): Collaborator
    {
        return Collaborator::create($data);
    }

    public function update(Collaborator $collaborator, array $data): Collaborator
    {
        $collaborator->update($data);

        return $collaborator;
    }

    public function delete(Collaborator $collaborator): bool
    {
        return (bool) $collaborator->delete();
    }

    /**
     * Obtenir les statistiques globales de charge d'équipe.
     */
    public function getTeamWorkloadSummary(): array
    {
        $collaborators = $this->getAll();
        $totalCapacity = $collaborators->sum('weekly_capacity_hours');
        $totalAssigned = $collaborators->sum(fn ($c) => $c->currentWeeklyLoad());
        $globalStaffingRate = $totalCapacity > 0 ? round(($totalAssigned / $totalCapacity) * 100, 1) : 0;

        return [
            'total_collaborators' => $collaborators->count(),
            'total_capacity_hours' => $totalCapacity,
            'total_assigned_hours' => $totalAssigned,
            'global_staffing_rate' => $globalStaffingRate,
            'overloaded_count' => $collaborators->filter(fn ($c) => $c->isOverCapacity())->count(),
        ];
    }
}
