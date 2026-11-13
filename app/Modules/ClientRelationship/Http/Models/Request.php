<?php

namespace App\Modules\ClientRelationship\Http\Models;

use App\Enums\RequestStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Request extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'client_id',
        'reference',
        'title',
        'description',
        'source_channel',
        'received_at',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'status' => RequestStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function initialUnderstanding(): HasOne
    {
        return $this->hasOne(InitialUnderstanding::class);
    }

    public function framings(): HasMany
    {
        return $this->hasMany(Framing::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
