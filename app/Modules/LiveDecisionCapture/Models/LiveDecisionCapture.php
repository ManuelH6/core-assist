<?php

namespace App\Modules\LiveDecisionCapture\Models;

use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveDecisionCapture extends Model
{
    use HasFactory;

    protected $table = 'live_decision_captures';

    protected $fillable = [
        'mission_id',
        'title',
        'status',
        'input_snapshot',
        'output_snapshot',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_snapshot' => 'array',
            'output_snapshot' => 'array',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exchanges(): HasMany
    {
        return $this->hasMany(DecisionExchange::class, 'capture_id')->latest('exchanged_at');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(CapturedDecision::class, 'capture_id')->latest('decided_at');
    }

    /**
     * Taux d'exécution des engagements découlant des décisions (% réalisés).
     */
    public function commitmentsCompletionPercentage(): float
    {
        $commitments = DecisionCommitment::whereIn('decision_id', $this->decisions()->pluck('id'))->get();
        $total = $commitments->count();
        if ($total === 0) {
            return 0.0;
        }

        $completed = $commitments->where('status', 'completed')->count();

        return round(($completed / $total) * 100, 1);
    }
}
