import { describe, it, expect, vi } from 'vitest';
import { Uploader, UploadSuspendedError } from '../../src/uploader';
import {
    container,
    createFullControllerElement,
    mockSuccessfulRemove,
    mockSuccessfulUpload,
    pendingUntilAborted,
    tick,
    triggerFileSelect,
    waitForCompletedItems,
} from './controller-test-harness';
import { XhrTestDouble } from './xhr-test-double';

describe('UxUploadController: lifecycle', () => {
    describe('hydration', () => {
        it('hydrates pre-existing uploads from hidden input value', async () => {
            const sidecar = JSON.stringify({
                token: 'abc',
                meta: { filename: 'test.pdf', mimeType: 'application/pdf', size: 1234 },
            });
            const element = createFullControllerElement({ resultValue: sidecar });
            container.appendChild(element);

            await tick();

            const listEl = element.querySelector('[data-ux-upload-target="list"]') as HTMLElement;
            const items = listEl.querySelectorAll('.ux-upload__item');

            expect(items.length).toBe(1);
            expect(items[0].querySelector('.ux-upload__name')?.textContent).toBe('test.pdf');
        });

        it('hydrated files show in file list with correct metadata', async () => {
            const sidecar = JSON.stringify({
                token: 'tok-hydrate',
                meta: {
                    filename: 'report.docx',
                    mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    size: 52428,
                },
            });
            const element = createFullControllerElement({ resultValue: sidecar });
            container.appendChild(element);

            await tick();

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect(item).toBeTruthy();
            expect(item.querySelector('.ux-upload__name')?.textContent).toBe('report.docx');
            expect(item.querySelector('.ux-upload__size')?.textContent).toBe('51.2 KB');
            expect(item.dataset.status).toBe('completed');
        });

        it('hydrated files can be removed', async () => {
            const sidecar = JSON.stringify({
                token: 'tok-removable',
                meta: { filename: 'old.pdf', mimeType: 'application/pdf', size: 9999 },
            });
            const element = createFullControllerElement({
                resultValue: sidecar,
                removeUrl: '/upload/remove-custom',
            });
            container.appendChild(element);

            await tick();

            // Verify it exists first
            let items = element.querySelectorAll('.ux-upload__item');
            expect(items.length).toBe(1);

            // Click the remove button
            const removeBtn = items[0].querySelector('.ux-upload__remove') as HTMLButtonElement;
            expect(removeBtn).toBeTruthy();
            mockSuccessfulRemove();
            removeBtn.click();

            await tick();

            // Verify removed
            items = element.querySelectorAll('.ux-upload__item');
            expect(items.length).toBe(0);

            // Verify hidden input is cleared
            const resultInput = element.querySelector('[data-ux-upload-target="result"]') as HTMLInputElement;
            expect(resultInput.value === '' || resultInput.value === '[]').toBe(true);
            expect(fetch).toHaveBeenCalledWith('/upload/remove-custom', expect.objectContaining({ method: 'DELETE' }));
        });

        it('keeps a hydrated file when server removal fails', async () => {
            const sidecar = JSON.stringify({
                token: 'tok-protected',
                meta: { filename: 'protected.pdf', mimeType: 'application/pdf', size: 9999 },
            });
            const element = createFullControllerElement({ resultValue: sidecar });
            container.appendChild(element);
            await tick();
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

    // ---------------------------------------------------------------
    // Cancel / Retry / Remove tests
    // ---------------------------------------------------------------

    describe('cancel, retry, and remove', () => {
        it('cancel button aborts in-progress upload', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            // Mock init that returns, then a slow chunk upload to keep upload in progress
            const uploadId = 'upload-cancel-test';
            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            uploadId,
                            uploadUrl: `/upload/${uploadId}?sig=test`,
                            config: {
                                chunkSize: 5 * 1024 * 1024,
                                totalChunks: 1,
                                compression: false,
                                parallel: 3,
                            },
                        }),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockImplementation(pendingUntilAborted);

            const file = new File(['data'], 'cancel-me.txt', { type: 'text/plain' });
            triggerFileSelect(element, [file]);

            await vi.waitFor(() => expect(fetch).toHaveBeenCalledTimes(3), { timeout: 1000, interval: 5 });

            // Find the item and its cancel button
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
            const element = createFullControllerElement({ directUrl: '/upload', chunkSize: 1024 });
            const progressHandler = vi.fn();
            element.addEventListener('ux-upload:progress', progressHandler);
            container.appendChild(element);
            await tick();
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

        it('disconnect suspends an upload without deleting its resumable session', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);
            await tick();
            vi.mocked(fetch)
                .mockReset()
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            uploadId: 'upload-disconnect',
                            uploadUrl: '/upload/upload-disconnect?sig=test',
                            resumeToken: 'resume-disconnect',
                            config: { chunkSize: 1, totalChunks: 4, compression: false, parallel: 1 },
                        }),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockImplementation(pendingUntilAborted);
            triggerFileSelect(element, [new File(['data'], 'data.txt', { type: 'text/plain' })]);
            await vi.waitFor(() => expect(fetch).toHaveBeenCalledTimes(3), { timeout: 1000, interval: 5 });

            element.remove();
            await tick();

            expect(vi.mocked(fetch).mock.calls.some(([, options]) => options?.method === 'DELETE')).toBe(false);
        });

        it('disconnect releases a paused upload instead of leaving it waiting', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);
            await tick();
            const uploadSpy = vi.spyOn(Uploader.prototype, 'upload');
            let releaseFirstChunk!: () => void;
            let firstChunkStarted!: () => void;
            const firstChunkRequested = new Promise<void>((resolve) => {
                firstChunkStarted = resolve;
            });
            vi.mocked(fetch)
                .mockReset()
                .mockImplementation((url, options) => {
                    const method = (options as RequestInit)?.method ?? 'GET';
                    if (method === 'POST' && String(url).endsWith('/init')) {
                        return Promise.resolve({
                            ok: true,
                            json: () =>
                                Promise.resolve({
                                    uploadId: 'upload-paused-disconnect',
                                    uploadUrl: '/upload/upload-paused-disconnect?sig=test',
                                    config: { chunkSize: 1, totalChunks: 4, compression: false, parallel: 1 },
                                }),
                        } as Response);
                    }
                    if (method === 'GET') {
                        return Promise.resolve({
                            ok: true,
                            json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                        } as Response);
                    }
                    if (method === 'PUT' && !releaseFirstChunk) {
                        return new Promise<Response>((resolve) => {
                            releaseFirstChunk = () => resolve({ ok: true } as Response);
                            firstChunkStarted();
                        });
                    }

                    return Promise.resolve({ ok: true } as Response);
                });
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
            const element = createFullControllerElement();
            container.appendChild(element);
            await tick();
            const uploadSpy = vi.spyOn(Uploader.prototype, 'upload');
            vi.mocked(fetch).mockReset().mockImplementation(pendingUntilAborted);
            triggerFileSelect(element, [new File(['data'], 'orphan.txt', { type: 'text/plain' })]);
            await vi.waitFor(() => expect(fetch).toHaveBeenCalledTimes(1));

            const uploadPromise = uploadSpy.mock.results[0].value as Promise<unknown>;
            element.remove();

            await expect(uploadPromise).rejects.toThrow('Upload cancelled');
            const initOptions = vi.mocked(fetch).mock.calls[0][1] as RequestInit;
            expect(initOptions.signal?.aborted).toBe(true);
        });

        it('cancelled upload occupies a slot until it is removed', async () => {
            const element = createFullControllerElement({ maxFiles: 2 });
            container.appendChild(element);

            await tick();

            // Use URL-aware mock to handle concurrent uploads correctly
            let initCount = 0;

            vi.mocked(fetch).mockImplementation((url, options) => {
                const urlStr = String(url);
                const method = (options as RequestInit)?.method ?? 'GET';

                // Init requests
                if (urlStr.includes('/upload/init') && method === 'POST') {
                    initCount++;
                    const id = `up-${initCount}`;
                    return Promise.resolve({
                        ok: true,
                        json: () =>
                            Promise.resolve({
                                uploadId: id,
                                uploadUrl: `/upload/${id}?sig=test`,
                                config: {
                                    chunkSize: 5 * 1024 * 1024,
                                    totalChunks: 1,
                                    compression: false,
                                    parallel: 3,
                                },
                            }),
                    } as Response);
                }

                // Resume/status check (GET on upload URL)
                if (method === 'GET') {
                    return Promise.resolve({
                        ok: true,
                        json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                    } as Response);
                }

                // Keep chunks active until the test cancels them.
                if (method === 'PUT') {
                    return pendingUntilAborted(url, options);
                }

                // Cancel (DELETE)
                if (method === 'DELETE') {
                    return Promise.resolve({ ok: true } as Response);
                }

                return Promise.resolve({ ok: true } as Response);
            });

            // Add 2 files (reaches maxFiles)
            triggerFileSelect(element, [
                new File(['a'], 'file1.txt', { type: 'text/plain' }),
                new File(['b'], 'file2.txt', { type: 'text/plain' }),
            ]);

            await vi.waitFor(() =>
                expect(vi.mocked(fetch).mock.calls.filter(([, options]) => options?.method === 'PUT')).toHaveLength(2)
            );

            let items = element.querySelectorAll('.ux-upload__item');
            expect(items.length).toBe(2);

            // Both should be uploading (chunks are blocked)
            expect((items[0] as HTMLElement).dataset.status).toBe('uploading');
            expect((items[1] as HTMLElement).dataset.status).toBe('uploading');

            // Cancel the first upload
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

        it('retry button re-uploads failed file', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

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

            // Now mock a successful retry
            vi.mocked(fetch).mockReset();
            mockSuccessfulUpload('up-retry-2', 'retry.txt', 10, 'text/plain', 'tok-retry');

            const retryBtn = item.querySelector('.ux-upload__retry') as HTMLButtonElement;
            expect(retryBtn.hidden).toBe(false);
            expect((item.querySelector('.ux-upload__cancel') as HTMLButtonElement).hidden).toBe(true);
            expect((item.querySelector('.ux-upload__remove') as HTMLButtonElement).hidden).toBe(true);
            retryBtn.click();
            expect((item.querySelector('.ux-upload__pause') as HTMLButtonElement).hidden).toBe(true);

            await waitForCompletedItems(element);
        });

        it('remove button removes completed file from list and hidden input', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

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

            // Verify result input has value
            const resultInput = element.querySelector('[data-ux-upload-target="result"]') as HTMLInputElement;
            expect(resultInput.value).toBeTruthy();
            expect(resultInput.value).not.toBe('');

            // Click remove
            const removeBtn = item.querySelector('.ux-upload__remove') as HTMLButtonElement;
            mockSuccessfulRemove();
            removeBtn.click();

            await tick();

            // Verify removed from list
            expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);

            // Verify hidden input is cleared
            expect(resultInput.value === '' || resultInput.value === '[]').toBe(true);
        });

        it('revokes the preview blob URL of the removed item only', async () => {
            const element = createFullControllerElement({
                autoUpload: false,
                multiple: true,
                showPreview: true,
            });
            container.appendChild(element);
            await tick();
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

    // ---------------------------------------------------------------
    // Batch upload tests
    // ---------------------------------------------------------------

    describe('batch upload (autoUpload=false)', () => {
        it('startAll button triggers upload of all pending files', async () => {
            const element = createFullControllerElement({ autoUpload: false, multiple: true });
            container.appendChild(element);

            await tick();

            const startAllBtn = element.querySelector('[data-ux-upload-target="start"]') as HTMLButtonElement;
            expect(startAllBtn.hidden).toBe(true);

            const file1 = new File(['a'], 'file1.txt', { type: 'text/plain' });
            const file2 = new File(['b'], 'file2.txt', { type: 'text/plain' });
            triggerFileSelect(element, [file1, file2]);

            await tick();

            // Files should be pending (autoUpload is false)
            const items = element.querySelectorAll('.ux-upload__item');
            expect(items.length).toBe(2);

            // Both should not have started uploading yet
            for (const item of items) {
                expect((item as HTMLElement).dataset.status).toBe('pending');
                expect((item.querySelector('.ux-upload__cancel') as HTMLButtonElement).hidden).toBe(false);
            }
            expect(startAllBtn.hidden).toBe(false);

            // Fetch should not have been called (no auto-upload)
            expect(fetch).not.toHaveBeenCalled();

            let nextUpload = 0;
            vi.mocked(fetch).mockImplementation((url, options) => {
                const method = options?.method ?? 'GET';
                const path = String(url);
                if ('POST' === method && path.endsWith('/init')) {
                    const uploadId = `batch-${++nextUpload}`;
                    return Promise.resolve({
                        ok: true,
                        json: () =>
                            Promise.resolve({
                                uploadId,
                                uploadUrl: `/upload/${uploadId}?sig=test`,
                                config: {
                                    chunkSize: 5 * 1024 * 1024,
                                    totalChunks: 1,
                                    compression: false,
                                    parallel: 3,
                                },
                            }),
                    } as Response);
                }
                if ('GET' === method) {
                    return Promise.resolve({
                        ok: true,
                        json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                    } as Response);
                }
                if ('PUT' === method) {
                    return Promise.resolve({ ok: true } as Response);
                }

                const uploadId = path.includes('batch-1') ? 'batch-1' : 'batch-2';
                return Promise.resolve({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            uploadId,
                            token: `tok-${uploadId}`,
                            meta: { filename: `${uploadId}.txt`, size: 1, mimeType: 'text/plain' },
                        }),
                } as Response);
            });

            // Click startAll
            startAllBtn.click();

            expect(startAllBtn.hidden).toBe(true);

            await waitForCompletedItems(element, 2);
            expect(fetch).toHaveBeenCalled();
        });

        it('startAll is hidden again when no pending files remain', async () => {
            const element = createFullControllerElement({ autoUpload: false });
            container.appendChild(element);

            await tick();

            mockSuccessfulUpload('up-all-done', 'done.txt', 4, 'text/plain', 'tok-all-done');

            const file = new File(['done'], 'done.txt', { type: 'text/plain' });
            triggerFileSelect(element, [file]);

            const startAllBtn = element.querySelector('[data-ux-upload-target="start"]') as HTMLButtonElement;
            expect(startAllBtn.hidden).toBe(false);
            startAllBtn.click();

            await waitForCompletedItems(element);

            expect(startAllBtn.hidden).toBe(true);
        });
    });

    // ---------------------------------------------------------------
    // Disabled state tests
    // ---------------------------------------------------------------

    describe('disabled state', () => {
        it('dropzone disabled when maxFiles reached', async () => {
            const element = createFullControllerElement({ maxFiles: 1 });
            container.appendChild(element);

            await tick();
            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            expect(dropzone.hidden).toBe(false);

            mockSuccessfulUpload('up-max1', 'only.txt', 4, 'text/plain', 'tok-max1');

            triggerFileSelect(element, [new File(['x'], 'only.txt', { type: 'text/plain' })]);

            await waitForCompletedItems(element);
            expect(dropzone.hidden).toBe(true);

            // Try to add another -- should get error
            vi.mocked(fetch).mockReset();
            triggerFileSelect(element, [new File(['y'], 'extra.txt', { type: 'text/plain' })]);

            await tick();

            const errorEl = element.querySelector('[data-ux-upload-target="error"]') as HTMLElement;
            expect(errorEl.hidden).toBe(false);

            // Should still only have 1 item
            expect(element.querySelectorAll('.ux-upload__item').length).toBe(1);
        });

        it('file input disabled when maxFiles reached via explicit disable', async () => {
            const element = createFullControllerElement({ maxFiles: 1 });
            container.appendChild(element);

            await tick();

            // Disable the input programmatically
            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            input.disabled = true;

            // Try adding a file -- should be ignored due to isDisabled() check
            triggerFileSelect(element, [new File(['x'], 'blocked.txt', { type: 'text/plain' })]);

            await tick();

            // No items should have been added
            expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);
            // Fetch should not have been called
            expect(fetch).not.toHaveBeenCalled();
        });

        it('dropzone re-enabled after removing a file', async () => {
            const element = createFullControllerElement({ maxFiles: 1 });
            container.appendChild(element);

            await tick();

            mockSuccessfulUpload('up-reenable', 'first.txt', 4, 'text/plain', 'tok-reenable');

            triggerFileSelect(element, [new File(['x'], 'first.txt', { type: 'text/plain' })]);

            await waitForCompletedItems(element);
            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            expect(dropzone.hidden).toBe(true);

            // maxFiles reached -- trying to add should fail
            vi.mocked(fetch).mockReset();
            triggerFileSelect(element, [new File(['y'], 'second.txt', { type: 'text/plain' })]);

            await waitForCompletedItems(element);

            const errorEl = element.querySelector('[data-ux-upload-target="error"]') as HTMLElement;
            expect(errorEl.hidden).toBe(false);
            expect(element.querySelectorAll('.ux-upload__item').length).toBe(1);

            // Remove the file
            const removeBtn = element.querySelector('.ux-upload__remove') as HTMLButtonElement;
            mockSuccessfulRemove();
            removeBtn.click();

            await tick();

            expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);
            expect(dropzone.hidden).toBe(false);

            // Now adding a file should work again
            mockSuccessfulUpload('up-reenable-2', 'second.txt', 4, 'text/plain', 'tok-reenable2');

            triggerFileSelect(element, [new File(['y'], 'second.txt', { type: 'text/plain' })]);

            await waitForCompletedItems(element);
            expect(element.querySelectorAll('.ux-upload__item').length).toBe(1);
        });
    });
});
