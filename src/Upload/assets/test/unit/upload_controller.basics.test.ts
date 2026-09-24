import { describe, it, expect, vi } from 'vitest';
import {
    container,
    createFullControllerElement,
    mockSuccessfulUpload,
    mount,
    tick,
    triggerFileSelect,
    waitForCompletedItems,
} from './upload_controller_harness';

describe('UxUploadController: basics', () => {
    describe('initialization', () => {
        it('sets the multiple attribute on the input when configured', async () => {
            const element = await mount({ multiple: true });

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            expect(input.multiple).toBe(true);
        });

        it('submits the token field in place of the native file input', async () => {
            const form = document.createElement('form');
            form.append(createFullControllerElement({ resultValue: JSON.stringify({ token: 'tok-1', meta: {} }) }));
            container.appendChild(form);
            await tick();

            const input = form.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            const result = form.querySelector('[data-ux-upload-target="result"]') as HTMLInputElement;

            expect(input.hasAttribute('name')).toBe(false);
            expect(result.disabled).toBe(false);
            expect(new FormData(form).getAll('upload')).toEqual([result.value]);
        });

        it('prevents a form from being submitted twice', async () => {
            const form = document.createElement('form');
            const submit = document.createElement('button');
            submit.type = 'submit';
            form.append(createFullControllerElement(), submit);
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
            form.append(createFullControllerElement(), createFullControllerElement());
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
            form.append(createFullControllerElement());
            container.appendChild(form);

            await tick();

            form.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true }));

            expect(form.classList.contains('ux-upload-form--submitting')).toBe(false);
            expect(form.hasAttribute('aria-busy')).toBe(false);
        });
    });

    describe('file validation', () => {
        it('rejects files exceeding the max size', async () => {
            const element = await mount({ maxSize: 1000 });

            triggerFileSelect(element, [new File(['x'.repeat(2000)], 'large.txt', { type: 'text/plain' })]);
            await tick();

            const errorEl = element.querySelector('[data-ux-upload-target="error"]') as HTMLElement;
            expect(errorEl.hidden).toBe(false);
            expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(0);
        });

        it('rejects disallowed file types', async () => {
            const element = await mount({ allowedTypes: ['image/*', '.pdf'] });

            triggerFileSelect(element, [new File(['test'], 'test.txt', { type: 'text/plain' })]);
            await tick();

            const errorEl = element.querySelector('[data-ux-upload-target="error"]') as HTMLElement;
            expect(errorEl.hidden).toBe(false);
            expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(0);
        });

        it('accepts allowed file types', async () => {
            const element = await mount({ allowedTypes: ['image/*'], autoUpload: false });

            triggerFileSelect(element, [new File(['test'], 'image.png', { type: 'image/png' })]);
            await tick();

            const errorEl = element.querySelector('[data-ux-upload-target="error"]') as HTMLElement;
            expect(errorEl.hidden).toBe(true);
            expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(1);
        });
    });

    describe('drag and drop', () => {
        it('toggles the active class on dragover and dragleave', async () => {
            const element = await mount();
            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;

            dropzone.dispatchEvent(new Event('dragover', { bubbles: true }));
            expect(dropzone.classList.contains('is-active')).toBe(true);

            dropzone.dispatchEvent(new Event('dragleave', { bubbles: true }));
            expect(dropzone.classList.contains('is-active')).toBe(false);
        });

        const dropEvent = (files: File[]): Event => {
            const data = new DataTransfer();
            for (const file of files) {
                data.items.add(file);
            }
            const event = new Event('drop', { bubbles: true, cancelable: true });
            Object.defineProperty(event, 'dataTransfer', { value: data });

            return event;
        };

        it('adds dropped files and clears the active state', async () => {
            const element = await mount({ autoUpload: false, multiple: true });
            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            dropzone.dispatchEvent(new Event('dragover', { bubbles: true }));

            const event = dropEvent([
                new File(['a'], 'first.txt', { type: 'text/plain' }),
                new File(['b'], 'second.txt', { type: 'text/plain' }),
            ]);
            dropzone.dispatchEvent(event);

            expect(event.defaultPrevented).toBe(true);
            expect(dropzone.classList.contains('is-active')).toBe(false);
            expect(Array.from(element.querySelectorAll('.ux-upload__name'), (name) => name.textContent)).toEqual([
                'first.txt',
                'second.txt',
            ]);
        });

        it('ignores dropped files while disabled but still clears the active state', async () => {
            const element = await mount({ autoUpload: false });
            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            dropzone.dispatchEvent(new Event('dragover', { bubbles: true }));
            element.dataset.uxUploadDisabled = 'true';

            dropzone.dispatchEvent(dropEvent([new File(['a'], 'blocked.txt', { type: 'text/plain' })]));

            expect(dropzone.classList.contains('is-active')).toBe(false);
            expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(0);
        });
    });

    describe('file list rendering', () => {
        it('renders a pending item from the template when a file is added', async () => {
            const element = await mount({ autoUpload: false });

            triggerFileSelect(element, [new File(['test'], 'test.txt', { type: 'text/plain' })]);

            const listEl = element.querySelector('[data-ux-upload-target="list"]') as HTMLElement;
            const uploadItems = listEl.querySelectorAll('.ux-upload__item');
            expect(uploadItems).toHaveLength(1);
            expect(uploadItems[0].querySelector('.ux-upload__name')?.textContent).toBe('test.txt');
            expect(uploadItems[0].getAttribute('data-file-id')).toBeTruthy();
            expect(uploadItems[0].getAttribute('data-status')).toBe('pending');
        });
    });

    describe('events', () => {
        it('dispatches progress and complete events with their detail', async () => {
            const element = await mount();
            const progressHandler = vi.fn();
            const completeHandler = vi.fn();
            element.addEventListener('ux-upload:progress', progressHandler);
            element.addEventListener('ux-upload:complete', completeHandler);
            mockSuccessfulUpload('event-upload', 'test.txt', 4, 'text/plain', 'tok-event');

            triggerFileSelect(element, [new File(['test'], 'test.txt', { type: 'text/plain' })]);
            await waitForCompletedItems(element);

            const fileId = (element.querySelector('.ux-upload__item') as HTMLElement).dataset.fileId;
            expect(progressHandler).toHaveBeenCalled();
            expect(progressHandler.mock.lastCall?.[0].detail).toMatchObject({ fileId, percent: 100 });
            expect(completeHandler).toHaveBeenCalledTimes(1);
            expect(completeHandler.mock.calls[0][0].detail).toEqual({
                fileId,
                result: {
                    uploadId: 'event-upload',
                    token: 'tok-event',
                    metadata: { filename: 'test.txt', size: 4, mimeType: 'text/plain' },
                },
            });
        });
    });

    describe('max files limit', () => {
        it('enforces the max files limit', async () => {
            const element = await mount({ maxFiles: 2, autoUpload: false });

            triggerFileSelect(element, [
                new File(['1'], 'file1.txt', { type: 'text/plain' }),
                new File(['2'], 'file2.txt', { type: 'text/plain' }),
                new File(['3'], 'file3.txt', { type: 'text/plain' }),
            ]);

            const listEl = element.querySelector('[data-ux-upload-target="list"]') as HTMLElement;
            expect(listEl.querySelectorAll('.ux-upload__item')).toHaveLength(2);
        });
    });
});
