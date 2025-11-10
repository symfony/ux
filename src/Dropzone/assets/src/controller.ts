/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    declare readonly inputTarget: HTMLInputElement;
    declare readonly placeholderTarget: HTMLDivElement;
    declare readonly previewTargets: HTMLDivElement[];
    declare readonly previewContainerTarget: HTMLDivElement;

    static targets = ['input', 'placeholder', 'preview', 'previewClearButton', 'previewFilename', 'previewImage', 'previewContainer'];

    files: Map<string, File> = new Map<string, File>();

    initialize() {
        this.clear = this.clear.bind(this);
        this.onInputChange = this.onInputChange.bind(this);
        this.onDragEnter = this.onDragEnter.bind(this);
        this.onDragLeave = this.onDragLeave.bind(this);
    }

    connect() {
        // Reset when connecting to work with Turbolinks
        this.clear();

        // Listen on input change and display preview
        this.inputTarget.addEventListener('change', this.onInputChange);

        // Add dragenter event listener
        this.element.addEventListener('dragenter', this.onDragEnter);

        // Add dragleave event listener
        this.element.addEventListener('dragleave', this.onDragLeave);

        this.dispatchEvent('connect');
    }

    disconnect() {
        this.inputTarget.removeEventListener('change', this.onInputChange);
        this.element.removeEventListener('dragenter', this.onDragEnter);
        this.element.removeEventListener('dragleave', this.onDragLeave);
    }

    clear(event?: { target?: HTMLElement; params?: { filename?: string } }) {
        if (event?.params) {
            const filename = event.params.filename;
            if (filename && this.files.has(filename)) {
                this.files.delete(filename);
                this.updateFileInput();
                this.renderPreview();
            }
        }
        if (!this.inputTarget || !this.inputTarget.files || this.inputTarget?.files?.length === 0) {
            this.placeholderTarget.style.display = "block";
            if (!this.isMultiple) {
                this.inputTarget.style.display = "block";
            }
        }

        this.dispatchEvent("clear");
    }

    onInputChange() {
        const files = this.inputTarget.files;

        if (!files || files.length <= 0) {
            return;
        }

        if (!this.isMultiple && this.files.size > 0) {
            this.inputTarget.style.display = "none";
        }

        const selectedFiles = this.isMultiple ? Array.from(files) : Array.from(files).slice(0, 1);
        this.addFiles(selectedFiles);
        this.updateFileInput();
        this.renderPreview();
        this.dispatchEvent("change", files);
    }

    private renderPreview() {
        this.clearPreviewContainer();
        for (const file of this.files.values()) {
            const preview = this.buildPreview(file);
            if (preview) {
                this.previewContainerTarget.appendChild(preview);
            }
        }

        if (this.previewTargets.length > 1) {
            this.placeholderTarget.style.display = 'none';
            if (!this.isMultiple) {
                this.inputTarget.style.display = 'none';
            } else {
                this.inputTarget.style.display = 'block';
            }
        }
    }

    private clearPreviewContainer() {
        const previews = this.previewTargets;
        previews.slice(1).forEach(el => el.remove());
    }

    private buildPreview(file: File, element?: HTMLElement): HTMLElement {
        if (!element) {
            element = this.previewContainerTarget.firstElementChild?.cloneNode(true) as HTMLElement;
        }
        element.style.display = 'flex';

        const fileName = element.querySelector('.dropzone-preview-filename');
        if (fileName) {
            fileName.textContent = file.name
        }

        const button = element.querySelector('.dropzone-preview-button');
        if (button) {
            button.setAttribute('data-symfony--ux-dropzone--dropzone-filename-param', file.name);
        }

        this._populateImagePreview(element, file);
        return element;
    }

    _populateImagePreview(element: HTMLElement, file: File) {
        const image = <HTMLElement>element.querySelector('.dropzone-preview-image');

        if (image && this.isImage(file) && typeof FileReader !== 'undefined') {
            const reader = new FileReader();

            reader.addEventListener('load', (event: any) => {
                image.querySelector('.dropzone-preview-image')?.remove();
                image.style.backgroundImage = `url('${event.target.result}')`;
                image.style.display = 'block';
            });

            reader.readAsDataURL(file as Blob);
        }
    }

    onDragEnter() {
        this.inputTarget.style.display = 'block';
    }

    onDragLeave(event: any) {
        event.preventDefault();
    }

    private updateFileInput() {
        const dataTransfer = new DataTransfer();
        for (const file of this.files.values()) {
            dataTransfer.items.add(file);
        }
        this.inputTarget.files = dataTransfer.files;
    }

    private addFiles(files: File[]) {
        for (const file of files) {
            this.files.set(file.name, file);
        }
    }

    private isImage(file: File): boolean {
        return typeof file.type !== 'undefined' && file.type.indexOf('image') !== -1;
    }

    private get isMultiple(): boolean {
        return this.inputTarget.multiple;
    }

    private dispatchEvent(name: string, payload: any = {}) {
        this.dispatch(name, {detail: payload, prefix: 'dropzone'});
    }
}
