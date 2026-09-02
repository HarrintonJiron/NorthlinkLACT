<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('effective_from');
            $table->decimal('inss_employee_rate', 6, 4);
            $table->decimal('inss_employer_rate', 6, 4);
            $table->decimal('inatec_rate', 6, 4);
            $table->json('ir_brackets');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('effective_from');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_policies');
    }
};
