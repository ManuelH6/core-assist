<?php

namespace App\Modules\Analysis\Http\Services;

use App\Modules\Analysis\Http\Models\FinancialAnalysis;
use App\Modules\FinancialCollection\Http\Models\FinancialCollection;
use Illuminate\Database\Eloquent\Collection;

class FinancialAnalysisService
{
    /**
     * Moteur de calcul pur (sans dépendance de modèle ni DTO).
     *
     * @return array{daily_revenue: float, daily_production_cost: float, daily_gross_margin: float}
     */
    public function calculate(float|string $sellingPrice, int $dailyQuantity, float|string $unitProductionCost): array
    {
        $sellingPriceFloat = (float) $sellingPrice;
        $unitProductionCostFloat = (float) $unitProductionCost;

        $dailyRevenue = round($sellingPriceFloat * $dailyQuantity, 2);
        $dailyProductionCost = round($unitProductionCostFloat * $dailyQuantity, 2);
        $dailyGrossMargin = round($dailyRevenue - $dailyProductionCost, 2);

        return [
            'daily_revenue' => $dailyRevenue,
            'daily_production_cost' => $dailyProductionCost,
            'daily_gross_margin' => $dailyGrossMargin,
        ];
    }

    /**
     * Analyser une collecte existante et persister le résultat avec un snapshot des données d'entrée.
     */
    public function analyzeCollection(FinancialCollection $collection): FinancialAnalysis
    {
        $calculated = $this->calculate(
            sellingPrice: $collection->selling_price,
            dailyQuantity: $collection->daily_quantity,
            unitProductionCost: $collection->unit_production_cost
        );

        return FinancialAnalysis::create([
            'financial_collection_id' => $collection->id,
            'daily_revenue' => $calculated['daily_revenue'],
            'daily_production_cost' => $calculated['daily_production_cost'],
            'daily_gross_margin' => $calculated['daily_gross_margin'],
            'input_snapshot' => [
                'title' => $collection->title,
                'entry_date' => $collection->entry_date ? $collection->entry_date->format('Y-m-d') : null,
                'currency' => $collection->currency ?? 'XOF',
                'selling_price' => $collection->selling_price,
                'daily_quantity' => $collection->daily_quantity,
                'unit_production_cost' => $collection->unit_production_cost,
            ],
        ]);
    }

    /**
     * Récupérer toutes les analyses.
     */
    public function getAll(): Collection
    {
        return FinancialAnalysis::with('collection')->latest()->get();
    }

    /**
     * Trouver une analyse par son identifiant.
     */
    public function findById(int|string $id): FinancialAnalysis
    {
        return FinancialAnalysis::with('collection')->findOrFail($id);
    }

    /**
     * Obtenir toutes les analyses associées à une collecte donnée.
     */
    public function getByCollectionId(int|string $collectionId): Collection
    {
        return FinancialAnalysis::where('financial_collection_id', $collectionId)->latest()->get();
    }
}
