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
        Schema::create('diagnostic_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matrix_id')->constrained('diagnostic_360_matrices')->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained('diagnostic_assessments')->cascadeOnDelete();
            $table->string('finding_type', 30); // 'strength', 'difficulty', 'hypothesis'
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('possible_causes')->nullable();
            $table->text('potential_consequences')->nullable();
            $table->string('severity_or_importance', 30)->default('medium'); // 'minor', 'medium', 'major', 'critical'
            $table->boolean('is_validated')->default(false);
            $table->foreignId('identified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['matrix_id', 'finding_type']);
            $table->index(['assessment_id', 'finding_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_findings');
    }
};
