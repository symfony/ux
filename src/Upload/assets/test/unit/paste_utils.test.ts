import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { extractFilesFromClipboard, renameAnonymousFile } from '../../src/paste_utils';
import { createPasteEvent } from './fixtures';

describe('extractFilesFromClipboard', () => {
    it('returns empty array when clipboardData is null', () => {
        const event = new ClipboardEvent('paste');
        expect(extractFilesFromClipboard(event)).toEqual([]);
    });

    it('extracts every file from clipboardData.files', () => {
        const files = [
            new File(['hello'], 'document.pdf', { type: 'application/pdf' }),
            new File(['b'], 'b.png', { type: 'image/png' }),
        ];
        const event = createPasteEvent(files);

        const result = extractFilesFromClipboard(event);
        expect(result.map((file) => file.name)).toEqual(['document.pdf', 'b.png']);
    });

    it('prefers clipboardData.files over clipboard items', () => {
        const listed = new File(['a'], 'listed.pdf', { type: 'application/pdf' });
        const itemOnly = new File(['b'], 'item-only.pdf', { type: 'application/pdf' });
        const event = {
            clipboardData: {
                files: [listed],
                items: [{ kind: 'file', type: 'application/pdf', getAsFile: () => itemOnly }],
            },
        } as unknown as ClipboardEvent;

        expect(extractFilesFromClipboard(event).map((file) => file.name)).toEqual(['listed.pdf']);
    });

    it('falls back to clipboard items when the file list is empty', () => {
        const screenshot = new File([new Uint8Array(10)], 'image.png', { type: 'image/png' });
        const event = {
            clipboardData: {
                files: [],
                items: [
                    { kind: 'string', type: 'text/plain', getAsFile: () => null },
                    { kind: 'file', type: 'image/png', getAsFile: () => screenshot },
                ],
            },
        } as unknown as ClipboardEvent;

        const result = extractFilesFromClipboard(event);

        expect(result).toHaveLength(1);
        expect(result[0].type).toBe('image/png');
        expect(result[0].name).toMatch(/^pasted-.*\.png$/);
    });

    it('renames anonymous files found in clipboardData.files', () => {
        const screenshot = new File([new Uint8Array(10)], 'image.png', { type: 'image/png' });
        const event = createPasteEvent([screenshot]);

        const result = extractFilesFromClipboard(event);
        expect(result).toHaveLength(1);
        expect(result[0].name).toMatch(/^pasted-.*\.png$/);
        expect(result[0].type).toBe('image/png');
    });

    it('returns empty array when clipboardData has no files or items', () => {
        expect(extractFilesFromClipboard(createPasteEvent([]))).toEqual([]);
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

    it('derives the extension from the MIME type and preserves type and lastModified', () => {
        const lastModified = Date.now() - 10000;
        const file = new File([new Uint8Array(4)], 'image.png', {
            type: 'image/webp',
            lastModified,
        });
        const result = renameAnonymousFile(file);
        expect(result.name).toMatch(/\.webp$/);
        expect(result.type).toBe('image/webp');
        expect(result.lastModified).toBe(lastModified);
    });

    it.each([
        { label: '"IMAGE.JPEG" (case-insensitive)', name: 'IMAGE.JPEG', type: 'image/jpeg', extension: 'jpeg' },
        { label: '"image.jpg"', name: 'image.jpg', type: 'image/jpeg', extension: 'jpeg' },
        { label: 'an svg+xml file', name: 'image.png', type: 'image/svg+xml', extension: 'svg' },
        { label: 'a file of unknown type', name: 'image.png', type: '', extension: 'bin' },
    ])('renames $label to a .$extension file', ({ name, type, extension }) => {
        const result = renameAnonymousFile(new File([new Uint8Array(4)], name, { type }));
        expect(result.name).toMatch(new RegExp(`^pasted-.*\\.${extension}$`));
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
