<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_collection_id')
                ->nullable()
                ->constrained('financial_collections')
                ->nullOnDelete();
            $table->decimal('daily_revenue', 15, 2);
            $table->decimal('daily_production_cost', 15, 2);
            $table->decimal('daily_gross_margin', 15, 2);
            $table->json('input_snapshot')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_analyses');
    }
};
