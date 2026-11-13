<?php

namespace App\Modules\NextStepRouter\Models;

use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NextStepRouter extends Model
{
    use HasFactory;

    protected $table = 'next_step_routers';

    protected $fillable = [
        'mission_id',
        'reference',
        'title',
        'description',
        'status',
        'responsible_id',
        'input_snapshot',
        'output_snapshot',
    ];

    protected $casts = [
        'input_snapshot' => 'array',
        'output_snapshot' => 'array',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class, 'next_step_router_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(NextStepAction::class, 'next_step_router_id');
    }

    public function completionPercentage(): int
    {
        $total = $this->actions()->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $this->actions()->where('status', 'completed')->count();

        return (int) round(($completed / $total) * 100);
    }

    /**
     * Regroupement dynamique de la feuille de route par horizon temporel (sans table roadmaps superflue).
     *
     * @return array<string, Collection<int, NextStepAction>>
     */
    public function roadmapByHorizon(): array
    {
        $actions = $this->actions()->with('recommendation')->get();

        return [
            'immediate' => [
                'label' => 'Phase 1 : Actions Immédiates & Sécurisation (0-30 jours)',
                'actions' => $actions->where('horizon', 'immediate')->values(),
            ],
            'short_term' => [
                'label' => 'Phase 2 : Optimisation & Structuration (1-3 mois)',
                'actions' => $actions->where('horizon', 'short_term')->values(),
            ],
            'medium_term' => [
                'label' => 'Phase 3 : Pérennisation & Trajectoire Cible (3-6 mois)',
                'actions' => $actions->where('horizon', 'medium_term')->values(),
            ],
            'long_term' => [
                'label' => 'Phase 4 : Transformation & Vision Stratégique (> 6 mois)',
                'actions' => $actions->where('horizon', 'long_term')->values(),
            ],
        ];
    }
}
