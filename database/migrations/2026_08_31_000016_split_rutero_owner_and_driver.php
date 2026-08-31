<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ruteros', function (Blueprint $table) {
            $table->string('owner_name')->nullable()->after('route_id');
            $table->string('owner_identity_number', 20)->nullable()->after('owner_name');
            $table->string('owner_phone', 20)->nullable()->after('owner_identity_number');
            $table->string('vehicle_description')->nullable()->after('owner_phone');
            $table->string('driver_name')->nullable()->after('vehicle_plate');
            $table->string('driver_identity_number', 20)->nullable()->after('driver_name');
            $table->string('driver_phone', 20)->nullable()->after('driver_identity_number');
        });

        foreach (DB::table('ruteros')->get() as $rutero) {
            DB::table('ruteros')->where('id', $rutero->id)->update([
                'owner_name' => $rutero->full_name,
                'owner_identity_number' => $rutero->identity_number,
                'owner_phone' => $rutero->phone,
                'driver_name' => $rutero->full_name,
                'driver_identity_number' => $rutero->identity_number,
                'driver_phone' => $rutero->phone,
            ]);
        }

        Schema::table('ruteros', function (Blueprint $table) {
            $table->dropUnique(['identity_number']);
            $table->dropColumn(['full_name', 'identity_number', 'phone']);
        });

        Schema::table('ruteros', function (Blueprint $table) {
            $table->unique('owner_identity_number');
        });
    }

    public function down(): void
    {
        Schema::table('ruteros', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('route_id');
            $table->string('identity_number', 20)->nullable()->after('full_name');
            $table->string('phone', 20)->nullable()->after('identity_number');
        });

        foreach (DB::table('ruteros')->get() as $rutero) {
            DB::table('ruteros')->where('id', $rutero->id)->update([
                'full_name' => $rutero->owner_name,
                'identity_number' => $rutero->owner_identity_number,
                'phone' => $rutero->owner_phone,
            ]);
        }

        Schema::table('ruteros', function (Blueprint $table) {
            $table->dropUnique(['owner_identity_number']);
            $table->dropColumn([
                'owner_name',
                'owner_identity_number',
                'owner_phone',
                'vehicle_description',
                'driver_name',
                'driver_identity_number',
                'driver_phone',
            ]);
            $table->unique('identity_number');
        });
    }
};
