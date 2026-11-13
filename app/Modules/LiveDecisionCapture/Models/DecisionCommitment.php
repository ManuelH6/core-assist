<?php

namespace App\Modules\LiveDecisionCapture\Models;

use App\Models\User;
use App\Modules\StakeholderInputGrid\Models\Stakeholder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionCommitment extends Model
{
    use HasFactory;

    protected $table = 'decision_commitments';

    protected $fillable = [
        'decision_id',
        'action',
        'responsible_name',
        'assigned_to_user_id',
        'assigned_to_stakeholder_id',
        'due_date',
        'expected_outcome',
        'status',
        'completed_at',
        'comments',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(CapturedDecision::class, 'decision_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignedStakeholder(): BelongsTo
    {
        return $this->belongsTo(Stakeholder::class, 'assigned_to_stakeholder_id');
    }
}
