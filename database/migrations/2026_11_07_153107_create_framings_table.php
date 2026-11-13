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
        Schema::create('framings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->restrictOnDelete();
            $table->string('reference', 50)->unique();
            $table->string('title');
            $table->string('status', 30)->default('draft');
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['request_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('framings');
    }
};
