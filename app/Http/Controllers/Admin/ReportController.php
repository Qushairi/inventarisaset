<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetReturn;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Location;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index()
    {
        $assetTotal = Asset::query()->count();
        $loanTotal = Loan::query()->count();
        $returnTotal = AssetReturn::query()->count();
        $availableAssetTotal = Asset::query()->where('status', 'Tersedia')->count();

        $loanPreview = Loan::query()
            ->with(['asset', 'user'])
            ->latest('loan_date')
            ->take(3)
            ->get()
            ->map(fn (Loan $loan) => [
                'asset_name' => $loan->asset?->name,
                'asset_code' => $loan->asset?->code,
                'employee_name' => $loan->user?->name,
                'employee_email' => $loan->user?->email,
                'loan_date' => optional($loan->loan_date)->format('d/m/Y'),
                'return_plan' => 'Rencana kembali ' . optional($loan->planned_return_date)->format('d/m/Y'),
            ]);

        $returnPreview = AssetReturn::query()
            ->with('asset')
            ->latest('returned_at')
            ->take(3)
            ->get()
            ->map(fn (AssetReturn $return) => [
                'asset_name' => $return->asset?->name,
                'asset_code' => $return->asset?->code,
                'returned_at' => optional($return->returned_at)->format('d/m/Y'),
                'status' => $return->status,
                'status_note' => $return->status_note,
                'report_number' => $return->report_number,
                'report_note' => $return->report_note,
            ]);

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

    public function download(Request $request, string $type): Response
    {
        $filters = $this->validatedFilters($request, $type);
        $report = $this->reportPayload($type, $filters);

        abort_if($report === null, 404);

        return Pdf::loadView('admin.reports.pdf', $report)
            ->setPaper('a4', 'landscape')
            ->download($report['filename']);
    }

    private function reportPayload(string $type, array $filters): ?array
    {
        return match ($type) {
            'inventaris' => [
                'title' => 'Laporan Inventaris Aset',
                'filename' => 'laporan-inventaris-aset.pdf',
                'columns' => ['No', 'Kode', 'Nama Aset', 'Kategori', 'Lokasi', 'Kondisi', 'Status', 'Harga', 'Tahun Pembuatan'],
                'rows' => $this->inventoryRows($filters),
                'filterSummary' => $this->inventoryFilterSummary($filters),
            ],
            'peminjaman' => [
                'title' => 'Laporan Peminjaman Aset',
                'filename' => 'laporan-peminjaman-aset.pdf',
                'columns' => ['No', 'Kode Aset', 'Nama Aset', 'Pegawai', 'Email', 'Tanggal Pinjam', 'Rencana Kembali', 'Status', 'Catatan'],
                'rows' => $this->loanRows($filters),
                'filterSummary' => $this->loanFilterSummary($filters),
            ],
            'pengembalian' => [
                'title' => 'Laporan Pengembalian Aset',
                'filename' => 'laporan-pengembalian-aset.pdf',
                'columns' => ['No', 'No. BA', 'Kode Aset', 'Nama Aset', 'Pegawai', 'Tanggal Kembali', 'Kondisi', 'Status', 'Catatan'],
                'rows' => $this->returnRows($filters),
                'filterSummary' => $this->returnFilterSummary($filters),
            ],
            default => null,
        };
    }

    private function inventoryRows(array $filters): Collection
    {
        return Asset::query()
            ->with(['category', 'location'])
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['location_id'] ?? null, fn ($query, $locationId) => $query->where('location_id', $locationId))
            ->when($filters['condition'] ?? null, fn ($query, $condition) => $query->where('condition', $condition))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['year'] ?? null, function ($query, $year) {
                $query->where(function ($query) use ($year) {
                    $query->where('acquisition_year', $year)
                        ->orWhere(function ($query) use ($year) {
                            $query->whereNull('acquisition_year')
                                ->whereYear('acquired_at', $year);
                        });
                });
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Asset $asset, int $index) => [
                $index + 1,
                $asset->code,
                $asset->name,
                $asset->category?->name ?? '-',
                $asset->location?->name ?? '-',
                $asset->condition,
                $asset->status,
                'Rp ' . number_format((float) $asset->acquisition_price, 0, ',', '.'),
                $asset->acquisition_year ?: optional($asset->acquired_at)->format('Y') ?: '-',
            ]);
    }

    private function loanRows(array $filters): Collection
    {
        return Loan::query()
            ->with(['asset', 'user'])
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('loan_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('loan_date', '<=', $date))
            ->latest('loan_date')
            ->get()
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

    private function returnRows(array $filters): Collection
    {
        return AssetReturn::query()
            ->with(['asset', 'user'])
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['condition'] ?? null, fn ($query, $condition) => $query->where('condition', $condition))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('returned_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('returned_at', '<=', $date))
            ->latest('returned_at')
            ->get()
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

    private function validatedFilters(Request $request, string $type): array
    {
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
                'date_from' => ['nullable', 'date'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            ],
            'pengembalian' => [
                'user_id' => ['nullable', 'exists:users,id'],
                'condition' => ['nullable', Rule::in($this->assetConditions())],
                'date_from' => ['nullable', 'date'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            ],
            default => [],
        };

        return collect($request->validate($rules))
            ->filter(fn ($value) => filled($value))
            ->all();
    }

    private function inventoryFilterSummary(array $filters): array
    {
        return array_values(array_filter([
            isset($filters['category_id']) ? 'Kategori: '.Category::query()->find($filters['category_id'])?->name : null,
            isset($filters['location_id']) ? 'Lokasi: '.Location::query()->find($filters['location_id'])?->name : null,
            isset($filters['condition']) ? 'Kondisi: '.$filters['condition'] : null,
            isset($filters['status']) ? 'Status: '.$filters['status'] : null,
            isset($filters['year']) ? 'Tahun pembuatan: '.$filters['year'] : null,
        ]));
    }

    private function loanFilterSummary(array $filters): array
    {
        return array_values(array_filter([
            isset($filters['user_id']) ? 'Pegawai: '.User::query()->find($filters['user_id'])?->name : null,
            isset($filters['status']) ? 'Status: '.$filters['status'] : null,
            isset($filters['date_from']) ? 'Dari: '.$this->formatDate($filters['date_from']) : null,
            isset($filters['date_to']) ? 'Sampai: '.$this->formatDate($filters['date_to']) : null,
        ]));
    }

    private function returnFilterSummary(array $filters): array
    {
        return array_values(array_filter([
            isset($filters['user_id']) ? 'Pegawai: '.User::query()->find($filters['user_id'])?->name : null,
            isset($filters['condition']) ? 'Kondisi: '.$filters['condition'] : null,
            isset($filters['date_from']) ? 'Dari: '.$this->formatDate($filters['date_from']) : null,
            isset($filters['date_to']) ? 'Sampai: '.$this->formatDate($filters['date_to']) : null,
        ]));
    }

    private function formatDate(string $date): string
    {
        return \Illuminate\Support\Carbon::parse($date)->format('d/m/Y');
    }

    private function assetConditions(): array
    {
        return ['Baik', 'Rusak Ringan', 'Rusak Berat'];
    }

    private function assetStatuses(): array
    {
        return ['Tersedia', 'Dipinjam', 'Perbaikan'];
    }

    private function loanStatuses(): array
    {
        return ['Menunggu', 'Disetujui', 'Selesai', 'Ditolak'];
    }
}
