import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { Uploader, UploadCancelledError, UploadSuspendedError } from '../../src/uploader';
import {
    chunkedResponses,
    initResponse,
    okEmpty,
    okJson,
    pendingUntilAborted,
    queueResponses,
    routedFetch,
    statusResponse,
} from './fixtures';
import { XhrTestDouble } from './xhr_test_double';

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

describe('Uploader', () => {
    const mockInitUrl = '/upload/init';
    let uploader: Uploader;

    const createMockFile = (name: string, size: number, type: string = 'text/plain'): File => {
        return new File([new Uint8Array(size)], name, { type });
    };

    beforeEach(() => {
        uploader = new Uploader(mockInitUrl);
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    type FetchMock = { mock: { calls: unknown[][] } };

    const putCalls = (fetchMock: FetchMock) =>
        (fetchMock.mock.calls as [RequestInfo | URL, RequestInit?][]).filter(
            ([, options]) => options?.method === 'PUT'
        );

    const chunkIndices = (fetchMock: FetchMock) =>
        putCalls(fetchMock)
            .map(([, options]) => (options as RequestInit).headers as Record<string, string>)
            .map((headers) => headers['X-Chunk-Index'])
            .sort();

    const gunzip = async (bytes: Uint8Array<ArrayBuffer>): Promise<string> => {
        const stream = new ReadableStream<BufferSource>({
            start(controller) {
                controller.enqueue(bytes);
                controller.close();
            },
        }).pipeThrough(new DecompressionStream('gzip'));
        const reader = stream.getReader();
        let text = '';
        const decoder = new TextDecoder();
        for (let chunk = await reader.read(); !chunk.done; chunk = await reader.read()) {
            text += decoder.decode(chunk.value, { stream: true });
        }

        return text + decoder.decode();
    };

    describe('constructor', () => {
        it('accepts named transport options', async () => {
            const customFetch = queueResponses(
                vi.fn(),
                chunkedResponses(initResponse('upload-123'), {
                    token: 'tok-123',
                    meta: { filename: 'test.txt', mimeType: 'text/plain', size: 100 },
                })
            );
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
            for (const [, options] of customFetch.mock.calls) {
                expect(options).toEqual(
                    expect.objectContaining({
                        credentials: 'include',
                        headers: expect.objectContaining({ Authorization: 'Bearer token' }),
                    })
                );
            }
        });

        it('sends CSRF on every mutation and keeps GET requests header-free', async () => {
            const customFetch = queueResponses(
                vi.fn(),
                chunkedResponses(initResponse('upload-csrf'), {
                    token: 'tok-csrf',
                    meta: { filename: 'test.txt', mimeType: 'text/plain', size: 100 },
                })
            );
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
            const customFetch = queueResponses(
                vi.fn(),
                chunkedResponses(initResponse('upload-no-compression', { compression: true }), {
                    token: 'tok',
                    meta: { filename: 'test.txt', mimeType: 'text/plain', size: 100 },
                })
            );
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
            // Exact on purpose: a Content-Type header would drop the multipart boundary
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
            expect(onDirectProgress.mock.calls[0][0]).toBe(file);

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
                    // Exact on purpose: a Content-Type header would drop the multipart boundary
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
            expect(onInit.mock.calls[0][1]).toBe(file);
            expect(onProgress).toHaveBeenCalledWith('direct-1', 100, 0);
            expect(onComplete).toHaveBeenCalledWith('direct-1', result);
        });

        it('keeps the chunk protocol above the direct threshold', async () => {
            const customFetch = queueResponses(
                vi.fn(),
                chunkedResponses(initResponse('chunked-1'), {
                    success: true,
                    uploadId: 'chunked-1',
                    token: 'token-1',
                    meta: { filename: 'large.txt', mimeType: 'text/plain', size: 5 },
                })
            );
            const instance = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                fetch: customFetch,
            });

            await instance.upload(new File(['12345'], 'large.txt', { type: 'text/plain' }));

            expect(customFetch.mock.calls[0][0]).toBe('/upload/init');
            expect(chunkIndices(customFetch)).toEqual(['0']);
            expect(customFetch.mock.calls.some(([url]) => url === '/upload')).toBe(false);
        });

        it('falls back to chunks only after a 413 response', async () => {
            const onInit = vi.fn();
            const customFetch = queueResponses(vi.fn(), [
                { ok: false, status: 413 } as Response,
                ...chunkedResponses(initResponse('fallback-1'), {
                    success: true,
                    uploadId: 'fallback-1',
                    token: 'token-1',
                    meta: { filename: 'small.txt', mimeType: 'text/plain', size: 4 },
                }),
            ]);
            const instance = new Uploader({
                initUrl: '/upload/init',
                directUrl: '/upload',
                directUploadThreshold: 4,
                fetch: customFetch,
                events: { onInit },
            });

            const file = new File(['data'], 'small.txt', { type: 'text/plain' });

            const result = await instance.upload(file);

            expect(customFetch.mock.calls[0][0]).toBe('/upload');
            expect(customFetch.mock.calls.filter(([url]) => url === '/upload')).toHaveLength(1);
            expect(customFetch.mock.calls[1][0]).toBe('/upload/init');
            expect(onInit).toHaveBeenCalledWith('fallback-1', file, true);
            expect(onInit.mock.calls[0][1]).toBe(file);
            expect(result.uploadId).toBe('fallback-1');
        });

        it('falls back from XMLHttpRequest to chunks only after a 413 response', async () => {
            const xhr = new XhrTestDouble();
            const customFetch = queueResponses(
                vi.fn(),
                chunkedResponses(initResponse('fallback-xhr'), {
                    success: true,
                    uploadId: 'fallback-xhr',
                    token: 'token-xhr',
                    meta: { filename: 'small.txt', mimeType: 'text/plain', size: 4 },
                })
            );
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
            expect(customFetch.mock.calls[0][0]).toBe('/upload/init');
            expect(customFetch.mock.calls.some(([url]) => url === '/upload')).toBe(false);
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
            await instance.upload(new File(['data'], 'small.txt', { type: 'text/plain' }));

            const body = customFetch.mock.calls[0][1]?.body as FormData;
            const transmitted = new Uint8Array(await (body.get('file') as File).arrayBuffer());
            expect(Array.from(transmitted.subarray(0, 2))).toEqual([0x1f, 0x8b]);
            expect(await gunzip(transmitted)).toBe('data');
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
            vi.stubGlobal(
                'CompressionStream',
                class {
                    constructor() {
                        throw new Error('CompressionStream unavailable');
                    }
                }
            );

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

    describe('chunked upload', () => {
        it('initializes, completes and reports the result', async () => {
            const onComplete = vi.fn();
            uploader = new Uploader(mockInitUrl, { onComplete });
            const file = createMockFile('test.txt', 100);
            const metadata = { filename: 'test.txt', size: 100, mimeType: 'text/plain' };

            queueResponses(
                vi.mocked(fetch),
                chunkedResponses(initResponse('upload-123'), { success: true, token: 'tok-abc', meta: metadata })
            );

            const result = await uploader.upload(file);

            expect(fetch).toHaveBeenCalledWith(
                mockInitUrl,
                expect.objectContaining({
                    method: 'POST',
                    headers: expect.objectContaining({ 'Content-Type': 'application/json' }),
                })
            );
            const body = JSON.parse(vi.mocked(fetch).mock.calls[0][1]?.body as string);
            expect(body).toMatchObject({ filename: 'test.txt', fileSize: 100, mimeType: 'text/plain' });
            expect(result).toEqual({ uploadId: 'upload-123', token: 'tok-abc', metadata });
            expect(onComplete).toHaveBeenCalledWith('upload-123', result);
        });

        it('throws when init fails', async () => {
            const file = createMockFile('test.txt', 100);

            vi.mocked(fetch).mockResolvedValueOnce({
                ok: false,
                json: () => Promise.resolve({ error: 'File too large' }),
            } as Response);

            await expect(uploader.upload(file)).rejects.toThrow('File too large');
        });
    });

    describe('checkResume', () => {
        it('sends only the chunks the server does not have yet', async () => {
            const file = createMockFile('test.txt', 10 * 1024 * 1024);
            queueResponses(
                vi.mocked(fetch),
                chunkedResponses(
                    initResponse('upload-123', { totalChunks: 2 }),
                    { success: true, meta: { filename: 'test.txt', size: 10 * 1024 * 1024, mimeType: 'text/plain' } },
                    [0]
                )
            );

            await uploader.upload(file);

            expect(chunkIndices(vi.mocked(fetch))).toEqual(['1']);
        });
    });

    describe('uploadChunks', () => {
        it('uploads every chunk and reports progress after each one', async () => {
            const onProgress = vi.fn();
            uploader = new Uploader(mockInitUrl, { onProgress });
            const file = createMockFile('test.txt', 12 * 1024 * 1024);
            queueResponses(
                vi.mocked(fetch),
                chunkedResponses(initResponse('upload-123', { totalChunks: 3 }), {
                    success: true,
                    token: 'tok-123',
                    meta: { filename: 'test.txt', mimeType: 'text/plain', size: 12 * 1024 * 1024 },
                })
            );

            await uploader.upload(file);

            expect(chunkIndices(vi.mocked(fetch))).toEqual(['0', '1', '2']);
            expect(onProgress.mock.calls.map(([, percent]) => percent)).toEqual([33, 67, 100]);
        });
    });

    describe('parallel chunks', () => {
        it('keeps every slot busy instead of waiting for the slowest chunk', async () => {
            const file = createMockFile('large.bin', 1000, 'image/jpeg');
            const init = initResponse('upload-pool', { chunkSize: 200, totalChunks: 5, parallel: 2 });

            let inFlight = 0;
            let maxInFlight = 0;
            let chunkZeroDone = false;
            const startedWhileChunkZeroRan: number[] = [];

            queueResponses(vi.mocked(fetch), [okJson(init), statusResponse()]).mockImplementation((_url, options) => {
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
        it('aborts the upload when cancel is called', async () => {
            const file = createMockFile('large.bin', 1000, 'image/jpeg');
            vi.mocked(fetch).mockImplementation(
                routedFetch({
                    init: () => okJson(initResponse('upload-123', { chunkSize: 200, totalChunks: 5, parallel: 2 })),
                    put: pendingUntilAborted,
                    complete: okEmpty,
                })
            );

            const uploadPromise = uploader.upload(file);
            await vi.waitFor(() => expect(putCalls(vi.mocked(fetch))).toHaveLength(2));

            uploader.cancel('upload-123');

            await expect(uploadPromise).rejects.toThrow('Upload cancelled');
            expect(fetch).toHaveBeenCalledWith(
                '/upload/upload-123?sig=test',
                expect.objectContaining({ method: 'DELETE' })
            );
        });

        it('suspends local work without deleting the resumable server session', async () => {
            const file = createMockFile('large.bin', 1000, 'image/jpeg');
            vi.mocked(fetch).mockImplementation(
                routedFetch({
                    init: () =>
                        okJson({
                            ...initResponse('upload-suspend', { chunkSize: 200, totalChunks: 5, parallel: 2 }),
                            resumeToken: 'resume-token',
                        }),
                    put: pendingUntilAborted,
                    complete: okEmpty,
                })
            );

            const promise = uploader.upload(file);
            await vi.waitFor(() => expect(putCalls(vi.mocked(fetch))).toHaveLength(2));

            uploader.suspend('upload-suspend');

            await expect(promise).rejects.toBeInstanceOf(UploadSuspendedError);
            expect(vi.mocked(fetch).mock.calls.some(([, options]) => options?.method === 'DELETE')).toBe(false);
        });

        it('settles a paused upload when it is suspended', async () => {
            const file = createMockFile('paused.bin', 400, 'image/jpeg');
            const customFetch = vi.fn(
                routedFetch({
                    init: () => okJson(initResponse('upload-paused', { chunkSize: 200, totalChunks: 2, parallel: 1 })),
                    put: () => {
                        // Pause between the two chunks, so the loop parks in waitIfPaused.
                        instance.pause('upload-paused');

                        return okEmpty();
                    },
                    complete: okEmpty,
                })
            );
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

            const customFetch = vi.fn(
                routedFetch({
                    init: () => okJson(initResponse('upload-backoff')),
                    put: () => ({ ok: false, statusText: 'Server Error' }) as Response,
                    complete: okEmpty,
                })
            );
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

        it('survives a page reload that signs the same policy again', async () => {
            const file = createMockFile('large.bin', 100);
            const fingerprint = (policyToken: string) =>
                (
                    new Uploader({ initUrl: '/upload/init', uploader: 'large', policyToken }) as unknown as {
                        fingerprint(file: File): Promise<string>;
                    }
                ).fingerprint(file);
            const policy = (expiresAt: number, hash: string, field = 'upload_form[file]') =>
                `_expiration=${expiresAt}&_hash=${hash}&e=${expiresAt}&f=${encodeURIComponent(field)}&n=1&s=67108864&t=application%2Foctet-stream&u=large`;

            // Each render signs the policy with a new expiry, hence a new hash.
            expect(await fingerprint(policy(1790284638, 'rtJoCemk'))).toBe(
                await fingerprint(policy(1790284639, 'ODbSHYhR'))
            );
            // Another field is another policy.
            expect(await fingerprint(policy(1790284638, 'rtJoCemk'))).not.toBe(
                await fingerprint(policy(1790284638, 'rtJoCemk', 'other_form[file]'))
            );
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
        it('aborts the completion request when the upload is cancelled', async () => {
            const onComplete = vi.fn();
            let completionStarted!: () => void;
            const completionRequested = new Promise<void>((resolve) => {
                completionStarted = resolve;
            });
            const customFetch = vi.fn(
                routedFetch({
                    init: () => okJson(initResponse('upload-finalizing')),
                    complete: (url, options) => {
                        completionStarted();

                        return pendingUntilAborted(url, options);
                    },
                })
            );
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
            const customFetch = vi.fn(
                routedFetch({
                    init: () => okJson(initResponse('upload-racing')),
                    complete: () => {
                        completionStarted();

                        return new Promise<Response>((resolve) => {
                            releaseCompletion = () =>
                                resolve(
                                    okJson({
                                        success: true,
                                        uploadId: 'upload-racing',
                                        token: 'tok-racing',
                                        meta: { filename: 'racing.bin', mimeType: 'image/jpeg', size: 100 },
                                    })
                                );
                        });
                    },
                })
            );
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
        it('calls onError when a chunk keeps failing', async () => {
            vi.useFakeTimers({ toFake: ['setTimeout'] });

            const onError = vi.fn();
            uploader = new Uploader(mockInitUrl, { onError });

            const file = createMockFile('test.txt', 100);

            queueResponses(vi.mocked(fetch), [okJson(initResponse('upload-123')), statusResponse()])
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
            // the first attempt plus three retries
            expect(putCalls(vi.mocked(fetch))).toHaveLength(4);
        });

        it('retries a failed chunk', async () => {
            vi.useFakeTimers({ toFake: ['setTimeout'] });

            const file = createMockFile('test.txt', 100);

            queueResponses(vi.mocked(fetch), [okJson(initResponse('upload-123')), statusResponse()])
                .mockResolvedValueOnce({ ok: false, statusText: 'Server Error' } as Response)
                .mockResolvedValueOnce(okEmpty())
                .mockResolvedValueOnce(
                    okJson({ success: true, meta: { filename: 'test.txt', size: 100, mimeType: 'text/plain' } })
                );

            const upload = uploader.upload(file);
            await advanceNextRetry();
            await upload;

            expect(putCalls(vi.mocked(fetch))).toHaveLength(2);
        });

        it.each([408, 429])('retries a chunk rejected with a %i', async (status) => {
            vi.useFakeTimers({ toFake: ['setTimeout'] });

            queueResponses(vi.mocked(fetch), [okJson(initResponse('upload-transient')), statusResponse()])
                .mockResolvedValueOnce({ ok: false, status, statusText: 'Try again' } as Response)
                .mockResolvedValueOnce(okEmpty())
                .mockResolvedValueOnce(
                    okJson({ success: true, meta: { filename: 'test.txt', size: 100, mimeType: 'text/plain' } })
                );

            const upload = uploader.upload(createMockFile('test.txt', 100));
            await advanceNextRetry();
            await upload;

            expect(putCalls(vi.mocked(fetch))).toHaveLength(2);
        });

        it('does not retry a chunk rejected with a 4xx', async () => {
            // Fake timers are never advanced here: a retry would leave the upload
            // pending on its backoff instead of rejecting.
            vi.useFakeTimers({ toFake: ['setTimeout'] });

            const file = createMockFile('test.txt', 100);

            queueResponses(vi.mocked(fetch), [okJson(initResponse('upload-403')), statusResponse()]).mockResolvedValue({
                ok: false,
                status: 403,
                statusText: 'Forbidden',
            } as Response);

            await expect(uploader.upload(file)).rejects.toThrow();

            expect(putCalls(vi.mocked(fetch))).toHaveLength(1);
        });
    });
});
