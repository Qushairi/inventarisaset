<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Loan;

class AssetStateService
{
    public function resolveState(Asset $asset, bool $preferLatestReturn = false): array
    {
        $resolvedCondition = $this->resolvedCondition($asset, $preferLatestReturn);
        $resolvedStatus = $this->hasActiveLoan($asset->id)
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
        $latestVerifiedReturn = AssetReturn::query()
            ->where('asset_id', $asset->id)
            ->where('status', 'Terverifikasi')
            ->latest('updated_at')
            ->latest('returned_at')
            ->latest('id')
            ->first();

        if (! $latestVerifiedReturn) {
            return $asset->condition;
        }

        if ($preferLatestReturn) {
            return $latestVerifiedReturn->condition;
        }

        if ($asset->updated_at && $latestVerifiedReturn->updated_at && $asset->updated_at->greaterThanOrEqualTo($latestVerifiedReturn->updated_at)) {
            return $asset->condition;
        }

        return $latestVerifiedReturn->condition;
    }

    private function statusWithoutActiveLoan(Asset $asset, string $condition, bool $preferLatestReturn = false): string
    {
        if ($preferLatestReturn) {
            return $condition === 'Rusak Berat' ? 'Perbaikan' : 'Tersedia';
        }

        if ($asset->status !== 'Dipinjam') {
            return $asset->status;
        }

        return $condition === 'Rusak Berat' ? 'Perbaikan' : 'Tersedia';
    }
}
