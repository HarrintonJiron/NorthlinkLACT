<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employee_deductions')
            ->where('type', 'anticipo')
            ->update(['type' => 'adelanto_salario']);
    }

    public function down(): void
    {
        DB::table('employee_deductions')
            ->where('type', 'adelanto_salario')
            ->update(['type' => 'anticipo']);
    }
};
