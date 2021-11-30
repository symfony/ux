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
import Cropper from 'cropperjs';
import CropEvent = Cropper.CropEvent;

export default class CropperController extends Controller {
    static values = {
        publicUrl: String,
        viewMode: Number,
        dragMode: String,
        responsive: Boolean,
        restore: Boolean,
        checkCrossOrigin: Boolean,
        checkOrientation: Boolean,
        modal: Boolean,
        guides: Boolean,
        center: Boolean,
        highlight: Boolean,
        background: Boolean,
        autoCrop: Boolean,
        autoCropArea: Number,
        movable: Boolean,
        rotatable: Boolean,
        scalable: Boolean,
        zoomable: Boolean,
        zoomOnTouch: Boolean,
        zoomOnWheel: Boolean,
        wheelZoomRatio: Number,
        cropBoxMovable: Boolean,
        cropBoxResizable: Boolean,
        toggleDragModeOnDblclick: Boolean,
        minContainerWidth: Number,
        minContainerHeight: Number,
        minCanvasWidth: Number,
        minCanvasHeight: Number,
        minCropBoxWidth: Number,
        minCropBoxHeight: Number,
        aspectRatio: Number,
        initialAspectRatio: Number,
    };

    connect() {
        // Create image view
        const img = document.createElement('img');
        img.classList.add('cropperjs-image');
        img.src = (this as any).publicUrlValue;

        const parent = (this.element as HTMLInputElement).parentNode;
        if (!parent) {
            throw new Error('Missing parent node for Cropperjs');
        }

        parent.appendChild(img);

        // Build the cropper
        let options: any = {};
        for (let name in CropperController.values) {
            if ((this as any)['has' + name.charAt(0).toUpperCase() + name.slice(1) + 'Value']) {
                options[name] = (this as any)[name + 'Value'];
            }
        }

        const cropper = new Cropper(img, options);

        img.addEventListener('crop', (event) => {
            (this.element as HTMLInputElement).value = JSON.stringify((event as CropEvent).detail);
        });

        this._dispatchEvent('cropperjs:connect', { cropper, options, img });
    }

    _dispatchEvent(name: string, payload: any = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);

        this.element.dispatchEvent(userEvent);
    }
}
