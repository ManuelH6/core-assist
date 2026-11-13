<?php

namespace App\Modules\Analysis\Http\Services;

use App\Modules\Analysis\Http\Models\FinancialAnalysis;
use App\Modules\FinancialCollection\Http\Models\FinancialCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class FinancialAnalysisService
{
    /**
     * Moteur de calcul pur de marge brute (sans dépendance de modèle).
     *
     * @return array{daily_revenue: float, daily_production_cost: float, daily_gross_margin: float, margin_rate_percent: float, unit_margin: float}
     */
    public function calculate(float|string $sellingPrice, int $dailyQuantity, float|string $unitProductionCost): array
    {
        $sellingPriceFloat = (float) $sellingPrice;
        $unitProductionCostFloat = (float) $unitProductionCost;

        $dailyRevenue = round($sellingPriceFloat * $dailyQuantity, 2);
        $dailyProductionCost = round($unitProductionCostFloat * $dailyQuantity, 2);
        $dailyGrossMargin = round($dailyRevenue - $dailyProductionCost, 2);
        $marginRatePercent = $dailyRevenue > 0 ? round(($dailyGrossMargin / $dailyRevenue) * 100, 2) : 0.0;
        $unitMargin = round($sellingPriceFloat - $unitProductionCostFloat, 2);

        return [
            'daily_revenue' => $dailyRevenue,
            'daily_production_cost' => $dailyProductionCost,
            'daily_gross_margin' => $dailyGrossMargin,
            'margin_rate_percent' => $marginRatePercent,
            'unit_margin' => $unitMargin,
        ];
    }

    /**
     * Normaliser les données de n'importe quel modèle source en jeu de données standardisé.
     *
     * @return array<string, mixed>
     */
    public function normalizeInput(Model $source): array
    {
        if ($source instanceof FinancialCollection) {
            return [
                'title' => $source->title,
                'entry_date' => $source->entry_date ? $source->entry_date->format('Y-m-d') : null,
                'currency' => $source->currency ?? 'XOF',
                'selling_price' => (float) $source->selling_price,
                'daily_quantity' => (int) $source->daily_quantity,
                'unit_production_cost' => (float) $source->unit_production_cost,
            ];
        }

        // Adaptateur générique pour toute future source (Diagnostic, Devis, etc.)
        return [
            'title' => $source->title ?? $source->name ?? 'Donnée source',
            'entry_date' => isset($source->entry_date) ? (string) $source->entry_date : null,
            'currency' => $source->currency ?? 'XOF',
            'selling_price' => (float) ($source->selling_price ?? $source->unit_price ?? $source->price ?? 0),
            'daily_quantity' => (int) ($source->daily_quantity ?? $source->quantity ?? 1),
            'unit_production_cost' => (float) ($source->unit_production_cost ?? $source->cost ?? 0),
        ];
    }

    /**
     * Analyser une entité source polymorphique et persister les snapshots d'entrée et de résultat.
     */
    public function analyze(Model $source, string $type = 'gross_margin'): FinancialAnalysis
    {
        $inputSnapshot = $this->normalizeInput($source);

        $resultSnapshot = match ($type) {
            'gross_margin' => $this->calculate(
                sellingPrice: $inputSnapshot['selling_price'],
                dailyQuantity: $inputSnapshot['daily_quantity'],
                unitProductionCost: $inputSnapshot['unit_production_cost']
            ),

            default => throw new InvalidArgumentException(
                "Type d'analyse non reconnu : [{$type}]."
            ),
        };

        return FinancialAnalysis::create([
            'source_type' => get_class($source),
            'source_id' => $source->getKey(),
            'analysis_type' => $type,
            'daily_revenue' => $resultSnapshot['daily_revenue'] ?? null,
            'daily_production_cost' => $resultSnapshot['daily_production_cost'] ?? null,
            'daily_gross_margin' => $resultSnapshot['daily_gross_margin'] ?? null,
            'input_snapshot' => $inputSnapshot,
            'result_snapshot' => $resultSnapshot,
        ]);
    }

    /**
     * Passerelle spécifique de collecte (rétrocompatibilité).
     */
    public function analyzeCollection(FinancialCollection $collection, string $type = 'gross_margin'): FinancialAnalysis
    {
        return $this->analyze($collection, $type);
    }

    /**
     * Récupérer toutes les analyses enregistrées avec leur source polymorphique.
     */
    public function getAll(): Collection
    {
        return FinancialAnalysis::with('source')->latest()->get();
    }

    /**
     * Trouver une analyse par son identifiant avec sa source.
     */
    public function findById(int|string $id): FinancialAnalysis
    {
        return FinancialAnalysis::with('source')->findOrFail($id);
    }

    /**
     * Obtenir toutes les analyses associées à une entité source spécifique.
     */
    public function getBySource(Model $source): Collection
    {
        return FinancialAnalysis::where('source_type', get_class($source))
            ->where('source_id', $source->getKey())
            ->latest()
            ->get();
    }

    /**
     * Obtenir toutes les analyses associées à une collecte donnée (compatibilité).
     */
    public function getByCollectionId(int|string $collectionId): Collection
    {
        return FinancialAnalysis::where('source_type', FinancialCollection::class)
            ->where('source_id', $collectionId)
            ->latest()
            ->get();
    }
}
