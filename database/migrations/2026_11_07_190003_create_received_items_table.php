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
        Schema::create('received_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('register_id')->constrained('diagnostic_intake_registers')->cascadeOnDelete();
            $table->string('title');
            $table->string('item_type', 50)->default('document');
            $table->string('source_origin')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reliability_score', 30)->default('to_assess');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['register_id', 'item_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('received_items');
    }
};
