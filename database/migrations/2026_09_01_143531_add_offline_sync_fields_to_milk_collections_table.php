<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milk_collections', function (Blueprint $table) {
            $table->uuid('external_uuid')->nullable()->unique();
            $table->foreignId('route_run_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('sync_status', 30)->default('synced');
            $table->timestampTz('synced_at')->nullable();

            $table->index(['sync_status', 'synced_at']);
        });
    }

    public function down(): void
    {
        Schema::table('milk_collections', function (Blueprint $table) {
            $table->dropForeign(['route_run_id']);
            $table->dropForeign(['device_id']);
            $table->dropIndex(['sync_status', 'synced_at']);
            $table->dropUnique(['external_uuid']);
            $table->dropColumn([
                'external_uuid',
                'route_run_id',
                'device_id',
                'sync_status',
                'synced_at',
            ]);
        });
    }
};
