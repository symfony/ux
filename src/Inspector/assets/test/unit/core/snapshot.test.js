import { afterEach, describe, expect, it, vi } from 'vitest';
import { safeValue, snapshotEvent } from '../../../src/core/snapshot';

const wideObject = (count) => Object.fromEntries(Array.from({ length: count }, (_, index) => [`k${index}`, index]));

describe('safeValue', () => {
    it('redacts sensitive keys, at the entry point and while descending', () => {
        expect(safeValue('private', 'csrfToken')).toBe('[redacted]');
        expect(safeValue({ nested: { password: 'private', user: 'ada' } })).toEqual({
            nested: { password: '[redacted]', user: 'ada' },
        });
    });

    it('reports accessors without invoking them, sensitive keys included', () => {
        const value = {
            get token() {
                throw new Error('must not run');
            },
            get label() {
                throw new Error('must not run');
            },
        };

        expect(safeValue(value)).toEqual({ token: '[accessor]', label: '[accessor]' });
    });

    it('marks repeated references as circular', () => {
        const value = { name: 'root' };
        value.self = value;
        value.again = value;

        expect(safeValue(value)).toEqual({ name: 'root', self: '[circular]', again: '[circular]' });
    });

    it('stops below the fourth level', () => {
        expect(safeValue({ a: { b: { c: { d: { e: 1 } } } } })).toEqual({ a: { b: { c: { d: '[max depth]' } } } });
    });

    it('caps arrays and object entries without marking what it dropped', () => {
        const list = safeValue(Array.from({ length: 25 }, (_, index) => index));
        expect(list).toHaveLength(20);
        expect(list.at(-1)).toBe(19);

        const bag = safeValue(wideObject(25));
        expect(Object.keys(bag)).toHaveLength(20);
        expect(bag._truncated).toBeUndefined();
    });

    it('caps strings without marking the cut', () => {
        expect(safeValue('x'.repeat(600))).toBe('x'.repeat(500));
    });

    it('passes primitives through untouched', () => {
        const fn = () => {};
        expect(safeValue(fn)).toBe(fn);
        expect(safeValue({ nothing: undefined, count: 2 })).toStrictEqual({ nothing: undefined, count: 2 });
    });

    it('reports values whose descriptors cannot be read', () => {
        const hostile = new Proxy(
            {},
            {
                ownKeys() {
                    throw new Error('denied');
                },
            }
        );

        expect(safeValue({ hostile })).toEqual({ hostile: '[unavailable]' });
    });

    it('keeps a parsed __proto__ key as an own entry', () => {
        const result = safeValue(JSON.parse('{"__proto__":{"polluted":true}}'));

        expect(Object.getPrototypeOf(result)).toBe(Object.prototype);
        expect(Object.keys(result)).toEqual(['__proto__']);
        expect(Object.getOwnPropertyDescriptor(result, '__proto__').value).toEqual({ polluted: true });
    });

    it('copies a wide graph in full: no shared node budget', () => {
        const wide = Object.fromEntries(
            Array.from({ length: 20 }, (_, index) => [`k${index}`, Array.from({ length: 20 }, () => ({}))])
        );
        const snapshot = safeValue(wide);

        expect(snapshot.k0).toHaveLength(20);
        expect(snapshot.k19).toHaveLength(20);
    });
});

describe('snapshotEvent', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('redacts sensitive keys, accessors included', () => {
        const value = {
            get token() {
                throw new Error('must not run');
            },
            password: 'private',
            nested: { secret: 'private', user: 'ada' },
        };

        expect(snapshotEvent(value)).toEqual({
            token: '[redacted]',
            password: '[redacted]',
            nested: { secret: '[redacted]', user: 'ada' },
        });
    });

    it('reports accessors without invoking them', () => {
        const value = {
            get label() {
                throw new Error('must not run');
            },
        };

        expect(snapshotEvent(value)).toEqual({ label: '[accessor]' });
    });

    it('marks repeated references as circular', () => {
        const value = { name: 'root' };
        value.self = value;

        expect(snapshotEvent(value)).toEqual({ name: 'root', self: '[circular]' });
    });

    it('stops one level above safeValue', () => {
        expect(snapshotEvent({ a: { b: { c: { d: 1 } } } })).toEqual({ a: { b: { c: '[truncated]' } } });
    });

    it('caps arrays and object entries and marks what it dropped', () => {
        const list = snapshotEvent(Array.from({ length: 25 }, (_, index) => index));
        expect(list).toHaveLength(21);
        expect(list.at(-1)).toBe('[truncated]');

        const bag = snapshotEvent(wideObject(25));
        expect(Object.keys(bag)).toHaveLength(21);
        expect(bag._truncated).toBe(true);
    });

    it('caps strings and marks the cut', () => {
        expect(snapshotEvent('x'.repeat(600))).toBe(`${'x'.repeat(500)}[truncated]`);
    });

    it('describes primitives it cannot serialise', () => {
        expect(snapshotEvent({ nothing: undefined, fn: () => {}, big: 7n })).toEqual({
            nothing: null,
            fn: '[function]',
            big: '7n',
        });
    });

    it('reports values whose descriptors cannot be read', () => {
        const hostile = new Proxy(
            {},
            {
                ownKeys() {
                    throw new Error('denied');
                },
            }
        );

        expect(snapshotEvent({ hostile })).toEqual({ hostile: '[unavailable]' });
    });

    it('keeps a parsed __proto__ key as an own entry', () => {
        const result = snapshotEvent(JSON.parse('{"__proto__":{"polluted":true}}'));

        expect(Object.getPrototypeOf(result)).toBe(Object.prototype);
        expect(Object.keys(result)).toEqual(['__proto__']);
        expect(Object.getOwnPropertyDescriptor(result, '__proto__').value).toEqual({ polluted: true });
    });

    it('stops descending once the shared node budget runs out', () => {
        const wide = Object.fromEntries(
            Array.from({ length: 20 }, (_, index) => [`k${index}`, Array.from({ length: 20 }, () => ({}))])
        );
        const snapshot = snapshotEvent(wide);

        expect(snapshot.k0).toHaveLength(20);
        expect(snapshot.k19).toBe('[truncated]');
    });

    it('keeps copying when a platform constructor is missing', () => {
        vi.stubGlobal('Request', undefined);
        vi.stubGlobal('URL', undefined);

        // Without the globalThis guards, instanceof throws and blanks the whole value.
        expect(snapshotEvent({ payload: { ok: true } })).toEqual({ payload: { ok: true } });
    });
});
