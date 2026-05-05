<?php

namespace App\Support;

use App\Models\AssetReturn;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\PegawaiDatabaseNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

class PegawaiNotificationService
{
    public function sendLoanStatusNotification(Loan $loan): bool
    {
        $loan->loadMissing(['asset', 'user']);

        if (! $loan->user instanceof User || $loan->user->role !== 'pegawai') {
            return false;
        }

        if (! in_array($loan->status, ['Disetujui', 'Ditolak'], true)) {
            return false;
        }

        $assetLabel = $this->assetLabel($loan->asset?->name, $loan->asset?->code);
        $isApproved = $loan->status === 'Disetujui';

        return $this->notifyIfMissing($loan->user, [
            'dedupe_key' => 'loan-status-'.$loan->id.'-'.$loan->status,
            'type_key' => $isApproved ? 'loan_approved' : 'loan_rejected',
            'title' => $isApproved ? 'Peminjaman disetujui' : 'Peminjaman ditolak',
            'message' => $isApproved
                ? 'Pengajuan peminjaman untuk aset '.$assetLabel.' telah disetujui admin.'
                : 'Pengajuan peminjaman untuk aset '.$assetLabel.' ditolak admin.',
            'action_label' => 'Lihat peminjaman',
            'action_url' => route('pegawai.loans.index', absolute: false),
            'icon' => $isApproved ? 'check-circle' : 'x-circle',
            'variant' => $isApproved ? 'success' : 'danger',
            'reference_type' => 'loan',
            'reference_id' => $loan->id,
            'occurred_at' => now()->toIso8601String(),
            'meta' => [
                'loan_id' => $loan->id,
                'asset_name' => $loan->asset?->name,
                'asset_code' => $loan->asset?->code,
                'loan_date' => optional($loan->loan_date)->format('d/m/Y'),
                'planned_return_date' => optional($loan->planned_return_date)->format('d/m/Y'),
                'status' => $loan->status,
                'status_note' => $loan->status_note,
            ],
        ]);
    }

    public function sendReturnVerifiedNotification(AssetReturn $return): bool
    {
        $return->loadMissing(['asset', 'user', 'loan']);

        if (! $return->user instanceof User || $return->user->role !== 'pegawai') {
            return false;
        }

        if ($return->status !== 'Terverifikasi') {
            return false;
        }

        $assetLabel = $this->assetLabel($return->asset?->name, $return->asset?->code);

        return $this->notifyIfMissing($return->user, [
            'dedupe_key' => 'return-verified-'.$return->id,
            'type_key' => 'return_verified',
            'title' => 'Pengembalian terverifikasi',
            'message' => 'Pengembalian aset '.$assetLabel.' telah diverifikasi admin.',
            'action_label' => 'Lihat pengembalian',
            'action_url' => route('pegawai.returns.index', absolute: false),
            'icon' => 'clipboard-check',
            'variant' => 'success',
            'reference_type' => 'asset_return',
            'reference_id' => $return->id,
            'occurred_at' => now()->toIso8601String(),
            'meta' => [
                'return_id' => $return->id,
                'loan_id' => $return->loan_id,
                'asset_name' => $return->asset?->name,
                'asset_code' => $return->asset?->code,
                'returned_at' => optional($return->returned_at)->format('d/m/Y'),
                'report_number' => $return->report_number,
                'condition' => $return->condition,
                'status' => $return->status,
                'status_note' => $return->status_note,
                'verified_note' => $return->verified_note,
            ],
        ]);
    }

    public function sendLoanDueSoonReminder(Loan $loan, CarbonInterface $today): bool
    {
        $loan->loadMissing(['asset', 'user']);

        if (! $loan->user instanceof User || $loan->user->role !== 'pegawai' || ! $loan->planned_return_date) {
            return false;
        }

        $assetLabel = $this->assetLabel($loan->asset?->name, $loan->asset?->code);

        return $this->notifyIfMissing($loan->user, [
            'dedupe_key' => 'loan-due-soon-'.$loan->id.'-'.$loan->planned_return_date->format('Ymd'),
            'type_key' => 'loan_due_soon',
            'title' => 'Pengembalian jatuh tempo besok',
            'message' => 'Aset '.$assetLabel.' perlu dikembalikan paling lambat '.$loan->planned_return_date->format('d/m/Y').'.',
            'action_label' => 'Ajukan pengembalian',
            'action_url' => route('pegawai.returns.index', absolute: false),
            'icon' => 'alarm',
            'variant' => 'warning',
            'reference_type' => 'loan',
            'reference_id' => $loan->id,
            'occurred_at' => $today->toIso8601String(),
            'meta' => [
                'loan_id' => $loan->id,
                'asset_name' => $loan->asset?->name,
                'asset_code' => $loan->asset?->code,
                'planned_return_date' => $loan->planned_return_date->format('d/m/Y'),
                'status' => $loan->status,
            ],
        ]);
    }

    public function sendLoanDueTodayReminder(Loan $loan, CarbonInterface $today): bool
    {
        $loan->loadMissing(['asset', 'user']);

        if (! $loan->user instanceof User || $loan->user->role !== 'pegawai' || ! $loan->planned_return_date) {
            return false;
        }

        $assetLabel = $this->assetLabel($loan->asset?->name, $loan->asset?->code);

        return $this->notifyIfMissing($loan->user, [
            'dedupe_key' => 'loan-due-today-'.$loan->id.'-'.$today->format('Ymd'),
            'type_key' => 'loan_due_today',
            'title' => 'Pengembalian jatuh tempo hari ini',
            'message' => 'Aset '.$assetLabel.' jatuh tempo pengembalian hari ini. Segera ajukan pengembalian.',
            'action_label' => 'Ajukan pengembalian',
            'action_url' => route('pegawai.returns.index', absolute: false),
            'icon' => 'bell',
            'variant' => 'warning',
            'reference_type' => 'loan',
            'reference_id' => $loan->id,
            'occurred_at' => $today->toIso8601String(),
            'meta' => [
                'loan_id' => $loan->id,
                'asset_name' => $loan->asset?->name,
                'asset_code' => $loan->asset?->code,
                'planned_return_date' => $loan->planned_return_date->format('d/m/Y'),
                'status' => $loan->status,
            ],
        ]);
    }

    public function sendLoanOverdueReminder(Loan $loan, CarbonInterface $today): bool
    {
        $loan->loadMissing(['asset', 'user']);

        if (! $loan->user instanceof User || $loan->user->role !== 'pegawai' || ! $loan->planned_return_date) {
            return false;
        }

        $assetLabel = $this->assetLabel($loan->asset?->name, $loan->asset?->code);
        $overdueDays = $loan->planned_return_date->diffInDays($today);

        return $this->notifyIfMissing($loan->user, [
            'dedupe_key' => 'loan-overdue-'.$loan->id.'-'.$today->format('Ymd'),
            'type_key' => 'loan_overdue',
            'title' => 'Pengembalian melewati jatuh tempo',
            'message' => 'Aset '.$assetLabel.' terlambat dikembalikan '.$overdueDays.' hari. Segera ajukan pengembalian.',
            'action_label' => 'Ajukan pengembalian',
            'action_url' => route('pegawai.returns.index', absolute: false),
            'icon' => 'exclamation-triangle',
            'variant' => 'danger',
            'reference_type' => 'loan',
            'reference_id' => $loan->id,
            'occurred_at' => $today->toIso8601String(),
            'meta' => [
                'loan_id' => $loan->id,
                'asset_name' => $loan->asset?->name,
                'asset_code' => $loan->asset?->code,
                'planned_return_date' => $loan->planned_return_date->format('d/m/Y'),
                'overdue_days' => $overdueDays,
                'status' => $loan->status,
            ],
        ]);
    }

    private function notifyIfMissing(User $user, array $payload): bool
    {
        if ($this->notificationExists($user, $payload['dedupe_key'])) {
            return false;
        }

        $user->notify(new PegawaiDatabaseNotification($payload));

        return true;
    }

    private function notificationExists(User $user, string $dedupeKey): bool
    {
        if (! Schema::hasTable('notifications')) {
            return false;
        }

        return $user->notifications()
            ->where('type', PegawaiDatabaseNotification::class)
            ->where('data->dedupe_key', $dedupeKey)
            ->exists();
    }

    private function assetLabel(?string $assetName, ?string $assetCode): string
    {
        return trim(($assetName ?: 'Aset').' '.($assetCode ? '('.$assetCode.')' : ''));
    }
}
