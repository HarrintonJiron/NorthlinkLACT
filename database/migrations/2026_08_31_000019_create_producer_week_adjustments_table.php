<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producer_week_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_id')->constrained()->cascadeOnDelete();
            $table->date('week_end');
            $table->decimal('density_price', 10, 4)->nullable();
            $table->decimal('advance_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['producer_id', 'week_end']);
            $table->index('week_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producer_week_adjustments');
    }
};
