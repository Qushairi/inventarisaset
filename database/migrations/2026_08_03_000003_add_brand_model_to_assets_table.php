<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('brand_model')->nullable()->after('code');
        });

        DB::table('assets')
            ->where('note', 'like', '%Sumber:%baris%')
            ->orderBy('id')
            ->chunkById(200, function ($assets): void {
                foreach ($assets as $asset) {
                    $note = (string) $asset->note;
                    $brandModel = null;
                    $remark = null;

                    if (preg_match('/(?:^|;\s*)Merk\/Model:\s*(.*?)(?=;\s*(?:Bagian|Kelompok|Satuan|Keterangan|Sumber):|;\s*Nilai paket|$)/iu', $note, $matches) === 1) {
                        $brandModel = trim($matches[1]);
                    }

                    if (preg_match('/(?:^|;\s*)Keterangan:\s*(.*?)(?=;\s*Sumber:|$)/iu', $note, $matches) === 1) {
                        $remark = trim($matches[1]);
                    }

                    DB::table('assets')->where('id', $asset->id)->update([
                        'brand_model' => $brandModel !== '' ? $brandModel : null,
                        'note' => $remark !== '' ? $remark : null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('brand_model');
        });
    }
};
