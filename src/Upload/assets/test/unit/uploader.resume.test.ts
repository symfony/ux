import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { Uploader, UploadCancelledError, UploadSuspendedError } from '../../src/uploader';
import { initResponse, okEmpty, okJson, pendingUntilAborted, routedFetch, statusResponse } from './fixtures';
import { FakeIndexedDB } from './indexeddb_fake';

type FetchCall = [RequestInfo | URL, RequestInit?];

const policy = (expiresAt: number, hash: string) =>
    `_expiration=${expiresAt}&_hash=${hash}&e=${expiresAt}&f=upload_form%5Bfile%5D&n=1&s=67108864&t=application%2Foctet-stream&u=large`;

const config = { chunkSize: 200, totalChunks: 2, parallel: 1 };

const putIndices = (fetchMock: { mock: { calls: unknown[][] } }) =>
    (fetchMock.mock.calls as FetchCall[])
        .filter(([, options]) => options?.method === 'PUT')
        .map(([, options]) => ((options as RequestInit).headers as Record<string, string>)['X-Chunk-Index']);

describe('Uploader: resume after reload', () => {
    let indexedDB: FakeIndexedDB;
    const file = new File([new Uint8Array(400)], 'large.bin', { type: 'image/jpeg', lastModified: 1_700_000_000_000 });
    const savedRecords = () => indexedDB.records('symfony-ux-upload', 'resume');

    beforeEach(() => {
        indexedDB = new FakeIndexedDB();
        vi.stubGlobal('indexedDB', indexedDB);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    const interruptFirstUpload = async (): Promise<void> => {
        let putCount = 0;
        const firstFetch = vi.fn(
            routedFetch({
                init: () => okJson({ ...initResponse('up-resume', config), resumeToken: 'resume-1' }),
                put: (url, options) => (++putCount === 1 ? okEmpty() : pendingUntilAborted(url, options)),
                complete: okEmpty,
            })
        );
        const first = new Uploader({
            initUrl: '/upload/init',
            uploader: 'large',
            policyToken: policy(1790284638, 'rtJoCemk'),
            fetch: firstFetch,
        });

        const upload = first.upload(file);
        await vi.waitFor(() => expect(putIndices(firstFetch)).toEqual(['0', '1']));
        first.suspend('up-resume');
        await expect(upload).rejects.toBeInstanceOf(UploadSuspendedError);
    };

    const reloadedUploader = (put: Parameters<typeof routedFetch>[0]['put']) => {
        const init = vi.fn(() => okJson(initResponse('up-fresh', config)));
        const secondFetch = vi.fn(
            routedFetch({
                init,
                status: () => statusResponse([0]),
                put,
                complete: (url) =>
                    url === '/upload/resume'
                        ? okJson(initResponse('up-resume', config))
                        : okJson({
                              success: true,
                              token: 'tok-resumed',
                              meta: { filename: 'large.bin', size: 400, mimeType: 'image/jpeg' },
                          }),
            })
        );
        const second = new Uploader({
            initUrl: '/upload/init',
            uploader: 'large',
            policyToken: policy(1790284639, 'ODbSHYhR'),
            fetch: secondFetch,
        });

        return { second, secondFetch, init };
    };

    it('keeps the resume record when the upload is suspended', async () => {
        await interruptFirstUpload();

        expect(savedRecords()).toEqual([expect.objectContaining({ token: 'resume-1' })]);
    });

    it('resumes the session from a reloaded page and sends only the missing chunk', async () => {
        await interruptFirstUpload();
        const { second, secondFetch, init } = reloadedUploader(okEmpty);

        const result = await second.upload(file);

        const resumeCall = (secondFetch.mock.calls as FetchCall[]).find(([url]) => url === '/upload/resume');
        expect(JSON.parse(resumeCall?.[1]?.body as string)).toEqual({
            resumeToken: 'resume-1',
            policyToken: policy(1790284639, 'ODbSHYhR'),
        });
        expect(init).not.toHaveBeenCalled();
        expect(putIndices(secondFetch)).toEqual(['1']);
        expect(result).toEqual(expect.objectContaining({ uploadId: 'up-resume', token: 'tok-resumed' }));
        expect(savedRecords()).toEqual([]);
    });

    it('deletes the resume record when the resumed upload is cancelled', async () => {
        await interruptFirstUpload();
        const { second, secondFetch } = reloadedUploader(pendingUntilAborted);

        const upload = second.upload(file);
        await vi.waitFor(() => expect(putIndices(secondFetch)).toEqual(['1']));
        second.cancel('up-resume');

        await expect(upload).rejects.toBeInstanceOf(UploadCancelledError);
        await vi.waitFor(() => expect(savedRecords()).toEqual([]));
    });

    it('starts a fresh session when the server refuses the resume token', async () => {
        await interruptFirstUpload();
        const init = vi.fn(() => okJson(initResponse('up-fresh', config)));
        const secondFetch = vi.fn(
            routedFetch({
                init,
                complete: (url) =>
                    url === '/upload/resume'
                        ? ({ ok: false, status: 404 } as Response)
                        : okJson({ success: true, token: 'tok-fresh', meta: {} }),
            })
        );
        const second = new Uploader({
            initUrl: '/upload/init',
            uploader: 'large',
            policyToken: policy(1790284639, 'ODbSHYhR'),
            fetch: secondFetch,
        });

        const result = await second.upload(file);

        expect(init).toHaveBeenCalledTimes(1);
        expect(putIndices(secondFetch)).toEqual(['0', '1']);
        expect(result.uploadId).toBe('up-fresh');
        expect(savedRecords()).toEqual([]);
    });

    it('drops a resume record the server refuses, even when the fresh session fails', async () => {
        await interruptFirstUpload();
        const second = new Uploader({
            initUrl: '/upload/init',
            uploader: 'large',
            policyToken: policy(1790284639, 'ODbSHYhR'),
            fetch: vi.fn(async (input: RequestInfo | URL) =>
                String(input) === '/upload/resume'
                    ? ({ ok: false, status: 404 } as Response)
                    : ({ ok: false, json: () => Promise.resolve({ error: 'Init refused' }) } as Response)
            ),
        });

        await expect(second.upload(file)).rejects.toThrow('Init refused');
        expect(savedRecords()).toEqual([]);
    });
});
