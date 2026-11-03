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
        Schema::create('product_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('inventory_products')->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->foreignId('unit_id')->constrained('inventory_units');
            $table->decimal('conversion_factor', 8, 4)->default(1.0000);
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'name']);
            $table->index(['product_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_presentations');
    }
};
