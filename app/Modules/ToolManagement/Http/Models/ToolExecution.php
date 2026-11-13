<?php

namespace App\Modules\ToolManagement\Http\Models;

use App\Enums\ToolExecutionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'mission_tool_usage_id',
        'execution_number',
        'input_data',
        'output_data',
        'status',
        'executed_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'execution_number' => 'integer',
            'input_data' => 'array',
            'output_data' => 'array',
            'status' => ToolExecutionStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function missionToolUsage(): BelongsTo
    {
        return $this->belongsTo(MissionToolUsage::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
