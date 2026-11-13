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
        Schema::create('framing_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('framing_id')->constrained('framings')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('content');
            $table->string('status', 30)->default('draft');
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('client_decision_at')->nullable();
            $table->text('client_feedback')->nullable();
            $table->timestamps();

            $table->unique(['framing_id', 'version_number']);
            $table->index(['framing_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('framing_versions');
    }
};
