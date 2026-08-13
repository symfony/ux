/**
 * Determine the semantic file category exposed to the Twig-owned icon.
 */
export function getFileIconCategory(mimeType: string, filename: string): string {
    const type = mimeType.toLowerCase();
    const ext = filename.split('.').pop()?.toLowerCase() ?? '';

    if (type === 'application/pdf' || ext === 'pdf') {
        return 'pdf';
    }
    if (
        type === 'application/vnd.ms-excel' ||
        type.includes('spreadsheet') ||
        ['xls', 'xlsx', 'ods', 'csv'].includes(ext)
    ) {
        return 'spreadsheet';
    }
    if (
        type === 'application/msword' ||
        type.includes('wordprocessing') ||
        type.startsWith('text/') ||
        ['doc', 'docx', 'odt', 'rtf'].includes(ext)
    ) {
        return 'document';
    }
    if (
        type === 'application/zip' ||
        type === 'application/gzip' ||
        type.startsWith('application/x-rar') ||
        type.startsWith('application/x-7z') ||
        ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'].includes(ext)
    ) {
        return 'archive';
    }
    if (type.startsWith('video/') || ['mp4', 'avi', 'mov', 'mkv', 'webm'].includes(ext)) {
        return 'video';
    }
    if (type.startsWith('audio/') || ['mp3', 'wav', 'ogg', 'flac', 'aac'].includes(ext)) {
        return 'audio';
    }

    return 'default';
}
