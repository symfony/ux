import { describe, it, expect } from 'vitest';
import {
    container,
    createFullControllerElement,
    mockSuccessfulUpload,
    tick,
    waitForCompletedItems,
} from './controller-test-harness';

describe('UxUploadController: paste', () => {
    describe('paste', () => {
        const createPasteEvent = (files: File[]): ClipboardEvent => {
            const dt = new DataTransfer();
            for (const f of files) {
                dt.items.add(f);
            }
            return new ClipboardEvent('paste', { clipboardData: dt, bubbles: true });
        };

        it('adds pasted files to the upload list', async () => {
            const element = createFullControllerElement({ autoUpload: false });
            container.appendChild(element);
            await tick();

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            const file = new File(['hello'], 'document.pdf', { type: 'application/pdf' });
            dropzone.dispatchEvent(createPasteEvent([file]));

            await tick();

            const items = element.querySelectorAll('.ux-upload__item');
            expect(items.length).toBe(1);
            expect(items[0].querySelector('.ux-upload__name')?.textContent).toBe('document.pdf');
        });

        it('renames anonymous screenshot blobs', async () => {
            const element = createFullControllerElement({ autoUpload: false });
            container.appendChild(element);
            await tick();

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            const screenshot = new File([new Uint8Array(10)], 'image.png', { type: 'image/png' });
            dropzone.dispatchEvent(createPasteEvent([screenshot]));

            await tick();

            const nameEl = element.querySelector('.ux-upload__name');
            expect(nameEl?.textContent).toMatch(/^pasted-.*\.png$/);
        });

        it('ignores paste when controller is disabled', async () => {
            const element = createFullControllerElement({ autoUpload: false });
            container.appendChild(element);
            await tick();

            // Disable the controller
            element.dataset.uxUploadDisabled = 'true';

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            dropzone.dispatchEvent(createPasteEvent([new File(['x'], 'a.txt', { type: 'text/plain' })]));

            await tick();

            expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);
        });

        it('ignores paste with no files', async () => {
            const element = createFullControllerElement({ autoUpload: false });
            container.appendChild(element);
            await tick();

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            const dt = new DataTransfer();
            dropzone.dispatchEvent(new ClipboardEvent('paste', { clipboardData: dt, bubbles: true }));

            await tick();

            expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);
        });

        it('respects maxFiles limit', async () => {
            const element = createFullControllerElement({ autoUpload: false, maxFiles: 1 });
            container.appendChild(element);
            await tick();

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            const files = [
                new File(['a'], 'a.txt', { type: 'text/plain' }),
                new File(['b'], 'b.txt', { type: 'text/plain' }),
            ];
            dropzone.dispatchEvent(createPasteEvent(files));

            await tick();

            expect(element.querySelectorAll('.ux-upload__item').length).toBe(1);
        });

        it('respects allowedTypes validation', async () => {
            const element = createFullControllerElement({
                autoUpload: false,
                allowedTypes: ['image/*'],
            });
            container.appendChild(element);
            await tick();

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            const file = new File(['data'], 'script.js', { type: 'application/javascript' });
            dropzone.dispatchEvent(createPasteEvent([file]));

            await tick();

            expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);
            const errorEl = element.querySelector('[data-ux-upload-target="error"]') as HTMLElement;
            expect(errorEl.hidden).toBe(false);
        });

        it('respects maxSize validation', async () => {
            const element = createFullControllerElement({
                autoUpload: false,
                maxSize: 10,
            });
            container.appendChild(element);
            await tick();

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            const file = new File(['x'.repeat(100)], 'big.txt', { type: 'text/plain' });
            dropzone.dispatchEvent(createPasteEvent([file]));

            await tick();

            expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);
        });

        it('triggers auto-upload when autoUpload is true', async () => {
            mockSuccessfulUpload('paste-upload', 'pasted.png', 10, 'image/png', 'tok-paste');

            const element = createFullControllerElement({ autoUpload: true });
            container.appendChild(element);
            await tick();

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            dropzone.dispatchEvent(
                createPasteEvent([new File([new Uint8Array(10)], 'pasted.png', { type: 'image/png' })])
            );

            await waitForCompletedItems(element);

            // fetch should have been called (init endpoint)
            expect(fetch).toHaveBeenCalled();
        });

        it('does not trigger upload when autoUpload is false', async () => {
            const element = createFullControllerElement({ autoUpload: false });
            container.appendChild(element);
            await tick();

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            dropzone.dispatchEvent(createPasteEvent([new File(['x'], 'file.txt', { type: 'text/plain' })]));

            await tick();

            // Item should be pending, no fetch called
            expect(element.querySelectorAll('.ux-upload__item').length).toBe(1);
            expect(fetch).not.toHaveBeenCalled();
        });

        it('handles multiple files in paste on multiple mode', async () => {
            const element = createFullControllerElement({ autoUpload: false, multiple: true });
            container.appendChild(element);
            await tick();

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;
            const files = [
                new File(['a'], 'a.pdf', { type: 'application/pdf' }),
                new File(['b'], 'b.pdf', { type: 'application/pdf' }),
                new File(['c'], 'c.pdf', { type: 'application/pdf' }),
            ];
            dropzone.dispatchEvent(createPasteEvent(files));

            await tick();

            expect(element.querySelectorAll('.ux-upload__item').length).toBe(3);
        });
    });
});
