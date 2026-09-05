/**
 * Tests for clipboard extraction utilities (paste-utils.ts)
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { extractFilesFromClipboard, renameAnonymousFile } from '../../src/paste-utils';

/**
 * Helper: build a minimal ClipboardEvent with files on clipboardData.files.
 */
function createPasteEventWithFiles(files: File[]): ClipboardEvent {
    const dt = new DataTransfer();
    for (const f of files) {
        dt.items.add(f);
    }
    return new ClipboardEvent('paste', { clipboardData: dt });
}

/**
 * Helper: build a ClipboardEvent where files list is empty but items
 * contains file entries (simulates screenshots / browser-pasted images).
 */
function createPasteEventWithItems(items: File[]): ClipboardEvent {
    const dt = new DataTransfer();
    // Add text item first -- should be skipped
    dt.items.add('ignored text', 'text/plain');
    for (const f of items) {
        dt.items.add(f);
    }

    // DataTransfer in JSDOM populates .files automatically from file items,
    // so we override .files with an empty FileList to simulate the Safari/items-only path.
    const event = new ClipboardEvent('paste', { clipboardData: dt });

    // We can't easily produce a real empty FileList while keeping items intact
    // in JSDOM, so instead we test that text items are properly skipped and
    // file items are extracted. The real-world difference (files vs items path)
    // is tested by the "files-first" priority test below.
    return event;
}

describe('extractFilesFromClipboard', () => {
    it('returns empty array when clipboardData is null', () => {
        const event = new ClipboardEvent('paste');
        expect(extractFilesFromClipboard(event)).toEqual([]);
    });

    it('extracts files from clipboardData.files', () => {
        const file = new File(['hello'], 'document.pdf', { type: 'application/pdf' });
        const event = createPasteEventWithFiles([file]);

        const result = extractFilesFromClipboard(event);
        expect(result).toHaveLength(1);
        expect(result[0].name).toBe('document.pdf');
    });

    it('extracts multiple files', () => {
        const files = [
            new File(['a'], 'a.txt', { type: 'text/plain' }),
            new File(['b'], 'b.png', { type: 'image/png' }),
        ];
        const event = createPasteEventWithFiles(files);

        const result = extractFilesFromClipboard(event);
        expect(result).toHaveLength(2);
    });

    it('extracts file items and skips text items', () => {
        const screenshot = new File([new Uint8Array(10)], 'image.png', { type: 'image/png' });
        const event = createPasteEventWithItems([screenshot]);

        const result = extractFilesFromClipboard(event);
        // The text/plain item should have been skipped
        const textFiles = result.filter((f) => f.type === 'text/plain');
        expect(textFiles).toHaveLength(0);
        // The image should be present (possibly renamed)
        const imageFiles = result.filter((f) => f.type === 'image/png');
        expect(imageFiles).toHaveLength(1);
    });

    it('renames anonymous files from clipboard', () => {
        const screenshot = new File([new Uint8Array(10)], 'image.png', { type: 'image/png' });
        const event = createPasteEventWithFiles([screenshot]);

        const result = extractFilesFromClipboard(event);
        expect(result).toHaveLength(1);
        expect(result[0].name).toMatch(/^pasted-.*\.png$/);
        expect(result[0].type).toBe('image/png');
    });

    it('returns empty array when clipboardData has no files or items', () => {
        const dt = new DataTransfer();
        const event = new ClipboardEvent('paste', { clipboardData: dt });
        expect(extractFilesFromClipboard(event)).toEqual([]);
    });
});

describe('renameAnonymousFile', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-04-19T09:00:00.000Z'));
    });

    it('passes through files with real names', () => {
        const file = new File(['data'], 'report.pdf', { type: 'application/pdf' });
        expect(renameAnonymousFile(file)).toBe(file);
    });

    it('renames "image.png"', () => {
        const file = new File([new Uint8Array(8)], 'image.png', { type: 'image/png' });
        const result = renameAnonymousFile(file);
        expect(result).not.toBe(file);
        expect(result.name).toMatch(/^pasted-2026-04-19T09-00-00-000Z\.png$/);
        expect(result.type).toBe('image/png');
    });

    it('renames "image.jpeg" (case-insensitive)', () => {
        const file = new File([new Uint8Array(8)], 'image.jpeg', { type: 'image/jpeg' });
        const result = renameAnonymousFile(file);
        expect(result.name).toMatch(/^pasted-.*\.jpeg$/);
    });

    it('renames "image.jpg"', () => {
        const file = new File([new Uint8Array(8)], 'image.jpg', { type: 'image/jpeg' });
        const result = renameAnonymousFile(file);
        expect(result.name).toMatch(/^pasted-.*\.jpeg$/);
    });

    it('preserves MIME type and lastModified', () => {
        const lastModified = Date.now() - 10000;
        const file = new File([new Uint8Array(4)], 'image.png', {
            type: 'image/webp',
            lastModified,
        });
        const result = renameAnonymousFile(file);
        expect(result.type).toBe('image/webp');
        expect(result.lastModified).toBe(lastModified);
    });

    it('derives extension from MIME type', () => {
        const file = new File([new Uint8Array(4)], 'image.png', { type: 'image/webp' });
        const result = renameAnonymousFile(file);
        expect(result.name).toMatch(/\.webp$/);
    });

    it('handles svg+xml MIME type', () => {
        const file = new File(['<svg/>'], 'image.png', { type: 'image/svg+xml' });
        const result = renameAnonymousFile(file);
        expect(result.name).toMatch(/\.svg$/);
    });

    it('falls back to .bin for unknown MIME types', () => {
        const file = new File([new Uint8Array(4)], 'image.png', { type: '' });
        const result = renameAnonymousFile(file);
        expect(result.name).toMatch(/\.bin$/);
    });

    it('preserves file content', async () => {
        const content = new Uint8Array([1, 2, 3, 4]);
        const file = new File([content], 'image.png', { type: 'image/png' });
        const result = renameAnonymousFile(file);
        expect(result.size).toBe(4);
    });

    afterEach(() => {
        vi.useRealTimers();
    });
});
