<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\Location;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExcelService
{
    public const MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * @param  Collection<int, Asset>  $assets
     */
    public function inventoryWorkbook(
        Collection $assets,
        ?Location $emptyLocation = null,
        ?int $periodYear = null,
    ): Spreadsheet {
        // Seperti workbook KIR 2026 yang melaporkan posisi per 31 Desember 2025.
        $periodYear ??= now()->year - 1;
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Sistem Inventaris Aset')
            ->setCompany('Dinas Pendidikan Kabupaten Bengkalis')
            ->setTitle('Kartu Inventaris Ruangan')
            ->setSubject('Laporan inventaris aset dalam format KIR');

        /** @var Collection<int, Collection<int, Asset>> $groups */
        $groups = $assets
            ->groupBy(fn (Asset $asset): string => (string) ($asset->location_id ?: 'tanpa-lokasi'))
            ->sortBy(fn (Collection $group): string => mb_strtolower(
                (string) ($group->first()?->location?->code ?: $group->first()?->location?->name ?: 'zzzz'),
            ))
            ->values();

        if ($groups->isEmpty()) {
            $groups = collect([collect()]);
        }

        $usedTitles = [];

        foreach ($groups as $index => $group) {
            $location = $group->first()?->location ?? ($index === 0 ? $emptyLocation : null);
            $title = $this->uniqueSheetTitle($location?->name ?: 'Inventaris', $usedTitles);
            $worksheet = $index === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();

            $worksheet->setTitle($title);
            $this->renderKirWorksheet($worksheet, $group->values(), $location, $periodYear);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  array<int, string>  $columns
     * @param  Collection<int, array<int, mixed>>  $rows
     * @param  array<int, string>  $filterSummary
     */
    public function tableWorkbook(
        string $title,
        string $sheetTitle,
        array $columns,
        Collection $rows,
        array $filterSummary = [],
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Sistem Inventaris Aset')
            ->setCompany('Dinas Pendidikan Kabupaten Bengkalis')
            ->setTitle($title);

        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle($this->safeSheetTitle($sheetTitle));
        $worksheet->setShowGridlines(false);

        $lastColumn = Coordinate::stringFromColumnIndex(count($columns));
        $worksheet->mergeCells("A1:{$lastColumn}1");
        $worksheet->mergeCells("A2:{$lastColumn}2");
        $worksheet->mergeCells("A3:{$lastColumn}3");
        $worksheet->mergeCells("A4:{$lastColumn}4");
        $worksheet->setCellValue('A1', $title);
        $worksheet->setCellValue('A2', 'DINAS PENDIDIKAN KABUPATEN BENGKALIS');
        $worksheet->setCellValue('A3', 'Dicetak: '.now()->locale('id')->translatedFormat('d F Y H:i'));
        $worksheet->setCellValue('A4', 'Filter: '.($filterSummary === [] ? 'Semua data' : implode(' | ', $filterSummary)));

        $headerRow = 6;
        $dataStartRow = 7;

        foreach ($columns as $index => $column) {
            $coordinate = Coordinate::stringFromColumnIndex($index + 1).$headerRow;
            $worksheet->setCellValue($coordinate, $column);
            $worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))
                ->setWidth($this->columnWidth($column));
        }

        foreach ($rows->values() as $rowIndex => $values) {
            $excelRow = $dataStartRow + $rowIndex;

            foreach ($values as $columnIndex => $value) {
                $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1).$excelRow;

                if (is_int($value) || is_float($value)) {
                    $worksheet->setCellValue($coordinate, $value);
                } else {
                    $this->setText($worksheet, $coordinate, $this->displayValue($value));
                }
            }

            $worksheet->getRowDimension($excelRow)->setRowHeight(30);
        }

        $lastDataRow = max($headerRow, $dataStartRow + $rows->count() - 1);
        $worksheet->getStyle("A1:{$lastColumn}{$lastDataRow}")
            ->getFont()
            ->setName('Arial')
            ->setSize(10);
        $worksheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => '17365D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $worksheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $worksheet->getStyle("A3:{$lastColumn}4")->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '595959']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $worksheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => $this->thinBorder('2F5597')],
        ]);
        $worksheet->getRowDimension($headerRow)->setRowHeight(30);

        if ($rows->isNotEmpty()) {
            $worksheet->getStyle("A{$dataStartRow}:{$lastColumn}{$lastDataRow}")->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true,
                ],
                'borders' => ['allBorders' => $this->thinBorder('BFBFBF')],
            ]);
            $worksheet->getStyle("A{$dataStartRow}:A{$lastDataRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $worksheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastDataRow}");
        $worksheet->freezePane('A'.$dataStartRow);
        $worksheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow)
            ->setPrintArea("A1:{$lastColumn}{$lastDataRow}");
        $worksheet->getPageMargins()
            ->setTop(0.5)
            ->setRight(0.35)
            ->setBottom(0.5)
            ->setLeft(0.35);

        return $spreadsheet;
    }

    /**
     * @param  Collection<int, Asset>  $assets
     */
    private function renderKirWorksheet(
        Worksheet $worksheet,
        Collection $assets,
        ?Location $location,
        int $periodYear,
    ): void {
        $worksheet->setShowGridlines(false);
        $worksheet->getSheetView()->setZoomScale(75);

        foreach ([
            'A' => 0.86,
            'B' => 4,
            'C' => 34,
            'D' => 19,
            'E' => 13,
            'F' => 10,
            'G' => 14,
            'H' => 9,
            'I' => 16,
            'J' => 10,
            'K' => 16,
            'L' => 10,
            'M' => 10,
            'N' => 10,
            'O' => 11,
        ] as $column => $width) {
            $worksheet->getColumnDimension($column)->setWidth($width);
        }

        $this->addKirLogo($worksheet);

        foreach (['C2:O2', 'C3:O3', 'B15:B16', 'C15:C16', 'D15:D16', 'E15:E16', 'F15:H15', 'I15:K15', 'L15:N15', 'O15:O16'] as $range) {
            $worksheet->mergeCells($range);
        }

        $worksheet->setCellValue('C2', 'KARTU INVENTARIS RUANGAN');
        $worksheet->setCellValue('C3', 'Per 31 Desember '.$periodYear);

        $metadata = [
            6 => ['Provinsi', ': Riau'],
            7 => ['Kabupaten / Kota', ': Pemerintah Kabupaten Bengkalis'],
            8 => ['Bidang', ': Bidang Pendidikan dan Kebudayaan'],
            9 => ['Unit Organisasi', ': Dinas Pendidikan'],
            10 => ['Sub Unit Organisasi', ': Dinas Pendidikan'],
            11 => ['U P B', ': Dinas Pendidikan'],
            12 => ['Ruangan', ': '.($location?->name ?: 'Semua Lokasi')],
            14 => ['Nomor Kode Lokasi', ': '.($location?->code ?: '-')],
        ];

        foreach ($metadata as $row => [$label, $value]) {
            $worksheet->setCellValue('B'.$row, $label);
            $this->setText($worksheet, 'D'.$row, $value);
        }

        $worksheet->fromArray([
            ['No.', "Jenis Barang /\nNama Barang", 'Merk / Model', "No. Seri\nPabrik", 'Tahun  Pembuatan / Pembelian', null, null, 'Jumlah Barang / Register', null, null, 'Keadaan Barang', null, null, 'Keterangan'],
            [null, null, null, null, 'Ukuran', 'Bahan', 'Tahun', "No. Kode\nBarang", 'Jumlah Barang', "Harga Beli /\nPerolehan", "Baik\n\n(B)", "Kurang\nBaik\n(KB)", "Rusak\nBerat\n(RB)", null],
            range(1, 14),
        ], null, 'B15');

        $dataRow = 19;

        foreach ($assets as $index => $asset) {
            $row = $dataRow + $index;
            $worksheet->setCellValue('B'.$row, $index + 1);
            $this->setText($worksheet, 'C'.$row, $this->displayValue($asset->name));
            $this->setText($worksheet, 'D'.$row, $this->placeholder($asset->brand_model));
            $this->setText($worksheet, 'E'.$row, $this->placeholder($asset->serial_number));
            $this->setText($worksheet, 'F'.$row, $this->placeholder($asset->size));
            $this->setText($worksheet, 'G'.$row, $this->placeholder($asset->material));

            $year = $asset->acquisition_year ?: $asset->acquired_at?->year;
            if ($year !== null) {
                $worksheet->setCellValue('H'.$row, (int) $year);
            } else {
                $this->setText($worksheet, 'H'.$row, '-');
            }

            $this->setText($worksheet, 'I'.$row, $this->displayValue($asset->code));
            $worksheet->setCellValue('J'.$row, max(0, (int) $asset->quantity));
            $worksheet->setCellValue('K'.$row, (float) ($asset->acquisition_price ?? 0));

            foreach (['L', 'M', 'N'] as $column) {
                $this->setText($worksheet, $column.$row, '-');
            }

            $conditionColumn = match ($asset->condition) {
                'Rusak Ringan' => 'M',
                'Rusak Berat' => 'N',
                default => 'L',
            };
            $conditionLabel = match ($asset->condition) {
                'Rusak Ringan' => 'Kurang Baik',
                'Rusak Berat' => 'Rusak Berat',
                default => 'Baik',
            };
            $this->setText($worksheet, $conditionColumn.$row, $conditionLabel);
            $this->setText($worksheet, 'O'.$row, $this->placeholder($asset->note));
            $worksheet->getRowDimension($row)->setRowHeight(34);
        }

        $lastAssetRow = $dataRow + $assets->count() - 1;
        $totalRow = max($dataRow, $lastAssetRow + 1);
        $worksheet->mergeCells("B{$totalRow}:J{$totalRow}");
        $worksheet->setCellValue('B'.$totalRow, 'J U M L A H');
        $worksheet->setCellValue(
            'K'.$totalRow,
            $assets->isEmpty() ? 0 : "=SUM(K{$dataRow}:K{$lastAssetRow})",
        );

        $signatureTop = $totalRow + 2;
        $signatureNameRow = $signatureTop + 8;
        $signatureRankRow = $signatureTop + 9;
        $signatureNipRow = $signatureTop + 10;

        $worksheet->setCellValue('K'.$signatureTop, 'Bengkalis, 31 Desember '.$periodYear);
        $worksheet->setCellValue('C'.($signatureTop + 1), 'Mengetahui  :');
        $worksheet->setCellValue('C'.($signatureTop + 2), 'KEPALA DINAS PENDIDIKAN');
        $worksheet->setCellValue('C'.($signatureTop + 3), 'KABUPATEN BENGKALIS,');
        $worksheet->setCellValue('K'.($signatureTop + 2), 'PENGURUS BARANG');
        $worksheet->setCellValue('K'.($signatureTop + 3), 'DINAS PENDIDIKAN KABUPATEN BENGKALIS');
        $worksheet->setCellValue('C'.$signatureNameRow, 'HADI PRASETYO, ST., M.Si');
        $worksheet->setCellValue('C'.$signatureRankRow, 'Pembina Utama Muda');
        $worksheet->setCellValue('C'.$signatureNipRow, 'NIP. 19790520 200502 1 001');
        $worksheet->setCellValue('K'.$signatureNameRow, 'DODDY SANJAYA');
        $worksheet->setCellValue('K'.$signatureRankRow, 'Penata Muda (III/a)');
        $worksheet->setCellValue('K'.$signatureNipRow, 'NIP. 19771201 200901 1 005');

        foreach ([$signatureNameRow, $signatureRankRow, $signatureNipRow] as $row) {
            $worksheet->mergeCells("K{$row}:M{$row}");
        }

        $lastPrintRow = $signatureNipRow + 5;
        $worksheet->getStyle("B1:O{$lastPrintRow}")->getFont()->setName('Cambria')->setSize(9);
        $worksheet->getStyle('C2:O2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $worksheet->getStyle('C3:O3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $worksheet->getStyle('B6:D14')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $worksheet->getStyle("B15:O{$totalRow}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => $this->thinBorder('000000')],
        ]);
        $worksheet->getStyle('B15:O17')->applyFromArray([
            'font' => ['bold' => true, 'size' => 8],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $worksheet->getRowDimension(15)->setRowHeight(35);
        $worksheet->getRowDimension(16)->setRowHeight(42);
        $worksheet->getRowDimension(17)->setRowHeight(20);
        $worksheet->getRowDimension(18)->setRowHeight(8);

        if ($assets->isNotEmpty()) {
            $worksheet->getStyle("B{$dataRow}:B{$lastAssetRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $worksheet->getStyle("H{$dataRow}:N{$lastAssetRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $worksheet->getStyle("K{$dataRow}:K{$lastAssetRow}")
                ->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0;-');
        }

        $worksheet->getStyle("B{$totalRow}:O{$totalRow}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $worksheet->getStyle('K'.$totalRow)
            ->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0;-');
        $worksheet->getStyle("B{$signatureTop}:O{$signatureNipRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle("C{$signatureNameRow}:M{$signatureNipRow}")->getFont()->setBold(true);

        $worksheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_LEGAL)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(1)
            ->setRowsToRepeatAtTopByStartAndEnd(1, 18)
            ->setPrintArea("A1:O{$lastPrintRow}");
        $worksheet->getPageMargins()
            ->setTop(0.25)
            ->setRight(0.2)
            ->setBottom(0.25)
            ->setLeft(0.2)
            ->setHeader(0.1)
            ->setFooter(0.1);
        $worksheet->getPageSetup()->setHorizontalCentered(true);
    }

    private function addKirLogo(Worksheet $worksheet): void
    {
        $path = public_path('images/logo-bengkalis.png');

        if (! is_file($path)) {
            return;
        }

        $drawing = new Drawing;
        $drawing->setName('Logo Kabupaten Bengkalis');
        $drawing->setDescription('Logo Kabupaten Bengkalis');
        $drawing->setPath($path);
        $drawing->setCoordinates('B1');
        $drawing->setHeight(92);
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(2);
        $drawing->setWorksheet($worksheet);
    }

    private function setText(Worksheet $worksheet, string $coordinate, string $value): void
    {
        $worksheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
    }

    private function displayValue(mixed $value): string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return '-';
        }

        return (string) $value;
    }

    private function placeholder(?string $value): string
    {
        return filled($value) ? trim((string) $value) : '-';
    }

    /**
     * @param  array<string, true>  $usedTitles
     */
    private function uniqueSheetTitle(string $desiredTitle, array &$usedTitles): string
    {
        $base = $this->safeSheetTitle($desiredTitle);
        $title = $base;
        $sequence = 2;

        while (isset($usedTitles[mb_strtolower($title)])) {
            $suffix = ' ('.$sequence.')';
            $title = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
            $sequence++;
        }

        $usedTitles[mb_strtolower($title)] = true;

        return $title;
    }

    private function safeSheetTitle(string $title): string
    {
        $title = preg_replace('/[\\\\\/\?\*\[\]:]+/u', ' ', $title) ?: '';
        $title = preg_replace('/\s+/u', ' ', trim($title, " \t\n\r\0\x0B'")) ?: '';

        if ($title === '' || mb_strtolower($title) === 'history') {
            $title = $title === '' ? 'Laporan' : 'Laporan History';
        }

        return mb_substr($title, 0, 31);
    }

    private function columnWidth(string $heading): float
    {
        $heading = mb_strtolower($heading);

        return match (true) {
            $heading === 'no' => 7,
            str_contains($heading, 'catatan') => 38,
            str_contains($heading, 'nama aset') => 30,
            str_contains($heading, 'pegawai') => 25,
            str_contains($heading, 'email') => 30,
            str_contains($heading, 'tanggal'), str_contains($heading, 'kembali') => 17,
            str_contains($heading, 'kode'), str_contains($heading, 'no. ba') => 20,
            default => 18,
        };
    }

    /**
     * @return array{style: string, color: array{rgb: string}}
     */
    private function thinBorder(string $color): array
    {
        return [
            'style' => Border::BORDER_THIN,
            'color' => ['rgb' => $color],
        ];
    }
}
