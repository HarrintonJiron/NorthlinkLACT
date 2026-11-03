<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained()->restrictOnDelete();
            $table->string('batch_number', 40)->unique();
            $table->string('status', 30)->default('planned');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('cream_weight_lb', 14, 3)->default(0);
            $table->decimal('trimmings_weight_lb', 14, 3)->default(0);
            $table->decimal('reprocess_weight_lb', 14, 3)->default(0);
            $table->decimal('waste_weight_lb', 14, 3)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['plant_id', 'status']);
            $table->index('started_at');
        });

        Schema::create('batch_milk_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->cascadeOnDelete();
            $table->date('source_date');
            $table->decimal('liters', 14, 3);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['production_batch_id', 'source_date']);
        });

        Schema::create('production_weight_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->decimal('weight_lb', 14, 3);
            $table->string('scale_reference', 100)->nullable();
            $table->timestamp('weighed_at');
            $table->foreignId('weighed_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('original_entry_id')->nullable()->constrained('production_weight_entries')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->text('correction_reason')->nullable();
            $table->timestamps();
            $table->unique(['production_batch_id', 'sequence']);
            $table->index(['production_batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_weight_entries');
        Schema::dropIfExists('batch_milk_inputs');
        Schema::dropIfExists('production_batches');
    }
};
