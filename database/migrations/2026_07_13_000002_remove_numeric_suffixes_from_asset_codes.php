<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assets')
            ->select(['id', 'code'])
            ->orderBy('id')
            ->get()
            ->each(function (object $asset): void {
                $code = (string) $asset->code;
                $normalizedCode = preg_replace('/-\d+$/', '', $code);

                if ($normalizedCode === null || $normalizedCode === $code) {
                    return;
                }

                DB::table('assets')
                    ->where('id', $asset->id)
                    ->update(['code' => $normalizedCode]);
            });
    }

    public function down(): void
    {
        // Numeric suffixes were generated only as duplicate markers, so they cannot be restored safely.
    }
};
