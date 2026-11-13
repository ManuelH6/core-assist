<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_item_id')->constrained('archive_items')->cascadeOnDelete();
            $table->string('source_tool', 50); // mfs, dsc, dir, sig, d360, psg, drd, ldc, nsr, project, client
            $table->string('source_type', 50); // framing, scope_item, intake_item, stakeholder, finding, problem, section, decision, commitment, file
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference', 50)->nullable();
            $table->string('relationship_type', 50)->default('origin'); // origin, justification, evidence, version_of, dependency
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['archive_item_id', 'source_tool']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_sources');
    }
};
