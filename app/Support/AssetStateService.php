<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssetStateService
{
    public function resolveState(Asset $asset, bool $preferLatestReturn = false): array
    {
        $resolvedCondition = $this->resolvedCondition($asset, $preferLatestReturn);
        $resolvedStatus = $asset->quantity <= 0 && $this->hasActiveLoan($asset->id)
            ? 'Dipinjam'
            : $this->statusWithoutActiveLoan($asset, $resolvedCondition, $preferLatestReturn);

        return [
            'condition' => $resolvedCondition,
            'status' => $resolvedStatus,
        ];
    }

    public function syncLoanById(?int $loanId): void
    {
        if (! $loanId) {
            return;
        }

        $loan = Loan::query()->find($loanId);

        if (! $loan) {
            return;
        }

        if (in_array($loan->status, ['Disetujui', 'Selesai'], true)) {
            $hasVerifiedReturn = AssetReturn::query()
                ->where('loan_id', $loan->id)
                ->where('status', 'Terverifikasi')
                ->exists();

            $resolvedStatus = $hasVerifiedReturn ? 'Selesai' : 'Disetujui';

            if ($loan->status !== $resolvedStatus) {
                $loan->forceFill([
                    'status' => $resolvedStatus,
                ])->saveQuietly();
            }
        }

        $this->syncAssetById($loan->asset_id);
    }

    public function applyLoanStock(Loan $loan): void
    {
        if (! in_array($loan->status, ['Disetujui', 'Selesai'], true) || $loan->stock_applied_at) {
            return;
        }

        DB::transaction(function () use ($loan) {
            $loan = Loan::query()->lockForUpdate()->find($loan->id);

            if (! $loan || $loan->stock_applied_at || ! in_array($loan->status, ['Disetujui', 'Selesai'], true)) {
                return;
            }

            $asset = Asset::query()->lockForUpdate()->find($loan->asset_id);
            $quantity = max(1, (int) $loan->quantity);

            if (! $asset || $asset->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah peminjaman melebihi stok aset yang tersedia.',
                ]);
            }

            $asset->forceFill([
                'quantity' => $asset->quantity - $quantity,
                'status' => ($asset->quantity - $quantity) > 0 ? 'Tersedia' : 'Dipinjam',
            ])->saveQuietly();

            $loan->forceFill([
                'stock_applied_at' => now(),
            ])->saveQuietly();
        });
    }

    public function reverseLoanStock(Loan $loan): void
    {
        if (! $loan->stock_applied_at || $loan->returnRecord()->whereNotNull('stock_applied_at')->exists()) {
            return;
        }

        DB::transaction(function () use ($loan) {
            $loan = Loan::query()->lockForUpdate()->find($loan->id);

            if (! $loan || ! $loan->stock_applied_at || $loan->returnRecord()->whereNotNull('stock_applied_at')->exists()) {
                return;
            }

            $asset = Asset::query()->lockForUpdate()->find($loan->asset_id);

            if ($asset) {
                $asset->forceFill([
                    'quantity' => $asset->quantity + max(1, (int) $loan->quantity),
                    'status' => 'Tersedia',
                ])->saveQuietly();
            }

            $loan->forceFill([
                'stock_applied_at' => null,
            ])->saveQuietly();
        });
    }

    public function applyReturnStock(AssetReturn $return): void
    {
        if ($return->status !== 'Terverifikasi' || $return->stock_applied_at) {
            return;
        }

        $return->loadMissing('loan');

        if ($return->loan && ! $return->loan->stock_applied_at && in_array($return->loan->status, ['Disetujui', 'Selesai'], true)) {
            $this->applyLoanStock($return->loan);
        }

        DB::transaction(function () use ($return) {
            $return = AssetReturn::query()->with('loan')->lockForUpdate()->find($return->id);

            if (! $return || $return->stock_applied_at || $return->status !== 'Terverifikasi') {
                return;
            }

            $loan = $return->loan;
            $sourceAsset = Asset::query()->lockForUpdate()->find($loan?->asset_id ?: $return->asset_id);

            if (! $sourceAsset) {
                return;
            }

            $quantity = max(1, (int) ($loan?->quantity ?: 1));
            $targetAsset = $this->stockTargetForCondition($sourceAsset, $return->condition);
            $targetAsset->forceFill([
                'quantity' => $targetAsset->quantity + $quantity,
                'status' => $return->condition === 'Rusak Berat' ? 'Perbaikan' : 'Tersedia',
            ])->saveQuietly();

            $return->forceFill([
                'asset_id' => $sourceAsset->id,
                'stock_asset_id' => $targetAsset->id,
                'stock_applied_at' => now(),
            ])->saveQuietly();

            if ($loan && $loan->status !== 'Selesai') {
                $loan->forceFill(['status' => 'Selesai'])->saveQuietly();
            }
        });
    }

    public function reverseReturnStock(AssetReturn $return): void
    {
        if (! $return->stock_applied_at || ! $return->stock_asset_id) {
            return;
        }

        DB::transaction(function () use ($return) {
            $return = AssetReturn::query()->with('loan')->lockForUpdate()->find($return->id);

            if (! $return || ! $return->stock_applied_at || ! $return->stock_asset_id) {
                return;
            }

            $quantity = max(1, (int) ($return->loan?->quantity ?: 1));
            $stockAsset = Asset::query()->lockForUpdate()->find($return->stock_asset_id);

            if ($stockAsset) {
                $stockAsset->forceFill([
                    'quantity' => max(0, $stockAsset->quantity - $quantity),
                    'status' => max(0, $stockAsset->quantity - $quantity) > 0
                        ? ($stockAsset->condition === 'Rusak Berat' ? 'Perbaikan' : 'Tersedia')
                        : 'Tersedia',
                ])->saveQuietly();
            }

            if ($return->loan) {
                $return->loan->forceFill(['status' => 'Disetujui'])->saveQuietly();
            }

            $return->forceFill([
                'stock_asset_id' => null,
                'stock_applied_at' => null,
            ])->saveQuietly();
        });
    }

    public function mergeAssetAfterManualUpdate(Asset $asset, ?string $previousCondition = null): void
    {
        if (! $previousCondition || $previousCondition === $asset->condition || $asset->quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($asset) {
            $asset = Asset::query()->lockForUpdate()->find($asset->id);

            if (! $asset || $asset->quantity <= 0) {
                return;
            }

            $target = $this->matchingAssetQuery($asset, $asset->condition)
                ->whereKeyNot($asset->id)
                ->lockForUpdate()
                ->first();

            if (! $target) {
                return;
            }

            $target->forceFill([
                'quantity' => $target->quantity + $asset->quantity,
                'status' => $asset->condition === 'Rusak Berat' ? 'Perbaikan' : 'Tersedia',
            ])->saveQuietly();

            if (! $asset->loans()->exists() && ! $asset->returns()->exists() && ! $asset->beritaAcaras()->exists()) {
                $asset->delete();

                return;
            }

            $asset->forceFill([
                'quantity' => 0,
                'status' => 'Tersedia',
            ])->saveQuietly();
        });
    }

    public function syncAssetById(?int $assetId, bool $preferLatestReturn = false): void
    {
        if (! $assetId) {
            return;
        }

        $asset = Asset::query()->find($assetId);

        if (! $asset) {
            return;
        }

        $resolvedState = $this->resolveState($asset, $preferLatestReturn);
        $resolvedCondition = $resolvedState['condition'];
        $resolvedStatus = $resolvedState['status'];

        if ($asset->condition === $resolvedCondition && $asset->status === $resolvedStatus) {
            return;
        }

        $asset->forceFill([
            'condition' => $resolvedCondition,
            'status' => $resolvedStatus,
        ])->saveQuietly();
    }

    public function syncAssetIds(array $assetIds, bool $preferLatestReturn = false): void
    {
        foreach (array_unique(array_filter($assetIds)) as $assetId) {
            $this->syncAssetById((int) $assetId, $preferLatestReturn);
        }
    }

    private function hasActiveLoan(int $assetId): bool
    {
        return Loan::query()
            ->where('asset_id', $assetId)
            ->where('status', 'Disetujui')
            ->whereDoesntHave('returnRecord', function ($query) {
                $query->where('status', 'Terverifikasi');
            })
            ->exists();
    }

    private function resolvedCondition(Asset $asset, bool $preferLatestReturn = false): string
    {
        return $asset->condition;
    }

    private function statusWithoutActiveLoan(Asset $asset, string $condition, bool $preferLatestReturn = false): string
    {
        if ($asset->quantity <= 0) {
            return 'Dipinjam';
        }

        if ($preferLatestReturn) {
            return $condition === 'Rusak Berat' ? 'Perbaikan' : 'Tersedia';
        }

        if ($asset->status !== 'Dipinjam') {
            return $asset->status;
        }

        return $condition === 'Rusak Berat' ? 'Perbaikan' : 'Tersedia';
    }

    private function stockTargetForCondition(Asset $sourceAsset, string $condition): Asset
    {
        if ($sourceAsset->condition === $condition) {
            return $sourceAsset;
        }

        $target = $this->matchingAssetQuery($sourceAsset, $condition)->lockForUpdate()->first();

        if ($target) {
            return $target;
        }

        return Asset::query()->create([
            'category_id' => $sourceAsset->category_id,
            'location_id' => $sourceAsset->location_id,
            'name' => $sourceAsset->name,
            'code' => $this->uniqueSplitCode($sourceAsset->code, $condition),
            'note' => $sourceAsset->note,
            'image_path' => $sourceAsset->image_path,
            'serial_number' => $sourceAsset->serial_number,
            'size' => $sourceAsset->size,
            'material' => $sourceAsset->material,
            'condition' => $condition,
            'status' => $condition === 'Rusak Berat' ? 'Perbaikan' : 'Tersedia',
            'quantity' => 0,
            'acquisition_price' => $sourceAsset->acquisition_price,
            'acquisition_year' => $sourceAsset->acquisition_year,
            'acquired_at' => $sourceAsset->acquired_at,
        ]);
    }

    private function matchingAssetQuery(Asset $asset, string $condition)
    {
        return Asset::query()
            ->where('category_id', $asset->category_id)
            ->where('location_id', $asset->location_id)
            ->where('name', $asset->name)
            ->where('note', $asset->note)
            ->where('image_path', $asset->image_path)
            ->where('serial_number', $asset->serial_number)
            ->where('size', $asset->size)
            ->where('material', $asset->material)
            ->where('acquisition_price', $asset->acquisition_price)
            ->where('acquisition_year', $asset->acquisition_year)
            ->when(
                $asset->acquired_at,
                fn ($query) => $query->whereDate('acquired_at', $asset->acquired_at),
                fn ($query) => $query->whereNull('acquired_at')
            )
            ->where('condition', $condition);
    }

    private function uniqueSplitCode(string $baseCode, string $condition): string
    {
        $suffix = match ($condition) {
            'Rusak Ringan' => 'RR',
            'Rusak Berat' => 'RB',
            default => 'B',
        };

        $base = Str::limit($baseCode, 45, '');
        $index = 1;

        do {
            $code = "{$base}-{$suffix}{$index}";
            $index++;
        } while (Asset::query()->where('code', $code)->exists());

        return $code;
    }
}
