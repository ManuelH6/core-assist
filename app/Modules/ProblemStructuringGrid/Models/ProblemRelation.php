<?php

namespace App\Modules\ProblemStructuringGrid\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProblemRelation extends Model
{
    use HasFactory;

    protected $table = 'problem_relations';

    protected $fillable = [
        'grid_id',
        'source_problem_id',
        'target_problem_id',
        'relation_type',
        'explanation',
        'is_hypothetical',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_hypothetical' => 'boolean',
        ];
    }

    public function grid(): BelongsTo
    {
        return $this->belongsTo(ProblemStructuringGrid::class, 'grid_id');
    }

    public function sourceProblem(): BelongsTo
    {
        return $this->belongsTo(StructuredProblem::class, 'source_problem_id');
    }

    public function targetProblem(): BelongsTo
    {
        return $this->belongsTo(StructuredProblem::class, 'target_problem_id');
    }
}
