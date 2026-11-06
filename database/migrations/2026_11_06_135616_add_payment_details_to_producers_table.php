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
        Schema::table('producers', function (Blueprint $table) {
            $table->string('payment_method', 30)->default('cash')->after('phone');
            $table->string('bank_name')->nullable()->after('payment_method');
            $table->text('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_account_holder')->nullable()->after('bank_account_number');
            $table->decimal('preferred_price_per_liter', 12, 4)->nullable()->after('bank_account_holder');
            $table->text('payment_notes')->nullable()->after('preferred_price_per_liter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('producers', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'bank_name',
                'bank_account_number',
                'bank_account_holder',
                'preferred_price_per_liter',
                'payment_notes',
            ]);
        });
    }
};
