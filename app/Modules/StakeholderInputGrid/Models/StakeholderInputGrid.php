<?php

namespace App\Modules\StakeholderInputGrid\Models;

use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StakeholderInputGrid extends Model
{
    use HasFactory;

    protected $table = 'stakeholder_input_grids';

    protected $fillable = [
        'mission_id',
        'title',
        'status',
        'input_snapshot',
        'output_snapshot',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_snapshot' => 'array',
            'output_snapshot' => 'array',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stakeholders(): HasMany
    {
        return $this->hasMany(Stakeholder::class, 'grid_id');
    }

    public function expectedContributions(): HasMany
    {
        return $this->hasMany(ExpectedContribution::class, 'grid_id');
    }

    public function receivedContributions(): HasMany
    {
        return $this->hasMany(ReceivedContribution::class, 'grid_id');
    }

    /**
     * Calcul du taux de mobilisation des parties prenantes (contributions réalisées / attendues * 100).
     */
    public function mobilizationPercentage(): float
    {
        $total = $this->expectedContributions()->count();
        if ($total === 0) {
            return 0.0;
        }

        $completed = $this->expectedContributions()->where('status', 'completed')->count();

        return round(($completed / $total) * 100, 1);
    }
}
