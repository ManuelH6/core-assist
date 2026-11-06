<?php

namespace App\Modules\FinancialCollection\Http\Services;

use App\Modules\FinancialCollection\Http\Models\FinancialCollection;
use Illuminate\Database\Eloquent\Collection;

class FinancialCollectionService
{
    /**
     * Obtenir toutes les collectes.
     */
    public function getAll(): Collection
    {
        return FinancialCollection::latest()->get();
    }

    /**
     * Trouver une collecte par son identifiant.
     */
    public function findById(int|string $id): FinancialCollection
    {
        return FinancialCollection::findOrFail($id);
    }

    /**
     * Créer une nouvelle collecte financière.
     */
    public function create(array $data): FinancialCollection
    {
        return FinancialCollection::create($data);
    }

    /**
     * Mettre à jour une collecte financière.
     */
    public function update(FinancialCollection $collection, array $data): FinancialCollection
    {
        $collection->update($data);
        return $collection;
    }

    /**
     * Supprimer une collecte financière.
     */
    public function delete(FinancialCollection $collection): bool
    {
        return (bool) $collection->delete();
    }
}
