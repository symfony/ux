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
    declare readonly maxFilesValue: number;
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
        maxFiles: Number,
        removeLabel: { type: String, default: 'Remove' },
    };

    private dataTransfer!: DataTransfer;
    private shownFile: File | null = null;
    private dropping = false;

    initialize() {
        this.clear = this.clear.bind(this);
        this.onInputChange = this.onInputChange.bind(this);
        this.onDragEnter = this.onDragEnter.bind(this);
        this.onDragLeave = this.onDragLeave.bind(this);
        this.onDrop = this.onDrop.bind(this);
        this.onMultipleChange = this.onMultipleChange.bind(this);
        this.onFilesDropped = this.onFilesDropped.bind(this);
    }

    connect() {
        this.element.addEventListener('drop', this.onFilesDropped);

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
        this.element.removeEventListener('drop', this.onFilesDropped);

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
        this.inputTarget.value = '';
        this.inputTarget.style.display = 'block';
        this.placeholderTarget.style.display = 'block';
        this.previewTarget.style.display = 'none';
        this.previewImageTarget.style.display = 'none';
        this.previewImageTarget.style.backgroundImage = 'none';
        this.previewFilenameTarget.textContent = '';
        this.shownFile = null;

        this.dispatchEvent('clear');
    }

    onInputChange(event: any) {
        const files: File[] = Array.from(event.target.files ?? []);
        if (files.length === 0) {
            return;
        }

        const [accepted, rejected] = this.checkAccept(files);
        const file = accepted[0];
        if (typeof file === 'undefined') {
            // The browser replaced the file shown before with the rejected ones: put it back
            this.setInputFiles(this.shownFile ? [this.shownFile] : []);
            this.reject(rejected, 'accept');
            return;
        }

        if (files.length !== 1 || files[0] !== file) {
            this.setInputFiles([file]);
        }

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

        this.shownFile = file;
        this.dispatchEvent('change', file);
        this.reject(rejected, 'accept');
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
            if (!this.inputTarget.files?.length) {
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
        const [accepted, rejected] = this.checkAccept(Array.from(this.inputTarget.files ?? []));
        const overflow: File[] = [];
        for (const file of accepted) {
            if (this.containsFile(file)) {
                continue;
            }

            if (this.maxFilesValue > 0 && this.dataTransfer.files.length >= this.maxFilesValue) {
                overflow.push(file);
            } else {
                this.dataTransfer.items.add(file);
            }
        }

        this.syncMultiple();
        this.reject(rejected, 'accept');
        this.reject(overflow, 'max_files');
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

    private onFilesDropped() {
        // The browser puts the dropped files into the input and fires "change" right after the drop event
        this.dropping = true;
        setTimeout(() => {
            this.dropping = false;
        });
    }

    private checkAccept(files: File[]): [File[], File[]] {
        // Only drops ignore the accept attribute: the file picker filters with the file types of the operating system
        if (!this.dropping) {
            return [files, []];
        }

        const tokens = this.inputTarget.accept
            .split(',')
            .map((token) => token.trim().toLowerCase())
            .filter((token) => token !== '');
        // "*" and "*/*" are not valid tokens, and browsers do not restrict the files with them either
        if (tokens.length === 0 || tokens.some((token) => token === '*' || token === '*/*')) {
            return [files, []];
        }

        const accepted: File[] = [];
        const rejected: File[] = [];
        for (const file of files) {
            const name = file.name.toLowerCase();
            const type = file.type.toLowerCase();
            const matches = tokens.some((token) => {
                if (token.startsWith('.')) {
                    return name.endsWith(token);
                }
                // Browsers report no type for some files (HEIC photos, for example), leave them to the server
                if (type === '') {
                    return true;
                }
                if (token.endsWith('/*')) {
                    return type.startsWith(token.slice(0, -1));
                }

                return type === token;
            });

            (matches ? accepted : rejected).push(file);
        }

        return [accepted, rejected];
    }

    private setInputFiles(files: File[]) {
        const dataTransfer = new DataTransfer();
        for (const file of files) {
            dataTransfer.items.add(file);
        }
        this.inputTarget.files = dataTransfer.files;
    }

    private reject(files: File[], reason: 'accept' | 'max_files') {
        if (files.length > 0) {
            this.dispatchEvent('reject', { files, reason });
        }
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

    private dispatchEvent(name: string, payload: any = {}) {
        this.dispatch(name, { detail: payload, prefix: 'dropzone' });
    }
}
