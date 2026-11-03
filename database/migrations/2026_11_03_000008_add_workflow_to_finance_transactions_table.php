<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('active');
            $table->text('void_reason')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->after('void_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('paid_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->after('paid_by');
            $table->foreignId('reconciled_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable()->after('reconciled_by');
            $table->index(['status', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('paid_by');
            $table->dropConstrainedForeignId('reconciled_by');
            $table->dropIndex(['status', 'transaction_date']);
            $table->dropColumn(['status', 'void_reason', 'approved_at', 'paid_at', 'reconciled_at']);
        });
    }
};
