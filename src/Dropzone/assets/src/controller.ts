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

    static targets = ['input', 'placeholder', 'preview', 'previewClearButton', 'previewFilename', 'previewImage'];

    initialize() {
        this.clear = this.clear.bind(this);
        this.onInputChange = this.onInputChange.bind(this);
        this.onDragEnter = this.onDragEnter.bind(this);
        this.onDragLeave = this.onDragLeave.bind(this);
    }

    connect() {
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
        this.previewClearButtonTarget.removeEventListener('click', this.clear);
        this.inputTarget.removeEventListener('change', this.onInputChange);
        this.element.removeEventListener('dragenter', this.onDragEnter);
        this.element.removeEventListener('dragleave', this.onDragLeave);
    }

    clear() {
        this.inputTarget.value = '';
        this.inputTarget.style.display = 'block';
        this.placeholderTarget.style.display = 'block';
        this.previewTarget.innerHTML = '';
        this.previewTarget.style.display = 'none';
        this.element.classList.remove('dropzone-on-drag-enter');

        this.dispatchEvent('clear');
    }

    onInputChange(event: any) {
        const files = event.target.files;
        if (files.length === 0) {
            this.previewClearButtonTarget.style.display = 'none';
            return;
        }

        // Hide the input and placeholder
        this.inputTarget.style.display = 'none';
        this.placeholderTarget.style.display = 'none';

        // Clear previous previews
        this.previewTarget.innerHTML = '';

        for (const file of files) {
            // Create a container for each file preview
            const filePreviewContainer = document.createElement('div');
            filePreviewContainer.classList.add('dropzone-preview-file');

            // Create a filename preview element
            const fileNameElement = document.createElement('span');
            fileNameElement.textContent = file.name;
            filePreviewContainer.appendChild(fileNameElement);

            // Create an image preview element if the file is an image, else a default svg file icon
            if (file.type) {
                const imagePreviewElement = document.createElement('div');

                if (file.type.indexOf('image') !== -1) {
                    imagePreviewElement.classList.add('dropzone-preview-image');
                    this._populateImagePreview(file, imagePreviewElement);
                } else {
                    const noPreviewSvg =
                        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M14 11a3 3 0 0 1-3-3V4H7a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-8zm-2-3a2 2 0 0 0 2 2h3.59L12 4.41zM7 3h5l7 7v9a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3"/></svg>';
                    imagePreviewElement.innerHTML = noPreviewSvg;

                    imagePreviewElement.classList.add('dropzone-no-preview');
                }

                filePreviewContainer.appendChild(imagePreviewElement);
            }

            // Append the file preview container to the main preview target
            this.previewTarget.appendChild(filePreviewContainer);

            this.dispatchEvent('change', file);
        }

        // Show the preview container
        this.previewTarget.style.display = 'grid';
    }

    _populateImagePreview(file: Blob, imagePreviewElement: HTMLElement) {
        if (typeof FileReader === 'undefined') {
            // FileReader API not available, skip
            return;
        }

        const reader = new FileReader();
        reader.addEventListener('load', (event: any) => {
            imagePreviewElement.style.backgroundImage = `url("${event.target.result}")`;
            imagePreviewElement.style.display = 'block';
        });

        reader.readAsDataURL(file);
    }

    onDragEnter() {
        this.inputTarget.style.display = 'block';
        this.placeholderTarget.style.display = 'block';
        this.previewTarget.style.display = 'none';
        this.element.classList.add('dropzone-on-drag-enter');
        this.element.classList.remove('dropzone-on-drag-leave');
    }

    onDragLeave(event: any) {
        event.preventDefault();

        // Check if we really leave the main drag area
        if (!this.element.contains(event.relatedTarget as Node)) {
            this.inputTarget.style.display = 'none';
            this.placeholderTarget.style.display = 'none';
            this.previewTarget.style.display = 'block';
            this.element.classList.remove('dropzone-on-drag-enter');
            this.element.classList.add('dropzone-on-drag-leave');
        }
    }

    private dispatchEvent(name: string, payload: any = {}) {
        this.dispatch(name, { detail: payload, prefix: 'dropzone' });
    }
}
