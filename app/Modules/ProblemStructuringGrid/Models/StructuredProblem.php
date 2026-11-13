<?php

namespace App\Modules\ProblemStructuringGrid\Models;

use App\Models\User;
use App\Modules\Diagnostic360Matrix\Models\DiagnosticFinding;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StructuredProblem extends Model
{
    use HasFactory;

    protected $table = 'structured_problems';

    protected $fillable = [
        'grid_id',
        'reference',
        'title',
        'description',
        'domain',
        'axis',
        'subaxis',
        'scope_item_id',
        'diagnostic_finding_id',
        'manifestations',
        'status',
        'created_by',
    ];

    public function grid(): BelongsTo
    {
        return $this->belongsTo(ProblemStructuringGrid::class, 'grid_id');
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFinding::class, 'diagnostic_finding_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function causes(): HasMany
    {
        return $this->hasMany(ProblemCause::class, 'problem_id');
    }

    public function consequences(): HasMany
    {
        return $this->hasMany(ProblemConsequence::class, 'problem_id');
    }

    public function priorityAssessment(): HasOne
    {
        return $this->hasOne(ProblemPriorityAssessment::class, 'problem_id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(ProblemEvidence::class, 'problem_id');
    }

    public function outgoingRelations(): HasMany
    {
        return $this->hasMany(ProblemRelation::class, 'source_problem_id');
    }

    public function incomingRelations(): HasMany
    {
        return $this->hasMany(ProblemRelation::class, 'target_problem_id');
    }
}
