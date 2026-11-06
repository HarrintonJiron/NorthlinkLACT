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
            $table->timestampTz('correction_resolved_at')->nullable();
            $table->foreignId('correction_resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('correction_resolution_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milk_collections', function (Blueprint $table) {
            $table->dropForeign(['correction_resolved_by']);
            $table->dropColumn([
                'correction_resolved_at',
                'correction_resolved_by',
                'correction_resolution_notes',
            ]);
        });
    }
};
