<?php

namespace App\Modules\Diagnostic360Matrix\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticAssessment extends Model
{
    use HasFactory;

    protected $table = 'diagnostic_assessments';

    protected $fillable = [
        'matrix_id',
        'scope_item_id',
        'domain',
        'axis',
        'subaxis',
        'evaluated_element',
        'observed_situation',
        'assessment_text',
        'rating_level',
        'status',
        'evaluator_id',
    ];

    public function matrix(): BelongsTo
    {
        return $this->belongsTo(Diagnostic360Matrix::class, 'matrix_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function evidences(): BelongsToMany
    {
        return $this->belongsToMany(
            DiagnosticEvidence::class,
            'assessment_evidence',
            'assessment_id',
            'evidence_id'
        )->withPivot(['relevance_degree', 'notes'])->withTimestamps();
    }

    public function findings(): HasMany
    {
        return $this->hasMany(DiagnosticFinding::class, 'assessment_id');
    }

    public function strengths(): HasMany
    {
        return $this->findings()->where('finding_type', 'strength');
    }

    public function difficulties(): HasMany
    {
        return $this->findings()->where('finding_type', 'difficulty');
    }

    public function hypotheses(): HasMany
    {
        return $this->findings()->where('finding_type', 'hypothesis');
    }
}
