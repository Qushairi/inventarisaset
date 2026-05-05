<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Support\PegawaiNotificationService;
use Illuminate\Console\Command;

class SendPegawaiReminderNotifications extends Command
{
    protected $signature = 'notifications:pegawai-reminders';

    protected $description = 'Kirim notifikasi pengingat pengembalian untuk pegawai.';

    public function handle(PegawaiNotificationService $notificationService): int
    {
        $today = today();
        $tomorrow = $today->copy()->addDay();

        $loans = Loan::query()
            ->with(['asset', 'user'])
            ->where('status', 'Disetujui')
            ->whereNotNull('planned_return_date')
            ->whereDoesntHave('returnRecord')
            ->whereDate('planned_return_date', '<=', $tomorrow)
            ->get();

        $dueSoonCount = 0;
        $dueTodayCount = 0;
        $overdueCount = 0;

        foreach ($loans as $loan) {
            if ($loan->planned_return_date?->isSameDay($tomorrow)) {
                $dueSoonCount += (int) $notificationService->sendLoanDueSoonReminder($loan, $today);
                continue;
            }

            if ($loan->planned_return_date?->isSameDay($today)) {
                $dueTodayCount += (int) $notificationService->sendLoanDueTodayReminder($loan, $today);
                continue;
            }

            if ($loan->planned_return_date?->lt($today)) {
                $overdueCount += (int) $notificationService->sendLoanOverdueReminder($loan, $today);
            }
        }

        $this->components->info(
            'Notifikasi pegawai dikirim. '
            .'Jatuh tempo besok: '.$dueSoonCount.', '
            .'jatuh tempo hari ini: '.$dueTodayCount.', '
            .'terlambat: '.$overdueCount.'.'
        );

        return self::SUCCESS;
    }
}
