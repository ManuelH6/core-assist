<?php

namespace App\Modules\Diagnostic360Matrix\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticFinding extends Model
{
    use HasFactory;

    protected $table = 'diagnostic_findings';

    protected $fillable = [
        'matrix_id',
        'assessment_id',
        'finding_type',
        'title',
        'description',
        'possible_causes',
        'potential_consequences',
        'severity_or_importance',
        'is_validated',
        'identified_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_validated' => 'boolean',
        ];
    }

    public function matrix(): BelongsTo
    {
        return $this->belongsTo(Diagnostic360Matrix::class, 'matrix_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(DiagnosticAssessment::class, 'assessment_id');
    }

    public function identifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'identified_by');
    }
}
