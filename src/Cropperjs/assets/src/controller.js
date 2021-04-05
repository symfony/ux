/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Controller } from 'stimulus';
import Cropper from 'cropperjs';

export default class extends Controller {
    connect() {
        // Create image view
        const img = document.createElement('img');
        img.classList.add('cropperjs-image');
        img.src = this.element.getAttribute('data-public-url');

        const parent = this.element.parentNode;
        parent.appendChild(img);

        // Build the cropper
        let options = {
            viewMode: parseInt(this.element.getAttribute('data-view-mode')),
            dragMode: this.element.getAttribute('data-drag-mode'),
            responsive: this.element.hasAttribute('data-responsive'),
            restore: this.element.hasAttribute('data-restore'),
            checkCrossOrigin: this.element.hasAttribute('data-check-cross-origin'),
            checkOrientation: this.element.hasAttribute('data-check-orientation'),
            modal: this.element.hasAttribute('data-modal'),
            guides: this.element.hasAttribute('data-guides'),
            center: this.element.hasAttribute('data-center'),
            highlight: this.element.hasAttribute('data-highlight'),
            background: this.element.hasAttribute('data-background'),
            autoCrop: this.element.hasAttribute('data-auto-crop'),
            autoCropArea: parseFloat(this.element.getAttribute('data-auto-crop-area')),
            movable: this.element.hasAttribute('data-movable'),
            rotatable: this.element.hasAttribute('data-rotatable'),
            scalable: this.element.hasAttribute('data-scalable'),
            zoomable: this.element.hasAttribute('data-zoomable'),
            zoomOnTouch: this.element.hasAttribute('data-zoom-on-touch'),
            zoomOnWheel: this.element.hasAttribute('data-zoom-on-wheel'),
            wheelZoomRatio: parseFloat(this.element.getAttribute('data-wheel-zoom-ratio')),
            cropBoxMovable: this.element.hasAttribute('data-crop-box-movable'),
            cropBoxResizable: this.element.hasAttribute('data-crop-box-resizable'),
            toggleDragModeOnDblclick: this.element.hasAttribute('data-toggle-drag-mode-on-dblclick'),
            minContainerWidth: parseInt(this.element.getAttribute('data-min-container-width')),
            minContainerHeight: parseInt(this.element.getAttribute('data-min-container-height')),
            minCanvasWidth: parseInt(this.element.getAttribute('data-min-canvas-width')),
            minCanvasHeight: parseInt(this.element.getAttribute('data-min-canvas-height')),
            minCropBoxWidth: parseInt(this.element.getAttribute('data-min-crop-box-width')),
            minCropBoxHeight: parseInt(this.element.getAttribute('data-min-crop-box-height')),
        };

        if (this.element.getAttribute('data-aspect-ratio')) {
            options.aspectRatio = parseFloat(this.element.getAttribute('data-aspect-ratio'));
        }

        if (this.element.getAttribute('data-initial-aspect-ratio')) {
            options.initialAspectRatio = parseFloat(this.element.getAttribute('data-initial-aspect-ratio'));
        }

        const cropper = new Cropper(img, options);

        img.addEventListener('crop', (event) => {
            this.element.value = JSON.stringify(event.detail);
        });

        this._dispatchEvent('cropperjs:connect', { cropper, options, img });
    }

    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);

        this.element.dispatchEvent(userEvent);
    }
}
