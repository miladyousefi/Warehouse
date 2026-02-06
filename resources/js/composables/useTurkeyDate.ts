export function formatTurkeyDate(dateStr: string | null | undefined): string {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('tr-TR', {
        timeZone: 'Europe/Istanbul',
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

export function nowTurkeyDatetimeLocal(): string {
    return new Date()
        .toLocaleString('sv-SE', { timeZone: 'Europe/Istanbul' })
        .slice(0, 16)
        .replace(' ', 'T');
}

// Backwards-compatible alias expected by some components
export const nowTurkeyDateLocal = nowTurkeyDatetimeLocal;
