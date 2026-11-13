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
        Schema::create('problem_priority_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('problem_id')->unique()->constrained('structured_problems')->cascadeOnDelete();
            $table->string('severity', 30)->default('moderate'); // 'minor', 'moderate', 'major', 'critical'
            $table->string('frequency', 30)->default('occasional'); // 'isolated', 'occasional', 'frequent', 'systemic'
            $table->string('scope_extent', 30)->default('departmental'); // 'localized', 'departmental', 'transversal', 'company_wide'
            $table->string('urgency', 30)->default('medium'); // 'low', 'medium', 'high', 'immediate'
            $table->string('strategic_impact', 30)->default('medium'); // 'low', 'medium', 'high', 'vital'
            $table->string('priority_level', 20)->default('P2'); // 'P1', 'P2', 'P3', 'P4'
            $table->text('rationale')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assessed_at')->nullable();
            $table->timestamps();

            $table->index(['priority_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('problem_priority_assessments');
    }
};
