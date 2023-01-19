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
        this.dispatchEvent('pre-connect', { options, img });
        const cropper = new Cropper(img, options);
        img.addEventListener('crop', (event) => {
            this.element.value = JSON.stringify(event.detail);
        });
        this.dispatchEvent('connect', { cropper, options, img });
    }
    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'cropperjs' });
    }
}
CropperController.values = {
    publicUrl: String,
    options: Object,
};

export { CropperController as default };
