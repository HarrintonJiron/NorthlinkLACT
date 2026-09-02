<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_vacations', function (Blueprint $table) {
            $table->boolean('paid')->default(true)->after('days');
        });
    }

    public function down(): void
    {
        Schema::table('employee_vacations', function (Blueprint $table) {
            $table->dropColumn('paid');
        });
    }
};
