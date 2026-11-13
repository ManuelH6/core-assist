<?php

namespace App\Modules\NextStepRouter\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NextStepAction extends Model
{
    use HasFactory;

    protected $table = 'next_step_actions';

    protected $fillable = [
        'next_step_router_id',
        'recommendation_id',
        'reference',
        'title',
        'description',
        'expected_result',
        'responsible_id',
        'responsible_name',
        'priority',
        'horizon',
        'planned_start_date',
        'due_date',
        'status',
        'actual_result',
        'problem_reference',
        'finding_reference',
        'decision_reference',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'due_date' => 'date',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(NextStepRouter::class, 'next_step_router_id');
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class, 'recommendation_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
}
