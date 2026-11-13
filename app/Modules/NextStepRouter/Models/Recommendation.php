<?php

namespace App\Modules\NextStepRouter\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recommendation extends Model
{
    use HasFactory;

    protected $table = 'recommendations';

    protected $fillable = [
        'next_step_router_id',
        'reference',
        'title',
        'description',
        'justification',
        'priority',
        'status',
        'domain',
        'axis',
        'input_snapshot',
        'output_snapshot',
        'created_by',
    ];

    protected $casts = [
        'input_snapshot' => 'array',
        'output_snapshot' => 'array',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(NextStepRouter::class, 'next_step_router_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(NextStepAction::class, 'recommendation_id');
    }
}
