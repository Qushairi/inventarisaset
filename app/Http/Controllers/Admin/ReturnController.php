<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Loan;
use App\Models\User;
use App\Support\AssetReturnLetterService;
use App\Support\AssetStateService;
use App\Support\PegawaiNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Mengelola pencatatan, pembaruan, penghapusan, dan surat pengembalian aset oleh admin.
 */
class ReturnController extends Controller
{
    /**
     * Menyediakan layanan sinkronisasi stok, pembuatan surat, dan notifikasi pegawai.
     */
    public function __construct(
        private readonly AssetStateService $assetStateService,
        private readonly AssetReturnLetterService $assetReturnLetterService,
        private readonly PegawaiNotificationService $pegawaiNotificationService,
    ) {}

    /**
     * Menampilkan daftar pengembalian beserta filter dan pilihan pinjaman yang dapat dikembalikan.
     */
    public function index(Request $request): View
    {
        // Hanya kata pencarian dan kondisi aset yang valid diteruskan ke kueri.
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', Rule::in($this->conditionOptions())],
        ]);

        // Data pengembalian diambil setelah filter diterapkan dan hasilnya dipaginasi.
        $returns = $this->paginatedReturns($filters);

        // Pilihan formulir hanya memuat pinjaman disetujui yang belum dikembalikan.
        return view('admin.returns.index', [
            'returns' => $returns,
            'returnTotal' => $returns->total(),
            'conditions' => $this->conditionOptions(),
            'createLoans' => Loan::query()
                ->with(['asset', 'user'])
                ->where('status', 'Disetujui')
                ->whereDoesntHave('returnRecord')
                ->latest('loan_date')
                ->get()
                ->groupBy(fn (Loan $loan) => $loan->transaction_uuid ?: 'loan-'.$loan->id)
                ->map(function ($group): array {
                    $loan = $group->first();

                    return [
                        'id' => $loan->id,
                        'label' => $group->map(fn (Loan $item) => $item->asset?->name.' ('.$item->asset?->code.')')->join(', ')
                            .' — '.$loan->user?->name.' — '.optional($loan->loan_date)->format('d/m/Y'),
                    ];
                })
                ->values(),
            'filters' => $filters,
            'hasActiveFilters' => collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty(),
        ]);
    }

    /**
     * Membentuk paginator pengembalian lengkap dengan relasi yang dibutuhkan tampilan.
     *
     * @param  array<string, mixed>  $filters
     */
    private function paginatedReturns(array $filters): LengthAwarePaginator
    {
        // Eager loading mencegah kueri berulang untuk aset, pengguna, dan peminjaman terkait.
        $query = AssetReturn::query()->with(['asset', 'user', 'loan.asset', 'loan.user']);

        // Filter dipisahkan agar susunan kueri utama tetap ringkas.
        $this->applyReturnFilters($query, $filters);

        // Pengembalian aset dari satu transaksi peminjaman ditampilkan sebagai satu baris.
        $groups = $query
            ->latest('returned_at')
            ->latest('id')
            ->get()
            ->groupBy(fn (AssetReturn $return) => $return->loan?->transaction_uuid
                ? 'transaction-'.$return->loan->transaction_uuid
                : 'return-'.$return->id)
            ->values();

        $page = Paginator::resolveCurrentPage();
        $perPage = 10;

        return new LengthAwarePaginator(
            $groups->forPage($page, $perPage)
                ->map(fn ($group) => $this->returnRow($group->first(), $group))
                ->values(),
            $groups->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }

    /**
     * Menambahkan filter pencarian bebas dan kondisi aset ke kueri pengembalian.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyReturnFilters(Builder $query, array $filters): void
    {
        // Pencarian mencakup catatan, nomor berita acara, aset, dan pengguna.
        if (filled($filters['search'] ?? null)) {
            $search = (string) $filters['search'];

            // Seluruh kondisi OR dikelompokkan agar tidak mengganggu batasan kueri lainnya.
            $query->where(function (Builder $query) use ($search): void {
                $query->where('verified_note', 'like', '%'.$search.'%')
                    ->orWhere('status_note', 'like', '%'.$search.'%')
                    ->orWhere('report_number', 'like', '%'.$search.'%')
                    ->orWhere('report_note', 'like', '%'.$search.'%')
                    ->orWhereHas('asset', function (Builder $query) use ($search): void {
                        $query->where('name', 'like', '%'.$search.'%')
                            ->orWhere('code', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('user', function (Builder $query) use ($search): void {
                        $query->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        // Kondisi aset diterapkan sebagai pencocokan tepat bila filter dipilih.
        if (filled($filters['condition'] ?? null)) {
            $query->where('condition', $filters['condition']);
        }
    }

    /**
     * Mengubah model pengembalian menjadi data siap tampil pada tabel.
     *
     * @return array<string, mixed>
     */
    private function returnRow(AssetReturn $return, $group = null): array
    {
        $group ??= collect([$return]);

        // Data pinjaman menjadi sumber cadangan jika relasi langsung pengembalian tidak tersedia.
        $loan = $return->loan;
        $loanDate = $loan?->loan_date;
        $returnedAt = $return->returned_at;

        // Tanggal, durasi, dan varian status diformat khusus untuk kebutuhan antarmuka.
        return [
            'id' => $return->id,
            'asset_name' => $return->asset?->name ?? $loan?->asset?->name,
            'asset_code' => $return->asset?->code ?? $loan?->asset?->code,
            'employee_name' => $return->user?->name ?? $loan?->user?->name,
            'employee_email' => $return->user?->email ?? $loan?->user?->email,
            'loan_date' => optional($loanDate)->format('d/m/Y'),
            'planned_return_date' => optional($loan?->planned_return_date)->format('d/m/Y'),
            'loan_quantity' => max(1, (int) ($loan?->quantity ?? 1)),
            'total_quantity' => $group->sum(fn (AssetReturn $item) => max(1, (int) ($item->loan?->quantity ?? 1))),
            'assets' => $group->map(fn (AssetReturn $item) => [
                'name' => $item->asset?->name ?? $item->loan?->asset?->name,
                'code' => $item->asset?->code ?? $item->loan?->asset?->code,
                'quantity' => max(1, (int) ($item->loan?->quantity ?? 1)),
                'condition' => $item->condition,
                'condition_variant' => $this->conditionVariant($item->condition),
                'report_number' => $item->report_number,
            ])->values()->all(),
            'loan_duration' => $loanDate && $returnedAt
                ? $loanDate->diffInDays($returnedAt).' hari'
                : '-',
            'returned_at' => optional($returnedAt)->format('d/m/Y'),
            'verified_note' => $return->verified_note,
            'condition' => $return->condition,
            'condition_variant' => $this->conditionVariant($return->condition),
            'status' => $return->status,
            'status_variant' => $return->status === 'Terverifikasi' ? 'success' : 'warning',
            'status_note' => $return->status_note,
            'report_number' => $return->report_number,
            'report_note' => $return->report_note,
        ];
    }

    /**
     * Mengarahkan admin ke halaman indeks dengan formulir tambah dalam keadaan terbuka.
     */
    public function create(): RedirectResponse
    {
        // Parameter create digunakan oleh halaman indeks untuk membuka formulir pengembalian.
        return redirect()->route('admin.returns.index', ['create' => 1]);
    }

    /**
     * Memvalidasi dan menyimpan pengembalian baru secara aman dalam transaksi basis data.
     */
    public function store(Request $request)
    {
        // Semua data masukan divalidasi sebelum transaksi pencatatan dimulai.
        $validated = $this->validateReturn($request);

        // Transaksi dan penguncian baris mencegah satu pinjaman dikembalikan secara bersamaan.
        $returnRecords = DB::transaction(function () use ($validated) {
            $selectedLoan = Loan::query()
                ->whereKey($validated['loan_id'])
                ->lockForUpdate()
                ->first();

            if (! $selectedLoan) {
                throw ValidationException::withMessages([
                    'loan_id' => 'Peminjaman yang dipilih sudah tidak dapat dikembalikan.',
                ]);
            }

            $loans = Loan::query()
                ->when(
                    filled($selectedLoan->transaction_uuid),
                    fn (Builder $query) => $query->where('transaction_uuid', $selectedLoan->transaction_uuid),
                    fn (Builder $query) => $query->whereKey($selectedLoan->id),
                )
                ->lockForUpdate()
                ->get();

            foreach ($loans as $loan) {
                if ($loan->status !== 'Disetujui' || $loan->returnRecord()->exists()) {
                    throw ValidationException::withMessages([
                        'loan_id' => 'Salah satu aset dalam peminjaman ini sudah tidak dapat dikembalikan.',
                    ]);
                }

                if ($loan->loan_date && $loan->loan_date->gt(Carbon::parse($validated['returned_at']))) {
                    throw ValidationException::withMessages([
                        'returned_at' => 'Tanggal kembali tidak boleh lebih awal dari tanggal peminjaman.',
                    ]);
                }
            }

            return $loans->values()->map(function (Loan $loan, int $index) use ($validated): AssetReturn {
                $returnData = array_merge($validated, [
                    'loan_id' => $loan->id,
                    'asset_id' => $loan->asset_id,
                    'user_id' => $loan->user_id,
                    'report_number' => $index === 0
                        ? $validated['report_number']
                        : $this->generateReportNumber(),
                ]);

                return AssetReturn::query()->create($returnData);
            });
        });

        // Pencatatan baru belum mengubah stok atau menyelesaikan peminjaman sampai diverifikasi admin.
        return redirect()
            ->route('admin.returns.index')
            ->with('success', $returnRecords->count() === 1
                ? 'Data pengembalian berhasil dicatat dan menunggu verifikasi.'
                : $returnRecords->count().' aset berhasil dicatat dalam satu pengembalian dan menunggu verifikasi.');
    }

    /**
     * Memverifikasi penerimaan fisik aset lalu menerapkan perubahan stok dan status pinjaman.
     */
    public function verify(Request $request, AssetReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'condition' => ['required', Rule::in($this->conditionOptions())],
            'verified_note' => ['nullable', 'string', 'max:255'],
        ]);

        $transactionUuid = $return->loan?->transaction_uuid;

        // Kunci seluruh pengembalian dalam transaksi peminjaman yang sama.
        $verifiedReturns = DB::transaction(function () use ($return, $validated, $transactionUuid) {
            $returnRecords = AssetReturn::query()
                ->whereHas('loan', fn (Builder $query) => $transactionUuid
                    ? $query->where('transaction_uuid', $transactionUuid)
                    : $query->whereKey($return->loan_id))
                ->lockForUpdate()
                ->get();

            if ($returnRecords->isEmpty()
                || $returnRecords->contains(fn (AssetReturn $item) => $item->status !== 'Menunggu Verifikasi')) {
                return null;
            }

            foreach ($returnRecords as $returnRecord) {
                $loan = Loan::query()
                    ->whereKey($returnRecord->loan_id)
                    ->lockForUpdate()
                    ->first();

                $hasOtherVerifiedReturn = AssetReturn::query()
                    ->where('loan_id', $returnRecord->loan_id)
                    ->whereKeyNot($returnRecord->id)
                    ->where('status', 'Terverifikasi')
                    ->exists();

                if (! $loan || $loan->status !== 'Disetujui' || $hasOtherVerifiedReturn) {
                    return null;
                }

                $returnRecord->forceFill([
                    'condition' => $validated['condition'],
                    'verified_note' => filled($validated['verified_note'] ?? null)
                        ? $validated['verified_note']
                        : 'Aset telah diterima dan diperiksa oleh admin.',
                    'status' => 'Terverifikasi',
                    'status_note' => $this->verifiedStatusNote($validated['condition']),
                ])->save();

                // Efek stok dan penyelesaian pinjaman diterapkan untuk setiap aset dalam transaksi.
                $this->assetStateService->applyReturnStock($returnRecord);
                $this->assetStateService->syncLoanById($returnRecord->loan_id);
                $returnRecord->refresh();
                $this->assetStateService->syncAssetIds([
                    $returnRecord->asset_id,
                    $returnRecord->stock_asset_id,
                ], true);
            }

            return $returnRecords;
        });

        if (! $verifiedReturns) {
            return redirect()
                ->route('admin.returns.index')
                ->with('error', 'Pengembalian sudah diverifikasi atau peminjamannya tidak lagi aktif.');
        }

        // Pegawai diberi tahu setelah transaksi verifikasi dan perubahan stok selesai.
        $verifiedReturns->each(fn (AssetReturn $item) => $this->pegawaiNotificationService->sendReturnVerifiedNotification($item));

        return redirect()
            ->route('admin.returns.index')
            ->with('success', 'Pengembalian berhasil diverifikasi.');
    }

    public function updateStatus(Request $request, AssetReturn $return)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Terverifikasi', 'Ditolak'])],
        ]);

        $status = $validated['status'];

        if ($status === 'Terverifikasi') {
            $return->update([
                'status' => 'Terverifikasi',
                'verified_note' => 'Pengembalian aset telah diverifikasi dan disetujui admin.',
            ]);

            $this->assetStateService->applyReturnStock($return);
            $this->assetStateService->syncLoanById($return->loan_id);
            $return->refresh();
            $this->assetStateService->syncAssetIds([$return->asset_id, $return->stock_asset_id], true);

            $this->pegawaiNotificationService->sendReturnVerifiedNotification($return);

            $message = 'Pengembalian aset berhasil diverifikasi dan disetujui.';
        } else {
            $return->update([
                'status' => 'Ditolak',
                'verified_note' => 'Pengembalian aset ditolak admin.',
            ]);

            $this->assetStateService->syncLoanById($return->loan_id);
            $this->pegawaiNotificationService->sendReturnRejectedNotification($return);

            $message = 'Pengembalian aset telah ditolak.';
        }

        return redirect()
            ->route('admin.returns.index')
            ->with('success', $message);
    }

    /**
     * Menampilkan formulir edit beserta seluruh pilihan relasi yang dibutuhkan.
     */
    public function edit(AssetReturn $return)
    {
        // Daftar aset, pegawai, pinjaman, dan kondisi disiapkan untuk mengisi formulir edit.
        return view('admin.returns.edit', [
            'returnRecord' => $return,
            'assets' => Asset::query()->orderBy('name')->get(),
            'employees' => User::query()->where('role', 'pegawai')->orderBy('name')->get(),
            'loans' => Loan::query()->with(['asset', 'user'])->latest('loan_date')->get(),
            'conditions' => $this->conditionOptions(),
        ]);
    }

    /**
     * Memperbarui pengembalian dan menghitung ulang stok serta status relasi terkait.
     */
    public function update(Request $request, AssetReturn $return)
    {
        // Identitas lama disimpan agar state relasi sebelumnya tetap dapat disinkronkan.
        $previousAssetId = $return->asset_id;
        $previousLoanId = $return->loan_id;

        // Data baru divalidasi tanpa mengubah status verifikasi yang sudah ada.
        $validated = $this->validateReturn($request, $return);

        // Efek stok lama dibatalkan sebelum data baru diterapkan agar jumlah tidak terhitung ganda.
        $this->assetStateService->reverseReturnStock($return);
        $return->update($validated);
        $this->assetStateService->applyReturnStock($return);
        $return->refresh();

        // Pinjaman dan aset lama maupun baru dihitung ulang berdasarkan catatan terbaru.
        $this->assetStateService->syncLoanById($previousLoanId);
        $this->assetStateService->syncLoanById($return->loan_id);
        $this->assetStateService->syncAssetIds([$previousAssetId, $return->asset_id, $return->stock_asset_id], true);

        // Kembali ke daftar setelah seluruh pembaruan selesai.
        return redirect()
            ->route('admin.returns.index')
            ->with('success', 'Data pengembalian berhasil diperbarui.');
    }

    /**
     * Menghapus catatan pengembalian dan memulihkan state stok serta pinjaman.
     */
    public function destroy(AssetReturn $return)
    {
        // Seluruh ID relasi disimpan sebelum model dihapus untuk kebutuhan sinkronisasi.
        $assetId = $return->asset_id;
        $loanId = $return->loan_id;
        $stockAssetId = $return->stock_asset_id;

        // Efek pengembalian dibalik, data dihapus, lalu state relasi dihitung ulang.
        $this->assetStateService->reverseReturnStock($return);
        $return->delete();
        $this->assetStateService->syncLoanById($loanId);
        $this->assetStateService->syncAssetIds([$assetId, $stockAssetId], true);

        // Admin menerima konfirmasi setelah penghapusan berhasil.
        return redirect()
            ->route('admin.returns.index')
            ->with('success', 'Data pengembalian berhasil dihapus.');
    }

    /**
     * Menampilkan pratinjau surat pengembalian beserta tautan tindakan terkait.
     */
    public function showLetter(AssetReturn $return)
    {
        // Seluruh relasi surat dimuat terlebih dahulu agar layanan dapat menyusun data lengkap.
        $return->loadMissing(['asset.category', 'user', 'loan.asset.category', 'loan.user', 'loan.approvedBy']);

        // Data surat digabung dengan URL navigasi dan unduhan.
        return view('admin.returns.letter', array_merge(
            $this->assetReturnLetterService->previewData($return, $this->currentAdmin()),
            [
                'backUrl' => route('admin.returns.index'),
                'downloadUrl' => route('admin.returns.letter.download', $return),
            ],
        ));
    }

    /**
     * Menghasilkan dan mengunduh surat pengembalian dalam format PDF.
     */
    public function downloadLetter(AssetReturn $return)
    {
        // Relasi yang diperlukan PDF dimuat sebelum dokumen dibangkitkan.
        $return->loadMissing(['asset.category', 'user', 'loan.asset.category', 'loan.user', 'loan.approvedBy']);

        // Biner PDF dikirim sebagai lampiran menggunakan nama berkas dari layanan surat.
        return response($this->assetReturnLetterService->pdfBinary($return, $this->currentAdmin()))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$this->assetReturnLetterService->pdfFilename($return).'"');
    }

    /**
     * Menentukan varian warna antarmuka berdasarkan kondisi aset yang dikembalikan.
     */
    private function conditionVariant(string $condition): string
    {
        // Kerusakan ringan dan berat diberi penekanan berbeda; kondisi lain dianggap baik.
        return match ($condition) {
            'Rusak Ringan' => 'warning',
            'Rusak Berat' => 'danger',
            default => 'success',
        };
    }

    /**
     * Mengembalikan daftar kondisi aset yang diizinkan pada formulir.
     *
     * @return array<int, string>
     */
    private function conditionOptions(): array
    {
        return ['Baik', 'Rusak Ringan', 'Rusak Berat'];
    }

    /**
     * Menyusun keterangan hasil verifikasi sesuai kondisi fisik yang diterima admin.
     */
    private function verifiedStatusNote(string $condition): string
    {
        return match ($condition) {
            'Rusak Ringan' => 'Pengembalian telah diverifikasi dengan kondisi rusak ringan.',
            'Rusak Berat' => 'Pengembalian telah diverifikasi dan aset masuk proses perbaikan.',
            default => 'Pengembalian telah diverifikasi admin.',
        };
    }

    /**
     * Memvalidasi data pengembalian dan melengkapi status serta nomor berita acara.
     *
     * @return array<string, mixed>
     */
    private function validateReturn(Request $request, ?AssetReturn $return = null): array
    {
        // Kebutuhan relasi dan nomor berita acara dibedakan antara proses tambah dan edit.
        $isCreating = $return === null;
        $reportNumberRules = [
            $return ? 'required' : 'nullable',
            'string',
            'max:100',
            Rule::unique('asset_returns', 'report_number')->ignore($return?->id),
        ];

        // Saat pembuatan, aset dan pengguna dikeluarkan karena nilainya diambil dari pinjaman.
        $validated = $request->validate([
            'loan_id' => [$isCreating ? 'required' : 'nullable', 'exists:loans,id'],
            'asset_id' => $isCreating ? ['exclude'] : ['required', 'exists:assets,id'],
            'user_id' => $isCreating ? ['exclude'] : ['required', 'exists:users,id'],
            'returned_at' => ['required', 'date', 'after_or_equal:today'],
            'verified_note' => $isCreating || $return?->status !== 'Terverifikasi'
                ? ['exclude']
                : ['nullable', 'string', 'max:255'],
            'condition' => ['required', Rule::in($this->conditionOptions())],
            'status_note' => ['nullable', 'string', 'max:255'],
            'report_number' => $reportNumberRules,
            'report_note' => ['nullable', 'string', 'max:255'],
        ]);

        // Catatan baru selalu menunggu pemeriksaan; edit mempertahankan status yang sudah ada.
        $validated['status'] = $return?->status ?? 'Menunggu Verifikasi';
        if ($isCreating && blank($validated['status_note'] ?? null)) {
            $validated['status_note'] = 'Menunggu verifikasi admin.';
        }
        $validated['report_number'] = filled($validated['report_number'] ?? null)
            ? $validated['report_number']
            : $this->generateReportNumber();

        return $validated;
    }

    /**
     * Membuat nomor berita acara unik berdasarkan waktu saat ini.
     */
    private function generateReportNumber(): string
    {
        // Waktu awal menjadi bagian nomor agar format tetap kronologis.
        $reportDate = now();

        // Tambahkan satu detik dan ulangi sampai nomor yang belum digunakan ditemukan.
        do {
            $reportNumber = 'BA-'.$reportDate->format('YmdHis');
            $reportDate->addSecond();
        } while (AssetReturn::query()->where('report_number', $reportNumber)->exists());

        return $reportNumber;
    }

    /**
     * Mengembalikan pengguna aktif hanya jika pengguna tersebut benar-benar admin.
     */
    private function currentAdmin(): ?User
    {
        // Autentikasi dapat menghasilkan tipe lain atau pengguna non-admin, sehingga perlu diperiksa.
        $user = auth()->user();

        // Pengguna non-admin tidak diteruskan sebagai penandatangan surat.
        return $user instanceof User && $user->role === 'admin'
            ? $user
            : null;
    }
}
