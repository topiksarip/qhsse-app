<?php

namespace App\Core\Export;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * Stream an Eloquent query as CSV without creating public files.
     *
     * @param  Builder<Model>  $query
     * @param  array<string, callable|\Stringable|string>  $columns
     */
    public function stream(Builder $query, array $columns, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $columns): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_keys($columns));

            $query->chunk(200, function ($items) use ($handle, $columns): void {
                foreach ($items as $item) {
                    $row = [];

                    foreach ($columns as $value) {
                        $cell = is_callable($value) ? $value($item) : data_get($item, (string) $value);
                        $row[] = $this->neutralizeFormula($cell);
                    }

                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function neutralizeFormula(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }
}
