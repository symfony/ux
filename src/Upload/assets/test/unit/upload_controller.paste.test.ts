import { describe, it, expect } from 'vitest';
import { createPasteEvent, mount, tick } from './upload_controller_harness';

describe('UxUploadController: paste', () => {
    const paste = (element: HTMLElement, event: ClipboardEvent): void => {
        (element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement).dispatchEvent(event);
    };

    it('adds pasted files to the upload list', async () => {
        const element = await mount({ autoUpload: false });

        paste(element, createPasteEvent([new File(['hello'], 'document.pdf', { type: 'application/pdf' })]));
        await tick();

        const items = element.querySelectorAll('.ux-upload__item');
        expect(items.length).toBe(1);
        expect(items[0].querySelector('.ux-upload__name')?.textContent).toBe('document.pdf');
        expect(fetch).not.toHaveBeenCalled();
    });

    it('ignores paste when controller is disabled', async () => {
        const element = await mount({ autoUpload: false });
        element.dataset.uxUploadDisabled = 'true';

        paste(element, createPasteEvent([new File(['x'], 'a.txt', { type: 'text/plain' })]));
        await tick();

        expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);
    });

    it('ignores paste with no files', async () => {
        const element = await mount({ autoUpload: false });

        paste(element, createPasteEvent([]));
        await tick();

        expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);
    });
});
