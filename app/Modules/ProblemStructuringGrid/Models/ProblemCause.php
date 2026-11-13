<?php

namespace App\Modules\ProblemStructuringGrid\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProblemCause extends Model
{
    use HasFactory;

    protected $table = 'problem_causes';

    protected $fillable = [
        'problem_id',
        'cause',
        'nature',
        'explanation',
        'confirmation_level',
        'comments',
        'identified_by',
    ];

    public function problem(): BelongsTo
    {
        return $this->belongsTo(StructuredProblem::class, 'problem_id');
    }

    public function identifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'identified_by');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(ProblemEvidence::class, 'cause_id');
    }
}
