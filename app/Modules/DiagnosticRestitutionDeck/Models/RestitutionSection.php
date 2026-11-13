<?php

namespace App\Modules\DiagnosticRestitutionDeck\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestitutionSection extends Model
{
    use HasFactory;

    protected $table = 'restitution_sections';

    protected $fillable = [
        'deck_id',
        'position',
        'title',
        'section_key',
        'objective',
        'status',
        'observations',
        'validated_by',
        'validated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'validated_at' => 'datetime',
        ];
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(DiagnosticRestitutionDeck::class, 'deck_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(RestitutionContent::class, 'section_id')->orderBy('position');
    }
}
