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
        this.previewTarget.style.display = 'none';
        this.previewImageTarget.style.display = 'none';
        this.previewImageTarget.style.backgroundImage = 'none';
        this.previewFilenameTarget.textContent = '';
        this.dispatchEvent('clear');
    }
    onInputChange(event) {
        const files = event.target.files;
        if (!files.length) {
            return;
        }
        this.inputTarget.style.display = 'none';
        this.placeholderTarget.style.display = 'none';
        const firstFile = files[0];
        let displayText = firstFile.name;
        if (files.length > 1) {
            const additionalFiles = files.length - 1;
            displayText += ` +${additionalFiles} ${additionalFiles === 1 ? 'file' : 'files'}`;
        }
        this.previewFilenameTarget.textContent = displayText;
        this.previewTarget.style.display = 'flex';
        this.previewImageTarget.style.display = 'none';
        if (firstFile.type && firstFile.type.indexOf('image') !== -1) {
            this._populateImagePreview(firstFile);
        }
        this.dispatchEvent('change', files);
    }
    _populateImagePreview(file) {
        if (typeof FileReader === 'undefined') {
            return;
        }
        const reader = new FileReader();
        reader.addEventListener('load', (event) => {
            this.previewImageTarget.style.display = 'block';
            this.previewImageTarget.style.backgroundImage = `url("${event.target.result}")`;
        });
        reader.readAsDataURL(file);
    }
    onDragEnter() {
        this.inputTarget.style.display = 'block';
        this.placeholderTarget.style.display = 'block';
        this.previewTarget.style.display = 'none';
    }
    onDragLeave(event) {
        event.preventDefault();
        if (!this.element.contains(event.relatedTarget)) {
            this.inputTarget.style.display = 'none';
            this.placeholderTarget.style.display = 'none';
            this.previewTarget.style.display = 'block';
        }
    }
    dispatchEvent(name, payload = {}) {
        this.dispatch(name, { detail: payload, prefix: 'dropzone' });
    }
}
default_1.targets = ['input', 'placeholder', 'preview', 'previewClearButton', 'previewFilename', 'previewImage'];

export { default_1 as default };
