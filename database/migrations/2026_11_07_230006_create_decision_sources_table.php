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
        Schema::create('decision_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decision_id')->constrained('captured_decisions')->cascadeOnDelete();
            $table->string('source_tool', 30); // 'd360', 'psg', 'drd', 'dir', 'sig', 'mfs'
            $table->string('source_type', 50); // 'problem', 'cause', 'finding', 'assessment', 'restitution_section'
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference', 100)->nullable();
            $table->text('source_summary')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['decision_id', 'source_tool']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_sources');
    }
};
