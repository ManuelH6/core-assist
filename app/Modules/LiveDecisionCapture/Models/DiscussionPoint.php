<?php

namespace App\Modules\LiveDecisionCapture\Models;

use App\Modules\ProblemStructuringGrid\Models\StructuredProblem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscussionPoint extends Model
{
    use HasFactory;

    protected $table = 'discussion_points';

    protected $fillable = [
        'exchange_id',
        'subject',
        'description',
        'domain',
        'axis',
        'problem_id',
        'observations',
    ];

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(DecisionExchange::class, 'exchange_id');
    }

    public function problem(): BelongsTo
    {
        return $this->belongsTo(StructuredProblem::class, 'problem_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(CapturedDecision::class, 'point_id');
    }
}
