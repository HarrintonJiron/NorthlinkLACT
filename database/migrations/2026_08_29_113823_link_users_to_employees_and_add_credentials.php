<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('employees')
                ->restrictOnDelete();
            $table->string('username', 50)->nullable()->unique()->after('employee_id');
            $table->string('pin')->nullable()->after('password');
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropUnique(['employee_id']);
            $table->dropUnique(['username']);
            $table->dropColumn(['employee_id', 'username', 'pin']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
