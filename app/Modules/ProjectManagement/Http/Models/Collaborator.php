<?php

namespace App\Modules\ProjectManagement\Http\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collaborator extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'role_title',
        'weekly_capacity_hours',
        'hourly_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weekly_capacity_hours' => 'integer',
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function activeTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereIn('status', ['todo', 'doing', 'in_progress']);
    }

    public function managedMissions(): HasMany
    {
        return $this->hasMany(Mission::class, 'manager_id');
    }

    /**
     * Charge hebdomadaire cumulée sur les interventions en cours (h/semaine).
     */
    public function currentWeeklyLoad(): float
    {
        return (float) $this->activeTasks()->sum('weekly_hours');
    }

    /**
     * Taux d'occupation / staffing du collaborateur par rapport à sa capacité contractuelle.
     */
    public function staffingRate(): float
    {
        if ($this->weekly_capacity_hours <= 0) {
            return 0.0;
        }

        return round(($this->currentWeeklyLoad() / $this->weekly_capacity_hours) * 100, 1);
    }

    /**
     * Vérifier si le collaborateur est en sur-staffing (> 100%).
     */
    public function isOverCapacity(): bool
    {
        return $this->currentWeeklyLoad() > $this->weekly_capacity_hours;
    }

    /**
     * Vérifier si le collaborateur est en sous-occupation notable (< 50%).
     */
    public function isUnderCapacity(): bool
    {
        return $this->is_active && $this->staffingRate() < 50.0;
    }
}
