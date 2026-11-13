<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pack_id')->constrained('diagnostic_archive_packs')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('archive_categories')->nullOnDelete();
            $table->string('reference', 30);
            $table->string('title');
            $table->string('item_type', 50)->default('data_record'); // document, tool_snapshot, data_record, contribution, decision, restitution_version, deliverable, export, other
            $table->string('source_tool', 50)->default('external'); // mfs, dsc, dir, sig, d360, psg, drd, ldc, nsr, external, mission
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference', 50)->nullable();
            $table->unsignedInteger('current_version_number')->default(1);
            $table->string('author_name')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('produced_at')->nullable();
            $table->timestamp('archived_at')->useCurrent();
            $table->text('description')->nullable();
            $table->string('location_type', 30)->default('database_record'); // database_record, file_path, external_url
            $table->text('location_path')->nullable();
            $table->string('storage_disk', 50)->default('local');
            $table->json('metadata')->nullable();
            $table->string('status', 30)->default('available'); // available, replaced, deprecated, archived
            $table->timestamps();

            $table->index(['pack_id', 'source_tool']);
            $table->index(['pack_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_items');
    }
};
