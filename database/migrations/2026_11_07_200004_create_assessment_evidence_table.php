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
        Schema::create('assessment_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('diagnostic_assessments')->cascadeOnDelete();
            $table->foreignId('evidence_id')->constrained('diagnostic_evidences')->cascadeOnDelete();
            $table->string('relevance_degree', 30)->default('direct');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'evidence_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_evidence');
    }
};
