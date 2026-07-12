<?php

namespace App\Core\Query;

use InvalidArgumentException;

final class DatePeriodExpression
{
    public static function month(string $driver, string $column): string
    {
        return match ($driver) {
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => throw new InvalidArgumentException("Unsupported database driver: {$driver}"),
        };
    }
}
