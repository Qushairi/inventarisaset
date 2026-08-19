<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use App\Support\ReportExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menangani halaman ringkasan laporan serta pembuatan laporan Excel untuk admin.
 */
class ReportController extends Controller
{
    /**
     * Menampilkan ringkasan statistik, pratinjau transaksi, dan pilihan filter laporan.
     */
    public function index()
    {
        // Hitung statistik utama yang akan ditampilkan pada kartu ringkasan laporan.
        $assetTotal = Asset::query()->count();
        $loanTotal = Loan::query()->count();
        $returnTotal = AssetReturn::query()->count();
        $availableAssetTotal = Asset::query()->where('status', 'Tersedia')->count();

        // Ambil tiga peminjaman terbaru beserta relasinya untuk pratinjau singkat.
        $loanPreview = Loan::query()
            ->with(['asset', 'user'])
            ->latest('loan_date')
            ->take(3)
            ->get()
            // Ubah setiap model menjadi struktur data yang siap dipakai oleh tampilan.
            ->map(fn (Loan $loan) => [
                'asset_name' => $loan->asset?->name,
                'asset_code' => $loan->asset?->code,
                'employee_name' => $loan->user?->name,
                'employee_email' => $loan->user?->email,
                'loan_date' => optional($loan->loan_date)->format('d/m/Y'),
                'return_plan' => 'Rencana kembali '.optional($loan->planned_return_date)->format('d/m/Y'),
            ]);

        // Ambil tiga pengembalian terbaru untuk pratinjau laporan pengembalian.
        $returnPreview = AssetReturn::query()
            ->with('asset')
            ->latest('returned_at')
            ->take(3)
            ->get()
            // Susun informasi pengembalian dalam format yang dibutuhkan tampilan.
            ->map(fn (AssetReturn $return) => [
                'asset_name' => $return->asset?->name,
                'asset_code' => $return->asset?->code,
                'returned_at' => optional($return->returned_at)->format('d/m/Y'),
                'status' => $return->status,
                'status_note' => $return->status_note,
                'report_number' => $return->report_number,
                'report_note' => $return->report_note,
            ]);

        // Kirim statistik, pratinjau, dan seluruh opsi filter ke halaman laporan.
        return view('admin.reports.index', [
            'summaryCards' => [
                ['label' => 'Total Aset', 'value' => $assetTotal],
                ['label' => 'Total Peminjaman', 'value' => $loanTotal],
                ['label' => 'Total Pengembalian', 'value' => $returnTotal],
                ['label' => 'Aset Tersedia', 'value' => $availableAssetTotal],
            ],
            'loanPreview' => $loanPreview,
            'loanTotal' => $loanTotal,
            'returnPreview' => $returnPreview,
            'returnTotal' => $returnTotal,
            'categories' => Category::query()->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
            'employees' => User::query()->where('role', 'pegawai')->orderBy('name')->get(),
            'assetConditions' => $this->assetConditions(),
            'assetStatuses' => $this->assetStatuses(),
            'loanStatuses' => $this->loanStatuses(),
        ]);
    }

    /**
     * Memvalidasi filter dan mengunduh laporan sesuai jenis yang diminta sebagai Excel.
     *
     * @param  Request  $request  Permintaan yang memuat nilai filter laporan.
     * @param  string  $type  Jenis laporan yang akan dibuat.
     */
    public function download(
        Request $request,
        string $type,
        ReportExcelService $excelService,
    ): StreamedResponse {
        abort_unless(in_array($type, ['inventaris', 'peminjaman', 'pengembalian'], true), 404);

        // Gunakan aturan validasi yang sesuai dengan jenis laporan.
        $filters = $this->validatedFilters($request, $type);

        if ($type === 'inventaris') {
            $location = isset($filters['location_id'])
                ? Location::query()->find($filters['location_id'])
                : null;
            $workbook = $excelService->inventoryWorkbook(
                $this->inventoryAssets($filters),
                $location,
            );
            $filename = 'data-aset.xlsx';
        } else {
            // Laporan transaksi memakai tabel Excel dengan filter yang sama seperti sebelumnya.
            $report = $this->reportPayload($type, $filters);
            abort_if($report === null, 404);

            $workbook = $excelService->tableWorkbook(
                title: $report['title'],
                sheetTitle: $report['sheetTitle'],
                columns: $report['columns'],
                rows: $report['rows'],
                filterSummary: $report['filterSummary'],
            );
            $filename = $report['filename'];
        }

        return $this->excelDownload($workbook, $filename);
    }

    /**
     * Menyusun judul, nama berkas, kolom, baris, dan ringkasan filter sebuah laporan.
     *
     * @param  string  $type  Jenis laporan.
     * @param  array<string, mixed>  $filters  Filter laporan yang telah divalidasi.
     * @return array<string, mixed>|null
     */
    private function reportPayload(string $type, array $filters): ?array
    {
        // Pilih konfigurasi dan sumber data laporan berdasarkan slug jenis laporan.
        return match ($type) {
            'peminjaman' => [
                'title' => 'Laporan Peminjaman Aset',
                'sheetTitle' => 'Peminjaman',
                'filename' => 'laporan-peminjaman-aset.xlsx',
                'columns' => ['No', 'Kode Aset', 'Nama Aset', 'Pegawai', 'Email', 'Tanggal Pinjam', 'Rencana Kembali', 'Status', 'Catatan'],
                'rows' => $this->loanRows($filters),
                'filterSummary' => $this->loanFilterSummary($filters),
            ],
            'pengembalian' => [
                'title' => 'Laporan Pengembalian Aset',
                'sheetTitle' => 'Pengembalian',
                'filename' => 'laporan-pengembalian-aset.xlsx',
                'columns' => ['No', 'No. BA', 'Kode Aset', 'Nama Aset', 'Pegawai', 'Tanggal Kembali', 'Kondisi', 'Status', 'Catatan'],
                'rows' => $this->returnRows($filters),
                'filterSummary' => $this->returnFilterSummary($filters),
            ],
            default => null,
        };
    }

    /**
     * Mengambil aset inventaris beserta relasi yang dibutuhkan workbook KIR.
     *
     * @param  array<string, mixed>  $filters  Filter inventaris yang telah divalidasi.
     * @return Collection<int, Asset>
     */
    private function inventoryAssets(array $filters): Collection
    {
        return Asset::query()
            // Muat kategori dan lokasi sekaligus agar tidak terjadi query berulang saat pemetaan.
            ->with(['category', 'location'])
            // Terapkan hanya filter yang benar-benar dikirim oleh pengguna.
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['location_id'] ?? null, fn ($query, $locationId) => $query->where('location_id', $locationId))
            ->when($filters['condition'] ?? null, fn ($query, $condition) => $query->where('condition', $condition))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['year'] ?? null, function ($query, $year) {
                // Prioritaskan tahun perolehan, lalu gunakan tahun tanggal perolehan sebagai cadangan.
                $query->where(function ($query) use ($year) {
                    $query->where('acquisition_year', $year)
                        ->orWhere(function ($query) use ($year) {
                            $query->whereNull('acquisition_year')
                                ->whereYear('acquired_at', $year);
                        });
                });
            })
            ->orderBy('location_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * Mengambil dan memformat baris laporan peminjaman berdasarkan filter.
     *
     * @param  array<string, mixed>  $filters  Filter peminjaman yang telah divalidasi.
     * @return Collection<int, array<int, mixed>>
     */
    private function loanRows(array $filters): Collection
    {
        return Loan::query()
            // Muat data aset dan pegawai yang terkait dengan setiap peminjaman.
            ->with(['asset', 'user'])
            // Batasi data berdasarkan pegawai, status, dan rentang tanggal bila tersedia.
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['year'] ?? null, fn ($query, $year) => $query->whereYear('loan_date', $year))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('loan_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('loan_date', '<=', $date))
            ->latest('loan_date')
            ->get()
            // Susun hasil terbaru menjadi baris tabel dan batasi panjang catatan.
            ->map(fn (Loan $loan, int $index) => [
                $index + 1,
                $loan->asset?->code ?? '-',
                $loan->asset?->name ?? '-',
                $loan->user?->name ?? '-',
                $loan->user?->email ?? '-',
                optional($loan->loan_date)->format('d/m/Y') ?? '-',
                optional($loan->planned_return_date)->format('d/m/Y') ?? '-',
                $loan->status,
                Str::limit($loan->status_note ?: '-', 80),
            ]);
    }

    /**
     * Mengambil dan memformat baris laporan pengembalian berdasarkan filter.
     *
     * @param  array<string, mixed>  $filters  Filter pengembalian yang telah divalidasi.
     * @return Collection<int, array<int, mixed>>
     */
    private function returnRows(array $filters): Collection
    {
        return AssetReturn::query()
            // Muat data aset dan pegawai yang terkait dengan setiap pengembalian.
            ->with(['asset', 'user'])
            // Terapkan filter pegawai, kondisi, dan rentang tanggal secara opsional.
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['condition'] ?? null, fn ($query, $condition) => $query->where('condition', $condition))
            ->when($filters['year'] ?? null, fn ($query, $year) => $query->whereYear('returned_at', $year))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('returned_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('returned_at', '<=', $date))
            ->latest('returned_at')
            ->get()
            // Ubah hasil menjadi baris tabel dan gunakan catatan berita acara sebagai cadangan.
            ->map(fn (AssetReturn $return, int $index) => [
                $index + 1,
                $return->report_number,
                $return->asset?->code ?? '-',
                $return->asset?->name ?? '-',
                $return->user?->name ?? '-',
                optional($return->returned_at)->format('d/m/Y') ?? '-',
                $return->condition,
                $return->status,
                Str::limit($return->status_note ?: $return->report_note ?: '-', 80),
            ]);
    }

    /**
     * Memvalidasi lalu membersihkan filter sesuai jenis laporan.
     *
     * @param  Request  $request  Permintaan yang memuat filter.
     * @param  string  $type  Jenis laporan.
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request, string $type): array
    {
        // Setiap jenis laporan memiliki kolom dan batas nilai filter yang berbeda.
        $rules = match ($type) {
            'inventaris' => [
                'category_id' => ['nullable', 'exists:categories,id'],
                'location_id' => ['nullable', 'exists:locations,id'],
                'condition' => ['nullable', Rule::in($this->assetConditions())],
                'status' => ['nullable', Rule::in($this->assetStatuses())],
                'year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 1)],
            ],
            'peminjaman' => [
                'user_id' => ['nullable', 'exists:users,id'],
                'status' => ['nullable', Rule::in($this->loanStatuses())],
                'year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 1)],
                'date_from' => ['nullable', 'date'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            ],
            'pengembalian' => [
                'user_id' => ['nullable', 'exists:users,id'],
                'condition' => ['nullable', Rule::in($this->assetConditions())],
                'year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 1)],
                'date_from' => ['nullable', 'date'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            ],
            default => [],
        };

        // Hilangkan nilai kosong agar query hanya menerima filter yang aktif.
        return collect($request->validate($rules))
            ->filter(fn ($value) => filled($value))
            ->all();
    }

    /**
     * Mengubah filter peminjaman aktif menjadi teks ringkas untuk kepala laporan.
     *
     * @param  array<string, mixed>  $filters  Filter peminjaman aktif.
     * @return array<int, string>
     */
    private function loanFilterSummary(array $filters): array
    {
        // Tampilkan hanya filter yang dipilih dan format tanggal agar mudah dibaca.
        return array_values(array_filter([
            isset($filters['user_id']) ? 'Pegawai: '.User::query()->find($filters['user_id'])?->name : null,
            isset($filters['status']) ? 'Status: '.$filters['status'] : null,
            isset($filters['year']) ? 'Tahun: '.$filters['year'] : null,
            isset($filters['date_from']) ? 'Dari: '.$this->formatDate($filters['date_from']) : null,
            isset($filters['date_to']) ? 'Sampai: '.$this->formatDate($filters['date_to']) : null,
        ]));
    }

    /**
     * Mengubah filter pengembalian aktif menjadi teks ringkas untuk kepala laporan.
     *
     * @param  array<string, mixed>  $filters  Filter pengembalian aktif.
     * @return array<int, string>
     */
    private function returnFilterSummary(array $filters): array
    {
        // Tampilkan hanya filter pengembalian yang sedang digunakan.
        return array_values(array_filter([
            isset($filters['user_id']) ? 'Pegawai: '.User::query()->find($filters['user_id'])?->name : null,
            isset($filters['condition']) ? 'Kondisi: '.$filters['condition'] : null,
            isset($filters['year']) ? 'Tahun: '.$filters['year'] : null,
            isset($filters['date_from']) ? 'Dari: '.$this->formatDate($filters['date_from']) : null,
            isset($filters['date_to']) ? 'Sampai: '.$this->formatDate($filters['date_to']) : null,
        ]));
    }

    /**
     * Mengalirkan workbook sebagai berkas XLSX tanpa menyimpannya di server.
     */
    private function excelDownload(Spreadsheet $workbook, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($workbook): void {
            try {
                (new Xlsx($workbook))->save('php://output');
            } finally {
                $workbook->disconnectWorksheets();
            }
        }, $filename, [
            'Content-Type' => ReportExcelService::MIME_TYPE,
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Mengubah tanggal menjadi format Indonesia yang digunakan pada laporan.
     */
    private function formatDate(string $date): string
    {
        return Carbon::parse($date)->format('d/m/Y');
    }

    /**
     * Menyediakan daftar kondisi aset yang sah untuk pilihan dan validasi filter.
     *
     * @return array<int, string>
     */
    private function assetConditions(): array
    {
        return ['Baik', 'Rusak Ringan', 'Rusak Berat'];
    }

    /**
     * Menyediakan daftar status aset yang sah untuk pilihan dan validasi filter.
     *
     * @return array<int, string>
     */
    private function assetStatuses(): array
    {
        return ['Tersedia', 'Dipinjam', 'Perbaikan'];
    }

    /**
     * Menyediakan daftar status peminjaman yang sah untuk pilihan dan validasi filter.
     *
     * @return array<int, string>
     */
    private function loanStatuses(): array
    {
        return ['Menunggu', 'Disetujui', 'Selesai', 'Ditolak'];
    }
}
