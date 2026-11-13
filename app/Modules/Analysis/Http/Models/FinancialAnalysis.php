<?php

namespace App\Modules\Analysis\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancialAnalysis extends Model
{
    use HasFactory;

    protected $table = 'financial_analyses';

    protected $fillable = [
        'source_type',
        'source_id',
        'analysis_type',
        'daily_revenue',
        'daily_production_cost',
        'daily_gross_margin',
        'input_snapshot',
        'result_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'daily_revenue' => 'decimal:2',
            'daily_production_cost' => 'decimal:2',
            'daily_gross_margin' => 'decimal:2',
            'input_snapshot' => 'array',
            'result_snapshot' => 'array',
        ];
    }

    /**
     * Source polymorphique des données de l'analyse (Collecte, Diagnostic, etc.).
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Alias de rétrocompatibilité pour accéder à la collecte source si applicable.
     */
    public function collection(): MorphTo
    {
        return $this->source();
    }

    /**
     * Obtenir une métrique issue du snapshot de résultat ou des attributs directs.
     */
    public function getResult(string $key, mixed $default = null): mixed
    {
        if (is_array($this->result_snapshot) && array_key_exists($key, $this->result_snapshot)) {
            return $this->result_snapshot[$key];
        }

        return $this->getAttribute($key) ?? $default;
    }
}
