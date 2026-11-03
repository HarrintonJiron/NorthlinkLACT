<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milk_collections', function (Blueprint $table) {
            if (! Schema::hasColumn('milk_collections', 'external_uuid')) {
                $table->uuid('external_uuid')->nullable()->unique();
            }

            if (! Schema::hasColumn('milk_collections', 'route_run_id')) {
                $table->foreignId('route_run_id')->nullable()->constrained()->restrictOnDelete();
            }

            if (! Schema::hasColumn('milk_collections', 'device_id')) {
                $table->foreignId('device_id')->nullable()->constrained()->restrictOnDelete();
            }

            if (! Schema::hasColumn('milk_collections', 'sync_status')) {
                $table->string('sync_status', 30)->default('synced');
            }

            if (! Schema::hasColumn('milk_collections', 'synced_at')) {
                $table->timestampTz('synced_at')->nullable();
                $table->index(['sync_status', 'synced_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('milk_collections', function (Blueprint $table) {
            if (Schema::hasColumn('milk_collections', 'route_run_id')) {
                $table->dropForeign(['route_run_id']);
            }

            if (Schema::hasColumn('milk_collections', 'device_id')) {
                $table->dropForeign(['device_id']);
            }

            if (Schema::hasColumn('milk_collections', 'synced_at')) {
                $table->dropIndex(['sync_status', 'synced_at']);
            }

            if (Schema::hasColumn('milk_collections', 'external_uuid')) {
                $table->dropUnique(['external_uuid']);
            }

            foreach (['external_uuid', 'route_run_id', 'device_id', 'sync_status', 'synced_at'] as $column) {
                if (Schema::hasColumn('milk_collections', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
