import { describe, it, expect, vi } from 'vitest';
import {
    container,
    createControllerElement,
    mockSuccessfulUpload,
    tick,
    waitForCompletedItems,
} from './controller-test-harness';

describe('UxUploadController: basics', () => {
    describe('initialization', () => {
        it('should initialize with default values', async () => {
            const element = createControllerElement();
            container.appendChild(element);

            await tick();

            expect(element.querySelector('[data-ux-upload-target="dropzone"]')).toBeTruthy();
            expect(element.querySelector('[data-ux-upload-target="input"]')).toBeTruthy();
            expect(element.querySelector('[data-ux-upload-target="template"]')).toBeTruthy();
        });

        it('should set multiple attribute on input when configured', async () => {
            const element = createControllerElement({ multiple: true });
            container.appendChild(element);

            await tick();

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            expect(input.multiple).toBe(true);
        });

        it('prevents a form from being submitted twice', async () => {
            const form = document.createElement('form');
            const submit = document.createElement('button');
            submit.type = 'submit';
            form.append(createControllerElement(), submit);
            container.appendChild(form);

            await tick();

            const first = new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter: submit });
            const second = new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter: submit });

            expect(form.dispatchEvent(first)).toBe(true);
            expect(form.dispatchEvent(second)).toBe(false);
            expect(second.defaultPrevented).toBe(true);
            expect(form.classList.contains('ux-upload-form--submitting')).toBe(true);
            expect(form.getAttribute('aria-busy')).toBe('true');
        });

        it('uses one submission guard for multiple upload fields in the same form', async () => {
            const form = document.createElement('form');
            form.append(createControllerElement(), createControllerElement());
            container.appendChild(form);

            await tick();

            const first = new SubmitEvent('submit', { bubbles: true, cancelable: true });
            const second = new SubmitEvent('submit', { bubbles: true, cancelable: true });

            expect(form.dispatchEvent(first)).toBe(true);
            expect(form.dispatchEvent(second)).toBe(false);
        });

        it('does not lock a form when submission was already cancelled', async () => {
            const form = document.createElement('form');
            form.addEventListener('submit', (event) => event.preventDefault());
            form.append(createControllerElement());
            container.appendChild(form);

            await tick();

            form.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true }));

            expect(form.classList.contains('ux-upload-form--submitting')).toBe(false);
            expect(form.hasAttribute('aria-busy')).toBe(false);
        });
    });

    describe('file validation', () => {
        it('should reject files exceeding max size', async () => {
            const element = createControllerElement({ maxSize: 1000 });
            container.appendChild(element);

            await tick();

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;

            // Create a file larger than max size
            const file = new File(['x'.repeat(2000)], 'large.txt', { type: 'text/plain' });
            Object.defineProperty(input, 'files', { value: [file] });

            input.dispatchEvent(new Event('change'));

            await tick();

            const errorEl = element.querySelector('[data-ux-upload-target="error"]') as HTMLElement;
            expect(errorEl.hidden).toBe(false);
        });

        it('should reject disallowed file types', async () => {
            const element = createControllerElement({ allowedTypes: ['image/*', '.pdf'] });
            container.appendChild(element);

            await tick();

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;

            // Create a file with disallowed type
            const file = new File(['test'], 'test.txt', { type: 'text/plain' });
            Object.defineProperty(input, 'files', { value: [file] });

            input.dispatchEvent(new Event('change'));

            await tick();

            const errorEl = element.querySelector('[data-ux-upload-target="error"]') as HTMLElement;
            expect(errorEl.hidden).toBe(false);
        });

        it('should accept allowed file types', async () => {
            const element = createControllerElement({ allowedTypes: ['image/*'], autoUpload: false });
            container.appendChild(element);

            await tick();

            // Mock successful init response
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: true,
                json: () =>
                    Promise.resolve({
                        uploadId: 'test-123',
                        chunks: [{ index: 0, url: '/upload/chunk/test-123/0', method: 'PUT' }],
                        completeUrl: '/upload/complete/test-123',
                        statusUrl: '/upload/status/test-123',
                        cancelUrl: '/upload/cancel/test-123',
                        expiresAt: new Date(Date.now() + 3600000).toISOString(),
                        config: {
                            chunkSize: 5 * 1024 * 1024,
                            totalChunks: 1,
                            compression: false,
                            parallel: 3,
                        },
                    }),
            } as Response);

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;

            const file = new File(['test'], 'image.png', { type: 'image/png' });
            Object.defineProperty(input, 'files', { value: [file] });

            input.dispatchEvent(new Event('change'));

            await tick();

            // Should not show error
            const errorEl = element.querySelector('[data-ux-upload-target="error"]') as HTMLElement;
            expect(errorEl.hidden).toBe(true);
            expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(1);
        });
    });

    describe('drag and drop', () => {
        it('should add active class on dragover', async () => {
            const element = createControllerElement();
            container.appendChild(element);

            await tick();

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;

            const dragoverEvent = new Event('dragover', { bubbles: true });
            Object.defineProperty(dragoverEvent, 'preventDefault', { value: vi.fn() });
            dropzone.dispatchEvent(dragoverEvent);

            expect(dropzone.classList.contains('is-active')).toBe(true);
        });

        it('should remove active class on dragleave', async () => {
            const element = createControllerElement();
            container.appendChild(element);

            await tick();

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;

            // First activate
            const dragoverEvent = new Event('dragover', { bubbles: true });
            Object.defineProperty(dragoverEvent, 'preventDefault', { value: vi.fn() });
            dropzone.dispatchEvent(dragoverEvent);

            // Then deactivate
            const dragleaveEvent = new Event('dragleave', { bubbles: true });
            Object.defineProperty(dragleaveEvent, 'preventDefault', { value: vi.fn() });
            dropzone.dispatchEvent(dragleaveEvent);

            expect(dropzone.classList.contains('is-active')).toBe(false);
        });
    });

    describe('file list rendering', () => {
        it('should render file item from template when file is added', async () => {
            const element = createControllerElement({ autoUpload: false });
            container.appendChild(element);

            await tick();

            // Mock init response
            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            uploadId: 'test-123',
                            chunks: [{ index: 0, url: '/upload/chunk/test-123/0', method: 'PUT' }],
                            completeUrl: '/upload/complete/test-123',
                            statusUrl: '/upload/status/test-123',
                            cancelUrl: '/upload/cancel/test-123',
                            expiresAt: new Date(Date.now() + 3600000).toISOString(),
                            config: {
                                chunkSize: 5 * 1024 * 1024,
                                totalChunks: 1,
                                compression: false,
                                parallel: 3,
                            },
                        }),
                } as Response)
                .mockResolvedValue({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response);

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            const file = new File(['test'], 'test.txt', { type: 'text/plain' });
            Object.defineProperty(input, 'files', { value: [file] });

            input.dispatchEvent(new Event('change'));

            await vi.waitFor(() => expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(1));

            const listEl = element.querySelector('[data-ux-upload-target="list"]') as HTMLElement;
            const uploadItem = listEl.querySelector('.ux-upload__item');

            expect(uploadItem).toBeTruthy();
            expect(uploadItem?.querySelector('.ux-upload__name')?.textContent).toBe('test.txt');
            expect(uploadItem?.getAttribute('data-file-id')).toBeTruthy();
        });

        it('should set pending data-status before a manual upload starts', async () => {
            const element = createControllerElement({ autoUpload: false });
            container.appendChild(element);

            await tick();

            // Mock init response
            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            uploadId: 'test-123',
                            chunks: [{ index: 0, url: '/upload/chunk/test-123/0', method: 'PUT' }],
                            completeUrl: '/upload/complete/test-123',
                            statusUrl: '/upload/status/test-123',
                            cancelUrl: '/upload/cancel/test-123',
                            expiresAt: new Date(Date.now() + 3600000).toISOString(),
                            config: {
                                chunkSize: 5 * 1024 * 1024,
                                totalChunks: 1,
                                compression: false,
                                parallel: 3,
                            },
                        }),
                } as Response)
                .mockResolvedValue({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response);

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            const file = new File(['test'], 'test.txt', { type: 'text/plain' });
            Object.defineProperty(input, 'files', { value: [file] });

            input.dispatchEvent(new Event('change'));

            await vi.waitFor(() => expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(1));

            const listEl = element.querySelector('[data-ux-upload-target="list"]') as HTMLElement;
            const uploadItem = listEl.querySelector('.ux-upload__item') as HTMLElement;

            expect(uploadItem).toBeTruthy();
            expect(uploadItem.dataset.status).toBe('pending');
        });
    });

    describe('events', () => {
        it('should dispatch progress event', async () => {
            const element = createControllerElement();
            container.appendChild(element);

            const progressHandler = vi.fn();
            element.addEventListener('ux-upload:progress', progressHandler);

            await tick();

            // Mock successful upload
            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            uploadId: 'test-123',
                            chunks: [{ index: 0, url: '/upload/chunk/test-123/0', method: 'PUT' }],
                            completeUrl: '/upload/complete/test-123',
                            statusUrl: '/upload/status/test-123',
                            cancelUrl: '/upload/cancel/test-123',
                            expiresAt: new Date(Date.now() + 3600000).toISOString(),
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
                .mockResolvedValueOnce({ ok: true } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            meta: {
                                filename: 'test.txt',
                                size: 4,
                                mimeType: 'text/plain',
                            },
                        }),
                } as Response);

            vi.mocked(fetch).mockReset();
            mockSuccessfulUpload('event-progress', 'test.txt', 4, 'text/plain', 'tok-event-progress');

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            const file = new File(['test'], 'test.txt', { type: 'text/plain' });
            Object.defineProperty(input, 'files', { value: [file] });

            input.dispatchEvent(new Event('change'));

            await vi.waitFor(() => expect(progressHandler).toHaveBeenCalled(), { timeout: 1000, interval: 5 });
            await waitForCompletedItems(element);
        });

        it('should dispatch complete event', async () => {
            const element = createControllerElement();
            container.appendChild(element);

            const completeHandler = vi.fn();
            element.addEventListener('ux-upload:complete', completeHandler);

            await tick();

            // Mock successful upload
            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            uploadId: 'test-123',
                            chunks: [{ index: 0, url: '/upload/chunk/test-123/0', method: 'PUT' }],
                            completeUrl: '/upload/complete/test-123',
                            statusUrl: '/upload/status/test-123',
                            cancelUrl: '/upload/cancel/test-123',
                            expiresAt: new Date(Date.now() + 3600000).toISOString(),
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
                .mockResolvedValueOnce({ ok: true } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            meta: {
                                filename: 'test.txt',
                                size: 4,
                                mimeType: 'text/plain',
                            },
                        }),
                } as Response);

            vi.mocked(fetch).mockReset();
            mockSuccessfulUpload('event-complete', 'test.txt', 4, 'text/plain', 'tok-event-complete');

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            const file = new File(['test'], 'test.txt', { type: 'text/plain' });
            Object.defineProperty(input, 'files', { value: [file] });

            input.dispatchEvent(new Event('change'));

            await vi.waitFor(() => expect(completeHandler).toHaveBeenCalled(), { timeout: 1000, interval: 5 });
            await waitForCompletedItems(element);
        });
    });

    describe('max files limit', () => {
        it('should enforce max files limit', async () => {
            const element = createControllerElement({ maxFiles: 2, autoUpload: false });
            container.appendChild(element);

            await tick();

            // Mock responses for successful uploads
            vi.mocked(fetch).mockResolvedValue({
                ok: true,
                json: () =>
                    Promise.resolve({
                        uploadId: `test-${Math.random()}`,
                        chunks: [{ index: 0, url: '/upload/chunk/test/0', method: 'PUT' }],
                        completeUrl: '/upload/complete/test',
                        statusUrl: '/upload/status/test',
                        cancelUrl: '/upload/cancel/test',
                        expiresAt: new Date(Date.now() + 3600000).toISOString(),
                        config: {
                            chunkSize: 5 * 1024 * 1024,
                            totalChunks: 1,
                            compression: false,
                            parallel: 3,
                        },
                    }),
            } as Response);

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;

            // Try to add 3 files
            const files = [
                new File(['1'], 'file1.txt', { type: 'text/plain' }),
                new File(['2'], 'file2.txt', { type: 'text/plain' }),
                new File(['3'], 'file3.txt', { type: 'text/plain' }),
            ];
            Object.defineProperty(input, 'files', { value: files });

            input.dispatchEvent(new Event('change'));

            await vi.waitFor(() => expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(2));

            const listEl = element.querySelector('[data-ux-upload-target="list"]') as HTMLElement;
            const uploadItems = listEl.querySelectorAll('.ux-upload__item');

            // Should only have 2 items
            expect(uploadItems.length).toBe(2);
        });
    });
});
