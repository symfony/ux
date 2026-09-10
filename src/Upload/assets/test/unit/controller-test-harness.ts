import { afterEach, beforeEach, vi } from 'vitest';
import { Application } from '@hotwired/stimulus';
import UxUploadController from '../../src/upload_controller';

let application: Application;
export let container: HTMLElement;

export const createUploadItemTemplate = (showPreview = true) => `
    <template data-ux-upload-target="template">
        <div class="ux-upload__item" data-ux-upload-item data-status="pending">
            <div class="ux-upload__visual">
                ${showPreview ? '<img class="ux-upload__preview" data-ux-upload-preview alt="" hidden>' : ''}
                <span class="ux-upload__file-icon" data-ux-upload-file-icon><svg viewBox="0 0 24 24"></svg></span>
                <span class="ux-upload__percent" data-ux-upload-percent>0%</span>
            </div>
            <div class="ux-upload__metadata">
                <span class="ux-upload__name" data-ux-upload-name></span>
                <span class="ux-upload__size" data-ux-upload-size></span>
            </div>
            <div class="ux-upload__progress" data-ux-upload-progress>
                <div class="ux-upload__progress-bar" data-ux-upload-progress-bar style="width: 0%"></div>
            </div>
            <span class="ux-upload__status" data-ux-upload-status>Pending</span>
            <div class="ux-upload__actions">
                <button type="button" class="ux-upload__pause" data-ux-upload-action="pause" hidden
                    data-action="ux-upload#pause">Pause</button>
                <button type="button" class="ux-upload__resume" data-ux-upload-action="resume" hidden
                    data-action="ux-upload#resumeUpload">Resume</button>
                <button type="button" class="ux-upload__cancel" data-ux-upload-action="cancel"
                    data-action="ux-upload#cancel">Cancel</button>
                <button type="button" class="ux-upload__remove" data-ux-upload-action="remove" hidden
                    data-action="ux-upload#remove">Remove</button>
                <button type="button" class="ux-upload__retry" data-ux-upload-action="retry" hidden
                    data-action="ux-upload#retry">Retry</button>
            </div>
        </div>
    </template>
`;

export const createControllerElement = (
    options: {
        initUrl?: string;
        maxSize?: number;
        maxFiles?: number;
        allowedTypes?: string[];
        multiple?: boolean;
        autoUpload?: boolean;
    } = {}
): HTMLElement => {
    const {
        initUrl = '/upload/init',
        maxSize = 0,
        maxFiles = 0,
        allowedTypes = [],
        multiple = false,
        autoUpload = true,
    } = options;

    const element = document.createElement('div');
    element.innerHTML = `
        <div data-controller="ux-upload"
             data-ux-upload-init-url-value="${initUrl}"
             data-ux-upload-max-size-value="${maxSize}"
             data-ux-upload-max-files-value="${maxFiles}"
             data-ux-upload-allowed-types-value='${JSON.stringify(allowedTypes)}'
             data-ux-upload-multiple-value="${multiple}"
             data-ux-upload-auto-upload-value="${autoUpload}">
            <div data-ux-upload-target="dropzone" class="ux-upload__dropzone"
                 data-action="dragover->ux-upload#dragover dragleave->ux-upload#dragleave drop->ux-upload#drop click->ux-upload#browse paste->ux-upload#paste">
                <input type="file" data-ux-upload-target="input"
                       data-action="change->ux-upload#selectFiles">
            </div>
            <div data-ux-upload-target="error" class="ux-upload__errors" hidden></div>
            <template data-ux-upload-target="errorTemplate"><div class="ux-upload__error"></div></template>
            <div data-ux-upload-target="list" class="ux-upload-list"></div>
            ${createUploadItemTemplate()}
        </div>
    `;

    return element.firstElementChild as HTMLElement;
};

export const mockSuccessfulRemove = (): void => {
    vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true }),
    } as Response);
};

beforeEach(async () => {
    document.body.innerHTML = '';
    container = document.createElement('div');
    document.body.appendChild(container);

    application = Application.start();
    application.register('ux-upload', UxUploadController);

    vi.mocked(fetch).mockReset();
});

afterEach(async () => {
    document.body.replaceChildren();
    await tick();
    application.stop();
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
});

export const createFullControllerElement = (
    options: {
        initUrl?: string;
        directUrl?: string;
        chunkSize?: number;
        removeUrl?: string;
        maxSize?: number;
        maxFiles?: number;
        allowedTypes?: string[];
        multiple?: boolean;
        autoUpload?: boolean;
        showPreview?: boolean;
        resultValue?: string;
    } = {}
): HTMLElement => {
    const {
        initUrl = '/upload/init',
        directUrl,
        chunkSize = 0,
        removeUrl,
        maxSize = 0,
        maxFiles = 0,
        allowedTypes = [],
        multiple = false,
        autoUpload = true,
        showPreview = true,
        resultValue = '',
    } = options;

    const escapedResultValue = resultValue.replace(/'/g, '&#39;');

    const element = document.createElement('div');
    element.innerHTML = `
        <div data-controller="ux-upload" class="${showPreview ? 'ux-upload--previews' : ''}"
             data-ux-upload-init-url-value="${initUrl}"
             ${directUrl ? `data-ux-upload-direct-url-value="${directUrl}"` : ''}
             data-ux-upload-chunk-size-value="${chunkSize}"
             ${removeUrl ? `data-ux-upload-remove-url-value="${removeUrl}"` : ''}
             data-ux-upload-max-size-value="${maxSize}"
             data-ux-upload-max-files-value="${maxFiles}"
             data-ux-upload-allowed-types-value='${JSON.stringify(allowedTypes)}'
             data-ux-upload-multiple-value="${multiple}"
             data-ux-upload-auto-upload-value="${autoUpload}"
             data-ux-upload-show-preview-value="${showPreview}">
            <div data-ux-upload-target="dropzone" class="ux-upload__dropzone"
                 tabindex="0" role="button"
                 data-action="dragover->ux-upload#dragover dragleave->ux-upload#dragleave drop->ux-upload#drop click->ux-upload#browse keydown->ux-upload#keydown paste->ux-upload#paste">
                <input type="file" data-ux-upload-target="input"
                       data-action="change->ux-upload#selectFiles">
            </div>
            <div data-ux-upload-target="error" class="ux-upload__errors" hidden></div>
            <template data-ux-upload-target="errorTemplate"><div class="ux-upload__error"></div></template>
            <div data-ux-upload-target="list" class="ux-upload-list"></div>
            <div data-ux-upload-target="summary" class="ux-upload__summary" hidden>
                <span data-ux-upload-target="summaryText" class="ux-upload__summary-text"></span>
                <span data-ux-upload-target="summaryProgress" class="ux-upload__summary-progress" hidden>
                    <span data-ux-upload-target="summaryProgressBar" class="ux-upload__summary-progress-bar"></span>
                </span>
            </div>
            <div data-ux-upload-target="announce" class="sr-only" aria-live="polite"></div>
            <input type="hidden" data-ux-upload-target="result" value='${escapedResultValue}'>
            ${
                autoUpload
                    ? ''
                    : '<button type="button" data-ux-upload-target="start" data-action="ux-upload#startAll" hidden>Start uploads</button>'
            }
            ${createUploadItemTemplate(showPreview)}
        </div>
    `;

    return element.firstElementChild as HTMLElement;
};

/**
 * Mock a full successful upload flow (init -> resume check -> chunk -> complete).
 */
export const mockSuccessfulUpload = (
    uploadId: string,
    filename: string,
    size: number,
    mimeType: string,
    token: string
) => {
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
        .mockResolvedValueOnce({ ok: true } as Response)
        .mockResolvedValueOnce({
            ok: true,
            json: () =>
                Promise.resolve({
                    success: true,
                    token,
                    meta: { filename, size, mimeType },
                }),
        } as Response);
};

/**
 * Helper: trigger a file change event on the input.
 */
export const triggerFileSelect = (element: HTMLElement, files: File[]) => {
    const input = element.querySelector('[data-ux-upload-target="input"]') as HTMLInputElement;
    Object.defineProperty(input, 'files', { value: files, configurable: true });
    input.dispatchEvent(new Event('change'));
};

export const tick = () => new Promise<void>((resolve) => setTimeout(resolve, 0));

export const waitForCompletedItems = (element: HTMLElement, count = 1) =>
    vi.waitFor(
        () => {
            const completed = element.querySelectorAll('[data-ux-upload-item][data-status="completed"]');
            if (completed.length !== count) {
                const statuses = Array.from(
                    element.querySelectorAll('[data-ux-upload-item]'),
                    (item) => (item as HTMLElement).dataset.status
                );
                const error = element.querySelector('[data-ux-upload-target="error"]')?.textContent;
                const requests = vi.mocked(fetch).mock.calls.map(([url, options]) => [String(url), options?.method]);
                throw new Error(
                    `Expected ${count} completed item(s); statuses=${JSON.stringify(statuses)}, error=${JSON.stringify(error)}, requests=${JSON.stringify(requests)}`
                );
            }
        },
        { timeout: 1000, interval: 5 }
    );

export const pendingUntilAborted = (_url: RequestInfo | URL, options?: RequestInit): Promise<Response> =>
    new Promise((_resolve, reject) => {
        const abort = () => reject(new DOMException('The operation was aborted.', 'AbortError'));
        if (options?.signal?.aborted) {
            abort();
            return;
        }
        options?.signal?.addEventListener('abort', abort, { once: true });
    });
