<?php

namespace App\Modules\ProblemStructuringGrid\Models;

use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProblemStructuringGrid extends Model
{
    use HasFactory;

    protected $table = 'problem_structuring_grids';

    protected $fillable = [
        'mission_id',
        'title',
        'status',
        'input_snapshot',
        'output_snapshot',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_snapshot' => 'array',
            'output_snapshot' => 'array',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function problems(): HasMany
    {
        return $this->hasMany(StructuredProblem::class, 'grid_id');
    }

    public function relations(): HasMany
    {
        return $this->hasMany(ProblemRelation::class, 'grid_id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(ProblemEvidence::class, 'grid_id');
    }

    /**
     * Calcule le taux de structuration des problèmes (% de problèmes passés au statut structuré ou validé).
     */
    public function structuringProgressPercentage(): float
    {
        $total = $this->problems()->count();
        if ($total === 0) {
            return 0.0;
        }

        $completed = $this->problems()
            ->whereIn('status', ['structured', 'validated'])
            ->count();

        return round(($completed / $total) * 100, 1);
    }
}
