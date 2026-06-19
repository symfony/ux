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
    };

    private dataTransfer!: DataTransfer;

    initialize() {
        this.clear = this.clear.bind(this);
        this.onInputChange = this.onInputChange.bind(this);
        this.onDragEnter = this.onDragEnter.bind(this);
        this.onDragLeave = this.onDragLeave.bind(this);
        this.onMultipleChange = this.onMultipleChange.bind(this);
    }

    connect() {
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

        this.dispatchEvent('connect');
    }

    disconnect() {
        if (this.multipleValue) {
            this.inputTarget.removeEventListener('change', this.onMultipleChange);
            return;
        }

        this.previewClearButtonTarget.removeEventListener('click', this.clear);
        this.inputTarget.removeEventListener('change', this.onInputChange);
        this.element.removeEventListener('dragenter', this.onDragEnter);
        this.element.removeEventListener('dragleave', this.onDragLeave);
    }

    clear() {
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
        const file = event.target.files[0];
        if (typeof file === 'undefined') {
            return;
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

        this.dispatchEvent('change', file);
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
        for (const file of Array.from(this.inputTarget.files ?? [])) {
            if (!this.containsFile(file)) {
                this.dataTransfer.items.add(file);
            }
        }

        this.syncMultiple();
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
        remove.addEventListener('click', () => this.removeFileAt(index, file));

        item.append(image, filename, remove);

        return item;
    }

    private dispatchEvent(name: string, payload: any = {}) {
        this.dispatch(name, { detail: payload, prefix: 'dropzone' });
    }
}
