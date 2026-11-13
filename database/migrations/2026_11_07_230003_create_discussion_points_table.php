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
        Schema::create('discussion_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_id')->constrained('decision_exchanges')->cascadeOnDelete();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('domain', 100)->nullable();
            $table->string('axis', 150)->nullable();
            $table->foreignId('problem_id')->nullable()->constrained('structured_problems')->nullOnDelete();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['exchange_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discussion_points');
    }
};
