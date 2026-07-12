<?php

use App\Core\Query\DatePeriodExpression;

it('builds a PostgreSQL month expression', function () {
    expect(DatePeriodExpression::month('pgsql', 'occurred_at'))
        ->toBe("to_char(occurred_at, 'YYYY-MM')");
});

it('builds a SQLite month expression', function () {
    expect(DatePeriodExpression::month('sqlite', 'occurred_at'))
        ->toBe("strftime('%Y-%m', occurred_at)");
});

it('rejects unsupported database drivers', function () {
    DatePeriodExpression::month('mysql', 'occurred_at');
})->throws(InvalidArgumentException::class, 'Unsupported database driver: mysql');
