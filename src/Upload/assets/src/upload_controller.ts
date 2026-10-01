/**
 * Stimulus controller for Symfony UX Upload.
 */

import { Controller } from '@hotwired/stimulus';
import {
    Uploader,
    UploaderEvents,
    UploadSpeed,
    IntegrityAlgorithm,
    UploadCancelledError,
    UploadSuspendedError,
} from './uploader';
import { PreviewCache } from './preview-cache';
import { getFileIconCategory } from './icons';
import { extractFilesFromClipboard } from './paste-utils';
import { formatSize, formatSpeed, formatEta } from './format';
import type { TokenEntry, UploadResult } from './types';

const INTEGRITY_ALGORITHMS: readonly IntegrityAlgorithm[] = ['sha256', 'sha384', 'sha512'];

interface FormSubmissionGuard {
    controllers: number;
    submitting: boolean;
    listener: (event: SubmitEvent) => void;
}

const formSubmissionGuards = new WeakMap<HTMLFormElement, FormSubmissionGuard>();

function toIntegrityAlgorithm(value: string): IntegrityAlgorithm {
    return (INTEGRITY_ALGORITHMS as readonly string[]).includes(value) ? (value as IntegrityAlgorithm) : 'sha256';
}

function connectFormSubmissionGuard(form: HTMLFormElement): void {
    const existing = formSubmissionGuards.get(form);
    if (existing) {
        existing.controllers++;

        return;
    }

    const guard: FormSubmissionGuard = {
        controllers: 1,
        submitting: false,
        listener: (event: SubmitEvent): void => {
            if (event.defaultPrevented) {
                return;
            }
            if (guard.submitting) {
                event.preventDefault();
                event.stopImmediatePropagation();

                return;
            }

            guard.submitting = true;
            form.classList.add('ux-upload-form--submitting');
            form.setAttribute('aria-busy', 'true');

            queueMicrotask(() => {
                if (event.defaultPrevented) {
                    guard.submitting = false;
                    form.classList.remove('ux-upload-form--submitting');
                    form.removeAttribute('aria-busy');
                }
            });
        },
    };

    formSubmissionGuards.set(form, guard);
    form.addEventListener('submit', guard.listener);
}

function disconnectFormSubmissionGuard(form: HTMLFormElement): void {
    const guard = formSubmissionGuards.get(form);
    if (!guard || --guard.controllers > 0) {
        return;
    }

    form.removeEventListener('submit', guard.listener);
    form.classList.remove('ux-upload-form--submitting');
    form.removeAttribute('aria-busy');
    formSubmissionGuards.delete(form);
}

export default class extends Controller<HTMLElement> {
    static targets = [
        'input',
        'dropzone',
        'error',
        'errorTemplate',
        'list',
        'result',
        'template',
        'summary',
        'summaryText',
        'summaryProgress',
        'summaryProgressBar',
        'announce',
        'start',
    ];
    static values = {
        directUrl: String,
        chunkSize: { type: Number, default: 0 },
        initUrl: String,
        removeUrl: String,
        csrfToken: String,
        maxSize: { type: Number, default: 0 },
        maxFiles: { type: Number, default: 0 },
        allowedTypes: { type: Array, default: [] },
        compression: { type: Boolean, default: false },
        multiple: { type: Boolean, default: false },
        required: { type: Boolean, default: false },
        autoUpload: { type: Boolean, default: true },
        showPreview: { type: Boolean, default: false },
        uploader: { type: String, default: 'default' },
        integrityAlgorithm: { type: String, default: 'sha256' },
        policyToken: String,
        // Translatable labels (passed from Twig via |trans)
        labelPending: { type: String, default: 'Pending' },
        labelComplete: { type: String, default: 'Complete' },
        labelCancelled: { type: String, default: 'Cancelled' },
        labelUploadFailed: { type: String, default: 'Upload failed' },
        labelMaxFiles: { type: String, default: 'Maximum number of files reached' },
        labelFileTooLarge: { type: String, default: 'File too large (max %max_size%)' },
        labelFileTypeNotAllowed: { type: String, default: 'File type not allowed' },
        labelSummaryAllComplete: { type: String, default: '%count% files uploaded' },
        labelSummaryUploading: { type: String, default: 'Uploading\u2026 %completed% of %total% complete' },
        labelSummaryPartial: { type: String, default: '%completed% of %total% uploaded, %failed% failed' },
        labelSummaryUploadingWithErrors: {
            type: String,
            default: 'Uploading\u2026 %completed% of %total% complete, %failed% failed',
        },
        labelSummaryDefault: { type: String, default: '%completed% of %total% files uploaded' },
        labelAnnounceStarted: { type: String, default: '%filename%: upload started' },
        labelAnnounceComplete: { type: String, default: '%filename%: upload complete' },
        labelAnnounceFailed: { type: String, default: '%filename%: upload failed' },
        labelAnnounceCancelled: { type: String, default: '%filename%: upload cancelled' },
        labelPaused: { type: String, default: 'Paused' },
        labelAnnouncePaused: { type: String, default: '%filename%: upload paused' },
        labelAnnounceResumed: { type: String, default: '%filename%: upload resumed' },
    };

    declare readonly inputTarget: HTMLInputElement;
    declare readonly dropzoneTarget: HTMLElement;
    declare readonly errorTarget: HTMLElement;
    declare readonly errorTemplateTarget: HTMLTemplateElement;
    declare readonly listTarget: HTMLElement;
    declare readonly resultTarget: HTMLInputElement;
    declare readonly templateTarget: HTMLTemplateElement;

    declare readonly hasInputTarget: boolean;
    declare readonly hasDropzoneTarget: boolean;
    declare readonly hasErrorTarget: boolean;
    declare readonly hasErrorTemplateTarget: boolean;
    declare readonly hasListTarget: boolean;
    declare readonly hasResultTarget: boolean;
    declare readonly hasTemplateTarget: boolean;
    declare readonly summaryTarget: HTMLElement;
    declare readonly hasSummaryTarget: boolean;
    declare readonly summaryTextTarget: HTMLElement;
    declare readonly hasSummaryTextTarget: boolean;
    declare readonly summaryProgressTarget: HTMLElement;
    declare readonly hasSummaryProgressTarget: boolean;
    declare readonly summaryProgressBarTarget: HTMLElement;
    declare readonly hasSummaryProgressBarTarget: boolean;
    declare readonly announceTarget: HTMLElement;
    declare readonly hasAnnounceTarget: boolean;
    declare readonly startTarget: HTMLButtonElement;
    declare readonly hasStartTarget: boolean;

    declare directUrlValue: string;
    declare chunkSizeValue: number;
    declare initUrlValue: string;
    declare removeUrlValue: string;
    declare csrfTokenValue: string;
    declare maxSizeValue: number;
    declare maxFilesValue: number;
    declare allowedTypesValue: string[];
    declare compressionValue: boolean;
    declare multipleValue: boolean;
    declare requiredValue: boolean;
    declare autoUploadValue: boolean;
    declare showPreviewValue: boolean;
    declare uploaderValue: string;
    declare integrityAlgorithmValue: string;
    declare policyTokenValue: string;
    declare labelPendingValue: string;
    declare labelCompleteValue: string;
    declare labelCancelledValue: string;
    declare labelUploadFailedValue: string;
    declare labelMaxFilesValue: string;
    declare labelFileTooLargeValue: string;
    declare labelFileTypeNotAllowedValue: string;
    declare labelSummaryAllCompleteValue: string;
    declare labelSummaryUploadingValue: string;
    declare labelSummaryPartialValue: string;
    declare labelSummaryUploadingWithErrorsValue: string;
    declare labelSummaryDefaultValue: string;
    declare labelAnnounceStartedValue: string;
    declare labelAnnounceCompleteValue: string;
    declare labelAnnounceFailedValue: string;
    declare labelAnnounceCancelledValue: string;
    declare labelPausedValue: string;
    declare labelAnnouncePausedValue: string;
    declare labelAnnounceResumedValue: string;

    private uploader!: Uploader;
    private previewCache!: PreviewCache;
    private cachedBlobUrls: Map<string, string[]> = new Map();
    private uploads: Map<string, { file: File; uploadId?: string; resumable?: boolean; result?: UploadResult }> =
        new Map();
    private fileCounter = 0;
    private fileByFile: Map<File, string> = new Map();
    private dragCounter = 0;
    private fileProgress: Map<string, number> = new Map();
    private lastSummaryText: string | null = null;
    private form: HTMLFormElement | null = null;

    connect(): void {
        this.uploader = new Uploader({
            directUrl: this.directUrlValue || undefined,
            directUploadThreshold: this.chunkSizeValue,
            initUrl: this.initUrlValue,
            removeUrl: this.removeUrlValue || undefined,
            events: this.createEvents(),
            uploader: this.uploaderValue,
            csrfToken: this.csrfTokenValue || null,
            integrityAlgorithm: toIntegrityAlgorithm(this.integrityAlgorithmValue),
            policyToken: this.policyTokenValue || null,
            compression: this.compressionValue,
        });
        this.previewCache = new PreviewCache();
        if (this.hasInputTarget && this.multipleValue) {
            this.inputTarget.multiple = true;
        }
        this.hydrate();
        this.syncRequired();
        this.form = this.element.closest('form');
        if (this.form) {
            connectFormSubmissionGuard(this.form);
        }
    }

    disconnect(): void {
        if (this.form) {
            disconnectFormSubmissionGuard(this.form);
            this.form = null;
        }

        for (const [fileId, upload] of this.uploads) {
            const status = this.findItem(fileId)?.dataset.status;
            if (status !== 'uploading' && status !== 'paused') {
                continue;
            }

            if (upload.uploadId) {
                // Suspending keeps the resumable server session and releases a
                // paused upload, so its promise chain can settle.
                this.uploader.suspend(upload.uploadId);
            } else {
                // No upload ID yet: abort whatever request is still in flight.
                this.uploader.cancelFile(upload.file);
            }
        }

        for (const fileId of Array.from(this.cachedBlobUrls.keys())) {
            this.revokeBlobUrls(fileId);
        }

        this.uploads.clear();
        this.fileByFile.clear();
        this.fileProgress.clear();
    }

    private hydrate(): void {
        if (!this.hasResultTarget || !this.resultTarget.value) {
            return;
        }

        try {
            const raw = JSON.parse(this.resultTarget.value);
            const entries: TokenEntry[] = this.multipleValue
                ? Array.isArray(raw)
                    ? raw
                    : []
                : raw && raw.token
                  ? [raw]
                  : [];

            for (const entry of entries) {
                if (!entry.token || !entry.meta) continue;

                const fileId = `file-${++this.fileCounter}`;
                const file = {
                    name: entry.meta.filename,
                    size: entry.meta.size,
                    type: entry.meta.mimeType,
                } as File;

                const result: UploadResult = { token: entry.token, metadata: entry.meta };
                this.uploads.set(fileId, { file, result });

                this.createUploadItem(fileId, file);
                this.setStatus(fileId, 'completed');

                if (this.showPreviewValue && entry.meta.mimeType?.startsWith('image/')) {
                    this.restoreImagePreview(fileId, entry.token, entry.meta.filename);
                } else {
                    this.showFileTypeIcon(fileId, entry.meta.mimeType, entry.meta.filename);
                }
            }
        } catch {
            // Graceful degradation: hydration failure is non-fatal
        }

        this.updateSummary();
    }

    /**
     * Handle file selection from input
     */
    selectFiles(event: Event): void {
        if (this.isDisabled()) {
            return;
        }

        const input = event.target as HTMLInputElement;
        if (input.files) {
            this.addFiles(Array.from(input.files));
        }
        input.value = '';
    }

    /**
     * Trigger file input click
     */
    browse(event?: Event): void {
        if (this.isDisabled()) {
            return;
        }

        if (event?.target === this.inputTarget) {
            return;
        }

        if (this.hasInputTarget) {
            this.inputTarget.click();
        }
    }

    /**
     * Handle paste (e.g. screenshot from clipboard)
     */
    paste(event: ClipboardEvent): void {
        if (this.isDisabled()) {
            return;
        }

        const files = extractFilesFromClipboard(event);
        if (files.length === 0) {
            return;
        }

        event.preventDefault();
        this.addFiles(files);
    }

    /**
     * Handle keyboard activation on the dropzone
     */
    keydown(event: KeyboardEvent): void {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            this.browse();
        }
    }

    /**
     * Handle file drop
     */
    drop(event: DragEvent): void {
        event.preventDefault();
        this.dragCounter = 0;
        this.removeDropzoneActive();

        if (this.isDisabled()) {
            return;
        }

        if (event.dataTransfer?.files) {
            this.addFiles(Array.from(event.dataTransfer.files));
        }
    }

    /**
     * Handle dragover
     */
    dragover(event: DragEvent): void {
        event.preventDefault();
        if (this.isDisabled()) {
            return;
        }
        if (this.dragCounter === 0) {
            this.dragCounter++;
            this.addDropzoneActive();
        }
    }

    /**
     * Handle dragleave
     */
    dragleave(event: DragEvent): void {
        event.preventDefault();
        if (this.isDisabled()) {
            return;
        }

        const dropzone = this.hasDropzoneTarget ? this.dropzoneTarget : null;
        if (dropzone && event.relatedTarget instanceof Node && dropzone.contains(event.relatedTarget)) {
            return;
        }
        this.dragCounter = 0;
        this.removeDropzoneActive();
    }

    /**
     * Cancel a specific upload
     */
    cancel(event: Event): void {
        if (this.isDisabled()) {
            return;
        }
        this.clearErrors();

        const fileId = this.resolveFileId(event);
        if (!fileId) return;

        const upload = this.uploads.get(fileId);
        const item = this.findItem(fileId);
        if ((item?.dataset.status === 'uploading' || item?.dataset.status === 'paused') && upload?.uploadId) {
            this.uploader.cancel(upload.uploadId);
            this.setStatus(fileId, 'cancelled');
            this.dispatch('cancel', { detail: { fileId } });
        } else if (item?.dataset.status === 'uploading' && upload) {
            this.uploader.cancelFile(upload.file);
            this.setStatus(fileId, 'cancelled');
            this.dispatch('cancel', { detail: { fileId } });
        } else if (item?.dataset.status === 'pending') {
            this.dispatch('cancel', { detail: { fileId } });
            this.removeUpload(fileId);
        }
    }

    /**
     * Remove a completed/cancelled upload from the list
     */
    async remove(event: Event): Promise<void> {
        if (this.isDisabled()) {
            return;
        }
        this.clearErrors();

        const fileId = this.resolveFileId(event);
        if (!fileId) {
            return;
        }
        const token = this.uploads.get(fileId)?.result?.token;
        if (token) {
            try {
                await this.uploader.remove(token);
            } catch (error) {
                this.addError(error instanceof Error ? error.message : 'Failed to remove upload');
                return;
            }
        }
        this.removeUpload(fileId);
    }

    /**
     * Retry a failed upload
     */
    retry(event: Event): void {
        if (this.isDisabled()) {
            return;
        }
        this.clearErrors();

        const fileId = this.resolveFileId(event);
        if (!fileId) return;

        const upload = this.uploads.get(fileId);
        const item = this.findItem(fileId);
        if (upload && item?.dataset.status === 'error') {
            this.dispatch('retry', { detail: { fileId } });
            this.startUpload(fileId, upload.file);
        }
    }

    /**
     * Pause an in-progress upload
     */
    pause(event: Event): void {
        if (this.isDisabled()) {
            return;
        }

        const fileId = this.resolveFileId(event);
        if (!fileId) return;

        const upload = this.uploads.get(fileId);
        const item = this.findItem(fileId);
        if (item?.dataset.status === 'uploading' && upload?.uploadId) {
            this.uploader.pause(upload.uploadId);
            this.setStatus(fileId, 'paused');
            this.dispatch('pause', { detail: { fileId } });
        }
    }

    /**
     * Resume a paused upload
     */
    resumeUpload(event: Event): void {
        if (this.isDisabled()) {
            return;
        }

        const fileId = this.resolveFileId(event);
        if (!fileId) return;

        const upload = this.uploads.get(fileId);
        const item = this.findItem(fileId);
        if (item?.dataset.status === 'paused' && upload?.uploadId) {
            this.uploader.resume(upload.uploadId);
            this.setStatus(fileId, 'uploading');
            this.dispatch('resume', { detail: { fileId } });
        }
    }

    /**
     * Start all pending uploads (used when autoUpload is false)
     */
    startAll(): void {
        if (this.isDisabled()) {
            return;
        }

        for (const [fileId, upload] of this.uploads) {
            const item = this.findItem(fileId);
            if (item?.dataset.status === 'pending') {
                this.startUpload(fileId, upload.file);
            }
        }
    }

    private resolveFileId(event: Event): string | null {
        const target = event.currentTarget as HTMLElement;
        const item = target.closest('[data-ux-upload-item][data-file-id]') as HTMLElement | null;
        return item?.dataset.fileId ?? null;
    }

    private createEvents(): UploaderEvents {
        return {
            onInit: (uploadId, file, resumable) => {
                const fileId = this.fileByFile.get(file);
                if (fileId) {
                    this.fileByFile.delete(file);
                    const upload = this.uploads.get(fileId);
                    if (upload) {
                        upload.uploadId = uploadId;
                        upload.resumable = resumable;
                    }
                    const item = this.findItem(fileId);
                    if (item) {
                        this.syncItemActions(item, item.dataset.status ?? 'pending');
                    }
                    this.dispatch('init', { detail: { uploadId, fileId, resumable } });
                }
            },
            onProgress: (uploadId, percent, _chunkIndex, speed) => {
                const fileId = this.findFileIdByUploadId(uploadId);
                if (fileId) {
                    this.updateProgress(fileId, percent, speed);
                }
            },
            onDirectProgress: (file, percent, speed) => {
                const fileId = this.fileByFile.get(file);
                if (fileId) {
                    this.updateProgress(fileId, percent, speed);
                }
            },
            onComplete: (uploadId, result) => {
                const fileId = this.findFileIdByUploadId(uploadId);
                if (fileId) {
                    this.completeUpload(fileId, result);
                }
            },
            onError: (uploadId, error) => {
                const fileId = this.findFileIdByUploadId(uploadId);
                if (fileId) {
                    this.failUpload(fileId, error.message);
                }
            },
            onChunkComplete: (uploadId, chunkIndex, totalChunks) => {
                this.dispatch('chunk', {
                    detail: { uploadId, chunkIndex, totalChunks },
                });
            },
        };
    }

    private addDropzoneActive(): void {
        if (this.hasDropzoneTarget && !this.isDisabled()) {
            this.dropzoneTarget.classList.add('is-active');
        }
    }

    private removeDropzoneActive(): void {
        if (this.hasDropzoneTarget) {
            this.dropzoneTarget.classList.remove('is-active');
        }
    }

    private isDisabled(): boolean {
        if (this.element.dataset.uxUploadDisabled === 'true') {
            return true;
        }

        if (this.hasInputTarget && this.inputTarget.disabled) {
            return true;
        }

        const fieldset = this.element.closest('fieldset');
        return !!(fieldset && fieldset.hasAttribute('disabled'));
    }

    private addFiles(files: File[]): void {
        if (this.isDisabled()) {
            return;
        }
        this.clearErrors();

        if (this.maxFilesValue > 0) {
            const available = this.maxFilesValue - this.uploads.size;
            if (available <= 0) {
                this.addError(this.labelMaxFilesValue);
                return;
            }
            files = files.slice(0, available);
        }

        for (const file of files) {
            const error = this.validateFile(file);
            if (error) {
                this.addError(error);
                continue;
            }

            const fileId = `file-${++this.fileCounter}`;
            this.uploads.set(fileId, { file });

            this.createUploadItem(fileId, file);

            if (this.showPreviewValue && file.type.startsWith('image/')) {
                const thumbUrl = URL.createObjectURL(file);
                this.showImagePreview(fileId, thumbUrl, file.name);
                this.trackBlobUrl(fileId, thumbUrl);
            } else {
                this.showFileTypeIcon(fileId, file.type, file.name);
            }

            this.dispatch('add', {
                detail: { fileId, file: { name: file.name, size: file.size, type: file.type } },
            });

            if (this.autoUploadValue) {
                this.startUpload(fileId, file);
            }
        }

        this.updateSummary();
    }

    private createUploadItem(fileId: string, file: File): void {
        if (!this.hasListTarget || !this.hasTemplateTarget) return;

        const clone = this.templateTarget.content.cloneNode(true) as DocumentFragment;
        const item = clone.firstElementChild as HTMLElement;

        item.dataset.fileId = fileId;
        item.dataset.progress = '0';
        this.syncItemActions(item, 'pending');

        const nameEl = item.querySelector('[data-ux-upload-name]');
        if (nameEl) {
            nameEl.textContent = file.name;
        }

        const sizeEl = item.querySelector('[data-ux-upload-size]');
        if (sizeEl) {
            sizeEl.textContent = formatSize(file.size);
        }

        // Set up ARIA progressbar
        const progressBar = item.querySelector('[data-ux-upload-progress]');
        if (progressBar) {
            progressBar.setAttribute('role', 'progressbar');
            progressBar.setAttribute('aria-valuenow', '0');
            progressBar.setAttribute('aria-valuemin', '0');
            progressBar.setAttribute('aria-valuemax', '100');
        }

        this.listTarget.appendChild(clone);
    }

    private updateItemDisplay(item: HTMLElement, status: string, progress?: number): void {
        item.dataset.status = status;
        this.syncItemActions(item, status);

        const statusText = item.querySelector('[data-ux-upload-status]');
        if (statusText) {
            switch (status) {
                case 'uploading':
                    statusText.textContent = `${progress ?? 0}%`;
                    break;
                case 'completed':
                    statusText.textContent = this.labelCompleteValue;
                    break;
                case 'error':
                    // error text set separately by setStatus
                    break;
                case 'paused':
                    statusText.textContent = this.labelPausedValue;
                    break;
                case 'cancelled':
                    statusText.textContent = this.labelCancelledValue;
                    break;
                default:
                    statusText.textContent = this.labelPendingValue;
            }
        }
    }

    private syncItemActions(item: HTMLElement, status: string): void {
        const upload = item.dataset.fileId ? this.uploads.get(item.dataset.fileId) : undefined;
        const resumable = upload?.resumable === true;
        const visibleActions = new Set<string>();

        if ('uploading' === status && resumable) visibleActions.add('pause');
        if ('paused' === status && resumable) visibleActions.add('resume');
        if (['pending', 'uploading', 'paused'].includes(status)) visibleActions.add('cancel');
        if (['completed', 'cancelled'].includes(status)) visibleActions.add('remove');
        if ('error' === status) visibleActions.add('retry');

        for (const action of item.querySelectorAll<HTMLButtonElement>('[data-ux-upload-action]')) {
            action.hidden = !visibleActions.has(action.dataset.uxUploadAction ?? '');
            action.disabled = this.isDisabled();
        }
    }

    private setStatus(fileId: string, status: string, error?: string): void {
        const item = this.findItem(fileId);
        if (!item) return;

        const upload = this.uploads.get(fileId);
        const fileName = upload?.file.name ?? 'File';

        this.updateItemDisplay(item, status);

        if (error) {
            const statusText = item.querySelector('[data-ux-upload-status]');
            if (statusText) {
                statusText.textContent = error;
            }
        }

        // Announce status changes to screen readers
        this.announceStatus(fileName, status, error);

        this.updateSummary();
    }

    private findItem(fileId: string): HTMLElement | null {
        return this.element.querySelector<HTMLElement>(`[data-ux-upload-item][data-file-id="${fileId}"]`);
    }

    private restoreImagePreview(fileId: string, token: string, filename: string): void {
        this.previewCache.retrieve(token).then((blobUrl) => {
            if (!blobUrl) return;

            // The item may have been removed (or the controller disconnected)
            // while the cache lookup was pending.
            if (!this.uploads.has(fileId)) {
                URL.revokeObjectURL(blobUrl);
                return;
            }

            this.trackBlobUrl(fileId, blobUrl);
            this.showImagePreview(fileId, blobUrl, filename);
            const item = this.findItem(fileId);
            if (item) {
                const percent = item.querySelector<HTMLElement>('[data-ux-upload-percent]');
                if (percent) percent.hidden = true;
            }
        });
    }

    private validateFile(file: File): string | null {
        if (this.maxSizeValue > 0 && file.size > this.maxSizeValue) {
            return this.labelFileTooLargeValue.replace('%max_size%', formatSize(this.maxSizeValue));
        }

        if (this.allowedTypesValue.length > 0) {
            const allowed = this.allowedTypesValue.some((type) => {
                if (type.endsWith('/*')) {
                    return file.type.startsWith(type.slice(0, -1));
                }
                return file.type === type || file.name.endsWith(type);
            });
            if (!allowed) {
                return this.labelFileTypeNotAllowedValue;
            }
        }

        return null;
    }

    private async startUpload(fileId: string, file: File): Promise<void> {
        const upload = this.uploads.get(fileId);
        if (upload) {
            delete upload.uploadId;
            delete upload.resumable;
        }

        this.setStatus(fileId, 'uploading');

        this.dispatch('start', {
            detail: { fileId, file: { name: file.name, size: file.size, type: file.type } },
        });

        this.fileByFile.set(file, fileId);

        try {
            const result = await this.uploader.upload(file);

            const upload = this.uploads.get(fileId);
            if (upload) {
                upload.result = result;
            }
        } catch (error) {
            if (error instanceof UploadSuspendedError) {
                return;
            }
            if (error instanceof UploadCancelledError) {
                this.setStatus(fileId, 'cancelled');
            } else {
                const message = error instanceof Error ? error.message : this.labelUploadFailedValue;
                this.failUpload(fileId, message);
            }
        }
    }

    private updateProgress(fileId: string, percent: number, speed?: UploadSpeed): void {
        this.fileProgress.set(fileId, percent);
        this.renderProgress(fileId, percent, speed);

        this.dispatch('progress', {
            detail: { fileId, percent, speed },
        });

        this.updateSummary();
    }

    private completeUpload(fileId: string, result: UploadResult): void {
        const upload = this.uploads.get(fileId);
        if (upload) {
            upload.result = result;
        }

        this.fileProgress.set(fileId, 100);
        this.setStatus(fileId, 'completed');

        const item = this.findItem(fileId);
        if (item) {
            item.dataset.progress = '100';
            this.renderProgress(fileId, 100);
        }

        this.updateResultInput();

        if (this.showPreviewValue && upload?.file.type.startsWith('image/')) {
            this.previewCache.store(result.token, upload.file);
            const completedItem = this.findItem(fileId);
            if (completedItem) {
                const percent = completedItem.querySelector<HTMLElement>('[data-ux-upload-percent]');
                if (percent) percent.hidden = true;
            }
        } else if (upload) {
            this.showFileTypeIcon(fileId, upload.file.type, upload.file.name);
        }

        this.dispatch('complete', {
            detail: { fileId, result },
        });

        this.updateSummary();
    }

    private failUpload(fileId: string, error: string): void {
        this.setStatus(fileId, 'error', error);
        this.updateResultInput();

        this.dispatch('error', {
            detail: { fileId, error },
        });
    }

    private trackBlobUrl(fileId: string, url: string): void {
        const urls = this.cachedBlobUrls.get(fileId);
        if (urls) {
            urls.push(url);
        } else {
            this.cachedBlobUrls.set(fileId, [url]);
        }
    }

    private revokeBlobUrls(fileId: string): void {
        const urls = this.cachedBlobUrls.get(fileId);
        if (!urls) return;

        this.cachedBlobUrls.delete(fileId);
        for (const url of urls) {
            URL.revokeObjectURL(url);
        }
    }

    private removeUpload(fileId: string): void {
        this.uploads.delete(fileId);
        this.fileProgress.delete(fileId);
        this.revokeBlobUrls(fileId);
        const item = this.findItem(fileId);

        this.updateResultInput();

        this.dispatch('remove', {
            detail: { fileId },
        });

        this.updateSummary();

        // Animated DOM removal
        if (item) {
            const supportsAnimation = typeof item.getAnimations === 'function';
            const prefersReducedMotion =
                typeof window !== 'undefined' &&
                typeof window.matchMedia === 'function' &&
                window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (supportsAnimation && !prefersReducedMotion) {
                item.classList.add('is-removing');
                const onDone = () => {
                    if (item.parentNode) item.remove();
                };
                item.addEventListener('animationend', onDone, { once: true });
                setTimeout(onDone, 400);
            } else {
                item.remove();
            }
        }
    }

    private renderProgress(fileId: string, percent: number, speed?: UploadSpeed): void {
        const item = this.findItem(fileId);
        if (!item) return;

        const previousPercent = Number(item.dataset.progress ?? '0');
        const duration = this.getProgressAnimationDuration(previousPercent, percent);
        item.dataset.progress = percent.toString();
        item.style.setProperty('--ux-upload-progress-duration', `${duration}s`);

        const progressBar = item.querySelector<HTMLElement>('[data-ux-upload-progress-bar]');
        if (progressBar) {
            progressBar.style.width = `${percent}%`;
        }

        // Update ARIA progressbar
        const progressContainer = item.querySelector('[data-ux-upload-progress]');
        if (progressContainer) {
            progressContainer.setAttribute('aria-valuenow', percent.toString());
        }

        const percentText = item.querySelector('[data-ux-upload-percent]');
        if (percentText) {
            percentText.textContent = `${percent}%`;
        }

        const statusText = item.querySelector('[data-ux-upload-status]');
        if (statusText) {
            let text = `${percent}%`;
            if (speed && percent < 100) {
                const speedStr = formatSpeed(speed.bytesPerSecond);
                const etaStr = formatEta(speed.remainingMs);
                text = `${percent}% \u00B7 ${speedStr}`;
                if (etaStr) {
                    text += ` \u00B7 ${etaStr}`;
                }
            }
            statusText.textContent = text;
        }
    }

    private updateResultInput(): void {
        if (!this.hasResultTarget) return;

        const entries: TokenEntry[] = [];
        for (const [, upload] of this.uploads) {
            if (upload.result) {
                entries.push({
                    token: upload.result.token,
                    meta: upload.result.metadata,
                });
            }
        }

        if (this.multipleValue) {
            this.resultTarget.value = entries.length > 0 ? JSON.stringify(entries) : '[]';
        } else {
            this.resultTarget.value = entries.length > 0 ? JSON.stringify(entries[0]) : '';
        }

        this.resultTarget.dispatchEvent(new Event('change', { bubbles: true }));

        this.syncRequired();
    }

    private findFileIdByUploadId(uploadId: string): string | null {
        for (const [fileId, upload] of this.uploads) {
            if (upload.uploadId === uploadId) {
                return fileId;
            }
        }

        return null;
    }

    private addError(message: string): void {
        if (this.hasErrorTarget) {
            if (this.hasErrorTemplateTarget) {
                const fragment = this.errorTemplateTarget.content.cloneNode(true) as DocumentFragment;
                const item = fragment.firstElementChild;
                if (item) {
                    item.textContent = message;
                    this.errorTarget.appendChild(fragment);
                }
            } else {
                this.errorTarget.textContent = message;
            }
            this.errorTarget.hidden = false;
        }
        this.dispatch('validation-error', { detail: { message } });
    }

    private clearErrors(): void {
        if (this.hasErrorTarget) {
            this.errorTarget.replaceChildren();
            this.errorTarget.hidden = true;
        }
    }

    private syncRequired(): void {
        if (!this.hasInputTarget || !this.requiredValue) return;
        let hasTokens = false;
        if (this.hasResultTarget && this.resultTarget.value) {
            try {
                const parsed = JSON.parse(this.resultTarget.value);
                if (Array.isArray(parsed)) {
                    hasTokens = parsed.some((entry: TokenEntry) => !!entry.token);
                } else if (parsed && parsed.token) {
                    hasTokens = true;
                }
            } catch {
                hasTokens = false;
            }
        }
        this.inputTarget.required = !hasTokens;
    }

    private getProgressAnimationDuration(previous: number, next: number): number {
        const delta = Math.abs(next - previous);
        const base = 0.4;
        const scaled = delta / 50;
        return Math.min(1.5, Math.max(base, scaled + base));
    }

    private updateSummary(): void {
        this.syncDropzoneAvailability();
        this.syncStartAction();

        if (!this.hasSummaryTarget) return;

        const total = this.uploads.size;

        // Hide for single-file uploads or when there are no files
        if (total <= 1) {
            if (this.hasSummaryTextTarget) this.summaryTextTarget.textContent = '';
            if (this.hasSummaryProgressTarget) this.summaryProgressTarget.hidden = true;
            this.summaryTarget.hidden = true;
            this.lastSummaryText = null;
            return;
        }

        let completed = 0;
        let uploading = 0;
        let failed = 0;
        let paused = 0;

        for (const [fileId] of this.uploads) {
            const item = this.findItem(fileId);
            const status = item?.dataset.status;
            if (status === 'completed') completed++;
            else if (status === 'uploading') uploading++;
            else if (status === 'error') failed++;
            else if (status === 'paused') paused++;
        }

        let text: string;
        if (completed === total) {
            text = this.labelSummaryAllCompleteValue.replace('%count%', total.toString());
        } else if (uploading > 0 && failed === 0) {
            text = this.labelSummaryUploadingValue
                .replace('%completed%', completed.toString())
                .replace('%total%', total.toString());
        } else if (failed > 0 && uploading === 0) {
            text = this.labelSummaryPartialValue
                .replace('%completed%', completed.toString())
                .replace('%total%', total.toString())
                .replace('%failed%', failed.toString());
        } else if (failed > 0 && uploading > 0) {
            text = this.labelSummaryUploadingWithErrorsValue
                .replace('%completed%', completed.toString())
                .replace('%total%', total.toString())
                .replace('%failed%', failed.toString());
        } else {
            text = this.labelSummaryDefaultValue
                .replace('%completed%', completed.toString())
                .replace('%total%', total.toString());
        }

        // Calculate aggregate progress across all active uploads
        let batchPercent = 0;
        const activeCount = completed + uploading + paused;
        if (activeCount > 0) {
            let totalPercent = 0;
            for (const [fileId] of this.uploads) {
                const item = this.findItem(fileId);
                const status = item?.dataset.status;
                if (status === 'completed') {
                    totalPercent += 100;
                } else if (status === 'uploading' || status === 'paused') {
                    totalPercent += this.fileProgress.get(fileId) ?? 0;
                }
            }
            batchPercent = Math.round(totalPercent / total);
        }

        // Themes mark the summary as a live region, so rewriting the same text
        // on every progress tick makes screen readers repeat the announcement.
        if (text !== this.lastSummaryText) {
            if (this.hasSummaryTextTarget) {
                this.summaryTextTarget.textContent = text;
            } else {
                this.summaryTarget.textContent = text;
            }
            this.lastSummaryText = text;
        }

        const showProgress = uploading > 0 || paused > 0;
        if (this.hasSummaryProgressTarget) {
            this.summaryProgressTarget.hidden = !showProgress;
            this.summaryProgressTarget.setAttribute('aria-valuenow', batchPercent.toString());
        }
        if (this.hasSummaryProgressBarTarget) {
            this.summaryProgressBarTarget.style.width = `${batchPercent}%`;
        }

        this.summaryTarget.hidden = false;
    }

    private syncStartAction(): void {
        if (!this.hasStartTarget) {
            return;
        }

        const hasPendingUpload = Array.from(this.uploads).some(([fileId]) => {
            return this.findItem(fileId)?.dataset.status === 'pending';
        });

        this.startTarget.hidden = !hasPendingUpload;
        this.startTarget.disabled = this.isDisabled();
    }

    private syncDropzoneAvailability(): void {
        if (!this.hasDropzoneTarget || !this.showPreviewValue || this.maxFilesValue <= 0) {
            return;
        }

        this.dropzoneTarget.hidden = this.uploads.size >= this.maxFilesValue;
    }

    private announceStatus(fileName: string, status: string, error?: string): void {
        if (!this.hasAnnounceTarget) return;

        let announcement: string;
        switch (status) {
            case 'uploading':
                announcement = this.labelAnnounceStartedValue.replace('%filename%', fileName);
                break;
            case 'completed':
                announcement = this.labelAnnounceCompleteValue.replace('%filename%', fileName);
                break;
            case 'error':
                announcement = this.labelAnnounceFailedValue.replace('%filename%', fileName);
                if (error) announcement += ` - ${error}`;
                break;
            case 'paused':
                announcement = this.labelAnnouncePausedValue.replace('%filename%', fileName);
                break;
            case 'cancelled':
                announcement = this.labelAnnounceCancelledValue.replace('%filename%', fileName);
                break;
            default:
                return;
        }

        this.announceTarget.textContent = announcement;
    }

    private showImagePreview(fileId: string, src: string, filename: string): void {
        const item = this.findItem(fileId);
        if (!item) return;

        const preview = item.querySelector<HTMLImageElement>('[data-ux-upload-preview]');
        if (!preview) return;

        preview.src = src;
        preview.alt = filename;
        preview.hidden = false;
        item.querySelector<HTMLElement>('[data-ux-upload-file-icon]')?.setAttribute('hidden', '');
        item.dataset.preview = 'image';
    }

    private showFileTypeIcon(fileId: string, mimeType: string, filename: string): void {
        const item = this.findItem(fileId);
        if (!item) return;

        const category = getFileIconCategory(mimeType, filename);
        item.dataset.fileType = category;
        item.querySelector<HTMLElement>('[data-ux-upload-file-icon]')?.removeAttribute('hidden');

        const preview = item.querySelector<HTMLImageElement>('[data-ux-upload-preview]');
        if (preview) {
            preview.hidden = true;
            preview.removeAttribute('src');
            preview.alt = '';
        }
    }
}
