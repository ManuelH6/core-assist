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
        Schema::create('diagnostic_domain_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matrix_id')->constrained('diagnostic_360_matrices')->cascadeOnDelete();
            $table->string('domain', 100);
            $table->string('overall_rating', 50)->nullable();
            $table->text('summary_text')->nullable();
            $table->text('key_takeaways')->nullable();
            $table->timestamps();

            $table->unique(['matrix_id', 'domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_domain_summaries');
    }
};
