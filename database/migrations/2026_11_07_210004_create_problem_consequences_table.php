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
        Schema::create('problem_consequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('problem_id')->constrained('structured_problems')->cascadeOnDelete();
            $table->string('consequence');
            $table->text('description')->nullable();
            $table->string('domain', 100)->nullable(); // Domaine concerné
            $table->string('importance', 30)->default('medium'); // 'minor', 'medium', 'high', 'critical'
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['problem_id', 'importance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('problem_consequences');
    }
};
