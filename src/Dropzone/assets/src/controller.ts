/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    declare readonly inputTarget: HTMLInputElement;
    declare readonly placeholderTarget: HTMLDivElement;
    declare readonly previewTarget: HTMLDivElement;
    declare readonly previewClearButtonTarget: HTMLButtonElement;
    declare readonly previewFilenameTarget: HTMLDivElement;
    declare readonly hasPreviewFilenameTarget: boolean;
    declare readonly previewImageTarget: HTMLDivElement;
    declare readonly hasPreviewImageTarget: boolean;
    declare readonly previewListTarget: HTMLUListElement;
    declare readonly hasPreviewListTarget: boolean;
    declare readonly previewItemTemplateTarget: HTMLTemplateElement;
    declare readonly hasPreviewItemTemplateTarget: boolean;

    declare readonly multipleValue: boolean;
    declare readonly removeLabelValue: string;

    static targets = [
        'input',
        'placeholder',
        'preview',
        'previewClearButton',
        'previewFilename',
        'previewImage',
        'previewList',
        'previewItemTemplate',
    ];

    static values = {
        multiple: Boolean,
        removeLabel: { type: String, default: 'Remove' },
    };

    private dataTransfer!: DataTransfer;
    private previewItem: Element | null = null;
    private processingCount = 0;
    private filled = false;
    private dragging = false;
    private queue: Promise<void> = Promise.resolve();
    private generation = 0;

    initialize() {
        this.clear = this.clear.bind(this);
        this.onInputChange = this.onInputChange.bind(this);
        this.onDragEnter = this.onDragEnter.bind(this);
        this.onDragLeave = this.onDragLeave.bind(this);
        this.onDrop = this.onDrop.bind(this);
        this.onMultipleChange = this.onMultipleChange.bind(this);
    }

    connect() {
        // A drag can end while the controller is disconnected
        this.dragging = false;

        this.element.addEventListener('dragenter', this.onDragEnter);
        this.element.addEventListener('dragleave', this.onDragLeave);
        this.element.addEventListener('drop', this.onDrop);

        if (this.multipleValue) {
            this.connectMultiple();
            this.dispatchEvent('connect');
            return;
        }

        // Themes copied from older versions hide these parts with an inline style, which would win over the hidden attribute
        for (const target of [this.previewTarget, ...(this.hasPreviewImageTarget ? [this.previewImageTarget] : [])]) {
            if (target.style.display === 'none') {
                target.style.removeProperty('display');
            }
        }

        // Reset when connecting to work with Turbolinks
        this.clear();

        // Listen on input change and display preview
        this.inputTarget.addEventListener('change', this.onInputChange);

        this.dispatchEvent('connect');
    }

    disconnect() {
        this.generation++;
        this.element.removeEventListener('dragenter', this.onDragEnter);
        this.element.removeEventListener('dragleave', this.onDragLeave);
        this.element.removeEventListener('drop', this.onDrop);

        if (this.multipleValue) {
            this.inputTarget.removeEventListener('change', this.onMultipleChange);
            return;
        }

        this.inputTarget.removeEventListener('change', this.onInputChange);
    }

    // Bound when the button appears, as it is optional and can be rendered after the controller connects
    previewClearButtonTargetConnected(button: HTMLButtonElement) {
        // In multiple mode, files are removed one by one, never cleared together
        if (!this.multipleValue) {
            button.addEventListener('click', this.clear);
        }
    }

    previewClearButtonTargetDisconnected(button: HTMLButtonElement) {
        button.removeEventListener('click', this.clear);
    }

    clear() {
        // The selections still processed must not bring a file back
        this.generation++;
        this.reset();
    }

    private reset() {
        this.inputTarget.value = '';
        if (this.hasPreviewImageTarget) {
            this.previewImageTarget.hidden = true;
            this.previewImageTarget.style.removeProperty('background-image');
        }
        if (this.hasPreviewFilenameTarget) {
            this.previewFilenameTarget.textContent = '';
        }
        this.filled = false;
        this.updateState();

        this.dispatchEvent('clear');
    }

    onInputChange(event: any) {
        const files: File[] = Array.from(event.target.files ?? []);
        if (files.length === 0) {
            return;
        }

        this.preAdd(files, (added) => {
            const file = added[0];
            if (typeof file === 'undefined') {
                this.reset();
                return;
            }

            // A later selection or a listener may have changed the input since this selection was made
            if (this.inputTarget.files?.[0] !== file) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                this.inputTarget.files = dataTransfer.files;
            }

            this.showFile(file);
        });
    }

    _populateImagePreview(target: HTMLElement, file: Blob) {
        this.readAsDataUrl(file, (url) => {
            target.hidden = false;
            target.style.backgroundImage = `url("${url}")`;
        });
    }

    onDragEnter() {
        this.dragging = true;
        this.updateState();
    }

    onDragLeave(event: any) {
        event.preventDefault();

        // Check if we really leave the main drag area
        if (!this.element.contains(event.relatedTarget as Node)) {
            this.dragging = false;
            this.updateState();
        }
    }

    onDrop() {
        // The drop ends the drag even when no "change" follows, as in Chrome when the very same file is dropped again.
        // Deferred so the input stays shown until the browser has put the dropped files into it.
        setTimeout(() => {
            this.dragging = false;
            this.updateState();
        });
    }

    // --- Multiple-file handling ------------------------------------------------

    private connectMultiple() {
        this.previewItem = null;
        if (this.hasPreviewItemTemplateTarget) {
            // Checked before any file is added, so an invalid template cannot leave the input and the list apart
            const elements = this.previewItemTemplateTarget.content.children;
            if (elements.length !== 1) {
                throw new Error('The "previewItemTemplate" target must contain one root element.');
            }
            this.previewItem = elements[0];
        }

        this.dataTransfer = new DataTransfer();

        // Reseed from the input in case the controller reconnects with files already selected
        for (const file of Array.from(this.inputTarget.files ?? [])) {
            this.dataTransfer.items.add(file);
        }

        this.inputTarget.addEventListener('change', this.onMultipleChange);
        this.renderList();
        this.updateState();
    }

    onMultipleChange() {
        const files = Array.from(this.inputTarget.files ?? []);

        // The browser replaced the input's files with the new selection, put back the files already added
        this.inputTarget.files = this.dataTransfer.files;

        this.preAdd(files, (added) => {
            for (const file of added) {
                if (!this.containsFile(file)) {
                    this.dataTransfer.items.add(file);
                }
            }

            this.syncMultiple();
        });
    }

    private showFile(file: File) {
        if (this.hasPreviewFilenameTarget) {
            this.previewFilenameTarget.textContent = file.name;
        }

        // If the file is an image, load it and display it as preview
        if (this.hasPreviewImageTarget) {
            this.previewImageTarget.hidden = true;
            if (file.type && file.type.indexOf('image') !== -1) {
                this._populateImagePreview(this.previewImageTarget, file);
            }
        }

        this.filled = true;
        this.updateState();

        this.dispatchEvent('change', file);
    }

    private preAdd(files: File[], add: (files: File[]) => void) {
        let promise = null as Promise<File[]> | null;
        let prevented = false;
        if (files.length > 0) {
            let dispatching = true;
            const event = this.dispatchEvent(
                'pre-add',
                {
                    files,
                    waitUntil: (filesPromise: File[] | Promise<File[]>) => {
                        if (!dispatching || promise) {
                            throw new Error('waitUntil() can only be called once, from a "dropzone:pre-add" listener.');
                        }
                        promise = Promise.resolve(filesPromise);
                    },
                },
                true
            );
            dispatching = false;
            prevented = event.defaultPrevented;
        }

        if (prevented && promise) {
            // The selection is dropped, but a rejection of its promise must not go unhandled
            promise.catch((error) => console.error(error));
            promise = null;
        }
        const selected = prevented ? [] : files;

        if (!promise && this.processingCount === 0) {
            add(selected);
            return;
        }

        const generation = this.generation;
        this.processingCount++;
        this.updateState();

        const result = (promise ?? Promise.resolve(selected))
            .then((resolved) => {
                if (!Array.isArray(resolved) || !resolved.every((file) => file instanceof File)) {
                    throw new TypeError('The promise passed to waitUntil() must resolve to an array of files.');
                }

                return resolved;
            })
            .catch((error) => {
                console.error(error);

                return files;
            });

        this.queue = this.queue
            .then(() => result)
            .then((resolved) => {
                // The dropzone was cleared or disconnected since this selection
                if (generation === this.generation) {
                    add(resolved);
                }
            })
            .finally(() => {
                this.processingCount--;
                this.updateState();
            });
    }

    private syncMultiple() {
        this.inputTarget.files = this.dataTransfer.files;
        this.renderList();
        this.updateState();
        this.dispatchEvent('change', this.inputTarget.files);
    }

    private removeFileAt(index: number, file: File) {
        this.dataTransfer.items.remove(index);
        this.syncMultiple();
        this.dispatchEvent('remove', file);
    }

    private containsFile(file: File): boolean {
        return Array.from(this.dataTransfer.files).some(
            (existing) =>
                existing.name === file.name &&
                existing.size === file.size &&
                existing.lastModified === file.lastModified
        );
    }

    private renderList() {
        if (!this.hasPreviewListTarget) {
            return;
        }

        const items = Array.from(this.dataTransfer.files).map((file, index) => this.buildListItem(file, index));
        this.previewListTarget.replaceChildren(...items);
    }

    private buildListItem(file: File, index: number): Element {
        if (this.previewItem) {
            return this.buildListItemFromTemplate(this.previewItem, file, index);
        }

        const item = document.createElement('li');
        item.className = 'dropzone-preview-list-item';

        const image = document.createElement('div');
        image.className = 'dropzone-preview-image';
        image.hidden = true;
        if (this.isImage(file)) {
            this._populateImagePreview(image, file);
        }

        const filename = document.createElement('span');
        filename.className = 'dropzone-preview-filename';
        // Always use textContent: file names are user input and must never be treated as HTML
        filename.textContent = file.name;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'dropzone-preview-list-remove';
        this.bindRemove(remove, file, index);

        item.append(image, filename, remove);

        return item;
    }

    private buildListItemFromTemplate(template: Element, file: File, index: number): Element {
        const item = template.cloneNode(true) as Element;
        const slots = (name: string): Element[] => {
            const selector = `[data-dropzone-slot="${name}"]`;

            return [...(item.matches(selector) ? [item] : []), ...Array.from(item.querySelectorAll(selector))];
        };

        const images = slots('image') as HTMLElement[];
        for (const image of images) {
            image.hidden = true;
        }
        if (images.length > 0 && this.isImage(file)) {
            this.readAsDataUrl(file, (url) => {
                for (const image of images) {
                    if (image instanceof HTMLImageElement) {
                        image.src = url;
                    } else {
                        image.style.backgroundImage = `url("${url}")`;
                    }
                    image.hidden = false;
                }
            });
        }

        for (const filename of slots('filename')) {
            // Always use textContent: file names are user input and must never be treated as HTML
            filename.textContent = file.name;
        }

        for (const remove of slots('remove')) {
            this.bindRemove(remove, file, index);
        }

        return item;
    }

    private isImage(file: File): boolean {
        return !!file.type && file.type.indexOf('image') !== -1;
    }

    private readAsDataUrl(file: Blob, onLoad: (url: string) => void) {
        if (typeof FileReader === 'undefined') {
            // FileReader API not available, skip
            return;
        }

        const reader = new FileReader();
        reader.addEventListener('load', () => onLoad(reader.result as string));
        reader.readAsDataURL(file);
    }

    private bindRemove(element: Element, file: File, index: number) {
        element.setAttribute('aria-label', `${this.removeLabelValue} ${file.name}`);
        element.addEventListener('click', () => this.removeFileAt(index, file));
    }

    private updateState() {
        const filled = this.multipleValue ? this.dataTransfer.files.length > 0 : this.filled;

        let state = filled ? 'filled' : 'empty';
        if (this.dragging) {
            state = 'dragging';
        }
        if (this.processingCount > 0) {
            state = 'processing';
        }
        this.element.setAttribute('data-state', state);

        if (this.multipleValue) {
            return;
        }

        // Show the input and placeholder to receive a drop, and the preview of the file otherwise
        const showPreview = filled && !this.dragging;
        this.inputTarget.hidden = showPreview;
        this.placeholderTarget.hidden = showPreview;
        this.previewTarget.hidden = !showPreview;
    }

    private dispatchEvent(name: string, payload: any = {}, cancelable = false) {
        return this.dispatch(name, { detail: payload, prefix: 'dropzone', cancelable });
    }
}
