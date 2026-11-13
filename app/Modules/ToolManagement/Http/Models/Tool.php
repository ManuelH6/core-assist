<?php

namespace App\Modules\ToolManagement\Http\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'purpose',
        'category',
        'status',
        'current_version_id',
        'created_by',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(ToolVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ToolVersion::class)->latestOfMany();
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(
            ToolVersion::class,
            'current_version_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
