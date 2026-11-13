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
        Schema::create('diagnostic_intake_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->unique()->constrained('missions')->cascadeOnDelete();
            $table->string('title')->default('Diagnostic Intake Register™');
            $table->string('status', 30)->default('active');
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
        Schema::dropIfExists('diagnostic_intake_registers');
    }
};
