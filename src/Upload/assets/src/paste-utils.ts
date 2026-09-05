/**
 * Clipboard extraction utilities for paste-to-upload.
 */

const ANONYMOUS_NAMES = new Set(['image.png', 'image.jpeg', 'image.jpg']);

/**
 * Extract File objects from a ClipboardEvent.
 *
 * Checks `clipboardData.files` first (OS file-manager copies), then falls
 * back to `clipboardData.items` (screenshots, browser-pasted images).
 */
export function extractFilesFromClipboard(event: ClipboardEvent): File[] {
    const data = event.clipboardData;
    if (!data) {
        return [];
    }

    if (data.files && data.files.length > 0) {
        return Array.from(data.files).map(renameAnonymousFile);
    }

    const files: File[] = [];
    for (const item of Array.from(data.items)) {
        if (item.kind !== 'file') {
            continue;
        }
        const file = item.getAsFile();
        if (file) {
            files.push(renameAnonymousFile(file));
        }
    }

    return files;
}

/**
 * Rename files with generic/empty names (e.g. OS screenshots) to a
 * timestamped name so upload backends can distinguish them.
 *
 * Files with real names are returned unchanged.
 */
export function renameAnonymousFile(file: File): File {
    if (file.name && !ANONYMOUS_NAMES.has(file.name.toLowerCase())) {
        return file;
    }

    const ext = extensionFromMime(file.type);
    const stamp = new Date().toISOString().replace(/[:.]/g, '-');

    return new File([file], `pasted-${stamp}.${ext}`, {
        type: file.type,
        lastModified: file.lastModified,
    });
}

function extensionFromMime(mime: string): string {
    if (!mime) {
        return 'bin';
    }

    const sub = mime.split('/')[1] ?? '';
    // Strip parameters (e.g. "svg+xml" -> "svg")
    const base = sub.split('+')[0];

    return base || 'bin';
}
