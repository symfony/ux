/**
 * Tests for Uploader class
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { Uploader, UploadCancelledError, UploadSuspendedError } from '../../src/uploader';
import type { InitResponse } from '../../src/types';
import { XhrTestDouble } from './xhr-test-double';

const realSetTimeout = globalThis.setTimeout.bind(globalThis);

const waitForPendingTimer = async (): Promise<void> => {
    for (let attempt = 0; attempt < 100; attempt++) {
        if (vi.getTimerCount() > 0) {
            return;
        }

        await new Promise<void>((resolve) => realSetTimeout(resolve, 0));
    }

    throw new Error('Uploader did not schedule the expected retry timer.');
};

const advanceNextRetry = async (): Promise<void> => {
    await waitForPendingTimer();
    await vi.runOnlyPendingTimersAsync();
};

const flushTasks = async (rounds = 5): Promise<void> => {
    for (let round = 0; round < rounds; round++) {
        await new Promise<void>((resolve) => realSetTimeout(resolve, 0));
    }
};

/** A request that only settles once the test aborts its signal. */
const pendingUntilAborted = (options?: RequestInit): Promise<Response> =>
    new Promise((_resolve, reject) => {
        const abort = () => reject(new DOMException('The operation was aborted.', 'AbortError'));
        if (options?.signal?.aborted) {
            abort();
            return;
        }
        options?.signal?.addEventListener('abort', abort, { once: true });
    });

describe('Uploader', () => {
    const mockInitUrl = '/upload/init';
    let uploader: Uploader;

    const createMockFile = (name: string, size: number, type: string = 'text/plain'): File => {
        return new File([new Uint8Array(size)], name, { type });
    };

    const createMockInitResponse = (uploadId: string, totalChunks: number): InitResponse => ({
        uploadId,
        uploadUrl: `/upload/${uploadId}?sig=test`,
        config: {
            chunkSize: 5 * 1024 * 1024,
            totalChunks,
            compression: true,
            parallel: 3,
        },
    });

    beforeEach(() => {
        uploader = new Uploader(mockInitUrl);
        vi.mocked(fetch).mockReset();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    describe('constructor', () => {
        it('should create an instance with init URL', () => {
            const instance = new Uploader('/api/upload/init');
            expect(instance).toBeInstanceOf(Uploader);
        });

        it('should accept event callbacks', () => {
            const onProgress = vi.fn();
            const onComplete = vi.fn();
            const onError = vi.fn();

            const instance = new Uploader('/api/upload/init', {
                onProgress,
                onComplete,
                onError,
            });

            expect(instance).toBeInstanceOf(Uploader);
        });

        it('should accept named transport options', async () => {
            const customFetch = vi
                .fn()
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(createMockInitResponse('upload-123', 1)),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockResolvedValueOnce({ ok: true } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            token: 'tok-123',
                            meta: { filename: 'test.txt', mimeType: 'text/plain', size: 100 },
                        }),
                } as Response);
            const instance = new Uploader({
                initUrl: '/api/upload/init',
                credentials: 'include',
                headers: { Authorization: 'Bearer token' },
                fetch: customFetch,
            });

            await instance.upload(createMockFile('test.txt', 100));

            expect(customFetch).toHaveBeenCalledWith(
                '/api/upload/init',
                expect.objectContaining({
                    credentials: 'include',
                    headers: expect.objectContaining({
                        Authorization: 'Bearer token',
                        'Content-Type': 'application/json',
                    }),
                })
            );
            expect(customFetch).toHaveBeenCalledTimes(4);
        });

        it('sends CSRF on every mutation and keeps GET requests header-free', async () => {
            const customFetch = vi
                .fn()
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(createMockInitResponse('upload-csrf', 1)),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockResolvedValueOnce({ ok: true } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            token: 'tok-csrf',
                            meta: { filename: 'test.txt', mimeType: 'text/plain', size: 100 },
                        }),
                } as Response);
            const instance = new Uploader({
                initUrl: '/upload/init',
                csrfToken: 'csrf-token',
                fetch: customFetch,
            });

            await instance.upload(createMockFile('test.txt', 100));

            const mutationCalls = customFetch.mock.calls.filter(([, options]) => options?.method !== 'GET');
            for (const [, options] of mutationCalls) {
                expect(options?.headers).toEqual(expect.objectContaining({ 'X-CSRF-Token': 'csrf-token' }));
            }
            const getCall = customFetch.mock.calls.find(([, options]) => options?.method === 'GET');
            expect(getCall?.[1]?.headers).not.toEqual(expect.objectContaining({ 'X-CSRF-Token': expect.anything() }));
        });

        it('does not compress chunks unless compression is explicitly enabled', async () => {
            const customFetch = vi
                .fn()
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(createMockInitResponse('upload-no-compression', 1)),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockResolvedValueOnce({ ok: true } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            token: 'tok',
                            meta: { filename: 'test.txt', mimeType: 'text/plain', size: 100 },
                        }),
                } as Response);
            const instance = new Uploader({ initUrl: '/upload/init', fetch: customFetch });

            await instance.upload(createMockFile('test.txt', 100));

            const putCall = customFetch.mock.calls.find(([, options]) => options?.method === 'PUT');
            expect(putCall?.[1]?.headers).not.toEqual(expect.objectContaining({ 'Content-Encoding': 'gzip' }));
        });
    });

    describe('direct upload', () => {
        it('uses XMLHttpRequest by default and reports upload progress', async () => {
            const xhr = new XhrTestDouble();
            const onDirectProgress = vi.fn();
            const instance = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                uploader: 'documents',
                csrfToken: 'csrf',
                credentials: 'include',
                headers: { Authorization: 'Bearer test' },
                xhr: xhr.factory(),
                events: { onDirectProgress },
            });
            const file = new File(['data'], 'small.txt', { type: 'text/plain' });

            const upload = instance.upload(file);
            await vi.waitFor(() => expect(xhr.body).toBeInstanceOf(FormData));

            expect(xhr.method).toBe('POST');
            expect(xhr.url).toBe('/upload');
            expect(xhr.withCredentials).toBe(true);
            expect(xhr.headers).toEqual(
                new Map([
                    ['Authorization', 'Bearer test'],
                    ['X-CSRF-Token', 'csrf'],
                ])
            );
            expect(fetch).not.toHaveBeenCalled();

            xhr.progress(2, 4);
            expect(onDirectProgress).toHaveBeenCalledWith(
                file,
                50,
                expect.objectContaining({ bytesPerSecond: expect.any(Number), remainingMs: expect.any(Number) })
            );

            xhr.respond(200, {
                success: true,
                uploadId: 'direct-xhr',
                token: 'token-xhr',
                meta: { filename: 'small.txt', mimeType: 'text/plain', size: 4 },
            });

            await expect(upload).resolves.toEqual({
                uploadId: 'direct-xhr',
                token: 'token-xhr',
                metadata: { filename: 'small.txt', mimeType: 'text/plain', size: 4 },
            });
        });

        it('uses one multipart POST for a file at or below the threshold', async () => {
            const onInit = vi.fn();
            const onProgress = vi.fn();
            const onComplete = vi.fn();
            const customFetch = vi.fn().mockResolvedValue({
                ok: true,
                status: 200,
                json: () =>
                    Promise.resolve({
                        success: true,
                        uploadId: 'direct-1',
                        token: 'token-1',
                        meta: { filename: 'small.txt', mimeType: 'text/plain', size: 4 },
                    }),
            } as Response);
            const instance = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                uploader: 'documents',
                csrfToken: 'csrf',
                policyToken: 'policy',
                credentials: 'include',
                headers: { Authorization: 'Bearer test' },
                fetch: customFetch,
                events: { onInit, onProgress, onComplete },
            });
            const file = new File(['data'], 'small.txt', { type: 'text/plain' });

            const result = await instance.upload(file);

            expect(customFetch).toHaveBeenCalledTimes(1);
            const [url, options] = customFetch.mock.calls[0];
            expect(url).toBe('/upload');
            expect(options).toEqual(
                expect.objectContaining({
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        Authorization: 'Bearer test',
                        'X-CSRF-Token': 'csrf',
                    },
                })
            );
            const body = options?.body as FormData;
            expect(body).toBeInstanceOf(FormData);
            expect(body.get('filename')).toBe('small.txt');
            expect(body.get('fileSize')).toBe('4');
            expect(body.get('mimeType')).toBe('text/plain');
            expect(body.get('uploader')).toBe('documents');
            expect(body.get('policyToken')).toBe('policy');
            expect(body.get('digest')).toMatch(/^[a-f0-9]{64}$/);
            expect(body.get('hashAlgorithm')).toBe('sha256');
            expect(result).toEqual({
                uploadId: 'direct-1',
                token: 'token-1',
                metadata: { filename: 'small.txt', mimeType: 'text/plain', size: 4 },
            });
            expect(onInit).toHaveBeenCalledWith('direct-1', file, false);
            expect(onProgress).toHaveBeenCalledWith('direct-1', 100, 0);
            expect(onComplete).toHaveBeenCalledWith('direct-1', result);
        });

        it('keeps the chunk protocol above the direct threshold', async () => {
            const customFetch = vi
                .fn()
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(createMockInitResponse('chunked-1', 1)),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockResolvedValueOnce({ ok: true } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            uploadId: 'chunked-1',
                            token: 'token-1',
                            meta: { filename: 'large.txt', mimeType: 'text/plain', size: 5 },
                        }),
                } as Response);
            const instance = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                fetch: customFetch,
            });

            await instance.upload(new File(['12345'], 'large.txt', { type: 'text/plain' }));

            expect(customFetch).toHaveBeenCalledTimes(4);
            expect(customFetch.mock.calls[0][0]).toBe('/upload/init');
            expect(customFetch.mock.calls.some(([url]) => url === '/upload')).toBe(false);
        });

        it('falls back to chunks only after a 413 response', async () => {
            const onInit = vi.fn();
            const customFetch = vi
                .fn()
                .mockResolvedValueOnce({ ok: false, status: 413 } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(createMockInitResponse('fallback-1', 1)),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockResolvedValueOnce({ ok: true } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            uploadId: 'fallback-1',
                            token: 'token-1',
                            meta: { filename: 'small.txt', mimeType: 'text/plain', size: 4 },
                        }),
                } as Response);
            const instance = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                fetch: customFetch,
                events: { onInit },
            });

            const result = await instance.upload(new File(['data'], 'small.txt', { type: 'text/plain' }));

            expect(customFetch).toHaveBeenCalledTimes(5);
            expect(customFetch.mock.calls[0][0]).toBe('/upload');
            expect(customFetch.mock.calls[1][0]).toBe('/upload/init');
            expect(onInit).toHaveBeenCalledWith('fallback-1', expect.any(File), true);
            expect(result.uploadId).toBe('fallback-1');
        });

        it('falls back from XMLHttpRequest to chunks only after a 413 response', async () => {
            const xhr = new XhrTestDouble();
            const customFetch = vi
                .fn()
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(createMockInitResponse('fallback-xhr', 1)),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockResolvedValueOnce({ ok: true } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            uploadId: 'fallback-xhr',
                            token: 'token-xhr',
                            meta: { filename: 'small.txt', mimeType: 'text/plain', size: 4 },
                        }),
                } as Response);
            const instance = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                fetch: customFetch,
                xhr: xhr.factory(),
            });

            const upload = instance.upload(new File(['data'], 'small.txt', { type: 'text/plain' }));
            await vi.waitFor(() => expect(xhr.body).toBeInstanceOf(FormData));
            xhr.respond(413, { error: 'Payload Too Large' });

            await expect(upload).resolves.toEqual(
                expect.objectContaining({ uploadId: 'fallback-xhr', token: 'token-xhr' })
            );
            expect(customFetch).toHaveBeenCalledTimes(4);
            expect(customFetch.mock.calls[0][0]).toBe('/upload/init');
        });

        it('does not replay validation or ambiguous network failures', async () => {
            const validationFetch = vi.fn().mockResolvedValue({
                ok: false,
                status: 422,
                json: () => Promise.resolve({ error: 'Rejected MIME type' }),
            } as Response);
            const validationUploader = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                fetch: validationFetch,
            });
            const file = new File(['data'], 'small.txt', { type: 'text/plain' });

            await expect(validationUploader.upload(file)).rejects.toThrow('Rejected MIME type');
            expect(validationFetch).toHaveBeenCalledTimes(1);

            const networkFetch = vi.fn().mockRejectedValue(new TypeError('Network request failed'));
            const networkUploader = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                fetch: networkFetch,
            });

            await expect(networkUploader.upload(file)).rejects.toThrow('Network request failed');
            expect(networkFetch).toHaveBeenCalledTimes(1);
        });

        it('does not replay XMLHttpRequest validation or ambiguous network failures', async () => {
            const validationXhr = new XhrTestDouble();
            const validationUploader = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                xhr: validationXhr.factory(),
            });
            const file = new File(['data'], 'small.txt', { type: 'text/plain' });
            const validationUpload = validationUploader.upload(file);
            await vi.waitFor(() => expect(validationXhr.body).toBeInstanceOf(FormData));
            validationXhr.respond(422, { error: 'Rejected MIME type' });

            await expect(validationUpload).rejects.toThrow('Rejected MIME type');
            expect(fetch).not.toHaveBeenCalled();

            const networkXhr = new XhrTestDouble();
            const networkUploader = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                xhr: networkXhr.factory(),
            });
            const networkUpload = networkUploader.upload(file);
            await vi.waitFor(() => expect(networkXhr.body).toBeInstanceOf(FormData));
            networkXhr.fail();

            await expect(networkUpload).rejects.toThrow('Direct upload network request failed');
            expect(fetch).not.toHaveBeenCalled();
        });

        it('sends compressed direct bytes when compression is enabled', async () => {
            const customFetch = vi.fn().mockResolvedValue({
                ok: true,
                status: 200,
                json: () =>
                    Promise.resolve({
                        success: true,
                        uploadId: 'compressed-1',
                        token: 'token-1',
                        meta: { filename: 'small.txt', mimeType: 'text/plain', size: 4 },
                    }),
            } as Response);
            const instance = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                compression: true,
                fetch: customFetch,
            });
            const compressed = new Uint8Array([0x1f, 0x8b, 1, 2]);
            vi.spyOn(
                instance as unknown as { compress(data: Uint8Array): Promise<Uint8Array> },
                'compress'
            ).mockResolvedValue(compressed);

            await instance.upload(new File(['data'], 'small.txt', { type: 'text/plain' }));

            const body = customFetch.mock.calls[0][1]?.body as FormData;
            const transmitted = body.get('file') as File;
            expect(new Uint8Array(await transmitted.arrayBuffer())).toEqual(compressed);
            expect(body.get('contentEncoding')).toBe('gzip');
        });

        it('omits the encoding marker when the payload is sent uncompressed', async () => {
            const customFetch = vi.fn().mockResolvedValue({
                ok: true,
                status: 200,
                json: () =>
                    Promise.resolve({
                        success: true,
                        uploadId: 'plain-1',
                        token: 'token-1',
                        meta: { filename: 'small.txt', mimeType: 'text/plain', size: 4 },
                    }),
            } as Response);
            const plain = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                fetch: customFetch,
            });

            await plain.upload(new File(['data'], 'small.txt', { type: 'text/plain' }));

            const plainBody = customFetch.mock.calls[0][1]?.body as FormData;
            expect(plainBody.get('contentEncoding')).toBeNull();

            // Compression enabled, but the payload falls back to the raw bytes.
            customFetch.mockClear();
            const failing = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                compression: true,
                fetch: customFetch,
            });
            vi.spyOn(
                failing as unknown as { compress(data: Uint8Array): Promise<Uint8Array> },
                'compress'
            ).mockRejectedValue(new Error('CompressionStream unavailable'));

            await failing.upload(new File(['data'], 'small.txt', { type: 'text/plain' }));

            const fallbackBody = customFetch.mock.calls[0][1]?.body as FormData;
            expect(fallbackBody.get('contentEncoding')).toBeNull();
            expect(new Uint8Array(await (fallbackBody.get('file') as File).arrayBuffer())).toEqual(
                new Uint8Array(await new Blob(['data']).arrayBuffer())
            );
        });

        it('can abort the direct fetch locally', async () => {
            const customFetch = vi.fn(
                (_url: RequestInfo | URL, options?: RequestInit) =>
                    new Promise<Response>((_resolve, reject) => {
                        options?.signal?.addEventListener('abort', () => {
                            reject(new DOMException('Aborted', 'AbortError'));
                        });
                    })
            );
            const instance = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                fetch: customFetch,
            });
            const file = new File(['data'], 'small.txt', { type: 'text/plain' });
            const upload = instance.upload(file);
            await vi.waitFor(() => expect(customFetch).toHaveBeenCalledTimes(1));

            instance.cancelFile(file);

            await expect(upload).rejects.toBeInstanceOf(UploadCancelledError);
        });

        it('can abort the direct XMLHttpRequest locally', async () => {
            const xhr = new XhrTestDouble();
            const instance = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                xhr: xhr.factory(),
            });
            const file = new File(['data'], 'small.txt', { type: 'text/plain' });
            const upload = instance.upload(file);
            await vi.waitFor(() => expect(xhr.body).toBeInstanceOf(FormData));

            instance.cancelFile(file);

            expect(xhr.aborted).toBe(true);
            await expect(upload).rejects.toBeInstanceOf(UploadCancelledError);
        });
    });

    describe('initializeUpload', () => {
        it('should send file metadata to init endpoint', async () => {
            const file = createMockFile('test.txt', 100);
            const initResponse = createMockInitResponse('upload-123', 1);

            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(initResponse),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            meta: {
                                filename: 'test.txt',
                                size: 100,
                                mimeType: 'text/plain',
                            },
                        }),
                } as Response);

            await uploader.upload(file);

            // Verify the init call was made with correct metadata
            // (body may optionally include a hash from Web Crypto API)
            expect(fetch).toHaveBeenCalledWith(
                mockInitUrl,
                expect.objectContaining({
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                })
            );

            const initCall = vi.mocked(fetch).mock.calls[0];
            const body = JSON.parse(initCall[1]?.body as string);
            expect(body).toMatchObject({
                filename: 'test.txt',
                fileSize: 100,
                mimeType: 'text/plain',
            });
        });

        it('should throw error if init fails', async () => {
            const file = createMockFile('test.txt', 100);

            vi.mocked(fetch).mockResolvedValueOnce({
                ok: false,
                json: () => Promise.resolve({ error: 'File too large' }),
            } as Response);

            await expect(uploader.upload(file)).rejects.toThrow('File too large');
        });
    });

    describe('checkResume', () => {
        it('should return existing chunk indices', async () => {
            const file = createMockFile('test.txt', 10 * 1024 * 1024);
            const initResponse = createMockInitResponse('upload-123', 2);

            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(initResponse),
                } as Response)
                // Status check returns first chunk already uploaded
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [0] } }),
                } as Response)
                // Only second chunk upload
                .mockResolvedValueOnce({
                    ok: true,
                } as Response)
                // Complete
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            meta: {
                                filename: 'test.txt',
                                size: 10 * 1024 * 1024,
                                mimeType: 'text/plain',
                            },
                        }),
                } as Response);

            await uploader.upload(file);

            // Should have called 4 times: init, status, 1 chunk, complete
            expect(fetch).toHaveBeenCalledTimes(4);
        });
    });

    describe('uploadChunks', () => {
        it('should upload all chunks', async () => {
            const file = createMockFile('test.txt', 12 * 1024 * 1024); // 3 chunks
            const initResponse = createMockInitResponse('upload-123', 3);

            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(initResponse),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                // 3 chunk uploads + complete (catch-all)
                .mockResolvedValue({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            token: 'tok-123',
                            meta: { filename: 'test.txt', mimeType: 'text/plain', size: 12 * 1024 * 1024 },
                        }),
                } as Response);

            await uploader.upload(file);

            // init + status + 3 chunks + complete = 6
            expect(fetch).toHaveBeenCalledTimes(6);
        });

        it('should report progress', async () => {
            const onProgress = vi.fn();
            uploader = new Uploader(mockInitUrl, { onProgress });

            const file = createMockFile('test.txt', 10 * 1024 * 1024);
            const initResponse = createMockInitResponse('upload-123', 2);

            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(initResponse),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockResolvedValue({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            meta: {
                                filename: 'test.txt',
                                size: 10 * 1024 * 1024,
                                mimeType: 'text/plain',
                            },
                        }),
                } as Response);

            await uploader.upload(file);

            expect(onProgress).toHaveBeenCalled();
        });
    });

    describe('parallel chunks', () => {
        it('keeps every slot busy instead of waiting for the slowest chunk', async () => {
            const file = createMockFile('large.bin', 1000, 'image/jpeg');
            const initResponse: InitResponse = {
                uploadId: 'upload-pool',
                uploadUrl: '/upload/upload-pool?sig=test',
                config: { chunkSize: 200, totalChunks: 5, compression: false, parallel: 2 },
            };

            let inFlight = 0;
            let maxInFlight = 0;
            let chunkZeroDone = false;
            const startedWhileChunkZeroRan: number[] = [];

            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(initResponse),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockImplementation((_url, options) => {
                    const headers = (options as RequestInit)?.headers as Record<string, string> | undefined;
                    const index = Number(headers?.['X-Chunk-Index'] ?? -1);

                    // The completion request carries no chunk index.
                    if (index < 0) {
                        return Promise.resolve({
                            ok: true,
                            json: () =>
                                Promise.resolve({
                                    success: true,
                                    meta: { filename: 'large.bin', size: 1000, mimeType: 'image/jpeg' },
                                }),
                        } as Response);
                    }

                    inFlight++;
                    maxInFlight = Math.max(maxInFlight, inFlight);
                    if (0 !== index && !chunkZeroDone) {
                        startedWhileChunkZeroRan.push(index);
                    }

                    return new Promise((resolve) => {
                        setTimeout(
                            () => {
                                inFlight--;
                                if (0 === index) {
                                    chunkZeroDone = true;
                                }
                                resolve({ ok: true } as Response);
                            },
                            0 === index ? 50 : 1
                        );
                    });
                });

            await uploader.upload(file);

            expect(maxInFlight).toBeLessThanOrEqual(2);
            // Chunk 0 holds one slot for the whole upload: the other slot must run
            // every remaining chunk meanwhile. Fixed batches would stop after 1.
            expect(startedWhileChunkZeroRan).toEqual([1, 2, 3, 4]);
        });
    });

    describe('cancel', () => {
        it('should abort upload when cancel is called', async () => {
            // Use image/jpeg to skip compression (avoids slow stream processing in JSDOM)
            const file = createMockFile('large.bin', 1000, 'image/jpeg');
            const initResponse: InitResponse = {
                uploadId: 'upload-123',
                uploadUrl: '/upload/upload-123?sig=test',
                config: { chunkSize: 200, totalChunks: 5, compression: false, parallel: 2 },
            };

            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(initResponse),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                // Simulate slow uploads that respect AbortSignal
                .mockImplementation(
                    (_url, options) =>
                        new Promise((resolve, reject) => {
                            const signal = (options as RequestInit)?.signal;
                            if (signal?.aborted) {
                                reject(new DOMException('The operation was aborted.', 'AbortError'));
                                return;
                            }
                            const timer = setTimeout(() => {
                                resolve({ ok: true } as Response);
                            }, 1000);
                            signal?.addEventListener('abort', () => {
                                clearTimeout(timer);
                                reject(new DOMException('The operation was aborted.', 'AbortError'));
                            });
                        })
                );

            const uploadPromise = uploader.upload(file);

            // Cancel after a short delay
            setTimeout(() => {
                uploader.cancel('upload-123');
            }, 100);

            await expect(uploadPromise).rejects.toThrow('Upload cancelled');
        });

        it('suspends local work without deleting the resumable server session', async () => {
            const file = createMockFile('large.bin', 1000, 'image/jpeg');
            const initResponse: InitResponse = {
                uploadId: 'upload-suspend',
                uploadUrl: '/upload/upload-suspend?sig=test',
                resumeToken: 'resume-token',
                config: { chunkSize: 200, totalChunks: 5, compression: false, parallel: 2 },
            };
            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(initResponse),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockImplementation(
                    (_url, options) =>
                        new Promise((resolve, reject) => {
                            const signal = (options as RequestInit)?.signal;
                            const timer = setTimeout(() => resolve({ ok: true } as Response), 1000);
                            signal?.addEventListener('abort', () => {
                                clearTimeout(timer);
                                reject(new DOMException('The operation was aborted.', 'AbortError'));
                            });
                        })
                );

            const promise = uploader.upload(file);
            setTimeout(() => uploader.suspend('upload-suspend'), 50);

            await expect(promise).rejects.toBeInstanceOf(UploadSuspendedError);
            expect(vi.mocked(fetch).mock.calls.some(([, options]) => options?.method === 'DELETE')).toBe(false);
        });

        it('settles a paused upload when it is suspended', async () => {
            const file = createMockFile('paused.bin', 400, 'image/jpeg');
            const customFetch = vi.fn((url: RequestInfo | URL, options?: RequestInit) => {
                const method = options?.method ?? 'GET';
                if (method === 'POST' && String(url).endsWith('/init')) {
                    return Promise.resolve({
                        ok: true,
                        json: () =>
                            Promise.resolve({
                                uploadId: 'upload-paused',
                                uploadUrl: '/upload/upload-paused?sig=test',
                                config: { chunkSize: 200, totalChunks: 2, compression: false, parallel: 1 },
                            }),
                    } as Response);
                }
                if (method === 'GET') {
                    return Promise.resolve({
                        ok: true,
                        json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                    } as Response);
                }
                if (method === 'PUT') {
                    // Pause between the two batches, so the loop parks in waitIfPaused.
                    instance.pause('upload-paused');
                    return Promise.resolve({ ok: true } as Response);
                }

                return Promise.resolve({ ok: true } as Response);
            });
            const instance = new Uploader({ initUrl: '/upload/init', fetch: customFetch });

            const promise = instance.upload(file);
            await vi.waitFor(() => expect(instance.isPaused('upload-paused')).toBe(true));
            // Let the chunk loop reach waitIfPaused before releasing it.
            await flushTasks();

            instance.suspend('upload-paused');

            await expect(promise).rejects.toBeInstanceOf(UploadSuspendedError);
            expect(customFetch.mock.calls.filter(([, options]) => options?.method === 'PUT')).toHaveLength(1);
        });

        it('stops the retry backoff instead of waiting for the next attempt', async () => {
            vi.useFakeTimers({ toFake: ['setTimeout', 'clearTimeout'] });

            const customFetch = vi.fn((url: RequestInfo | URL, options?: RequestInit) => {
                const method = options?.method ?? 'GET';
                if (method === 'POST' && String(url).endsWith('/init')) {
                    return Promise.resolve({
                        ok: true,
                        json: () => Promise.resolve(createMockInitResponse('upload-backoff', 1)),
                    } as Response);
                }
                if (method === 'GET') {
                    return Promise.resolve({
                        ok: true,
                        json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                    } as Response);
                }
                if (method === 'PUT') {
                    return Promise.resolve({ ok: false, statusText: 'Server Error' } as Response);
                }

                return Promise.resolve({ ok: true } as Response);
            });
            const instance = new Uploader({ initUrl: '/upload/init', fetch: customFetch });

            const promise = instance.upload(createMockFile('retry.bin', 100, 'image/jpeg'));
            await waitForPendingTimer();

            instance.cancel('upload-backoff');

            await expect(promise).rejects.toBeInstanceOf(UploadCancelledError);
            expect(vi.getTimerCount()).toBe(0);
            expect(customFetch.mock.calls.filter(([, options]) => options?.method === 'PUT')).toHaveLength(1);
        });
    });

    describe('resume fingerprint', () => {
        it('is scoped by uploader and signed form policy', async () => {
            const file = createMockFile('same.txt', 100);
            const fingerprint = (instance: Uploader) =>
                (
                    instance as unknown as {
                        fingerprint(file: File): Promise<string>;
                    }
                ).fingerprint(file);
            const first = new Uploader({ initUrl: '/upload/init', uploader: 'documents', policyToken: 'policy-a' });
            const same = new Uploader({ initUrl: '/upload/init', uploader: 'documents', policyToken: 'policy-a' });
            const otherUploader = new Uploader({
                initUrl: '/upload/init',
                uploader: 'avatars',
                policyToken: 'policy-a',
            });
            const otherPolicy = new Uploader({
                initUrl: '/upload/init',
                uploader: 'documents',
                policyToken: 'policy-b',
            });

            expect(await fingerprint(first)).toBe(await fingerprint(same));
            expect(await fingerprint(first)).not.toBe(await fingerprint(otherUploader));
            expect(await fingerprint(first)).not.toBe(await fingerprint(otherPolicy));
        });
    });

    describe('remove', () => {
        it('deletes a completed temporary upload through the dedicated endpoint', async () => {
            const fetcher = vi.fn().mockResolvedValue({ ok: true } as Response);
            const instance = new Uploader({
                initUrl: '/upload/init',
                removeUrl: '/upload/remove-custom',
                csrfToken: 'csrf',
                fetch: fetcher,
            });

            await instance.remove('completed-token');

            expect(fetcher).toHaveBeenCalledWith(
                '/upload/remove-custom',
                expect.objectContaining({
                    method: 'DELETE',
                    headers: expect.objectContaining({
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': 'csrf',
                    }),
                    body: JSON.stringify({ token: 'completed-token' }),
                })
            );
        });

        it('derives the default remove endpoint from the init endpoint', async () => {
            const fetcher = vi.fn().mockResolvedValue({ ok: true } as Response);
            const instance = new Uploader({
                initUrl: '/upload/init',
                fetch: fetcher,
            });

            await instance.remove('completed-token');

            expect(fetcher).toHaveBeenCalledWith('/upload/remove', expect.objectContaining({ method: 'DELETE' }));
        });

        it('reports a remove-specific fallback error', async () => {
            const fetcher = vi.fn().mockResolvedValue({
                ok: false,
                json: () => Promise.reject(new SyntaxError('Invalid JSON')),
            } as Response);
            const instance = new Uploader({
                initUrl: '/upload/init',
                fetch: fetcher,
            });

            await expect(instance.remove('completed-token')).rejects.toThrow('Failed to remove upload');
        });
    });

    describe('completeUpload', () => {
        it('should return upload result on success', async () => {
            const file = createMockFile('test.txt', 100, 'image/jpeg');
            const initResponse = createMockInitResponse('upload-123', 1);

            const fileMetadata = {
                filename: 'test.txt',
                size: 100,
                mimeType: 'image/jpeg',
            };

            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(initResponse),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            token: 'tok-abc',
                            meta: fileMetadata,
                        }),
                } as Response);

            const result = await uploader.upload(file);

            expect(result).toEqual({ uploadId: 'upload-123', token: 'tok-abc', metadata: fileMetadata });
        });

        it('should call onComplete callback', async () => {
            const onComplete = vi.fn();
            uploader = new Uploader(mockInitUrl, { onComplete });

            const file = createMockFile('test.txt', 100);
            const initResponse = createMockInitResponse('upload-123', 1);

            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(initResponse),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            token: 'tok-abc',
                            meta: {
                                filename: 'test.txt',
                                size: 100,
                                mimeType: 'text/plain',
                            },
                        }),
                } as Response);

            await uploader.upload(file);

            expect(onComplete).toHaveBeenCalledWith(
                'upload-123',
                expect.objectContaining({
                    token: 'tok-abc',
                    metadata: expect.objectContaining({ filename: 'test.txt' }),
                })
            );
        });

        it('aborts the completion request when the upload is cancelled', async () => {
            const onComplete = vi.fn();
            let completionStarted!: () => void;
            const completionRequested = new Promise<void>((resolve) => {
                completionStarted = resolve;
            });
            const customFetch = vi.fn((url: RequestInfo | URL, options?: RequestInit) => {
                const method = options?.method ?? 'GET';
                if (method === 'POST' && String(url).endsWith('/init')) {
                    return Promise.resolve({
                        ok: true,
                        json: () => Promise.resolve(createMockInitResponse('upload-finalizing', 1)),
                    } as Response);
                }
                if (method === 'GET') {
                    return Promise.resolve({
                        ok: true,
                        json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                    } as Response);
                }
                if (method === 'PUT') {
                    return Promise.resolve({ ok: true } as Response);
                }
                if (method === 'DELETE') {
                    return Promise.resolve({ ok: true } as Response);
                }

                completionStarted();

                return pendingUntilAborted(options);
            });
            const instance = new Uploader({
                initUrl: '/upload/init',
                fetch: customFetch,
                events: { onComplete },
            });

            const promise = instance.upload(createMockFile('finalizing.bin', 100, 'image/jpeg'));
            await completionRequested;

            instance.cancel('upload-finalizing');

            await expect(promise).rejects.toBeInstanceOf(UploadCancelledError);
            expect(onComplete).not.toHaveBeenCalled();
        });

        it('does not complete an upload cancelled while the completion response was in flight', async () => {
            const onComplete = vi.fn();
            let releaseCompletion!: () => void;
            let completionStarted!: () => void;
            const completionRequested = new Promise<void>((resolve) => {
                completionStarted = resolve;
            });
            // The completion response ignores the signal, mimicking a request
            // that already reached the server when the user hits cancel.
            const customFetch = vi.fn((url: RequestInfo | URL, options?: RequestInit) => {
                const method = options?.method ?? 'GET';
                if (method === 'POST' && String(url).endsWith('/init')) {
                    return Promise.resolve({
                        ok: true,
                        json: () => Promise.resolve(createMockInitResponse('upload-racing', 1)),
                    } as Response);
                }
                if (method === 'GET') {
                    return Promise.resolve({
                        ok: true,
                        json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                    } as Response);
                }
                if (method === 'PUT' || method === 'DELETE') {
                    return Promise.resolve({ ok: true } as Response);
                }

                completionStarted();

                return new Promise<Response>((resolve) => {
                    releaseCompletion = () =>
                        resolve({
                            ok: true,
                            json: () =>
                                Promise.resolve({
                                    success: true,
                                    uploadId: 'upload-racing',
                                    token: 'tok-racing',
                                    meta: { filename: 'racing.bin', mimeType: 'image/jpeg', size: 100 },
                                }),
                        } as Response);
                });
            });
            const instance = new Uploader({
                initUrl: '/upload/init',
                fetch: customFetch,
                events: { onComplete },
            });

            const promise = instance.upload(createMockFile('racing.bin', 100, 'image/jpeg'));
            await completionRequested;

            instance.cancel('upload-racing');
            releaseCompletion();

            await expect(promise).rejects.toBeInstanceOf(UploadCancelledError);
            expect(onComplete).not.toHaveBeenCalled();
        });
    });

    describe('error handling', () => {
        it('should call onError callback on chunk failure', async () => {
            vi.useFakeTimers({ toFake: ['setTimeout'] });

            const onError = vi.fn();
            uploader = new Uploader(mockInitUrl, { onError });

            const file = createMockFile('test.txt', 100);
            const initResponse = createMockInitResponse('upload-123', 1);

            vi.mocked(fetch)
                // init succeeds
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(initResponse),
                } as Response)
                // resume check
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                // chunk fails repeatedly (exhausts retries)
                .mockResolvedValue({
                    ok: false,
                    statusText: 'Server Error',
                } as Response);

            const upload = uploader.upload(file).catch((error: unknown) => error);
            await advanceNextRetry();
            await advanceNextRetry();
            await advanceNextRetry();

            expect(await upload).toBeInstanceOf(Error);

            expect(onError).toHaveBeenCalledWith('upload-123', expect.any(Error));
            expect(fetch).toHaveBeenCalledTimes(6);
        });

        it('should retry failed chunks', async () => {
            vi.useFakeTimers({ toFake: ['setTimeout'] });

            const file = createMockFile('test.txt', 100);
            const initResponse = createMockInitResponse('upload-123', 1);

            let chunkAttempts = 0;

            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(initResponse),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                // First chunk attempt fails
                .mockImplementationOnce(() => {
                    chunkAttempts++;
                    return Promise.resolve({ ok: false, statusText: 'Server Error' } as Response);
                })
                // Second chunk attempt succeeds
                .mockResolvedValueOnce({
                    ok: true,
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            meta: {
                                filename: 'test.txt',
                                size: 100,
                                mimeType: 'text/plain',
                            },
                        }),
                } as Response);

            const upload = uploader.upload(file);
            await advanceNextRetry();
            await upload;

            expect(chunkAttempts).toBe(1);
            expect(fetch).toHaveBeenCalledTimes(5);
        });

        it('does not retry a chunk rejected with a 4xx', async () => {
            // Fake timers are never advanced here: a retry would leave the upload
            // pending on its backoff instead of rejecting.
            vi.useFakeTimers({ toFake: ['setTimeout'] });

            const file = createMockFile('test.txt', 100);
            const initResponse = createMockInitResponse('upload-403', 1);

            let chunkAttempts = 0;

            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve(initResponse),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockImplementation(() => {
                    chunkAttempts++;
                    return Promise.resolve({ ok: false, status: 403, statusText: 'Forbidden' } as Response);
                });

            await expect(uploader.upload(file)).rejects.toThrow();

            expect(chunkAttempts).toBe(1);
        });
    });
});
