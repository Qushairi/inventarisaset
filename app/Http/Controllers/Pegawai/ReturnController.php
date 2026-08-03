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

                return [
                    'asset_name' => $assetName,
                    'asset_code' => $assetCode,
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
                    'status_variant' => $return->status === 'Terverifikasi' ? 'success' : 'info',
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
            'condition' => ['required', Rule::in($this->conditionOptions())],
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

        $return = AssetReturn::query()->create([
            'loan_id' => $loan->id,
            'asset_id' => $loan->asset_id,
            'user_id' => $pegawai->id,
            'returned_at' => $validated['returned_at'],
            'verified_note' => 'Terverifikasi otomatis oleh sistem.',
            'condition' => $validated['condition'],
            'status' => 'Terverifikasi',
            'status_note' => 'Pengembalian otomatis terverifikasi.',
            'report_number' => $this->generateReportNumber(),
            'report_note' => $validated['report_note'] ?: null,
        ]);

        $this->assetStateService->applyReturnStock($return);
        $this->assetStateService->syncLoanById($return->loan_id);
        $return->refresh();
        $this->assetStateService->syncAssetIds([$return->asset_id, $return->stock_asset_id], true);
        $this->adminNotificationService->sendReturnRequestNotification($return);
        $this->pegawaiNotificationService->sendReturnVerifiedNotification($return);

        return redirect()
            ->route('pegawai.returns.index')
            ->with('success', 'Pengembalian berhasil dikirim dan otomatis terverifikasi.');
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
