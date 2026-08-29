<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->string('owner_name')->nullable()->after('name');
            $table->string('owner_identity_number', 20)->nullable()->after('owner_name');
            $table->string('owner_phone', 20)->nullable()->after('owner_identity_number');
            $table->string('vehicle_plate', 20)->nullable()->after('owner_phone');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn([
                'owner_name',
                'owner_identity_number',
                'owner_phone',
                'vehicle_plate',
            ]);
        });
    }
};
