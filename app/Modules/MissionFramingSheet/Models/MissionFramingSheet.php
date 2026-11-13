<?php

namespace App\Modules\MissionFramingSheet\Models;

use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionFramingSheet extends Model
{
    use HasFactory;

    protected $table = 'mission_framing_sheets';

    protected $fillable = [
        'mission_id',
        'title',
        'specific_objective',
        'expected_results',
        'responsible_id',
        'mission_leader_id',
        'planned_start_date',
        'planned_end_date',
        'execution_conditions',
        'operational_constraints',
        'additional_observations',
        'status',
        'submitted_at',
        'validated_at',
        'validated_by',
        'inherited_data',
        'field_provenance',
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
            'planned_start_date' => 'date',
            'planned_end_date' => 'date',
            'submitted_at' => 'datetime',
            'validated_at' => 'datetime',
            'inherited_data' => 'array',
            'field_provenance' => 'array',
            'input_snapshot' => 'array',
            'output_snapshot' => 'array',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function missionLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mission_leader_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
