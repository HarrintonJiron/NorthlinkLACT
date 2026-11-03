<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_deductions', function (Blueprint $table) {
            $table->string('concept')->nullable()->after('type');
            $table->unsignedBigInteger('payroll_item_id')->nullable()->after('status');
            $table->string('notes', 500)->nullable()->after('payroll_item_id');

            $table->index('payroll_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('employee_deductions', function (Blueprint $table) {
            $table->dropIndex(['payroll_item_id']);
            $table->dropColumn(['concept', 'payroll_item_id', 'notes']);
        });
    }
};
