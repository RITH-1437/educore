<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams rows as a CSV download (`docs/31_CSV-Exports-Report.md`).
 *
 * - UTF-8 with a byte-order mark, so spreadsheet apps show Khmer names
 *   correctly.
 * - Text cells that start with `=`, `+`, `-`, `@`, tab or carriage return are
 *   prefixed with `'`, so a cell can never run as a spreadsheet formula
 *   (CSV injection). Numbers are written as they are.
 */
class CsvExport
{
    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * @param  list<string>  $header
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, $header, escape: '');

            foreach ($rows as $row) {
                fputcsv($out, array_map(self::cell(...), $row), escape: '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** `{name}-{date}.csv`, e.g. `invoices-2026-10-02.csv`. */
    public static function filename(string $name): string
    {
        return $name.'-'.now()->toDateString().'.csv';
    }

    public static function cell(mixed $value): string|int|float
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $value = (string) $value;

        return $value !== '' && in_array($value[0], self::FORMULA_PREFIXES, true) ? "'".$value : $value;
    }
}
