<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milk_collections', function (Blueprint $table): void {
            $table->decimal('density', 5, 3)->nullable()->after('liters');
        });
    }

    public function down(): void
    {
        Schema::table('milk_collections', function (Blueprint $table): void {
            $table->dropColumn('density');
        });
    }
};
