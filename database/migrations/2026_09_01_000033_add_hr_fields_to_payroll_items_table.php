<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->decimal('bonus_amount', 12, 2)->default(0)->after('base_salary');
            $table->decimal('loan_deduction', 12, 2)->default(0)->after('other_deductions');
            $table->unsignedSmallInteger('days_worked')->nullable()->after('loan_deduction');
            $table->unsignedSmallInteger('vacation_days')->default(0)->after('days_worked');
            $table->unsignedSmallInteger('absence_days')->default(0)->after('vacation_days');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn(['bonus_amount', 'loan_deduction', 'days_worked', 'vacation_days', 'absence_days']);
        });
    }
};
