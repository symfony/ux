/**
 * Tests for formatting utilities (format.ts)
 */

import { describe, it, expect } from 'vitest';
import { formatSize, formatSpeed, formatEta } from '../../src/format';

describe('formatSize', () => {
    it('formats bytes', () => {
        expect(formatSize(0)).toBe('0 B');
        expect(formatSize(500)).toBe('500 B');
        expect(formatSize(1023)).toBe('1023 B');
    });

    it('formats kilobytes', () => {
        expect(formatSize(1024)).toBe('1.0 KB');
        expect(formatSize(1536)).toBe('1.5 KB');
        expect(formatSize(10240)).toBe('10.0 KB');
    });

    it('formats megabytes', () => {
        expect(formatSize(1048576)).toBe('1.0 MB');
        expect(formatSize(5242880)).toBe('5.0 MB');
        expect(formatSize(1572864)).toBe('1.5 MB');
    });

    it('formats gigabytes', () => {
        expect(formatSize(1073741824)).toBe('1.0 GB');
        expect(formatSize(2147483648)).toBe('2.0 GB');
    });

    it('caps at gigabytes', () => {
        // Even very large values should show as GB
        expect(formatSize(1099511627776)).toBe('1024.0 GB');
    });
});

describe('formatSpeed', () => {
    it('formats speed as size per second', () => {
        expect(formatSpeed(1024)).toBe('1.0 KB/s');
        expect(formatSpeed(1048576)).toBe('1.0 MB/s');
        expect(formatSpeed(500)).toBe('500 B/s');
    });
});

describe('formatEta', () => {
    it('returns empty string for zero or negative', () => {
        expect(formatEta(0)).toBe('');
        expect(formatEta(-1000)).toBe('');
    });

    it('returns empty string for Infinity', () => {
        expect(formatEta(Infinity)).toBe('');
    });

    it('formats seconds', () => {
        expect(formatEta(1000)).toBe('1s');
        expect(formatEta(5000)).toBe('5s');
        expect(formatEta(59000)).toBe('59s');
    });

    it('formats minutes and seconds', () => {
        expect(formatEta(60000)).toBe('1m');
        expect(formatEta(90000)).toBe('1m 30s');
        expect(formatEta(125000)).toBe('2m 5s');
    });

    it('formats hours and minutes', () => {
        expect(formatEta(3600000)).toBe('1h');
        expect(formatEta(5400000)).toBe('1h 30m');
        expect(formatEta(7200000)).toBe('2h');
    });

    it('rounds up partial seconds', () => {
        expect(formatEta(500)).toBe('1s');
        expect(formatEta(1500)).toBe('2s');
    });
});
