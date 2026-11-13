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
        Schema::create('restitution_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained('diagnostic_restitution_decks')->cascadeOnDelete();
            $table->unsignedInteger('version_number')->default(1);
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('restitution_snapshot'); // Copie figée de toute l'arborescence des sections, contenus et sources
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->unique(['deck_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restitution_versions');
    }
};
