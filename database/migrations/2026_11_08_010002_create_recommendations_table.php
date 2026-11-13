<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('next_step_router_id')->constrained('next_step_routers')->cascadeOnDelete();
            $table->string('reference', 30);
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('justification')->nullable();
            $table->string('priority', 30)->default('high'); // critical, high, medium, low
            $table->string('status', 30)->default('proposed'); // proposed, validated, in_progress, implemented, rejected
            $table->string('domain', 100)->nullable();
            $table->string('axis', 150)->nullable();
            $table->json('input_snapshot')->nullable();
            $table->json('output_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['next_step_router_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
