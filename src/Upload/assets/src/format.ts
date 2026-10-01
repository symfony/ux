/**
 * Formatting utilities for file sizes, speeds, and time estimates.
 */

/**
 * Format a byte count into a human-readable string (e.g. "2.5 MB").
 */
export function formatSize(bytes: number): string {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }

    return `${size.toFixed(unitIndex > 0 ? 1 : 0)} ${units[unitIndex]}`;
}

/**
 * Format a speed in bytes/second (e.g. "1.2 MB/s").
 */
export function formatSpeed(bytesPerSecond: number): string {
    return `${formatSize(bytesPerSecond)}/s`;
}

/**
 * Format remaining time in milliseconds to a human-readable ETA.
 */
export function formatEta(remainingMs: number): string {
    const seconds = Math.ceil(remainingMs / 1000);
    if (seconds < 1 || !isFinite(seconds)) return '';
    if (seconds < 60) return `${seconds}s`;
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    if (minutes < 60) return secs > 0 ? `${minutes}m ${secs}s` : `${minutes}m`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
}
