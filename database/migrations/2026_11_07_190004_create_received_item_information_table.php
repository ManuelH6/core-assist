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
        Schema::create('received_item_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('received_item_id')->constrained('received_items')->cascadeOnDelete();
            $table->foreignId('required_information_id')->constrained('required_information')->cascadeOnDelete();
            $table->string('coverage_degree', 30)->default('full');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['received_item_id', 'required_information_id'], 'item_info_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('received_item_information');
    }
};
