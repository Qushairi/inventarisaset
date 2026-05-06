<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\Asset;
use App\Models\Loan;
use App\Support\BeritaAcaraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoanController extends BasePegawaiController
{
    public function __construct(
        private readonly BeritaAcaraService $beritaAcaraService,
    ) {
    }

    public function index()
    {
        $pegawai = $this->currentPegawai();

        $loans = Loan::query()
            ->with('asset')
            ->where('user_id', $pegawai->id)
            ->latest('loan_date')
            ->paginate(10)
            ->through(function (Loan $loan) {
                $beritaAcara = $this->beritaAcaraService->ensureForLoan($loan);

                return [
                    'id' => $loan->id,
                    'asset_name' => $loan->asset?->name,
                    'asset_code' => $loan->asset?->code,
                    'loan_date' => optional($loan->loan_date)->format('d/m/Y'),
                    'return_plan' => 'Rencana kembali ' . optional($loan->planned_return_date)->format('d/m/Y'),
                    'status' => $loan->status,
                    'status_variant' => match ($loan->status) {
                        'Ditolak' => 'danger',
                        'Menunggu' => 'warning',
                        default => 'success',
                    },
                    'status_note' => $loan->status_note,
                    'letter_number' => $beritaAcara?->number,
                    'letter_url' => $beritaAcara
                        ? route('pegawai.loans.letter.show', $loan)
                        : null,
                    'letter_download_url' => $beritaAcara
                        ? route('pegawai.loans.letter.download', $loan)
                        : null,
                ];
            });

        return view('pegawai.loans.index', $this->layoutData([
            'availableAssets' => $this->availableAssetsQuery()->get(),
            'loans' => $loans,
            'loanTotal' => Loan::query()->where('user_id', $pegawai->id)->count(),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $pegawai = $this->currentPegawai();

        $validated = $request->validateWithBag('createLoan', [
            'asset_id' => ['required', 'exists:assets,id'],
            'loan_date' => [
                'required',
                'date',
                Rule::unique('loans')->where(fn ($query) => $query
                    ->where('asset_id', $request->input('asset_id'))
                    ->where('user_id', $pegawai->id)
                    ->where('loan_date', $request->input('loan_date'))),
            ],
            'planned_return_date' => ['required', 'date', 'after_or_equal:loan_date'],
            'status_note' => ['nullable', 'string', 'max:255'],
        ]);

        $asset = $this->availableAssetsQuery()
            ->whereKey($validated['asset_id'])
            ->first();

        if (! $asset) {
            throw ValidationException::withMessages([
                'asset_id' => 'Aset yang dipilih sedang tidak tersedia untuk dipinjam.',
            ])->errorBag('createLoan');
        }

        Loan::query()->create([
            'asset_id' => $asset->id,
            'user_id' => $pegawai->id,
            'loan_date' => $validated['loan_date'],
            'planned_return_date' => $validated['planned_return_date'],
            'status' => 'Menunggu',
            'status_note' => $validated['status_note'] ?: 'Pengajuan peminjaman dari pegawai.',
        ]);

        return redirect()
            ->route('pegawai.loans.index')
            ->with('success', 'Pengajuan peminjaman berhasil dikirim dan menunggu persetujuan admin.');
    }

    private function availableAssetsQuery()
    {
        return Asset::query()
            ->with(['category', 'location'])
            ->where('status', 'Tersedia')
            ->whereDoesntHave('loans', function ($query) {
                $query->whereIn('status', ['Menunggu', 'Disetujui']);
            })
            ->orderBy('name');
    }
}
