<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_uuid')->unique();
            $table->foreignId('route_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('device_id')->constrained()->restrictOnDelete();
            $table->date('run_date');
            $table->string('status', 30)->default('in_progress');
            $table->string('sync_status', 30)->default('synced');
            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->timestamps();

            $table->index(['route_id', 'run_date']);
            $table->index(['device_id', 'sync_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_runs');
    }
};
