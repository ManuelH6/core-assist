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
        Schema::create('restitution_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('restitution_contents')->cascadeOnDelete();
            $table->string('source_tool', 30); // 'mfs', 'dsc', 'dir', 'sig', 'd360', 'psg'
            $table->string('source_type', 50); // 'mfs_framing', 'dsc_item', 'dir_item', 'sig_contribution', 'd360_assessment', 'd360_finding', 'd360_summary', 'psg_problem', 'psg_cause'
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference', 100)->nullable();
            $table->text('source_summary')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['content_id', 'source_tool']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restitution_sources');
    }
};
