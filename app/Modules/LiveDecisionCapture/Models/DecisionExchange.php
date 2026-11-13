<?php

namespace App\Modules\LiveDecisionCapture\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DecisionExchange extends Model
{
    use HasFactory;

    protected $table = 'decision_exchanges';

    protected $fillable = [
        'capture_id',
        'title',
        'exchanged_at',
        'modality',
        'location',
        'context',
        'observations',
        'attendees',
        'recorded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exchanged_at' => 'datetime',
            'attendees' => 'array',
        ];
    }

    public function capture(): BelongsTo
    {
        return $this->belongsTo(LiveDecisionCapture::class, 'capture_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function discussionPoints(): HasMany
    {
        return $this->hasMany(DiscussionPoint::class, 'exchange_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(CapturedDecision::class, 'exchange_id');
    }
}
