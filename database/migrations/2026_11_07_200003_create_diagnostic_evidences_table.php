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
        Schema::create('diagnostic_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matrix_id')->constrained('diagnostic_360_matrices')->cascadeOnDelete();
            $table->string('source_type', 50)->default('direct_observation');
            $table->foreignId('received_item_id')->nullable()->constrained('received_items')->nullOnDelete();
            $table->foreignId('received_contribution_id')->nullable()->constrained('received_contributions')->nullOnDelete();
            $table->string('title');
            $table->text('content_summary')->nullable();
            $table->string('assessed_reliability', 30)->default('solid');
            $table->text('limits_or_reservations')->nullable();
            $table->text('comments')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['matrix_id', 'source_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_evidences');
    }
};
