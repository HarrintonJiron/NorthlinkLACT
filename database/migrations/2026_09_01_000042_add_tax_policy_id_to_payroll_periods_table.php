<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_policy_id')->nullable()->after('pay_frequency');
            $table->index('tax_policy_id');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropIndex(['tax_policy_id']);
            $table->dropColumn('tax_policy_id');
        });
    }
};
