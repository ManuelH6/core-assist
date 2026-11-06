<?php

namespace App\Modules\ProjectManagement\Http\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'start_date',
        'due_date',
        'budget',
        'manager_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'budget' => 'decimal:2',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class, 'manager_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Progression de la mission en pourcentage de tâches terminées.
     */
    public function progressPercentage(): int
    {
        $total = $this->tasks()->count();

        if ($total === 0) {
            return 0;
        }

        $done = $this->tasks()->where('status', 'done')->count();

        return (int) round(($done / $total) * 100);
    }

    /**
     * Volume d'heures par semaine mobilisé sur l'ensemble de la mission.
     */
    public function totalWeeklyHours(): float
    {
        return (float) $this->tasks()->whereIn('status', ['todo', 'doing', 'in_progress'])->sum('weekly_hours');
    }

    /**
     * Vérifier si la mission est en retard critique sur son échéance.
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'completed'
            && $this->due_date !== null
            && $this->due_date->endOfDay()->isPast();
    }

    /**
     * Nombre de jours de retard.
     */
    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return (int) max(1, $this->due_date->diffInDays(now()));
    }

    /**
     * Vérifier si la mission présente un risque élevé (échéance sous 7 jours et avancement < 50%).
     */
    public function isAtRisk(): bool
    {
        if ($this->status === 'completed' || $this->due_date === null || $this->isOverdue()) {
            return false;
        }

        $daysRemaining = (int) now()->diffInDays($this->due_date, false);

        return $daysRemaining >= 0 && $daysRemaining <= 7 && $this->progressPercentage() < 50;
    }

    /**
     * Scope pour les missions actives (non terminées).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'completed');
    }

    /**
     * Scope pour les missions en retard.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', today());
    }
}
