/**
 * Tests for PreviewCache (preview-cache.ts)
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { PreviewCache } from '../../src/preview-cache';

// Minimal CacheStorage mock
function createMockCaches() {
    const store = new Map<string, Response>();

    const mockCache = {
        put: vi.fn(async (key: string, response: Response) => {
            store.set(key, response.clone());
        }),
        match: vi.fn(async (key: string) => {
            const resp = store.get(key);
            return resp ? resp.clone() : undefined;
        }),
        delete: vi.fn(async (key: string) => {
            return store.delete(key);
        }),
    };

    return {
        open: vi.fn(async () => mockCache),
        delete: vi.fn(async (_name: string) => {
            store.clear();
            return true;
        }),
        has: vi.fn(async () => true),
        keys: vi.fn(async () => []),
        match: vi.fn(async () => undefined),
        mockCache,
        store,
    };
}

describe('PreviewCache', () => {
    let cache: PreviewCache;
    let mockCaches: ReturnType<typeof createMockCaches>;

    beforeEach(() => {
        mockCaches = createMockCaches();
        // @ts-expect-error - replacing global caches
        globalThis.caches = mockCaches;
        cache = new PreviewCache('test-previews');
    });

    afterEach(() => {
        // @ts-expect-error - cleanup
        delete globalThis.caches;
    });

    describe('isSupported', () => {
        it('returns true when CacheStorage and Canvas are available', () => {
            expect(PreviewCache.isSupported()).toBe(true);
        });

        it('returns false when caches is not defined', () => {
            // @ts-expect-error - removing global
            delete globalThis.caches;
            expect(PreviewCache.isSupported()).toBe(false);
        });
    });

    describe('store', () => {
        it('skips non-image files silently', async () => {
            const file = new File(['data'], 'doc.pdf', { type: 'application/pdf' });
            await cache.store('token-1', file);
            expect(mockCaches.open).not.toHaveBeenCalled();
        });

        it('skips when CacheStorage is unavailable', async () => {
            // @ts-expect-error - removing global
            delete globalThis.caches;
            const file = new File([new Uint8Array(10)], 'photo.png', { type: 'image/png' });
            // Should not throw
            await cache.store('token-2', file);
        });

        // NOTE: store() internally calls createImageBitmap which is not available
        // in JSDOM. This test verifies the graceful degradation path.
        it('degrades gracefully when createImageBitmap is unavailable', async () => {
            const original = globalThis.createImageBitmap;
            // @ts-expect-error - removing API
            delete globalThis.createImageBitmap;

            const file = new File([new Uint8Array(10)], 'photo.png', { type: 'image/png' });
            await cache.store('token-3', file);

            // Should not have stored anything since bitmap couldn't be created
            const result = await cache.retrieve('token-3');
            expect(result).toBeNull();

            if (original) {
                globalThis.createImageBitmap = original;
            }
        });
    });

    describe('retrieve', () => {
        it('returns null for uncached token', async () => {
            const result = await cache.retrieve('nonexistent');
            expect(result).toBeNull();
        });

        it('returns blob URL for cached token', async () => {
            // Manually put something in the mock cache
            const blob = new Blob(['fake-image'], { type: 'image/jpeg' });
            const response = new Response(blob, {
                headers: { 'Content-Type': 'image/jpeg' },
            });
            await mockCaches.mockCache.put('/_ux-upload-preview/my-token', response);

            const result = await cache.retrieve('my-token');
            expect(result).toMatch(/^blob:/);
        });

        it('returns null when CacheStorage unavailable', async () => {
            // @ts-expect-error - removing global
            delete globalThis.caches;
            const result = await cache.retrieve('any-token');
            expect(result).toBeNull();
        });
    });

    describe('remove', () => {
        it('deletes a cached entry', async () => {
            // Put then remove
            const blob = new Blob(['data'], { type: 'image/jpeg' });
            await mockCaches.mockCache.put('/_ux-upload-preview/del-token', new Response(blob));
            expect(mockCaches.store.size).toBe(1);

            await cache.remove('del-token');
            expect(mockCaches.mockCache.delete).toHaveBeenCalledWith('/_ux-upload-preview/del-token');
        });

        it('silently handles missing entries', async () => {
            await cache.remove('nonexistent');
            // No error thrown
        });

        it('silently handles unavailable CacheStorage', async () => {
            // @ts-expect-error - removing global
            delete globalThis.caches;
            await cache.remove('any-token');
        });
    });

    describe('clear', () => {
        it('deletes the entire cache', async () => {
            await cache.clear();
            expect(mockCaches.delete).toHaveBeenCalledWith('test-previews');
        });

        it('silently handles unavailable CacheStorage', async () => {
            // @ts-expect-error - removing global
            delete globalThis.caches;
            await cache.clear();
        });
    });
});
