<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assets') || ! Schema::hasColumn('assets', 'quantity')) {
            return;
        }

        $identityColumns = array_values(array_filter([
            'category_id',
            'location_id',
            'name',
            'code',
            'note',
            'image_path',
            'serial_number',
            'size',
            'material',
            'condition',
            'condition_summary',
            'status',
            'acquisition_price',
            'acquisition_year',
            'acquired_at',
        ], fn (string $column): bool => Schema::hasColumn('assets', $column)));

        DB::transaction(function () use ($identityColumns): void {
            $groups = DB::table('assets')
                ->orderBy('id')
                ->get()
                ->groupBy(function (object $asset) use ($identityColumns): string {
                    $identity = [];

                    foreach ($identityColumns as $column) {
                        $identity[$column] = $asset->{$column};
                    }

                    return hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR));
                });

            foreach ($groups as $assets) {
                if ($assets->count() < 2) {
                    continue;
                }

                $canonical = $assets->shift();
                $quantity = (int) $canonical->quantity;

                foreach ($assets as $duplicate) {
                    if ($this->hasConflictingLoan((int) $canonical->id, (int) $duplicate->id)) {
                        continue;
                    }

                    $this->moveAssetReferences((int) $duplicate->id, (int) $canonical->id);
                    $quantity += (int) $duplicate->quantity;
                    DB::table('assets')->where('id', $duplicate->id)->delete();
                }

                DB::table('assets')
                    ->where('id', $canonical->id)
                    ->update([
                        'quantity' => $quantity,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        // Penggabungan stok tidak dapat dipisahkan kembali tanpa menebak jumlah setiap baris lama.
    }

    private function hasConflictingLoan(int $canonicalId, int $duplicateId): bool
    {
        if (! Schema::hasTable('loans')) {
            return false;
        }

        return DB::table('loans as duplicate_loans')
            ->join('loans as canonical_loans', function (JoinClause $join) use ($canonicalId): void {
                $join->on('canonical_loans.user_id', '=', 'duplicate_loans.user_id')
                    ->on('canonical_loans.loan_date', '=', 'duplicate_loans.loan_date')
                    ->where('canonical_loans.asset_id', $canonicalId);
            })
            ->where('duplicate_loans.asset_id', $duplicateId)
            ->exists();
    }

    private function moveAssetReferences(int $fromAssetId, int $toAssetId): void
    {
        foreach ([
            ['table' => 'loans', 'column' => 'asset_id'],
            ['table' => 'asset_returns', 'column' => 'asset_id'],
            ['table' => 'asset_returns', 'column' => 'stock_asset_id'],
            ['table' => 'berita_acaras', 'column' => 'asset_id'],
        ] as $reference) {
            if (! Schema::hasTable($reference['table']) || ! Schema::hasColumn($reference['table'], $reference['column'])) {
                continue;
            }

            DB::table($reference['table'])
                ->where($reference['column'], $fromAssetId)
                ->update([$reference['column'] => $toAssetId]);
        }
    }
};
