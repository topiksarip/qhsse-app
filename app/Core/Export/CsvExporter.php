<?php

namespace App\Core\Export;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * Stream an Eloquent query as CSV without creating public files.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string, callable|\Stringable|string>  $columns
     */
    public function stream(Builder $query, array $columns, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $columns): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, array_keys($columns));

            $query->chunk(200, function ($items) use ($handle, $columns): void {
                foreach ($items as $item) {
                    $row = [];

                    foreach ($columns as $value) {
                        $row[] = is_callable($value) ? $value($item) : data_get($item, (string) $value);
                    }

                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
