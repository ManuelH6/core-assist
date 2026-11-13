<?php

namespace App\Modules\Diagnostic360Matrix\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticDomainSummary extends Model
{
    use HasFactory;

    protected $table = 'diagnostic_domain_summaries';

    protected $fillable = [
        'matrix_id',
        'domain',
        'overall_rating',
        'summary_text',
        'key_takeaways',
    ];

    public function matrix(): BelongsTo
    {
        return $this->belongsTo(Diagnostic360Matrix::class, 'matrix_id');
    }
}
