<?php

namespace App\Modules\ClientRelationship\Http\Models;

use App\Enums\ClientType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'client_type',
        'sector',
        'email',
        'phone',
        'address',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'client_type' => ClientType::class,
        ];
    }

    /**
     * Sollicitations / demandes formulées par ce client.
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }

    /**
     * Comptes d'accès portail rattachés à ce client.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
