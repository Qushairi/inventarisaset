<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Location;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Membaca format Kartu Inventaris Ruangan (KIR) dan menyimpannya sebagai aset.
 */
class KirAssetImportService
{
    /** @var array<string, array{name: string, description: string}> */
    private const CATEGORY_CATALOG = [
        '01' => [
            'name' => 'Tanah',
            'description' => 'Golongan Tanah berdasarkan Tabel Kode Barang Daerah.',
        ],
        '02' => [
            'name' => 'Peralatan dan Mesin',
            'description' => 'Golongan Peralatan dan Mesin berdasarkan Tabel Kode Barang Daerah.',
        ],
        '03' => [
            'name' => 'Gedung dan Bangunan',
            'description' => 'Golongan Gedung dan Bangunan berdasarkan Tabel Kode Barang Daerah.',
        ],
        '04' => [
            'name' => 'Jalan, Irigasi dan Jaringan',
            'description' => 'Golongan Jalan, Irigasi dan Jaringan berdasarkan Tabel Kode Barang Daerah.',
        ],
        '05' => [
            'name' => 'Aset Tetap Lainnya',
            'description' => 'Golongan Aset Tetap Lainnya berdasarkan Tabel Kode Barang Daerah.',
        ],
        '06' => [
            'name' => 'Konstruksi Dalam Pengerjaan',
            'description' => 'Golongan Konstruksi Dalam Pengerjaan berdasarkan Tabel Kode Barang Daerah.',
        ],
    ];

    public function __construct(
        private readonly AssetStateService $assetStateService,
    ) {}

    /**
     * @return array{assets: int, quantity: int, locations: int, categories: int, created_locations: int, created_categories: int}
     */
    public function import(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([
                'import_file' => 'Berkas Excel tidak dapat dibaca. Silakan unggah ulang berkasnya.',
            ]);
        }

        $workbook = $this->read($path);

        return DB::transaction(function () use ($workbook): array {
            $assetCount = 0;
            $totalQuantity = 0;
            $createdLocations = 0;
            $createdCategories = 0;
            $locationIds = [];
            $categories = [];

            foreach ($workbook['sheets'] as $sheet) {
                $location = Location::query()->firstOrCreate(
                    ['code' => $sheet['location']['code']],
                    [
                        'name' => $sheet['location']['name'],
                        'address' => $sheet['location']['organization'],
                        'address_note' => 'Kode lokasi KIR: '.$sheet['location']['code'],
                        'description' => 'Ruangan hasil impor Kartu Inventaris Ruangan.',
                        'note' => 'Dibuat otomatis saat impor Excel KIR.',
                    ],
                );

                if ($location->wasRecentlyCreated) {
                    $createdLocations++;
                }

                $locationIds[$location->id] = true;

                foreach ($sheet['assets'] as $asset) {
                    $categoryDefinition = $this->categoryDefinition($asset['code']);
                    $categoryCode = $categoryDefinition['code'];

                    if (! isset($categories[$categoryCode])) {
                        $category = Category::query()->where('code', $categoryCode)->first();

                        if ($category !== null && $this->normalizedName($category->name) !== $this->normalizedName($categoryDefinition['name'])) {
                            throw ValidationException::withMessages([
                                'import_file' => "Kode kategori {$categoryCode} sudah digunakan oleh kategori \"{$category->name}\". Sesuaikan data kategori sebelum mengimpor.",
                            ]);
                        }

                        if ($category === null) {
                            $category = Category::query()->create([
                                'code' => $categoryCode,
                                'name' => $categoryDefinition['name'],
                                'description' => $categoryDefinition['description'],
                                'note' => 'Dibuat otomatis saat impor Excel KIR.',
                            ]);
                        }

                        if ($category->wasRecentlyCreated) {
                            $createdCategories++;
                        }

                        $categories[$categoryCode] = $category;
                    }

                    $this->assetStateService->addOrMergeAsset([
                        ...$asset,
                        'category_id' => $categories[$categoryCode]->id,
                        'location_id' => $location->id,
                    ]);

                    $assetCount++;
                    $totalQuantity += $asset['quantity'];
                }
            }

            return [
                'assets' => $assetCount,
                'quantity' => $totalQuantity,
                'locations' => count($locationIds),
                'categories' => count($categories),
                'created_locations' => $createdLocations,
                'created_categories' => $createdCategories,
            ];
        });
    }

    /**
     * @return array{code: string, name: string, description: string}
     */
    private function categoryDefinition(string $assetCode): array
    {
        if (preg_match('/^\d{1,2}(?:\.\d+){4}$/', trim($assetCode)) !== 1) {
            throw ValidationException::withMessages([
                'import_file' => "Kode barang {$assetCode} tidak mengikuti format kode barang daerah.",
            ]);
        }

        $firstSegment = explode('.', trim($assetCode))[0] ?? '';
        $categoryCode = str_pad((string) ((int) $firstSegment), 2, '0', STR_PAD_LEFT);
        $category = self::CATEGORY_CATALOG[$categoryCode] ?? null;

        if ($category === null) {
            throw ValidationException::withMessages([
                'import_file' => "Golongan kode barang {$categoryCode} dari kode {$assetCode} tidak ditemukan pada referensi kode barang.",
            ]);
        }

        return [
            'code' => $categoryCode,
            ...$category,
        ];
    }

    private function normalizedName(string $name): string
    {
        return Str::lower(Str::squish($name));
    }

    /**
     * Membaca workbook tanpa menulis ke database.
     *
     * @return array{sheets: array<int, array{title: string, location: array{name: string, code: string, organization: string}, assets: array<int, array<string, mixed>>}>, assets: int, quantity: int}
     */
    public function read(string $path): array
    {
        $spreadsheet = null;

        try {
            $reader = new Xlsx;

            if (! $reader->canRead($path)) {
                throw ValidationException::withMessages([
                    'import_file' => 'Berkas bukan workbook XLSX yang valid.',
                ]);
            }

            $reader
                ->setReadDataOnly(true)
                ->setReadEmptyCells(false)
                ->setIgnoreRowsWithNoCells(true);

            $spreadsheet = $reader->load($path);

            return $this->parseSpreadsheet($spreadsheet);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'import_file' => 'Workbook tidak dapat diproses. Pastikan formatnya .xlsx dan mengikuti susunan KIR.',
            ]);
        } finally {
            $spreadsheet?->disconnectWorksheets();
        }
    }

    /**
     * @return array{sheets: array<int, array{title: string, location: array{name: string, code: string, organization: string}, assets: array<int, array<string, mixed>>}>, assets: int, quantity: int}
     */
    private function parseSpreadsheet(Spreadsheet $spreadsheet): array
    {
        $sheets = [];
        $assetCount = 0;
        $totalQuantity = 0;

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $parsedSheet = $this->parseWorksheet($worksheet);

            if ($parsedSheet === null || $parsedSheet['assets'] === []) {
                continue;
            }

            $sheets[] = $parsedSheet;
            $assetCount += count($parsedSheet['assets']);
            $totalQuantity += array_sum(array_column($parsedSheet['assets'], 'quantity'));
        }

        if ($assetCount === 0) {
            throw ValidationException::withMessages([
                'import_file' => 'Tidak ditemukan baris aset pada workbook. Gunakan format KIR dengan kolom Nama, Kode Barang, dan Jumlah.',
            ]);
        }

        return [
            'sheets' => $sheets,
            'assets' => $assetCount,
            'quantity' => $totalQuantity,
        ];
    }

    /**
     * @return array{title: string, location: array{name: string, code: string, organization: string}, assets: array<int, array<string, mixed>>}|null
     */
    private function parseWorksheet(Worksheet $worksheet): ?array
    {
        $headerRow = $this->findHeaderRow($worksheet);

        if ($headerRow === null) {
            return null;
        }

        $sheetTitle = $this->cleanText($worksheet->getTitle()) ?? 'Sheet';
        $rawLocationName = $this->stripLabelPrefix($this->cellValue($worksheet, 'D12'));
        $rawLocationCode = $this->stripLabelPrefix($this->cellValue($worksheet, 'D14'));
        $rawOrganization = $this->stripLabelPrefix($this->cellValue($worksheet, 'D11'));
        $locationName = $this->locationName(
            $this->cellValue($worksheet, 'D12'),
            $sheetTitle,
        );
        $locationCode = $this->locationCode(
            $this->cellValue($worksheet, 'D14'),
            $locationName,
        );
        $organization = $rawOrganization ?? '';

        $assets = [];
        for ($row = $headerRow + 3; $row <= $worksheet->getHighestDataRow(); $row++) {
            $number = $this->nullableText($this->cellValue($worksheet, 'B'.$row));
            $name = $this->nullableText($this->cellValue($worksheet, 'C'.$row));
            $code = $this->nullableText($this->cellValue($worksheet, 'I'.$row));
            $quantityValue = $this->cellValue($worksheet, 'J'.$row);
            $hasQuantity = $this->isMeaningful($quantityValue);

            if ($this->isTotalLabel($number)) {
                break;
            }

            $isDetail = $name !== null && $code !== null && $hasQuantity;

            if (! $isDetail) {
                if ($name !== null && ($code !== null || $hasQuantity)) {
                    throw ValidationException::withMessages([
                        'import_file' => "Data pada sheet \"{$sheetTitle}\" baris {$row} belum memiliki Nama, Kode Barang, dan Jumlah secara lengkap.",
                    ]);
                }

                continue;
            }

            $quantity = $this->quantityValue($quantityValue, $sheetTitle, $row);
            $price = $this->moneyValue($this->cellValue($worksheet, 'K'.$row));

            $condition = $this->conditionValue($worksheet, $sheetTitle, $row);

            $assets[] = [
                'name' => Str::limit($this->cleanListPrefix($name), 255, ''),
                'code' => Str::limit($code, 50, ''),
                'brand_model' => $this->limitedCellText($worksheet, 'D'.$row),
                'note' => $this->limitedCellText($worksheet, 'O'.$row),
                'image_path' => null,
                'serial_number' => $this->limitedCellText($worksheet, 'E'.$row),
                'size' => $this->limitedCellText($worksheet, 'F'.$row),
                'material' => $this->limitedCellText($worksheet, 'G'.$row),
                'condition' => $condition,
                'status' => $condition === 'Rusak Berat' ? 'Perbaikan' : 'Tersedia',
                'quantity' => $quantity,
                'acquisition_price' => $price,
                'acquisition_year' => $this->yearValue($this->cellValue($worksheet, 'H'.$row)),
                'acquired_at' => null,
            ];
        }

        if ($assets !== [] && ($rawLocationName === null || $rawLocationCode === null || $rawOrganization === null)) {
            throw ValidationException::withMessages([
                'import_file' => "Sheet \"{$sheetTitle}\" memiliki data aset, tetapi identitas U P B, Ruangan, atau Nomor Kode Lokasi belum lengkap.",
            ]);
        }

        return [
            'title' => $sheetTitle,
            'location' => [
                'name' => Str::limit($locationName, 255, ''),
                'code' => Str::limit($locationCode, 255, ''),
                'organization' => Str::limit($organization, 255, ''),
            ],
            'assets' => $assets,
        ];
    }

    private function findHeaderRow(Worksheet $worksheet): ?int
    {
        $limit = min(50, $worksheet->getHighestDataRow());

        for ($row = 1; $row <= $limit; $row++) {
            $heading = Str::lower($this->cleanText($this->cellValue($worksheet, 'C'.$row)) ?? '');

            if (Str::contains($heading, ['jenis barang', 'nama barang'])) {
                return $row;
            }
        }

        return null;
    }

    private function cellValue(Worksheet $worksheet, string $coordinate): mixed
    {
        $cell = $worksheet->getCell($coordinate);

        try {
            return $cell->getCalculatedValue();
        } catch (Throwable) {
            return $this->fallbackCellValue($cell);
        }
    }

    private function fallbackCellValue(Cell $cell): mixed
    {
        $cachedValue = $cell->getOldCalculatedValue();

        if ($cachedValue !== null) {
            return $cachedValue;
        }

        $value = $cell->getValue();

        return is_string($value) && str_starts_with($value, '=') ? null : $value;
    }

    private function locationName(mixed $value, string $fallback): string
    {
        $name = $this->stripLabelPrefix($value) ?? $fallback;
        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) >= 3 && collect($parts)->every(fn (string $part): bool => mb_strlen($part) === 1)) {
            return implode('', $parts);
        }

        return $name;
    }

    private function locationCode(mixed $value, string $locationName): string
    {
        return $this->stripLabelPrefix($value)
            ?? 'KIR-'.Str::upper(substr(hash('sha256', Str::lower($locationName)), 0, 12));
    }

    private function stripLabelPrefix(mixed $value): ?string
    {
        $text = $this->nullableText($value);

        if ($text === null) {
            return null;
        }

        return $this->nullableText(ltrim($text, ": \t\n\r\0\x0B"));
    }

    private function limitedCellText(Worksheet $worksheet, string $coordinate): ?string
    {
        $value = $this->nullableText($this->cellValue($worksheet, $coordinate));

        return $value === null ? null : Str::limit($value, 255, '');
    }

    private function cleanListPrefix(string $value): string
    {
        return preg_replace('/^[a-z]\s*[.)]\s*/iu', '', $value) ?: $value;
    }

    private function conditionValue(Worksheet $worksheet, string $sheetTitle, int $row): string
    {
        $conditionColumns = collect(['L', 'M', 'N'])
            ->filter(fn (string $column): bool => $this->isMeaningful($this->cellValue($worksheet, $column.$row)))
            ->values();

        if ($conditionColumns->count() !== 1) {
            throw ValidationException::withMessages([
                'import_file' => "Keadaan barang pada sheet \"{$sheetTitle}\" baris {$row} harus diisi tepat pada salah satu kolom Baik, Kurang Baik, atau Rusak Berat.",
            ]);
        }

        if ($conditionColumns->first() === 'N') {
            return 'Rusak Berat';
        }

        if ($conditionColumns->first() === 'M') {
            return 'Rusak Ringan';
        }

        return 'Baik';
    }

    private function quantityValue(mixed $value, string $sheetTitle, int $row): int
    {
        if (is_numeric($value)) {
            $quantity = (int) round((float) $value);
        } else {
            preg_match('/\d+/', (string) $value, $matches);
            $quantity = isset($matches[0]) ? (int) $matches[0] : 0;
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'import_file' => "Jumlah barang pada sheet \"{$sheetTitle}\" baris {$row} tidak valid.",
            ]);
        }

        return $quantity;
    }

    private function yearValue(mixed $value): ?int
    {
        if (! $this->isMeaningful($value)) {
            return null;
        }

        preg_match('/(?:19|20)\d{2}/', (string) $value, $matches);
        $year = isset($matches[0]) ? (int) $matches[0] : 0;

        return $year >= 1900 && $year <= ((int) date('Y') + 1) ? $year : null;
    }

    private function moneyValue(mixed $value): float
    {
        if (! $this->isMeaningful($value)) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return max(0, round((float) $value, 2));
        }

        $number = preg_replace('/[^\d,.-]/u', '', (string) $value) ?? '';

        if (str_contains($number, ',') && str_contains($number, '.')) {
            $number = strrpos($number, ',') > strrpos($number, '.')
                ? str_replace(',', '.', str_replace('.', '', $number))
                : str_replace(',', '', $number);
        } elseif (substr_count($number, '.') > 1 || preg_match('/\.\d{3}$/', $number)) {
            $number = str_replace('.', '', $number);
        } elseif (str_contains($number, ',')) {
            $number = preg_match('/,\d{1,2}$/', $number)
                ? str_replace(',', '.', $number)
                : str_replace(',', '', $number);
        }

        return is_numeric($number) ? max(0, round((float) $number, 2)) : 0.0;
    }

    private function isTotalLabel(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return preg_replace('/\s+/u', '', Str::upper($value)) === 'JUMLAH';
    }

    private function isMeaningful(mixed $value): bool
    {
        return $this->nullableText($value) !== null;
    }

    private function nullableText(mixed $value): ?string
    {
        $text = $this->cleanText($value);

        if ($text === null || in_array(Str::lower($text), ['-', '–', '—', 'n/a', 'null'], true)) {
            return null;
        }

        return $text;
    }

    private function cleanText(mixed $value): ?string
    {
        if ($value === null || is_bool($value)) {
            return null;
        }

        $text = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $text === null || $text === '' ? null : $text;
    }
}
