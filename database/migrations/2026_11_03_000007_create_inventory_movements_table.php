<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('inventory_products')->restrictOnDelete();
            $table->string('type', 30);
            $table->decimal('quantity', 14, 3);
            $table->smallInteger('direction');
            $table->decimal('previous_stock', 14, 3);
            $table->decimal('new_stock', 14, 3);
            $table->string('reason', 500);
            $table->string('reference', 100)->nullable();
            $table->nullableMorphs('source');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
