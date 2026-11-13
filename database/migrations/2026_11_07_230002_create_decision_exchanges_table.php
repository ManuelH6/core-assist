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
        Schema::create('decision_exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capture_id')->constrained('live_decision_captures')->cascadeOnDelete();
            $table->string('title');
            $table->dateTime('exchanged_at');
            $table->string('modality', 50)->default('meeting'); // 'meeting', 'interview', 'videoconference', 'workshop', 'steering_committee', 'phone_call', 'other'
            $table->string('location')->nullable();
            $table->text('context')->nullable();
            $table->text('observations')->nullable();
            $table->json('attendees')->nullable(); // Liste structurée des participants
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['capture_id', 'exchanged_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_exchanges');
    }
};
