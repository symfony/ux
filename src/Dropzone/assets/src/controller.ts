/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    readonly inputTarget: HTMLInputElement;
    readonly placeholderTarget: HTMLDivElement;
    readonly previewTarget: HTMLDivElement;
    readonly previewClearButtonTarget: HTMLButtonElement;
    readonly previewFilenameTarget: HTMLDivElement;
    readonly previewImageTarget: HTMLDivElement;

    static targets = ['input', 'placeholder', 'preview', 'previewClearButton', 'previewFilename', 'previewImage'];

    connect() {
        // Reset when connecting to work with Turbolinks
        this.clear();

        // Clear on click on clear button
        this.previewClearButtonTarget.addEventListener('click', () => this.clear());

        // Listen on input change and display preview
        this.inputTarget.addEventListener('change', (event) => this.onInputChange(event));

        this._dispatchEvent('dropzone:connect');
    }

    clear() {
        this.inputTarget.value = '';
        this.inputTarget.style.display = 'block';
        this.placeholderTarget.style.display = 'block';
        this.previewTarget.style.display = 'none';
        this.previewImageTarget.style.display = 'none';
        this.previewImageTarget.style.backgroundImage = 'none';
        this.previewFilenameTarget.textContent = '';

        this._dispatchEvent('dropzone:clear');
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
            this._populateImagePreview(file);
        }

        this._dispatchEvent('dropzone:change', file);
    }

    _populateImagePreview(file: Blob) {
        if (typeof FileReader === 'undefined') {
            // FileReader API not available, skip
            return;
        }

        const reader = new FileReader();

        reader.addEventListener('load', (event: any) => {
            this.previewImageTarget.style.display = 'block';
            this.previewImageTarget.style.backgroundImage = 'url("' + event.target.result + '")';
        });

        reader.readAsDataURL(file);
    }

    _dispatchEvent(name: string, payload: any = {}) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
