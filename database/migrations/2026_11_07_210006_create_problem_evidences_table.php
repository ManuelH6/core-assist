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
        Schema::create('problem_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grid_id')->constrained('problem_structuring_grids')->cascadeOnDelete();
            $table->foreignId('problem_id')->constrained('structured_problems')->cascadeOnDelete();
            $table->foreignId('cause_id')->nullable()->constrained('problem_causes')->cascadeOnDelete();
            $table->foreignId('consequence_id')->nullable()->constrained('problem_consequences')->cascadeOnDelete();
            $table->string('source_type', 50)->default('observation'); // 'd360_evidence', 'dir_item', 'sig_contribution', 'observation', 'other'
            $table->foreignId('d360_evidence_id')->nullable()->constrained('diagnostic_evidences')->nullOnDelete();
            $table->foreignId('received_item_id')->nullable()->constrained('received_items')->nullOnDelete();
            $table->foreignId('received_contribution_id')->nullable()->constrained('received_contributions')->nullOnDelete();
            $table->string('title');
            $table->text('content_summary')->nullable();
            $table->string('assessed_reliability', 30)->default('solid');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['problem_id', 'source_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('problem_evidences');
    }
};
