import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.files = new Map();
    }
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
        this.clear();
        this.inputTarget.addEventListener('change', this.onInputChange);
        this.element.addEventListener('dragleave', this.onDragLeave);
        this.element.addEventListener('dragover', this.onDragOver);
        this.element.addEventListener('drop', this.onDrop);
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
    onInputChange(event) {
        const files = Array.from(event.target.files).filter((file) => typeof file !== 'undefined');
        if (files.length === 0) {
            return;
        }
        this.files.clear();
        this.addFiles(files);
        this.refreshPreview();
        this.dispatchEvent('change', this.isLegacy ? this.firstFile : Array.from(this.files.values()));
    }
    onDragLeave(event) {
        event.preventDefault();
        if (!this.element.contains(event.relatedTarget)) {
            this.element.classList.remove('dropzone-active');
            if (this.isLegacy) {
                this.hideLegacyFileInput();
                this.showLegacyPreview();
            }
        }
    }
    onDragOver(event) {
        event.preventDefault();
        this.element.classList.add('dropzone-active');
        if (this.isLegacy) {
            this.hideLegacyPreview();
            this.showLegacyFileInput();
        }
    }
    onDrop(event) {
        event.preventDefault();
        const files = Array.from(event.dataTransfer.files).filter((file) => typeof file !== 'undefined');
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
    onPreviewContainerClick(event) {
        event.stopPropagation();
        this.inputTarget.click();
    }
    onPreviewButtonClick(event) {
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
    dispatchEvent(name, payload = {}) {
        this.dispatch(name, { detail: payload, prefix: 'dropzone' });
    }
    addFiles(files) {
        for (const file of files) {
            this.files.set(file.name, file);
        }
    }
    buildPreview(file, el) {
        if (!el) {
            el = this.previewTemplateTarget.content.firstElementChild?.cloneNode(true);
        }
        const button = el.querySelector('.dropzone-preview-button');
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
        const image = el.querySelector('.dropzone-preview-image');
        if (image && this.isImage(file) && typeof FileReader !== 'undefined') {
            const reader = new FileReader();
            image.classList.add('dropzone-preview-image-hidden');
            reader.addEventListener('load', (event) => {
                image.querySelector('.dropzone-preview-image-placeholder')?.remove();
                image.style.backgroundImage = `url('${event.target.result}')`;
                image.classList.remove('dropzone-preview-image-hidden');
            });
            reader.readAsDataURL(file);
        }
        return el;
    }
    refreshPreview() {
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
            const hide = this.previewTargets.length > 0 &&
                (canToggle === true || (canToggle === 'auto' && this.previewTargets.length < 2));
            this.element.classList.toggle('dropzone-placeholder-hidden', hide);
        }
    }
    isImage(file) {
        return typeof file.type !== 'undefined' && file.type.indexOf('image') !== -1;
    }
    get isMultiple() {
        return this.inputTarget.multiple;
    }
    updateFileInput() {
        const dataTransfer = new DataTransfer();
        for (const file of this.files.values()) {
            dataTransfer.items.add(file);
        }
        this.inputTarget.files = dataTransfer.files;
    }
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0)
            return '0 Bytes';
        const k = 1024;
        const dm = decimals || 2;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${Number.parseFloat((bytes / k ** i).toFixed(dm))} ${sizes[i]}`;
    }
    get firstFile() {
        return this.files.values().next().value;
    }
    get isLegacy() {
        return this.optionsValue.preview.style === 'legacy';
    }
    refreshLegacyPreview() {
        const preview = this.previewTargets[0];
        const image = preview.querySelector('.dropzone-preview-image');
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
            filename.title = Array.from(this.files.values())
                .map((file) => file.name)
                .join('\n');
        }
        if (image) {
            if (this.isImage(file)) {
                image.style.display = 'block';
            }
            else {
                image.style.display = 'none';
                image.style.backgroundImage = 'none';
            }
        }
        this.showLegacyPreview();
        this.hideLegacyFileInput();
    }
    showLegacyPreview() {
        this.previewTargets[0].style.display = 'flex';
    }
    hideLegacyPreview() {
        this.previewTargets[0].style.display = 'none';
    }
    showLegacyFileInput() {
        this.inputTarget.style.display = 'block';
        this.placeholderTarget.style.display = 'block';
    }
    hideLegacyFileInput() {
        this.inputTarget.style.display = 'none';
        this.placeholderTarget.style.display = 'none';
    }
}
default_1.values = {
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
default_1.targets = ['input', 'placeholder', 'preview', 'previewContainer', 'previewTemplate'];

export { default_1 as default };
