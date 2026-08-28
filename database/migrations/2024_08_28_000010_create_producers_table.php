<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('full_name');
            $table->string('identity_number', 50)->unique();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('community')->nullable();
            $table->string('municipality')->nullable();
            $table->string('department')->nullable();
            $table->decimal('latitude', 8, 6)->nullable();
            $table->decimal('longitude', 8, 6)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producers');
    }
};
