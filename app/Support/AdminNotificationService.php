<?php

namespace App\Support;

use App\Models\AssetReturn;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\AdminDatabaseNotification;
use Illuminate\Support\Facades\Schema;

class AdminNotificationService
{
    public function sendLoanRequestNotification(Loan $loan): int
    {
        $loan->loadMissing(['asset', 'user']);

        if (! $loan->user instanceof User || $loan->user->role !== 'pegawai') {
            return 0;
        }

        if ($loan->status !== 'Menunggu') {
            return 0;
        }

        $assetLabel = $this->assetLabel($loan->asset?->name, $loan->asset?->code);

        // Send email notification to Admin
        User::query()
            ->where('role', 'admin')
            ->orderBy('id')
            ->get()
            ->each(function (User $admin) use ($loan): void {
                if (filled($admin->email)) {
                    try {
                        \Illuminate\Support\Facades\Mail::to($admin->email)->send(new \App\Mail\LoanRequestAdminMail($loan));
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });

        return $this->notifyAdmins([
            'dedupe_key' => 'admin-loan-request-'.$loan->id,
            'type_key' => 'admin_loan_request',
            'title' => 'Pengajuan peminjaman baru',
            'message' => $loan->user->name.' mengajukan peminjaman aset '.$assetLabel.'.',
            'action_label' => 'Tinjau peminjaman',
            'action_url' => route('admin.loans.index', absolute: false),
            'icon' => 'journal-check',
            'variant' => 'warning',
            'reference_type' => 'loan',
            'reference_id' => $loan->id,
            'occurred_at' => now()->toIso8601String(),
            'meta' => [
                'loan_id' => $loan->id,
                'employee_name' => $loan->user->name,
                'employee_email' => $loan->user->email,
                'asset_name' => $loan->asset?->name,
                'asset_code' => $loan->asset?->code,
                'loan_date' => optional($loan->loan_date)->format('d/m/Y'),
                'planned_return_date' => optional($loan->planned_return_date)->format('d/m/Y'),
                'status' => $loan->status,
            ],
        ]);
    }

    public function sendReturnRequestNotification(AssetReturn $return): int
    {
        $return->loadMissing(['asset', 'user', 'loan']);

        if (! $return->user instanceof User || $return->user->role !== 'pegawai') {
            return 0;
        }

        if (! in_array($return->status, ['Menunggu', 'Menunggu Verifikasi', 'Terverifikasi'], true)) {
            return 0;
        }

        $assetLabel = $this->assetLabel($return->asset?->name, $return->asset?->code);
        $isVerified = $return->status === 'Terverifikasi';

        // Send email notification to Admin when a new return is requested
        if (! $isVerified) {
            User::query()
                ->where('role', 'admin')
                ->orderBy('id')
                ->get()
                ->each(function (User $admin) use ($return): void {
                    if (filled($admin->email)) {
                        try {
                            \Illuminate\Support\Facades\Mail::to($admin->email)->send(new \App\Mail\ReturnRequestAdminMail($return));
                        } catch (\Throwable $e) {
                            report($e);
                        }
                    }
                });
        }

        return $this->notifyAdmins([
            'dedupe_key' => 'admin-return-request-'.$return->id,
            'type_key' => 'admin_return_request',
            'title' => 'Pengajuan pengembalian baru',
            'message' => $return->user->name.' mengajukan pengembalian aset '.$assetLabel.'.',
            'action_label' => 'Lihat pengembalian',
            'action_url' => route('admin.returns.index', absolute: false),
            'icon' => 'arrow-counterclockwise',
            'variant' => 'warning',
            'reference_type' => 'asset_return',
            'reference_id' => $return->id,
            'occurred_at' => now()->toIso8601String(),
            'meta' => [
                'return_id' => $return->id,
                'loan_id' => $return->loan_id,
                'employee_name' => $return->user->name,
                'employee_email' => $return->user->email,
                'asset_name' => $return->asset?->name,
                'asset_code' => $return->asset?->code,
                'returned_at' => optional($return->returned_at)->format('d/m/Y'),
                'report_number' => $return->report_number,
                'condition' => $return->condition,
                'status' => $return->status,
            ],
        ]);
    }

    private function notifyAdmins(array $payload): int
    {
        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        $sent = 0;

        User::query()
            ->where('role', 'admin')
            ->orderBy('id')
            ->get()
            ->each(function (User $admin) use ($payload, &$sent): void {
                if ($this->notificationExists($admin, $payload['dedupe_key'])) {
                    return;
                }

                $admin->notify(new AdminDatabaseNotification($payload));
                $sent++;
            });

        return $sent;
    }

    private function notificationExists(User $user, string $dedupeKey): bool
    {
        return $user->notifications()
            ->where('type', AdminDatabaseNotification::class)
            ->where('data->dedupe_key', $dedupeKey)
            ->exists();
    }

    private function assetLabel(?string $assetName, ?string $assetCode): string
    {
        return trim(($assetName ?: 'Aset').' '.($assetCode ? '('.$assetCode.')' : ''));
    }
}
