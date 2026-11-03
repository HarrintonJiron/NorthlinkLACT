<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milk_collections', function (Blueprint $table): void {
            $table->string('visit_status', 30)->default('entregado')->after('liters');
            $table->index(['route_id', 'collection_date', 'visit_status']);
        });
    }

    public function down(): void
    {
        Schema::table('milk_collections', function (Blueprint $table): void {
            $table->dropIndex(['route_id', 'collection_date', 'visit_status']);
            $table->dropColumn('visit_status');
        });
    }
};
