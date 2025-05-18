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
    declare readonly placeholderTarget: HTMLElement;
    declare readonly previewTargets: HTMLElement[];
    declare readonly previewContainerTarget: HTMLElement;
    declare readonly previewTemplateTarget: HTMLTemplateElement;
    declare readonly optionsValue: any;

    static values = {
        // Default required for `legacy` style since options aren't bound in legacy controller markup
        // Remove default when `legacy` style is deprecated, as defaults are set in form options
        options: {
            type: Object,
            default: {
                preview: {
                    style: 'legacy',
                    can_open_file_picker: true,
                    can_toggle_placeholder: true,
                },
            },
        },
    };

    static targets = ['input', 'placeholder', 'preview', 'previewContainer', 'previewTemplate'];

    files: Map<string, File> = new Map<string, File>();

    initialize() {
        this.clear = this.clear.bind(this);
        this.onInputChange = this.onInputChange.bind(this);
        this.onDragLeave = this.onDragLeave.bind(this);
        this.onDragOver = this.onDragOver.bind(this);
        this.onDrop = this.onDrop.bind(this);
        this.onPreviewButtonClick = this.onPreviewButtonClick.bind(this);
        this.onPreviewContainerClick = this.onPreviewContainerClick.bind(this);
    }

    connect() {
        // Reset when connecting to work with Turbolinks
        this.clear();

        // Listen on input change and display preview
        this.inputTarget.addEventListener('change', this.onInputChange);

        // Add dragleave event listener
        this.element.addEventListener('dragleave', this.onDragLeave);

        // Add dragover event listener
        this.element.addEventListener('dragover', this.onDragOver);

        // Add drop event listener
        this.element.addEventListener('drop', this.onDrop);

        // Show file picker when preview container is clicked
        if (!this.isLegacy && this.optionsValue.preview.can_open_file_picker) {
            this.previewContainerTarget.addEventListener('click', this.onPreviewContainerClick);
        }

        this.dispatchEvent('connect');
    }

    disconnect() {
        this.clear();
        this.inputTarget.removeEventListener('change', this.onInputChange);
        this.element.removeEventListener('dragleave', this.onDragLeave);
        this.element.removeEventListener('dragover', this.onDragOver);
        this.element.removeEventListener('drop', this.onDrop);
        if (!this.isLegacy && this.optionsValue.preview.can_open_file_picker) {
            this.previewContainerTarget.removeEventListener('click', this.onPreviewContainerClick);
        }
    }

    clear() {
        this.files.clear();
        this.updateFileInput();
        this.refreshPreview();
        this.element.classList.remove('dropzone-active');

        if (this.isLegacy) {
            this.showLegacyFileInput();
        }

        this.dispatchEvent('clear');
    }

    onInputChange(event: any) {
        const files = (<File[]>Array.from(event.target.files)).filter((file) => typeof file !== 'undefined');
        if (files.length === 0) {
            return;
        }

        this.files.clear();
        this.addFiles(files);
        this.refreshPreview();

        this.dispatchEvent('change', this.isLegacy ? this.firstFile : Array.from(this.files.values()));
    }

    onDragLeave(event: any) {
        event.preventDefault();

        // Check if we really leave the main drag area
        if (!this.element.contains(event.relatedTarget as Node)) {
            this.element.classList.remove('dropzone-active');

            if (this.isLegacy) {
                this.hideLegacyFileInput();
                this.showLegacyPreview();
            }
        }
    }

    onDragOver(event: any) {
        event.preventDefault();
        this.element.classList.add('dropzone-active');

        if (this.isLegacy) {
            this.hideLegacyPreview();
            this.showLegacyFileInput();
        }
    }

    onDrop(event: any) {
        event.preventDefault();

        const files = (<File[]>Array.from(event.dataTransfer.files)).filter((file) => typeof file !== 'undefined');
        if (files.length === 0) {
            return;
        }

        if (!this.isMultiple) {
            this.files.clear();
        }

        this.addFiles(files);
        this.updateFileInput();
        this.refreshPreview();
        this.element.classList.remove('dropzone-active');

        this.dispatchEvent('change', Array.from(this.files.values()));
    }

    onPreviewContainerClick(event: any) {
        event.stopPropagation();
        this.inputTarget.click();
    }

    onPreviewButtonClick(event: any) {
        event.stopPropagation();

        if (this.isLegacy) {
            return this.clear();
        }

        const button = event.currentTarget;
        button.removeEventListener('click', this.onPreviewButtonClick);

        const preview = button.closest('.dropzone-preview');
        preview.remove();

        if (!button.dataset.filename) {
            return;
        }
        this.files.delete(button.dataset.filename);

        this.updateFileInput();
        this.refreshPreview();
    }

    private dispatchEvent(name: string, payload: any = {}) {
        this.dispatch(name, { detail: payload, prefix: 'dropzone' });
    }

    private addFiles(files: File[]) {
        for (const file of files) {
            this.files.set(file.name, file);
        }
    }

    private buildPreview(file: File, el?: HTMLElement): HTMLElement {
        if (!el) {
            el = this.previewTemplateTarget.content.firstElementChild?.cloneNode(true) as HTMLElement;
        }

        const button = <HTMLElement>el.querySelector('.dropzone-preview-button');
        if (button) {
            button.dataset.filename = file.name;
            button.addEventListener('click', this.onPreviewButtonClick);
        }

        const filename = el.querySelector('.dropzone-preview-filename');
        if (filename) {
            filename.textContent = file.name;
        }

        const size = el.querySelector('.dropzone-preview-file-size');
        if (size) {
            size.textContent = this.formatBytes(file.size);
        }

        const image = <HTMLElement>el.querySelector('.dropzone-preview-image');

        if (image && this.isImage(file) && typeof FileReader !== 'undefined') {
            // If the file is an image, load it and display it as preview
            const reader = new FileReader();

            image.classList.add('dropzone-preview-image-hidden');
            reader.addEventListener('load', (event: any) => {
                image.querySelector('.dropzone-preview-image-placeholder')?.remove();
                image.style.backgroundImage = `url('${event.target.result}')`;
                image.classList.remove('dropzone-preview-image-hidden');
            });

            reader.readAsDataURL(file as Blob);
        }

        return el;
    }

    private refreshPreview() {
        if (this.isLegacy) {
            return this.refreshLegacyPreview();
        }

        this.element.classList.add('dropzone-preview-container-hidden');

        for (const preview of this.previewTargets) {
            preview.querySelector('.dropzone-preview-button')?.removeEventListener('click', this.onPreviewButtonClick);
            preview.remove();
        }

        for (const file of this.files.values()) {
            const preview = this.buildPreview(file);
            this.previewContainerTarget.appendChild(preview);
        }

        if (this.previewTargets.length > 0) {
            this.element.classList.remove('dropzone-preview-container-hidden');
        }

        const canToggle = this.optionsValue.preview.can_toggle_placeholder;
        if (canToggle) {
            const hide =
                this.previewTargets.length > 0 &&
                (canToggle === true || (canToggle === 'auto' && this.previewTargets.length < 2));
            this.element.classList.toggle('dropzone-placeholder-hidden', hide);
        }
    }

    private isImage(file: File): boolean {
        return typeof file.type !== 'undefined' && file.type.indexOf('image') !== -1;
    }

    private get isMultiple(): boolean {
        return this.inputTarget.multiple;
    }

    private updateFileInput() {
        const dataTransfer = new DataTransfer();
        for (const file of this.files.values()) {
            dataTransfer.items.add(file);
        }
        this.inputTarget.files = dataTransfer.files;
    }

    // Credit: [Pawel Zentala](https://github.com/zentala)
    // https://gist.github.com/zentala/1e6f72438796d74531803cc3833c039c
    private formatBytes(bytes: number, decimals = 2): string {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals || 2;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${Number.parseFloat((bytes / k ** i).toFixed(dm))} ${sizes[i]}`;
    }

    //
    // Legacy methods
    //

    private get firstFile(): File | undefined {
        return this.files.values().next().value;
    }

    private get isLegacy(): boolean {
        return this.optionsValue.preview.style === 'legacy';
    }

    private refreshLegacyPreview() {
        const preview = this.previewTargets[0];
        const image = <HTMLElement>preview.querySelector('.dropzone-preview-image');
        const filename = preview.querySelector('.dropzone-preview-filename');

        const file = this.firstFile;

        if (!file) {
            this.hideLegacyPreview();

            if (filename) {
                filename.textContent = '';
            }

            if (image) {
                image.style.display = 'none';
                image.style.backgroundImage = 'none';
            }

            return;
        }

        this.buildPreview(file, preview);

        const fileCount = this.files.size;
        if (filename && fileCount > 1) {
            filename.textContent += ` +${fileCount - 1}`;
            (<HTMLElement>filename).title = Array.from(this.files.values())
                .map((file: File) => file.name)
                .join('\n');
        }

        if (image) {
            if (this.isImage(file)) {
                image.style.display = 'block';
            } else {
                image.style.display = 'none';
                image.style.backgroundImage = 'none';
            }
        }

        this.showLegacyPreview();
        this.hideLegacyFileInput();
    }

    private showLegacyPreview() {
        this.previewTargets[0].style.display = 'flex';
    }

    private hideLegacyPreview() {
        this.previewTargets[0].style.display = 'none';
    }

    private showLegacyFileInput() {
        this.inputTarget.style.display = 'block';
        this.placeholderTarget.style.display = 'block';
    }

    private hideLegacyFileInput() {
        this.inputTarget.style.display = 'none';
        this.placeholderTarget.style.display = 'none';
    }
}
