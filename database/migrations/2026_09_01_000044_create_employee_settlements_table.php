<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('termination_type');
            $table->date('hired_at');
            $table->date('termination_date');
            $table->integer('tenure_days');

            $table->date('pending_salary_start');
            $table->date('pending_salary_end');
            $table->integer('pending_salary_days');
            $table->decimal('pending_salary_amount', 12, 2);

            $table->decimal('vacation_days_pending', 8, 2);
            $table->decimal('vacation_amount', 12, 2);

            $table->decimal('aguinaldo_days', 8, 2);
            $table->decimal('aguinaldo_amount', 12, 2);

            $table->string('severance_method');
            $table->decimal('severance_amount', 12, 2);

            $table->decimal('loan_deduction', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);

            $table->decimal('gross_amount', 12, 2);
            $table->decimal('net_amount', 12, 2);

            $table->string('status')->default('draft');
            $table->string('payment_method')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_settlements');
    }
};
