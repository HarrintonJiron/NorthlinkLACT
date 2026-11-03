<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_cost_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('description');
            $table->decimal('amount', 14, 2);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['production_batch_id', 'category']);
        });

        Schema::create('finished_product_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_product_id')->constrained('inventory_products')->restrictOnDelete();
            $table->string('lot_number', 50)->unique();
            $table->decimal('quantity', 14, 3);
            $table->decimal('available_quantity', 14, 3);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->date('expiration_date')->nullable();
            $table->string('status', 20)->default('available');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['inventory_product_id', 'status']);
        });

        Schema::create('product_dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->date('dispatch_date');
            $table->string('destination');
            $table->string('recipient')->nullable();
            $table->string('status', 20)->default('dispatched');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'dispatch_date']);
        });

        Schema::create('product_dispatch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained('product_dispatches')->cascadeOnDelete();
            $table->foreignId('finished_product_lot_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_product_id')->constrained('inventory_products')->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 4);
            $table->timestamps();
            $table->unique(['dispatch_id', 'finished_product_lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_dispatch_items');
        Schema::dropIfExists('product_dispatches');
        Schema::dropIfExists('finished_product_lots');
        Schema::dropIfExists('production_cost_entries');
    }
};
