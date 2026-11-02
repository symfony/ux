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
    declare readonly previewImageTarget: HTMLDivElement;
    declare readonly previewListTarget: HTMLUListElement;
    declare readonly hasPreviewListTarget: boolean;

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
    ];

    static values = {
        multiple: Boolean,
        removeLabel: { type: String, default: 'Remove' },
    };

    private dataTransfer!: DataTransfer;
    private processingCount = 0;
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
        // A page snapshot taken while files were processed keeps the state of the previous controller
        if (this.processingCount === 0) {
            this.element.removeAttribute('data-state');
        }

        if (this.multipleValue) {
            this.connectMultiple();
            this.dispatchEvent('connect');
            return;
        }

        // Reset when connecting to work with Turbolinks
        this.clear();

        // Clear on click on clear button
        this.previewClearButtonTarget.addEventListener('click', this.clear);

        // Listen on input change and display preview
        this.inputTarget.addEventListener('change', this.onInputChange);

        // Add dragenter event listener
        this.element.addEventListener('dragenter', this.onDragEnter);

        // Add dragleave event listener
        this.element.addEventListener('dragleave', this.onDragLeave);

        // Add drop event listener
        this.element.addEventListener('drop', this.onDrop);

        this.dispatchEvent('connect');
    }

    disconnect() {
        this.generation++;

        if (this.multipleValue) {
            this.inputTarget.removeEventListener('change', this.onMultipleChange);
            return;
        }

        this.previewClearButtonTarget.removeEventListener('click', this.clear);
        this.inputTarget.removeEventListener('change', this.onInputChange);
        this.element.removeEventListener('dragenter', this.onDragEnter);
        this.element.removeEventListener('dragleave', this.onDragLeave);
        this.element.removeEventListener('drop', this.onDrop);
    }

    clear() {
        // The selections still processed must not bring a file back
        this.generation++;
        this.reset();
    }

    private reset() {
        this.inputTarget.value = '';
        this.inputTarget.style.display = 'block';
        this.placeholderTarget.style.display = 'block';
        this.previewTarget.style.display = 'none';
        this.previewImageTarget.style.display = 'none';
        this.previewImageTarget.style.backgroundImage = 'none';
        this.previewFilenameTarget.textContent = '';

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
        if (typeof FileReader === 'undefined') {
            // FileReader API not available, skip
            return;
        }

        const reader = new FileReader();

        reader.addEventListener('load', (event: any) => {
            target.style.display = 'block';
            target.style.backgroundImage = `url("${event.target.result}")`;
        });

        reader.readAsDataURL(file);
    }

    onDragEnter() {
        this.inputTarget.style.display = 'block';
        this.placeholderTarget.style.display = 'block';
        this.previewTarget.style.display = 'none';
    }

    onDragLeave(event: any) {
        event.preventDefault();

        // Check if we really leave the main drag area
        if (!this.element.contains(event.relatedTarget as Node)) {
            this.inputTarget.style.display = 'none';
            this.placeholderTarget.style.display = 'none';
            this.previewTarget.style.display = 'block';
        }
    }

    onDrop() {
        // Chrome does not fire "change" when the very same file is picked again, so
        // onInputChange never runs to undo what onDragEnter did and the zone looks empty
        // while a file is still selected. Deferred so input.files and any "change" landed first.
        setTimeout(() => {
            // While the dropped files are processed, the preview is shown once they are added
            if (!this.inputTarget.files?.length || this.processingCount > 0) {
                return;
            }

            this.inputTarget.style.display = 'none';
            this.placeholderTarget.style.display = 'none';
            this.previewTarget.style.display = 'flex';
        });
    }

    // --- Multiple-file handling ------------------------------------------------

    private connectMultiple() {
        this.dataTransfer = new DataTransfer();

        // Reseed from the input in case the controller reconnects with files already selected
        for (const file of Array.from(this.inputTarget.files ?? [])) {
            this.dataTransfer.items.add(file);
        }

        this.inputTarget.addEventListener('change', this.onMultipleChange);
        this.renderList();
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
        // Hide the input and placeholder
        this.inputTarget.style.display = 'none';
        this.placeholderTarget.style.display = 'none';

        // Show the filename in preview
        this.previewFilenameTarget.textContent = file.name;
        this.previewTarget.style.display = 'flex';

        // If the file is an image, load it and display it as preview
        this.previewImageTarget.style.display = 'none';
        if (file.type && file.type.indexOf('image') !== -1) {
            this._populateImagePreview(this.previewImageTarget, file);
        }

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
        this.element.setAttribute('data-state', 'processing');

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
                if (this.processingCount === 0) {
                    this.element.removeAttribute('data-state');
                }
            });
    }

    private syncMultiple() {
        this.inputTarget.files = this.dataTransfer.files;
        this.renderList();
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

    private buildListItem(file: File, index: number): HTMLLIElement {
        const item = document.createElement('li');
        item.className = 'dropzone-preview-list-item';

        const image = document.createElement('div');
        image.className = 'dropzone-preview-image';
        image.style.display = 'none';
        if (file.type && file.type.indexOf('image') !== -1) {
            this._populateImagePreview(image, file);
        }

        const filename = document.createElement('span');
        filename.className = 'dropzone-preview-filename';
        // Always use textContent: file names are user input and must never be treated as HTML
        filename.textContent = file.name;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'dropzone-preview-list-remove';
        remove.setAttribute('aria-label', `${this.removeLabelValue} ${file.name}`);
        remove.addEventListener('click', () => this.removeFileAt(index, file));

        item.append(image, filename, remove);

        return item;
    }

    private dispatchEvent(name: string, payload: any = {}, cancelable = false) {
        return this.dispatch(name, { detail: payload, prefix: 'dropzone', cancelable });
    }
}
