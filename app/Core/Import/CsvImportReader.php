<?php

namespace App\Core\Import;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use SplFileObject;

class CsvImportReader
{
    /** @return array<int, array{row: int, data: array<string, string>}> */
    public function read(UploadedFile $file, array $requiredHeaders): array
    {
        $csv = new SplFileObject($file->getRealPath(), 'r');
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $csv->setCsvControl(',');

        $header = $csv->fgetcsv();
        if (! is_array($header)) {
            throw ValidationException::withMessages(['file' => 'CSV tidak memiliki header.']);
        }

        $header = array_map(function ($value): string {
            $value = trim((string) $value);

            return mb_strtolower(ltrim($value, "\xEF\xBB\xBF"));
        }, $header);

        if (in_array('', $header, true) || count($header) !== count(array_unique($header))) {
            throw ValidationException::withMessages(['file' => 'Header CSV kosong atau duplikat.']);
        }

        $missing = array_values(array_diff($requiredHeaders, $header));
        if ($missing !== []) {
            throw ValidationException::withMessages(['file' => 'Header wajib tidak ditemukan: '.implode(', ', $missing).'.']);
        }

        $rows = [];
        while (! $csv->eof()) {
            $values = $csv->fgetcsv();
            if (! is_array($values) || $values === [null] || collect($values)->every(fn ($value): bool => trim((string) $value) === '')) {
                continue;
            }
            if (count($values) !== count($header)) {
                throw ValidationException::withMessages(['file' => "Jumlah kolom tidak sesuai pada baris {$csv->key()}."]);
            }
            if (count($rows) >= 1000) {
                throw ValidationException::withMessages(['file' => 'Maksimal 1.000 data per import.']);
            }

            $data = array_combine($header, array_map(fn ($value): string => trim((string) $value), $values));
            if (! is_array($data) || collect($data)->contains(fn (string $value): bool => ! mb_check_encoding($value, 'UTF-8'))) {
                throw ValidationException::withMessages(['file' => "Encoding UTF-8 tidak valid pada baris {$csv->key()}."]);
            }
            $rows[] = ['row' => $csv->key() + 1, 'data' => $data];
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'CSV tidak memiliki data.']);
        }

        return $rows;
    }
}
