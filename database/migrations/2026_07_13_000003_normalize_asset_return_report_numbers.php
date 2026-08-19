<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asset_returns')) {
            return;
        }

        $returns = DB::table('asset_returns')
            ->select(['id', 'report_number'])
            ->orderBy('id')
            ->get();

        foreach ($returns as $return) {
            $oldReportNumber = (string) $return->report_number;

            if (! preg_match('/^(?:RET|BA)-(\d{14})(?:-[A-Z0-9]+)?$/i', $oldReportNumber, $matches)) {
                continue;
            }

            try {
                $reportDate = Carbon::createFromFormat('YmdHis', $matches[1]);
            } catch (Throwable) {
                continue;
            }

            if (! $reportDate) {
                continue;
            }

            do {
                $reportNumber = 'BA-'.$reportDate->format('YmdHis');
                $reportDate->addSecond();
            } while (DB::table('asset_returns')
                ->where('id', '!=', $return->id)
                ->where('report_number', $reportNumber)
                ->exists());

            DB::table('asset_returns')
                ->where('id', $return->id)
                ->update(['report_number' => $reportNumber]);

            $this->updateNotificationCopies((int) $return->id, $oldReportNumber, $reportNumber);
        }
    }

    public function down(): void
    {
        // Suffix acak lama tidak dapat dipulihkan setelah dinormalisasi.
    }

    private function updateNotificationCopies(int $returnId, string $oldReportNumber, string $reportNumber): void
    {
        if ($oldReportNumber === $reportNumber || ! Schema::hasTable('notifications')) {
            return;
        }

        $notifications = DB::table('notifications')
            ->where('data', 'like', '%'.$oldReportNumber.'%')
            ->get(['id', 'data']);

        foreach ($notifications as $notification) {
            $data = json_decode((string) $notification->data, true);

            if (! is_array($data)
                || (int) data_get($data, 'meta.return_id') !== $returnId
                || data_get($data, 'meta.report_number') !== $oldReportNumber) {
                continue;
            }

            data_set($data, 'meta.report_number', $reportNumber);

            DB::table('notifications')
                ->where('id', $notification->id)
                ->update([
                    'data' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]);
        }
    }
};
