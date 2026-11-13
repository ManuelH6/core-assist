<?php

namespace App\Modules\ToolManagement\Http\Models;

use App\Enums\ToolVersionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_id',
        'version_number',
        'definition',
        'configuration_schema',
        'business_rules',
        'interface_type',
        'output_definition',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'definition' => 'array',
            'configuration_schema' => 'array',
            'business_rules' => 'array',
            'output_definition' => 'array',
            'status' => ToolVersionStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MissionToolUsage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
