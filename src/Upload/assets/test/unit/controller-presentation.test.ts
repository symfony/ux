import { describe, it, expect, vi } from 'vitest';
import {
    container,
    createFullControllerElement,
    mockSuccessfulRemove,
    mockSuccessfulUpload,
    tick,
    triggerFileSelect,
    waitForCompletedItems,
} from './controller-test-harness';

describe('UxUploadController: presentation', () => {
    it('updates markup supplied by templates without creating presentation elements', async () => {
        const element = createFullControllerElement({ multiple: true, autoUpload: false, maxFiles: 3 });
        container.appendChild(element);
        await tick();

        const createElement = vi.spyOn(document, 'createElement');
        triggerFileSelect(element, [
            new File(['one'], 'one.png', { type: 'image/png' }),
            new File(['two'], 'two.pdf', { type: 'application/pdf' }),
        ]);

        expect(element.querySelectorAll('.ux-upload__item')).toHaveLength(2);
        expect(element.querySelector('.ux-upload__preview:not([hidden])')).toBeTruthy();
        expect(element.querySelector('[data-file-type="pdf"]')).toBeTruthy();
        expect(element.querySelector('[data-ux-upload-target="summary"]')?.hidden).toBe(false);
        expect(createElement).not.toHaveBeenCalled();
    });

    describe('previews', () => {
        it('image files show preview thumbnail', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            mockSuccessfulUpload('up-preview', 'photo.png', 100, 'image/png', 'tok-preview');

            const file = new File(['fake-png-data'], 'photo.png', { type: 'image/png' });
            triggerFileSelect(element, [file]);

            await waitForCompletedItems(element);

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            const img = item.querySelector('.ux-upload__preview') as HTMLImageElement;
            expect(img.hidden).toBe(false);
            expect(item.dataset.preview).toBe('image');
        });

        it('non-image files show generic icon', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            mockSuccessfulUpload('up-nopreview', 'doc.pdf', 100, 'application/pdf', 'tok-nopreview');

            const file = new File(['fake-pdf-data'], 'doc.pdf', { type: 'application/pdf' });
            triggerFileSelect(element, [file]);

            await waitForCompletedItems(element);

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            const img = item.querySelector('.ux-upload__preview') as HTMLImageElement;
            expect(img.hidden).toBe(true);
            expect(item.dataset.fileType).toBe('pdf');
            expect(item.querySelector('.ux-upload__file-icon svg')).toBeTruthy();
        });

        it('preview alt text matches filename', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            mockSuccessfulUpload('up-alt', 'landscape.jpg', 50, 'image/jpeg', 'tok-alt');

            const file = new File(['fake-jpg-data'], 'landscape.jpg', { type: 'image/jpeg' });
            triggerFileSelect(element, [file]);

            await waitForCompletedItems(element);

            const img = element.querySelector('.ux-upload__preview') as HTMLImageElement;
            expect(img).toBeTruthy();
            expect(img.alt).toBe('landscape.jpg');
        });

        it('uses compact rows without thumbnails when previews are disabled', async () => {
            const element = createFullControllerElement({ showPreview: false });
            container.appendChild(element);

            await tick();

            mockSuccessfulUpload('up-row', 'photo.png', 100, 'image/png', 'tok-row');
            triggerFileSelect(element, [new File(['fake-png-data'], 'photo.png', { type: 'image/png' })]);

            await waitForCompletedItems(element);

            expect(element.classList.contains('ux-upload--previews')).toBe(false);
            expect(element.querySelector('.ux-upload__preview')).toBeNull();
            expect(element.querySelector('.ux-upload__file-icon svg')).toBeTruthy();
        });
    });

    // ---------------------------------------------------------------
    // Summary tests
    // ---------------------------------------------------------------

    describe('summary', () => {
        it('summary updates with file count and total size', async () => {
            const element = createFullControllerElement({ multiple: true });
            container.appendChild(element);

            await tick();

            // Upload 2 files to trigger summary (summary only shows for >1 files)
            mockSuccessfulUpload('up-s1', 'a.txt', 1000, 'text/plain', 'tok-s1');

            triggerFileSelect(element, [new File(['x'.repeat(1000)], 'a.txt', { type: 'text/plain' })]);

            await waitForCompletedItems(element);

            vi.mocked(fetch).mockReset();
            mockSuccessfulUpload('up-s2', 'b.txt', 2000, 'text/plain', 'tok-s2');

            triggerFileSelect(element, [new File(['y'.repeat(2000)], 'b.txt', { type: 'text/plain' })]);

            await waitForCompletedItems(element, 2);

            const summary = element.querySelector('[data-ux-upload-target="summary"]') as HTMLElement;
            expect(summary).toBeTruthy();
            // With 2 completed files, summary should display the all-complete label
            expect(summary.hidden).toBe(false);
            expect(summary.textContent).toContain('2');
        });

        it('summary reflects cancellations and removals', async () => {
            const element = createFullControllerElement({ multiple: true });
            container.appendChild(element);

            await tick();

            // Upload 2 files
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

            // Remove one file
            const items = element.querySelectorAll('.ux-upload__item');
            const removeBtn = items[1].querySelector('.ux-upload__remove') as HTMLButtonElement;
            mockSuccessfulRemove();
            removeBtn.click();

            await tick();

            // With only 1 file, summary should be hidden (controller hides for <=1)
            expect(summary.hidden).toBe(true);
        });

        it('rewrites announced summary text only when it changes', async () => {
            const element = createFullControllerElement({ multiple: true });
            container.appendChild(element);
            await tick();

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
            vi.mocked(fetch).mockImplementation((url, options) => {
                const method = (options as RequestInit)?.method ?? 'GET';
                if (method === 'POST' && String(url).endsWith('/init')) {
                    const uploadId = `up-live-${++nextUpload}`;
                    return Promise.resolve({
                        ok: true,
                        json: () =>
                            Promise.resolve({
                                uploadId,
                                uploadUrl: `/upload/${uploadId}?sig=test`,
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
                if (method === 'PUT') {
                    return Promise.resolve({ ok: true } as Response);
                }

                return Promise.resolve({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            token: `tok-${String(url)}`,
                            meta: { filename: 'live.txt', size: 4, mimeType: 'text/plain' },
                        }),
                } as Response);
            });

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

    // ---------------------------------------------------------------
    // Screen reader announcement tests
    // ---------------------------------------------------------------

    describe('screen reader announcements', () => {
        it('upload start announced to screen readers', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            let resolveChunk!: (response: Response) => void;
            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            uploadId: 'up-announce-start',
                            uploadUrl: '/upload/up-announce-start?sig=test',
                            config: { chunkSize: 5 * 1024 * 1024, totalChunks: 1, compression: false, parallel: 3 },
                        }),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockImplementationOnce(
                    () =>
                        new Promise<Response>((resolve) => {
                            resolveChunk = resolve;
                        })
                )
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            token: 'tok-announce-start',
                            meta: { filename: 'announce.txt', mimeType: 'text/plain', size: 4 },
                        }),
                } as Response);

            triggerFileSelect(element, [new File(['data'], 'announce.txt', { type: 'text/plain' })]);

            await tick();

            const announce = element.querySelector('[data-ux-upload-target="announce"]') as HTMLElement;
            expect(announce).toBeTruthy();
            expect(announce.textContent).toContain('announce.txt');
            expect(announce.textContent).toContain('started');

            await vi.waitFor(() => expect(resolveChunk).toBeTypeOf('function'));
            resolveChunk({ ok: true } as Response);
            await waitForCompletedItems(element);
        });

        it('upload complete announced to screen readers', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            mockSuccessfulUpload('up-announce-done', 'finished.txt', 4, 'text/plain', 'tok-announce-done');

            triggerFileSelect(element, [new File(['data'], 'finished.txt', { type: 'text/plain' })]);

            await waitForCompletedItems(element);

            const announce = element.querySelector('[data-ux-upload-target="announce"]') as HTMLElement;
            expect(announce).toBeTruthy();
            expect(announce.textContent).toContain('finished.txt');
            expect(announce.textContent).toContain('complete');
        });

        it('upload error announced to screen readers', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            vi.mocked(fetch).mockResolvedValueOnce({
                ok: false,
                statusText: 'Internal Server Error',
                json: () => Promise.resolve({ error: 'Upload initialization failed' }),
            } as Response);

            triggerFileSelect(element, [new File(['data'], 'failing.txt', { type: 'text/plain' })]);

            const announce = element.querySelector('[data-ux-upload-target="announce"]') as HTMLElement;
            expect(announce).toBeTruthy();
            await vi.waitFor(
                () => {
                    expect(announce.textContent).toContain('failing.txt');
                    expect(announce.textContent).toContain('failed');
                },
                { timeout: 1000, interval: 5 }
            );
        });
    });

    // ---------------------------------------------------------------
    // syncRequired tests
    // ---------------------------------------------------------------

    describe('syncRequired', () => {
        it('restores required when all files removed in multiple mode', async () => {
            const sidecar = JSON.stringify([
                {
                    token: 'tok-req',
                    meta: { filename: 'a.txt', mimeType: 'text/plain', size: 10 },
                },
            ]);
            const element = createFullControllerElement({ multiple: true, resultValue: sidecar });
            // Mark the input as required via the Stimulus value
            element.setAttribute('data-ux-upload-required-value', 'true');
            container.appendChild(element);

            await tick();

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            // With a token present, required should be false
            expect(input.required).toBe(false);

            // Remove the file
            const removeBtn = element.querySelector('.ux-upload__remove') as HTMLButtonElement;
            mockSuccessfulRemove();
            removeBtn.click();

            await tick();

            // After removal, result is '[]', so required should be true again
            expect(input.required).toBe(true);
        });
    });

    // ---------------------------------------------------------------
    // Animated removal tests
    // ---------------------------------------------------------------

    describe('animated removal', () => {
        it('removes item immediately in JSDOM (no getAnimations)', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            mockSuccessfulUpload('up-anim-remove', 'anim.txt', 4, 'text/plain', 'tok-anim');

            const file = new File(['data'], 'anim.txt', { type: 'text/plain' });
            triggerFileSelect(element, [file]);

            await waitForCompletedItems(element);

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect(item).toBeTruthy();
            expect(item.dataset.status).toBe('completed');

            // In JSDOM, getAnimations is not available, so removal should be immediate
            const removeBtn = item.querySelector('.ux-upload__remove') as HTMLButtonElement;
            mockSuccessfulRemove();
            removeBtn.click();

            await tick();

            expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);
        });

        it('adds removing class when getAnimations is available', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            mockSuccessfulUpload('up-anim-cls', 'animated.txt', 4, 'text/plain', 'tok-anim-cls');

            const file = new File(['data'], 'animated.txt', { type: 'text/plain' });
            triggerFileSelect(element, [file]);

            await waitForCompletedItems(element);

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect(item).toBeTruthy();
            expect(item.dataset.status).toBe('completed');

            // Mock getAnimations on this element to enable the animated path
            item.getAnimations = () => [];

            // Mock matchMedia to return prefers-reduced-motion: false
            const originalMatchMedia = window.matchMedia;
            window.matchMedia = vi.fn().mockReturnValue({ matches: false });

            const removeBtn = item.querySelector('.ux-upload__remove') as HTMLButtonElement;
            mockSuccessfulRemove();
            removeBtn.click();
            await tick();

            // Immediately after click: item should have the removing class
            expect(item.classList.contains('is-removing')).toBe(true);

            // Item should still be in the DOM (waiting for animationend or timeout)
            expect(element.querySelectorAll('.ux-upload__item').length).toBe(1);

            // Trigger animationend to complete removal
            item.dispatchEvent(new Event('animationend'));

            await tick();

            expect(element.querySelectorAll('.ux-upload__item').length).toBe(0);

            // Restore matchMedia
            window.matchMedia = originalMatchMedia;
        });
    });

    // ---------------------------------------------------------------
    // Template-owned thumbnail tests
    // ---------------------------------------------------------------

    describe('template-owned thumbnail', () => {
        it('populates the image element supplied by the template', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            let resolveChunk!: (response: Response) => void;
            vi.mocked(fetch)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            uploadId: 'up-thumb',
                            uploadUrl: '/upload/up-thumb?sig=test',
                            config: { chunkSize: 5 * 1024 * 1024, totalChunks: 1, compression: false, parallel: 3 },
                        }),
                } as Response)
                .mockResolvedValueOnce({
                    ok: true,
                    json: () => Promise.resolve({ progress: { chunkIndices: [] } }),
                } as Response)
                .mockImplementationOnce(
                    () =>
                        new Promise<Response>((resolve) => {
                            resolveChunk = resolve;
                        })
                )
                .mockResolvedValueOnce({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            success: true,
                            token: 'tok-thumb',
                            meta: { filename: 'thumb.png', mimeType: 'image/png', size: 8 },
                        }),
                } as Response);

            const file = new File(['fake-img'], 'thumb.png', { type: 'image/png' });
            triggerFileSelect(element, [file]);

            await tick();

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect(item).toBeTruthy();

            const thumbImg = item.querySelector('.ux-upload__preview') as HTMLImageElement;
            expect(thumbImg).toBeTruthy();
            expect(thumbImg.hidden).toBe(false);
            expect(item.dataset.preview).toBe('image');

            await vi.waitFor(() => expect(resolveChunk).toBeTypeOf('function'));
            resolveChunk({ ok: true } as Response);
            await waitForCompletedItems(element);

            expect(thumbImg.getAttribute('alt')).toBe('thumb.png');
        });
    });

    // ---------------------------------------------------------------
    // Keyboard accessibility tests
    // ---------------------------------------------------------------

    describe('keyboard accessibility', () => {
        it('dropzone activates on Enter key', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            const clickSpy = vi.spyOn(input, 'click');

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;

            const enterEvent = new KeyboardEvent('keydown', { key: 'Enter', bubbles: true });
            dropzone.dispatchEvent(enterEvent);

            await tick();

            expect(clickSpy).toHaveBeenCalled();
        });

        it('dropzone activates on Space key', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
            const clickSpy = vi.spyOn(input, 'click');

            const dropzone = element.querySelector('[data-ux-upload-target="dropzone"]') as HTMLElement;

            const spaceEvent = new KeyboardEvent('keydown', { key: ' ', bubbles: true });
            dropzone.dispatchEvent(spaceEvent);

            await tick();

            expect(clickSpy).toHaveBeenCalled();
        });

        it('action buttons are keyboard focusable', async () => {
            const element = createFullControllerElement();
            container.appendChild(element);

            await tick();

            mockSuccessfulUpload('up-focus', 'focus.txt', 4, 'text/plain', 'tok-focus');

            triggerFileSelect(element, [new File(['data'], 'focus.txt', { type: 'text/plain' })]);

            await waitForCompletedItems(element);

            const item = element.querySelector('.ux-upload__item') as HTMLElement;
            expect(item).toBeTruthy();

            // All action buttons should be <button> elements (inherently focusable)
            const buttons = item.querySelectorAll('.ux-upload__actions button');
            expect(buttons.length).toBeGreaterThan(0);

            for (const btn of buttons) {
                const el = btn as HTMLButtonElement;
                // Buttons are inherently focusable. Verify they are not explicitly
                // removed from tab order (tabindex=-1 would prevent keyboard access).
                expect(el.tabIndex).not.toBe(-1);
                expect(el.tagName).toBe('BUTTON');
            }
        });
    });
});
