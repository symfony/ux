import { vi } from 'vitest';
import type { InitResponse } from '../../src/types';

type FetchHandler = (url: string, options?: RequestInit) => Response | Promise<Response>;

export const tick = () => new Promise<void>((resolve) => setTimeout(resolve, 0));

export const okJson = (body: unknown): Response => ({ ok: true, json: () => Promise.resolve(body) }) as Response;

export const okEmpty = (): Response => ({ ok: true }) as Response;

export const statusResponse = (chunkIndices: number[] = []): Response => okJson({ progress: { chunkIndices } });

export const initResponse = (uploadId: string, config: Partial<InitResponse['config']> = {}): InitResponse => ({
    uploadId,
    uploadUrl: `/upload/${uploadId}?sig=test`,
    config: { chunkSize: 5 * 1024 * 1024, totalChunks: 1, compression: false, parallel: 3, ...config },
});

export const chunkedResponses = (init: InitResponse, complete: unknown, existingChunks: number[] = []): Response[] => [
    okJson(init),
    statusResponse(existingChunks),
    ...Array.from({ length: init.config.totalChunks - existingChunks.length }, okEmpty),
    okJson(complete),
];

export const queueResponses = <T extends { mockResolvedValueOnce(value: Response): T }>(
    fetchMock: T,
    responses: Response[]
): T => {
    for (const response of responses) {
        fetchMock.mockResolvedValueOnce(response);
    }

    return fetchMock;
};

export const routedFetch =
    (handlers: {
        init: FetchHandler;
        complete: FetchHandler;
        status?: FetchHandler;
        put?: FetchHandler;
        del?: FetchHandler;
    }) =>
    (input: RequestInfo | URL, options?: RequestInit): Promise<Response> => {
        const url = String(input);
        const method = options?.method ?? 'GET';
        const { init, complete, status = () => statusResponse(), put = okEmpty, del = okEmpty } = handlers;
        if (method === 'POST' && url.endsWith('/init')) {
            return Promise.resolve(init(url, options));
        }
        const handler = { GET: status, PUT: put, DELETE: del }[method] ?? complete;

        return Promise.resolve(handler(url, options));
    };

export const pendingUntilAborted = (_url: RequestInfo | URL, options?: RequestInit): Promise<Response> =>
    new Promise((_resolve, reject) => {
        const abort = () => reject(new DOMException('The operation was aborted.', 'AbortError'));
        if (options?.signal?.aborted) {
            abort();
            return;
        }
        options?.signal?.addEventListener('abort', abort, { once: true });
    });

export const mockSuccessfulUpload = (
    uploadId: string,
    filename: string,
    size: number,
    mimeType: string,
    token: string
): void => {
    queueResponses(
        vi.mocked(fetch),
        chunkedResponses(initResponse(uploadId), { success: true, token, meta: { filename, size, mimeType } })
    );
};

export const mockSuccessfulRemove = (): void => {
    vi.mocked(fetch).mockResolvedValueOnce(okJson({ success: true }));
};

export const createPasteEvent = (files: File[]): ClipboardEvent => {
    const data = new DataTransfer();
    for (const file of files) {
        data.items.add(file);
    }

    return new ClipboardEvent('paste', { clipboardData: data, bubbles: true });
};
