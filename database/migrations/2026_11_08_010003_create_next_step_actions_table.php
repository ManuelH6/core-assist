<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('next_step_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('next_step_router_id')->constrained('next_step_routers')->cascadeOnDelete();
            $table->foreignId('recommendation_id')->nullable()->constrained('recommendations')->nullOnDelete();
            $table->string('reference', 30);
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('expected_result')->nullable();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('responsible_name')->nullable();
            $table->string('priority', 30)->default('high'); // critical, high, medium, low
            $table->string('horizon', 30)->default('short_term'); // immediate, short_term, medium_term, long_term
            $table->date('planned_start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('proposed'); // proposed, validated, planned, in_progress, completed, blocked, abandoned
            $table->text('actual_result')->nullable();
            $table->string('problem_reference', 50)->nullable();
            $table->string('finding_reference', 50)->nullable();
            $table->string('decision_reference', 50)->nullable();
            $table->timestamps();

            $table->index(['next_step_router_id', 'status']);
            $table->index(['next_step_router_id', 'priority']);
            $table->index(['next_step_router_id', 'horizon']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('next_step_actions');
    }
};
