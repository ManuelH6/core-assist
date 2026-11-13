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
        Schema::create('required_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('register_id')->constrained('diagnostic_intake_registers')->cascadeOnDelete();
            $table->string('domain', 100);
            $table->string('axis', 150);
            $table->string('subaxis', 150)->nullable();
            $table->string('information_name');
            $table->text('justification')->nullable();
            $table->string('expected_source')->nullable();
            $table->string('collection_method', 50)->default('document');
            $table->string('priority', 20)->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->string('verification_status', 30)->default('unverified');
            $table->text('observations')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['register_id', 'status']);
            $table->index(['register_id', 'domain']);
            $table->index(['assigned_to', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('required_information');
    }
};
