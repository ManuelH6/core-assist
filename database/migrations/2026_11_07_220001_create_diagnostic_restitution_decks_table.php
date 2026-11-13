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
        Schema::create('diagnostic_restitution_decks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->unique()->constrained('missions')->cascadeOnDelete();
            $table->string('title')->default('Diagnostic Restitution Deck™');
            $table->string('subtitle')->nullable();
            $table->string('status', 30)->default('in_progress'); // 'draft', 'in_progress', 'ready_for_review', 'validated', 'published'
            $table->json('input_snapshot')->nullable();
            $table->json('output_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['mission_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_restitution_decks');
    }
};
