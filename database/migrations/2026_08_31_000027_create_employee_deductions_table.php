<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('type', 30);
            $table->decimal('amount', 12, 2);
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->decimal('installment_amount', 12, 2)->nullable();
            $table->unsignedSmallInteger('installments_total')->nullable();
            $table->unsignedSmallInteger('installments_paid')->default(0);
            $table->date('deduction_date');
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('activa');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'deduction_date']);
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_deductions');
    }
};
