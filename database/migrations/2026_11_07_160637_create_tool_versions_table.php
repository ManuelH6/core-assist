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
        Schema::create('tool_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
            $table->string('version_number', 30);
            $table->json('definition');
            $table->json('configuration_schema')->nullable();
            $table->json('business_rules')->nullable();
            $table->string('interface_type', 50)->nullable();
            $table->json('output_definition')->nullable();
            $table->string('status', 30)->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['tool_id', 'version_number']);
            $table->index(['tool_id', 'status']);
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('tool_versions')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('tool_versions');
    }
};
