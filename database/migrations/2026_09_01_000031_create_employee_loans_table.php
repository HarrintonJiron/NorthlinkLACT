<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason', 500)->nullable();
            $table->decimal('installment_amount', 12, 2);
            $table->decimal('remaining_balance', 12, 2);
            $table->string('status', 20)->default('active');
            $table->date('granted_at');
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
