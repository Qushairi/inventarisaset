<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (! Schema::hasColumn('loans', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('planned_return_date');
            }

            if (! Schema::hasColumn('loans', 'stock_applied_at')) {
                $table->timestamp('stock_applied_at')->nullable()->after('loan_letter_generated_at');
            }
        });

        Schema::table('asset_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_returns', 'stock_asset_id')) {
                $table->foreignId('stock_asset_id')
                    ->nullable()
                    ->after('asset_id')
                    ->constrained('assets')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('asset_returns', 'stock_applied_at')) {
                $table->timestamp('stock_applied_at')->nullable()->after('status_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_returns', function (Blueprint $table) {
            if (Schema::hasColumn('asset_returns', 'stock_asset_id')) {
                $table->dropConstrainedForeignId('stock_asset_id');
            }

            if (Schema::hasColumn('asset_returns', 'stock_applied_at')) {
                $table->dropColumn('stock_applied_at');
            }
        });

        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'stock_applied_at')) {
                $table->dropColumn('stock_applied_at');
            }

            if (Schema::hasColumn('loans', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};
