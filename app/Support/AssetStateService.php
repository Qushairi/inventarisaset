<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Loan;

class AssetStateService
{
    public function resolveState(Asset $asset): array
    {
        $latestVerifiedReturn = AssetReturn::query()
            ->where('asset_id', $asset->id)
            ->where('status', 'Terverifikasi')
            ->latest('returned_at')
            ->latest('id')
            ->first();

        $resolvedCondition = $latestVerifiedReturn?->condition ?? $asset->condition;
        $resolvedStatus = $this->hasActiveLoan($asset->id)
            ? 'Dipinjam'
            : $this->statusFromCondition($resolvedCondition);

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

    public function syncAssetById(?int $assetId): void
    {
        if (! $assetId) {
            return;
        }

        $asset = Asset::query()->find($assetId);

        if (! $asset) {
            return;
        }

        $resolvedState = $this->resolveState($asset);
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

    public function syncAssetIds(array $assetIds): void
    {
        foreach (array_unique(array_filter($assetIds)) as $assetId) {
            $this->syncAssetById((int) $assetId);
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

    private function statusFromCondition(string $condition): string
    {
        return $condition === 'Rusak Berat'
            ? 'Perbaikan'
            : 'Tersedia';
    }
}
