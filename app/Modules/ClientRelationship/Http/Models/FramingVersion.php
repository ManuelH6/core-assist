<?php

namespace App\Modules\ClientRelationship\Http\Models;

use App\Enums\FramingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FramingVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'framing_id',
        'version_number',
        'content',
        'status',
        'prepared_by',
        'submitted_at',
        'client_decision_at',
        'client_feedback',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'content' => 'array',
            'status' => FramingStatus::class,
            'submitted_at' => 'datetime',
            'client_decision_at' => 'datetime',
        ];
    }

    public function framing(): BelongsTo
    {
        return $this->belongsTo(Framing::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
