<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assets') || ! $this->uniqueIndexExists()) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            $table->dropUnique('assets_code_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('assets') || $this->uniqueIndexExists() || $this->hasDuplicateCodes()) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    private function uniqueIndexExists(): bool
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return (bool) DB::selectOne(
                'select 1 from information_schema.statistics where table_schema = database() and table_name = ? and index_name = ? and non_unique = 0 limit 1',
                ['assets', 'assets_code_unique']
            );
        }

        if ($driver === 'sqlite') {
            foreach (DB::select('pragma index_list(assets)') as $index) {
                if (($index->name ?? null) === 'assets_code_unique' && (int) ($index->unique ?? 0) === 1) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'pgsql') {
            return (bool) DB::selectOne(
                'select 1 from pg_indexes where tablename = ? and indexname = ? limit 1',
                ['assets', 'assets_code_unique']
            );
        }

        return true;
    }

    private function hasDuplicateCodes(): bool
    {
        return DB::table('assets')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('count(*) > 1')
            ->exists();
    }
};
