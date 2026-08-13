/**
 * Core upload logic for Symfony UX Upload
 */

import type { InitResponse, UploadConfig, CompleteResponse, UploadResult, ProgressResponse } from './types';
import { ResumeStore } from './resume-store';

export interface UploadSpeed {
    bytesPerSecond: number;
    remainingMs: number;
}

export interface UploaderEvents {
    onInit?: (uploadId: string, file: File, resumable: boolean) => void;
    onDirectProgress?: (file: File, percent: number, speed?: UploadSpeed) => void;
    onProgress?: (uploadId: string, percent: number, chunkIndex: number, speed?: UploadSpeed) => void;
    onComplete?: (uploadId: string, result: UploadResult) => void;
    onError?: (uploadId: string, error: Error) => void;
    onChunkComplete?: (uploadId: string, chunkIndex: number, totalChunks: number) => void;
}

export type IntegrityAlgorithm = 'sha256' | 'sha384' | 'sha512';
export type UploaderFetch = (input: RequestInfo | URL, init?: RequestInit) => Promise<Response>;
export type UploaderXhrFactory = () => XMLHttpRequest;

export interface UploaderOptions {
    initUrl: string;
    directUrl?: string;
    directUploadThreshold?: number;
    removeUrl?: string;
    events?: UploaderEvents;
    uploader?: string;
    csrfToken?: string | null;
    integrityAlgorithm?: IntegrityAlgorithm;
    policyToken?: string | null;
    compression?: boolean;
    credentials?: RequestCredentials;
    headers?: Record<string, string>;
    fetch?: UploaderFetch;
    xhr?: UploaderXhrFactory;
}

export class UploadCancelledError extends Error {
    constructor() {
        super('Upload cancelled');
        this.name = 'UploadCancelledError';
    }
}

export class UploadSuspendedError extends Error {
    constructor() {
        super('Upload suspended');
        this.name = 'UploadSuspendedError';
    }
}

const WEB_CRYPTO_ALGORITHMS: Record<IntegrityAlgorithm, string> = {
    sha256: 'SHA-256',
    sha384: 'SHA-384',
    sha512: 'SHA-512',
};
const MAX_BROWSER_FILE_HASH_SIZE = 64 * 1024 * 1024;

function isAbortError(error: unknown): boolean {
    return (error instanceof DOMException || error instanceof Error) && error.name === 'AbortError';
}

interface SpeedSample {
    time: number;
    bytes: number;
}

export class Uploader {
    private resumeStore = new ResumeStore();
    private abortControllers: Map<string, AbortController> = new Map();
    private uploadUrls: Map<string, string> = new Map();
    private uploadFingerprints: Map<string, string> = new Map();
    private abortReasons: Map<string, 'cancel' | 'suspend'> = new Map();
    private pauseFlags: Map<string, boolean> = new Map();
    private pauseResolvers: Map<string, () => void> = new Map();
    private speedSamples: Map<string, SpeedSample[]> = new Map();
    private uploadFileSizes: Map<string, number> = new Map();
    private initUrl: string;
    private directUrl?: string;
    private directUploadThreshold: number;
    private removeUrl: string;
    private events: UploaderEvents;
    private uploaderName: string;
    private csrfToken: string | null;
    private integrityAlgorithm: IntegrityAlgorithm;
    private policyToken: string | null;
    private compressionEnabled: boolean;
    private credentials?: RequestCredentials;
    private headers: Record<string, string>;
    private fetcher: UploaderFetch;
    private fileAbortControllers: WeakMap<File, AbortController> = new WeakMap();
    private xhrFactory: UploaderXhrFactory;
    private useXhrForDirect: boolean;

    constructor(options: UploaderOptions);
    constructor(
        initUrl: string,
        events?: UploaderEvents,
        uploaderName?: string,
        csrfToken?: string | null,
        integrityAlgorithm?: IntegrityAlgorithm,
        policyToken?: string | null,
        compressionEnabled?: boolean
    );
    constructor(
        optionsOrInitUrl: UploaderOptions | string,
        events: UploaderEvents = {},
        uploaderName: string = 'default',
        csrfToken: string | null = null,
        integrityAlgorithm: IntegrityAlgorithm = 'sha256',
        policyToken: string | null = null,
        compressionEnabled: boolean = false
    ) {
        const options: UploaderOptions =
            typeof optionsOrInitUrl === 'string'
                ? {
                      initUrl: optionsOrInitUrl,
                      events,
                      uploader: uploaderName,
                      csrfToken,
                      integrityAlgorithm,
                      policyToken,
                      compression: compressionEnabled,
                  }
                : optionsOrInitUrl;

        this.initUrl = options.initUrl;
        this.directUrl = options.directUrl;
        this.directUploadThreshold = options.directUploadThreshold ?? 0;
        this.removeUrl = options.removeUrl ?? options.initUrl.replace(/\/init$/, '/remove');
        this.events = options.events ?? {};
        this.uploaderName = options.uploader ?? 'default';
        this.csrfToken = options.csrfToken ?? null;
        this.integrityAlgorithm = options.integrityAlgorithm ?? 'sha256';
        this.policyToken = options.policyToken ?? null;
        this.compressionEnabled = options.compression ?? false;
        this.credentials = options.credentials;
        this.headers = options.headers ?? {};
        this.fetcher = options.fetch ?? ((input, init) => fetch(input, init));
        this.xhrFactory = options.xhr ?? (() => new XMLHttpRequest());
        this.useXhrForDirect =
            options.xhr !== undefined || (options.fetch === undefined && options.credentials !== 'omit');
    }

    /**
     * Upload a file with chunking, compression, and retry support
     */
    async upload(file: File): Promise<UploadResult> {
        // One controller covers the whole file so a cancel issued before the
        // server hands out an upload ID still stops the work already started.
        const abortController = new AbortController();
        const signal = abortController.signal;
        this.fileAbortControllers.set(file, abortController);

        try {
            // 1. Compute the configured file hash for integrity verification
            const hash = await this.computeFileHash(file);
            this.throwIfAborted(signal);

            if (this.usesDirectUpload(file)) {
                try {
                    return await this.uploadDirect(file, signal, hash);
                } catch (error) {
                    if (!(error instanceof DirectUploadFallbackError)) {
                        throw error;
                    }
                }
            }

            // 2. Initialize upload - get signed URLs
            const fingerprint = await this.fingerprint(file);
            const initResponse =
                (await this.resumeUpload(fingerprint)) ?? (await this.initializeUpload(file, signal, hash));
            this.throwIfAborted(signal);
            const { uploadId, uploadUrl, config } = initResponse;
            config.compression = config.compression && this.compressionEnabled;
            if (initResponse.resumeToken) {
                await this.resumeStore.put({
                    fingerprint,
                    token: initResponse.resumeToken,
                    expiresAt: Date.now() + 24 * 60 * 60 * 1000,
                });
            }

            this.events.onInit?.(uploadId, file, true);

            this.abortControllers.set(uploadId, abortController);
            this.uploadUrls.set(uploadId, uploadUrl);
            this.uploadFingerprints.set(uploadId, fingerprint);
            this.speedSamples.set(uploadId, []);
            this.uploadFileSizes.set(uploadId, file.size);

            try {
                // 3. Check for resume (get existing chunks)
                const existingChunks = await this.checkResume(uploadUrl);

                const chunksToUpload: number[] = [];
                for (let i = 0; i < config.totalChunks; i++) {
                    if (!existingChunks.includes(i)) {
                        chunksToUpload.push(i);
                    }
                }

                // 4. Upload chunks in parallel
                await this.uploadChunks(
                    file,
                    uploadId,
                    uploadUrl,
                    chunksToUpload,
                    config,
                    signal,
                    existingChunks.length
                );

                // 5. Complete upload
                const result = await this.completeUpload(uploadUrl, uploadId, signal);
                await this.resumeStore.delete(fingerprint);

                this.events.onComplete?.(uploadId, result);

                return result;
            } catch (error) {
                if (isAbortError(error)) {
                    const reason = this.abortReasons.get(uploadId);
                    if (reason === 'cancel') {
                        await this.resumeStore.delete(fingerprint);
                        throw new UploadCancelledError();
                    }
                    if (reason === 'suspend') {
                        throw new UploadSuspendedError();
                    }
                    throw new UploadCancelledError();
                }
                this.events.onError?.(uploadId, error as Error);
                throw error;
            } finally {
                this.abortControllers.delete(uploadId);
                this.uploadUrls.delete(uploadId);
                this.uploadFingerprints.delete(uploadId);
                this.abortReasons.delete(uploadId);
                this.pauseFlags.delete(uploadId);
                this.pauseResolvers.delete(uploadId);
                this.speedSamples.delete(uploadId);
                this.uploadFileSizes.delete(uploadId);
            }
        } catch (error) {
            // Aborted before the upload ID was known: no session to report on.
            if (isAbortError(error)) {
                throw new UploadCancelledError();
            }
            throw error;
        } finally {
            this.fileAbortControllers.delete(file);
        }
    }

    usesDirectUpload(file: File): boolean {
        return Boolean(this.directUrl) && this.directUploadThreshold > 0 && file.size <= this.directUploadThreshold;
    }

    /**
     * Cancel an upload that has no server upload ID yet (direct upload, or a
     * chunked upload still initializing).
     */
    cancelFile(file: File): void {
        this.fileAbortControllers.get(file)?.abort();
    }

    /**
     * Cancel an in-progress upload
     */
    cancel(uploadId: string): void {
        this.abortReasons.set(uploadId, 'cancel');
        const controller = this.abortControllers.get(uploadId);
        if (controller) {
            controller.abort();
            this.abortControllers.delete(uploadId);
        }

        // Resume if paused so the promise chain can settle
        this.resumeIfPaused(uploadId);

        // Also call backend to clean up if we have the URL
        const uploadUrl = this.uploadUrls.get(uploadId);
        if (uploadUrl) {
            this.request(uploadUrl, { method: 'DELETE' }).catch(() => {});
            this.uploadUrls.delete(uploadId);
        }
        const fingerprint = this.uploadFingerprints.get(uploadId);
        if (fingerprint) {
            this.resumeStore.delete(fingerprint).catch(() => {});
        }
    }

    /**
     * Stop local work while preserving the server session for a later resume.
     */
    suspend(uploadId: string): void {
        this.abortReasons.set(uploadId, 'suspend');
        this.abortControllers.get(uploadId)?.abort();
        this.resumeIfPaused(uploadId);
    }

    /**
     * Remove a completed upload.
     */
    async remove(token: string): Promise<void> {
        const response = await this.request(this.removeUrl, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                ...(this.csrfToken ? { 'X-CSRF-Token': this.csrfToken } : {}),
            },
            body: JSON.stringify({
                token,
                ...(this.policyToken ? { policyToken: this.policyToken } : {}),
            }),
        });
        if (!response.ok) {
            let message = 'Failed to remove upload';
            try {
                const error = await response.json();
                if (error.error) message = error.error;
            } catch {
                // Response body is not JSON
            }
            throw new Error(message);
        }
    }

    /**
     * Pause an in-progress upload (stops sending new chunks)
     */
    pause(uploadId: string): void {
        this.pauseFlags.set(uploadId, true);
    }

    /**
     * Resume a paused upload
     */
    resume(uploadId: string): void {
        this.resumeIfPaused(uploadId);
    }

    /**
     * Check if an upload is paused
     */
    isPaused(uploadId: string): boolean {
        return this.pauseFlags.get(uploadId) === true;
    }

    /**
     * Initialize upload and get signed URLs
     */
    private async initializeUpload(file: File, signal: AbortSignal, hash?: string): Promise<InitResponse> {
        const response = await this.request(this.initUrl, {
            method: 'POST',
            signal,
            headers: {
                'Content-Type': 'application/json',
                ...(this.csrfToken ? { 'X-CSRF-Token': this.csrfToken } : {}),
            },
            body: JSON.stringify({
                filename: file.name,
                fileSize: file.size,
                mimeType: file.type || 'application/octet-stream',
                ...(this.uploaderName !== 'default' ? { uploader: this.uploaderName } : {}),
                ...(hash ? { hash } : {}),
                ...(hash ? { hashAlgorithm: this.integrityAlgorithm } : {}),
                ...(this.policyToken ? { policyToken: this.policyToken } : {}),
            }),
        });

        if (!response.ok) {
            let message = 'Failed to initialize upload';
            try {
                const error = await response.json();
                if (error.error) message = error.error;
            } catch {
                // Response body is not JSON
            }
            throw new Error(message);
        }

        return response.json();
    }

    private async uploadDirect(file: File, signal: AbortSignal, hash?: string): Promise<UploadResult> {
        const directUrl = this.directUrl;
        if (!directUrl) {
            throw new DirectUploadFallbackError();
        }

        const originalData = new Uint8Array(await file.arrayBuffer());
        const digest = await this.digestBuffer(originalData, 'SHA-256');
        let transmittedData: Uint8Array<ArrayBufferLike> = originalData;
        let compressed = false;
        if (this.compressionEnabled && this.isCompressionSupported() && !this.isAlreadyCompressed(file.type)) {
            try {
                transmittedData = await this.compress(originalData);
                compressed = true;
            } catch {
                transmittedData = originalData;
            }
        }

        const body = new FormData();
        body.append('file', new Blob([transmittedData as BlobPart]), file.name);
        body.append('filename', file.name);
        body.append('fileSize', file.size.toString());
        body.append('mimeType', file.type || 'application/octet-stream');
        body.append('digest', digest);
        // Declare the encoding instead of letting the server sniff magic bytes.
        if (compressed) body.append('contentEncoding', 'gzip');
        if (this.uploaderName !== 'default') body.append('uploader', this.uploaderName);
        if (hash) {
            body.append('hash', hash);
            body.append('hashAlgorithm', this.integrityAlgorithm);
        }
        if (this.policyToken) body.append('policyToken', this.policyToken);

        try {
            const data = this.useXhrForDirect
                ? await this.uploadDirectWithXhr(file, directUrl, body, signal)
                : await this.uploadDirectWithFetch(directUrl, body, signal);
            if (!data?.uploadId) {
                throw new Error('Direct upload response is missing its upload ID');
            }
            const result = {
                uploadId: data.uploadId,
                token: data.token,
                metadata: data.meta,
            };
            this.events.onInit?.(data.uploadId, file, false);
            this.events.onProgress?.(data.uploadId, 100, 0);
            this.events.onComplete?.(data.uploadId, result);

            return result;
        } catch (error) {
            if (isAbortError(error)) {
                throw new UploadCancelledError();
            }
            throw error;
        }
    }

    private uploadDirectWithXhr(
        file: File,
        directUrl: string,
        body: FormData,
        signal: AbortSignal
    ): Promise<CompleteResponse> {
        return new Promise((resolve, reject) => {
            const xhr = this.xhrFactory();
            const startedAt = performance.now();

            const rejectNetworkError = (): void => reject(new TypeError('Direct upload network request failed'));
            const rejectAbort = (): void => reject(new UploadCancelledError());

            xhr.open('POST', directUrl);
            xhr.withCredentials = this.credentials === 'include';
            for (const [name, value] of Object.entries(this.headers)) {
                xhr.setRequestHeader(name, value);
            }
            if (this.csrfToken) {
                xhr.setRequestHeader('X-CSRF-Token', this.csrfToken);
            }

            xhr.upload.addEventListener('progress', (event) => {
                if (!event.lengthComputable || event.total <= 0) {
                    return;
                }

                const elapsedMs = Math.max(performance.now() - startedAt, 1);
                const bytesPerSecond = Math.round((event.loaded * 1000) / elapsedMs);
                const percent = Math.min(Math.round((event.loaded / event.total) * 100), 100);
                const remainingMs =
                    bytesPerSecond > 0 ? Math.round(((event.total - event.loaded) / bytesPerSecond) * 1000) : 0;

                this.events.onDirectProgress?.(file, percent, { bytesPerSecond, remainingMs });
            });
            xhr.addEventListener('load', () => {
                if (413 === xhr.status) {
                    reject(new DirectUploadFallbackError());
                    return;
                }

                const data = this.parseDirectResponse(xhr.responseText);
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject(new Error(this.readDirectError(data)));
                    return;
                }

                resolve(data as CompleteResponse);
            });
            xhr.addEventListener('error', rejectNetworkError);
            xhr.addEventListener('abort', rejectAbort);

            if (signal.aborted) {
                rejectAbort();
                return;
            }
            signal.addEventListener('abort', () => xhr.abort(), { once: true });
            xhr.send(body);
        });
    }

    private async uploadDirectWithFetch(
        directUrl: string,
        body: FormData,
        signal: AbortSignal
    ): Promise<CompleteResponse> {
        const response = await this.request(directUrl, {
            method: 'POST',
            body,
            signal,
        });
        if (413 === response.status) {
            throw new DirectUploadFallbackError();
        }

        let data: unknown;
        try {
            data = await response.json();
        } catch {
            data = null;
        }
        if (!response.ok) {
            throw new Error(this.readDirectError(data));
        }

        return data as CompleteResponse;
    }

    private parseDirectResponse(responseText: string): unknown {
        try {
            return JSON.parse(responseText);
        } catch {
            return null;
        }
    }

    private readDirectError(data: unknown): string {
        if (data && typeof data === 'object' && 'error' in data && typeof data.error === 'string') {
            return data.error;
        }

        return 'Direct upload failed';
    }

    private async resumeUpload(fingerprint: string): Promise<InitResponse | null> {
        const record = await this.resumeStore.get(fingerprint);
        if (!record) return null;
        try {
            const response = await this.request(this.initUrl.replace(/\/init$/, '/resume'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    resumeToken: record.token,
                    ...(this.policyToken ? { policyToken: this.policyToken } : {}),
                }),
            });
            if (response.ok) return response.json();
        } catch {
            // A failed resume falls back to a fresh session.
        }
        await this.resumeStore.delete(fingerprint);
        return null;
    }

    private async fingerprint(file: File): Promise<string> {
        const sampleSize = Math.min(64 * 1024, file.size);
        const first = new Uint8Array(await file.slice(0, sampleSize).arrayBuffer());
        const last = new Uint8Array(await file.slice(Math.max(0, file.size - sampleSize)).arrayBuffer());
        const sample = new Uint8Array(first.length + last.length);
        sample.set(first);
        sample.set(last, first.length);
        const policyFingerprint = this.policyToken
            ? await this.digestBuffer(new TextEncoder().encode(this.policyToken), 'SHA-256')
            : 'none';
        const scope = new TextEncoder().encode(
            `${this.initUrl}\0${this.uploaderName}\0${policyFingerprint}\0${this.integrityAlgorithm}`
        );
        const scopeFingerprint = await this.digestBuffer(scope, 'SHA-256');
        return `v2:${scopeFingerprint}:${file.name}:${file.size}:${file.lastModified}:${await this.digestBuffer(sample, 'SHA-256')}`;
    }

    /**
     * Check which chunks already exist (for resume)
     */
    private async checkResume(uploadUrl: string): Promise<number[]> {
        try {
            const response = await this.request(uploadUrl, { method: 'GET' });
            if (response.ok) {
                const data: ProgressResponse = await response.json();
                return data.progress?.chunkIndices || [];
            }
        } catch {
            // Ignore errors - assume no chunks exist
        }
        return [];
    }

    /**
     * Upload chunks with parallel execution
     */
    private async uploadChunks(
        file: File,
        uploadId: string,
        uploadUrl: string,
        chunks: number[],
        config: UploadConfig,
        signal: AbortSignal,
        existingCount: number
    ): Promise<void> {
        const { chunkSize, parallel, compression, totalChunks } = config;

        // Ensure chunks is an array
        if (!Array.isArray(chunks)) {
            chunks = [];
        }

        let completedCount = existingCount;

        // Process chunks in batches
        for (let i = 0; i < chunks.length; i += parallel) {
            // Wait if paused (resolves immediately if not paused)
            await this.waitIfPaused(uploadId);

            this.throwIfAborted(signal);

            const batch = chunks.slice(i, i + parallel);

            await Promise.all(
                batch.map(async (chunkIndex) => {
                    await this.uploadChunk(
                        file,
                        uploadId,
                        uploadUrl,
                        chunkIndex,
                        chunkSize,
                        compression,
                        totalChunks,
                        signal
                    );
                    // Report progress after each chunk completes
                    completedCount++;
                    const percent = Math.min(Math.round((completedCount / totalChunks) * 100), 100);
                    const chunkBytes = Math.min(chunkSize, file.size - chunkIndex * chunkSize);
                    const speed = this.recordSpeedSample(uploadId, chunkBytes, percent);
                    this.events.onProgress?.(uploadId, percent, chunkIndex, speed);
                    this.events.onChunkComplete?.(uploadId, chunkIndex, totalChunks);
                })
            );
        }
    }

    /**
     * Upload a single chunk with retry logic
     */
    private async uploadChunk(
        file: File,
        uploadId: string,
        uploadUrl: string,
        chunkIndex: number,
        chunkSize: number,
        compression: boolean,
        totalChunks: number,
        signal: AbortSignal,
        attempt: number = 0
    ): Promise<void> {
        const maxRetries = 3;
        const start = chunkIndex * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const blob = file.slice(start, end);

        let data: ArrayBuffer | Uint8Array = await blob.arrayBuffer();
        const chunkDigest = await this.digestBuffer(data, 'SHA-256');
        const headers: Record<string, string> = {
            'Content-Type': 'application/octet-stream',
            'X-Chunk-Index': chunkIndex.toString(),
            'X-Chunk-Digest': chunkDigest,
        };

        // Compress if enabled and supported
        if (compression && this.isCompressionSupported() && !this.isAlreadyCompressed(file.type)) {
            try {
                data = await this.compress(new Uint8Array(data));
                headers['Content-Encoding'] = 'gzip';
            } catch {
                // Fall back to uncompressed
            }
        }

        try {
            const response = await this.request(uploadUrl, {
                method: 'PUT',
                headers,
                body: data as BodyInit,
                signal,
            });

            if (!response.ok) {
                let message = response.statusText;
                try {
                    const body = await response.json();
                    if (body.error) message = body.error;
                } catch {
                    // Response body is not JSON -- use statusText
                }
                throw new Error(`Chunk upload failed: ${message}`);
            }
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                throw error;
            }

            // Retry with exponential backoff
            if (attempt < maxRetries) {
                const delay = Math.pow(2, attempt) * 1000;
                await this.sleep(delay, signal);
                return this.uploadChunk(
                    file,
                    uploadId,
                    uploadUrl,
                    chunkIndex,
                    chunkSize,
                    compression,
                    totalChunks,
                    signal,
                    attempt + 1
                );
            }

            throw error;
        }
    }

    /**
     * Complete the upload
     */
    private async completeUpload(uploadUrl: string, uploadId: string, signal: AbortSignal): Promise<UploadResult> {
        this.throwIfAborted(signal);

        const response = await this.request(uploadUrl, {
            method: 'POST',
            signal,
            headers: {
                'Content-Type': 'application/json',
                ...(this.csrfToken ? { 'X-CSRF-Token': this.csrfToken } : {}),
            },
        });

        if (!response.ok) {
            let message = 'Failed to complete upload';
            try {
                const error = await response.json();
                if (error.error) message = error.error;
            } catch {
                // Response body is not JSON
            }
            throw new Error(message);
        }

        const data: CompleteResponse = await response.json();
        // A cancel raced with the response: settle as cancelled, never complete.
        this.throwIfAborted(signal);

        return {
            uploadId: data.uploadId ?? uploadId,
            token: data.token,
            metadata: data.meta,
        };
    }

    private request(input: RequestInfo | URL, init: RequestInit = {}): Promise<Response> {
        const method = (init.method ?? 'GET').toUpperCase();
        const csrfHeaders: Record<string, string> = {};
        if (this.csrfToken && method !== 'GET' && method !== 'HEAD') {
            csrfHeaders['X-CSRF-Token'] = this.csrfToken;
        }

        return this.fetcher(input, {
            ...init,
            ...(this.credentials ? { credentials: this.credentials } : {}),
            headers: {
                ...this.headers,
                ...csrfHeaders,
                ...(init.headers as Record<string, string> | undefined),
            },
        });
    }

    /**
     * Check if Compression Streams API is supported
     */
    private isCompressionSupported(): boolean {
        return typeof CompressionStream !== 'undefined';
    }

    /**
     * Check if file type is already compressed
     */
    private isAlreadyCompressed(mimeType: string): boolean {
        const compressedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'video/',
            'audio/',
            'application/zip',
            'application/gzip',
            'application/x-rar',
            'application/x-7z',
        ];

        return compressedTypes.some((type) => mimeType.startsWith(type) || mimeType === type);
    }

    /**
     * Compress data using Compression Streams API
     */
    private async compress(data: Uint8Array): Promise<Uint8Array> {
        const stream = new ReadableStream({
            start(controller) {
                controller.enqueue(data);
                controller.close();
            },
        });

        const compressedStream = stream.pipeThrough(new CompressionStream('gzip'));
        const reader = compressedStream.getReader();
        const chunks: Uint8Array[] = [];

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            chunks.push(value);
        }

        // Combine all chunks
        const totalLength = chunks.reduce((acc, chunk) => acc + chunk.length, 0);
        const result = new Uint8Array(totalLength);
        let offset = 0;
        for (const chunk of chunks) {
            result.set(chunk, offset);
            offset += chunk.length;
        }

        return result;
    }

    /**
     * Record a speed sample and calculate current speed + ETA
     */
    private recordSpeedSample(uploadId: string, chunkBytes: number, percent: number): UploadSpeed | undefined {
        const samples = this.speedSamples.get(uploadId);
        if (!samples) return undefined;

        const now = Date.now();
        samples.push({ time: now, bytes: chunkBytes });

        // Keep only samples from the last 10 seconds for a moving window
        const windowMs = 10_000;
        const cutoff = now - windowMs;
        while (samples.length > 1 && samples[0].time < cutoff) {
            samples.shift();
        }

        // Need at least 2 samples to calculate speed
        if (samples.length < 2) return undefined;

        const elapsed = (samples[samples.length - 1].time - samples[0].time) / 1000;
        if (elapsed <= 0) return undefined;

        const totalBytes = samples.reduce((sum, s) => sum + s.bytes, 0);
        const bytesPerSecond = totalBytes / elapsed;

        if (bytesPerSecond <= 0) return undefined;

        const fileSize = this.uploadFileSizes.get(uploadId) ?? 0;
        const bytesRemaining = fileSize * (1 - percent / 100);
        const remainingMs = (bytesRemaining / bytesPerSecond) * 1000;

        return { bytesPerSecond, remainingMs };
    }

    /**
     * Compute a file hash using Web Crypto API.
     * Returns undefined if the API is not available (graceful degradation).
     */
    private async computeFileHash(file: File): Promise<string | undefined> {
        if (file.size > MAX_BROWSER_FILE_HASH_SIZE) {
            return undefined;
        }
        if (typeof crypto === 'undefined' || !crypto.subtle) {
            return undefined;
        }

        try {
            const buffer = await file.arrayBuffer();
            const hashBuffer = await crypto.subtle.digest(WEB_CRYPTO_ALGORITHMS[this.integrityAlgorithm], buffer);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map((b) => b.toString(16).padStart(2, '0')).join('');
        } catch {
            // Graceful degradation: skip hash if computation fails
            return undefined;
        }
    }

    private async digestBuffer(data: ArrayBuffer | Uint8Array, algorithm: string): Promise<string> {
        const bytes = data instanceof Uint8Array ? data : new Uint8Array(data);
        const digest = await crypto.subtle.digest(algorithm, bytes as Uint8Array<ArrayBuffer>);
        return Array.from(new Uint8Array(digest), (byte) => byte.toString(16).padStart(2, '0')).join('');
    }

    /**
     * Wait for the retry backoff, giving up as soon as the upload is aborted
     */
    private sleep(ms: number, signal: AbortSignal): Promise<void> {
        return new Promise((resolve, reject) => {
            if (signal.aborted) {
                reject(new DOMException('Upload cancelled', 'AbortError'));
                return;
            }

            const onAbort = () => {
                clearTimeout(timer);
                reject(new DOMException('Upload cancelled', 'AbortError'));
            };
            const timer = setTimeout(() => {
                signal.removeEventListener('abort', onAbort);
                resolve();
            }, ms);
            signal.addEventListener('abort', onAbort, { once: true });
        });
    }

    /**
     * Internal: stop the current step as soon as the upload has been aborted
     */
    private throwIfAborted(signal: AbortSignal): void {
        if (signal.aborted) {
            throw new DOMException('Upload cancelled', 'AbortError');
        }
    }

    /**
     * Wait if the upload is paused, resolving when resumed
     */
    private waitIfPaused(uploadId: string): Promise<void> {
        if (!this.pauseFlags.get(uploadId)) {
            return Promise.resolve();
        }

        return new Promise<void>((resolve) => {
            this.pauseResolvers.set(uploadId, resolve);
        });
    }

    /**
     * Internal: clear pause state and resolve any waiting promise
     */
    private resumeIfPaused(uploadId: string): void {
        this.pauseFlags.delete(uploadId);
        const resolver = this.pauseResolvers.get(uploadId);
        if (resolver) {
            this.pauseResolvers.delete(uploadId);
            resolver();
        }
    }
}

class DirectUploadFallbackError extends Error {}
