<?php

namespace App\Modules\DiagnosticIntakeRegister\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RequiredInformation extends Model
{
    use HasFactory;

    protected $table = 'required_information';

    protected $fillable = [
        'register_id',
        'domain',
        'axis',
        'subaxis',
        'information_name',
        'justification',
        'expected_source',
        'collection_method',
        'priority',
        'assigned_to',
        'status',
        'verification_status',
        'observations',
        'received_at',
        'verified_at',
        'verified_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(DiagnosticIntakeRegister::class, 'register_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function receivedItems(): BelongsToMany
    {
        return $this->belongsToMany(ReceivedItem::class, 'received_item_information')
            ->withPivot(['coverage_degree', 'notes'])
            ->withTimestamps();
    }
}
