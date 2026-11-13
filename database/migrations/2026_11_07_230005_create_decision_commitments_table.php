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
        Schema::create('decision_commitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decision_id')->constrained('captured_decisions')->cascadeOnDelete();
            $table->string('action');
            $table->string('responsible_name')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_stakeholder_id')->nullable()->constrained('stakeholders')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->text('expected_outcome')->nullable();
            $table->string('status', 30)->default('to_do'); // 'to_do', 'in_progress', 'completed', 'blocked', 'cancelled'
            $table->dateTime('completed_at')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['decision_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_commitments');
    }
};
