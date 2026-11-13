<?php

namespace App\Modules\StakeholderInputGrid\Models;

use App\Models\User;
use App\Modules\DiagnosticIntakeRegister\Models\RequiredInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReceivedContribution extends Model
{
    use HasFactory;

    protected $table = 'received_contributions';

    protected $fillable = [
        'grid_id',
        'expected_contribution_id',
        'stakeholder_id',
        'received_at',
        'modality',
        'summary',
        'key_findings',
        'associated_document',
        'recorded_by',
        'reliability_perception',
        'observations',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    public function grid(): BelongsTo
    {
        return $this->belongsTo(StakeholderInputGrid::class, 'grid_id');
    }

    public function expectedContribution(): BelongsTo
    {
        return $this->belongsTo(ExpectedContribution::class, 'expected_contribution_id');
    }

    public function stakeholder(): BelongsTo
    {
        return $this->belongsTo(Stakeholder::class, 'stakeholder_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function requiredInformation(): BelongsToMany
    {
        return $this->belongsToMany(RequiredInformation::class, 'received_contribution_information')
            ->withPivot(['contribution_value'])
            ->withTimestamps();
    }
}
