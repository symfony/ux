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

    initialize() {
        this.clear = this.clear.bind(this);
        this.onInputChange = this.onInputChange.bind(this);
        this.onDragEnter = this.onDragEnter.bind(this);
        this.onDragLeave = this.onDragLeave.bind(this);
        this.onDrop = this.onDrop.bind(this);
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

        // Add drop event listener
        this.element.addEventListener('drop', this.onDrop);

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
        this.readAsDataUrl(file, (url) => {
            target.style.display = 'block';
            target.style.backgroundImage = `url("${url}")`;
        });
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

    private buildListItem(file: File, index: number): Element {
        if (this.previewItem) {
            return this.buildListItemFromTemplate(this.previewItem, file, index);
        }

        const item = document.createElement('li');
        item.className = 'dropzone-preview-list-item';

        const image = document.createElement('div');
        image.className = 'dropzone-preview-image';
        image.style.display = 'none';
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

    private dispatchEvent(name: string, payload: any = {}) {
        this.dispatch(name, { detail: payload, prefix: 'dropzone' });
    }
}
