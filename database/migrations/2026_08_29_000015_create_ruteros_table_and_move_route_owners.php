<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruteros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->unique()->constrained()->onDelete('cascade');
            $table->string('full_name');
            $table->string('identity_number', 20)->unique();
            $table->string('phone', 20);
            $table->string('vehicle_plate', 20);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('active');
        });

        if (Schema::hasColumn('routes', 'owner_name')) {
            $routes = DB::table('routes')
                ->whereNotNull('owner_name')
                ->where('owner_name', '!=', '')
                ->get();

            foreach ($routes as $route) {
                DB::table('ruteros')->insert([
                    'route_id' => $route->id,
                    'full_name' => $route->owner_name,
                    'identity_number' => $route->owner_identity_number ?: sprintf('000-%05d-0000A', $route->id),
                    'phone' => $route->owner_phone ?: '0000-0000',
                    'vehicle_plate' => $route->vehicle_plate ?: 'S/N',
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('routes', function (Blueprint $table) {
                $table->dropColumn([
                    'owner_name',
                    'owner_identity_number',
                    'owner_phone',
                    'vehicle_plate',
                ]);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('routes', 'owner_name')) {
            Schema::table('routes', function (Blueprint $table) {
                $table->string('owner_name')->nullable()->after('name');
                $table->string('owner_identity_number', 20)->nullable()->after('owner_name');
                $table->string('owner_phone', 20)->nullable()->after('owner_identity_number');
                $table->string('vehicle_plate', 20)->nullable()->after('owner_phone');
            });
        }

        foreach (DB::table('ruteros')->get() as $rutero) {
            DB::table('routes')->where('id', $rutero->route_id)->update([
                'owner_name' => $rutero->full_name,
                'owner_identity_number' => $rutero->identity_number,
                'owner_phone' => $rutero->phone,
                'vehicle_plate' => $rutero->vehicle_plate,
            ]);
        }

        Schema::dropIfExists('ruteros');
    }
};
