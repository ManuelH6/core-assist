<?php

namespace App\Modules\DiagnosticIntakeRegister\Models;

use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticIntakeRegister extends Model
{
    use HasFactory;

    protected $table = 'diagnostic_intake_registers';

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

    public function requiredInformation(): HasMany
    {
        return $this->hasMany(RequiredInformation::class, 'register_id');
    }

    public function receivedItems(): HasMany
    {
        return $this->hasMany(ReceivedItem::class, 'register_id');
    }

    /**
     * Calcul de l'avancement brut de la collecte (informations obtenues / totales * 100).
     */
    public function progressPercentage(): float
    {
        $total = $this->requiredInformation()->count();
        if ($total === 0) {
            return 0.0;
        }

        $obtained = $this->requiredInformation()->whereIn('status', ['received', 'verified'])->count();

        return round(($obtained / $total) * 100, 1);
    }

    /**
     * Calcul du taux d'informations vérifiées et fiabilisées.
     */
    public function verificationPercentage(): float
    {
        $total = $this->requiredInformation()->count();
        if ($total === 0) {
            return 0.0;
        }

        $verified = $this->requiredInformation()->where('status', 'verified')->count();

        return round(($verified / $total) * 100, 1);
    }
}
