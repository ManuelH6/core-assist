<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_item_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_item_id')->constrained('archive_items')->cascadeOnDelete();
            $table->foreignId('archive_category_id')->constrained('archive_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['archive_item_id', 'archive_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_item_categories');
    }
};
