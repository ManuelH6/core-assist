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
        Schema::create('diagnostic_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matrix_id')->constrained('diagnostic_360_matrices')->cascadeOnDelete();
            $table->string('scope_item_id')->nullable();
            $table->string('domain', 100);
            $table->string('axis', 150);
            $table->string('subaxis', 150)->nullable();
            $table->string('evaluated_element')->nullable();
            $table->text('observed_situation')->nullable();
            $table->text('assessment_text')->nullable();
            $table->string('rating_level', 50)->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['matrix_id', 'domain']);
            $table->index(['matrix_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_assessments');
    }
};
