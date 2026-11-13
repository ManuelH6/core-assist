<?php

namespace App\Modules\StakeholderInputGrid\Models;

use App\Models\User;
use App\Modules\DiagnosticIntakeRegister\Models\RequiredInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpectedContribution extends Model
{
    use HasFactory;

    protected $table = 'expected_contributions';

    protected $fillable = [
        'grid_id',
        'stakeholder_id',
        'scope_item_id',
        'required_information_id',
        'domain',
        'axis',
        'subaxis',
        'expected_contribution',
        'justification',
        'modality',
        'priority',
        'assigned_to',
        'status',
        'scheduled_at',
        'completed_at',
        'observations',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function grid(): BelongsTo
    {
        return $this->belongsTo(StakeholderInputGrid::class, 'grid_id');
    }

    public function stakeholder(): BelongsTo
    {
        return $this->belongsTo(Stakeholder::class, 'stakeholder_id');
    }

    public function requiredInformation(): BelongsTo
    {
        return $this->belongsTo(RequiredInformation::class, 'required_information_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function receivedContributions(): HasMany
    {
        return $this->hasMany(ReceivedContribution::class, 'expected_contribution_id');
    }
}
