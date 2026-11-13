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
        Schema::create('problem_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grid_id')->constrained('problem_structuring_grids')->cascadeOnDelete();
            $table->foreignId('source_problem_id')->constrained('structured_problems')->cascadeOnDelete();
            $table->foreignId('target_problem_id')->constrained('structured_problems')->cascadeOnDelete();
            $table->string('relation_type', 50); // 'contributes_to', 'causes', 'aggravates', 'depends_on', 'shares_common_cause_with'
            $table->text('explanation')->nullable();
            $table->boolean('is_hypothetical')->default(false);
            $table->timestamps();

            $table->unique(['source_problem_id', 'target_problem_id', 'relation_type'], 'prb_rel_unique');
            $table->index(['grid_id', 'relation_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('problem_relations');
    }
};
