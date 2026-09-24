/**
 * Preview cache for Symfony UX Upload
 *
 * Stores resized image thumbnails in CacheStorage keyed by upload token,
 * so previews survive page re-renders during hydration.
 */

const MAX_DIMENSION = 200;
const JPEG_QUALITY = 0.7;

export class PreviewCache {
    private cacheName: string;

    constructor(cacheName: string = 'ux-upload-previews') {
        this.cacheName = cacheName;
    }

    /**
     * Resize an image file to a thumbnail and store it in CacheStorage.
     * Skips silently if the file is not an image or CacheStorage is unavailable.
     */
    async store(token: string, file: File): Promise<void> {
        if (!PreviewCache.isSupported() || !file.type.startsWith('image/')) {
            return;
        }

        try {
            const blob = await this.createThumbnail(file);
            if (!blob) {
                return;
            }

            const cache = await caches.open(this.cacheName);
            const response = new Response(blob, {
                headers: { 'Content-Type': 'image/jpeg' },
            });
            await cache.put(this.buildKey(token), response);
        } catch {
            // Graceful degradation: cache miss is acceptable
        }
    }

    /**
     * Retrieve a cached thumbnail as a blob URL, or null if not cached.
     */
    async retrieve(token: string): Promise<string | null> {
        if (!PreviewCache.isSupported()) {
            return null;
        }

        try {
            const cache = await caches.open(this.cacheName);
            const response = await cache.match(this.buildKey(token));
            if (!response) {
                return null;
            }

            const blob = await response.blob();
            return URL.createObjectURL(blob);
        } catch {
            return null;
        }
    }

    /**
     * Remove a single cached preview.
     */
    async remove(token: string): Promise<void> {
        if (!PreviewCache.isSupported()) {
            return;
        }

        try {
            const cache = await caches.open(this.cacheName);
            await cache.delete(this.buildKey(token));
        } catch {
            // Ignore removal failures
        }
    }

    /**
     * Clear the entire preview cache.
     */
    async clear(): Promise<void> {
        if (!PreviewCache.isSupported()) {
            return;
        }

        try {
            await caches.delete(this.cacheName);
        } catch {
            // Ignore clear failures
        }
    }

    /**
     * Check for CacheStorage + Canvas support.
     * Returns false in Node/test environments where these APIs are unavailable.
     */
    static isSupported(): boolean {
        return (
            typeof caches !== 'undefined' &&
            typeof document !== 'undefined' &&
            typeof document.createElement === 'function' &&
            typeof HTMLCanvasElement !== 'undefined'
        );
    }

    private buildKey(token: string): string {
        return `/_ux-upload-preview/${encodeURIComponent(token)}`;
    }

    /**
     * Create a resized JPEG thumbnail from an image file.
     * Uses OffscreenCanvas when available, falls back to a regular canvas element.
     */
    private async createThumbnail(file: File): Promise<Blob | null> {
        const bitmap = await this.loadImageBitmap(file);
        if (!bitmap) {
            return null;
        }

        const { width, height } = this.fitDimensions(bitmap.width, bitmap.height);

        if (typeof OffscreenCanvas !== 'undefined') {
            return this.renderOffscreen(bitmap, width, height);
        }

        return this.renderCanvas(bitmap, width, height);
    }

    private async loadImageBitmap(file: File): Promise<ImageBitmap | null> {
        if (typeof createImageBitmap === 'function') {
            try {
                return await createImageBitmap(file);
            } catch {
                return null;
            }
        }
        return null;
    }

    private fitDimensions(originalWidth: number, originalHeight: number): { width: number; height: number } {
        if (originalWidth <= MAX_DIMENSION && originalHeight <= MAX_DIMENSION) {
            return { width: originalWidth, height: originalHeight };
        }

        const ratio = Math.min(MAX_DIMENSION / originalWidth, MAX_DIMENSION / originalHeight);
        return {
            width: Math.round(originalWidth * ratio),
            height: Math.round(originalHeight * ratio),
        };
    }

    private async renderOffscreen(bitmap: ImageBitmap, width: number, height: number): Promise<Blob | null> {
        const canvas = new OffscreenCanvas(width, height);
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return null;
        }

        ctx.drawImage(bitmap, 0, 0, width, height);
        return canvas.convertToBlob({ type: 'image/jpeg', quality: JPEG_QUALITY });
    }

    private renderCanvas(bitmap: ImageBitmap, width: number, height: number): Promise<Blob | null> {
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return Promise.resolve(null);
        }

        ctx.drawImage(bitmap, 0, 0, width, height);

        return new Promise((resolve) => {
            canvas.toBlob((blob) => resolve(blob), 'image/jpeg', JPEG_QUALITY);
        });
    }
}
