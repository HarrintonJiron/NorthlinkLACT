<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->unique()->after('id');
            $table->string('first_name')->nullable()->after('employee_role_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('area', 100)->nullable()->after('employee_role_id');
            $table->foreignId('plant_id')->nullable()->after('area')->constrained('plants')->nullOnDelete();
            $table->string('status', 20)->default('activo')->after('hired_at');
            $table->string('contract_type', 30)->nullable()->after('status');
            $table->date('contract_start_date')->nullable()->after('contract_type');
            $table->date('contract_end_date')->nullable()->after('contract_start_date');
            $table->decimal('salary', 12, 2)->nullable()->after('contract_end_date');
            $table->boolean('inss_insured')->default(false)->after('salary');
            $table->string('inss_number', 50)->nullable()->after('inss_insured');
            $table->string('payment_method', 30)->nullable()->after('inss_number');
            $table->string('bank_account', 100)->nullable()->after('payment_method');
            $table->string('address')->nullable()->after('phone');
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_phone', 50)->nullable()->after('emergency_contact_name');
        });

        foreach (DB::table('employees')->orderBy('id')->get() as $employee) {
            $parts = preg_split('/\s+/', trim((string) $employee->full_name), 2) ?: ['', ''];

            DB::table('employees')->where('id', $employee->id)->update([
                'code' => 'EMP-'.str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT),
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? '',
                'status' => $employee->active ? 'activo' : 'retirado',
            ]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['employee_role_id', 'active']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['active', 'full_name']);
            $table->index(['employee_role_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('employee_role_id');
            $table->boolean('active')->default(true)->after('hired_at');
        });

        DB::table('employees')->orderBy('id')->each(function (object $employee): void {
            DB::table('employees')->where('id', $employee->id)->update([
                'full_name' => trim("{$employee->first_name} {$employee->last_name}"),
                'active' => $employee->status === 'activo',
            ]);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('full_name')->nullable(false)->change();
            $table->dropConstrainedForeignId('plant_id');
            $table->dropColumn([
                'code',
                'first_name',
                'last_name',
                'area',
                'status',
                'contract_type',
                'contract_start_date',
                'contract_end_date',
                'salary',
                'inss_insured',
                'inss_number',
                'payment_method',
                'bank_account',
                'address',
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);
        });
    }
};
