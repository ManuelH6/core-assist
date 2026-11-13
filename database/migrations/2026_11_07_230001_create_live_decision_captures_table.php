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
        Schema::create('live_decision_captures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->unique()->constrained('missions')->cascadeOnDelete();
            $table->string('title')->default('Live Decision Capture™');
            $table->string('status', 30)->default('in_progress');
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
        Schema::dropIfExists('live_decision_captures');
    }
};
