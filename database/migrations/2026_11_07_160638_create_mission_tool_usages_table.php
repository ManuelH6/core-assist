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
        Schema::create('mission_tool_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->foreignId('tool_version_id')->constrained('tool_versions')->restrictOnDelete();
            $table->string('name');
            $table->json('configuration')->nullable();
            $table->string('status', 30)->default('configured');
            $table->foreignId('configured_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['mission_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_tool_usages');
    }
};
