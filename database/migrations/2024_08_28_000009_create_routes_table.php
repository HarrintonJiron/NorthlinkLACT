<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('plant_id')->constrained()->onDelete('cascade');
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'plant_id', 'code']);
            $table->index(['company_id', 'active']);
            $table->index(['plant_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
