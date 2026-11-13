<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('next_step_routers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete()->unique();
            $table->string('reference', 30)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('in_progress'); // in_progress, validated, closed
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('input_snapshot')->nullable();
            $table->json('output_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('next_step_routers');
    }
};
