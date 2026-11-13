<?php

namespace App\Modules\Diagnostic360Matrix\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticAxisSummary extends Model
{
    use HasFactory;

    protected $table = 'diagnostic_axis_summaries';

    protected $fillable = [
        'matrix_id',
        'domain',
        'axis',
        'summary_text',
        'strengths_summary',
        'difficulties_summary',
        'information_gaps',
        'contradictions_noted',
        'further_investigations_needed',
    ];

    public function matrix(): BelongsTo
    {
        return $this->belongsTo(Diagnostic360Matrix::class, 'matrix_id');
    }
}
