<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\Asset;
use App\Models\Loan;
use App\Support\AdminNotificationService;
use App\Support\AssetStateService;
use App\Support\SuratPeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoanController extends BasePegawaiController
{
    public function __construct(
        private readonly SuratPeminjamanService $suratPeminjamanService,
        private readonly AdminNotificationService $adminNotificationService,
        private readonly AssetStateService $assetStateService,
    ) {
    }

    public function index(Request $request)
    {
        $pegawai = $this->currentPegawai();
        $perPage = $this->perPage($request);

        $loans = Loan::query()
            ->with('asset')
            ->where('user_id', $pegawai->id)
            ->whereDoesntHave('returnRecord')
            ->latest('loan_date')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Loan $loan) {
                $suratPeminjaman = $this->suratPeminjamanService->ensureForLoan($loan);

                $itemList = $loan->getItemList();
                $firstAsset = $itemList->first()['asset'] ?? $loan->asset;
                $itemCount = $itemList->count();

                $assetName = $itemCount > 1
                    ? $firstAsset?->name . ' (+' . ($itemCount - 1) . ' barang lainnya)'
                    : $firstAsset?->name;

                $assetCode = $itemCount > 1
                    ? $itemCount . ' Jenis Barang (' . $itemList->sum('quantity') . ' Total Unit)'
                    : $firstAsset?->code;

                return [
                    'id' => $loan->id,
                    'asset_name' => $assetName,
                    'asset_code' => $assetCode,
                    'items_list' => $itemList->map(function ($item) {
                        return [
                            'name' => $item['asset']?->name ?? 'Aset Tidak Ditemukan',
                            'code' => $item['asset']?->code ?? '-',
                            'quantity' => $item['quantity'],
                        ];
                    })->values()->all(),
                    'loan_date' => optional($loan->loan_date)->translatedFormat('d F Y'),
                    'return_plan' => 'Rencana kembali ' . optional($loan->planned_return_date)->translatedFormat('d F Y'),
                    'quantity' => $itemList->sum('quantity'),
                    'status' => $loan->status,
                    'status_variant' => match ($loan->status) {
                        'Ditolak' => 'danger',
                        'Menunggu' => 'warning',
                        default => 'success',
                    },
                    'status_note' => $loan->status_note,
                    'letter_number' => $suratPeminjaman?->number,
                    'letter_url' => $suratPeminjaman
                        ? route('pegawai.loans.letter.show', $loan)
                        : null,
                    'letter_download_url' => $suratPeminjaman
                        ? route('pegawai.loans.letter.download', $loan)
                        : null,
                ];
            });

        return view('pegawai.loans.index', $this->layoutData([
            'availableAssets' => $this->availableAssetsQuery()->get(),
            'loans' => $loans,
            'loanTotal' => Loan::query()
                ->where('user_id', $pegawai->id)
                ->whereDoesntHave('returnRecord')
                ->count(),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $pegawai = $this->currentPegawai();

        $validated = $request->validateWithBag('createLoan', [
            'loan_date' => ['required', 'date'],
            'planned_return_date' => ['required', 'date', 'after_or_equal:loan_date'],
            'status_note' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.asset_id' => ['required_with:items', 'exists:assets,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'asset_id' => ['nullable', 'exists:assets,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ], [
            'loan_date.required' => 'Tanggal pinjam wajib diisi.',
            'loan_date.date' => 'Format tanggal pinjam tidak valid.',
            'planned_return_date.required' => 'Rencana tanggal kembali wajib diisi.',
            'planned_return_date.date' => 'Format rencana tanggal kembali tidak valid.',
            'planned_return_date.after_or_equal' => 'Rencana tanggal kembali tidak boleh sebelum tanggal pinjam.',
            'items.required' => 'Pilih minimal satu barang aset yang akan dipinjam.',
            'items.min' => 'Pilih minimal satu barang aset yang akan dipinjam.',
        ]);

        $itemList = collect();
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $itemList->push([
                    'asset_id' => (int) $item['asset_id'],
                    'quantity' => (int) $item['quantity'],
                ]);
            }
        } elseif (!empty($validated['asset_id'])) {
            $itemList->push([
                'asset_id' => (int) $validated['asset_id'],
                'quantity' => (int) ($validated['quantity'] ?? 1),
            ]);
        } else {
            throw ValidationException::withMessages([
                'items' => 'Pilih minimal satu barang yang akan dipinjam.',
            ])->errorBag('createLoan');
        }

        // Strict Real Stock Validation & Duplicate Loan Check
        foreach ($itemList as $itemData) {
            $asset = \App\Models\Asset::find($itemData['asset_id']);
            if (! $asset || $asset->status !== 'Tersedia' || $asset->quantity < 1) {
                throw ValidationException::withMessages([
                    'items' => "Aset '".($asset?->name ?? 'Barang')."' sedang tidak tersedia untuk dipinjam.",
                ])->errorBag('createLoan');
            }

            if ($itemData['quantity'] > $asset->quantity) {
                throw ValidationException::withMessages([
                    'items' => "Jumlah peminjaman untuk '{$asset->name}' ({$itemData['quantity']} unit) melebihi stok yang tersedia (Stok riil: {$asset->quantity} unit).",
                ])->errorBag('createLoan');
            }

            $existingLoan = Loan::where('user_id', $pegawai->id)
                ->where('asset_id', $itemData['asset_id'])
                ->whereDate('loan_date', $validated['loan_date'])
                ->exists();

            if ($existingLoan) {
                $formattedDate = date('d-m-Y', strtotime($validated['loan_date']));
                throw ValidationException::withMessages([
                    'loan_date' => "Anda sudah memiliki pengajuan peminjaman untuk barang '{$asset->name}' pada tanggal {$formattedDate}. Silakan pilih tanggal lain atau cek riwayat peminjaman Anda.",
                ])->errorBag('createLoan');
            }
        }

        $firstItem = $itemList->first();
        $totalQty = $itemList->sum('quantity');

        try {
            $loan = Loan::query()->create([
                'asset_id' => $firstItem['asset_id'],
                'user_id' => $pegawai->id,
                'loan_date' => $validated['loan_date'],
                'planned_return_date' => $validated['planned_return_date'],
                'quantity' => $totalQty,
                'status' => 'Menunggu',
                'status_note' => $validated['status_note'] ?: 'Pengajuan peminjaman dari pegawai.',
            ]);

            foreach ($itemList as $itemData) {
                $loan->items()->create([
                    'asset_id' => $itemData['asset_id'],
                    'quantity' => $itemData['quantity'],
                ]);
            }
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'loan_date' => 'Pengajuan peminjaman untuk barang dan tanggal yang sama sudah pernah dibuat sebelumnya.',
            ])->errorBag('createLoan');
        }

        $this->adminNotificationService->sendLoanRequestNotification($loan);

        return redirect()
            ->route('pegawai.loans.index')
            ->with('success', 'Pengajuan peminjaman multi-item berhasil dikirim dan menunggu persetujuan admin.');
    }

    private function availableAssetsQuery()
    {
        return Asset::query()
            ->with(['category', 'location'])
            ->where('status', 'Tersedia')
            ->where('quantity', '>', 0)
            ->whereDoesntHave('loans', function ($query) {
                $query->where('status', 'Disetujui')
                    ->whereColumn('loans.quantity', '>=', 'assets.quantity')
                    ->whereDoesntHave('returnRecord', function ($query) {
                        $query->where('status', 'Terverifikasi');
                    });
            })
            ->orderBy('name');
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);

        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }
}
