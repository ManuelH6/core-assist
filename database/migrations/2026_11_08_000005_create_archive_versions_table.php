<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_item_id')->constrained('archive_items')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('version_tag', 50)->nullable(); // v1.0, v2.0-valide...
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('location_path')->nullable();
            $table->json('payload_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('produced_at')->nullable();
            $table->timestamp('archived_at')->useCurrent();
            $table->text('changelog')->nullable();
            $table->timestamps();

            $table->unique(['archive_item_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_versions');
    }
};
