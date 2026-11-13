<?php

namespace App\Modules\StakeholderInputGrid\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stakeholder extends Model
{
    use HasFactory;

    protected $table = 'stakeholders';

    protected $fillable = [
        'grid_id',
        'name',
        'stakeholder_type',
        'position',
        'organization',
        'role_in_diagnostic',
        'contact_details',
        'observations',
    ];

    public function grid(): BelongsTo
    {
        return $this->belongsTo(StakeholderInputGrid::class, 'grid_id');
    }

    public function expectedContributions(): HasMany
    {
        return $this->hasMany(ExpectedContribution::class, 'stakeholder_id');
    }

    public function receivedContributions(): HasMany
    {
        return $this->hasMany(ReceivedContribution::class, 'stakeholder_id');
    }
}
