<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producer_route_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_id')->constrained()->onDelete('cascade');
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->date('assigned_at');
            $table->date('ended_at')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['producer_id', 'route_id', 'assigned_at'], 'producer_route_unique');
            $table->index(['producer_id', 'assigned_at']);
            $table->index(['route_id', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producer_route_assignments');
    }
};
