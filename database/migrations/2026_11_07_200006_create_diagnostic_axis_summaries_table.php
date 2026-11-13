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
        Schema::create('diagnostic_axis_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matrix_id')->constrained('diagnostic_360_matrices')->cascadeOnDelete();
            $table->string('domain', 100);
            $table->string('axis', 150);
            $table->text('summary_text')->nullable();
            $table->text('strengths_summary')->nullable();
            $table->text('difficulties_summary')->nullable();
            $table->text('information_gaps')->nullable();
            $table->text('contradictions_noted')->nullable();
            $table->text('further_investigations_needed')->nullable();
            $table->timestamps();

            $table->unique(['matrix_id', 'domain', 'axis']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_axis_summaries');
    }
};
