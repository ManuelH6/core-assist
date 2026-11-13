<?php

namespace App\Modules\ProblemStructuringGrid\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProblemConsequence extends Model
{
    use HasFactory;

    protected $table = 'problem_consequences';

    protected $fillable = [
        'problem_id',
        'consequence',
        'description',
        'domain',
        'importance',
        'comments',
    ];

    public function problem(): BelongsTo
    {
        return $this->belongsTo(StructuredProblem::class, 'problem_id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(ProblemEvidence::class, 'consequence_id');
    }
}
