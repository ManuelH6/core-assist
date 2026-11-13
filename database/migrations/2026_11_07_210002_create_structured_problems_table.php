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
        Schema::create('structured_problems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grid_id')->constrained('problem_structuring_grids')->cascadeOnDelete();
            $table->string('reference', 30);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('domain', 100);
            $table->string('axis', 150);
            $table->string('subaxis', 150)->nullable();
            $table->string('scope_item_id')->nullable();
            $table->foreignId('diagnostic_finding_id')->nullable()->constrained('diagnostic_findings')->nullOnDelete();
            $table->text('manifestations')->nullable();
            $table->string('status', 30)->default('to_examine'); // 'to_examine', 'in_analysis', 'structured', 'validated'
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['grid_id', 'domain']);
            $table->index(['grid_id', 'status']);
            $table->index(['grid_id', 'reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('structured_problems');
    }
};
