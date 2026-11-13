<?php

namespace App\Modules\ProjectManagement\Http\Models;

use App\Enums\WorkPriority;
use App\Enums\WorkStatus;
use App\Enums\WorkType;
use App\Models\User;
use App\Modules\ToolManagement\Http\Models\MissionToolUsage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Work extends Model
{
    use HasFactory;

    protected $table = 'works';

    protected $fillable = [
        'mission_id',
        'parent_id',
        'reference',
        'title',
        'description',
        'work_type',
        'priority',
        'status',
        'start_date',
        'due_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'work_type' => WorkType::class,
            'priority' => WorkPriority::class,
            'status' => WorkStatus::class,
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkAssignment::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'work_assignments')
            ->withPivot(['id', 'assignment_role', 'status', 'assigned_at', 'assigned_by'])
            ->withTimestamps();
    }

    public function toolUsages(): BelongsToMany
    {
        return $this->belongsToMany(MissionToolUsage::class, 'work_tool_usages')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
