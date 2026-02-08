<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExportsCsv
{
    /**
     * Stream a CSV download from the given records.
     *
     * @param  iterable  $records   Eloquent collection / array of rows
     * @param  array     $headers   Column headers for the first CSV row
     * @param  callable  $rowMapper fn($record): array — maps each record to a CSV row
     * @param  string    $filename  Download filename (e.g. "clientes.csv")
     */
    protected function streamCsv(iterable $records, array $headers, callable $rowMapper, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($records, $headers, $rowMapper) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers, ';');
            foreach ($records as $record) {
                fputcsv($handle, $rowMapper($record), ';');
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
