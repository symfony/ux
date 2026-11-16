/**
 * Tests for PreviewCache (preview_cache.ts)
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { PreviewCache } from '../../src/preview_cache';

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
    };

    return {
        open: vi.fn(async () => mockCache),
        mockCache,
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
        vi.unstubAllGlobals();
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

        it('turns every operation into a no-op when unsupported', async () => {
            // caches stays defined, so a missing support check would reach it
            vi.stubGlobal('HTMLCanvasElement', undefined);
            const createImageBitmap = vi.fn(async () => {
                throw new Error('not reached');
            });
            vi.stubGlobal('createImageBitmap', createImageBitmap);
            const file = new File([new Uint8Array(10)], 'photo.png', { type: 'image/png' });

            await expect(cache.store('token', file)).resolves.toBeUndefined();
            await expect(cache.retrieve('token')).resolves.toBeNull();
            expect(createImageBitmap).not.toHaveBeenCalled();
            expect(mockCaches.open).not.toHaveBeenCalled();
        });
    });

    describe('store', () => {
        it('skips non-image files silently', async () => {
            const file = new File(['data'], 'doc.pdf', { type: 'application/pdf' });
            await cache.store('token-1', file);
            expect(mockCaches.open).not.toHaveBeenCalled();
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
            // Build the body from a string, not a Blob: jsdom's Blob and Node's
            // Response come from two different implementations, and undici does not
            // accept the former.
            const response = new Response('fake-image', {
                headers: { 'Content-Type': 'image/jpeg' },
            });
            await mockCaches.mockCache.put('/_ux-upload-preview/my-token', response);

            const result = await cache.retrieve('my-token');
            expect(result).toMatch(/^blob:/);
        });
    });
});
