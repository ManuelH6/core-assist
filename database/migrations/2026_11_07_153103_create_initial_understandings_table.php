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
        Schema::create('initial_understandings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->unique()->constrained('requests')->cascadeOnDelete();
            $table->text('context')->nullable();
            $table->text('expressed_need')->nullable();
            $table->text('reformulated_need')->nullable();
            $table->text('expected_outcome')->nullable();
            $table->text('central_question')->nullable();
            $table->json('known_constraints')->nullable();
            $table->json('missing_information')->nullable();
            $table->text('initial_notes')->nullable();
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initial_understandings');
    }
};
