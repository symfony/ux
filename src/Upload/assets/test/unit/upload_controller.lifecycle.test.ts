import { describe, it, expect, vi } from 'vitest';
import { Uploader, UploadSuspendedError } from '../../src/uploader';
import {
    initResponse,
    mockSuccessfulRemove,
    mockSuccessfulUpload,
    mount,
    okEmpty,
    okJson,
    pendingUntilAborted,
    queueResponses,
    routedFetch,
    statusResponse,
    tick,
    triggerFileSelect,
    waitForCompletedItems,
} from './upload_controller_harness';
import { XhrTestDouble } from './xhr_test_double';

describe('UxUploadController: lifecycle', () => {
    describe('hydration', () => {
        it('hydrated files show in file list with correct metadata', async () => {
            const sidecar = JSON.stringify({
                token: 'tok-hydrate',
                meta: {
                    filename: 'report.docx',
                    mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    size: 52428,
                },
            });
            const element = await mount({ resultValue: sidecar });

            expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(1);
            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect(item.querySelector('.ux-upload__name')?.textContent).toBe('report.docx');
            expect(item.querySelector('.ux-upload__size')?.textContent).toBe('51.2 KB');
            expect(item.dataset.status).toBe('completed');
        });

        it('shows a hydrated file as complete, without its progress indicators', async () => {
            const sidecar = JSON.stringify({
                token: 'tok-hydrate',
                meta: { filename: 'report.pdf', mimeType: 'application/pdf', size: 2048 },
            });
            const element = await mount({ resultValue: sidecar });

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect(item.dataset.progress).toBe('100');
            expect(item.querySelector<HTMLElement>('[data-ux-upload-status]')?.hidden).toBe(true);
            expect(item.querySelector('[data-ux-upload-status]')?.textContent).toBe('');
            expect(item.querySelector<HTMLElement>('[data-ux-upload-percent]')?.hidden).toBe(true);
            expect(item.querySelector<HTMLElement>('[data-ux-upload-progress]')?.hidden).toBe(true);
        });

        it('shows the same completed state after a live upload as after hydration', async () => {
            mockSuccessfulUpload('up-live', 'report.pdf', 4, 'application/pdf', 'tok-live');
            const element = await mount();

            triggerFileSelect(element, [new File(['data'], 'report.pdf', { type: 'application/pdf' })]);
            await waitForCompletedItems(element);

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect(item.dataset.progress).toBe('100');
            expect(item.querySelector<HTMLElement>('[data-ux-upload-status]')?.hidden).toBe(true);
            expect(item.querySelector('[data-ux-upload-status]')?.textContent).toBe('');
            expect(item.querySelector<HTMLElement>('[data-ux-upload-percent]')?.hidden).toBe(true);
            expect(item.querySelector<HTMLElement>('[data-ux-upload-progress]')?.hidden).toBe(true);
        });

        it('restores the preview of a hydrated image from the preview cache', async () => {
            const match = vi.fn(async () => new Response('thumbnail'));
            vi.stubGlobal('caches', { open: async () => ({ match }) });
            vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:restored');
            const sidecar = JSON.stringify({
                token: 'tok-photo',
                meta: { filename: 'photo.png', mimeType: 'image/png', size: 2048 },
            });

            const element = await mount({ resultValue: sidecar });

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            const img = item.querySelector('.ux-upload__preview') as HTMLImageElement;
            await vi.waitFor(() => expect(img.getAttribute('src')).toBe('blob:restored'));
            expect(match).toHaveBeenCalledWith('/_ux-upload-preview/tok-photo');
            expect(img.hidden).toBe(false);
            expect(img.alt).toBe('photo.png');
            expect(item.dataset.preview).toBe('image');
            expect(item.querySelector<HTMLElement>('.ux-upload__percent')?.hidden).toBe(true);
        });

        it('revokes a restored preview whose hydrated item was removed meanwhile', async () => {
            let releaseMatch!: (response: Response) => void;
            const match = vi.fn(
                () =>
                    new Promise<Response>((resolve) => {
                        releaseMatch = resolve;
                    })
            );
            vi.stubGlobal('caches', { open: async () => ({ match }) });
            vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:late');
            const revokeObjectURL = vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => {});
            const sidecar = JSON.stringify({
                token: 'tok-late',
                meta: { filename: 'late.png', mimeType: 'image/png', size: 2048 },
            });
            const element = await mount({ resultValue: sidecar });
            await vi.waitFor(() => expect(match).toHaveBeenCalled());

            mockSuccessfulRemove();
            (element.querySelector('.ux-upload__remove') as HTMLButtonElement).click();
            await vi.waitFor(() => expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(0));
            releaseMatch(new Response('thumbnail'));

            await vi.waitFor(() => expect(revokeObjectURL).toHaveBeenCalledWith('blob:late'));
        });

        it('hydrated files can be removed', async () => {
            const sidecar = JSON.stringify({
                token: 'tok-removable',
                meta: { filename: 'old.pdf', mimeType: 'application/pdf', size: 9999 },
            });
            const element = await mount({
                resultValue: sidecar,
                removeUrl: '/upload/remove-custom',
            });

            let items = element.querySelectorAll('.ux-upload__item');
            expect(items.length).toBe(1);

            const removeBtn = items[0].querySelector('.ux-upload__remove') as HTMLButtonElement;
            expect(removeBtn).toBeTruthy();
            mockSuccessfulRemove();
            removeBtn.click();

            await tick();

            items = element.querySelectorAll('.ux-upload__item');
            expect(items.length).toBe(0);

            const resultInput = element.querySelector('[data-ux-upload-target="result"]') as HTMLInputElement;
            expect(resultInput.value).toBe('');
            expect(fetch).toHaveBeenCalledWith('/upload/remove-custom', expect.objectContaining({ method: 'DELETE' }));
        });

        it('keeps a hydrated file when server removal fails', async () => {
            const sidecar = JSON.stringify({
                token: 'tok-protected',
                meta: { filename: 'protected.pdf', mimeType: 'application/pdf', size: 9999 },
            });
            const element = await mount({ resultValue: sidecar });
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: false,
                json: () => Promise.resolve({ error: 'Upload could not be removed' }),
            } as Response);

            (element.querySelector('.ux-upload__remove') as HTMLButtonElement).click();
            await tick();

            expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(1);
            expect((element.querySelector('[data-ux-upload-target="result"]') as HTMLInputElement).value).toContain(
                'tok-protected'
            );
            expect(element.querySelector('[data-ux-upload-target="error"]')?.textContent).toContain(
                'Upload could not be removed'
            );
        });
    });

    describe('form submission while uploading', () => {
        const startPendingUpload = async (element: HTMLElement): Promise<HTMLElement> => {
            queueResponses(vi.mocked(fetch), [
                okJson(initResponse('upload-blocking')),
                statusResponse(),
            ]).mockImplementation(pendingUntilAborted);

            triggerFileSelect(element, [new File(['data'], 'slow.txt', { type: 'text/plain' })]);

            await vi.waitFor(
                () => expect(vi.mocked(fetch).mock.calls.some(([, options]) => options?.method === 'PUT')).toBe(true),
                { timeout: 1000, interval: 5 }
            );

            return element.querySelector('.ux-upload__item') as HTMLElement;
        };

        it('marks the file input invalid while an upload is in progress', async () => {
            const element = await mount();
            element.setAttribute('data-ux-upload-label-upload-in-progress-value', 'Wait for the upload');
            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;

            await startPendingUpload(element);

            expect(input.validationMessage).toBe('Wait for the upload');
            expect(input.checkValidity()).toBe(false);
        });

        it('keeps the file input invalid while an upload is paused', async () => {
            const element = await mount();
            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;

            const item = await startPendingUpload(element);
            (item.querySelector('.ux-upload__pause') as HTMLButtonElement).click();

            expect(item.dataset.status).toBe('paused');
            expect(input.validity.customError).toBe(true);
        });

        it('keeps the paused label when a chunk in flight lands', async () => {
            const element = await mount();
            let releaseChunk: (() => void) | undefined;
            queueResponses(vi.mocked(fetch), [
                okJson(initResponse('upload-paused-chunk', { chunkSize: 2, totalChunks: 2, parallel: 1 })),
                statusResponse(),
            ])
                .mockImplementationOnce(
                    () =>
                        new Promise((resolve) => {
                            releaseChunk = () => resolve(okEmpty());
                        })
                )
                .mockImplementation(pendingUntilAborted);
            triggerFileSelect(element, [new File(['data'], 'slow.txt', { type: 'text/plain' })]);
            await vi.waitFor(() => expect(releaseChunk).toBeDefined());
            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            (item.querySelector('.ux-upload__pause') as HTMLButtonElement).click();

            releaseChunk?.();
            await vi.waitFor(() => expect(item.dataset.progress).toBe('50'));

            expect(item.dataset.status).toBe('paused');
            expect(item.querySelector('[data-ux-upload-status]')?.textContent).toBe('Paused');
        });

        it('makes the file input valid again once the upload is cancelled', async () => {
            const element = await mount();
            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;

            const item = await startPendingUpload(element);
            (item.querySelector('.ux-upload__cancel') as HTMLButtonElement).click();
            await tick();

            expect(input.validity.customError).toBe(false);
        });

        it('makes the file input valid again once the upload completes', async () => {
            const element = await mount();
            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;

            mockSuccessfulUpload('up-valid', 'done.txt', 4, 'text/plain', 'tok-valid');
            triggerFileSelect(element, [new File(['data'], 'done.txt', { type: 'text/plain' })]);
            await waitForCompletedItems(element);

            expect(input.validity.customError).toBe(false);
        });
    });

    describe('cancel, retry, and remove', () => {
        it('cancel button aborts in-progress upload', async () => {
            const element = await mount();

            queueResponses(vi.mocked(fetch), [
                okJson(initResponse('upload-cancel-test')),
                statusResponse(),
            ]).mockImplementation(pendingUntilAborted);

            const file = new File(['data'], 'cancel-me.txt', { type: 'text/plain' });
            triggerFileSelect(element, [file]);

            await vi.waitFor(
                () => expect(vi.mocked(fetch).mock.calls.some(([, options]) => options?.method === 'PUT')).toBe(true),
                { timeout: 1000, interval: 5 }
            );

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect(item).toBeTruthy();
            expect(item.dataset.status).toBe('uploading');

            const pauseBtn = item.querySelector('.ux-upload__pause') as HTMLButtonElement;
            const resumeBtn = item.querySelector('.ux-upload__resume') as HTMLButtonElement;
            const cancelBtn = item.querySelector('.ux-upload__cancel') as HTMLButtonElement;
            expect(pauseBtn.hidden).toBe(false);
            expect(resumeBtn.hidden).toBe(true);
            expect(cancelBtn.hidden).toBe(false);

            pauseBtn.click();
            expect(item.dataset.status).toBe('paused');
            expect(pauseBtn.hidden).toBe(true);
            expect(resumeBtn.hidden).toBe(false);
            expect(cancelBtn.hidden).toBe(false);

            resumeBtn.click();
            expect(item.dataset.status).toBe('uploading');
            expect(pauseBtn.hidden).toBe(false);
            expect(resumeBtn.hidden).toBe(true);

            cancelBtn.click();

            await tick();

            expect(item.dataset.status).toBe('cancelled');
            expect(cancelBtn.hidden).toBe(true);
            expect((item.querySelector('.ux-upload__remove') as HTMLButtonElement).hidden).toBe(false);
        });

        it('cancel button aborts a direct request without a server upload ID', async () => {
            const xhr = new XhrTestDouble();
            vi.stubGlobal(
                'XMLHttpRequest',
                class {
                    constructor() {
                        return xhr;
                    }
                }
            );
            const element = await mount({ directUrl: '/upload', chunkSize: 1024 });
            const progressHandler = vi.fn();
            element.addEventListener('ux-upload:progress', progressHandler);
            triggerFileSelect(element, [new File(['data'], 'direct.txt', { type: 'text/plain' })]);
            await vi.waitFor(() => expect(xhr.body).toBeInstanceOf(FormData));

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect((item.querySelector('.ux-upload__pause') as HTMLButtonElement).hidden).toBe(true);
            expect((item.querySelector('.ux-upload__cancel') as HTMLButtonElement).hidden).toBe(false);

            xhr.progress(50, 100);
            expect(item.dataset.progress).toBe('50');
            expect(progressHandler).toHaveBeenCalledWith(
                expect.objectContaining({ detail: expect.objectContaining({ percent: 50 }) })
            );

            (item.querySelector('.ux-upload__cancel') as HTMLButtonElement).click();
            await tick();

            expect(item.dataset.status).toBe('cancelled');
            expect(xhr.url).toBe('/upload');
            expect(xhr.aborted).toBe(true);
            expect(fetch).not.toHaveBeenCalled();
        });

        it('disconnect releases a paused upload instead of leaving it waiting', async () => {
            const element = await mount();
            const uploadSpy = vi.spyOn(Uploader.prototype, 'upload');
            let releaseFirstChunk!: () => void;
            let firstChunkStarted!: () => void;
            const firstChunkRequested = new Promise<void>((resolve) => {
                firstChunkStarted = resolve;
            });
            vi.mocked(fetch).mockImplementation(
                routedFetch({
                    init: () =>
                        okJson(initResponse('upload-paused-disconnect', { chunkSize: 1, totalChunks: 4, parallel: 1 })),
                    put: () => {
                        if (!releaseFirstChunk) {
                            return new Promise<Response>((resolve) => {
                                releaseFirstChunk = () => resolve(okEmpty());
                                firstChunkStarted();
                            });
                        }

                        return okEmpty();
                    },
                    complete: okEmpty,
                })
            );
            triggerFileSelect(element, [new File(['data'], 'paused.txt', { type: 'text/plain' })]);
            await firstChunkRequested;

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            await vi.waitFor(() => expect(item.dataset.status).toBe('uploading'));
            (item.querySelector('.ux-upload__pause') as HTMLButtonElement).click();
            expect(item.dataset.status).toBe('paused');

            releaseFirstChunk();
            await tick();
            expect(vi.mocked(fetch).mock.calls.filter(([, options]) => options?.method === 'PUT')).toHaveLength(1);

            const uploadPromise = uploadSpy.mock.results[0].value as Promise<unknown>;
            element.remove();

            await expect(uploadPromise).rejects.toBeInstanceOf(UploadSuspendedError);
            expect(vi.mocked(fetch).mock.calls.some(([, options]) => options?.method === 'DELETE')).toBe(false);
        });

        it('disconnect aborts a chunked upload that has no upload ID yet', async () => {
            const element = await mount();
            const uploadSpy = vi.spyOn(Uploader.prototype, 'upload');
            vi.mocked(fetch).mockImplementation(pendingUntilAborted);
            triggerFileSelect(element, [new File(['data'], 'orphan.txt', { type: 'text/plain' })]);
            await vi.waitFor(() => expect(fetch).toHaveBeenCalledTimes(1));

            const uploadPromise = uploadSpy.mock.results[0].value as Promise<unknown>;
            element.remove();

            await expect(uploadPromise).rejects.toThrow('Upload cancelled');
            const initOptions = vi.mocked(fetch).mock.calls[0][1] as RequestInit;
            expect(initOptions.signal?.aborted).toBe(true);
        });

        it('cancelled upload occupies a slot until it is removed', async () => {
            const element = await mount({ maxFiles: 2 });

            let initCount = 0;
            vi.mocked(fetch).mockImplementation(
                routedFetch({
                    init: () => okJson(initResponse(`up-${++initCount}`)),
                    put: pendingUntilAborted,
                    complete: okEmpty,
                })
            );

            triggerFileSelect(element, [
                new File(['a'], 'file1.txt', { type: 'text/plain' }),
                new File(['b'], 'file2.txt', { type: 'text/plain' }),
            ]);

            await vi.waitFor(() =>
                expect(vi.mocked(fetch).mock.calls.filter(([, options]) => options?.method === 'PUT')).toHaveLength(2)
            );

            let items = element.querySelectorAll('.ux-upload__item');
            expect(items.length).toBe(2);

            expect((items[0] as HTMLElement).dataset.status).toBe('uploading');
            expect((items[1] as HTMLElement).dataset.status).toBe('uploading');

            const cancelBtn = items[0].querySelector('.ux-upload__cancel') as HTMLButtonElement;
            cancelBtn.click();

            await tick();

            expect((items[0] as HTMLElement).dataset.status).toBe('cancelled');

            // The cancelled card still occupies a visible gallery slot.
            triggerFileSelect(element, [new File(['c'], 'file3.txt', { type: 'text/plain' })]);

            await tick();

            items = element.querySelectorAll('.ux-upload__item');
            expect(items.length).toBe(2);

            const errorEl = element.querySelector('[data-ux-upload-target="error"]') as HTMLElement;
            expect(errorEl.hidden).toBe(false);

            // Removing the cancelled card releases the slot.
            const removeBtn = items[0].querySelector('.ux-upload__remove') as HTMLButtonElement;
            removeBtn.click();
            await tick();

            triggerFileSelect(element, [new File(['c'], 'file3.txt', { type: 'text/plain' })]);
            await vi.waitFor(() =>
                expect(vi.mocked(fetch).mock.calls.filter(([, options]) => options?.method === 'PUT')).toHaveLength(3)
            );

            items = element.querySelectorAll('.ux-upload__item');
            expect(items.length).toBe(2);

            for (const item of items) {
                (item.querySelector('.ux-upload__cancel') as HTMLButtonElement).click();
            }
            await vi.waitFor(() =>
                expect(Array.from(items).every((item) => (item as HTMLElement).dataset.status === 'cancelled')).toBe(
                    true
                )
            );
        });

        it('lets a failed file be removed, which frees its slot', async () => {
            const element = await mount({ maxFiles: 1 });
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: false,
                statusText: 'Bad Request',
                json: () => Promise.resolve({ error: 'Refused by the server' }),
            } as Response);

            triggerFileSelect(element, [new File(['data'], 'refused.txt', { type: 'text/plain' })]);
            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            await vi.waitFor(() => expect(item.dataset.status).toBe('error'), { timeout: 1000, interval: 5 });

            vi.mocked(fetch).mockReset();
            const remove = item.querySelector('.ux-upload__remove') as HTMLButtonElement;
            expect(remove.hidden).toBe(false);
            remove.click();
            await tick();

            expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(0);
            expect(fetch).not.toHaveBeenCalled();

            mockSuccessfulUpload('up-after', 'accepted.txt', 4, 'text/plain', 'tok-after');
            triggerFileSelect(element, [new File(['data'], 'accepted.txt', { type: 'text/plain' })]);
            await waitForCompletedItems(element);
        });

        it('retry button re-uploads failed file', async () => {
            const element = await mount();

            vi.mocked(fetch).mockResolvedValueOnce({
                ok: false,
                statusText: 'Internal Server Error',
                json: () => Promise.resolve({ error: 'Upload initialization failed' }),
            } as Response);

            const file = new File(['retry-data'], 'retry.txt', { type: 'text/plain' });
            triggerFileSelect(element, [file]);

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect(item).toBeTruthy();
            await vi.waitFor(() => expect(item.dataset.status).toBe('error'), { timeout: 1000, interval: 5 });

            vi.mocked(fetch).mockReset();
            mockSuccessfulUpload('up-retry-2', 'retry.txt', 10, 'text/plain', 'tok-retry');

            const retryBtn = item.querySelector('.ux-upload__retry') as HTMLButtonElement;
            expect(retryBtn.hidden).toBe(false);
            expect((item.querySelector('.ux-upload__cancel') as HTMLButtonElement).hidden).toBe(true);
            expect((item.querySelector('.ux-upload__remove') as HTMLButtonElement).hidden).toBe(false);
            retryBtn.click();
            expect((item.querySelector('.ux-upload__pause') as HTMLButtonElement).hidden).toBe(true);

            await waitForCompletedItems(element);
        });

        it('remove button removes completed file from list and hidden input', async () => {
            const element = await mount();

            mockSuccessfulUpload('up-remove', 'done.txt', 4, 'text/plain', 'tok-done');

            const file = new File(['done'], 'done.txt', { type: 'text/plain' });
            triggerFileSelect(element, [file]);

            await waitForCompletedItems(element);

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect(item).toBeTruthy();
            expect(item.dataset.status).toBe('completed');
            expect((item.querySelector('.ux-upload__remove') as HTMLButtonElement).hidden).toBe(false);
            expect((item.querySelector('.ux-upload__cancel') as HTMLButtonElement).hidden).toBe(true);
            expect((item.querySelector('.ux-upload__retry') as HTMLButtonElement).hidden).toBe(true);

            const resultInput = element.querySelector('[data-ux-upload-target="result"]') as HTMLInputElement;
            expect(resultInput.value).toBeTruthy();
            expect(resultInput.value).not.toBe('');

            const removeBtn = item.querySelector('.ux-upload__remove') as HTMLButtonElement;
            mockSuccessfulRemove();
            removeBtn.click();

            await tick();

            expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);

            expect(resultInput.value).toBe('');
        });

        it('revokes the preview blob URL of the removed item only', async () => {
            const element = await mount({
                autoUpload: false,
                multiple: true,
                showPreview: true,
            });
            const createObjectURL = vi
                .spyOn(URL, 'createObjectURL')
                .mockImplementationOnce(() => 'blob:first')
                .mockImplementationOnce(() => 'blob:second');
            const revokeObjectURL = vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => {});

            triggerFileSelect(element, [
                new File(['a'], 'first.png', { type: 'image/png' }),
                new File(['b'], 'second.png', { type: 'image/png' }),
            ]);
            await tick();

            expect(createObjectURL).toHaveBeenCalledTimes(2);
            const items = element.querySelectorAll('.ux-upload__item');
            expect(items).toHaveLength(2);

            (items[0].querySelector('.ux-upload__cancel') as HTMLButtonElement).click();
            await tick();

            expect(revokeObjectURL).toHaveBeenCalledTimes(1);
            expect(revokeObjectURL).toHaveBeenCalledWith('blob:first');

            element.remove();
            await tick();

            expect(revokeObjectURL).toHaveBeenCalledTimes(2);
            expect(revokeObjectURL).toHaveBeenLastCalledWith('blob:second');
        });
    });

    describe('batch upload (autoUpload=false)', () => {
        it('startAll button triggers upload of all pending files', async () => {
            const element = await mount({ autoUpload: false, multiple: true });

            const startAllBtn = element.querySelector('[data-ux-upload-target="start"]') as HTMLButtonElement;
            expect(startAllBtn.hidden).toBe(true);

            const file1 = new File(['a'], 'file1.txt', { type: 'text/plain' });
            const file2 = new File(['b'], 'file2.txt', { type: 'text/plain' });
            triggerFileSelect(element, [file1, file2]);

            await tick();

            const items = element.querySelectorAll('.ux-upload__item');
            expect(items.length).toBe(2);

            for (const item of items) {
                expect((item as HTMLElement).dataset.status).toBe('pending');
                expect((item.querySelector('.ux-upload__cancel') as HTMLButtonElement).hidden).toBe(false);
            }
            expect(startAllBtn.hidden).toBe(false);

            expect(fetch).not.toHaveBeenCalled();

            let nextUpload = 0;
            vi.mocked(fetch).mockImplementation(
                routedFetch({
                    init: () => okJson(initResponse(`batch-${++nextUpload}`)),
                    complete: (url) => {
                        const uploadId = url.includes('batch-1') ? 'batch-1' : 'batch-2';

                        return okJson({
                            success: true,
                            uploadId,
                            token: `tok-${uploadId}`,
                            meta: { filename: `${uploadId}.txt`, size: 1, mimeType: 'text/plain' },
                        });
                    },
                })
            );

            startAllBtn.click();

            expect(startAllBtn.hidden).toBe(true);

            await waitForCompletedItems(element, 2);
            expect(startAllBtn.hidden).toBe(true);
        });
    });

    describe('disabled state', () => {
        it('ignores file selection while the input is disabled', async () => {
            const element = await mount();

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            input.disabled = true;

            triggerFileSelect(element, [new File(['x'], 'blocked.txt', { type: 'text/plain' })]);

            await tick();

            expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);
            expect(fetch).not.toHaveBeenCalled();
        });

        it('hides the picker of a field without previews once its file is complete', async () => {
            const element = await mount({ maxFiles: 1, showPreview: false });
            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            mockSuccessfulUpload('up-single', 'only.txt', 4, 'text/plain', 'tok-single');

            triggerFileSelect(element, [new File(['data'], 'only.txt', { type: 'text/plain' })]);
            await waitForCompletedItems(element);

            expect(dropzone.hidden).toBe(true);

            mockSuccessfulRemove();
            (element.querySelector('.ux-upload__remove') as HTMLButtonElement).click();
            await tick();

            expect(dropzone.hidden).toBe(false);
        });

        it('starts without its picker when the form comes back with its file', async () => {
            const sidecar = JSON.stringify({
                token: 'tok-kept',
                meta: { filename: 'kept.pdf', mimeType: 'application/pdf', size: 2048 },
            });
            const element = await mount({ maxFiles: 1, showPreview: false, resultValue: sidecar });

            expect((element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement).hidden).toBe(true);
        });

        it('keeps the picker while its only file is still uploading', async () => {
            const element = await mount({ maxFiles: 1 });
            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            queueResponses(vi.mocked(fetch), [okJson(initResponse('up-slow')), statusResponse()]).mockImplementation(
                pendingUntilAborted
            );

            triggerFileSelect(element, [new File(['data'], 'slow.txt', { type: 'text/plain' })]);
            await vi.waitFor(
                () =>
                    expect((element.querySelector('.ux-upload__item') as HTMLElement).dataset.status).toBe('uploading'),
                { timeout: 1000, interval: 5 }
            );

            // The browser reports "wait for the upload" on the file input: it must stay rendered
            expect(dropzone.hidden).toBe(false);
        });

        it('hides the dropzone at maxFiles and shows it again after a removal', async () => {
            const element = await mount({ maxFiles: 1 });
            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            expect(dropzone.hidden).toBe(false);

            mockSuccessfulUpload('up-reenable', 'first.txt', 4, 'text/plain', 'tok-reenable');

            triggerFileSelect(element, [new File(['x'], 'first.txt', { type: 'text/plain' })]);

            await waitForCompletedItems(element);
            expect(dropzone.hidden).toBe(true);

            vi.mocked(fetch).mockReset();
            triggerFileSelect(element, [new File(['y'], 'second.txt', { type: 'text/plain' })]);

            await waitForCompletedItems(element);

            const errorEl = element.querySelector('[data-ux-upload-target="error"]') as HTMLElement;
            expect(errorEl.hidden).toBe(false);
            expect(element.querySelectorAll('.ux-upload__item').length).toBe(1);

            const removeBtn = element.querySelector('.ux-upload__remove') as HTMLButtonElement;
            mockSuccessfulRemove();
            removeBtn.click();

            await tick();

            expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);
            expect(dropzone.hidden).toBe(false);

            mockSuccessfulUpload('up-reenable-2', 'second.txt', 4, 'text/plain', 'tok-reenable2');

            triggerFileSelect(element, [new File(['y'], 'second.txt', { type: 'text/plain' })]);

            await waitForCompletedItems(element);
            expect(element.querySelectorAll('.ux-upload__item').length).toBe(1);
        });
    });
});
