<?php

namespace App\Modules\ProjectManagement\Http\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'mission_id',
        'collaborator_id',
        'title',
        'description',
        'status',
        'priority',
        'weekly_hours',
        'start_date',
        'due_date',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'weekly_hours' => 'decimal:2',
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class);
    }

    /**
     * Mettre à jour le statut et horodater si terminé.
     */
    public function updateStatus(string $status): bool
    {
        $this->status = $status;
        $this->completed_at = ($status === 'done') ? now() : null;

        return $this->save();
    }

    /**
     * Tâche active en retard sur son échéance.
     */
    public function isOverdue(): bool
    {
        return !in_array($this->status, ['done', 'cancelled'])
            && $this->due_date !== null
            && $this->due_date->endOfDay()->isPast();
    }

    /**
     * Nombre de jours de retard sur la tâche.
     */
    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return (int) max(1, $this->due_date->diffInDays(now()));
    }

    /**
     * Tâche urgente ou haute priorité non démarrée alors que la date de début est atteinte.
     */
    public function isUrgentPending(): bool
    {
        return $this->status === 'todo'
            && in_array($this->priority, ['urgent', 'high'])
            && ($this->start_date === null || $this->start_date->lte(today()));
    }

    /**
     * Tâche orpheline (active sans aucun collaborateur assigné).
     */
    public function isOrphan(): bool
    {
        return $this->collaborator_id === null
            && !in_array($this->status, ['done', 'cancelled']);
    }

    /**
     * Scope pour les tâches actives.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['done', 'cancelled']);
    }

    /**
     * Scope pour les tâches en retard.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', today());
    }

    /**
     * Scope pour les tâches orphelines.
     */
    public function scopeOrphan(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['done', 'cancelled'])
            ->whereNull('collaborator_id');
    }
}
