<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_role_id')->constrained()->restrictOnDelete();
            $table->string('full_name');
            $table->string('identity_number', 50)->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 50)->nullable();
            $table->date('hired_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_role_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
