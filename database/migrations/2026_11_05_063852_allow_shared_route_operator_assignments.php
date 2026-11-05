<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('route_operator_assignments', function (Blueprint $table) {
            $table->dropUnique(['route_id', 'assigned_date']);
            $table->dropUnique(['user_id', 'assigned_date']);
            $table->string('vehicle_plate', 20)->nullable()->after('assigned_by');
            $table->boolean('is_shared_test')->default(false)->after('vehicle_plate');
            $table->unique(['route_id', 'user_id', 'assigned_date'], 'route_operator_assignment_unique');
            $table->index(['route_id', 'assigned_date']);
            $table->index(['user_id', 'assigned_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('route_operator_assignments')->where('is_shared_test', true)->delete();

        Schema::table('route_operator_assignments', function (Blueprint $table) {
            $table->dropUnique('route_operator_assignment_unique');
            $table->dropIndex(['route_id', 'assigned_date']);
            $table->dropIndex(['user_id', 'assigned_date']);
            $table->dropColumn(['vehicle_plate', 'is_shared_test']);
            $table->unique(['route_id', 'assigned_date']);
            $table->unique(['user_id', 'assigned_date']);
        });
    }
};
