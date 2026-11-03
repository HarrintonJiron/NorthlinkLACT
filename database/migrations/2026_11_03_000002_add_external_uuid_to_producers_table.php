<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producers', function (Blueprint $table): void {
            $table->uuid('external_uuid')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('producers', function (Blueprint $table): void {
            $table->dropUnique(['external_uuid']);
            $table->dropColumn('external_uuid');
        });
    }
};
