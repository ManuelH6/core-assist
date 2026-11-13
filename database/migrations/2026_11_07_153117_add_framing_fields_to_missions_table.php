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
        Schema::table('missions', function (Blueprint $table) {
            $table->foreignId('framing_id')->nullable()->after('id')->constrained('framings')->nullOnDelete();
            $table->string('reference', 50)->nullable()->unique()->after('framing_id');
            $table->text('objective')->nullable()->after('description');
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->dateTime('started_at')->nullable()->after('created_by');
            $table->dateTime('closed_at')->nullable()->after('started_at');
            $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();

            $table->index(['framing_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropIndex(['framing_id', 'status']);
            $table->dropForeign(['closed_by']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['framing_id']);
            $table->dropColumn([
                'framing_id',
                'reference',
                'objective',
                'created_by',
                'started_at',
                'closed_at',
                'closed_by',
            ]);
        });
    }
};
