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
        Schema::create('tool_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_tool_usage_id')->constrained('mission_tool_usages')->cascadeOnDelete();
            $table->unsignedInteger('execution_number');
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('executed_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['mission_tool_usage_id', 'execution_number']);
            $table->index(['mission_tool_usage_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_executions');
    }
};
