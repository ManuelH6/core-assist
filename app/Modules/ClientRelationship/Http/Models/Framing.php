<?php

namespace App\Modules\ClientRelationship\Http\Models;

use App\Enums\FramingStatus;
use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Framing extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'reference',
        'title',
        'status',
        'prepared_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => FramingStatus::class,
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FramingVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(FramingVersion::class)->latestOfMany('version_number');
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
