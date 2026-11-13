<?php

namespace App\Modules\DiagnosticRestitutionDeck\Models;

use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticRestitutionDeck extends Model
{
    use HasFactory;

    protected $table = 'diagnostic_restitution_decks';

    protected $fillable = [
        'mission_id',
        'title',
        'subtitle',
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

    public function sections(): HasMany
    {
        return $this->hasMany(RestitutionSection::class, 'deck_id')->orderBy('position');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(RestitutionVersion::class, 'deck_id')->orderByDesc('version_number');
    }

    /**
     * Calcule le taux d'avancement de la rédaction du deck (% de sections validées).
     */
    public function readinessPercentage(): float
    {
        $total = $this->sections()->count();
        if ($total === 0) {
            return 0.0;
        }

        $validated = $this->sections()->where('status', 'validated')->count();

        return round(($validated / $total) * 100, 1);
    }
}
