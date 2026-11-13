<?php

namespace App\Modules\LiveDecisionCapture\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapturedDecision extends Model
{
    use HasFactory;

    protected $table = 'captured_decisions';

    protected $fillable = [
        'capture_id',
        'exchange_id',
        'point_id',
        'reference',
        'title',
        'decision_text',
        'nature',
        'decision_maker_type',
        'decision_maker_name',
        'justification',
        'decided_at',
        'status',
        'revision_notes',
        'previous_version_id',
        'recorded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function capture(): BelongsTo
    {
        return $this->belongsTo(LiveDecisionCapture::class, 'capture_id');
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(DecisionExchange::class, 'exchange_id');
    }

    public function discussionPoint(): BelongsTo
    {
        return $this->belongsTo(DiscussionPoint::class, 'point_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    public function commitments(): HasMany
    {
        return $this->hasMany(DecisionCommitment::class, 'decision_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(DecisionSource::class, 'decision_id');
    }
}
