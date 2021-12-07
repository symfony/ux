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
        options: Object,
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

        const options = this.optionsValue;
        this._dispatchEvent('cropperjs:pre-connect', { options, img });

        // Build the cropper
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
