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
        Schema::table('milk_collections', function (Blueprint $table) {
            $table->string('correction_status', 30)->nullable()->index();
            $table->text('correction_reason')->nullable();
            $table->timestampTz('correction_requested_at')->nullable();
            $table->foreignId('correction_requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milk_collections', function (Blueprint $table) {
            $table->dropForeign(['correction_requested_by']);
            $table->dropIndex(['correction_status']);
            $table->dropColumn([
                'correction_status',
                'correction_reason',
                'correction_requested_at',
                'correction_requested_by',
            ]);
        });
    }
};
