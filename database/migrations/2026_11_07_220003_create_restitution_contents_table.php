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
        Schema::create('restitution_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('restitution_sections')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->string('content_type', 50)->default('text'); // 'text', 'summary_table', 'domain_rating', 'strengths_list', 'difficulties_list', 'problems_list', 'causal_tree', 'conclusion_box', 'callout'
            $table->string('title')->nullable();
            $table->text('body_text')->nullable();
            $table->json('structured_data')->nullable(); // Table data, list items, graph elements, metrics
            $table->boolean('is_custom_editorial')->default(false); // True si rédigé sur-mesure par le consultant
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['section_id', 'position']);
            $table->index(['section_id', 'content_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restitution_contents');
    }
};
