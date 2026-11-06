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
        Schema::create('financial_collections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('entry_date')->nullable();
            $table->string('currency', 3)->default('XOF');
            $table->decimal('selling_price', 15, 2);
            $table->unsignedInteger('daily_quantity');
            $table->decimal('unit_production_cost', 15, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_collections');
    }
};
