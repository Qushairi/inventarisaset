<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Loan;
use App\Models\User;
use App\Support\AssetStateService;
use App\Support\PegawaiNotificationService;
use App\Support\SuratPeminjamanService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Mengelola daftar, pembuatan, pembaruan, persetujuan, dan penghapusan peminjaman aset oleh admin.
 */
class LoanController extends Controller
{
    private const DUPLICATE_LOAN_MESSAGE = 'Peminjaman untuk aset, peminjam, dan tanggal tersebut sudah ada. Tidak ada data baru yang disimpan.';

    /**
     * Menyediakan layanan sinkronisasi stok, notifikasi pegawai, dan surat peminjaman.
     */
    public function __construct(
        private readonly AssetStateService $assetStateService,
        private readonly PegawaiNotificationService $pegawaiNotificationService,
        private readonly SuratPeminjamanService $suratPeminjamanService,
    ) {}

    /**
     * Menampilkan daftar peminjaman aktif beserta filter dan data pendukung formulir.
     */
    public function index(Request $request): View
    {
        // Parameter edit membatasi daftar pada data yang sedang dibuka melalui formulir edit.
        $editId = $request->integer('edit');

        // Hanya filter pencarian dan status aktif yang diizinkan masuk ke kueri.
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in($this->activeStatusOptions())],
        ]);

        // Data dipaginasi setelah seluruh filter dari permintaan diterapkan.
        $loans = $this->paginatedLoans($filters, $editId);

        // Seluruh pilihan formulir create dan edit disiapkan bersama data tabel.
        return view('admin.loans.index', [
            'loans' => $loans,
            'loanTotal' => $loans->total(),
            'statuses' => $this->activeStatusOptions(),
            'createAssets' => Asset::query()
                ->with(['category', 'location'])
                ->where('status', 'Tersedia')
                ->where('quantity', '>', 0)
                ->orderBy('name')
                ->get(),
            'createEmployees' => $this->borrowerQuery()->get(),
            'createStatuses' => $this->statusOptions(),
            'editAssets' => Asset::query()->orderBy('name')->get(),
            'editEmployees' => $this->borrowerQuery()->get(),
            'editStatuses' => $this->statusOptions(),
            'filters' => $filters,
            'hasActiveFilters' => collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty(),
        ]);
    }

    /**
     * Membentuk paginator peminjaman yang belum memiliki catatan pengembalian.
     *
     * @param  array<string, mixed>  $filters
     */
    private function paginatedLoans(array $filters, int $editId): LengthAwarePaginator
    {
        // Eager loading mencegah kueri berulang saat data aset dan pengguna ditampilkan.
        $query = Loan::query()
            ->with(['asset', 'user'])
            ->whereDoesntHave('returnRecord');

        // Saat formulir edit aktif, hanya peminjaman terkait yang perlu dimuat.
        if ($editId) {
            $query->whereKey($editId);
        }

        // Filter dipisahkan agar susunan kueri utama tetap mudah dibaca.
        $this->applyLoanFilters($query, $filters);

        // Semua aset yang dibuat dalam sekali simpan dihitung sebagai satu transaksi/baris.
        $groups = $query
            ->latest('loan_date')
            ->latest('id')
            ->get()
            ->groupBy(fn (Loan $loan) => $loan->transaction_uuid ?: 'legacy-'.$loan->id)
            ->values();

        $page = Paginator::resolveCurrentPage();
        $perPage = 10;

        return new LengthAwarePaginator(
            $groups->forPage($page, $perPage)
                ->map(fn ($group) => $this->loanRow($group->first(), $group))
                ->values(),
            $groups->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }

    /**
     * Menambahkan filter pencarian bebas dan status ke kueri peminjaman.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyLoanFilters(Builder $query, array $filters): void
    {
        // Pencarian mencakup catatan status, identitas aset, dan identitas peminjam.
        if (filled($filters['search'] ?? null)) {
            $search = (string) $filters['search'];

            // Seluruh kondisi OR dikelompokkan agar tidak mengubah batasan kueri lainnya.
            $query->where(function (Builder $query) use ($search): void {
                $query->where('status_note', 'like', '%'.$search.'%')
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

        // Status diterapkan sebagai pencocokan tepat bila pengguna memilih filter.
        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
    }

    /**
     * Mengubah model peminjaman menjadi data siap tampil pada tabel dan formulir.
     *
     * @return array<string, mixed>
     */
    private function loanRow(Loan $loan, $group = null): array
    {
        $group ??= collect([$loan]);

        // Format tanggal dibedakan antara teks antarmuka dan nilai input HTML.
        return [
            'id' => $loan->id,
            'asset_id' => $loan->asset_id,
            'user_id' => $loan->user_id,
            'asset_name' => $loan->asset?->name,
            'asset_code' => $loan->asset?->code,
            'employee_name' => $loan->user?->name,
            'employee_email' => $loan->user?->email,
            'loan_date' => optional($loan->loan_date)->format('d/m/Y'),
            'edit_loan_date' => optional($loan->loan_date)->format('Y-m-d'),
            'return_plan' => 'Rencana kembali '.optional($loan->planned_return_date)->format('d/m/Y'),
            'planned_return_date' => optional($loan->planned_return_date)->format('Y-m-d'),
            'quantity' => $loan->quantity,
            'assets' => $group->map(fn (Loan $item) => [
                'name' => $item->asset?->name,
                'code' => $item->asset?->code,
                'quantity' => $item->quantity,
            ])->values()->all(),
            'status' => $loan->status,
            'status_variant' => $this->statusVariant($loan->status),
            'status_note' => $loan->status_note,
        ];
    }

    /**
     * Mengarahkan admin ke halaman indeks dengan formulir tambah dalam keadaan terbuka.
     */
    public function create()
    {
        // Parameter create digunakan oleh halaman indeks untuk membuka formulir tambah.
        return redirect()->route('admin.loans.index', ['create' => 1]);
    }

    /**
     * Memvalidasi dan menyimpan peminjaman baru serta memperbarui seluruh state terkait.
     */
    public function store(Request $request)
    {
        // Format lama satu aset tetap diterima, sedangkan form baru mengirim beberapa item sekaligus.
        $creation = $this->validateLoanCreation($request);

        // Seluruh item dikunci dan diperiksa sebelum satu pun peminjaman dibuat.
        $createdCount = DB::transaction(function () use ($request, $creation): int {
            $transactionUuid = (string) Str::uuid();
            $assetIds = collect($creation['items'])
                ->pluck('asset_id')
                ->sort()
                ->values();
            $assets = Asset::query()
                ->whereIn('id', $assetIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $duplicateAssetIds = Loan::query()
                ->where('user_id', $creation['common']['user_id'])
                ->whereDate('loan_date', $creation['common']['loan_date'])
                ->whereIn('asset_id', $assetIds)
                ->pluck('asset_id')
                ->map(fn ($assetId) => (int) $assetId)
                ->all();

            foreach ($creation['items'] as $index => $item) {
                $asset = $assets->get($item['asset_id']);
                $assetErrorKey = $creation['is_batch'] ? 'items.'.$index.'.asset_id' : 'asset_id';
                $quantityErrorKey = $creation['is_batch'] ? 'items.'.$index.'.quantity' : 'quantity';

                if (! $asset || $asset->status !== 'Tersedia' || $asset->quantity <= 0) {
                    throw ValidationException::withMessages([
                        $assetErrorKey => 'Aset yang dipilih sedang tidak tersedia untuk dipinjam.',
                    ]);
                }

                if ($item['quantity'] > $asset->quantity) {
                    throw ValidationException::withMessages([
                        $quantityErrorKey => $creation['is_batch']
                            ? 'Jumlah peminjaman untuk '.$asset->name.' melebihi stok yang tersedia ('.$asset->quantity.' unit).'
                            : 'Jumlah peminjaman melebihi stok aset yang tersedia.',
                    ]);
                }

                if (in_array($item['asset_id'], $duplicateAssetIds, true)) {
                    $messages = ['loan_date' => self::DUPLICATE_LOAN_MESSAGE];

                    if ($creation['is_batch']) {
                        $messages[$assetErrorKey] = self::DUPLICATE_LOAN_MESSAGE;
                    }

                    throw ValidationException::withMessages($messages);
                }
            }

            $loans = [];

            try {
                foreach ($creation['items'] as $item) {
                    $loans[] = Loan::query()->create(array_merge(
                        $creation['common'],
                        [
                            'transaction_uuid' => $transactionUuid,
                            'asset_id' => $item['asset_id'],
                            'quantity' => $item['quantity'],
                        ],
                    ));
                }
            } catch (UniqueConstraintViolationException) {
                // Indeks unik tetap menjadi pengaman jika dua request lolos validasi secara bersamaan.
                throw ValidationException::withMessages([
                    'loan_date' => self::DUPLICATE_LOAN_MESSAGE,
                ]);
            }

            // Setelah seluruh baris berhasil dibuat, stok dan dokumen diproses untuk setiap aset.
            foreach ($loans as $loan) {
                $this->syncApprover($loan, $request->user());
                $this->assetStateService->applyLoanStock($loan);
                $this->assetStateService->syncLoanById($loan->id);
                $loan->refresh();
                $this->refreshSuratPeminjamanIfEligible($loan, $request->user());
                $this->pegawaiNotificationService->sendLoanStatusNotification($loan);
            }

            return count($loans);
        });

        // Admin dikembalikan ke daftar dengan pesan keberhasilan.
        return redirect()
            ->route('admin.loans.index')
            ->with(
                'success',
                $createdCount === 1
                    ? 'Data peminjaman berhasil disimpan.'
                    : $createdCount.' data peminjaman berhasil disimpan sekaligus.'
            );
    }

    /**
     * Mengarahkan admin ke halaman indeks dengan data peminjaman terpilih untuk diedit.
     */
    public function edit(Loan $loan)
    {
        // ID edit memberi tahu halaman indeks data mana yang harus dimuat ke formulir.
        return redirect()->route('admin.loans.index', ['edit' => $loan->id]);
    }

    /**
     * Memperbarui peminjaman dan menyinkronkan stok, surat, serta notifikasi status.
     */
    public function update(Request $request, Loan $loan)
    {
        // ID aset lama diperlukan untuk menyinkronkan aset jika pilihan aset berubah.
        $previousAssetId = $loan->asset_id;

        // Stok lama harus dikembalikan lebih dahulu bila aset atau jumlah pinjaman berubah.
        $shouldReapplyStock = $loan->stock_applied_at
            && ! $loan->returnRecord()->whereNotNull('stock_applied_at')->exists()
            && (
                (int) $request->input('asset_id') !== (int) $loan->asset_id
                || (int) $request->input('quantity') !== (int) $loan->quantity
            );

        // Simpan status lama agar perubahan status dapat memicu notifikasi secara selektif.
        $validated = $this->validateLoan($request, $loan);
        $previousStatus = $loan->status;

        // Pembalikan sebelum update mencegah perhitungan stok lama tertinggal.
        if ($shouldReapplyStock) {
            $this->assetStateService->reverseLoanStock($loan);
        }

        // Data, penyetuju, dan stok diselaraskan berdasarkan nilai peminjaman terbaru.
        $loan->update($validated);
        $this->syncApprover($loan, $request->user());

        // Status selain disetujui atau selesai tidak boleh mempertahankan pengurangan stok.
        if (! in_array($loan->status, ['Disetujui', 'Selesai'], true)) {
            $this->assetStateService->reverseLoanStock($loan);
        }

        // Terapkan state akhir lalu sinkronkan peminjaman serta aset lama dan baru.
        $this->assetStateService->applyLoanStock($loan);
        $this->assetStateService->syncLoanById($loan->id);
        $this->assetStateService->syncAssetIds([$previousAssetId, $loan->asset_id]);
        $loan->refresh();
        $this->refreshSuratPeminjamanIfEligible($loan, $request->user());

        // Pegawai hanya diberi notifikasi bila status peminjaman benar-benar berubah.
        if ($previousStatus !== $loan->status) {
            $this->pegawaiNotificationService->sendLoanStatusNotification($loan);
        }

        // Kembali ke daftar setelah seluruh pembaruan berhasil diselesaikan.
        return redirect()
            ->route('admin.loans.index')
            ->with('success', 'Data peminjaman berhasil diperbarui.');
    }

    /**
     * Menerima atau menolak pengajuan peminjaman yang masih menunggu.
     */
    public function updateStatus(Request $request, Loan $loan)
    {
        // Aksi persetujuan dibatasi hanya pada dua status keputusan admin.
        $validated = $request->validate([
            'status' => ['required', Rule::in(['Disetujui', 'Ditolak'])],
        ]);

        $loans = Loan::query()
            ->when(
                filled($loan->transaction_uuid),
                fn (Builder $query) => $query->where('transaction_uuid', $loan->transaction_uuid),
                fn (Builder $query) => $query->whereKey($loan->id),
            )
            ->get();

        // Satu keputusan berlaku untuk seluruh aset dalam transaksi yang sama.
        if ($loans->contains(fn (Loan $item) => $item->status !== 'Menunggu')) {
            return redirect()
                ->route('admin.loans.index')
                ->with('error', 'Pengajuan peminjaman ini sudah diproses sebelumnya.');
        }

        DB::transaction(function () use ($loans, $validated, $request): void {
            foreach ($loans as $item) {
                $item->update([
                    'status' => $validated['status'],
                    'approved_by_user_id' => $validated['status'] === 'Disetujui' ? $request->user()?->id : null,
                ]);

                $this->assetStateService->applyLoanStock($item);
                $this->assetStateService->syncLoanById($item->id);
                $item->refresh();
                $this->refreshSuratPeminjamanIfEligible($item, $request->user());
                $this->pegawaiNotificationService->sendLoanStatusNotification($item);
            }
        });

        // Pesan sukses menyesuaikan keputusan yang dipilih oleh admin.
        return redirect()
            ->route('admin.loans.index')
            ->with('success', 'Pengajuan peminjaman berhasil '.($validated['status'] === 'Disetujui' ? 'diterima.' : 'ditolak.'));
    }

    /**
     * Menghapus peminjaman yang belum memiliki catatan pengembalian.
     */
    public function destroy(Loan $loan)
    {
        // Relasi pengembalian menjadi pengaman agar riwayat transaksi tidak terputus.
        if ($loan->returnRecord()->exists()) {
            return redirect()
                ->route('admin.loans.index')
                ->with('error', 'Peminjaman tidak bisa dihapus karena sudah memiliki data pengembalian.');
        }

        // Stok dikembalikan sebelum data dihapus, kemudian state aset dihitung ulang.
        $assetId = $loan->asset_id;
        $this->assetStateService->reverseLoanStock($loan);
        $loan->delete();
        $this->assetStateService->syncAssetById($assetId);

        // Admin menerima konfirmasi setelah penghapusan berhasil.
        return redirect()
            ->route('admin.loans.index')
            ->with('success', 'Data peminjaman berhasil dihapus.');
    }

    /**
     * Menentukan varian warna antarmuka berdasarkan status peminjaman.
     */
    private function statusVariant(string $status): string
    {
        // Status ditolak dan menunggu memiliki penekanan khusus; status lain dianggap berhasil.
        return match ($status) {
            'Ditolak' => 'danger',
            'Menunggu' => 'warning',
            default => 'success',
        };
    }

    /**
     * Mengembalikan seluruh status yang dapat dipilih pada formulir peminjaman.
     *
     * @return array<int, string>
     */
    private function statusOptions(): array
    {
        return ['Menunggu', 'Disetujui', 'Selesai', 'Ditolak'];
    }

    /**
     * Mengembalikan status yang relevan untuk daftar peminjaman aktif.
     *
     * @return array<int, string>
     */
    private function activeStatusOptions(): array
    {
        return ['Menunggu', 'Disetujui', 'Ditolak'];
    }

    /**
     * Membuat ulang surat peminjaman ketika status pinjaman sudah memenuhi syarat.
     */
    private function refreshSuratPeminjamanIfEligible(Loan $loan, ?User $approver = null): void
    {
        // Surat hanya berlaku untuk peminjaman yang disetujui atau sudah selesai.
        if (in_array($loan->status, ['Disetujui', 'Selesai'], true)) {
            $this->suratPeminjamanService->ensureForLoan($loan, $approver, force: true);
        }
    }

    /**
     * Menyelaraskan identitas admin penyetuju dengan status peminjaman saat ini.
     */
    private function syncApprover(Loan $loan, ?User $approver = null): void
    {
        // Status yang belum disetujui atau ditolak tidak menyimpan admin penyetuju.
        $approvedByUserId = in_array($loan->status, ['Disetujui', 'Selesai'], true)
            ? $approver?->id
            : null;

        // Penyimpanan senyap hanya dilakukan jika nilai penyetuju memang berubah.
        if ($loan->approved_by_user_id !== $approvedByUserId) {
            $loan->forceFill([
                'approved_by_user_id' => $approvedByUserId,
            ])->saveQuietly();
        }
    }

    /**
     * Memvalidasi data peminjaman baru maupun data peminjaman yang diperbarui.
     *
     * @return array<string, mixed>
     */
    private function validateLoan(Request $request, ?Loan $loan = null): array
    {
        // whereDate menjaga pemeriksaan tetap konsisten untuk kolom date di MySQL maupun SQLite.
        $uniqueRule = function (string $attribute, mixed $value, \Closure $fail) use ($request, $loan): void {
            $duplicateQuery = Loan::query()
                ->where('asset_id', $request->input('asset_id'))
                ->where('user_id', $request->input('user_id'))
                ->whereDate('loan_date', $value);

            if ($loan) {
                $duplicateQuery->whereKeyNot($loan->getKey());
            }

            if ($duplicateQuery->exists()) {
                $fail(self::DUPLICATE_LOAN_MESSAGE);
            }
        };

        // Validasi memastikan relasi, tanggal, jumlah, status, dan catatan sesuai kebutuhan domain.
        return $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', ['admin', 'pegawai'])),
            ],
            'loan_date' => ['bail', 'required', 'date', $uniqueRule],
            'planned_return_date' => ['nullable', 'date', 'after_or_equal:loan_date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'status_note' => ['nullable', 'string', 'max:255'],
        ], [
            'asset_id.required' => 'Aset wajib dipilih.',
            'asset_id.exists' => 'Aset yang dipilih tidak tersedia.',
            'user_id.required' => 'Peminjam wajib dipilih.',
            'user_id.exists' => 'Peminjam yang dipilih tidak tersedia.',
            'loan_date.required' => 'Tanggal pinjam wajib diisi.',
            'loan_date.date' => 'Format tanggal pinjam tidak valid.',
            'planned_return_date.date' => 'Format rencana kembali tidak valid.',
            'planned_return_date.after_or_equal' => 'Rencana kembali tidak boleh lebih awal dari tanggal pinjam.',
            'quantity.required' => 'Jumlah wajib diisi.',
            'quantity.integer' => 'Jumlah harus berupa bilangan bulat.',
            'quantity.min' => 'Jumlah peminjaman minimal 1.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status yang dipilih tidak valid.',
            'status_note.string' => 'Keterangan harus berupa teks.',
            'status_note.max' => 'Keterangan maksimal 255 karakter.',
        ], [
            'asset_id' => 'aset',
            'user_id' => 'peminjam',
            'loan_date' => 'tanggal pinjam',
            'planned_return_date' => 'rencana kembali',
            'quantity' => 'jumlah',
            'status' => 'status',
            'status_note' => 'keterangan',
        ]);
    }

    /**
     * Memvalidasi data bersama dan daftar aset untuk pembuatan peminjaman massal.
     *
     * @return array{common: array<string, mixed>, items: list<array{asset_id: int, quantity: int}>, is_batch: bool}
     */
    private function validateLoanCreation(Request $request): array
    {
        // Payload tunggal lama dinormalisasi agar integrasi yang sudah ada tetap berfungsi.
        if (! $request->has('items')) {
            $validated = $this->validateLoan($request);
            $item = [
                'asset_id' => (int) $validated['asset_id'],
                'quantity' => (int) $validated['quantity'],
            ];
            unset($validated['asset_id'], $validated['quantity']);

            return [
                'common' => $validated,
                'items' => [$item],
                'is_batch' => false,
            ];
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.asset_id' => ['bail', 'required', 'integer', 'distinct', 'exists:assets,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', ['admin', 'pegawai'])),
            ],
            'loan_date' => ['required', 'date'],
            'planned_return_date' => ['nullable', 'date', 'after_or_equal:loan_date'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'status_note' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Pilih minimal satu aset untuk dipinjam.',
            'items.array' => 'Daftar aset tidak valid.',
            'items.min' => 'Pilih minimal satu aset untuk dipinjam.',
            'items.max' => 'Maksimal 50 aset dalam satu peminjaman.',
            'items.*.asset_id.required' => 'Aset wajib dipilih.',
            'items.*.asset_id.integer' => 'Aset yang dipilih tidak valid.',
            'items.*.asset_id.distinct' => 'Aset yang sama tidak boleh dipilih lebih dari sekali.',
            'items.*.asset_id.exists' => 'Aset yang dipilih tidak tersedia.',
            'items.*.quantity.required' => 'Jumlah setiap aset wajib diisi.',
            'items.*.quantity.integer' => 'Jumlah aset harus berupa bilangan bulat.',
            'items.*.quantity.min' => 'Jumlah setiap aset minimal 1.',
            'user_id.required' => 'Peminjam wajib dipilih.',
            'user_id.exists' => 'Peminjam yang dipilih tidak tersedia.',
            'loan_date.required' => 'Tanggal pinjam wajib diisi.',
            'loan_date.date' => 'Format tanggal pinjam tidak valid.',
            'planned_return_date.date' => 'Format rencana kembali tidak valid.',
            'planned_return_date.after_or_equal' => 'Rencana kembali tidak boleh lebih awal dari tanggal pinjam.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status yang dipilih tidak valid.',
            'status_note.string' => 'Keterangan harus berupa teks.',
            'status_note.max' => 'Keterangan maksimal 255 karakter.',
        ]);

        $items = collect($validated['items'])
            ->map(fn (array $item) => [
                'asset_id' => (int) $item['asset_id'],
                'quantity' => (int) $item['quantity'],
            ])
            ->values()
            ->all();
        unset($validated['items']);

        return [
            'common' => $validated,
            'items' => $items,
            'is_batch' => true,
        ];
    }

    /**
     * Menyusun kueri pengguna yang diperbolehkan menjadi peminjam.
     */
    private function borrowerQuery(): Builder
    {
        // Admin dan pegawai diurutkan berdasarkan peran lalu nama untuk pilihan formulir.
        return User::query()
            ->whereIn('role', ['admin', 'pegawai'])
            ->orderBy('role')
            ->orderBy('name');
    }
}
