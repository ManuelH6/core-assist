<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Analysis\Http\Models\FinancialAnalysis;
use App\Modules\FinancialCollection\Http\Models\FinancialCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_financial_collection_via_api(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/collections', [
            'title' => 'Vente Baguettes - Journée 1',
            'entry_date' => '2026-11-05',
            'currency' => 'XOF',
            'selling_price' => 200,
            'daily_quantity' => 300,
            'unit_production_cost' => 120,
            'created_by' => $user->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Vente Baguettes - Journée 1')
            ->assertJsonPath('data.currency', 'XOF')
            ->assertJsonPath('data.selling_price', '200.00')
            ->assertJsonPath('data.daily_quantity', 300)
            ->assertJsonPath('data.unit_production_cost', '120.00');

        $this->assertDatabaseHas('financial_collections', [
            'title' => 'Vente Baguettes - Journée 1',
            'currency' => 'XOF',
            'selling_price' => 200,
            'daily_quantity' => 300,
            'unit_production_cost' => 120,
        ]);
    }

    public function test_can_view_collection_and_analysis_pages_in_blade(): void
    {
        $collection = FinancialCollection::create([
            'title' => 'Croissants pur beurre',
            'entry_date' => '2026-11-05',
            'currency' => 'XOF',
            'selling_price' => 250.00,
            'daily_quantity' => 150,
            'unit_production_cost' => 140.00,
        ]);

        // 1. Liste des collectes
        $response = $this->get(route('collections.index'));
        $response->assertOk()
            ->assertSee('Collectes Financières')
            ->assertSee('Croissants pur beurre');

        // 2. Formulaire de création de collecte
        $response = $this->get(route('collections.create'));
        $response->assertOk()
            ->assertSee('Saisie', false);

        // 3. Soumission du formulaire web de collecte
        $response = $this->post(route('collections.store'), [
            'title' => 'Pains au chocolat',
            'entry_date' => '2026-11-05',
            'currency' => 'XOF',
            'selling_price' => 250,
            'daily_quantity' => 100,
            'unit_production_cost' => 130,
        ]);
        $response->assertRedirect();

        // 4. Détail d'une collecte
        $response = $this->get(route('collections.show', $collection));
        $response->assertOk()
            ->assertSee('Croissants pur beurre')
            ->assertSee('Lancer une analyse');

        // 5. Exécution de l'analyse via formulaire Web
        $response = $this->post(route('analyses.store'), [
            'financial_collection_id' => $collection->id,
        ]);
        $response->assertRedirect();

        $analysis = FinancialAnalysis::latest()->first();
        $this->assertNotNull($analysis);
        $this->assertEquals('37500.00', $analysis->daily_revenue);
        $this->assertEquals('21000.00', $analysis->daily_production_cost);
        $this->assertEquals('16500.00', $analysis->daily_gross_margin);

        // 6. Affichage de la vue de l'analyse
        $response = $this->get(route('analyses.show', $analysis));
        $response->assertOk()
            ->assertSee('Rapport d\'Analyse Financière', false)
            ->assertSee('37 500 XOF')
            ->assertSee('16 500 XOF')
            ->assertSee('Snapshot immuable');
    }

    public function test_can_run_financial_analysis_from_collection_and_snapshot_data(): void
    {
        $user = User::factory()->create();

        $collection = FinancialCollection::create([
            'title' => 'Fournée Boulangerie',
            'entry_date' => '2026-11-05',
            'currency' => 'XOF',
            'selling_price' => 200.00,
            'daily_quantity' => 300,
            'unit_production_cost' => 120.00,
            'created_by' => $user->id,
        ]);

        $response = $this->postJson('/api/analyses', [
            'financial_collection_id' => $collection->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.daily_revenue', '60000.00')
            ->assertJsonPath('data.daily_production_cost', '36000.00')
            ->assertJsonPath('data.daily_gross_margin', '24000.00')
            ->assertJsonPath('data.input_snapshot.selling_price', '200.00')
            ->assertJsonPath('data.input_snapshot.daily_quantity', 300)
            ->assertJsonPath('data.input_snapshot.currency', 'XOF');

        $this->assertDatabaseHas('financial_analyses', [
            'financial_collection_id' => $collection->id,
            'daily_revenue' => 60000,
            'daily_production_cost' => 36000,
            'daily_gross_margin' => 24000,
        ]);

        // Vérification de l'intégrité de l'historique après modification de la collecte
        $collection->update([
            'selling_price' => 250.00,
            'daily_quantity' => 500,
        ]);

        $analysis = FinancialAnalysis::first();
        $this->assertEquals('60000.00', $analysis->daily_revenue);
        $this->assertEquals('200.00', $analysis->input_snapshot['selling_price']);
        $this->assertEquals(300, $analysis->input_snapshot['daily_quantity']);
    }
}
