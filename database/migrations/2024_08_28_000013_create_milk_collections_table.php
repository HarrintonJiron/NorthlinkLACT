<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milk_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('plant_id')->constrained()->onDelete('cascade');
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->foreignId('producer_id')->constrained()->onDelete('cascade');
            $table->date('collection_date');
            $table->decimal('liters', 10, 2);
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('acidity', 5, 2)->nullable();
            $table->decimal('fat_percentage', 5, 2)->nullable();
            $table->foreignId('collected_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'plant_id', 'route_id', 'producer_id', 'collection_date'], 'unique_collection');
            $table->index(['route_id', 'collection_date']);
            $table->index(['producer_id', 'collection_date']);
            $table->index(['collection_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milk_collections');
    }
};
