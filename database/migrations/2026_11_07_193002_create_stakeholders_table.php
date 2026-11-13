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
        Schema::create('stakeholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grid_id')->constrained('stakeholder_input_grids')->cascadeOnDelete();
            $table->string('name');
            $table->string('stakeholder_type', 50)->default('internal');
            $table->string('position')->nullable();
            $table->string('organization')->nullable();
            $table->string('role_in_diagnostic')->nullable();
            $table->string('contact_details')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['grid_id', 'stakeholder_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stakeholders');
    }
};
