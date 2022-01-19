import { Controller } from '@hotwired/stimulus';
import Cropper from 'cropperjs';

class CropperController extends Controller {
    connect() {
        const img = document.createElement('img');
        img.classList.add('cropperjs-image');
        img.src = this.publicUrlValue;
        const parent = this.element.parentNode;
        if (!parent) {
            throw new Error('Missing parent node for Cropperjs');
        }
        parent.appendChild(img);
        const options = this.optionsValue;
        this._dispatchEvent('cropperjs:pre-connect', { options, img });
        const cropper = new Cropper(img, options);
        img.addEventListener('crop', (event) => {
            this.element.value = JSON.stringify(event.detail);
        });
        this._dispatchEvent('cropperjs:connect', { cropper, options, img });
    }
    _dispatchEvent(name, payload) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
CropperController.values = {
    publicUrl: String,
    options: Object,
};

export { CropperController as default };
