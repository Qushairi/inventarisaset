<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\AssetReturn;
use App\Models\Loan;
use App\Support\AdminNotificationService;
use App\Support\AssetStateService;
use App\Support\PegawaiNotificationService;
use App\Support\SuratPeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReturnController extends BasePegawaiController
{
    public function __construct(
        private readonly SuratPeminjamanService $suratPeminjamanService,
        private readonly AdminNotificationService $adminNotificationService,
        private readonly AssetStateService $assetStateService,
        private readonly PegawaiNotificationService $pegawaiNotificationService,
    ) {
    }

    public function index(Request $request)
    {
        $pegawai = $this->currentPegawai();
        $perPage = $this->perPage($request);

        $returns = AssetReturn::query()
            ->with(['asset', 'loan.asset.category', 'loan.user', 'loan.approvedBy', 'loan.suratPeminjaman'])
            ->where('user_id', $pegawai->id)
            ->latest('returned_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (AssetReturn $return) {
                $loan = $return->loan;
                $suratPeminjaman = $loan
                    ? $this->suratPeminjamanService->ensureForLoan($loan)
                    : null;

                $itemList = $loan ? $loan->getItemList() : collect();
                $firstAsset = $itemList->first()['asset'] ?? ($return->asset ?? $loan?->asset);
                $itemCount = $itemList->count();

                $assetName = $itemCount > 1
                    ? $firstAsset?->name . ' (+' . ($itemCount - 1) . ' barang lainnya)'
                    : ($firstAsset?->name ?? 'Aset Inventaris');

                $assetCode = $itemCount > 1
                    ? $itemCount . ' Jenis Barang (' . $itemList->sum('quantity') . ' Total Unit)'
                    : ($firstAsset?->code ?? '-');

                $itemConditionsMap = [];
                if ($return->report_note && str_contains($return->report_note, 'Kondisi per barang:')) {
                    $parts = explode('Kondisi per barang:', $return->report_note);
                    $conditionStr = explode('—', $parts[1] ?? '')[0] ?? '';
                    $itemParts = explode('|', $conditionStr);
                    foreach ($itemParts as $ip) {
                        if (preg_match('/^(.*)\((.*)\)$/u', trim($ip), $matches)) {
                            $name = trim($matches[1]);
                            $cond = trim($matches[2]);
                            $itemConditionsMap[$name] = $cond;
                        }
                    }
                }

                $itemsList = $itemList->map(function ($item) use ($itemConditionsMap, $return) {
                    $name = $item['asset']?->name ?? 'Aset Inventaris';
                    $itemCond = $itemConditionsMap[$name] ?? ($return->condition ?? 'Baik');
                    $variant = match ($itemCond) {
                        'Rusak Ringan' => 'warning',
                        'Rusak Berat' => 'danger',
                        default => 'success',
                    };

                    return [
                        'name' => $name,
                        'code' => $item['asset']?->code ?? '-',
                        'quantity' => $item['quantity'],
                        'condition' => $itemCond,
                        'condition_variant' => $variant,
                    ];
                })->values()->all();

                return [
                    'asset_name' => $assetName,
                    'asset_code' => $assetCode,
                    'items_list' => $itemsList,
                    'quantity' => $itemList->sum('quantity') ?: 1,
                    'loan_date' => optional($loan?->loan_date)->translatedFormat('d F Y'),
                    'returned_at' => optional($return->returned_at)->translatedFormat('d F Y'),
                    'verified_note' => $return->verified_note,
                    'condition' => $return->condition,
                    'condition_variant' => match ($return->condition) {
                        'Rusak Ringan' => 'warning',
                        'Rusak Berat' => 'danger',
                        default => 'success',
                    },
                    'status' => $return->status,
                    'status_variant' => match ($return->status) {
                        'Ditolak' => 'danger',
                        'Terverifikasi' => 'success',
                        default => 'warning',
                    },
                    'status_note' => $return->status_note,
                    'report_number' => $return->report_number,
                    'report_note' => $return->report_note,
                    'letter_number' => $suratPeminjaman?->number,
                    'letter_url' => $suratPeminjaman && $loan
                        ? route('pegawai.loans.letter.show', ['loan' => $loan, 'from' => 'returns'])
                        : null,
                    'letter_download_url' => $suratPeminjaman && $loan
                        ? route('pegawai.loans.letter.download', $loan)
                        : null,
                ];
            });

        return view('pegawai.returns.index', $this->layoutData([
            'conditions' => $this->conditionOptions(),
            'returns' => $returns,
            'returnTotal' => AssetReturn::query()->where('user_id', $pegawai->id)->count(),
            'returnableLoans' => $this->returnableLoansQuery($pegawai->id)->get(),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $pegawai = $this->currentPegawai();

        $validated = $request->validateWithBag('createReturn', [
            'loan_id' => ['required', 'exists:loans,id'],
            'returned_at' => ['required', 'date'],
            'condition' => ['nullable', 'string'],
            'item_conditions' => ['nullable', 'array'],
            'report_note' => ['nullable', 'string', 'max:255'],
        ]);

        $loan = $this->returnableLoansQuery($pegawai->id)
            ->whereKey($validated['loan_id'])
            ->first();

        if (! $loan) {
            throw ValidationException::withMessages([
                'loan_id' => 'Peminjaman yang dipilih belum dapat diajukan untuk pengembalian.',
            ])->errorBag('createReturn');
        }

        if ($loan->loan_date && $loan->loan_date->gt(Carbon::parse($validated['returned_at']))) {
            throw ValidationException::withMessages([
                'returned_at' => 'Tanggal kembali tidak boleh lebih awal dari tanggal peminjaman.',
            ])->errorBag('createReturn');
        }

        $itemConditions = $request->input('item_conditions', []);
        $formattedConditions = [];
        $uniqueConditions = [];

        if (is_array($itemConditions) && count($itemConditions) > 0) {
            $itemList = $loan->getItemList();
            foreach ($itemList as $it) {
                $assetObj = $it['asset'];
                $assetId = $assetObj?->id;
                $cond = $itemConditions[$assetId] ?? 'Baik';
                if (! in_array($cond, $this->conditionOptions(), true)) {
                    $cond = 'Baik';
                }
                $formattedConditions[] = ($assetObj?->name ?? 'Aset').' ('.$cond.')';
                $uniqueConditions[] = $cond;
            }
        }

        $uniqueConditions = array_values(array_unique($uniqueConditions));

        if (count($uniqueConditions) === 1) {
            $finalCondition = $uniqueConditions[0];
        } elseif (count($uniqueConditions) > 1) {
            $finalCondition = 'Kondisi Bervariasi (' . implode(', ', $uniqueConditions) . ')';
        } else {
            $finalCondition = $validated['condition'] ?? 'Baik';
        }

        $conditionSummary = count($formattedConditions) > 0
            ? 'Kondisi per barang: ' . implode(' | ', $formattedConditions)
            : null;

        $userNote = $validated['report_note'] ?? null;
        $fullReportNote = trim(($conditionSummary ? $conditionSummary . ($userNote ? ' — ' : '') : '') . ($userNote ?? ''));

        $return = AssetReturn::query()->create([
            'loan_id' => $loan->id,
            'asset_id' => $loan->asset_id,
            'user_id' => $pegawai->id,
            'returned_at' => $validated['returned_at'],
            'verified_note' => 'Menunggu pengecekan fisik barang oleh admin.',
            'condition' => $finalCondition,
            'status' => 'Menunggu',
            'status_note' => 'Pengajuan pengembalian telah dikirim dan menunggu verifikasi admin.',
            'report_number' => $this->generateReportNumber(),
            'report_note' => $fullReportNote ?: null,
        ]);

        $this->adminNotificationService->sendReturnRequestNotification($return);

        return redirect()
            ->route('pegawai.returns.index')
            ->with('success', 'Pengajuan pengembalian berhasil dikirim. Menunggu pengecekan fisik barang dan verifikasi oleh Admin.');
    }

    private function conditionOptions(): array
    {
        return ['Baik', 'Rusak Ringan', 'Rusak Berat'];
    }

    private function generateReportNumber(): string
    {
        do {
            $reportNumber = 'RET-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (AssetReturn::query()->where('report_number', $reportNumber)->exists());

        return $reportNumber;
    }

    private function returnableLoansQuery(int $pegawaiId)
    {
        return Loan::query()
            ->with('asset')
            ->where('user_id', $pegawaiId)
            ->where('status', 'Disetujui')
            ->whereDoesntHave('returnRecord')
            ->orderByDesc('loan_date');
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);

        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }
}
