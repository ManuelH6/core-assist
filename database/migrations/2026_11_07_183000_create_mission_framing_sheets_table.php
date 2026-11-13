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
        Schema::create('mission_framing_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->unique()->constrained('missions')->cascadeOnDelete();
            $table->string('title');
            $table->text('specific_objective')->nullable();
            $table->text('expected_results')->nullable();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('mission_leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->text('execution_conditions')->nullable();
            $table->text('operational_constraints')->nullable();
            $table->text('additional_observations')->nullable();
            $table->string('status', 30)->default('draft');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('inherited_data')->nullable();
            $table->json('field_provenance')->nullable();
            $table->json('input_snapshot')->nullable();
            $table->json('output_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['mission_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_framing_sheets');
    }
};
