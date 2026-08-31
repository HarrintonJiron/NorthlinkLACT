<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ruteros', function (Blueprint $table) {
            $table->dropUnique(['route_id']);
        });

        Schema::table('ruteros', function (Blueprint $table) {
            $table->unsignedBigInteger('route_id')->nullable()->change();
        });

        Schema::table('ruteros', function (Blueprint $table) {
            $table->unique('route_id');
        });
    }

    public function down(): void
    {
        Schema::table('ruteros', function (Blueprint $table) {
            $table->dropUnique(['route_id']);
        });

        Schema::table('ruteros', function (Blueprint $table) {
            $table->unsignedBigInteger('route_id')->nullable(false)->change();
        });

        Schema::table('ruteros', function (Blueprint $table) {
            $table->unique('route_id');
        });
    }
};
