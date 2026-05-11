/**
 * Format a date string to a human-readable format.
 * e.g. "2025-01-15" → "Jan 15, 2025"
 */
export function formatDate(date: string | null | undefined): string {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

/**
 * Format a datetime string to a human-readable format.
 * e.g. "2025-01-15T10:30:00" → "Jan 15, 2025, 10:30 AM"
 */
export function formatDateTime(date: string | null | undefined): string {
    if (!date) return '—';
    return new Date(date).toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

