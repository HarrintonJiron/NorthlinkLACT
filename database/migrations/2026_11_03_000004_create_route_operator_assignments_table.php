<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_operator_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('assigned_date');
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['route_id', 'assigned_date']);
            $table->unique(['user_id', 'assigned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_operator_assignments');
    }
};
