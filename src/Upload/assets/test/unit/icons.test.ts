/**
 * Tests for icon utilities (icons.ts)
 */

import { describe, it, expect } from 'vitest';
import { getFileIconCategory } from '../../src/icons';

describe('getFileIconCategory', () => {
    // PDF
    it('detects PDF by mime type', () => {
        expect(getFileIconCategory('application/pdf', 'doc.pdf')).toBe('pdf');
    });

    it('detects PDF by extension', () => {
        expect(getFileIconCategory('application/octet-stream', 'report.pdf')).toBe('pdf');
    });

    // Spreadsheet
    it('detects Excel by mime type', () => {
        expect(getFileIconCategory('application/vnd.ms-excel', 'data.xls')).toBe('spreadsheet');
    });

    it('detects spreadsheet by extension', () => {
        expect(getFileIconCategory('application/octet-stream', 'data.xlsx')).toBe('spreadsheet');
        expect(getFileIconCategory('application/octet-stream', 'data.csv')).toBe('spreadsheet');
        expect(getFileIconCategory('application/octet-stream', 'data.ods')).toBe('spreadsheet');
    });

    it('detects OpenDocument spreadsheet mime type', () => {
        expect(getFileIconCategory('application/vnd.oasis.opendocument.spreadsheet', 'data.ods')).toBe('spreadsheet');
    });

    // Document
    it('detects Word by mime type', () => {
        expect(getFileIconCategory('application/msword', 'doc.doc')).toBe('document');
    });

    it('detects text files', () => {
        expect(getFileIconCategory('text/plain', 'readme.txt')).toBe('document');
        expect(getFileIconCategory('text/html', 'page.html')).toBe('document');
    });

    it('detects document by extension', () => {
        expect(getFileIconCategory('application/octet-stream', 'report.docx')).toBe('document');
        expect(getFileIconCategory('application/octet-stream', 'essay.odt')).toBe('document');
        expect(getFileIconCategory('application/octet-stream', 'note.rtf')).toBe('document');
    });

    // Archive
    it('detects ZIP by mime type', () => {
        expect(getFileIconCategory('application/zip', 'files.zip')).toBe('archive');
        expect(getFileIconCategory('application/gzip', 'files.tar.gz')).toBe('archive');
    });

    it('detects archive by extension', () => {
        expect(getFileIconCategory('application/octet-stream', 'files.rar')).toBe('archive');
        expect(getFileIconCategory('application/octet-stream', 'files.7z')).toBe('archive');
        expect(getFileIconCategory('application/octet-stream', 'files.tar')).toBe('archive');
    });

    // Video
    it('detects video by mime type', () => {
        expect(getFileIconCategory('video/mp4', 'clip.mp4')).toBe('video');
        expect(getFileIconCategory('video/webm', 'clip.webm')).toBe('video');
    });

    it('detects video by extension', () => {
        expect(getFileIconCategory('application/octet-stream', 'clip.mkv')).toBe('video');
        expect(getFileIconCategory('application/octet-stream', 'clip.avi')).toBe('video');
        expect(getFileIconCategory('application/octet-stream', 'clip.mov')).toBe('video');
    });

    // Audio
    it('detects audio by mime type', () => {
        expect(getFileIconCategory('audio/mpeg', 'song.mp3')).toBe('audio');
        expect(getFileIconCategory('audio/ogg', 'song.ogg')).toBe('audio');
    });

    it('detects audio by extension', () => {
        expect(getFileIconCategory('application/octet-stream', 'song.wav')).toBe('audio');
        expect(getFileIconCategory('application/octet-stream', 'song.flac')).toBe('audio');
        expect(getFileIconCategory('application/octet-stream', 'song.aac')).toBe('audio');
    });

    // Default
    it('returns default for unknown types', () => {
        expect(getFileIconCategory('application/octet-stream', 'file.bin')).toBe('default');
        expect(getFileIconCategory('application/octet-stream', 'data.dat')).toBe('default');
    });

    // Case insensitivity
    it('handles uppercase MIME types', () => {
        expect(getFileIconCategory('APPLICATION/PDF', 'doc.pdf')).toBe('pdf');
        expect(getFileIconCategory('VIDEO/MP4', 'clip.mp4')).toBe('video');
    });

    it('handles no file extension', () => {
        expect(getFileIconCategory('application/pdf', 'Makefile')).toBe('pdf');
        expect(getFileIconCategory('application/octet-stream', 'Makefile')).toBe('default');
    });
});
