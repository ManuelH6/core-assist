<?php

namespace App\Modules\ToolManagement\Http\Models;

use App\Enums\MissionToolUsageStatus;
use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\ProjectManagement\Http\Models\Work;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MissionToolUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'mission_id',
        'tool_version_id',
        'name',
        'configuration',
        'status',
        'configured_by',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'status' => MissionToolUsageStatus::class,
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function toolVersion(): BelongsTo
    {
        return $this->belongsTo(ToolVersion::class);
    }

    public function works(): BelongsToMany
    {
        return $this->belongsToMany(Work::class, 'work_tool_usages')
            ->withTimestamps();
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ToolExecution::class);
    }

    public function configurator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'configured_by');
    }
}
