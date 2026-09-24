import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ResumeStore } from '../../src/resume_store';
import { FakeIndexedDB } from './indexeddb_fake';

describe('ResumeStore', () => {
    const record = (fingerprint: string, expiresAt = Date.now() + 60_000) => ({
        fingerprint,
        token: `token-${fingerprint}`,
        expiresAt,
    });

    describe('with IndexedDB', () => {
        let indexedDB: FakeIndexedDB;

        beforeEach(() => {
            indexedDB = new FakeIndexedDB();
            vi.stubGlobal('indexedDB', indexedDB);
        });

        afterEach(() => {
            vi.unstubAllGlobals();
        });

        it('finds a saved record by its fingerprint', async () => {
            const store = new ResumeStore();
            await store.put(record('fp-1'));

            expect(await new ResumeStore().get('fp-1')).toEqual(record('fp-1', expect.any(Number)));
            expect(await store.get('fp-unknown')).toBeNull();
        });

        it('ignores an expired record', async () => {
            const store = new ResumeStore();
            await store.put(record('fp-old', Date.now() - 1));

            expect(await store.get('fp-old')).toBeNull();
        });

        it('deletes a record', async () => {
            const store = new ResumeStore();
            await store.put(record('fp-1'));
            await store.put(record('fp-2'));

            await store.delete('fp-1');

            expect(await store.get('fp-1')).toBeNull();
            expect(await store.get('fp-2')).not.toBeNull();
        });

        it('closes the database after each operation', async () => {
            const store = new ResumeStore();

            await store.put(record('fp-1'));
            await store.get('fp-1');
            await store.delete('fp-1');

            expect(indexedDB.openConnections).toBe(0);
        });
    });

    it('does nothing when IndexedDB is unavailable', async () => {
        expect('indexedDB' in globalThis).toBe(false);
        const store = new ResumeStore();

        await expect(store.put(record('fp-1'))).resolves.toBeUndefined();
        await expect(store.get('fp-1')).resolves.toBeNull();
        await expect(store.delete('fp-1')).resolves.toBeUndefined();
    });
});
