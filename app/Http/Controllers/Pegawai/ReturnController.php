<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\AssetReturn;

class ReturnController extends BasePegawaiController
{
    public function index()
    {
        $pegawai = $this->currentPegawai();

        $returns = AssetReturn::query()
            ->with(['asset', 'loan'])
            ->where('user_id', $pegawai->id)
            ->latest('returned_at')
            ->paginate(10)
            ->through(function (AssetReturn $return) {
                return [
                    'asset_name' => $return->asset?->name,
                    'asset_code' => $return->asset?->code,
                    'returned_at' => optional($return->returned_at)->format('d/m/Y'),
                    'verified_note' => $return->verified_note,
                    'condition' => $return->condition,
                    'condition_variant' => match ($return->condition) {
                        'Rusak Ringan' => 'warning',
                        'Rusak Berat' => 'danger',
                        default => 'success',
                    },
                    'status' => $return->status,
                    'status_variant' => $return->status === 'Terverifikasi' ? 'success' : 'info',
                    'status_note' => $return->status_note,
                    'report_number' => $return->report_number,
                    'report_note' => $return->report_note,
                ];
            });

        return view('pegawai.returns.index', $this->layoutData([
            'returns' => $returns,
            'returnTotal' => AssetReturn::query()->where('user_id', $pegawai->id)->count(),
        ]));
    }
}
