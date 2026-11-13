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
        Schema::create('received_contribution_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('received_contribution_id')->constrained('received_contributions')->cascadeOnDelete();
            $table->foreignId('required_information_id')->constrained('required_information')->cascadeOnDelete();
            $table->text('contribution_value')->nullable();
            $table->timestamps();

            $table->unique(['received_contribution_id', 'required_information_id'], 'rec_contrib_info_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('received_contribution_information');
    }
};
