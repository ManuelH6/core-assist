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
        Schema::create('restitution_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained('diagnostic_restitution_decks')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->string('title');
            $table->string('section_key', 50)->nullable(); // e.g. 'presentation', 'methodology', 'scope', 'general_synthesis', 'domain_analysis', 'problems', 'priorities', 'conclusion'
            $table->text('objective')->nullable();
            $table->string('status', 30)->default('to_prepare'); // 'to_prepare', 'drafting', 'to_review', 'validated'
            $table->text('observations')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('validated_at')->nullable();
            $table->timestamps();

            $table->index(['deck_id', 'position']);
            $table->index(['deck_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restitution_sections');
    }
};
