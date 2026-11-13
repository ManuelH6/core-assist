<?php

namespace App\Modules\DiagnosticRestitutionDeck\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestitutionContent extends Model
{
    use HasFactory;

    protected $table = 'restitution_contents';

    protected $fillable = [
        'section_id',
        'position',
        'content_type',
        'title',
        'body_text',
        'structured_data',
        'is_custom_editorial',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'structured_data' => 'array',
            'is_custom_editorial' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(RestitutionSection::class, 'section_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(RestitutionSource::class, 'content_id');
    }
}
