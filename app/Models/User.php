<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Modules\ClientRelationship\Http\Models\Client;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'client_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::Client;
    }

    public function isCabinet(): bool
    {
        return ! $this->isClient();
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function canManageClientsAndRequests(): bool
    {
        return $this->role?->canManageClientsAndRequests() ?? false;
    }

    public function canConfigureMissions(): bool
    {
        return $this->role?->canConfigureMissions() ?? false;
    }
}
