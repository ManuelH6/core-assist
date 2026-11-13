<?php

namespace App\Modules\ProjectManagement\Http\Models;

use App\Enums\MissionRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'mission_id',
        'user_id',
        'mission_role',
        'status',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'mission_role' => MissionRole::class,
            'joined_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
