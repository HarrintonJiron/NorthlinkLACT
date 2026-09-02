<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aguinaldo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aguinaldo_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->decimal('base_salary', 12, 2);
            $table->unsignedSmallInteger('days_employed');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique(['aguinaldo_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aguinaldo_items');
    }
};
