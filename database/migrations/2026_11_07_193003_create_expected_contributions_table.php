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
        Schema::create('expected_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grid_id')->constrained('stakeholder_input_grids')->cascadeOnDelete();
            $table->foreignId('stakeholder_id')->constrained('stakeholders')->cascadeOnDelete();
            $table->string('scope_item_id')->nullable();
            $table->foreignId('required_information_id')->nullable()->constrained('required_information')->nullOnDelete();
            $table->string('domain', 100);
            $table->string('axis', 150);
            $table->string('subaxis', 150)->nullable();
            $table->text('expected_contribution');
            $table->text('justification')->nullable();
            $table->string('modality', 50)->default('interview');
            $table->string('priority', 20)->default('normal');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('to_solicit');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['grid_id', 'status']);
            $table->index(['stakeholder_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expected_contributions');
    }
};
