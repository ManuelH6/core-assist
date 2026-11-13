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
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('works')->nullOnDelete();
            $table->string('reference', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('work_type', 40)->default('analysis');
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30)->default('draft');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['mission_id', 'reference']);
            $table->index(['mission_id', 'status']);
            $table->index(['parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
