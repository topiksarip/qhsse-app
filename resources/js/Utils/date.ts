export function formatDateOnly(value: string | null | undefined, fallback = '—'): string {
    if (!value) {
        return fallback;
    }

    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value);
    if (!match) {
        return value;
    }

    const [, year, month, day] = match;

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeZone: 'UTC',
    }).format(new Date(Date.UTC(Number(year), Number(month) - 1, Number(day))));
}
