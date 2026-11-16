import { describe, it, expect, vi } from 'vitest';
import {
    container,
    createFullControllerElement,
    initResponse,
    mockSuccessfulRemove,
    mockSuccessfulUpload,
    mount,
    okJson,
    routedFetch,
    tick,
    triggerFileSelect,
    waitForCompletedItems,
} from './upload_controller_harness';

describe('UxUploadController: presentation', () => {
    it('updates markup supplied by templates without creating presentation elements', async () => {
        const element = await mount({ multiple: true, autoUpload: false, maxFiles: 3 });

        const createElement = vi.spyOn(document, 'createElement');
        triggerFileSelect(element, [
            new File(['one'], 'one.png', { type: 'image/png' }),
            new File(['two'], 'two.pdf', { type: 'application/pdf' }),
        ]);

        expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(2);
        expect(element.querySelector('.ux-upload__preview:not([hidden])')).toBeTruthy();
        expect(element.querySelector('[data-file-type="pdf"]')).toBeTruthy();
        expect(element.querySelector<HTMLElement>('[data-ux-upload-target="summary"]')?.hidden).toBe(false);
        expect(createElement).not.toHaveBeenCalled();
    });

    describe('previews', () => {
        it('shows the template image as preview from selection to completion', async () => {
            const element = await mount();
            mockSuccessfulUpload('up-preview', 'photo.png', 100, 'image/png', 'tok-preview');

            triggerFileSelect(element, [new File(['fake-png-data'], 'photo.png', { type: 'image/png' })]);

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            const img = item.querySelector('.ux-upload__preview') as HTMLImageElement;
            expect(img.hidden).toBe(false);
            expect(img.alt).toBe('photo.png');
            expect(item.dataset.preview).toBe('image');

            await waitForCompletedItems(element);

            expect(img.hidden).toBe(false);
            expect(img.alt).toBe('photo.png');
            expect(item.dataset.preview).toBe('image');
        });

        it('uses compact rows without thumbnails when previews are disabled', async () => {
            const element = await mount({ showPreview: false });
            mockSuccessfulUpload('up-row', 'photo.png', 100, 'image/png', 'tok-row');

            triggerFileSelect(element, [new File(['fake-png-data'], 'photo.png', { type: 'image/png' })]);
            await waitForCompletedItems(element);

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect(item.dataset.preview).toBeUndefined();
            expect(item.dataset.fileType).toBe('default');
            expect(item.querySelector('.ux-upload__file-icon')?.hasAttribute('hidden')).toBe(false);
        });
    });

    describe('summary', () => {
        it('shows the summary for two files and hides it when one is removed', async () => {
            const element = await mount({ multiple: true });

            mockSuccessfulUpload('up-sr1', 'keep.txt', 1000, 'text/plain', 'tok-sr1');
            triggerFileSelect(element, [new File(['x'], 'keep.txt', { type: 'text/plain' })]);
            await waitForCompletedItems(element);

            vi.mocked(fetch).mockReset();
            mockSuccessfulUpload('up-sr2', 'remove-me.txt', 1000, 'text/plain', 'tok-sr2');
            triggerFileSelect(element, [new File(['y'], 'remove-me.txt', { type: 'text/plain' })]);
            await waitForCompletedItems(element, 2);

            const summary = element.querySelector('[data-ux-upload-target="summary"]') as HTMLElement;
            expect(summary.hidden).toBe(false);
            expect(summary.textContent).toContain('2');

            mockSuccessfulRemove();
            (
                element.querySelectorAll('.ux-upload__item')[1].querySelector('.ux-upload__remove') as HTMLButtonElement
            ).click();
            await tick();

            expect(summary.hidden).toBe(true);
        });

        it('rewrites announced summary text only when it changes', async () => {
            const element = await mount({ multiple: true });

            const summaryText = element.querySelector('[data-ux-upload-target="summaryText"]') as HTMLElement;
            const summaryProgress = element.querySelector('[data-ux-upload-target="summaryProgress"]') as HTMLElement;
            const writtenTexts: string[] = [];
            const progressValues: string[] = [];
            const observer = new MutationObserver((records) => {
                if (records.some((record) => record.type !== 'attributes')) {
                    writtenTexts.push(summaryText.textContent ?? '');
                }
                for (const record of records) {
                    if (record.type === 'attributes') {
                        progressValues.push((record.target as HTMLElement).getAttribute('aria-valuenow') ?? '');
                    }
                }
            });
            observer.observe(summaryText, { childList: true, characterData: true, subtree: true });
            observer.observe(summaryProgress, { attributes: true, attributeFilter: ['aria-valuenow'] });

            let nextUpload = 0;
            vi.mocked(fetch).mockImplementation(
                routedFetch({
                    init: () =>
                        okJson(initResponse(`up-live-${++nextUpload}`, { chunkSize: 1, totalChunks: 4, parallel: 1 })),
                    complete: (url) =>
                        okJson({
                            success: true,
                            token: `tok-${url}`,
                            meta: { filename: 'live.txt', size: 4, mimeType: 'text/plain' },
                        }),
                })
            );

            triggerFileSelect(element, [
                new File(['data'], 'first.txt', { type: 'text/plain' }),
                new File(['data'], 'second.txt', { type: 'text/plain' }),
            ]);
            await waitForCompletedItems(element, 2);
            await tick();
            observer.disconnect();

            expect(writtenTexts.length).toBeGreaterThan(1);
            expect(writtenTexts.every((text, index) => 0 === index || text !== writtenTexts[index - 1])).toBe(true);
            expect(writtenTexts[writtenTexts.length - 1]).toBe('2 files uploaded');
            expect(new Set(progressValues).size).toBeGreaterThan(1);
        });
    });

    describe('screen reader announcements', () => {
        it('announces the upload start and completion to screen readers', async () => {
            const element = await mount();
            mockSuccessfulUpload('up-announce', 'announce.txt', 4, 'text/plain', 'tok-announce');

            triggerFileSelect(element, [new File(['data'], 'announce.txt', { type: 'text/plain' })]);

            const announce = element.querySelector('[data-ux-upload-target="announce"]') as HTMLElement;
            expect(announce.textContent).toContain('announce.txt');
            expect(announce.textContent).toContain('started');

            await waitForCompletedItems(element);

            expect(announce.textContent).toContain('announce.txt');
            expect(announce.textContent).toContain('complete');
        });

        it('upload error announced to screen readers', async () => {
            const element = await mount();
            vi.mocked(fetch).mockResolvedValueOnce({
                ok: false,
                statusText: 'Internal Server Error',
                json: () => Promise.resolve({ error: 'Upload initialization failed' }),
            } as Response);

            triggerFileSelect(element, [new File(['data'], 'failing.txt', { type: 'text/plain' })]);

            const announce = element.querySelector('[data-ux-upload-target="announce"]') as HTMLElement;
            await vi.waitFor(
                () => {
                    expect(announce.textContent).toContain('failing.txt');
                    expect(announce.textContent).toContain('failed');
                },
                { timeout: 1000, interval: 5 }
            );
        });
    });

    describe('syncRequired', () => {
        it('restores required when all files removed in multiple mode', async () => {
            const sidecar = JSON.stringify([
                {
                    token: 'tok-req',
                    meta: { filename: 'a.txt', mimeType: 'text/plain', size: 10 },
                },
            ]);
            const element = createFullControllerElement({ multiple: true, resultValue: sidecar });
            element.setAttribute('data-ux-upload-required-value', 'true');
            container.appendChild(element);
            await tick();

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            expect(input.required).toBe(false);

            mockSuccessfulRemove();
            (element.querySelector('.ux-upload__remove') as HTMLButtonElement).click();
            await tick();

            expect(input.required).toBe(true);
        });
    });

    describe('animated removal', () => {
        const completedItem = async (element: HTMLElement): Promise<HTMLElement> => {
            mockSuccessfulUpload('up-anim', 'animated.txt', 4, 'text/plain', 'tok-anim');
            triggerFileSelect(element, [new File(['data'], 'animated.txt', { type: 'text/plain' })]);
            await waitForCompletedItems(element);

            return element.querySelector('.ux-upload__item') as HTMLElement;
        };

        it('removes the item at once when the stylesheet animates nothing', async () => {
            const element = await mount();
            const item = await completedItem(element);
            item.getAnimations = () => [];

            mockSuccessfulRemove();
            (item.querySelector('.ux-upload__remove') as HTMLButtonElement).click();
            await tick();

            expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(0);
        });

        it('keeps the item until its removal animation finishes', async () => {
            const element = await mount();
            const item = await completedItem(element);
            let finish = (): void => {};
            const finished = new Promise<void>((resolve) => {
                finish = resolve;
            });
            item.getAnimations = () => [{ finished } as unknown as Animation];

            mockSuccessfulRemove();
            (item.querySelector('.ux-upload__remove') as HTMLButtonElement).click();
            await tick();

            expect(item.classList.contains('is-removing')).toBe(true);
            expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(1);

            finish();
            await tick();

            expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(0);
        });
    });

    describe('keyboard accessibility', () => {
        it.each([
            ['Enter', 'Enter'],
            ['Space', ' '],
        ])('dropzone activates on %s key', async (_label, key) => {
            const element = await mount();
            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            const clickSpy = vi.spyOn(input, 'click');

            (element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement).dispatchEvent(
                new KeyboardEvent('keydown', { key, bubbles: true })
            );
            await tick();

            expect(clickSpy).toHaveBeenCalled();
        });
    });
});
