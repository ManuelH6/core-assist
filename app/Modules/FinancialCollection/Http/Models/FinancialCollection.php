<?php

namespace App\Modules\FinancialCollection\Http\Models;

use App\Models\User;
use App\Modules\Analysis\Http\Models\FinancialAnalysis;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FinancialCollection extends Model
{
    use HasFactory;

    protected $table = 'financial_collections';

    protected $fillable = [
        'title',
        'entry_date',
        'currency',
        'selling_price',
        'daily_quantity',
        'unit_production_cost',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'selling_price' => 'decimal:2',
            'daily_quantity' => 'integer',
            'unit_production_cost' => 'decimal:2',
        ];
    }

    /**
     * Analyses financières polymorphiques rattachées à cette collecte.
     */
    public function analyses(): MorphMany
    {
        return $this->morphMany(FinancialAnalysis::class, 'source');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
