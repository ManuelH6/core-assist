<?php

namespace App\Modules\DiagnosticRestitutionDeck\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestitutionSource extends Model
{
    use HasFactory;

    protected $table = 'restitution_sources';

    protected $fillable = [
        'content_id',
        'source_tool',
        'source_type',
        'source_id',
        'source_reference',
        'source_summary',
        'notes',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(RestitutionContent::class, 'content_id');
    }
}
