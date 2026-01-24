<?php

declare(strict_types=1);

namespace AhmedArafat\AllInOne\Traits;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use RuntimeException;
use Maatwebsite\Excel\Facades\Excel;

trait ExcelFormatter
{
    /**
     * Extract Excel data from an uploaded file in the request.
     *
     * @param string $fileKey
     * @param bool $likeExcelCells
     * @param int $sheetIndex
     * @param bool $skipEmptyCells
     * @return array
     */
    public function excelFileExtractor(
        string $fileKey,
        bool   $likeExcelCells = true,
        int    $sheetIndex = 0,
        bool   $skipEmptyCells = true
    ): array
    {
        $file = request()->file($fileKey);

        if (!$file instanceof UploadedFile) {
            throw new InvalidArgumentException(
                "File key '{$fileKey}' was not found or is not a valid uploaded file."
            );
        }

        return $this->extractFromUploadedFile(
            $file,
            $likeExcelCells,
            $sheetIndex,
            $skipEmptyCells
        );
    }

    /**
     * Extract Excel data from an UploadedFile instance.
     *
     * @param UploadedFile $file
     * @param bool $likeExcelCells
     * @param int $sheetIndex
     * @param bool $skipEmptyCells
     * @return array
     */
    protected function extractFromUploadedFile(
        UploadedFile $file,
        bool         $likeExcelCells = true,
        int          $sheetIndex = 0,
        bool         $skipEmptyCells = true
    ): array
    {
        $this->ensureExcelPackageIsInstalled();

        $sheets = Excel::toArray(null, $file);

        if (!isset($sheets[$sheetIndex])) {
            throw new InvalidArgumentException(
                "Sheet index {$sheetIndex} does not exist in the uploaded Excel file."
            );
        }

        $rows = $sheets[$sheetIndex];

        return $likeExcelCells
            ? $this->formatExcelRows($rows, $skipEmptyCells)
            : $rows;
    }

    /**
     * Convert rows to Excel-like cell addresses (A1, B2, AA10, ...).
     *
     * @param array $rows
     * @param bool $skipEmptyCells
     * @return array
     */
    protected function formatExcelRows(array $rows, bool $skipEmptyCells = true): array
    {
        $data = [];

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                if (
                    $skipEmptyCells &&
                    ($value === null || $value === '')
                ) {
                    continue;
                }

                $cellAddress = $this->getExcelCellAddress(
                    $rowIndex + 1,
                    $colIndex + 1
                );

                $data[$cellAddress] = $value;
            }
        }

        return $data;
    }

    /**
     * Convert row & column numbers to Excel cell address.
     *
     * @param int $row
     * @param int $col
     * @return string
     */
    private function getExcelCellAddress(int $row, int $col): string
    {
        $letters = '';

        while ($col > 0) {
            $remainder = ($col - 1) % 26;
            $letters = chr(65 + $remainder) . $letters;
            $col = intdiv($col - 1, 26);
        }

        return $letters . $row;
    }

    /**
     * Ensure maatwebsite/excel is installed.
     *
     * @return void
     */
    private function ensureExcelPackageIsInstalled(): void
    {
        if (!class_exists(Excel::class)) {
            throw new RuntimeException(
                'The "maatwebsite/excel" package is required to use ExcelFormatterTrait. ' .
                'Please install it via: composer require maatwebsite/excel'
            );
        }
    }
}
