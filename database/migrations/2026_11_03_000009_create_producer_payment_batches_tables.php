<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producer_payment_batches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->date('week_start');
            $table->date('week_end')->unique();
            $table->string('status', 20)->default('closed');
            $table->unsignedInteger('producer_count');
            $table->decimal('total_liters', 14, 2);
            $table->decimal('gross_amount', 14, 2);
            $table->decimal('advance_amount', 14, 2);
            $table->decimal('net_amount', 14, 2);
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at');
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('finance_transaction_id')->nullable()->constrained('finance_transactions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('producer_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('producer_payment_batches')->cascadeOnDelete();
            $table->foreignId('producer_id')->constrained('producers')->restrictOnDelete();
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->string('producer_code', 30);
            $table->string('producer_name');
            $table->string('route_code', 30)->nullable();
            $table->decimal('liters', 14, 2);
            $table->decimal('price_per_liter', 12, 4);
            $table->decimal('gross_amount', 14, 2);
            $table->decimal('advance_amount', 14, 2);
            $table->decimal('net_amount', 14, 2);
            $table->json('daily_liters');
            $table->decimal('average_density', 8, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'producer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producer_payment_items');
        Schema::dropIfExists('producer_payment_batches');
    }
};
