<?php

namespace App\Modules\ClientRelationship\Http\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InitialUnderstanding extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'context',
        'expressed_need',
        'reformulated_need',
        'expected_outcome',
        'central_question',
        'known_constraints',
        'missing_information',
        'initial_notes',
        'prepared_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'known_constraints' => 'array',
            'missing_information' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }
}
