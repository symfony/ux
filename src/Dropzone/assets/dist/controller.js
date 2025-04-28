import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    initialize() {
        this.clear = this.clear.bind(this);
        this.onInputChange = this.onInputChange.bind(this);
        this.onDragEnter = this.onDragEnter.bind(this);
        this.onDragLeave = this.onDragLeave.bind(this);
    }
    connect() {
        this.clear();
        this.previewClearButtonTarget.addEventListener('click', this.clear);
        this.inputTarget.addEventListener('change', this.onInputChange);
        this.element.addEventListener('dragenter', this.onDragEnter);
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
    onInputChange(event) {
        const files = event.target.files;
        if (files.length === 0) {
            this.previewClearButtonTarget.style.display = 'none';
            return;
        }
        this.inputTarget.style.display = 'none';
        this.placeholderTarget.style.display = 'none';
        this.previewTarget.innerHTML = '';
        for (const file of files) {
            const filePreviewContainer = document.createElement('div');
            filePreviewContainer.classList.add('dropzone-preview-file');
            const fileNameElement = document.createElement('span');
            fileNameElement.textContent = file.name;
            filePreviewContainer.appendChild(fileNameElement);
            if (file.type) {
                const imagePreviewElement = document.createElement('div');
                if (file.type.indexOf('image') !== -1) {
                    imagePreviewElement.classList.add('dropzone-preview-image');
                    this._populateImagePreview(file, imagePreviewElement);
                }
                else {
                    const noPreviewSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M14 11a3 3 0 0 1-3-3V4H7a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-8zm-2-3a2 2 0 0 0 2 2h3.59L12 4.41zM7 3h5l7 7v9a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3"/></svg>';
                    imagePreviewElement.innerHTML = noPreviewSvg;
                    imagePreviewElement.classList.add('dropzone-no-preview');
                }
                filePreviewContainer.appendChild(imagePreviewElement);
            }
            this.previewTarget.appendChild(filePreviewContainer);
            this.dispatchEvent('change', file);
        }
        this.previewTarget.style.display = 'grid';
    }
    _populateImagePreview(file, imagePreviewElement) {
        if (typeof FileReader === 'undefined') {
            return;
        }
        const reader = new FileReader();
        reader.addEventListener('load', (event) => {
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
    onDragLeave(event) {
        event.preventDefault();
        if (!this.element.contains(event.relatedTarget)) {
            this.inputTarget.style.display = 'none';
            this.placeholderTarget.style.display = 'none';
            this.previewTarget.style.display = 'block';
            this.element.classList.remove('dropzone-on-drag-enter');
            this.element.classList.add('dropzone-on-drag-leave');
        }
    }
    dispatchEvent(name, payload = {}) {
        this.dispatch(name, { detail: payload, prefix: 'dropzone' });
    }
}
default_1.targets = ['input', 'placeholder', 'preview', 'previewClearButton', 'previewFilename', 'previewImage'];

export { default_1 as default };
