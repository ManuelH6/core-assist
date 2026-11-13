<?php

namespace App\Modules\Diagnostic360Matrix\Models;

use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Diagnostic360Matrix extends Model
{
    use HasFactory;

    protected $table = 'diagnostic_360_matrices';

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

    public function assessments(): HasMany
    {
        return $this->hasMany(DiagnosticAssessment::class, 'matrix_id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(DiagnosticEvidence::class, 'matrix_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(DiagnosticFinding::class, 'matrix_id');
    }

    public function axisSummaries(): HasMany
    {
        return $this->hasMany(DiagnosticAxisSummary::class, 'matrix_id');
    }

    public function domainSummaries(): HasMany
    {
        return $this->hasMany(DiagnosticDomainSummary::class, 'matrix_id');
    }

    /**
     * Calcule le taux d'avancement des évaluations (% d'axes évalués ou validés).
     */
    public function evaluationProgressPercentage(): float
    {
        $total = $this->assessments()->count();
        if ($total === 0) {
            return 0.0;
        }

        $completed = $this->assessments()
            ->whereIn('status', ['assessed', 'validated'])
            ->count();

        return round(($completed / $total) * 100, 1);
    }
}
