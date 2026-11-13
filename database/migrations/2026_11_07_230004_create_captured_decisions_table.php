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
        Schema::create('captured_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capture_id')->constrained('live_decision_captures')->cascadeOnDelete();
            $table->foreignId('exchange_id')->nullable()->constrained('decision_exchanges')->nullOnDelete();
            $table->foreignId('point_id')->nullable()->constrained('discussion_points')->nullOnDelete();
            $table->string('reference', 30); // 'DEC-001', 'DEC-002'
            $table->string('title');
            $table->text('decision_text');
            $table->string('nature', 50)->default('arbitrage'); // 'validation', 'arbitrage', 'accord', 'refusal', 'postponement', 'orientation', 'other'
            $table->string('decision_maker_type', 50)->default('client'); // 'client', 'cabinet', 'joint', 'steering_committee'
            $table->string('decision_maker_name')->nullable();
            $table->text('justification')->nullable();
            $table->dateTime('decided_at');
            $table->string('status', 30)->default('taken'); // 'proposed', 'taken', 'to_confirm', 'revised', 'cancelled'
            $table->text('revision_notes')->nullable();
            $table->foreignId('previous_version_id')->nullable()->constrained('captured_decisions')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['capture_id', 'status']);
            $table->index(['capture_id', 'nature']);
            $table->index(['capture_id', 'reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('captured_decisions');
    }
};
