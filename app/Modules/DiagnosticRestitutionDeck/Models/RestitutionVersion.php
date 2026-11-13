<?php

namespace App\Modules\DiagnosticRestitutionDeck\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestitutionVersion extends Model
{
    use HasFactory;

    protected $table = 'restitution_versions';

    protected $fillable = [
        'deck_id',
        'version_number',
        'title',
        'summary',
        'restitution_snapshot',
        'created_by',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'restitution_snapshot' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(DiagnosticRestitutionDeck::class, 'deck_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
