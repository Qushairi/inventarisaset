<?php

namespace App\Http\Controllers\Pegawai;

use App\Models\AssetReturn;
use App\Models\Loan;
use App\Support\AdminNotificationService;
use App\Support\SuratPeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Mengelola pengajuan dan riwayat pengembalian aset milik pegawai.
 */
class ReturnController extends BasePegawaiController
{
    /**
     * Menyuntikkan layanan surat dan notifikasi admin.
     */
    public function __construct(
        private readonly SuratPeminjamanService $suratPeminjamanService,
        private readonly AdminNotificationService $adminNotificationService,
    ) {}

    /**
     * Menampilkan riwayat pengembalian dan peminjaman yang dapat dikembalikan.
     */
    public function index(Request $request)
    {
        // Seluruh data dibatasi pada pegawai yang sedang aktif.
        $pegawai = $this->currentPegawai();

        // Gunakan ukuran halaman yang sudah dinormalisasi.
        $perPage = $this->perPage($request);

        // Muat relasi yang diperlukan untuk detail aset, pinjaman, dan surat.
        $returns = AssetReturn::query()
            ->with(['asset', 'loan.asset.category', 'loan.user', 'loan.approvedBy', 'loan.suratPeminjaman'])
            ->where('user_id', $pegawai->id)
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (AssetReturn $return) {
                // Ambil pinjaman asal untuk tanggal dan akses surat peminjaman.
                $loan = $return->loan;

                // Surat hanya dibuat bila catatan pengembalian masih memiliki pinjaman terkait.
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

        // Lengkapi halaman dengan opsi kondisi, total, dan daftar pinjaman yang valid.
        return view('pegawai.returns.index', $this->layoutData([
            'conditions' => $this->conditionOptions(),
            'returns' => $returns,
            'returnTotal' => AssetReturn::query()->where('user_id', $pegawai->id)->count(),
            'returnableLoans' => $this->returnableLoansQuery($pegawai->id)->get(),
        ]));
    }

    /**
     * Membuat pengajuan pengembalian yang menunggu verifikasi penerimaan oleh admin.
     */
    public function store(Request $request): RedirectResponse
    {
        // Identitas pengembali berasal dari sesi, bukan dari input pengguna.
        $pegawai = $this->currentPegawai();

        // Validasi formulir ke bag modal pengembalian.
        $validated = $request->validateWithBag('createReturn', [
            'loan_id' => ['required', 'exists:loans,id'],
            'returned_at' => ['required', 'date', 'after_or_equal:today'],
            'condition' => ['nullable', 'string'],
            'item_conditions' => ['nullable', 'array'],
            'report_note' => ['nullable', 'string', 'max:255'],
        ]);

        // Kunci pinjaman agar klik ganda tidak membuat dua pengajuan untuk barang yang sama.
        $return = DB::transaction(function () use ($pegawai, $validated, $request): AssetReturn {
            $loan = Loan::query()
                ->whereKey($validated['loan_id'])
                ->where('user_id', $pegawai->id)
                ->lockForUpdate()
                ->first();

            // Tolak ID yang tidak aktif atau sudah memiliki catatan pengembalian.
            if (! $loan || $loan->status !== 'Disetujui' || $loan->returnRecord()->exists()) {
                throw ValidationException::withMessages([
                    'loan_id' => 'Peminjaman yang dipilih belum dapat diajukan untuk pengembalian.',
                ])->errorBag('createReturn');
            }

            // Lindungi integritas kronologi jika tanggal pinjam berada setelah tanggal kembali.
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

            return AssetReturn::query()->create([
                'loan_id' => $loan->id,
                'asset_id' => $loan->asset_id,
                'user_id' => $pegawai->id,
                'returned_at' => $validated['returned_at'],
                'verified_note' => null,
                'condition' => $finalCondition,
                'status' => 'Menunggu Verifikasi',
                'status_note' => 'Menunggu verifikasi admin.',
                'report_number' => $this->generateReportNumber(),
                'report_note' => $fullReportNote ?: null,
            ]);
        });

        // Informasikan pengembalian baru kepada admin.
        $this->adminNotificationService->sendReturnRequestNotification($return);

        $assetName = $return->asset?->name ?? ($return->loan?->asset?->name ?? 'Aset');

        \App\Support\ActivityLogger::log(
            'return_created',
            'Pengajuan Pengembalian Aset',
            "Mengajukan pengembalian aset {$assetName} dengan kondisi {$return->condition}."
        );

        // Kembali ke daftar riwayat dengan pesan keberhasilan.
        return redirect()
            ->route('pegawai.returns.index')
            ->with('success', 'Pengajuan pengembalian berhasil dikirim dan menunggu verifikasi admin.');
    }

    /**
     * Mengembalikan daftar kondisi aset yang sah untuk pengembalian.
     *
     * @return list<string>
     */
    private function conditionOptions(): array
    {
        return ['Baik', 'Rusak Ringan', 'Rusak Berat'];
    }

    /**
     * Membuat nomor berita acara unik berdasarkan waktu saat ini.
     */
    private function generateReportNumber(): string
    {
        // Waktu menjadi bagian nomor agar urut dan mudah dilacak.
        $reportDate = now();

        // Tambah satu detik dan ulangi bila nomor pada detik tersebut sudah digunakan.
        do {
            $reportNumber = 'BA-'.$reportDate->format('YmdHis');
            $reportDate->addSecond();
        } while (AssetReturn::query()->where('report_number', $reportNumber)->exists());

        // Kembalikan nomor pertama yang belum tercatat di database.
        return $reportNumber;
    }

    /**
     * Menyusun query pinjaman disetujui yang belum memiliki pengembalian.
     */
    private function returnableLoansQuery(int $pegawaiId)
    {
        // Filter kepemilikan, status persetujuan, dan ketiadaan relasi returnRecord.
        return Loan::query()
            ->with('asset')
            ->where('user_id', $pegawaiId)
            ->where('status', 'Disetujui')
            ->whereDoesntHave('returnRecord')
            ->orderByDesc('loan_date');
    }

    /**
     * Menentukan jumlah riwayat yang ditampilkan pada setiap halaman.
     */
    private function perPage(Request $request): int
    {
        // Ambil nilai dari query string dengan nilai awal 10.
        $perPage = (int) $request->query('per_page', 10);

        // Batasi pilihan untuk mencegah query dengan ukuran halaman berlebihan.
        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }
}
