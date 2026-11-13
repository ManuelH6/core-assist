<?php

namespace App\Modules\LiveDecisionCapture\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionSource extends Model
{
    use HasFactory;

    protected $table = 'decision_sources';

    protected $fillable = [
        'decision_id',
        'source_tool',
        'source_type',
        'source_id',
        'source_reference',
        'source_summary',
        'notes',
    ];

    public function decision(): BelongsTo
    {
        return $this->belongsTo(CapturedDecision::class, 'decision_id');
    }
}
