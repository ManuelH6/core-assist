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
        Schema::create('received_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grid_id')->constrained('stakeholder_input_grids')->cascadeOnDelete();
            $table->foreignId('expected_contribution_id')->nullable()->constrained('expected_contributions')->nullOnDelete();
            $table->foreignId('stakeholder_id')->constrained('stakeholders')->cascadeOnDelete();
            $table->dateTime('received_at')->nullable();
            $table->string('modality', 50)->default('interview');
            $table->text('summary');
            $table->text('key_findings')->nullable();
            $table->string('associated_document')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reliability_perception', 50)->default('to_evaluate');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['grid_id', 'stakeholder_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('received_contributions');
    }
};
