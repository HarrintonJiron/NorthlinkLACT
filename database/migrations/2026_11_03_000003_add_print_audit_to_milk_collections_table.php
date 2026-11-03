<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milk_collections', function (Blueprint $table): void {
            $table->timestampTz('original_printed_at')->nullable()->after('density');
            $table->unsignedSmallInteger('print_copy_count')->default(0)->after('original_printed_at');
        });
    }

    public function down(): void
    {
        Schema::table('milk_collections', function (Blueprint $table): void {
            $table->dropColumn(['original_printed_at', 'print_copy_count']);
        });
    }
};
