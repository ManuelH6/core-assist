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
        Schema::create('problem_causes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('problem_id')->constrained('structured_problems')->cascadeOnDelete();
            $table->string('cause');
            $table->string('nature', 50)->default('organizational'); // 'organizational', 'human', 'financial', 'technical', 'strategic', 'other'
            $table->text('explanation')->nullable();
            $table->string('confirmation_level', 30)->default('hypothesis'); // 'hypothesis', 'partially_supported', 'confirmed'
            $table->text('comments')->nullable();
            $table->foreignId('identified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['problem_id', 'confirmation_level']);
            $table->index(['problem_id', 'nature']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('problem_causes');
    }
};
