<?php

namespace App\Modules\ProblemStructuringGrid\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProblemPriorityAssessment extends Model
{
    use HasFactory;

    protected $table = 'problem_priority_assessments';

    protected $fillable = [
        'problem_id',
        'severity',
        'frequency',
        'scope_extent',
        'urgency',
        'strategic_impact',
        'priority_level',
        'rationale',
        'assessed_by',
        'assessed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assessed_at' => 'datetime',
        ];
    }

    public function problem(): BelongsTo
    {
        return $this->belongsTo(StructuredProblem::class, 'problem_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
